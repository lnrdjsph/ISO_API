<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateAllProductAllocations extends Command
{
    protected $signature = 'products:update-allocations';
    protected $description = 'Update WMS allocation and case pack for hardcoded products tables using oci8';

    public function handle()
    {
        $startTime = microtime(true);
        $date = now()->format('Y-m-d');
        $hour = now()->format('H');

        // Logs directory
        $logDir = storage_path("logs/wms_logs/{$date}");
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0777, true);
        }
        $logFile = "{$logDir}/allocations_{$hour}.log";

        $this->log($logFile, "=== Starting allocation update ===");

        // Oracle connection using your current .env variables
        $oracleUser = env('ORACLE_WMS_USERNAME', 'shop_metro');
        $oraclePass = env('ORACLE_WMS_PASSWORD', 'somethingforyou');
        $oracleHost = env('ORACLE_WMS_HOST','sitwmsdb.metro.com.ph');
        $oraclePort = env('ORACLE_WMS_PORT', 1521);
        $oracleDb   = env('ORACLE_WMS_DATABASE');

        // Build TNS string
        $oracleTns = "{$oracleHost}:{$oraclePort}/{$oracleDb}";
        $conn = oci_connect($oracleUser, $oraclePass, $oracleTns);
        if (!$conn) {
            $e = oci_error();
            $this->log($logFile, "Oracle connection failed: " . $e['message']);
            return Command::FAILURE;
        }

        $this->log($logFile, "Oracle connection established successfully");

        $productTables = ['products_f2','products_h8'];
        $totalProcessed = 0;

        foreach ($productTables as $tableName) {
            $this->log($logFile, "Processing table: {$tableName}");
            
            $tableStartTime = microtime(true);
            $tableProcessed = 0;

            try {
                $chunkSize = 500;
                DB::connection('mysql')->table($tableName)
                    ->select('sku')
                    ->orderBy('sku')
                    ->chunk($chunkSize, function($products) use ($conn, $tableName, $logFile, &$tableProcessed) {

                        $skuChunk = $products->pluck('sku')->toArray();
                        if (empty($skuChunk)) return;

                        $this->log($logFile, "Processing chunk of " . count($skuChunk) . " SKUs");

                        $inClause = "'" . implode("','", $skuChunk) . "'";

                        // Fetch allocation and unit_qty separately to avoid LISTAGG overflow
                        $sql = "
                            SELECT 
                                ci.item_id,
                                SUM(CASE WHEN c.container_status NOT IN ('S','D','A') THEN ci.unit_qty ELSE 0 END) AS total_qty,
                                ci.unit_qty AS single_qty
                            FROM rwms.container_item ci
                            JOIN rwms.container c
                                ON ci.facility_id = c.facility_id
                                AND ci.container_id = c.container_id
                            WHERE ci.item_id IN ({$inClause})
                            GROUP BY ci.item_id, ci.unit_qty
                        ";

                        $stid = oci_parse($conn, $sql);
                        if (!$stid) {
                            $e = oci_error($conn);
                            $this->log($logFile, "Oracle parse failed: " . $e['message']);
                            return;
                        }

                        if (!oci_execute($stid)) {
                            $e = oci_error($stid);
                            $this->log($logFile, "Oracle execute failed: " . $e['message']);
                            return;
                        }

                        $updates = [];
                        $casePackMap = [];

                        while ($row = oci_fetch_assoc($stid)) {
                            $sku = $row['ITEM_ID'];
                            $totalQty = isset($row['TOTAL_QTY']) ? (int)$row['TOTAL_QTY'] : 0;
                            $unitQty = isset($row['SINGLE_QTY']) ? $row['SINGLE_QTY'] : null;

                            $updates[$sku]['wms_allocation_per_case'] = $totalQty;
                            $updates[$sku]['updated_at'] = now();

                            if ($unitQty) {
                                if (!isset($casePackMap[$sku])) $casePackMap[$sku] = [];
                                $casePackMap[$sku][] = $unitQty;
                            }
                        }

                        oci_free_statement($stid);

                        // Build case_pack string in PHP
                        foreach ($casePackMap as $sku => $arr) {
                            $updates[$sku]['case_pack'] = implode(' | ', array_unique($arr));
                        }

                        // Log summary instead of individual SKUs
                        $updatedCount = count($updates);
                        $this->log($logFile, "Prepared updates for {$updatedCount} SKUs in this chunk");

                        // Batch update MySQL with error handling
                        $batchErrors = 0;
                        foreach (array_chunk($updates, 100) as $batchIndex => $batch) {
                            try {
                                foreach ($batch as $sku => $data) {
                                    DB::connection('mysql')->table($tableName)
                                        ->where('sku', $sku)
                                        ->update($data);
                                }
                            } catch (\Exception $e) {
                                $batchErrors++;
                                $this->log($logFile, "Batch update error for batch {$batchIndex}: " . $e->getMessage());
                            }
                        }

                        $tableProcessed += $updatedCount;
                        
                        if ($batchErrors > 0) {
                            $this->log($logFile, "Chunk completed with {$batchErrors} batch errors");
                        }
                    });

                $tableEndTime = microtime(true);
                $tableDuration = $tableEndTime - $tableStartTime;
                $tableMinutes = floor($tableDuration / 60);
                $tableSeconds = round($tableDuration - ($tableMinutes * 60), 2);

                $this->log($logFile, "Table {$tableName} completed: {$tableProcessed} SKUs processed in {$tableMinutes}m {$tableSeconds}s");
                $totalProcessed += $tableProcessed;

            } catch (\Exception $e) {
                $this->log($logFile, "Error processing table {$tableName}: " . $e->getMessage());
            }
        }

        oci_close($conn);
        $this->log($logFile, "=== All products updated in all hardcoded products tables ===");
        $this->log($logFile, "Total SKUs processed: {$totalProcessed}");
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $minutes = floor($duration / 60);
        $seconds = round($duration - ($minutes * 60), 2);

        $this->log($logFile, "Process completed in {$minutes}m {$seconds}s.");
        
        return Command::SUCCESS;
    }

    private function log(string $file, string $message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        File::append($file, "[{$timestamp}] {$message}\n");
        $this->info($message);
    }
}