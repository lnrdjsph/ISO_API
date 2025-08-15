<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateAllProductAllocations extends Command
{
    protected $signature = 'products:update-allocations';
    protected $description = 'Update WMS allocation and case pack for hardcoded products tables';

    public function handle()
    {
        $date = now()->format('Y-m-d');
        $hour = now()->format('H'); // 24-hour format

        // Logs directory per day
        $logDir = storage_path("logs/wms_logs/{$date}");
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0777, true);
        }

        $logFile = "{$logDir}/allocations_{$hour}.log";

        $this->log($logFile, "=== Starting allocation update ===");

        try {
            // Hardcoded products tables
            $productTables = [
                'products_f2',
                'products_h8',
                // add more tables here if needed
            ];

            foreach ($productTables as $tableName) {
                $this->log($logFile, "Processing table: {$tableName}");

                $products = DB::connection('mysql')->table($tableName)->select('sku')->get();

                foreach ($products as $product) {
                    $sku = $product->sku;

                    try {
                        // Fetch allocation
                        $allocation = DB::connection('oracle_wms')->selectOne("
                            SELECT SUM(ci.unit_qty) AS total_unit_qty
                            FROM (
                                SELECT facility_id, container_id
                                FROM rwms.container
                                WHERE container_status NOT IN ('S','D','A')
                            ) c
                            JOIN (
                                SELECT facility_id, container_id, unit_qty
                                FROM rwms.container_item
                                WHERE item_id = ?
                            ) ci
                            ON c.facility_id = ci.facility_id
                            AND c.container_id = ci.container_id
                        ", [$sku]);

                        $totalQty = $allocation->total_unit_qty ?? 0;

                        // Fetch distinct case_pack
                        $casePackRows = DB::connection('oracle_wms')->select("
                            SELECT DISTINCT unit_qty
                            FROM rwms.container_item
                            WHERE item_id = ?
                            AND container_qty = 1
                            AND LENGTH(distro_nbr) > 9
                            ORDER BY unit_qty DESC
                        ", [$sku]);

                        $casePackArray = array_map(fn($r) => $r->unit_qty, $casePackRows);
                        $casePackStr = implode(' | ', $casePackArray);

                        // Update table
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

            $this->log($logFile, "=== All products updated in all hardcoded products tables ===");
        } catch (\Throwable $e) {
            $this->log($logFile, "Failed updating allocations: " . $e->getMessage());
        }
    }

    private function log(string $file, string $message)
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        File::append($file, "[{$timestamp}] {$message}\n");
        $this->info($message);
    }
}
