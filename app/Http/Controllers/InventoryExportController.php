<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;

class InventoryExportController extends Controller
{
    public function showForm()
    {
        return view('inventory.upload');
    }

    public function export(Request $request)
    {
        $request->validate([
            'sku_csv'       => 'required|file|mimes:csv,txt',
            'store_codes'   => 'required|string',
            'export_format' => 'required|in:csv,excel',
        ]);

        $file   = $request->file('sku_csv')->getRealPath();
        $stores = array_map('trim', explode(',', $request->input('store_codes')));
        $format = $request->input('export_format');

        // ---- 1. Get store names ----
        $in       = implode(',', array_map(fn($c) => "'$c'", $stores));
        $storeMap = [];
        $query    = "SELECT store, store_name FROM store WHERE store IN ($in)";
        $rows     = DB::connection('oracle_rms')->select($query);

        foreach ($rows as $r) {
            $storeMap[$r->store] = $r->store_name;
        }
        if (empty($storeMap)) {
            return back()->withErrors(['store_codes' => 'No valid store codes found.']);
        }

        // ---- 2. Read SKUs from CSV ----
        $skus = [];
        if (($handle = fopen($file, "r")) !== false) {
            fgetcsv($handle, 1000, ","); // skip header
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (!empty($data[0])) $skus[] = trim($data[0]);
            }
            fclose($handle);
        }
        if (empty($skus)) {
            return back()->withErrors(['sku_csv' => 'No SKUs found.']);
        }
        $skuChunks = array_chunk($skus, 1000);

        // ---- 3. Generate CSV (pivot) ----
        if ($format === 'csv') {
            $pivot = [];
            foreach (array_keys($storeMap) as $storeCode) {
                foreach ($skuChunks as $batchSkus) {
                    $placeholders = implode(",", array_fill(0, count($batchSkus), '?'));
                    $sql = "
                        SELECT im.item AS sku, im.item_desc AS product_name,
                               il.loc AS store,
                               CASE
                                   WHEN ils.stock_on_hand <= 0 
                                        AND (d.purchase_type = 2 OR d.group_no IN (2020, 2030, 2040))
                                   THEN 1000
                                   ELSE ils.stock_on_hand
                               END AS stock
                        FROM item_master im
                        JOIN deps d ON im.dept = d.dept
                        JOIN item_loc il ON im.item = il.item
                        JOIN item_loc_soh ils ON il.loc = ils.loc AND il.item = ils.item
                        WHERE il.loc = ? AND im.item IN ($placeholders)
                    ";
                    $params = array_merge([$storeCode], $batchSkus);
                    $rows   = DB::connection('oracle_rms')->select($sql, $params);

                    foreach ($rows as $r) {
                        $sku = $r->sku;
                        if (!isset($pivot[$sku])) {
                            $pivot[$sku] = ['ITEM' => $sku, 'ITEM_DESC' => $r->product_name];
                        }
                        $pivot[$sku][$r->store] = $r->stock;
                    }
                }
            }

            if (empty($pivot)) {
                return back()->withErrors(['sku_csv' => 'No data found.']);
            }

            $filename = 'SKU_INVENTORY_' . implode('_', array_keys($storeMap)) . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\""
            ];

            $callback = function () use ($pivot, $storeMap) {
                $out = fopen('php://output', 'w');
                fputcsv($out, array_merge(['ITEM','ITEM_DESC'], array_values($storeMap)));
                foreach ($pivot as $row) {
                    $line = [$row['ITEM'], $row['ITEM_DESC']];
                    foreach (array_keys($storeMap) as $code) {
                        $line[] = $row[$code] ?? '';
                    }
                    fputcsv($out, $line);
                }
                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        // ---- 4. Generate Excel (separate sheets) ----
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // remove default empty sheet

        foreach ($storeMap as $storeCode => $storeName) {
            $storeData = [];
            foreach ($skuChunks as $batchSkus) {
                $placeholders = implode(",", array_fill(0, count($batchSkus), '?'));
                $sql = "
                    SELECT im.item AS sku, im.item_desc AS product_name,
                           CASE
                               WHEN ils.stock_on_hand <= 0 
                                    AND (d.purchase_type = 2 OR d.group_no IN (2020, 2030, 2040))
                               THEN 1000
                               ELSE ils.stock_on_hand
                           END AS stock
                    FROM item_master im
                    JOIN deps d ON im.dept = d.dept
                    JOIN item_loc il ON im.item = il.item
                    JOIN item_loc_soh ils ON il.loc = ils.loc AND il.item = ils.item
                    WHERE il.loc = ? AND im.item IN ($placeholders)
                ";
                $params = array_merge([$storeCode], $batchSkus);
                $rows   = DB::connection('oracle_rms')->select($sql, $params);

                foreach ($rows as $r) {
                    $storeData[] = [$r->sku, $r->product_name, $r->stock];
                }
            }

            // Create a new sheet per store
            $sheet = $spreadsheet->createSheet();
            $safeTitle = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $storeName), 0, 31);
            $sheet->setTitle($safeTitle ?: 'Sheet' . $storeCode);

            // Write headers + rows
            $sheet->fromArray([['ITEM', 'ITEM_DESC', 'STOCKS']], null, 'A1'); // must be nested
            if (!empty($storeData)) {
                $sheet->fromArray($storeData, null, 'A2');
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $fileName = 'SKU_INVENTORY_' . implode('_', array_keys($storeMap)) . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
