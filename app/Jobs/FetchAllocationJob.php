<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 300 seconds max per SKU
    public int $tries = 3; // No retries, skip and move on
    public int $maxExceptions = 1; // Don't retry on exception

    protected string $sku;
    protected string $facilityId;
    protected string $warehouseCode;
    protected string $productTable;

    public function __construct(
        string $sku,
        string $facilityId,
        string $warehouseCode,
        string $productTable
    ) {
        $this->sku = $sku;
        $this->facilityId = $facilityId;
        $this->warehouseCode = $warehouseCode;
        $this->productTable = $productTable;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $processedKey = "wms_processed_{$this->warehouseCode}";
        $failedKey = "wms_failed_{$this->warehouseCode}";

        try {
            // Purge old Oracle connection to force a fresh PDO/OCI8 connection
            DB::purge('oracle_wms');
            $oracle = DB::connection('oracle_wms');
            $oracle->getPdo(); // initialize OCI8
            $oracle->select("SELECT 1 FROM DUAL"); // dummy query to force connection

            $mysql = DB::connection('mysql');
            $mysql->getPdo();

            // Set statement timeout (optional)
            try {
                $oracle->statement("ALTER SESSION SET QUERY_TIMEOUT = 5");
            } catch (\Exception $e) {
                // ignore if not supported
            }

            // Fetch allocation from Oracle
            $allocationValue = 0;
            $skuFoundInOracle = false;

            try {
                $oracleRows = $oracle->select("
                    SELECT ci.item_id,
                           SUM(CASE WHEN c.container_status NOT IN ('X','T','S','D','A') 
                                    THEN ci.unit_qty ELSE 0 END) AS total_qty
                    FROM rwms.container_item ci
                    JOIN rwms.container c
                      ON ci.facility_id = c.facility_id
                     AND ci.container_id = c.container_id
                    WHERE ci.facility_id = ?
                      AND ci.item_id = ?
                    GROUP BY ci.item_id
                ", [$this->facilityId, $this->sku]);

                if (!empty($oracleRows)) {
                    $skuFoundInOracle = true;
                    $allocationValue = (int) $oracleRows[0]->total_qty;
                }
            } catch (\Exception $e) {
                Log::warning("[FetchAllocationJob] Oracle query failed for SKU {$this->sku}: " . $e->getMessage());
                $this->markAsFailed($failedKey, $processedKey);
                return;
            }

            // Update allocation in MySQL
            try {
                $mysql->table('product_wms_allocations')->updateOrInsert(
                    [
                        'sku' => $this->sku,
                        'warehouse_code' => $this->warehouseCode
                    ],
                    [
                        'wms_actual_allocation'  => $allocationValue,
                        'wms_virtual_allocation' => $allocationValue,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning("[FetchAllocationJob] Failed to update allocation for SKU {$this->sku}: " . $e->getMessage());
                $this->markAsFailed($failedKey, $processedKey);
                return;
            }

            // Update case pack data (optional)
            try {
                $caseRows = $oracle->select("
                    SELECT item_id, unit_qty
                    FROM (
                        SELECT ci.item_id, ci.unit_qty,
                               ROW_NUMBER() OVER (PARTITION BY ci.item_id ORDER BY ci.unit_qty) rn
                        FROM rwms.container_item ci
                        WHERE ci.facility_id = ?
                          AND ci.item_id = ?
                    )
                    WHERE rn <= 5
                ", [$this->facilityId, $this->sku]);

                if (!empty($caseRows)) {
                    $casePacks = array_unique(array_map(fn($row) => $row->unit_qty, $caseRows));
                    $mysql->table($this->productTable)
                        ->where('sku', $this->sku)
                        ->update([
                            'case_pack' => implode(' | ', $casePacks),
                            'updated_at' => now()
                        ]);
                }
            } catch (\Exception $e) {
                Log::debug("[FetchAllocationJob] Case pack skipped for SKU {$this->sku}: {$e->getMessage()}");
            }

            // SUCCESS: mark as processed
            Cache::increment($processedKey);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (!$skuFoundInOracle) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: NOT FOUND | Set to 0 | {$duration}ms");
            } elseif ($allocationValue > 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: FOUND | Allocation: {$allocationValue} | {$duration}ms");
            } else {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: FOUND (ZERO STOCK) | Allocation: 0 | {$duration}ms");
            }
        } catch (\Throwable $e) {
            Log::error("[FetchAllocationJob] ✗ Unexpected error for SKU {$this->sku} | WH: {$this->warehouseCode}: " . $e->getMessage());
            $this->markAsFailed($failedKey, $processedKey);
            return;
        }
    }

    protected function markAsFailed(string $failedKey, string $processedKey): void
    {
        Cache::increment($failedKey);
        Cache::increment($processedKey);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[FetchAllocationJob] FINAL FAILURE for SKU {$this->sku} | WH: {$this->warehouseCode}: " . $exception->getMessage());
        Cache::increment("wms_failed_{$this->warehouseCode}");
        Cache::increment("wms_processed_{$this->warehouseCode}");
    }
}
