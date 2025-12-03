<?php

namespace App\Console\Commands;

use App\Jobs\FetchAllocationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateAllProductAllocations extends Command
{
    protected $signature = 'products:update-allocations 
                            {--warehouse= : Specific warehouse code to process (e.g., 80181, 80141)}
                            {--async : Use async job queue instead of batch processing}';
    
    protected $description = 'Update product_wms_allocations table (wms_actual_allocation and wms_virtual_allocation) and case pack using oracle_wms config';

    // Master mapping: Warehouse Code => [Facility Label, Store Codes]
    protected array $warehouseConfig = [
        '80181' => ['facility' => 'BD', 'stores' => ['4002']],                    // Bacolod Depot
        '80141' => ['facility' => 'SI', 'stores' => ['6012']],                    // Silangan Warehouse
        '80001' => ['facility' => 'BD', 'stores' => ['2010']],                    // Central Warehouse
        '80041' => ['facility' => 'BD', 'stores' => ['2017']],                    // Procter Warehouse
        '80051' => ['facility' => 'BD', 'stores' => ['2019']],                    // Opao-ISO Warehouse
        '80071' => ['facility' => 'BD', 'stores' => ['3018']],                    // Big Blue Warehouse
        '80131' => ['facility' => 'BD', 'stores' => ['3019']],                    // Lower Tingub Warehouse
        '80201' => ['facility' => 'SL', 'stores' => ['2008']],                    // Sta. Rosa Warehouse
        '80191' => ['facility' => 'BD', 'stores' => ['6009', '6010']],            // Tacloban Depot
    ];

    public function handle()
    {
        $startTime = microtime(true);
        $phNow = now()->timezone('Asia/Manila');
        $date = $phNow->format('Y-m-d');
        $hour = $phNow->format('H');

        // Logs directory
        $logDir = storage_path("logs/wms_logs/{$date}");
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0777, true);
        }
        $logFile = "{$logDir}/allocations_{$hour}.log";

        $this->log($logFile, "=== Starting allocation update ===");

        // Check if async mode is enabled
        $asyncMode = $this->option('async');
        
        if ($asyncMode) {
            $this->log($logFile, "Running in ASYNC mode (using job queue)");
            return $this->handleAsync($logFile);
        } else {
            $this->log($logFile, "Running in BATCH mode (synchronous)");
            return $this->handleBatch($logFile, $startTime);
        }
    }

    /**
     * Handle async mode - dispatch individual jobs for each SKU
     */
    protected function handleAsync($logFile)
    {
        $warehouseCode = $this->option('warehouse');

        if (!$warehouseCode) {
            $this->log($logFile, "Warehouse code is required for async mode. Exiting.");
            return Command::FAILURE;
        }

        // Get warehouse configuration
        $config = $this->warehouseConfig[$warehouseCode] ?? null;
        
        if (!$config) {
            $this->log($logFile, "No configuration found for warehouse {$warehouseCode}. Exiting.");
            return Command::FAILURE;
        }

        $facilityId = $config['facility'];
        $stores = $config['stores'];

        $this->log($logFile, "Resolved warehouse '{$warehouseCode}' → Facility '{$facilityId}' → Stores: " . implode(', ', $stores));

        // Collect all SKUs from all stores using this warehouse
        $allSkus = [];
        foreach ($stores as $store) {
            $tableName = "products_{$store}";
            $skus = $this->collectSkusFromTable($tableName, $logFile);
            $allSkus = array_merge($allSkus, $skus);
        }
        
        $allSkus = array_unique($allSkus);

        if (empty($allSkus)) {
            $this->log($logFile, "No SKUs found for warehouse {$warehouseCode}. Exiting.");
            return Command::SUCCESS;
        }

        $this->log($logFile, "Dispatching jobs for " . count($allSkus) . " SKUs...");

        // Dispatch async jobs
        foreach ($allSkus as $sku) {
            FetchAllocationJob::dispatch($sku, $facilityId, $warehouseCode)
                ->onQueue('wms');
        }

        $this->log($logFile, "Successfully dispatched " . count($allSkus) . " jobs to 'wms' queue.");
        $this->log($logFile, "Run: php artisan queue:work --queue=wms");

        return Command::SUCCESS;
    }

    /**
     * Handle batch mode - process all warehouses synchronously
     */
    protected function handleBatch($logFile, $startTime)
    {
        $specificWarehouse = $this->option('warehouse');
        
        // Determine which warehouses to process
        if ($specificWarehouse) {
            if (!isset($this->warehouseConfig[$specificWarehouse])) {
                $this->log($logFile, "Invalid warehouse code: {$specificWarehouse}. Exiting.");
                return Command::FAILURE;
            }
            $warehousesToProcess = [$specificWarehouse];
        } else {
            // Process all configured warehouses
            $warehousesToProcess = array_keys($this->warehouseConfig);
        }

        $this->log($logFile, "Warehouses to process: " . implode(', ', $warehousesToProcess));

        $grandTotalUpdated = 0;
        $grandTotalInserted = 0;
        $grandTotalCasePack = 0;

        // Process each warehouse
        foreach ($warehousesToProcess as $warehouseCode) {
            $config = $this->warehouseConfig[$warehouseCode];
            $facilityId = $config['facility'];
            $stores = $config['stores'];

            $this->log($logFile, "---- Processing Warehouse: {$warehouseCode} (Facility: {$facilityId}, Stores: " . implode(', ', $stores) . ") ----");

            // Collect all SKUs from all stores using this warehouse
            $allSkus = [];
            foreach ($stores as $store) {
                $tableName = "products_{$store}";
                
                // Check if table exists
                if (!DB::connection('mysql')->getSchemaBuilder()->hasTable($tableName)) {
                    $this->log($logFile, "Table {$tableName} does not exist. Skipping.");
                    continue;
                }
                
                $skus = $this->collectSkusFromTable($tableName, $logFile);
                $allSkus = array_merge($allSkus, $skus);
            }
            
            $allSkus = array_unique($allSkus);
            
            if (empty($allSkus)) {
                $this->log($logFile, "No SKUs found for warehouse {$warehouseCode}. Skipping.");
                continue;
            }

            $inClause = "'" . implode("','", $allSkus) . "'";

            // Query Oracle for allocations
            $this->log($logFile, "Querying Oracle WMS for facility {$facilityId} allocations...");
            $allocations = [];
            
            try {
                $inventoryRows = DB::connection('oracle_wms')->select("
                    SELECT ci.item_id,
                        SUM(CASE WHEN c.container_status NOT IN ('X', 'T', 'S', 'D', 'A') 
                                    THEN ci.unit_qty ELSE 0 END) AS total_qty
                    FROM rwms.container_item ci
                    JOIN rwms.container c
                    ON ci.facility_id = c.facility_id
                    AND ci.container_id = c.container_id
                    WHERE ci.facility_id = '{$facilityId}'
                    AND ci.item_id IN ({$inClause})
                    GROUP BY ci.item_id
                ");

                foreach ($inventoryRows as $row) {
                    $allocations[$row->item_id] = (int) $row->total_qty;
                }
                
                $this->log($logFile, "Retrieved allocations for " . count($allocations) . " SKUs");
            } catch (\Exception $e) {
                $this->log($logFile, "Oracle query failed: " . $e->getMessage());
                continue;
            }

            // Update/Insert allocations
            $updated = 0;
            $inserted = 0;

            foreach ($allSkus as $sku) {
                $allocationValue = $allocations[$sku] ?? 0;
                
                try {
                    $exists = DB::connection('mysql')->table('product_wms_allocations')
                        ->where('sku', $sku)
                        ->where('warehouse_code', $warehouseCode)
                        ->update([
                            'wms_actual_allocation' => $allocationValue,
                            'wms_virtual_allocation' => $allocationValue,
                            'updated_at' => now()
                        ]);

                    if ($exists) {
                        $updated++;
                    } else {
                        DB::connection('mysql')->table('product_wms_allocations')->insert([
                            'sku' => $sku,
                            'warehouse_code' => $warehouseCode,
                            'wms_actual_allocation' => $allocationValue,
                            'wms_virtual_allocation' => $allocationValue,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $inserted++;
                    }
                } catch (\Exception $e) {
                    $this->log($logFile, "Failed to update SKU {$sku}: " . $e->getMessage());
                }
            }

            $this->log($logFile, "Allocations: {$updated} updated, {$inserted} inserted");
            $grandTotalUpdated += $updated;
            $grandTotalInserted += $inserted;

            // Query Oracle for case pack data
            $this->log($logFile, "Querying Oracle WMS for case pack data...");
            $caseMap = [];
            try {
                $caseRows = DB::connection('oracle_wms')->select("
                    SELECT item_id, unit_qty
                    FROM (
                        SELECT ci.item_id, ci.unit_qty,
                            ROW_NUMBER() OVER (PARTITION BY ci.item_id ORDER BY ci.unit_qty) AS rn
                        FROM rwms.container_item ci
                        WHERE ci.facility_id = '{$facilityId}'
                        AND ci.item_id IN ({$inClause})
                    )
                    WHERE rn <= 5
                ");
                foreach ($caseRows as $row) {
                    $caseMap[$row->item_id][] = $row->unit_qty;
                }
            } catch (\Exception $e) {
                $this->log($logFile, "Oracle case pack query failed: " . $e->getMessage());
            }

            // Update case pack in products tables for each store
            $casePackProcessed = 0;
            foreach ($stores as $store) {
                $tableName = "products_{$store}";
                
                if (!DB::connection('mysql')->getSchemaBuilder()->hasTable($tableName)) {
                    continue;
                }
                
                $storeSkus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();
                
                foreach ($storeSkus as $sku) {
                    if (isset($caseMap[$sku])) {
                        try {
                            DB::connection('mysql')->table($tableName)
                                ->where('sku', $sku)
                                ->update([
                                    'case_pack' => implode(' | ', array_unique($caseMap[$sku])),
                                    'updated_at' => now()
                                ]);
                            $casePackProcessed++;
                        } catch (\Exception $e) {
                            $this->log($logFile, "Case pack update failed for SKU {$sku}: " . $e->getMessage());
                        }
                    }
                }
            }

            $this->log($logFile, "Case packs: {$casePackProcessed} updated");
            $grandTotalCasePack += $casePackProcessed;
        }

        // Final summary
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $minutes = floor($duration / 60);
        $seconds = round($duration % 60);

        $this->log($logFile, "=== All warehouses processed ===");
        $this->log($logFile, "Total allocations updated: {$grandTotalUpdated}");
        $this->log($logFile, "Total allocations inserted: {$grandTotalInserted}");
        $this->log($logFile, "Total case packs updated: {$grandTotalCasePack}");
        $this->log($logFile, "Process completed in {$minutes}m {$seconds}s");

        return Command::SUCCESS;
    }

    /**
     * Collect SKUs from a specific table
     */
    protected function collectSkusFromTable($tableName, $logFile)
    {
        try {
            $skus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();
            $this->log($logFile, "Fetched " . count($skus) . " SKUs from {$tableName}");
            return array_unique($skus);
        } catch (\Exception $e) {
            $this->log($logFile, "Failed to fetch SKUs from {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    private function log(string $file, string $message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        File::append($file, "[{$timestamp}] {$message}\n");
        $this->info("[{$timestamp}] {$message}");
        flush();
    }
}