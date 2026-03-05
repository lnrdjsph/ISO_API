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
        '80181' => ['facility' => 'BD', 'stores' => ['4002', '2010', '2017', '2019', '3018']],                    // Bacolod Depot
        '80141' => ['facility' => 'SI', 'stores' => ['6012', '3019', '2008', '6009', '6010']],                    // Silangan Warehouse
        // '80001' => ['facility' => 'BD', 'stores' => ['2010']],                    // Central Warehouse
        // '80041' => ['facility' => 'BD', 'stores' => ['2017']],                    // Procter Warehouse
        // '80051' => ['facility' => 'BD', 'stores' => ['2019']],                    // Opao-ISO Warehouse
        // '80071' => ['facility' => 'BD', 'stores' => ['3018']],                    // Big Blue Warehouse
        // '80131' => ['facility' => 'BD', 'stores' => ['3019']],                    // Lower Tingub Warehouse
        // '80201' => ['facility' => 'SL', 'stores' => ['2008']],                    // Sta. Rosa Warehouse
        // '80191' => ['facility' => 'BD', 'stores' => ['6009', '6010']],            // Tacloban Depot
    ];
    protected array $warehouseMap = [
        '80141' => 'Silangan Warehouse',
        '80001' => 'Central Warehouse',
        '80041' => 'Procter Warehouse',
        '80051' => 'Opao-ISO Warehouse',
        '80071' => 'Big Blue Warehouse',
        '80131' => 'Lower Tingub Warehouse',
        '80211' => 'Sta. Rosa Warehouse',
        '80181' => 'Bacolod Depot',
        '80191' => 'Tacloban Depot',
    ];


    private $logFile;
    private $processStartTime;

    public function handle()
    {
        // CRITICAL: Disable ALL output buffering immediately
        while (ob_get_level()) {
            ob_end_clean();
        }

        // CRITICAL: Prevent script timeout and set network timeouts
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '512M');
        ini_set('default_socket_timeout', '120');

        $this->processStartTime = microtime(true);
        $phNow = now()->timezone('Asia/Manila');
        $date = $phNow->format('Y-m-d');
        $hour = $phNow->format('H');

        // Logs directory
        $logDir = storage_path("logs/wms_logs/{$date}");
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0777, true);
        }
        $this->logFile = "{$logDir}/allocations_{$hour}.log";

        // Register shutdown handler FIRST (before any DB operations)
        register_shutdown_function(function () {
            $this->handleShutdown();
        });

        $this->log("\n" . str_repeat("=", 80) . "\n");
        $this->log("=== Starting allocation update ===");
        $this->log("\n" . str_repeat("=", 80) . "\n");
        $this->log("Running from: " . (php_sapi_name() === 'cli' ? 'CLI' : 'WEB'));
        $this->log("PID: " . getmypid());
        $this->log("PHP max_execution_time: " . ini_get('max_execution_time'));
        $this->log("PHP default_socket_timeout: " . ini_get('default_socket_timeout'));
        $this->log("Memory limit: " . ini_get('memory_limit'));
        $this->log("Output buffering: " . (ob_get_level() > 0 ? 'ON (Level: ' . ob_get_level() . ')' : 'OFF'));

        // Check if async mode is enabled
        $asyncMode = $this->option('async');

        if ($asyncMode) {
            $this->log("Running in ASYNC mode (using job queue)");
            return $this->handleAsync();
        } else {
            $this->log("Running in BATCH mode (synchronous)");
            return $this->handleBatch();
        }
    }

    /**
     * Handle shutdown gracefully
     */
    protected function handleShutdown()
    {
        $error = error_get_last();
        $message = "\n" . str_repeat("=", 80) . "\n";
        $message .= "[SHUTDOWN] Process ended at: " . date('Y-m-d H:i:s') . "\n";

        if ($this->processStartTime) {
            $duration = microtime(true) - $this->processStartTime;
            $message .= "[SHUTDOWN] Total runtime: " . round($duration, 2) . "s\n";
        }

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message .= "[FATAL ERROR] Script terminated unexpectedly:\n";
            $message .= "Type: " . $error['type'] . "\n";
            $message .= "Message: " . $error['message'] . "\n";
            $message .= "File: " . $error['file'] . "\n";
            $message .= "Line: " . $error['line'] . "\n";
        } else {
            $message .= "[NORMAL SHUTDOWN] Process completed successfully\n";
        }

        $message .= str_repeat("=", 80) . "\n";

        if ($this->logFile) {
            file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Handle async mode - dispatch individual jobs for each SKU
     */
    protected function handleAsync()
    {
        $warehouseCode = $this->option('warehouse');

        if (!$warehouseCode) {
            $this->log("Warehouse code is required for async mode. Exiting.");
            return Command::FAILURE;
        }

        // Get warehouse configuration
        $config = $this->warehouseConfig[$warehouseCode] ?? null;

        if (!$config) {
            $this->log("No configuration found for warehouse {$warehouseCode}. Exiting.");
            return Command::FAILURE;
        }

        $facilityId = $config['facility'];
        $stores = $config['stores'];

        $this->log("Resolved warehouse '{$warehouseCode}' → Facility '{$facilityId}' → Stores: " . implode(', ', $stores));

        // Collect all SKUs from all stores using this warehouse
        $allSkus = [];
        foreach ($stores as $store) {
            $tableName = "products_{$store}";
            $skus = $this->collectSkusFromTable($tableName);
            $allSkus = array_merge($allSkus, $skus);
        }

        $allSkus = array_unique($allSkus);

        if (empty($allSkus)) {
            $this->log("No SKUs found for warehouse {$warehouseCode}. Exiting.");
            return Command::SUCCESS;
        }

        $this->log("Dispatching jobs for " . count($allSkus) . " SKUs...");

        // Dispatch async jobs
        foreach ($allSkus as $sku) {

            $tableName = "products_{$stores[0]}";
            FetchAllocationJob::dispatch($sku, $facilityId, $warehouseCode, $tableName)
                ->onQueue('wms');
        }

        $this->log("Successfully dispatched " . count($allSkus) . " jobs to 'wms' queue.");
        $this->log("Run: php artisan queue:work --queue=wms");

        return Command::SUCCESS;
    }

    /**
     * Handle batch mode - process all warehouses synchronously
     */
    protected function handleBatch()
    {
        $specificWarehouse = $this->option('warehouse');

        // Determine which warehouses to process
        if ($specificWarehouse) {
            if (!isset($this->warehouseConfig[$specificWarehouse])) {
                $this->log("Invalid warehouse code: {$specificWarehouse}. Exiting.");
                return Command::FAILURE;
            }
            $warehousesToProcess = [$specificWarehouse];
        } else {
            // Process all configured warehouses
            $warehousesToProcess = array_keys($this->warehouseConfig);
        }

        $this->log("Warehouses to process: " . implode(', ', $warehousesToProcess));

        $grandTotalUpdated = 0;
        $grandTotalInserted = 0;
        $grandTotalCasePack = 0;

        // Process each warehouse
        foreach ($warehousesToProcess as $warehouseCode) {
            $config = $this->warehouseConfig[$warehouseCode];
            $facilityId = $config['facility'];
            $stores = $config['stores'];

            $this->log("---- Processing Warehouse: {$warehouseCode} (Facility: {$facilityId}, Stores: " . implode(', ', $stores) . ") ----");

            // Collect all SKUs from all stores using this warehouse
            $allSkus = [];
            foreach ($stores as $store) {
                $tableName = "products_{$store}";

                // Check if table exists
                if (!DB::connection('mysql')->getSchemaBuilder()->hasTable($tableName)) {
                    $this->log("Table {$tableName} does not exist. Skipping.");
                    continue;
                }

                $skus = $this->collectSkusFromTable($tableName);
                $allSkus = array_merge($allSkus, $skus);
            }

            $allSkus = array_unique($allSkus);

            if (empty($allSkus)) {
                $this->log("No SKUs found for warehouse {$warehouseCode}. Skipping.");
                continue;
            }

            $this->log("Total unique SKUs collected: " . count($allSkus));

            // Test Oracle connection with enhanced error reporting
            // try {
            //     $this->log("Testing Oracle connection...");
            //     $this->log("Oracle Host: " . config('database.connections.oracle_wms.host'));
            //     $this->log("Oracle Database: " . config('database.connections.oracle_wms.database'));
            //     $this->log("Oracle Port: " . config('database.connections.oracle_wms.port'));
            //     $this->log("Oracle Username: " . config('database.connections.oracle_wms.username'));

            //     // Check if oci8 extension is loaded
            //     $this->log("Checking PHP OCI8 extension...");
            //     if (!extension_loaded('oci8')) {
            //         throw new \Exception("OCI8 extension not loaded");
            //     }
            //     $this->log("OCI8 extension is loaded");

            //     // Check environment variables
            //     $this->log("Environment check:");
            //     $this->log("→ ORACLE_HOME: " . (getenv('ORACLE_HOME') ?: 'NOT SET'));
            //     $this->log("→ LD_LIBRARY_PATH: " . (getenv('LD_LIBRARY_PATH') ?: 'NOT SET'));
            //     $this->log("→ NLS_LANG: " . (getenv('NLS_LANG') ?: 'NOT SET'));

            //     $testStart = microtime(true);

            //     // CRITICAL: Reconnect to ensure fresh connection in cron context
            //     $this->log("Purging existing Oracle connection...");
            //     DB::purge('oracle_wms');
            //     $this->log("Getting new Oracle connection...");
            //     $connection = DB::connection('oracle_wms');
            //     $this->log("Connection object created");

            //     try {
            //         $this->log("Attempting to get PDO connection...");
            //         $pdo = $connection->getPdo();
            //         $this->log("PDO connection object retrieved");

            //         $pdo->setAttribute(PDO::ATTR_TIMEOUT, 60);
            //         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //         $this->log("PDO attributes set (timeout: 60s, error mode: exception)");
            //     } catch (\Exception $e) {
            //         $this->log("WARNING: Could not configure PDO - " . $e->getMessage());
            //         $this->log("Exception type: " . get_class($e));
            //         // Don't fail here, try to continue
            //     }

            //     // Test with a simple query
            //     $this->log("Executing test query...");
            //     $result = $connection->select("SELECT 1 AS test FROM DUAL");

            //     $testEnd = microtime(true);
            //     $testDuration = round(($testEnd - $testStart) * 1000, 2);
            //     $this->log("Oracle connection successful (response time: {$testDuration}ms)");
            //     $this->log("Test result: " . json_encode($result));
            //     $this->log("Proceeding to query allocations...");
            // } catch (\PDOException $e) {
            //     $this->log("ERROR: Oracle PDO Exception");
            //     $this->log("Message: " . $e->getMessage());
            //     $this->log("Code: " . $e->getCode());
            //     $this->log("File: " . $e->getFile());
            //     $this->log("Line: " . $e->getLine());
            //     $this->log("Trace: " . $e->getTraceAsString());
            //     $this->log("Skipping warehouse {$warehouseCode}");
            //     continue;
            // } catch (\Exception $e) {
            //     $this->log("ERROR: Oracle connection failed");
            //     $this->log("Type: " . get_class($e));
            //     $this->log("Message: " . $e->getMessage());
            //     $this->log("Code: " . $e->getCode());
            //     $this->log("File: " . $e->getFile());
            //     $this->log("Line: " . $e->getLine());
            //     $this->log("Trace: " . $e->getTraceAsString());
            //     $this->log("Skipping warehouse {$warehouseCode}");
            //     continue;
            // }

            // Process SKUs in chunks to avoid query issues
            $chunkSize = 500;
            $skuChunks = array_chunk($allSkus, $chunkSize);
            $this->log("Processing in " . count($skuChunks) . " chunks of {$chunkSize} SKUs each");

            $allocations = [];

            foreach ($skuChunks as $chunkIndex => $skuChunk) {
                $chunkNum = $chunkIndex + 1;
                $this->log("Processing chunk {$chunkNum}/" . count($skuChunks) . " (" . count($skuChunk) . " SKUs)");

                $inClause = "'" . implode("','", $skuChunk) . "'";

                try {
                    $queryStart = microtime(true);
                    $this->log("Executing Oracle query for chunk {$chunkNum}...");
                    $this->log("→ Started at: " . date('H:i:s.u'));

                    // Ensure connection is still alive
                    DB::connection('oracle_wms')->getPdo();

                    $inventoryRows = DB::connection('oracle_rms')->select("
                        SELECT /*+ PARALLEL(4) */ 
                            ITEM AS item_id,
                            (STOCK_ON_HAND - TSF_RESERVED_QTY) AS available_qty
                        FROM ITEM_LOC_SOH
                        WHERE LOC = '{$warehouseCode}'
                            AND ITEM IN ({$inClause})
                    ");

                    $queryEnd = microtime(true);
                    $queryDuration = round($queryEnd - $queryStart, 2);

                    $this->log("→ Finished at: " . date('H:i:s.u'));
                    $this->log("→ Duration: {$queryDuration}s");
                    $this->log("Query executed, processing results...");

                    $resultCount = 0;
                    foreach ($inventoryRows as $row) {
                        $allocations[$row->item_id] = (int) $row->available_qty;  // ✓ FIXED: Use correct alias
                        $resultCount++;
                    }

                    $this->log("Chunk {$chunkNum} completed in {$queryDuration}s - Retrieved {$resultCount} allocations");
                } catch (\Exception $e) {
                    $this->log("ERROR: Chunk {$chunkNum} failed");
                    $this->log("Type: " . get_class($e));
                    $this->log("Message: " . $e->getMessage());
                    $this->log("Code: " . $e->getCode());
                    continue;
                }
            }

            $this->log("Total allocations retrieved: " . count($allocations) . " SKUs");

            // Fetch existing allocations for comparison
            $this->log("Fetching existing allocations for comparison...");
            $existingAllocations = DB::connection('mysql')
                ->table('product_wms_allocations')
                ->whereIn('sku', $allSkus)
                ->where('warehouse_code', $warehouseCode)
                ->get()
                ->keyBy('sku');

            // Update/Insert allocations with per-SKU logging
            $updated = 0;
            $inserted = 0;

            $warehouseName = strtoupper($this->warehouseMap[$warehouseCode] ?? 'Unknown Warehouse');

            $this->log("");
            $this->logRaw(str_repeat("=", 120));
            $this->logRaw("ALLOCATION UPDATE DETAILS - WAREHOUSE: {$warehouseCode} - {$warehouseName}");
            $this->logRaw(str_repeat("=", 120));
            $this->logRaw(sprintf("%-20s | %-25s | %-25s | %-20s", "SKU", "BEFORE (Actual/Virtual)", "AFTER (Actual/Virtual)", "STATUS"));
            $this->logRaw(str_repeat("-", 120));

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

                    $this->logRaw(sprintf("%-20s | %-25s | %-25s | %-20s", $sku, $before, $after, $status));
                } catch (\Exception $e) {
                    $this->logRaw(sprintf("%-20s | %-25s | %-25s | %-20s", $sku, "ERROR", "ERROR", "FAILED: " . $e->getMessage()));
                }
            }

            $this->logRaw(str_repeat("=", 120));
            $this->logRaw(sprintf("SUMMARY: %d Updated | %d Inserted | %d Total Processed", $updated, $inserted, count($allSkus)));
            $this->logRaw(str_repeat("=", 120));
            $this->log("");
            $this->log("Allocations: {$updated} updated, {$inserted} inserted");
            $grandTotalUpdated += $updated;
            $grandTotalInserted += $inserted;

            // // Query Oracle for case pack data
            // $this->log("Querying Oracle WMS for case pack data...");
            // $caseMap = [];

            // foreach ($skuChunks as $chunkIndex => $skuChunk) {
            //     $chunkNum = $chunkIndex + 1;
            //     $inClause = "'" . implode("','", $skuChunk) . "'";

            //     try {
            //         $queryStart = microtime(true);

            //         $caseRows = DB::connection('oracle_wms')->select("
            //             SELECT item_id, unit_qty
            //             FROM (
            //                 SELECT ci.item_id, ci.unit_qty,
            //                     ROW_NUMBER() OVER (PARTITION BY ci.item_id ORDER BY ci.unit_qty) AS rn
            //                 FROM rwms.container_item ci
            //                 WHERE ci.facility_id = '{$facilityId}'
            //                 AND ci.item_id IN ({$inClause})
            //             )
            //             WHERE rn <= 5
            //         ");

            //         $queryEnd = microtime(true);
            //         $queryDuration = round($queryEnd - $queryStart, 2);

            //         foreach ($caseRows as $row) {
            //             $caseMap[$row->item_id][] = $row->unit_qty;
            //         }

            //         $this->log("Case pack chunk {$chunkNum} completed in {$queryDuration}s");
            //     } catch (\Exception $e) {
            //         $this->log("ERROR: Case pack chunk {$chunkNum} failed - " . $e->getMessage());
            //         continue;
            //     }
            // }

            // $this->log("Total case packs retrieved: " . count($caseMap) . " SKUs");

            // // Update case pack in products tables for each store
            // $casePackProcessed = 0;
            // foreach ($stores as $store) {
            //     $tableName = "products_{$store}";

            //     if (!DB::connection('mysql')->getSchemaBuilder()->hasTable($tableName)) {
            //         continue;
            //     }

            //     $storeSkus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();

            //     foreach ($storeSkus as $sku) {
            //         if (isset($caseMap[$sku])) {
            //             try {
            //                 DB::connection('mysql')->table($tableName)
            //                     ->where('sku', $sku)
            //                     ->update([
            //                         'case_pack' => implode(' | ', array_unique($caseMap[$sku])),
            //                         'updated_at' => now()
            //                     ]);
            //                 $casePackProcessed++;
            //             } catch (\Exception $e) {
            //                 $this->log("Case pack update failed for SKU {$sku}: " . $e->getMessage());
            //             }
            //         }
            //     }
            // }

            // $this->log("Case packs: {$casePackProcessed} updated");
            // $grandTotalCasePack += $casePackProcessed;
        }

        // Final summary
        $endTime = microtime(true);
        $duration = $endTime - $this->processStartTime;
        $minutes = floor($duration / 60);
        $seconds = round($duration % 60);

        $this->log("=== All warehouses processed ===");
        $this->log("Total allocations updated: {$grandTotalUpdated}");
        $this->log("Total allocations inserted: {$grandTotalInserted}");
        // $this->log("Total case packs updated: {$grandTotalCasePack}");
        $this->log("Process completed in {$minutes}m {$seconds}s");

        return Command::SUCCESS;
    }

    /**
     * Collect SKUs from a specific table
     */
    protected function collectSkusFromTable($tableName)
    {
        try {
            $skus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();
            $this->log("Fetched " . count($skus) . " SKUs from {$tableName}");
            return array_unique($skus);
        } catch (\Exception $e) {
            $this->log("Failed to fetch SKUs from {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log with timestamp - uses file_put_contents with LOCK_EX for immediate write
     */
    private function log(string $message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";

        // Write to file immediately with exclusive lock
        if ($this->logFile) {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }

        // Output to console
        $this->info($message);
    }

    /**
     * Log without timestamp - for formatted output
     */
    private function logRaw(string $message)
    {
        if ($this->logFile) {
            file_put_contents($this->logFile, $message . "\n", FILE_APPEND | LOCK_EX);
        }

        $this->line($message);
    }
}
