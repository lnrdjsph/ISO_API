<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateAllProductAllocations extends Command
{
    // ✅ Add location option
    protected $signature = 'products:update-allocations {--location=}';
    protected $description = 'Update WMS allocation and case pack using oracle_wms config';

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

        // ✅ Pick tables based on location option
        $location = strtolower($this->option('location'));
        if ($location) {
            $productTables = ["products_{$location}"];
        } else {
            $productTables = ['products_f2', 'products_h8']; // fallback default
        }

        $this->log($logFile, "Tables to process: " . implode(', ', $productTables));

        $totalProcessed = 0;

        foreach ($productTables as $tableName) {
            $this->log($logFile, "---- Processing table: {$tableName} ----");

            $tableStartTime = microtime(true);
            $tableProcessed = 0;
            $tableSkipped   = 0;

            try {
                // 1. Fetch SKUs
                $this->log($logFile, "Fetching SKUs from {$tableName}...");
                $skus = DB::connection('mysql')->table($tableName)->pluck('sku')->toArray();
                $this->log($logFile, "Fetched " . count($skus) . " SKUs.");

                if (empty($skus)) {
                    $this->log($logFile, "No SKUs found in {$tableName}, skipping.");
                    continue;
                }

                $inClause = "'" . implode("','", $skus) . "'";

                // 2. Inventory Query
                $this->log($logFile, "Running Oracle inventory query...");
                $inventoryRows = [];
                try {
                    $inventoryRows = DB::connection('oracle_wms')->select("
                        SELECT ci.item_id,
                            SUM(CASE WHEN c.container_status NOT IN ('S','D','A') 
                                        THEN ci.unit_qty ELSE 0 END) AS total_qty
                        FROM rwms.container_item ci
                        JOIN rwms.container c
                        ON ci.facility_id = c.facility_id
                        AND ci.container_id = c.container_id
                        WHERE ci.item_id IN ({$inClause})
                        GROUP BY ci.item_id
                    ");
                    $this->log($logFile, "Inventory query returned " . count($inventoryRows) . " rows.");
                } catch (\Exception $e) {
                    $this->log($logFile, "Oracle inventory query failed: " . $e->getMessage());
                }

                $inventoryMap = [];
                foreach ($inventoryRows as $row) {
                    $inventoryMap[$row->item_id] = (int) $row->total_qty;
                }

                // 3. Case Pack Query
                $this->log($logFile, "Running Oracle case pack query...");
                $caseRows = [];
                try {
                    $caseRows = DB::connection('oracle_wms')->select("
                        SELECT item_id, unit_qty
                        FROM (
                            SELECT ci.item_id, ci.unit_qty,
                                ROW_NUMBER() OVER (PARTITION BY ci.item_id ORDER BY ci.unit_qty) AS rn
                            FROM rwms.container_item ci
                            WHERE ci.item_id IN ({$inClause})
                        )
                        WHERE rn <= 5
                    ");
                    $this->log($logFile, "Case pack query returned " . count($caseRows) . " rows.");
                } catch (\Exception $e) {
                    $this->log($logFile, "Oracle case pack query failed: " . $e->getMessage());
                }

                $caseMap = [];
                foreach ($caseRows as $row) {
                    $caseMap[$row->item_id][] = $row->unit_qty;
                }

                // 4. Apply updates per SKU
                $this->log($logFile, "Updating SKUs in {$tableName}...");
                foreach ($skus as $sku) {
                    $data = ['updated_at' => now()];

                    if (isset($inventoryMap[$sku])) {
                        $data['wms_allocation_per_case'] = $inventoryMap[$sku];
                    }

                    if (isset($caseMap[$sku])) {
                        $data['case_pack'] = implode(' | ', array_unique($caseMap[$sku]));
                    }

                    if (count($data) > 1) {
                        try {
                            DB::connection('mysql')->table($tableName)
                                ->where('sku', $sku)
                                ->update($data);

                            $tableProcessed++;
                            $this->log($logFile, "SKU {$sku} updated: " . json_encode($data));
                        } catch (\Exception $e) {
                            $this->log($logFile, "Update failed for SKU {$sku}: " . $e->getMessage());
                        }
                    } else {
                        $tableSkipped++;
                        $this->log($logFile, "SKU {$sku} skipped (no inventory/case pack).");
                    }
                }

                // 5. Finish table
                $tableEndTime = microtime(true);
                $duration = round($tableEndTime - $tableStartTime, 2);
                $this->log($logFile, "Table {$tableName} completed: {$tableProcessed} updated, {$tableSkipped} skipped in {$duration}s");

                $totalProcessed += $tableProcessed;
            } catch (\Exception $e) {
                $this->log($logFile, "Error processing table {$tableName}: " . $e->getMessage());
            }
        }

        $this->log($logFile, "=== All products updated ===");
        $this->log($logFile, "Total SKUs processed: {$totalProcessed}");

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $minutes = floor($duration / 60);
        $seconds = round($duration % 60);

        $this->log($logFile, "Process completed in {$minutes}m {$seconds}s");

        return Command::SUCCESS;
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
