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

            // Initialize variables
            $allocationValue = 0;
            $skuFoundInOracle = false;
            $physicalStock = 0;
            $tsfReservedQty = 0;
            $nonSellableQty = 0;
            $customerResvQty = 0;
            $backorderQty = 0;
            $rtvQty = 0;
            $availableQty = 0;

            try {
                // 🔥 Select ALL the columns you need with proper aliases
                $oracleRows = $oracle->select("
                    SELECT 
                        ITEM AS item_id,
                        STOCK_ON_HAND AS physical_stock,
                        COALESCE(TSF_RESERVED_QTY, 0) AS tsf_reserved_qty,
                        COALESCE(NON_SELLABLE_QTY, 0) AS non_sellable_qty,
                        COALESCE(CUSTOMER_RESV, 0) AS customer_resv_qty,
                        COALESCE(CUSTOMER_BACKORDER, 0) AS backorder_qty,
                        COALESCE(RTV_QTY, 0) AS rtv_qty,
                        (STOCK_ON_HAND 
                         - COALESCE(TSF_RESERVED_QTY, 0)
                         - COALESCE(NON_SELLABLE_QTY, 0)
                         - COALESCE(CUSTOMER_RESV, 0)
                         - COALESCE(CUSTOMER_BACKORDER, 0)
                         - COALESCE(RTV_QTY, 0)
                        ) AS available_qty
                    FROM ITEM_LOC_SOH
                    WHERE LOC = ?
                        AND ITEM = ?
                ", [$this->warehouseCode, $this->sku]);

                if (!empty($oracleRows)) {
                    $skuFoundInOracle = true;
                    $row = $oracleRows[0];

                    $physicalStock = (int) $row->physical_stock;
                    $tsfReservedQty = (int) $row->tsf_reserved_qty;
                    $nonSellableQty = (int) $row->non_sellable_qty;
                    $customerResvQty = (int) $row->customer_resv_qty;
                    $backorderQty = (int) $row->backorder_qty;
                    $rtvQty = (int) $row->rtv_qty;
                    $availableQty = (int) $row->available_qty;

                    // Calculate total reserved for logging
                    $totalReserved = $tsfReservedQty + $nonSellableQty + $customerResvQty + $backorderQty + $rtvQty;

                    // Ensure non-negative
                    $allocationValue = max(0, $availableQty);

                    // Log with all details
                    Log::info("[FetchAllocationJob] SKU {$this->sku} | WH: {$this->warehouseCode} | " .
                        "Physical: {$physicalStock} | TSF Reserved: {$tsfReservedQty} | " .
                        "Non-Sellable: {$nonSellableQty} | Customer Resv: {$customerResvQty} | " .
                        "Backorder: {$backorderQty} | RTV: {$rtvQty} | Total Reserved: {$totalReserved} | " .
                        "Available: {$allocationValue}");
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
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning("[FetchAllocationJob] Failed to update allocation for SKU {$this->sku}: " . $e->getMessage());
                $this->markAsFailed($failedKey, $processedKey);
                return;
            }

            // SUCCESS: mark as processed
            Cache::increment($processedKey);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Improved logging with correct variables
            if (!$skuFoundInOracle) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: NOT IN RMS | Set to 0 | {$duration}ms");
            } elseif ($physicalStock == 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: NO PHYSICAL STOCK | Set to 0 | {$duration}ms");
            } elseif ($totalReserved >= $physicalStock) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: FULLY UNAVAILABLE | Physical: {$physicalStock} | Total Reserved: {$totalReserved} | Available: 0 | {$duration}ms");
            } elseif ($allocationValue > 0) {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: AVAILABLE | Physical: {$physicalStock} | Total Reserved: {$totalReserved} | Available: {$allocationValue} | {$duration}ms");
            } else {
                Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | WH: {$this->warehouseCode} | Status: ZERO AVAILABLE | Physical: {$physicalStock} | Total Reserved: {$totalReserved} | {$duration}ms");
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
