<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Http\Requests\BulkUpdateProductsRequest;
// use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use App\Events\ProductsBulkArchived;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Response;
// use App\Jobs\UpdateWmsAllocationsJob;
use App\Jobs\FetchAllocationJob;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
// use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use App\Support\LocationConfig;




class ProductController extends Controller
{
    public function __construct()
    {
        // Apply middleware
        $this->middleware('auth');
        // $this->middleware('throttle:bulk_operations,5,10')->only(['bulkUpdate', 'bulkArchive']);
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $userLocation = strtolower($user->user_location ?? '');
            $tableStoreCode = $this->resolveTableStoreCode();
            $tableName = "products_{$tableStoreCode}";

            if (!Schema::connection('mysql')->hasTable($tableName)) {
                throw new \Exception("The database table '{$tableName}' does not exist.");
            }


            $warehouseMap = LocationConfig::warehouses();

            $isPersonnel = str_contains(strtolower($user->role ?? ''), 'personnel');

            // Determine current warehouse
            $currentWarehouse = $this->getWarehouseCode($request);

            // Sorting
            $sort = $request->get('sort', 'description');
            $direction = $request->get('direction', 'asc');
            $allowedSorts = ['sku', 'description'];
            $sort = in_array(strtolower($sort), $allowedSorts) ? $sort : 'sku';
            $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

            // Search
            $search = strtolower($request->get('query', ''));

            // Base query
            $productsQuery = DB::connection('mysql')
                ->table($tableName)
                ->select(
                    "$tableName.id",
                    "$tableName.sku",
                    "$tableName.description",
                    "$tableName.department_code",
                    "$tableName.department",
                    "$tableName.allocation_per_case",
                    "$tableName.initial_allocation_per_case",
                    "$tableName.case_pack",
                    "$tableName.srp",
                    "$tableName.cash_bank_card_scheme",
                    "$tableName.po15_scheme",
                    "$tableName.discount_scheme",
                    "$tableName.freebie_sku",
                    "wms.wms_virtual_allocation AS warehouse_allocation",
                    "wms.wms_actual_allocation AS warehouse_actual_allocation",
                    "$tableName.updated_at"
                )
                ->leftJoin('product_wms_allocations as wms', function ($join) use ($tableName, $currentWarehouse) {
                    $join->on("$tableName.sku", '=', 'wms.sku')
                        ->where('wms.warehouse_code', $currentWarehouse);
                })
                ->whereNull("$tableName.archived_at");

            // Apply search filter
            if ($search) {
                $productsQuery->where(function ($q) use ($search, $tableName) {
                    $q->whereRaw("LOWER($tableName.description) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER($tableName.sku) LIKE ?", ["%{$search}%"]);

                    if (preg_match('/^[a-zA-Z0-9\-]+$/', $search)) {
                        $q->orWhereRaw("LOWER($tableName.sku) = ?", [$search]);
                    }
                });
            }

            $perPage = $request->get('per_page', 10);

            // Get paginated products
            $products = $productsQuery
                ->orderBy($sort, $direction)
                ->paginate($perPage)
                ->appends($request->query())
                ->appends(['warehouse' => $currentWarehouse]);

            // Fetch freebie descriptions
            $freebieSkus = collect($products->items())
                ->pluck('freebie_sku')
                ->filter()
                ->unique()
                ->toArray();

            $freebieDescriptions = DB::connection('mysql')
                ->table($tableName)
                ->whereIn('sku', $freebieSkus)
                ->pluck('description', 'sku');

            foreach ($products as $product) {
                $product->freebie_description = $freebieDescriptions[$product->freebie_sku] ?? null;
            }

            return view('products.index', [
                'products'         => $products,
                'warehouseMap'     => LocationConfig::warehouses(),
                'currentWarehouse' => $currentWarehouse,
                'isPersonnel'      => $isPersonnel,
                'totalProducts'    => $products->total(),
                'currentStore'     => $tableStoreCode,               // add
                'storeMap'         => LocationConfig::accessibleStores(  // add
                    auth()->user()->role ?? '',
                    auth()->user()->user_location ?? ''
                ),
            ]);
        } catch (\Exception $e) {
            return view('errors.db_error', ['error' => $e->getMessage()]);
        }
    }




    public function search(Request $request)
    {
        $query = trim($request->query('query', ''));

        if (!$query) {
            return response()->json([]);
        }

        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $this->resolveTableStoreCode(request('store'));

        if (!Schema::connection('mysql')->hasTable($tableName)) {
            return response()->json([]);
        }

        $results = DB::connection('mysql')
            ->table($tableName)
            ->select('sku', 'description', 'department', 'department_code')
            ->where(function ($q) use ($query) {
                $q->where('description', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%")
                    ->orWhereRaw("TRIM(IFNULL(department, '')) LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("TRIM(IFNULL(department_code, '')) LIKE ?", ["%{$query}%"]);
            })
            ->whereNull('archived_at')
            ->limit(10)
            ->get();


        return response()->json($results);
    }





    public function export(Request $request)
    {
        try {
            // 🏬 Get user location → build table name
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = 'products_' . $this->resolveTableStoreCode(request('store'));

            // ✅ Validate that table exists before querying
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                throw new \Exception("The database table '{$tableName}' does not exist.");
            }

            $productsQuery = DB::connection('mysql')
                ->table($tableName)
                ->select(
                    'sku',
                    'description',
                    'allocation_per_case',
                    'case_pack',
                    'srp',
                    'cash_bank_card_scheme',
                    'po15_scheme',
                    'discount_scheme',
                    'freebie_sku',
                    'department_code',
                    'department'
                )
                ->whereNull('archived_at'); // keep consistent with your index()

            // 🎯 If specific product IDs are passed
            if ($request->filled('product_ids')) {
                $productIds = $request->input('product_ids');
                $productsQuery->whereIn('id', (array) $productIds);
            }

            // 🎯 Or if SKUs are provided
            if ($request->filled('sku')) {
                $skus = explode(',', $request->sku);
                $productsQuery->whereIn('sku', $skus);
            }

            $products = $productsQuery->get();

            // 📂 Set dynamic filename
            $filename = 'products_export_' . $userLocation . '_' . date('Ymd_His') . '.csv';

            // 📄 Headers
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            // 📤 Stream CSV
            $callback = function () use ($products) {
                $handle = fopen('php://output', 'w');

                // CSV header
                fputcsv($handle, [
                    'SKU',
                    'Description',
                    'Allocation per Case',
                    'Case Pack',
                    'SRP',
                    'Cash/Bank/Card Scheme',
                    'PO15 Scheme',
                    'Discount Scheme',
                    'Freebie SKU',
                    'Sub-Department Code',
                    'Sub-Department Name'
                ]);

                // Rows
                foreach ($products as $product) {
                    // Normalize product to array so static analysis sees scalar values and we avoid "TValue" issues
                    $row = is_array($product) ? $product : (array) $product;

                    fputcsv($handle, [
                        $row['sku'] ?? '',
                        $row['description'] ?? '',
                        $row['allocation_per_case'] ?? '',
                        $row['case_pack'] ?? '',
                        $row['srp'] ?? '',
                        $row['cash_bank_card_scheme'] ?? '',
                        $row['po15_scheme'] ?? '',
                        $row['discount_scheme'] ?? '',
                        $row['freebie_sku'] ?? '',
                        $row['department_code'] ?? '',
                        $row['department'] ?? ''
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }





    public function bulkUpdate(BulkUpdateProductsRequest $request)
    {
        try {
            $productIds = $request->input('product_ids');
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = 'products_' . $this->resolveTableStoreCode(request('store'));

            // Build update array with only fields that have values
            $updateData = collect($request->validated())
                ->except(['product_ids'])
                ->filter(fn($value) => !is_null($value) && $value !== '')
                ->toArray();

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No fields to update. Please provide at least one field value.'
                ], 400);
            }

            DB::beginTransaction();

            // Use query builder to update in dynamic table
            $updatedCount = DB::table($tableName)
                ->whereIn('id', $productIds)
                ->update($updateData);

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

        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $this->resolveTableStoreCode(request('store'));

        // Custom validation because 'exists' rule doesn't support dynamic tables well
        $productIds = $request->input('product_ids', []);

        $validator = Validator::make($request->all(), [
            'product_ids' => ['required', 'array', 'min:1', 'max:' . config('app.max_bulk_operation_size', 100)],
            'archive_reason' => 'nullable|string|max:500',
        ]);

        $validator->after(function ($validator) use ($productIds, $tableName) {
            if (!empty($productIds)) {
                $existingIds = DB::connection('mysql')->table($tableName)
                    ->whereIn('id', $productIds)
                    ->pluck('id')
                    ->toArray();

                $missing = array_diff($productIds, $existingIds);

                if (!empty($missing)) {
                    $validator->errors()->add('product_ids', 'Some product IDs do not exist or do not belong to your location.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check products not already archived in the location table
            $nonArchivedCount = DB::connection('mysql')->table($tableName)
                ->whereIn('id', $productIds)
                ->whereNull('archived_at')
                ->count();

            if ($nonArchivedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected products are already archived.'
                ], 400);
            }

            $archivedCount = DB::connection('mysql')->table($tableName)
                ->whereIn('id', $productIds)
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => now(),
                    'archived_by' => auth()->id(),
                    'archive_reason' => $request->input('archive_reason'),
                    'updated_at' => now()
                ]);

            // Log and fire event (adjust Product model usage if needed)
            $this->logBulkActivity('bulk_archive', $productIds, [
                'reason' => $request->input('archive_reason')
            ]);

            event(new ProductsBulkArchived($productIds, auth()->user(), $request->input('archive_reason')));

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
                'product_ids' => $productIds,
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

    public function store(Request $request)
    {
        $skus                 = $request->input('sku');
        $descriptions         = $request->input('description');
        $casePacks            = $request->input('case_pack');
        $srps                 = $request->input('srp');
        $allocationPerCases   = $request->input('allocation_per_case');
        $casePacks            = $request->input('case_pack'); // fix input name here
        $cashBankCardSchemes  = $request->input('cbc_scheme'); // fix input name here
        $po15Schemes          = $request->input('po15_scheme');
        $discountSchemes     = $request->input('discount_scheme'); // fix input name here
        $freebieSkus          = $request->input('freebie_sku');

        // Validate arrays presence
        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $this->resolveTableStoreCode(request('store'));

        $request->validate([
            'sku' => 'required|array',
            'sku.*' => [
                'required',
                function ($attribute, $value, $fail) use ($tableName) {
                    $exists = DB::connection('mysql')
                        ->table($tableName)
                        ->where('sku', strtoupper($value))
                        ->exists();
                    if ($exists) {
                        $fail('The sku ' . $value . ' has already been taken.');
                    }
                }
            ],
            'description' => 'required|array',
            'description.*' => 'required|string',

            'case_pack' => 'nullable|array',
            'case_pack.*' => 'nullable|numeric',

            'srp' => 'nullable|array',
            'srp.*' => 'nullable|numeric',

            'allocation_per_case' => 'nullable|array',
            'allocation_per_case.*' => 'nullable|numeric',

            'cbc_scheme' => 'nullable|array',
            'cbc_scheme.*' => 'nullable|string',

            'po15_scheme' => 'nullable|array',
            'po15_scheme.*' => 'nullable|string',

            'discount_scheme' => 'nullable|array',
            'discount_scheme.*' => 'nullable|string',

            'freebie_sku' => 'nullable|array',
            'freebie_sku.*' => 'nullable|string',
        ]);

        // Prepare bulk insert data
        $insertData = [];
        foreach ($skus as $index => $sku) {
            $insertData[] = [
                'sku'                   => strtoupper($sku),
                'description'           => $descriptions[$index] ?? null,
                'case_pack'             => $casePacks[$index] ?? 0,
                'srp'                   => $srps[$index] ?? null,
                'allocation_per_case'   => $allocationPerCases[$index] ?? null,
                'cash_bank_card_scheme' => $cashBankCardSchemes[$index] ?? null,
                'po15_scheme'           => $po15Schemes[$index] ?? null,
                'discount_scheme'       => $discountSchemes[$index] ?? null,
                'freebie_sku'           => $freebieSkus[$index] ?? null,
                'created_at'            => now(),
                'updated_at'            => now(),
            ];
        }

        DB::connection('mysql')->table($tableName)->insert($insertData);

        return redirect()->back()->with('success', 'Products added successfully.');
    }



    public function getSkus()
    {
        try {
            // 1. Get user's location
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = "products_" . $this->resolveTableStoreCode();

            // 2. Check if table exists
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                return response()->json([
                    'error' => "Table '{$tableName}' does not exist."
                ], 404);
            }

            // 3. Fetch SKUs from the location-specific table
            $skus = DB::table($tableName)
                ->pluck('sku')
                ->map(fn($sku) => strtoupper($sku));

            return response()->json($skus);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch SKUs: ' . $e->getMessage()
            ], 500);
        }
    }


    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        try {
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = "products_" . $this->resolveTableStoreCode();

            $file = $request->file('csv_file');
            $csvContent = file_get_contents($file->getRealPath());

            // Split CSV lines and remove empty rows
            $lines = array_filter(
                preg_split('/\r\n|\r|\n/', trim($csvContent)),
                fn($line) => count(array_filter(str_getcsv(trim($line)), fn($col) => trim($col) !== '')) > 0
            );

            if (count($lines) < 2) {
                return redirect()->back()->with('import_errors', ['CSV must have header + at least 1 data row.']);
            }

            $dataLines = array_slice($lines, 1);
            $errors = [];
            $allData = [];

            // Get existing SKUs and case_pack
            $existingProducts = DB::table($tableName)
                ->select('sku', 'case_pack')
                ->get()
                ->keyBy(fn($row) => strtoupper($row->sku));

            $seenCsvSkus = [];

            foreach ($dataLines as $lineNumber => $line) {
                $rowNumber = $lineNumber + 2;
                $columns = str_getcsv(trim($line));

                if (count($columns) < 9) {
                    $errors[] = "Row {$rowNumber}: Missing required columns.";
                    continue;
                }

                [$sku, $description, $allocationPerCase, $casePackRaw, $srpRaw, $cashBankCardScheme, $po15Scheme, $discountScheme, $freebieSkuRaw] =
                    array_map(fn($col) => preg_replace('#[^a-zA-Z0-9./+% | ()]#', '', trim($col)), $columns);

                $formattedSku = strtoupper($sku);

                // --- VALIDATIONS ---
                if (!$sku || !preg_match('/^\d+$/', $sku)) {
                    $errors[] = "Row {$rowNumber}: SKU must be numeric and not empty.";
                    continue;
                }

                if (in_array($formattedSku, $seenCsvSkus)) {
                    $errors[] = "Row {$rowNumber}: Duplicate SKU '{$sku}' found in CSV.";
                    continue;
                }

                $seenCsvSkus[] = $formattedSku;

                if (!$description) {
                    $errors[] = "Row {$rowNumber}: Product Description is required.";
                    continue;
                }

                if ($allocationPerCase === '' || !is_numeric($allocationPerCase) || $allocationPerCase <= 0) {
                    $errors[] = "Row {$rowNumber}: Store Allocation must be a number greater than 0.";
                    continue;
                }

                // Case Pack merge
                $casePackNumbers = [];
                if ($casePackRaw !== '') {
                    $casePackNumbers = array_filter(array_map('trim', explode('|', $casePackRaw)));
                    foreach ($casePackNumbers as $num) {
                        if (!is_numeric($num) || $num <= 0) {
                            $errors[] = "Row {$rowNumber}: Invalid Case Pack value '{$num}'.";
                            continue 2;
                        }
                    }
                }

                // Determine case pack value
                $casePack = '';
                if (isset($existingProducts[$formattedSku]) && $existingProducts[$formattedSku]->case_pack) {
                    $existingNumbers = array_map('trim', explode('|', $existingProducts[$formattedSku]->case_pack));
                    $allNumbers = array_unique(array_merge($existingNumbers, $casePackNumbers));
                    $casePack = implode(' | ', $allNumbers);
                } else {
                    $casePack = implode(' | ', $casePackNumbers);
                }

                // SRP validation
                $srp = preg_replace('/[^0-9.]/', '', $srpRaw);
                if ($srp === '' || !is_numeric($srp) || $srp <= 0) {
                    $errors[] = "Row {$rowNumber}: SRP must be numeric and greater than 0.";
                    continue;
                }

                // Scheme & freebie validation
                if ($cashBankCardScheme && !preg_match('/^\d+\+\d+$/', $cashBankCardScheme)) {
                    $errors[] = "Row {$rowNumber}: CBC Scheme must be in 'number+number' format.";
                    continue;
                }

                if ($po15Scheme && !preg_match('/^\d+\+\d+$/', $po15Scheme)) {
                    $errors[] = "Row {$rowNumber}: PO15 Scheme must be in 'number+number' format.";
                    continue;
                }

                if ($discountScheme && !preg_match('/^\d+%?$/', $discountScheme)) {
                    $errors[] = "Row {$rowNumber}: Discount must be numeric with optional '%'.";
                    continue;
                }

                $freebieSku = trim($freebieSkuRaw);
                if ($freebieSku && !preg_match('/^\d+([\/|\|\s]+\d+)*$/', $freebieSku)) {
                    $errors[] = "Row {$rowNumber}: Freebie SKU must be numeric or multiple separated by '/', '|', or spaces.";
                    continue;
                }

                // Get department CODE and NAME from Oracle
                $oracleData = DB::connection('oracle_rms')->selectOne("
                SELECT 
                    item_master.dept AS department_code,
                    deps.dept_name AS department_name
                FROM item_master
                LEFT JOIN deps ON deps.dept = item_master.dept
                WHERE item_master.item_parent = ?
                AND ROWNUM = 1
            ", [$sku]);

                // Build record - ALL records get created_at
                $record = [
                    'sku' => $formattedSku,
                    'description' => $description,
                    'department_code' => $oracleData->department_code ?? null,
                    'department' => $oracleData->department_name ?? null,
                    'case_pack' => $casePack !== '' ? $casePack : null,
                    'srp' => floatval($srp),
                    'allocation_per_case' => intval($allocationPerCase),
                    'initial_allocation_per_case' => intval($allocationPerCase),
                    'cash_bank_card_scheme' => $cashBankCardScheme,
                    'po15_scheme' => $po15Scheme,
                    'discount_scheme' => $discountScheme,
                    'freebie_sku' => $freebieSku,
                    'archived_at' => null,
                    'archived_by' => null,
                    'archive_reason' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add to data array
                $allData[] = $record;
            }

            // Upsert all valid data
            if (!empty($allData)) {
                DB::table($tableName)->upsert($allData, ['sku'], [
                    'description',
                    'department_code',
                    'department',
                    'case_pack',
                    'srp',
                    'allocation_per_case',
                    'initial_allocation_per_case',
                    'cash_bank_card_scheme',
                    'po15_scheme',
                    'discount_scheme',
                    'freebie_sku',
                    'archived_at',
                    'archived_by',
                    'archive_reason',
                    'updated_at'
                    // Note: created_at is intentionally NOT in this list
                    // This means it will only be set on INSERT, not UPDATE
                ]);
            }

            // Calculate insert and update counts for summary
            $insertCount = 0;
            $updateCount = 0;
            foreach ($allData as $record) {
                if (isset($existingProducts[$record['sku']])) {
                    $updateCount++;
                } else {
                    $insertCount++;
                }
            }

            $summary = "Import complete: {$insertCount} inserted, {$updateCount} updated.";
            return redirect()->back()->with('import_success', $summary)->with('import_errors', $errors);
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
            // 1. Determine location-specific products table
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = "products_" . $this->resolveTableStoreCode();

            // Optional: check if table exists before proceeding
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                return response()->json([
                    'valid' => false,
                    'errors' => ["Table '{$tableName}' does not exist."]
                ]);
            }

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

            // 2. Check for existing SKUs in the location-specific table
            if (!empty($skusToCheck)) {
                $existingSkus = DB::connection('mysql')
                    ->table($tableName)
                    ->whereIn('sku', $skusToCheck)
                    ->pluck('sku')
                    ->map(fn($sku) => strtoupper($sku))
                    ->toArray();

                foreach ($existingSkus as $existingSku) {
                    $errors[] = "SKU '{$existingSku}' already exists in {$tableName} database.";
                }
            }

            return response()->json([
                'valid' => empty($errors),
                'errors' => $errors,
                'valid_rows' => $validRows,
                'total_rows' => count($dataLines)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => ['Error processing file: ' . $e->getMessage()]
            ]);
        }
    }




    /**
     * Get warehouse code based on user location and role
     * Uses the same mapping logic as index() method
     */
    protected function getWarehouseCode(Request $request): ?string
    {
        $user          = auth()->user();
        $userRole      = strtolower($user->role ?? '');
        $userLocation  = strtolower($user->user_location ?? '');

        $allowedWarehouses = LocationConfig::warehouses();
        $isPersonnel       = str_contains($userRole, 'personnel');
        $isSuperAdmin      = $userRole === 'super admin';

        // Non-personnel can switch warehouses via request,
        // but only to warehouses they have access to
        if (!$isPersonnel) {
            $requestedWarehouse = $request->get('warehouse');

            if ($requestedWarehouse && array_key_exists($requestedWarehouse, $allowedWarehouses)) {
                // Regional users: ensure requested warehouse belongs to their region
                $regionStores = LocationConfig::regionStores($userLocation);
                if (!empty($regionStores) && !$isSuperAdmin) {
                    $regionWarehouses = collect($regionStores)
                        ->map(fn($code) => LocationConfig::warehouseForStore($code))
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();

                    if (in_array($requestedWarehouse, $regionWarehouses, true)) {
                        return $requestedWarehouse;
                    }
                } else {
                    return $requestedWarehouse;
                }
            }
        }

        // Fallback: derive from location
        // If user_location is a region code, use first store in region
        $regionStores = LocationConfig::regionStores($userLocation);
        if (!empty($regionStores)) {
            $firstStore = $regionStores[0] ?? null;
            return $firstStore ? LocationConfig::warehouseForStore($firstStore) : null;
        }

        // Single store
        return LocationConfig::warehouseForStore($userLocation);
    }



    /**
     * Get warehouse display name
     */
    protected function getWarehouseName(string $warehouseCode): string
    {
        return LocationConfig::warehouseName($warehouseCode);
    }

    /**
     * Get cache keys for WMS operations
     */
    protected function getWmsCacheKeys(string $warehouseCode): array
    {
        return [
            'running' => "wms_update_running_{$warehouseCode}",
            'processed' => "wms_processed_{$warehouseCode}",
            'failed' => "wms_failed_{$warehouseCode}",
        ];
    }

    /**
     * Update WMS allocations
     */
    public function wmsUpdate(Request $request)
    {
        $user = auth()->user();
        $userLocation = $user->user_location ?? null;

        if (!$userLocation) {
            return response()->json([
                'status' => 'error',
                'message' => 'User location is required.',
            ], 400);
        }

        $warehouseCode = $this->getWarehouseCode($request);
        if (!$warehouseCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'No warehouse selected or mapped.',
            ], 400);
        }

        $cacheKeys = $this->getWmsCacheKeys($warehouseCode);
        Log::info("Updating warehouse", [
            'warehouse_code' => $warehouseCode,
            'cache_keys' => $cacheKeys
        ]);

        // Prevent multiple simultaneous updates
        if (Cache::has($cacheKeys['running'])) {
            return response()->json([
                'status' => 'running',
                'message' => 'An allocation update is already in progress for '
                    . $this->getWarehouseName($warehouseCode) . '.',
            ], 409);
        }

        try {
            $this->validateDatabaseConnections();

            $facilityId = LocationConfig::facilityForWarehouse($warehouseCode);

            // If user_location is a region code, resolve to a store code for the table name
            $tableName = "products_" . $this->resolveTableStoreCode();
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                throw new \Exception("Table '{$tableName}' does not exist for location '{$userLocation}'.");
            }

            $totalSkus = DB::connection('mysql')
                ->table($tableName)
                ->whereNull('archived_at')
                ->distinct('sku')
                ->count('sku');

            if ($totalSkus === 0) {
                throw new \Exception("No active SKUs found in '{$tableName}'.");
            }

            // CRITICAL: Clear all cache keys before starting
            Cache::forget($cacheKeys['processed']);
            Cache::forget($cacheKeys['failed']);
            Cache::forget($cacheKeys['running']);

            // Initialize counters
            Cache::put($cacheKeys['processed'], 0, now()->addHours(3));
            Cache::put($cacheKeys['failed'], 0, now()->addHours(3));

            // Mark warehouse update as running
            Cache::put($cacheKeys['running'], [
                'started_at' => now()->toDateTimeString(),
                'total_skus' => $totalSkus,
                'warehouse_code' => $warehouseCode,
                'facility_id' => $facilityId,
                'user_location' => $userLocation,
            ], now()->addHours(3));

            // Ensure queue worker is running before dispatching
            // $this->ensureQueueWorkerRunning();

            // Dispatch jobs for this warehouse only
            $dispatched = $this->dispatchAllocationJobs($tableName, $facilityId, $warehouseCode);

            Log::info("Queued {$dispatched} allocation jobs", [
                'warehouse_code' => $warehouseCode,
                'facility_id' => $facilityId,
                'cache_keys' => $cacheKeys,
            ]);

            return response()->json([
                'status' => 'started',
                'message' => "Queued {$dispatched} allocation updates for "
                    . $this->getWarehouseName($warehouseCode) . ".",
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $this->getWarehouseName($warehouseCode),
                'facility_id' => $facilityId,
                'total_skus' => $dispatched,
            ]);
        } catch (\Exception $e) {
            Cache::forget($cacheKeys['running']);
            Log::error("Failed WMS update for warehouse {$warehouseCode}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start allocation update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve the store code to use for the products table.
     * If user_location is a region code, returns the first store in that region.
     * If it's already a store code, returns it as-is.
     */
    protected function resolveTableStoreCode(?string $requestedStore = null): string
    {
        $userLocation = strtolower(auth()->user()->user_location ?? '');
        $isSuperAdmin = strtolower(auth()->user()->role ?? '') === 'super admin';
        $regionStores = LocationConfig::regionStores($userLocation);

        if ($isSuperAdmin) {
            $accessibleStores = array_map('strtolower', array_keys(LocationConfig::stores()));
        } elseif (!empty($regionStores)) {
            $accessibleStores = array_map('strtolower', $regionStores);
        } else {
            // Single store — no switching
            return $userLocation;
        }

        // Honour requested store if it's within accessible stores
        if ($requestedStore && in_array(strtolower($requestedStore), $accessibleStores, true)) {
            $code = strtolower($requestedStore);
            if (Schema::connection('mysql')->hasTable("products_{$code}")) {
                return $code;
            }
        }

        // Fallback: first accessible store with an existing table
        foreach ($accessibleStores as $code) {
            if (Schema::connection('mysql')->hasTable("products_{$code}")) {
                return $code;
            }
        }

        return $accessibleStores[0] ?? $userLocation;
    }



    /**
     * Get WMS update status
     */
    public function wmsStatus(Request $request)
    {
        $user = auth()->user();
        $userLocation = $user && $user->user_location
            ? strtolower($user->user_location)
            : null;

        if (!$userLocation) {
            return response()->json([
                'status' => 'idle',
                'message' => 'User location is required.',
            ], 400);
        }

        $warehouseCode = $this->getWarehouseCode($request);

        if (!$warehouseCode) {
            return response()->json([
                'status' => 'idle',
                'message' => 'No warehouse selected or mapped.',
            ], 400);
        }

        $cacheKeys = $this->getWmsCacheKeys($warehouseCode);
        $cache = Cache::get($cacheKeys['running']);

        // Debug logging
        Log::debug('WMS Status Check', [
            'warehouse_code' => $warehouseCode,
            'cache_keys' => $cacheKeys,
            'has_running_cache' => !empty($cache),
        ]);

        if (!$cache) {
            return response()->json([
                'status' => 'idle',
                'message' => 'No update in progress for ' . $this->getWarehouseName($warehouseCode) . '.',
                'warehouse_code' => $warehouseCode,
                'warehouse_name' => $this->getWarehouseName($warehouseCode),
            ]);
        }

        try {
            $totalSkus = $cache['total_skus'];
            $startedAt = $cache['started_at'];
            $facilityId = $cache['facility_id'];

            // Get progress with explicit cache retrieval
            $processed = (int) Cache::get($cacheKeys['processed'], 0);
            $failed = (int) Cache::get($cacheKeys['failed'], 0);
            $percent = $totalSkus > 0 ? round(($processed / $totalSkus) * 100, 1) : 0;

            // DETAILED DEBUG LOGGING - Always log to see what's happening
            Log::info('WMS Progress Detail', [
                'warehouse_code' => $warehouseCode,
                'cache_keys' => $cacheKeys,
                'processed_raw' => Cache::get($cacheKeys['processed']),
                'failed_raw' => Cache::get($cacheKeys['failed']),
                'processed' => $processed,
                'failed' => $failed,
                'total' => $totalSkus,
                'percentage' => $percent,
                'running_cache' => $cache,
            ]);

            // Check queue status
            $pendingJobs = DB::table('jobs')->where('queue', 'default')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            Log::info('Queue Status', [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'queue_worker_running' => $this->isQueueWorkerRunning(),
            ]);

            // Ensure queue worker is still running
            if (!$this->isQueueWorkerRunning()) {
                Log::warning('⚠ Queue worker stopped during processing, restarting...');
                try {
                    $this->startQueueWorker();
                } catch (\Exception $e) {
                    Log::error('Failed to restart queue worker: ' . $e->getMessage());
                }
            }

            // Check if completed
            if ($processed >= $totalSkus && $totalSkus > 0) {
                // Keep "done" status alive for a few minutes
                Cache::forget($cacheKeys['running']);

                Log::info('WMS Update Completed', [
                    'warehouse_code' => $warehouseCode,
                    'processed' => $processed,
                    'failed' => $failed,
                    'total' => $totalSkus,
                ]);

                return response()->json([
                    'status' => 'done',
                    'message' => 'Allocation update completed for '
                        . $this->getWarehouseName($warehouseCode) . '.',
                    'summary' => [
                        'total_skus' => $totalSkus,
                        'processed_skus' => $processed,
                        'failed_skus' => $failed,
                        'warehouse_code' => $warehouseCode,
                        'warehouse_name' => $this->getWarehouseName($warehouseCode),
                        'facility_id' => $facilityId,
                        'started_at' => $startedAt,
                        'completed_at' => now()->toDateTimeString(),
                    ],
                ]);
            }

            // If no progress after some time, might be stuck
            $startTime = \Carbon\Carbon::parse($startedAt);
            $elapsedMinutes = now()->diffInMinutes($startTime);

            if ($processed === 0 && $elapsedMinutes > 5) {
                Log::warning('No progress after 5 minutes', [
                    'warehouse_code' => $warehouseCode,
                    'elapsed_minutes' => $elapsedMinutes,
                    'pending_jobs' => $pendingJobs,
                ]);
            }

            return response()->json([
                'status' => 'running',
                'message' => "Processing {$processed} / {$totalSkus} SKUs ({$percent}%) for "
                    . $this->getWarehouseName($warehouseCode),
                'progress' => [
                    'current_step' => 'Fetching allocations from Oracle WMS',
                    'processed' => $processed,
                    'failed' => $failed,
                    'total' => $totalSkus,
                    'percentage' => $percent,
                    'warehouse_code' => $warehouseCode,
                    'warehouse_name' => $this->getWarehouseName($warehouseCode),
                    'facility_id' => $facilityId,
                    'pending_jobs' => $pendingJobs ?? 0,
                    'elapsed_time' => $elapsedMinutes ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("✗ Status check failed for warehouse {$warehouseCode}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check update status: ' . $e->getMessage(),
                'warehouse_code' => $warehouseCode,
            ], 500);
        }
    }


    /**
     * Ensure queue worker is running
     */
    protected function ensureQueueWorkerRunning(): void
    {
        if ($this->isQueueWorkerRunning()) {
            Log::info('✓ Queue worker is already running');
            return;
        }

        Log::warning('⚠ Queue worker not running, attempting to start...');
        $this->startQueueWorker();
    }

    /**
     * Check if queue worker is running
     */
    protected function isQueueWorkerRunning(): bool
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            return $this->isWindowsQueueWorkerRunning();
        }

        return $this->isLinuxQueueWorkerRunning();
    }

    /**
     * Check if queue worker is running on Windows
     */
    protected function isWindowsQueueWorkerRunning(): bool
    {
        $output = shell_exec('wmic process where "name=\'php.exe\'" get CommandLine 2>nul');

        return $output && (
            stripos($output, 'queue:work') !== false ||
            stripos($output, 'queue:listen') !== false
        );
    }

    /**
     * Check if queue worker is running on Linux
     */
    protected function isLinuxQueueWorkerRunning(): bool
    {
        $output = shell_exec('ps aux | grep "queue:work\|queue:listen" | grep -v grep');
        return !empty($output);
    }

    /**
     * Start queue worker in background
     */
    protected function startQueueWorker(): void
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $this->startWindowsQueueWorker();
        } else {
            $this->startLinuxQueueWorker();
        }

        sleep(2);

        if ($this->isQueueWorkerRunning()) {
            Log::info('✓ Queue worker started successfully');
        } else {
            Log::error('✗ Failed to start queue worker automatically');
            throw new \Exception('Queue worker could not be started. Please start it manually: php artisan queue:work');
        }
    }

    /**
     * Start queue worker on Windows
     */
    protected function startWindowsQueueWorker(): void
    {
        $phpBinary = PHP_BINARY;
        $basePath = base_path();
        $artisanPath = str_replace('/', '\\', "{$basePath}\\artisan");

        $command = "powershell -Command \"Start-Process '{$phpBinary}' "
            . "-ArgumentList '{$artisanPath} queue:work --queue=default --tries=3 --timeout=300' "
            . "-WindowStyle Hidden\"";

        pclose(popen($command, 'r'));
    }

    /**
     * Start queue worker on Linux
     */
    protected function startLinuxQueueWorker(): void
    {
        $projectPath = "/var/www/html/ISO_API";
        $phpBinary = trim(shell_exec("which php")) ?: PHP_BINARY;
        $logPath = "{$projectPath}/storage/logs/queue-worker.log";

        // ensure log file is writable
        if (!file_exists($logPath)) {
            touch($logPath);
        }
        chmod($logPath, 0777);

        // run queue worker inside project directory
        $command = "cd {$projectPath} && nohup {$phpBinary} artisan queue:work "
            . "--queue=default --tries=3 --timeout=300 --sleep=1 "
            . ">> {$logPath} 2>&1 & echo $!";

        $pid = exec($command);

        if (!$pid) {
            throw new \Exception("Queue worker failed to start using: php artisan queue:work");
        }
    }


    /**
     * Validate database connections
     */
    protected function validateDatabaseConnections(): void
    {
        try {
            DB::connection('mysql')->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('MySQL connection failed: ' . $e->getMessage());
        }

        try {
            DB::connection('oracle_wms')->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('Oracle WMS connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch allocation jobs for all SKUs
     */
    protected function dispatchAllocationJobs(
        string $tableName,
        string $facilityId,
        string $warehouseCode
    ): int {
        $dispatched = 0;

        DB::connection('mysql')
            ->table($tableName)
            ->whereNull('archived_at')
            ->select('sku')
            ->distinct()
            ->orderBy('sku')
            ->chunk(100, function ($rows) use (
                &$dispatched,
                $facilityId,
                $warehouseCode,
                $tableName
            ) {
                foreach ($rows as $row) {
                    FetchAllocationJob::dispatch(
                        strtoupper($row->sku),
                        $facilityId,
                        $warehouseCode,
                        $tableName // ✅ PASS EXACT TABLE
                    )->onQueue('default');

                    $dispatched++;
                }
            });

        return $dispatched;
    }


    /**
     * Clear WMS cache
     */
    protected function clearWmsCache(array $cacheKeys): void
    {
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Log::info('WMS Cache cleared', ['keys' => array_values($cacheKeys)]);
    }
}
