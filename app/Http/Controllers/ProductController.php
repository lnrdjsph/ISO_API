<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProductController extends Controller
{
    // Show product search view
    public function index(Request $request)
    {
        try {
            $sort = $request->get('sort', 'SKU');
            $direction = $request->get('direction', 'asc');

            $allowedSorts = ['SKU', 'NAME'];
            if (!in_array(strtoupper($sort), $allowedSorts)) {
                $sort = 'SKU';
            }
            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                $direction = 'asc';
            }

            $products = DB::connection('mysql')
                ->table('products')
                ->select('SKU as sku', 'NAME as name')
                ->orderBy($sort, $direction)
                ->paginate(10)
                ->appends($request->query());

            foreach ($products as $product) {
                $oracleData = DB::connection('oracle_rms')->selectOne("
                    SELECT * FROM (
                        SELECT
                            item_master.item_parent AS sku,
                            item_master.item_desc AS description,
                            deps.dept_name AS department,
                            uda_values.uda_value_desc AS brand,
                            groups.group_name,
                            COALESCE(stock.stock_on_hand, 0) AS stock_on_hand,
                            class.class_name AS class_name
                        FROM item_supplier
                        LEFT JOIN item_master 
                            ON item_supplier.item = item_master.item
                        LEFT JOIN uda_item_lov 
                            ON item_supplier.item = uda_item_lov.item 
                            AND item_master.item = uda_item_lov.item
                        LEFT JOIN uda_values 
                            ON uda_item_lov.uda_value = uda_values.uda_value 
                            AND uda_item_lov.uda_id = uda_values.uda_id
                        LEFT JOIN deps 
                            ON deps.dept = item_master.dept
                        LEFT JOIN groups 
                            ON groups.group_no = deps.group_no
                        LEFT JOIN class 
                            ON class.dept = item_master.dept 
                            AND class.class = item_master.class
                        LEFT JOIN (
                            SELECT item, SUM(stock_on_hand) AS stock_on_hand
                            FROM item_loc_soh
                            GROUP BY item
                        ) stock 
                            ON stock.item = item_master.item
                        WHERE uda_values.uda_id = 9
                        AND item_master.item = ?
                    ) WHERE ROWNUM = 1
                ", [$product->sku]);


                if ($oracleData) {
                    $product->department = $oracleData->department ?? null;
                    $product->brand = $oracleData->brand ?? null;
                    $product->group_name = $oracleData->group_name ?? null;
                    $product->stock_on_hand = $oracleData->stock_on_hand ?? 0;
                    $product->class_name = $oracleData->class_name ?? null;
                }
            }

            return view('products.index', compact('products'));

        } catch (\Exception $e) {
            return view('errors.db_error', ['error' => $e->getMessage()]);
        }
    }


    // Handle AJAX product search
    public function search(Request $request)
    {
        $query = strtolower($request->query('query'));

        $results = DB::connection('mysql')
            ->table('products')
            ->select('SKU as sku', 'NAME as name')
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(NAME) LIKE ?', ["%{$query}%"])
                ->orWhereRaw('LOWER(SKU) LIKE ?', ["%{$query}%"]);
            })
            ->get();

        return response()->json($results);
    }






    // Show create product form
    public function create()
    {
        return view('products.create');
    }

    public function scheme()
    {
        return view('products.scheme');
    }

    // Store new product
    public function store(Request $request)
    {
        $skus = $request->input('sku');
        $names = $request->input('name');

        // Validate arrays presence
        $request->validate([
            'sku' => 'required|array',
            'sku.*' => ['required', function ($attribute, $value, $fail) {
                $exists = DB::connection('mysql')
                    ->table('products') // Consistent table name
                    ->where('SKU', strtoupper($value))
                    ->exists();
                if ($exists) {
                    $fail('The SKU '.$value.' has already been taken.');
                }
            }],
            'name' => 'required|array',
            'name.*' => 'required|string',
        ]);

        // Prepare bulk insert data
        $insertData = [];
        foreach ($skus as $index => $sku) {
            $insertData[] = [
                'SKU' => strtoupper($sku),
                'NAME' => $names[$index],
                'CREATED_AT' => now(),
            ];
        }

        DB::connection('mysql')->table('products')->insert($insertData);

        return redirect()->back()->with('success', 'Products added successfully.');
    }

    // Handle CSV import
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        try {
            $file = $request->file('csv_file');
            $csvContent = file_get_contents($file->getRealPath());
            $lines = array_filter(explode("\n", $csvContent), 'strlen');

            if (count($lines) < 2) {
                return redirect()->back()->with('import_errors', ['CSV file must contain at least a header and one data row.']);
            }

            // Skip header row
            $dataLines = array_slice($lines, 1);
            $successCount = 0;
            $errors = [];
            $insertData = [];

            foreach ($dataLines as $lineNumber => $line) {
                $rowNumber = $lineNumber + 2; // +2 because we start from line 1 and skip header
                $columns = str_getcsv(trim($line));

                // Validate row has exactly 2 columns
                if (count($columns) < 2) {
                    $errors[] = "Row {$rowNumber}: Missing required columns (SKU, Description)";
                    continue;
                }

                $sku = trim($columns[0]);
                $description = trim($columns[1]);

                // Validate required fields
                if (empty($sku)) {
                    $errors[] = "Row {$rowNumber}: SKU is required";
                    continue;
                }

                if (empty($description)) {
                    $errors[] = "Row {$rowNumber}: Product Description is required";
                    continue;
                }

                // Check if SKU already exists
                $exists = DB::connection('mysql')
                    ->table('products')
                    ->where('SKU', strtoupper($sku))
                    ->exists();

                if ($exists) {
                    $errors[] = "Row {$rowNumber}: SKU '{$sku}' already exists";
                    continue;
                }

                // Prepare data for insertion
                $insertData[] = [
                    'SKU' => strtoupper($sku),
                    'NAME' => $description,
                    'CREATED_AT' => now(),
                ];
                $successCount++;
            }

            // Bulk insert valid records
            if (!empty($insertData)) {
                DB::connection('mysql')->table('products')->insert($insertData);
            }

            // Prepare response messages
            if ($successCount > 0 && empty($errors)) {
                return redirect()->back()->with('import_success', "Successfully imported {$successCount} products.");
            } elseif ($successCount > 0 && !empty($errors)) {
                return redirect()->back()
                    ->with('import_success', "Successfully imported {$successCount} products.")
                    ->with('import_errors', $errors);
            } else {
                return redirect()->back()->with('import_errors', array_merge(['No products were imported.'], $errors));
            }

        } catch (Exception $e) {
            return redirect()->back()->with('import_errors', ['An error occurred during import: ' . $e->getMessage()]);
        }
    }

    // Download CSV template
    public function downloadTemplate()
    {
        $csvContent = "SKU,Product Description\n";
        $csvContent .= "ABC001,Premium Wireless Headphones\n";
        $csvContent .= "DEF002,Bluetooth Speaker System\n";
        $csvContent .= "GHI003,Smart Watch Series X\n";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ];

        return response($csvContent, 200, $headers);
    }

    // Show import view
    public function showImport()
    {
        return view('products.import');
    }

    // API endpoint for validating CSV data before import
    public function validateCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        try {
            $file = $request->file('csv_file');
            $csvContent = file_get_contents($file->getRealPath());
            $lines = array_filter(explode("\n", $csvContent), 'strlen');

            if (count($lines) < 2) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['CSV file must contain at least a header and one data row.']
                ]);
            }

            $dataLines = array_slice($lines, 1);
            $errors = [];
            $validRows = 0;
            $skusToCheck = [];

            foreach ($dataLines as $lineNumber => $line) {
                $rowNumber = $lineNumber + 2;
                $columns = str_getcsv(trim($line));

                if (count($columns) < 2) {
                    $errors[] = "Row {$rowNumber}: Missing required columns";
                    continue;
                }

                $sku = trim($columns[0]);
                $description = trim($columns[1]);

                if (empty($sku) || empty($description)) {
                    $errors[] = "Row {$rowNumber}: SKU and Description are required";
                    continue;
                }

                $skusToCheck[] = strtoupper($sku);
                $validRows++;
            }

            // Check for existing SKUs in batch
            if (!empty($skusToCheck)) {
                $existingSkus = DB::connection('mysql')
                    ->table('products')
                    ->whereIn('SKU', $skusToCheck)
                    ->pluck('SKU')
                    ->toArray();

                foreach ($existingSkus as $existingSku) {
                    $errors[] = "SKU '{$existingSku}' already exists in database";
                }
            }

            return response()->json([
                'valid' => empty($errors),
                'errors' => $errors,
                'valid_rows' => $validRows,
                'total_rows' => count($dataLines)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => ['Error processing file: ' . $e->getMessage()]
            ]);
        }
    }
}