<?php

namespace App\Console\Commands;

use App\Jobs\FetchAllocationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;

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
        // CRITICAL: Prevent script timeout and set network timeouts
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '512M');
        ini_set('default_socket_timeout', '120');  // 2 minutes for network operations
        
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
        $this->log($logFile, "PHP max_execution_time: " . ini_get('max_execution_time'));
        $this->log($logFile, "PHP default_socket_timeout: " . ini_get('default_socket_timeout'));
        $this->log($logFile, "Memory limit: " . ini_get('memory_limit'));

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

            $this->log($logFile, "Total unique SKUs collected: " . count($allSkus));

    
            // Test Oracle connection first
            try {
                $this->log($logFile, "Testing Oracle connection...");
                $this->log($logFile, "Oracle Host: " . config('database.connections.oracle_wms.host'));
                $this->log($logFile, "Oracle Database: " . config('database.connections.oracle_wms.database'));
                
                $testStart = microtime(true);
                
                // Get connection and set timeout at runtime (belt and suspenders approach)
                $connection = DB::connection('oracle_wms');
                
                try {
                    $pdo = $connection->getPdo();
                    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 60);
                    $this->log($logFile, "PDO connection established, timeout set to 60s");
                } catch (\Exception $e) {
                    $this->log($logFile, "Could not set PDO timeout: " . $e->getMessage());
                }
                
                // Test with a simple query
                $this->log($logFile, "Executing test query...");
                $result = $connection->select("SELECT 1 AS test FROM DUAL");
                
                $testEnd = microtime(true);
                $testDuration = round(($testEnd - $testStart) * 1000, 2);
                $this->log($logFile, "Oracle connection successful (response time: {$testDuration}ms)");
                $this->log($logFile, "Proceeding to query allocations...");
                
            } catch (\PDOException $e) {
                $this->log($logFile, "ERROR: Oracle PDO Exception - " . $e->getMessage());
                $this->log($logFile, "PDO Error Code: " . $e->getCode());
                $this->log($logFile, "This usually indicates a network timeout or connection issue");
                $this->log($logFile, "Skipping warehouse {$warehouseCode}");
                continue;
            } catch (\Exception $e) {
                $this->log($logFile, "ERROR: Oracle connection failed - " . $e->getMessage());
                $this->log($logFile, "Error Type: " . get_class($e));
                $this->log($logFile, "Error Code: " . $e->getCode());
                $this->log($logFile, "Skipping warehouse {$warehouseCode}");
                continue;
            }

            // Process SKUs in chunks to avoid query issues
            $chunkSize = 500; // Oracle IN clause limit consideration
            $skuChunks = array_chunk($allSkus, $chunkSize);
            $this->log($logFile, "Processing in " . count($skuChunks) . " chunks of {$chunkSize} SKUs each");

            $allocations = [];
            
            foreach ($skuChunks as $chunkIndex => $skuChunk) {
                $chunkNum = $chunkIndex + 1;
                $this->log($logFile, "Processing chunk {$chunkNum}/" . count($skuChunks) . " (" . count($skuChunk) . " SKUs)");
                
                $inClause = "'" . implode("','", $skuChunk) . "'";

                try {
                    $queryStart = microtime(true);
                    $this->log($logFile, "Executing Oracle query for chunk {$chunkNum}...");
                    
                    // Set query timeout (5 minutes)
                    ini_set('default_socket_timeout', 300);
                    
                    $inventoryRows = DB::connection('oracle_wms')->select("
                        SELECT /*+ PARALLEL(4) */ ci.item_id,
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

                    $this->log($logFile, "Query executed, processing results...");
                    
                    $queryEnd = microtime(true);
                    $queryDuration = round($queryEnd - $queryStart, 2);
                    
                    $resultCount = 0;
                    foreach ($inventoryRows as $row) {
                        $allocations[$row->item_id] = (int) $row->total_qty;
                        $resultCount++;
                    }
                    
                    $this->log($logFile, "Chunk {$chunkNum} completed in {$queryDuration}s - Retrieved {$resultCount} allocations");
                } catch (\Exception $e) {
                    $this->log($logFile, "ERROR: Chunk {$chunkNum} failed - " . $e->getMessage());
                    $this->log($logFile, "Error Code: " . $e->getCode());
                    // Continue with next chunk instead of stopping entirely
                    continue;
                }
            }

            $this->log($logFile, "Total allocations retrieved: " . count($allocations) . " SKUs");

            // Fetch existing allocations for comparison
            $this->log($logFile, "Fetching existing allocations for comparison...");
            $existingAllocations = DB::connection('mysql')
                ->table('product_wms_allocations')
                ->whereIn('sku', $allSkus)
                ->where('warehouse_code', $warehouseCode)
                ->get()
                ->keyBy('sku');

            // Update/Insert allocations with per-SKU logging
            $updated = 0;
            $inserted = 0;

            $this->log($logFile, "");
            $this->logRaw($logFile, str_repeat("=", 120));
            $this->logRaw($logFile, "ALLOCATION UPDATE DETAILS - WAREHOUSE: {$warehouseCode}");
            $this->logRaw($logFile, str_repeat("=", 120));
            $this->logRaw($logFile, sprintf("%-20s | %-25s | %-25s | %-20s", "SKU", "BEFORE (Actual/Virtual)", "AFTER (Actual/Virtual)", "STATUS"));
            $this->logRaw($logFile, str_repeat("-", 120));

            foreach ($allSkus as $sku) {
                $newAllocation = $allocations[$sku] ?? 0;
                $existing = $existingAllocations->get($sku);
                
                try {
                    if ($existing) {
                        $oldActual = $existing->wms_actual_allocation;
                        $oldVirtual = $existing->wms_virtual_allocation;
                        $before = sprintf("%d / %d", $oldActual, $oldVirtual);
                        $after = sprintf("%d / %d", $newAllocation, $newAllocation);
                        $status = "UPDATED";
                        
                        // Update
                        DB::connection('mysql')->table('product_wms_allocations')
                            ->where('sku', $sku)
                            ->where('warehouse_code', $warehouseCode)
                            ->update([
                                'wms_actual_allocation' => $newAllocation,
                                'wms_virtual_allocation' => $newAllocation,
                                'updated_at' => now()
                            ]);
                        
                        $updated++;
                    } else {
                        $before = "NULL / NULL";
                        $after = sprintf("%d / %d", $newAllocation, $newAllocation);
                        $status = "INSERTED";
                        
                        // Insert
                        DB::connection('mysql')->table('product_wms_allocations')->insert([
                            'sku' => $sku,
                            'warehouse_code' => $warehouseCode,
                            'wms_actual_allocation' => $newAllocation,
                            'wms_virtual_allocation' => $newAllocation,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        $inserted++;
                    }
                    
                    $this->logRaw($logFile, sprintf("%-20s | %-25s | %-25s | %-20s", $sku, $before, $after, $status));
                    
                } catch (\Exception $e) {
                    $this->logRaw($logFile, sprintf("%-20s | %-25s | %-25s | %-20s", $sku, "ERROR", "ERROR", "FAILED: " . $e->getMessage()));
                }
            }

            $this->logRaw($logFile, str_repeat("=", 120));
            $this->logRaw($logFile, sprintf("SUMMARY: %d Updated | %d Inserted | %d Total Processed", $updated, $inserted, count($allSkus)));
            $this->logRaw($logFile, str_repeat("=", 120));
            $this->log($logFile, "");
            $this->log($logFile, "Allocations: {$updated} updated, {$inserted} inserted");
            $grandTotalUpdated += $updated;
            $grandTotalInserted += $inserted;

            // Query Oracle for case pack data
            $this->log($logFile, "Querying Oracle WMS for case pack data...");
            $caseMap = [];
            
            foreach ($skuChunks as $chunkIndex => $skuChunk) {
                $chunkNum = $chunkIndex + 1;
                $inClause = "'" . implode("','", $skuChunk) . "'";
                
                try {
                    $queryStart = microtime(true);
                    
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
                    
                    $queryEnd = microtime(true);
                    $queryDuration = round($queryEnd - $queryStart, 2);
                    
                    foreach ($caseRows as $row) {
                        $caseMap[$row->item_id][] = $row->unit_qty;
                    }
                    
                    $this->log($logFile, "Case pack chunk {$chunkNum} completed in {$queryDuration}s");
                } catch (\Exception $e) {
                    $this->log($logFile, "ERROR: Case pack chunk {$chunkNum} failed - " . $e->getMessage());
                    continue;
                }
            }
            
            $this->log($logFile, "Total case packs retrieved: " . count($caseMap) . " SKUs");

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

    private function log(string $file, string $message, bool $consoleOnly = false)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}";
        
        // Write to file immediately (unless console only)
        if (!$consoleOnly) {
            // Use file_put_contents with LOCK_EX for immediate write
            $handle = fopen($file, 'a');
            if ($handle) {
                flock($handle, LOCK_EX);
                fwrite($handle, $logMessage . "\n");
                fflush($handle);  // Force write to disk
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
        
        // Output to console
        $this->info($logMessage);
        
        // Force all output buffers to flush
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    private function logRaw(string $file, string $message)
    {
        // Use file handle for immediate write
        $handle = fopen($file, 'a');
        if ($handle) {
            flock($handle, LOCK_EX);
            fwrite($handle, $message . "\n");
            fflush($handle);  // Force write to disk
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        
        $this->line($message);
        
        // Force all output buffers to flush
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}