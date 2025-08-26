<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateAllProductAllocations extends Command
{
    protected $signature = 'products:update-allocations';
    protected $description = 'Update WMS allocation and case pack separately for hardcoded products tables using oracle_wms config';

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

        $productTables = ['products_f2', 'products_h8'];
        $totalProcessed = 0;

        foreach ($productTables as $tableName) {
            $this->log($logFile, "Processing table: {$tableName}");

            $tableStartTime = microtime(true);
            $tableProcessed = 0;

            try {
                // Get all SKUs in this table
                $skus = DB::connection('mysql')->table($tableName)
                    ->pluck('sku')
                    ->toArray();

                if (empty($skus)) {
                    $this->log($logFile, "No SKUs found in {$tableName}");
                    continue;
                }

                $inClause = "'" . implode("','", $skus) . "'";

                /** ------------------------
                 * INVENTORY QUERY (allocations)
                 * ------------------------
                 */
                $invSql = "
                    SELECT 
                        ci.item_id,
                        SUM(CASE WHEN c.container_status NOT IN ('S','D','A') 
                            THEN ci.unit_qty ELSE 0 END) AS total_qty
                    FROM rwms.container_item ci
                    JOIN rwms.container c
                        ON ci.facility_id = c.facility_id
                        AND ci.container_id = c.container_id
                    WHERE ci.item_id IN ({$inClause})
                    GROUP BY ci.item_id
                ";

                $inventoryRows = [];
                try {
                    $inventoryRows = DB::connection('oracle_wms')->select($invSql);
                } catch (\Exception $e) {
                    $this->log($logFile, "Oracle inventory query failed: " . $e->getMessage());
                }

                $inventoryMap = [];
                foreach ($inventoryRows as $row) {
                    $inventoryMap[$row->item_id] = (int)$row->total_qty;
                }

                /** ------------------------
                 * CASE PACK QUERY
                 * ------------------------
                 */
                $caseSql = "
                SELECT item_id, unit_qty
                FROM (
                    SELECT ci.item_id, ci.unit_qty,
                        ROW_NUMBER() OVER (PARTITION BY ci.item_id ORDER BY ci.unit_qty) AS rn
                    FROM rwms.container_item ci
                    WHERE ci.item_id IN ({$inClause})
                )
                WHERE rn <= 5
                ";


                $caseRows = [];
                try {
                    $caseRows = DB::connection('oracle_wms')->select($caseSql);
                } catch (\Exception $e) {
                    $this->log($logFile, "Oracle case pack query failed: " . $e->getMessage());
                }

                $caseMap = [];
                foreach ($caseRows as $row) {
                    if (!isset($caseMap[$row->item_id])) {
                        $caseMap[$row->item_id] = [];
                    }
                    $caseMap[$row->item_id][] = $row->unit_qty;
                }

                /** ------------------------
                 * APPLY UPDATES (per SKU)
                 * ------------------------
                 */
                foreach ($skus as $sku) {
                    $data = ['updated_at' => now()];

                    if (isset($inventoryMap[$sku])) {
                        $data['wms_allocation_per_case'] = $inventoryMap[$sku];
                    }

                    if (isset($caseMap[$sku])) {
                        $data['case_pack'] = implode(' | ', array_unique($caseMap[$sku]));
                    }

                    if (count($data) > 1) { // skip if nothing to update
                        try {
                            DB::connection('mysql')->table($tableName)
                                ->where('sku', $sku)
                                ->update($data);

                            $tableProcessed++;

                            // Log per SKU update
                            $logMessage = "SKU {$sku} updated";
                            if (isset($inventoryMap[$sku])) {
                                $logMessage .= ", wms_allocation_per_case: {$inventoryMap[$sku]}";
                            }
                            if (isset($caseMap[$sku])) {
                                $logMessage .= ", case_pack: " . implode(' | ', array_unique($caseMap[$sku]));
                            }
                            $this->log($logFile, $logMessage);

                        } catch (\Exception $e) {
                            $this->log($logFile, "Update failed for SKU {$sku}: " . $e->getMessage());
                        }
                    }
                }


                $tableEndTime = microtime(true);
                $duration = $tableEndTime - $tableStartTime;
                $this->log($logFile, "Table {$tableName} completed: {$tableProcessed} SKUs processed in " . round($duration, 2) . "s");

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
        flush(); // Force output immediately
    }

}
