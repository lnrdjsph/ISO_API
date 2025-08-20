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
        $startTime = microtime(true); // start timer
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
        $oracleTns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$oracleHost})(PORT={$oraclePort}))(CONNECT_DATA=(SERVICE_NAME={$oracleDb})))";

        $conn = oci_connect($oracleUser, $oraclePass, $oracleTns);
        if (!$conn) {
            $e = oci_error();
            $this->log($logFile, "Oracle connection failed: " . $e['message']);
            return;
        }

        $productTables = ['products_f2','products_h8'];

        foreach ($productTables as $tableName) {
            $this->log($logFile, "Processing table: {$tableName}");
            $products = DB::connection('mysql')->table($tableName)->select('sku')->get();

            foreach ($products as $product) {
                $sku = $product->sku;

                try {
                    // Allocation query
                    $sqlAlloc = "
                        SELECT SUM(ci.unit_qty) AS total_qty
                        FROM rwms.container c
                        JOIN rwms.container_item ci
                        ON c.facility_id = ci.facility_id
                        AND c.container_id = ci.container_id
                        WHERE ci.item_id = :sku
                        AND c.container_status NOT IN ('S','D','A')
                    ";
                    $stid = oci_parse($conn, $sqlAlloc);
                    oci_bind_by_name($stid, ':sku', $sku);
                    oci_execute($stid);
                    $row = oci_fetch_assoc($stid);
                    $totalQty = isset($row['TOTAL_QTY']) ? (int)$row['TOTAL_QTY'] : 0;

                    // Case pack query
                    $sqlCase = "
                        SELECT DISTINCT unit_qty AS case_pack
                        FROM rwms.container_item
                        WHERE item_id = :sku
                        AND container_qty = 1
                        AND LENGTH(distro_nbr) > 9
                        ORDER BY unit_qty DESC
                    ";
                    $stid2 = oci_parse($conn, $sqlCase);
                    oci_bind_by_name($stid2, ':sku', $sku);
                    oci_execute($stid2);

                    $casePackArray = [];
                    while ($r = oci_fetch_assoc($stid2)) {
                        if (isset($r['CASE_PACK'])) {
                            $casePackArray[] = $r['CASE_PACK'];
                        }
                    }
                    $casePackStr = implode(' | ', $casePackArray);
                    // Update MySQL
                    DB::connection('mysql')->table($tableName)
                        ->where('sku', $sku)
                        ->update([
                            'wms_allocation_per_case' => $totalQty,
                            'case_pack' => $casePackStr,
                            'updated_at' => now(),
                        ]);

                    $this->log($logFile, "Updated SKU: {$sku} | Allocation: {$totalQty} | CasePack: {$casePackStr}");
                } catch (\Throwable $e) {
                    $this->log($logFile, "Failed SKU: {$sku} | Error: " . $e->getMessage());
                }
            }
        }

        oci_close($conn);
        $this->log($logFile, "=== All products updated in all hardcoded products tables ===");
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        $this->log($logFile, "Process completed in {$minutes}m {$seconds}s.");
    }

    private function log(string $file, string $message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        File::append($file, "[{$timestamp}] {$message}\n");
        $this->info($message);
    }
}
