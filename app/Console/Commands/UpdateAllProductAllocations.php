<?php

namespace App\Console\Commands;

use App\Jobs\FetchAllocationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateAllProductAllocations extends Command
{
    protected $signature = 'products:update-allocations 
                            {--location= : Specific location to process}
                            {--async : Use async job queue instead of batch processing}';
    
    protected $description = 'Update product_wms_allocations table (wms_actual_allocation and wms_virtual_allocation) and case pack using oracle_wms config';

    // Warehouse facility codes
    protected array $warehouses = [
        '80141', // Silangan Warehouse
        '80001', // Central Warehouse
        '80041', // Procter Warehouse
        '80051', // Opao-ISO Warehouse
        '80071', // Big Blue Warehouse
        '80131', // Lower Tingub Warehouse
        '80211', // Sta. Rosa Warehouse
        '80181', // Bacolod Depot
        '80191', // Tacloban Depot
    ];

    // Store code to warehouse facility ID mapping
    protected array $locationToWarehouse = [
        '4002' => 'BD', // Bacolod Depot (80181)
        // Add more mappings as needed...
    ];

    // Warehouse code to facility ID mapping
    protected array $warehouseToFacility = [
        '80181' => 'BD', // Bacolod Depot
        '80141' => 'BD', // Silangan Warehouse
        '80001' => 'BD', // Central Warehouse
        '80041' => 'BD', // Procter Warehouse
        '80051' => 'BD', // Opao-ISO Warehouse
        '80071' => 'BD', // Big Blue Warehouse
        '80131' => 'BD', // Lower Tingub Warehouse
        '80211' => 'BD', // Sta. Rosa Warehouse
        '80191' => 'BD', // Tacloban Depot
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
        $location = strtolower($this->option('location'));

        if (!$location) {
            $this->log($logFile, "Location is required for async mode. Exiting.");
            return Command::FAILURE;
        }

        /**
         * Step 1: Store location → warehouse label (BD)
         */
        $facilityLabel = $this->locationToWarehouse[$location] ?? null;

        if (!$facilityLabel) {
            $this->log($logFile, "No warehouse mapping found for location {$location}. Exiting.");
            return Command::FAILURE;
        }

        /**
         * Step 2: Label (BD) → Oracle facility ID (80181)
         */
        $facilityId = array_search($facilityLabel, $this->warehouseToFacility, true);

        if (!$facilityId) {
            $this->log($logFile, "No Oracle facility ID found for warehouse label '{$facilityLabel}'. Exiting.");
            return Command::FAILURE;
        }

        /**
         * Step 3: Warehouse code = facility ID
         */
        $warehouseCode = $facilityId;

        $this->log($logFile, "Resolved location '{$location}' → label '{$facilityLabel}' → Oracle facility {$facilityId}");

        /**
         * Step 4: Collect all SKUs
         */
        $allProductTables = $this->getAllProductTables();
        $allSkus = $this->collectAllSkus($allProductTables, $logFile);

        $this->log($logFile, "Dispatching jobs for " . count($allSkus) . " SKUs...");

        /**
         * Step 5: Dispatch async jobs
         */
        foreach ($allSkus as $sku) {
            FetchAllocationJob::dispatch($sku, $facilityId, $warehouseCode)
                ->onQueue('wms');
        }

        $this->log($logFile, "Successfully dispatched " . count($allSkus) . " jobs to 'wms' queue.");
        $this->log($logFile, "Run: php artisan queue:work --queue=wms");

        return Command::SUCCESS;
    }



    /**
     * Handle batch mode - process all SKUs synchronously in batches
     */
    protected function handleBatch($logFile, $startTime)
    {
        $location = strtolower($this->option('location'));
        
        // Define all product tables
        $allProductTables = $this->getAllProductTables();

        // Pick tables based on location option
        if ($location) {
            $productTables = ["products_{$location}"];
            $storeCode = $location;
        } else {
            $productTables = $allProductTables;
            preg_match('/products_(.+)$/', $productTables[0], $matches);
            $storeCode = $matches[1] ?? null;
        }

        $this->log($logFile, "Tables to process: " . implode(', ', $productTables));

        // Get the mapped warehouse facility for the store
        $facilityId = $this->locationToWarehouse[$storeCode] ?? null;
        
        if (!$facilityId) {
            $this->log($logFile, "No warehouse facility mapping found for store {$storeCode}. Exiting.");
            return Command::FAILURE;
        }

        // Map facility to warehouse code for storage (BD -> 80181)
        $warehouseCode = array_search($facilityId, $this->warehouseToFacility) ?: $facilityId;

        $this->log($logFile, "Using facility ID '{$facilityId}' (warehouse code: {$warehouseCode}) for store {$storeCode}");

        // Step 1: Fetch all unique SKUs across ALL product tables
        if ($location) {
            $allSkus = $this->collectAllSkus($productTables, $logFile);
        } else {
            $allSkus = $this->collectAllSkus($allProductTables, $logFile);
        }

        if (empty($allSkus)) {
            $this->log($logFile, "No SKUs found. Exiting.");
            return Command::SUCCESS;
        }

        $inClause = "'" . implode("','", $allSkus) . "'";

        $totalAllocationsProcessed = 0;
        $totalAllocationsInserted = 0;
        $totalCasePackProcessed = 0;

        // Step 2: Query Oracle using the mapped facility ID
        $this->log($logFile, "---- Processing facility: {$facilityId} ----");

        // Query Oracle for allocations for this facility
        $this->log($logFile, "Querying Oracle WMS for facility {$facilityId} allocations...");
        $facilityAllocations = [];
        
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
                $facilityAllocations[$row->item_id] = (int) $row->total_qty;
            }
            
            $this->log($logFile, "Retrieved allocations for " . count($facilityAllocations) . " SKUs from facility {$facilityId}");
        } catch (\Exception $e) {
            $this->log($logFile, "Oracle query failed for facility {$facilityId}: " . $e->getMessage());
        }

        // Update/Insert product_wms_allocations for this facility
        $facilityUpdated = 0;
        $facilityInserted = 0;

        foreach ($allSkus as $sku) {
            if (isset($facilityAllocations[$sku])) {
                $allocationValue = $facilityAllocations[$sku];
                
                try {
                    // Try to update existing record
                    $updated = DB::connection('mysql')->table('product_wms_allocations')
                        ->where('sku', $sku)
                        ->where('warehouse_code', $warehouseCode)
                        ->update([
                            'wms_actual_allocation' => $allocationValue,
                            'wms_virtual_allocation' => $allocationValue,
                            'updated_at' => now()
                        ]);

                    if ($updated) {
                        $facilityUpdated++;
                    } else {
                        // Insert if doesn't exist
                        DB::connection('mysql')->table('product_wms_allocations')->insert([
                            'sku' => $sku,
                            'warehouse_code' => $warehouseCode,
                            'wms_actual_allocation' => $allocationValue,
                            'wms_virtual_allocation' => $allocationValue,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $facilityInserted++;
                    }
                } catch (\Exception $e) {
                    $this->log($logFile, "Failed to update/insert allocation for SKU {$sku} in warehouse {$warehouseCode}: " . $e->getMessage());
                }
            }
        }

        $this->log($logFile, "Facility {$facilityId} (warehouse {$warehouseCode}): {$facilityUpdated} updated, {$facilityInserted} inserted");
        $totalAllocationsProcessed = $facilityUpdated;
        $totalAllocationsInserted = $facilityInserted;

        // Step 3: Query Oracle for case pack data from the same facility
        $this->log($logFile, "Querying Oracle WMS for case pack data from {$facilityId}...");
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
            $this->log($logFile, "Case pack query returned " . count($caseRows) . " rows.");
        } catch (\Exception $e) {
            $this->log($logFile, "Oracle case pack query failed: " . $e->getMessage());
        }

        // Step 4: Update case_pack in each products table
        foreach ($productTables as $tableName) {
            $this->log($logFile, "---- Updating case pack for table: {$tableName} ----");

            $tableStartTime = microtime(true);
            $casePackProcessed = 0;
            $casePackSkipped = 0;

            $skus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();

            foreach ($skus as $sku) {
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
                } else {
                    $casePackSkipped++;
                }
            }

            $tableEndTime = microtime(true);
            $duration = round($tableEndTime - $tableStartTime, 2);

            $this->log($logFile, "Table {$tableName}: {$casePackProcessed} updated, {$casePackSkipped} skipped in {$duration}s");
            $totalCasePackProcessed += $casePackProcessed;
        }

        // Final log
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $minutes = floor($duration / 60);
        $seconds = round($duration % 60);

        $this->log($logFile, "=== All products updated ===");
        $this->log($logFile, "Total allocations updated: {$totalAllocationsProcessed}");
        $this->log($logFile, "Total allocations inserted: {$totalAllocationsInserted}");
        $this->log($logFile, "Total case packs updated: {$totalCasePackProcessed}");
        $this->log($logFile, "Process completed in {$minutes}m {$seconds}s");

        return Command::SUCCESS;
    }

    /**
     * Get all product table names
     */
    protected function getAllProductTables()
    {
        return [
            'products_4002',
            'products_2010',
            'products_2017',
            'products_2019',
            'products_3018',
            'products_3019',
            'products_2008',
            'products_6012',
            'products_6009',
            'products_6010',
        ];
    }

    /**
     * Collect all unique SKUs from given tables
     */
    protected function collectAllSkus($tables, $logFile)
    {
        $allSkus = [];
        foreach ($tables as $tableName) {
            try {
                $skus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();
                $allSkus = array_merge($allSkus, $skus);
                $this->log($logFile, "Fetched " . count($skus) . " SKUs from {$tableName}");
            } catch (\Exception $e) {
                $this->log($logFile, "Failed to fetch SKUs from {$tableName}: " . $e->getMessage());
            }
        }
        
        return array_unique($allSkus);
    }

    private function log(string $file, string $message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        File::append($file, "[{$timestamp}] {$message}\n");

        // Live console output
        $this->info("[{$timestamp}] {$message}");
        flush();
    }
}