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

    public int $timeout = 300; // 5 minutes per SKU
    public int $tries = 3;

    protected string $sku;
    protected string $facilityId;
    protected string $warehouseCode;
    protected string $location;

    public function __construct(string $sku, string $facilityId, string $warehouseCode)
    {
        $this->sku = $sku;
        $this->facilityId = $facilityId;
        $this->warehouseCode = $warehouseCode;
        
        // Derive location from warehouse code
        $warehouseToLocation = [
            '80181' => '4002',
            '80141' => '6012',
            '80001' => '2010',
            '80041' => '2017',
            '80051' => '3018',
            '80071' => '3019',
            '80131' => '2008',
            '80201' => '6009',
            '80191' => '6010',
        ];
        
        $this->location = $warehouseToLocation[$warehouseCode] ?? '';
    }

    public function handle(): void
    {
        $processedSuccessfully = false;
        
        try {
            $mysql = DB::connection('mysql');
            $oracle = DB::connection('oracle_wms');

            // Fetch allocation from Oracle
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

            $allocationValue = 0;
            if (!empty($oracleRows)) {
                $allocationValue = (int) $oracleRows[0]->total_qty;
            }

            // Update/Insert allocation (even if 0 - this marks it as processed)
            $mysql->table('product_wms_allocations')->updateOrInsert(
                [
                    'sku' => $this->sku, 
                    'warehouse_code' => $this->warehouseCode
                ],
                [
                    'wms_actual_allocation'  => $allocationValue,
                    'wms_virtual_allocation' => $allocationValue,
                    'updated_at' => now(),
                    'created_at' => now(), // For insert case
                ]
            );

            // Fetch case pack data
            $caseRows = $oracle->select("
                SELECT item_id, unit_qty
                FROM (
                    SELECT ci.item_id, ci.unit_qty,
                        ROW_NUMBER() OVER (PARTITION BY ci.item_id ORDER BY ci.unit_qty) AS rn
                    FROM rwms.container_item ci
                    WHERE ci.facility_id = ?
                    AND ci.item_id = ?
                )
                WHERE rn <= 5
            ", [$this->facilityId, $this->sku]);

            // Update case pack (MATCH handleBatch behavior)
            if (!empty($caseRows) && $this->location) {

                // Extract unit_qty values exactly like handleBatch
                $casePacks = array_unique(array_map(
                    fn($row) => $row->unit_qty,
                    $caseRows
                ));

                $tableName = "products_{$this->location}";

                if ($mysql->getSchemaBuilder()->hasTable($tableName)) {
                    $mysql->table($tableName)
                        ->where('sku', $this->sku)
                        ->update([
                            'case_pack'  => implode(' | ', $casePacks),
                            'updated_at' => now()
                        ]);
                }
            }

            // Mark as successfully processed
            $processedSuccessfully = true;

            Log::info("[FetchAllocationJob] ✓ SKU {$this->sku} | Allocation: {$allocationValue}");

        } catch (\Throwable $e) {
            Log::error("[FetchAllocationJob] ✗ SKU {$this->sku}: " . $e->getMessage());
            throw $e; // Let queue retry
        } finally {
            // ALWAYS increment processed count if we made it through without exception
            // This ensures progress tracking is accurate even if no data found
            if ($processedSuccessfully) {
                $cacheKey = "wms_processed_{$this->location}_{$this->warehouseCode}";
                Cache::increment($cacheKey);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[FetchAllocationJob] FINAL FAILURE SKU {$this->sku}: {$exception->getMessage()}");
        
        // Increment both failed AND processed count so total adds up correctly
        $failedKey = "wms_failed_{$this->location}_{$this->warehouseCode}";
        $processedKey = "wms_processed_{$this->location}_{$this->warehouseCode}";
        
        Cache::increment($failedKey);
        Cache::increment($processedKey);
    }
}