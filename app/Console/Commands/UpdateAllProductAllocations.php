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
                            {--warehouse= : Specific warehouse code to process (e.g., 80181, 80151)}
                            {--async : Use async job queue instead of batch processing}';

    protected $description = 'Update product_wms_allocations table (wms_actual_allocation and wms_virtual_allocation) and case pack using oracle_rms config';

    // Master mapping: Warehouse Code => [Facility Label, Store Codes]
    protected array $warehouseConfig = [
        '80181' => ['facility' => 'BD', 'stores' => ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010']],                    // Bacolod Depot
        '80151' => ['facility' => 'SI', 'stores' => ['6012']],                    // Silangan Warehouse
    ];

    protected array $warehouseMap = [
        '80151' => 'Silangan Warehouse',
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

        $lock = cache()->lock('allocation-update-lock', 3600);

        if (!$lock->get()) {
            $this->log("Another allocation process is already running. Exiting.");
            return Command::FAILURE;
        }

        try {

            $this->processStartTime = microtime(true);
            $phNow = now()->timezone('Asia/Manila');
            $date = $phNow->format('Y-m-d');
            $hour = $phNow->format('H');

            $logDir = storage_path("logs/wms_logs/{$date}");
            if (!File::exists($logDir)) {
                File::makeDirectory($logDir, 0777, true);
            }
            $this->logFile = "{$logDir}/allocations_{$hour}.log";

            register_shutdown_function(function () {
                $this->handleShutdown();
            });

            $this->log("=== Starting allocation update ===");

            if (!$this->checkDependencies()) {
                $this->log("Dependency check failed. Aborting process.");
                return Command::FAILURE;
            }

            $asyncMode = $this->option('async');

            if ($asyncMode) {
                $this->log("Running in ASYNC mode (using job queue)");
                return $this->handleAsync();
            }

            $this->log("Running in BATCH mode (synchronous)");
            return $this->handleBatch();
        } finally {
            $lock->release();
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
                if (!DB::getSchemaBuilder()->hasTable($tableName)) {
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

            // FIX 1: Purge and reconnect oracle_rms (matching FetchAllocationJob behavior)
            try {
                $this->log("Establishing fresh Oracle RMS connection...");
                DB::purge('oracle_rms');
                $oracle = DB::connection('oracle_rms');
                $pdo = $oracle->getPdo();

                // Configure PDO attributes for reliability
                $pdo->setAttribute(PDO::ATTR_TIMEOUT, 60);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Verify connection with a test query
                $testStart = microtime(true);
                $oracle->select("SELECT 1 AS test FROM DUAL");
                $testDuration = round((microtime(true) - $testStart) * 1000, 2);

                $this->log("Oracle RMS connection verified (response time: {$testDuration}ms)");
            } catch (\Exception $e) {
                $this->log("ERROR: Oracle RMS connection failed for warehouse {$warehouseCode}");
                $this->log("Type: " . get_class($e));
                $this->log("Message: " . $e->getMessage());
                $this->log("Skipping warehouse {$warehouseCode}");
                continue;
            }

            // Process SKUs in chunks
            $chunkSize = 500;
            $skuChunks = array_chunk($allSkus, $chunkSize);
            $this->log("Processing in " . count($skuChunks) . " chunks of {$chunkSize} SKUs each");

            $allocations = [];

            foreach ($skuChunks as $chunkIndex => $skuChunk) {
                $chunkNum = $chunkIndex + 1;
                $this->log("Processing chunk {$chunkNum}/" . count($skuChunks) . " (" . count($skuChunk) . " SKUs)");

                // FIX 2: Use parameterized placeholders for the IN clause
                $placeholders = implode(',', array_fill(0, count($skuChunk), '?'));
                $bindings = array_merge([$warehouseCode], array_values($skuChunk));

                try {
                    $queryStart = microtime(true);
                    $this->log("Executing Oracle RMS query for chunk {$chunkNum}...");
                    $this->log("→ Started at: " . date('H:i:s.u'));

                    // FIX 3: Use oracle_rms consistently (not oracle_wms) and reuse $oracle
                    // Verify connection is still alive before each chunk
                    try {
                        $oracle->select("SELECT 1 FROM DUAL");
                    } catch (\Exception $e) {
                        $this->log("Connection stale, reconnecting...");
                        DB::purge('oracle_rms');
                        $oracle = DB::connection('oracle_rms');
                        $oracle->getPdo();
                    }

                    $inventoryRows = $oracle->select("
                        SELECT /*+ PARALLEL(4) */
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
                            AND ITEM IN ({$placeholders})
                    ", $bindings);

                    $queryEnd = microtime(true);
                    $queryDuration = round($queryEnd - $queryStart, 2);

                    $this->log("→ Finished at: " . date('H:i:s.u'));
                    $this->log("→ Duration: {$queryDuration}s");
                    $this->log("Query executed, processing results...");

                    $resultCount = 0;
                    foreach ($inventoryRows as $row) {
                        // FIX 4: Guard against negative values (matching FetchAllocationJob)
                        $allocations[$row->item_id] = max(0, (int) $row->available_qty);
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

                        // FIX 5: Use updateOrInsert for consistency with FetchAllocationJob
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
        }

        // Final summary
        $endTime = microtime(true);
        $duration = $endTime - $this->processStartTime;
        $minutes = floor($duration / 60);
        $seconds = round($duration % 60);

        $this->log("=== All warehouses processed ===");
        $this->log("Total allocations updated: {$grandTotalUpdated}");
        $this->log("Total allocations inserted: {$grandTotalInserted}");
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

    protected function checkDependencies()
    {
        $this->log("Running system dependency checks...");

        // Check MySQL
        try {
            DB::connection('mysql')->select('SELECT 1');
            $this->log("MySQL connection OK");
        } catch (\Exception $e) {
            $this->log("CRITICAL: MySQL database is unreachable.");
            $this->log("Error: " . $e->getMessage());
            return false;
        }

        // Check Oracle RMS
        try {
            DB::purge('oracle_rms');
            $oracle = DB::connection('oracle_rms');
            $oracle->select('SELECT 1 FROM DUAL');
            $this->log("Oracle RMS connection OK");
        } catch (\Exception $e) {
            $this->log("CRITICAL: Oracle RMS database is unreachable.");
            $this->log("Error: " . $e->getMessage());
            return false;
        }

        return true;
    }
}
