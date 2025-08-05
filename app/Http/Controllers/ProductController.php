<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Http\Requests\BulkUpdateProductsRequest;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use App\Events\ProductsBulkArchived;
use Illuminate\Support\Facades\Log;


class ProductController extends Controller
{
    public function __construct()
    {
        // Apply middleware
        $this->middleware('auth');
        // $this->middleware('throttle:bulk_operations,5,10')->only(['bulkUpdate', 'bulkArchive']);
    }
    // Show product search view
    public function index(Request $request)
    {
        try {
            $sort = $request->get('sort', 'sku');
            $direction = $request->get('direction', 'asc');

            $allowedSorts = ['sku', 'description'];
            if (!in_array(strtolower($sort), $allowedSorts)) {
                $sort = 'sku';
            }
            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                $direction = 'asc';
            }
            

            // Fetch paginated products from MySQL
            $products = DB::connection('mysql')
                ->table('products')
                ->select(
                    'id',
                    'sku',
                    'description',
                    'case_pack',
                    'srp',
                    'allocation_per_case',
                    'cash_bank_card_scheme',
                    'po15_scheme',
                    'freebie_sku',
                )
                ->orderBy($sort, $direction)
                ->paginate(10)
                ->appends($request->query());

            // Fetch freebie descriptions using freebie_sku matched against sku
            $freebieSkus = collect($products->items())->pluck('freebie_sku')->filter()->unique()->toArray();

            $freebieDescriptions = DB::connection('mysql')
                ->table('products')
                ->whereIn('sku', $freebieSkus)
                ->pluck('description', 'sku'); // [sku => description]

            foreach ($products as $product) {
                $product->freebie_description = $freebieDescriptions[$product->freebie_sku] ?? null;
            }

            // Enrich each product with Oracle RMS data
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
            ->select('sku as sku', 'description as description')
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(description) LIKE ?', ["%{$query}%"])
                ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$query}%"]);
            })
            ->whereNull('archived_at')
            ->get();

        return response()->json($results);
    }



   /**
     * Bulk update with enhanced security
     */

    public function bulkUpdate(BulkUpdateProductsRequest $request)
    {
        try {
            $productIds = $request->input('product_ids');
            
            // Build update array with only fields that have values
            $updateData = collect($request->validated())
                ->except(['product_ids'])
                ->filter(function ($value) {
                    return !is_null($value) && $value !== '';
                })
                ->toArray();

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fields to update. Please provide at least one field value.'
                ], 400);
            }

            DB::beginTransaction();
            
            // Use the model method if you're using Eloquent
            $updatedCount = Product::bulkUpdateFields($productIds, $updateData);
            
            // Or use raw query as shown earlier
            // $updatedCount = DB::table('products')->whereIn('id', $productIds)->update($updateData);

            $this->logBulkActivity('bulk_update', $productIds, $updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} products",
                'updated_count' => $updatedCount,
                'updated_fields' => array_keys($updateData)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk archive with enhanced security
     */
    public function bulkArchive(Request $request)
    {
        if (!Gate::allows('bulk-archive', Product::class)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to archive products.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1|max:' . config('app.max_bulk_operation_size', 100),
            'product_ids.*' => 'exists:products,id',
            'archive_reason' => 'nullable|string|max:500', // Optional reason for archiving
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $productIds = $request->input('product_ids');
            $archiveReason = $request->input('archive_reason');

            DB::beginTransaction();

            // Check if products are not already archived
            $nonArchivedCount = Product::whereIn('id', $productIds)
                ->whereNull('archived_at')
                ->count();

            if ($nonArchivedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected products are already archived.'
                ], 400);
            }

            $archivedCount = Product::whereIn('id', $productIds)
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => now(),
                    'archived_by' => auth()->id(),
                    'archive_reason' => $archiveReason,
                    'updated_at' => now()
                ]);

            // Log the bulk archive activity
            $this->logBulkActivity('bulk_archive', $productIds, [
                'reason' => $archiveReason
            ]);

            // Fire event
            event(new ProductsBulkArchived($productIds, auth()->user(), $archiveReason));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully archived {$archivedCount} products",
                'archived_count' => $archivedCount
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk archive failed', [
                'user_id' => auth()->id(),
                'product_ids' => $productIds ?? [],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while archiving products. Please try again.'
            ], 500);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'exists:mysql.products,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $productIds = $request->input('product_ids');

            DB::connection('mysql')->beginTransaction();
            $count = DB::connection('mysql')->table('products')
                ->whereIn('id', $productIds)
                ->whereNotNull('archived_at')
                ->update(['archived_at' => null, 'updated_at' => now()]);

            $this->logBulkActivity('bulk_restore', $productIds);
            DB::connection('mysql')->commit();

            return response()->json(['success' => true, 'message' => "Restored {$count} products", 'restored_count' => $count]);
        } catch (Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

        /**
     * Enhanced logging method
     */
    private function logBulkActivity($action, $productIds, $data = null)
    {
        try {
            $activityData = [
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $this->generateActivityDescription($action, count($productIds)),
                'properties' => json_encode([
                    'product_ids' => $productIds,
                    'product_count' => count($productIds),
                    'updated_fields' => $data ? array_keys($data) : null,
                    'data' => $data,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toISOString()
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table('activity_logs')->insert($activityData);

            // Also log to Laravel's log file for debugging
            Log::info("Bulk operation performed", $activityData);

        } catch (Exception $e) {
            Log::error('Failed to log bulk activity: ' . $e->getMessage());
        }
    }

    /**
     * Generate activity description
     */
    private function generateActivityDescription($action, $count)
    {
        $descriptions = [
            'bulk_update' => "Updated {$count} products via bulk operation",
            'bulk_archive' => "Archived {$count} products via bulk operation",
            'bulk_restore' => "Restored {$count} products via bulk operation"
        ];

        return $descriptions[$action] ?? "Performed {$action} on {$count} products";
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
        $descriptions = $request->input('description');

        // Validate arrays presence
        $request->validate([
            'sku' => 'required|array',
            'sku.*' => ['required', function ($attribute, $value, $fail) {
                $exists = DB::connection('mysql')
                    ->table('products') // Consistent table name
                    ->where('sku', strtoupper($value))
                    ->exists();
                if ($exists) {
                    $fail('The sku '.$value.' has already been taken.');
                }
            }],
            'description' => 'required|array',
            'description.*' => 'required|string',
        ]);

        // Prepare bulk insert data
        $insertData = [];
        foreach ($skus as $index => $sku) {
            $insertData[] = [
                'sku' => strtoupper($sku),
                'description' => $descriptions[$index],
                'CREATED_AT' => now(),
            ];
        }

        DB::connection('mysql')->table('products')->insert($insertData);

        return redirect()->back()->with('success', 'Products added successfully.');
    }

    public function getSkus()
    {
        $skus = DB::table('products')->pluck('sku')->map(fn($sku) => strtoupper($sku));
        return response()->json($skus);
    }
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        try {
            $file = $request->file('csv_file');
            $csvContent = file_get_contents($file->getRealPath());
            $lines = array_filter(
                preg_split('/\r\n|\r|\n/', trim($csvContent)),
                fn($line) => count(array_filter(str_getcsv(trim($line)), fn($col) => trim($col) !== '')) > 0
            );

            if (count($lines) < 2) {
                return redirect()->back()->with('import_errors', ['CSV must have header + at least 1 data row.']);
            }

            $dataLines = array_slice($lines, 1);
            $errors = [];
            $insertData = [];
            $updateData = [];

            $existingSkus = DB::table('products')->pluck('sku')->map(fn($sku) => strtoupper($sku))->toArray();

            foreach ($dataLines as $lineNumber => $line) {
                $rowNumber = $lineNumber + 2;
                $columns = str_getcsv(trim($line));

                if (count($columns) < 8) {
                    $errors[] = "Row {$rowNumber}: Missing required columns.";
                    continue;
                }

                [$sku, $description, $casePack, $srp, $allocationPerCase, $cashBankCardScheme, $po15Scheme, $freebieSku] = array_map('trim', $columns);

                // Field validations
                if (!$sku) {
                    $errors[] = "Row {$rowNumber}: SKU is required";
                    continue;
                }
                if (!$description) {
                    $errors[] = "Row {$rowNumber}: Product Description is required";
                    continue;
                }
                if (!is_numeric($casePack) || $casePack <= 0) {
                    $errors[] = "Row {$rowNumber}: Case Pack must be a positive number";
                    continue;
                }
                if (!is_numeric($srp) || $srp <= 0) {
                    $errors[] = "Row {$rowNumber}: SRP must be a positive number";
                    continue;
                }
                if (!is_numeric($allocationPerCase) || $allocationPerCase <= 0) {
                    $errors[] = "Row {$rowNumber}: Allocation Per Case must be a positive number";
                    continue;
                }
                if (!preg_match('/^\d+\+\d+$/', $cashBankCardScheme)) {
                    $errors[] = "Row {$rowNumber}: Cash/Bank/Card Scheme must be in 'number+number' format";
                    continue;
                }
                if (!preg_match('/^\d+\+\d+$/', $po15Scheme)) {
                    $errors[] = "Row {$rowNumber}: PO15 Scheme must be in 'number+number' format";
                    continue;
                }
                if (!$freebieSku) {
                    $errors[] = "Row {$rowNumber}: Freebie SKU is required";
                    continue;
                }

                $formattedSku = strtoupper($sku);

                $record = [
                    'sku' => $formattedSku,
                    'description' => $description,
                    'case_pack' => intval($casePack),
                    'srp' => floatval($srp),
                    'allocation_per_case' => intval($allocationPerCase),
                    'cash_bank_card_scheme' => $cashBankCardScheme,
                    'po15_scheme' => $po15Scheme,
                    'freebie_sku' => $freebieSku,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if (in_array($formattedSku, $existingSkus)) {
                    $updateData[] = $record;
                } else {
                    $insertData[] = $record;
                }
            }

            $allData = array_merge($insertData, $updateData);

            if (!empty($allData)) {
                DB::table('products')->upsert($allData, ['sku'], [
                    'description',
                    'case_pack',
                    'srp',
                    'allocation_per_case',
                    'cash_bank_card_scheme',
                    'po15_scheme',
                    'freebie_sku',
                    'updated_at'
                ]);
            }

            $insertedCount = count($insertData);
            $updatedCount = count($updateData);

            $summary = "Import complete: {$insertedCount} inserted, {$updatedCount} updated.";

            if (($insertedCount + $updatedCount) > 0 && empty($errors)) {
                return redirect()->back()->with('import_success', $summary);
            } elseif (!empty($errors)) {
                return redirect()->back()->with('import_success', $summary)->with('import_errors', $errors);
            } else {
                return redirect()->back()->with('import_errors', array_merge(['No products were imported.'], $errors));
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('import_errors', ['Import failed: ' . $e->getMessage()]);
        }
    }

    // Download CSV template
    public function downloadTemplate()
    {
        $csvContent = "sku,Product Description\n";
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
                    $errors[] = "Row {$rowNumber}: sku and Description are required";
                    continue;
                }

                $skusToCheck[] = strtoupper($sku);
                $validRows++;
            }

            // Check for existing skus in batch
            if (!empty($skusToCheck)) {
                $existingSkus = DB::connection('mysql')
                    ->table('products')
                    ->whereIn('sku', $skusToCheck)
                    ->pluck('sku')
                    ->toArray();

                foreach ($existingSkus as $existingSku) {
                    $errors[] = "sku '{$existingSku}' already exists in database";
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