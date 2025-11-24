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

    public int $timeout = 300;   // 5 minutes
    public int $tries = 2;

    protected string $sku;
    protected string $facilityId;
    protected string $warehouseCode;

    /**
     * Job constructor.
     */
    public function __construct(string $sku, string $facilityId, string $warehouseCode)
    {
        $this->sku = $sku;
        $this->facilityId = $facilityId;
        $this->warehouseCode = $warehouseCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Fetch allocation from Oracle WMS
            $row = DB::connection('oracle_wms')->selectOne("
                SELECT 
                    ci.item_id,
                    SUM(
                        CASE 
                            WHEN c.container_status NOT IN ('X', 'T', 'S') 
                            THEN ci.unit_qty 
                            ELSE 0 
                        END
                    ) AS total_qty
                FROM rwms.container_item ci
                JOIN rwms.container c
                    ON ci.facility_id = c.facility_id
                    AND ci.container_id = c.container_id
                WHERE ci.facility_id = :facility
                AND ci.item_id = :sku
                GROUP BY ci.item_id
            ", [
                'facility' => $this->facilityId,
                'sku'      => $this->sku,
            ]);

            $allocationValue = $row ? (int) $row->total_qty : 0;

            // Upsert MySQL allocation
            DB::connection('mysql')->table('product_wms_allocations')->updateOrInsert(
                [
                    'sku'            => $this->sku,
                    'warehouse_code' => $this->warehouseCode,
                ],
                [
                    'wms_actual_allocation'  => $allocationValue,
                    'wms_virtual_allocation' => $allocationValue,
                    'updated_at'             => now(),
                    'created_at'             => now(),
                ]
            );

            // Cache 30 minutes
            Cache::put(
                "allocation_{$this->sku}_{$this->warehouseCode}",
                $allocationValue,
                now()->addMinutes(30)
            );

            Log::debug("[FetchAllocationJob] SKU {$this->sku} | WH {$this->warehouseCode} | Allocation: {$allocationValue}");

        } catch (\Throwable $e) {

            Log::error("[FetchAllocationJob] FAILED | SKU {$this->sku} | Facility {$this->facilityId} | Error: {$e->getMessage()}");

            throw $e; // Trigger retry
        }
    }

    /**
     * Run when job fails completely after retries.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[FetchAllocationJob] FINAL FAILURE | SKU {$this->sku} | Facility {$this->facilityId} | {$exception->getMessage()}");
    }
}
