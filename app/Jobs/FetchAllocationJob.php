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

    public int $timeout = 300;
    public int $tries = 3;
    public int $maxExceptions = 1;

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
            // Purge old Oracle connection
            DB::purge('oracle_rms');
            $oracle = DB::connection('oracle_rms');
            $oracle->getPdo();
            $oracle->select("SELECT 1 FROM DUAL");

            $mysql = DB::connection('mysql');
            $mysql->getPdo();

            // 🔥 FIXED: Properly subtract TSF_RESERVED_QTY
            $allocationValue = 0;
            $skuFoundInOracle = false;
            $physicalStock = 0;
            $reservedQty = 0;
            $availableQty = 0;

            try {
                // 🔥 FIXED: Properly handle TSF_RESERVED_QTY with explicit casting
                $oracleRows = $oracle->select("
        SELECT 
            ITEM AS item_id,
            STOCK_ON_HAND AS physical_stock,
            -- Force numeric conversion and handle NULL
            COALESCE(CAST(TSF_RESERVED_QTY AS NUMBER), 0) AS reserved_qty,
            -- Calculate available quantity
            (STOCK_ON_HAND - COALESCE(CAST(TSF_RESERVED_QTY AS NUMBER), 0)) AS available_qty
        FROM ITEM_LOC_SOH
        WHERE LOC = ?
            AND ITEM = ?
    ", [$this->warehouseCode, $this->sku]);

                if (!empty($oracleRows)) {
                    $skuFoundInOracle = true;
                    $row = $oracleRows[0];

                    $physicalStock = (int) $row->physical_stock;
                    $reservedQty = (int) $row->reserved_qty;
                    $availableQty = (int) $row->available_qty;

                    // Ensure non-negative
                    $allocationValue = max(0, $availableQty);

                    // Log with details
                    Log::info("[FetchAllocationJob] SKU {$this->sku} | WH: {$this->warehouseCode} | Physical: {$physicalStock} | Reserved: {$reservedQty} | Available: {$allocationValue}");
                } else {
                    // SKU not found in this location
                    $allocationValue = 0;
                    Log::info("[FetchAllocationJob] SKU {$this->sku} | WH: {$this->warehouseCode} | NOT FOUND in ITEM_LOC_SOH");
                }
            } catch (\Exception $e) {
                Log::error("[FetchAllocationJob] Query failed for SKU {$this->sku}: " . $e->getMessage());
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
                        // 'physical_stock' => $physicalStock,  // 🔥 NEW: Store for debugging
                        // 'reserved_qty' => $reservedQty,      // 🔥 NEW: Store for debugging
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                // 🔥 Also update the product table if needed
                if ($this->productTable) {
                    $mysql->table($this->productTable)
                        ->where('sku', $this->sku)
                        ->update([
                            'wms_available' => $allocationValue,
                            'updated_at' => now()
                        ]);
                }
            } catch (\Exception $e) {
                Log::warning("[FetchAllocationJob] Failed to update allocation for SKU {$this->sku}: " . $e->getMessage());
                $this->markAsFailed($failedKey, $processedKey);
                return;
            }

            // SUCCESS: mark as processed
            Cache::increment($processedKey);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // 🔥 IMPROVED: More detailed logging
            if (!$skuFoundInOracle) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: NOT IN RMS | Set to 0 | {$duration}ms");
            } elseif ($physicalStock == 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: NO PHYSICAL STOCK | Set to 0 | {$duration}ms");
            } elseif ($reservedQty >= $physicalStock) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: FULLY RESERVED | Physical: {$physicalStock} | Reserved: {$reservedQty} | Available: 0 | {$duration}ms");
            } elseif ($allocationValue > 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: AVAILABLE | Physical: {$physicalStock} | Reserved: {$reservedQty} | Available: {$allocationValue} | {$duration}ms");
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
