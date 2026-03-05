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
            DB::purge('oracle_rms'); // 🔥 CHANGED: from oracle_wms to oracle_rms
            $oracle = DB::connection('oracle_rms'); // 🔥 CHANGED: from oracle_wms to oracle_rms
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

            // 🔥 NEW: Fetch allocation from Oracle RMS using ITEM_LOC_SOH
            $allocationValue = 0;
            $skuFoundInOracle = false;
            $physicalStock = 0;
            $reservedQty = 0;

            try {
                // Check if TSF_RESERVED_QTY exists in ITEM_LOC_SOH
                $oracleRows = $oracle->select("
                    SELECT 
                        ITEM AS item_id,
                        STOCK_ON_HAND AS physical_stock,
                        NVL(TSF_RESERVED_QTY, 0) AS reserved_qty,
                        (STOCK_ON_HAND - NVL(TSF_RESERVED_QTY, 0)) AS available_qty
                    FROM ITEM_LOC_SOH
                    WHERE LOC = ?
                        AND ITEM = ?
                ", [$this->warehouseCode, $this->sku]); // 🔥 Use warehouseCode directly

                if (!empty($oracleRows)) {
                    $skuFoundInOracle = true;
                    $row = $oracleRows[0];
                    $physicalStock = (int) $row->physical_stock;
                    $reservedQty = (int) $row->reserved_qty;
                    $allocationValue = (int) $row->available_qty; // This is STOCK_ON_HAND - TSF_RESERVED_QTY
                }

                // If no row found, SKU might not exist in this location
                // allocationValue stays 0

            } catch (\Exception $e) {
                // Check if error is because TSF_RESERVED_QTY column doesn't exist
                if (strpos($e->getMessage(), 'TSF_RESERVED_QTY') !== false) {
                    Log::warning("[FetchAllocationJob] TSF_RESERVED_QTY column not found, falling back to STOCK_ON_HAND only");

                    // Fallback query without TSF_RESERVED_QTY
                    $oracleRows = $oracle->select("
                        SELECT 
                            ITEM AS item_id,
                            STOCK_ON_HAND AS available_qty
                        FROM ITEM_LOC_SOH
                        WHERE LOC = ?
                            AND ITEM = ?
                    ", [$this->warehouseCode, $this->sku]);

                    if (!empty($oracleRows)) {
                        $skuFoundInOracle = true;
                        $allocationValue = (int) $oracleRows[0]->available_qty;
                    }
                } else {
                    Log::warning("[FetchAllocationJob] Oracle query failed for SKU {$this->sku}: " . $e->getMessage());
                    $this->markAsFailed($failedKey, $processedKey);
                    return;
                }
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

            // 🔥 OPTIONAL: Remove case pack logic if not needed
            // If you still need case pack data from WMS, keep it separate

            // SUCCESS: mark as processed
            Cache::increment($processedKey);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (!$skuFoundInOracle) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: NOT IN RMS | Set to 0 | {$duration}ms");
            } elseif ($allocationValue > 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Physical: {$physicalStock} | Reserved: {$reservedQty} | Available: {$allocationValue} | {$duration}ms");
            } else {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: ZERO AVAILABLE | Physical: {$physicalStock} | Reserved: {$reservedQty} | {$duration}ms");
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
