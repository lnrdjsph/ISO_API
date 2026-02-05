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

        if (!defined('OCI_DEFAULT')) {
            define('OCI_DEFAULT', 0);
        }
        if (!defined('OCI_COMMIT_ON_SUCCESS')) {
            define('OCI_COMMIT_ON_SUCCESS', 32);
        }
        if (!defined('OCI_NO_AUTO_COMMIT')) {
            define('OCI_NO_AUTO_COMMIT', 0);
        }

        $startTime = microtime(true);
        $processedKey = "wms_processed_{$this->warehouseCode}";
        $failedKey = "wms_failed_{$this->warehouseCode}";

        try {
            // Quick connection check with short timeout
            $mysql = DB::connection('mysql');
            $oracle = DB::connection('oracle_wms');

            try {
                $mysql->getPdo();
                $oracle->getPdo();
            } catch (\Exception $e) {
                Log::error("[FetchAllocationJob] DB Connection failed for SKU {$this->sku}: " . $e->getMessage());
                $this->markAsFailed($failedKey, $processedKey);
                return;
            }

            // Set statement timeout for Oracle queries (5 seconds max per query)
            try {
                $oracle->statement("ALTER SESSION SET QUERY_TIMEOUT = 5");
            } catch (\Exception $e) {
                // Ignore if not supported
            }

            // Fetch allocation from Oracle with timeout
            $allocationValue = 0;
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
                    $allocationValue = (int) $oracleRows[0]->total_qty;
                }
            } catch (\Exception $e) {
                Log::warning("[FetchAllocationJob] Oracle query failed for SKU {$this->sku}, skipping: " . $e->getMessage());
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
                Log::warning("[FetchAllocationJob] Failed to update allocation for SKU {$this->sku}, skipping: " . $e->getMessage());
                $this->markAsFailed($failedKey, $processedKey);
                return;
            }

            // Update case pack data (optional, don't fail if this errors)
            // Update case pack data (warehouse-based only)
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
                    $casePacks = array_unique(array_map(
                        fn($row) => $row->unit_qty,
                        $caseRows
                    ));

                    $mysql->table($this->productTable)
                        ->where('sku', $this->sku)
                        ->update([
                            'case_pack' => implode(' | ', $casePacks),
                            'updated_at' => now()
                        ]);
                }
            } catch (\Exception $e) {
                Log::debug("[FetchAllocationJob] Case pack skipped {$this->sku}: {$e->getMessage()}");
            }


            // SUCCESS: Mark as processed
            Cache::increment($processedKey);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Log differently for SKUs not in warehouse vs actual data
            if ($allocationValue > 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Allocation: {$allocationValue} | {$duration}ms");
            } else {
                Log::debug("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Not in warehouse (0) | {$duration}ms");
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
        Log::error("[FetchAllocationJob] FINAL FAILURE (timeout/exception) SKU {$this->sku} | WH: {$this->warehouseCode}: " . $exception->getMessage());

        $failedKey = "wms_failed_{$this->warehouseCode}";
        $processedKey = "wms_processed_{$this->warehouseCode}";

        Cache::increment($failedKey);
        Cache::increment($processedKey);
    }
}
