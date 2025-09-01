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
use Illuminate\Support\Facades\Response;
use App\Jobs\FetchAllocationJob;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;




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
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = 'products_' . $userLocation;

            // Check if table exists in the connection before query
            if (!Schema::connection('mysql')->hasTable($tableName)) {
                throw new \Exception("The database table '{$tableName}' does not exist.");
            }

            $sort = $request->get('sort', 'description');
            $direction = $request->get('direction', 'asc');

            $allowedSorts = ['sku', 'description'];
            if (!in_array(strtolower($sort), $allowedSorts)) {
                $sort = 'sku';
            }
            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                $direction = 'asc';
            }

            $search = strtolower($request->get('query'));

            $productsQuery = DB::connection('mysql')
                ->table($tableName)
                ->select(
                    'id',
                    'sku',
                    'description',
                    'wms_allocation_per_case',
                    'allocation_per_case',
                    'case_pack',
                    'srp',
                    'cash_bank_card_scheme',
                    'po15_scheme',
                    'discount_scheme',
                    'freebie_sku'
                )
                ->whereNull('archived_at');

            if ($search) {
                $productsQuery->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"]);
                });

                if (preg_match('/^[a-zA-Z0-9\-]+$/', $search)) {
                    $productsQuery->orWhereRaw('LOWER(sku) = ?', [$search]);
                }
            }
            $perPage = $request->get('per_page', 10); // default 10 if not provided

            $products = $productsQuery->orderBy($sort, $direction)
                ->paginate($perPage)
                ->appends($request->query());

            $freebieSkus = collect($products->items())->pluck('freebie_sku')->filter()->unique()->toArray();

            $freebieDescriptions = DB::connection('mysql')
                ->table($tableName)
                ->whereIn('sku', $freebieSkus)
                ->pluck('description', 'sku');

            foreach ($products as $product) {
                $product->freebie_description = $freebieDescriptions[$product->freebie_sku] ?? null;
            }

            return view('products.index', compact('products'));

        } catch (\Exception $e) {
            return view('errors.db_error', ['error' => $e->getMessage()]);
        }
    }




    // // Show product search view
    // public function index(Request $request)
    // {
    //     try {
    //         $sort = $request->get('sort', 'description');
    //         $direction = $request->get('direction', 'asc');

    //         $allowedSorts = ['sku', 'description'];
    //         if (!in_array(strtolower($sort), $allowedSorts)) {
    //             $sort = 'sku';
    //         }
    //         if (!in_array(strtolower($direction), ['asc', 'desc'])) {
    //             $direction = 'asc';
    //         }

    //         $search = strtolower($request->get('query'));

    //         $productsQuery = DB::connection('mysql')
    //             ->table('products')
    //             ->select(
    //                 'id',
    //                 'sku',
    //                 'description',
    //                 'allocation_per_case',
    //                 'case_pack',
    //                 'srp',
    //                 'cash_bank_card_scheme',
    //                 'po15_scheme',
    //                 'freebie_sku'
    //             );

    //         // Search filter
    //         if ($search) {
    //             $productsQuery->where(function ($q) use ($search) {
    //                 $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
    //                 ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"]);
    //             });

    //             // Optional: exact match for SKU
    //             if (preg_match('/^[a-zA-Z0-9\-]+$/', $search)) {
    //                 $productsQuery->orWhereRaw('LOWER(sku) = ?', [$search]);
    //             }
    //         }

    //         $products = $productsQuery->orderBy($sort, $direction)
    //             ->paginate(10)
    //             ->appends($request->query());

    //         // Freebie descriptions
    //         $freebieSkus = collect($products->items())->pluck('freebie_sku')->filter()->unique()->toArray();

    //         $freebieDescriptions = DB::connection('mysql')
    //             ->table('products')
    //             ->whereIn('sku', $freebieSkus)
    //             ->pluck('description', 'sku');

    //         foreach ($products as $product) {
    //             $product->freebie_description = $freebieDescriptions[$product->freebie_sku] ?? null;
    //         }

    //         // Enrich products with Oracle RMS & WMS data
    //         foreach ($products as $product) {
    //             // // Oracle RMS data
    //             // $oracleData = DB::connection('oracle_rms')->selectOne("
    //             //     SELECT * FROM (
    //             //         SELECT
    //             //             item_master.item_parent AS sku,
    //             //             item_master.item_desc AS description,
    //             //             deps.dept_name AS department,
    //             //             uda_values.uda_value_desc AS brand,
    //             //             groups.group_name,
    //             //             COALESCE(stock.stock_on_hand, 0) AS stock_on_hand,
    //             //             class.class_name AS class_name
    //             //         FROM item_supplier
    //             //         LEFT JOIN item_master 
    //             //             ON item_supplier.item = item_master.item
    //             //         LEFT JOIN uda_item_lov 
    //             //             ON item_supplier.item = uda_item_lov.item 
    //             //             AND item_master.item = uda_item_lov.item
    //             //         LEFT JOIN uda_values 
    //             //             ON uda_item_lov.uda_value = uda_values.uda_value 
    //             //             AND uda_item_lov.uda_id = uda_values.uda_id
    //             //         LEFT JOIN deps 
    //             //             ON deps.dept = item_master.dept
    //             //         LEFT JOIN groups 
    //             //             ON groups.group_no = deps.group_no
    //             //         LEFT JOIN class 
    //             //             ON class.dept = item_master.dept 
    //             //             AND class.class = item_master.class
    //             //         LEFT JOIN (
    //             //             SELECT item, SUM(stock_on_hand) AS stock_on_hand
    //             //             FROM item_loc_soh
    //             //             GROUP BY item
    //             //         ) stock 
    //             //             ON stock.item = item_master.item
    //             //         WHERE uda_values.uda_id = 9
    //             //         AND item_master.item = ?
    //             //     ) WHERE ROWNUM = 1
    //             // ", [$product->sku]);

    //             // if ($oracleData) {
    //             //     $product->department = $oracleData->department ?? null;
    //             //     $product->brand = $oracleData->brand ?? null;
    //             //     $product->group_name = $oracleData->group_name ?? null;
    //             //     $product->stock_on_hand = $oracleData->stock_on_hand ?? 0;
    //             //     $product->class_name = $oracleData->class_name ?? null;
    //             // }

    //             // $allocation = DB::connection('oracle_wms')->selectOne("
    //             //     SELECT SUM(sub_outer.unit_qty) AS total_unit_qty
    //             //     FROM (
    //             //         SELECT sub_inner.unit_qty
    //             //         FROM (
    //             //             SELECT ci.unit_qty, c.container_id
    //             //             FROM rwms.container c
    //             //             JOIN rwms.container_item ci
    //             //                 ON c.facility_id = ci.facility_id
    //             //             AND c.container_id = ci.container_id
    //             //             WHERE c.container_status NOT IN ('S','D','A')
    //             //             AND ci.item_id = ?
    //             //         ) sub_inner
    //             //         WHERE ROWNUM <= 5
    //             //     ) sub_outer
    //             // ", [$product->sku]);

    //             // $product->allocation_per_case = $allocation->total_unit_qty ?? 0;


    //         }

    //         return view('products.index', compact('products'));

    //     } catch (\Exception $e) {
    //         return view('errors.db_error', ['error' => $e->getMessage()]);
    //     }
    // }


    // public function getAllocation(Request $request)
    // {
    //     $sku = $request->input('sku');
    //     if (!$sku) {
    //         return response()->json(['error' => 'SKU is required'], 400);
    //     }

    //     // Dispatch background job
    //     FetchAllocationJob::dispatch($sku);

    //     // Immediately respond (you can optionally return cached value if exists)
    //     $cached = Cache::get("allocation_{$sku}", null);

    //     return response()->json([
    //         'sku' => $sku,
    //         'allocation_per_case' => $cached,
    //     ]);
    // }


    // public function getAllocation(Request $request)
    // {
    //     $sku = $request->input('sku');
    //     if (!$sku) {
    //         return response()->json(['error' => 'SKU is required'], 400);
    //     }

    //     set_time_limit(300); // allow slow queries

    //     try {
    //         // 🔹 Allocation (sum of unit_qty across active containers)
    //         $allocation = DB::connection('oracle_wms')->selectOne("
    //             SELECT SUM(ci.unit_qty) AS total_unit_qty
    //             FROM (
    //                 SELECT facility_id, container_id
    //                 FROM rwms.container
    //                 WHERE container_status NOT IN ('S','D','A')
    //             ) c
    //             JOIN (
    //                 SELECT facility_id, container_id, unit_qty
    //                 FROM rwms.container_item
    //                 WHERE item_id = ?
    //             ) ci
    //             ON c.facility_id = ci.facility_id
    //             AND c.container_id = ci.container_id
    //         ", [$sku]);

    //         $totalQty = $allocation->total_unit_qty ?? 0;

    //         // 🔹 Case pack (all distinct unit_qty where container_qty = 1 and distro_nbr > 9)
    //         $casePackRows = DB::connection('oracle_wms')->select("
    //             SELECT DISTINCT unit_qty
    //             FROM rwms.container_item
    //             WHERE item_id = ?
    //             AND container_qty = 1
    //             AND LENGTH(distro_nbr) > 9
    //             ORDER BY unit_qty DESC
    //         ", [$sku]);

    //         // Convert to plain array of values
    //         $casePackArray = array_map(fn($row) => $row->unit_qty, $casePackRows);

    //         return response()->json([
    //             'sku' => $sku,
    //             'allocation_per_case' => $totalQty,
    //             'case_pack' => $casePackArray, // array of all distinct unit_qty
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'sku' => $sku,
    //             'error' => 'Failed to fetch allocation',
    //             'details' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    // // Show product search view
    // public function index(Request $request)
    // {
    //     try {
    //         $sort = $request->get('sort', 'sku');
    //         $direction = $request->get('direction', 'asc');

    //         $allowedSorts = ['sku', 'description'];
    //         if (!in_array(strtolower($sort), $allowedSorts)) {
    //             $sort = 'sku';
    //         }
    //         if (!in_array(strtolower($direction), ['asc', 'desc'])) {
    //             $direction = 'asc';
    //         }

    //         $search = strtolower($request->get('query'));

    //         $productsQuery = DB::connection('mysql')
    //             ->table('products')
    //             ->select(
    //                 'id',
    //                 'sku',
    //                 'description',
    //                 'case_pack',
    //                 'srp',
    //                 'cash_bank_card_scheme',
    //                 'po15_scheme',
    //                 'freebie_sku'
    //             );

    //         // Search filter
    //         if ($search) {
    //             $productsQuery->where(function ($q) use ($search) {
    //                 $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
    //                 ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"]);
    //             });

    //             // Optional: exact match for SKU
    //             if (preg_match('/^[a-zA-Z0-9\-]+$/', $search)) {
    //                 $productsQuery->orWhereRaw('LOWER(sku) = ?', [$search]);
    //             }
    //         }

    //         $products = $productsQuery->orderBy($sort, $direction)
    //             ->paginate(10)
    //             ->appends($request->query());

    //         // Freebie descriptions
    //         $freebieSkus = collect($products->items())->pluck('freebie_sku')->filter()->unique()->toArray();

    //         $freebieDescriptions = DB::connection('mysql')
    //             ->table('products')
    //             ->whereIn('sku', $freebieSkus)
    //             ->pluck('description', 'sku');

    //         foreach ($products as $product) {
    //             $product->freebie_description = $freebieDescriptions[$product->freebie_sku] ?? null;
    //         }

    //         // Enrich products with Oracle RMS & WMS data
    //         foreach ($products as $product) {
    //             // // Oracle RMS data
    //             // $oracleData = DB::connection('oracle_rms')->selectOne("
    //             //     SELECT * FROM (
    //             //         SELECT
    //             //             item_master.item_parent AS sku,
    //             //             item_master.item_desc AS description,
    //             //             deps.dept_name AS department,
    //             //             uda_values.uda_value_desc AS brand,
    //             //             groups.group_name,
    //             //             COALESCE(stock.stock_on_hand, 0) AS stock_on_hand,
    //             //             class.class_name AS class_name
    //             //         FROM item_supplier
    //             //         LEFT JOIN item_master 
    //             //             ON item_supplier.item = item_master.item
    //             //         LEFT JOIN uda_item_lov 
    //             //             ON item_supplier.item = uda_item_lov.item 
    //             //             AND item_master.item = uda_item_lov.item
    //             //         LEFT JOIN uda_values 
    //             //             ON uda_item_lov.uda_value = uda_values.uda_value 
    //             //             AND uda_item_lov.uda_id = uda_values.uda_id
    //             //         LEFT JOIN deps 
    //             //             ON deps.dept = item_master.dept
    //             //         LEFT JOIN groups 
    //             //             ON groups.group_no = deps.group_no
    //             //         LEFT JOIN class 
    //             //             ON class.dept = item_master.dept 
    //             //             AND class.class = item_master.class
    //             //         LEFT JOIN (
    //             //             SELECT item, SUM(stock_on_hand) AS stock_on_hand
    //             //             FROM item_loc_soh
    //             //             GROUP BY item
    //             //         ) stock 
    //             //             ON stock.item = item_master.item
    //             //         WHERE uda_values.uda_id = 9
    //             //         AND item_master.item = ?
    //             //     ) WHERE ROWNUM = 1
    //             // ", [$product->sku]);

    //             // if ($oracleData) {
    //             //     $product->department = $oracleData->department ?? null;
    //             //     $product->brand = $oracleData->brand ?? null;
    //             //     $product->group_name = $oracleData->group_name ?? null;
    //             //     $product->stock_on_hand = $oracleData->stock_on_hand ?? 0;
    //             //     $product->class_name = $oracleData->class_name ?? null;
    //             // }

    //             $allocation = DB::connection('oracle_wms')->selectOne("
    //                 SELECT SUM(sub_outer.unit_qty) AS total_unit_qty
    //                 FROM (
    //                     SELECT sub_inner.unit_qty
    //                     FROM (
    //                         SELECT ci.unit_qty, c.container_id
    //                         FROM rwms.container c
    //                         JOIN rwms.container_item ci
    //                             ON c.facility_id = ci.facility_id
    //                         AND c.container_id = ci.container_id
    //                         WHERE c.container_status NOT IN ('S','D','A')
    //                         AND ci.item_id = ?
    //                     ) sub_inner
    //                     WHERE ROWNUM <= 5
    //                 ) sub_outer
    //             ", [$product->sku]);

    //             $product->allocation_per_case = $allocation->total_unit_qty ?? 0;


    //         }

    //         return view('products.index', compact('products'));

    //     } catch (\Exception $e) {
    //         return view('errors.db_error', ['error' => $e->getMessage()]);
    //     }
    // }




    public function search(Request $request)
    {
        $query = strtolower($request->query('query'));

        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $userLocation;

        $results = DB::connection('mysql')
            ->table($tableName)
            ->select('sku', 'description')
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(description) LIKE ?', ["%{$query}%"])
                ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$query}%"]);
            })
            ->whereNull('archived_at')
            ->get();

        return response()->json($results);
    }




    public function export(Request $request)
    {
        $productsQuery = DB::connection('mysql')
            ->table('products')
            ->select(
                'sku',
                'description',
                'allocation_per_case',
                'case_pack',
                'srp',
                'cash_bank_card_scheme',
                'po15_scheme',
                'freebie_sku'
            );

        // Apply filter if SKU is provided
        if ($request->filled('sku')) {
            $skus = explode(',', $request->sku);
            $productsQuery->whereIn('sku', $skus);
        }

        $products = $productsQuery->get();

        // Set dynamic filename
        $filename = 'products_export_' . date('Ymd_His') . '.csv';

        // Set headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        // Stream CSV response
        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');
            // Write CSV header
            fputcsv($handle, [
                'SKU', 'Description', 'Case Pack', 'SRP',
                'Allocation per Case', 'Cash/Bank/Card Scheme',
                'PO15 Scheme', 'Freebie SKU'
            ]);

            // Write each product
            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->sku,
                    $product->description,
                    $product->allocation_per_case,
                    $product->case_pack,
                    $product->srp,
                    $product->cash_bank_card_scheme,
                    $product->po15_scheme,
                    $product->freebie_sku
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }




    public function bulkUpdate(BulkUpdateProductsRequest $request)
    {
        try {
            $productIds = $request->input('product_ids');
            $userLocation = strtolower(auth()->user()->user_location);
            $tableName = 'products_' . $userLocation;

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
        $tableName = 'products_' . $userLocation;

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
        $tableName = 'products_' . $userLocation;

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
            $userLocation = strtolower(auth()->user()->user_location); // e.g. 'f2'
            $tableName = "products_{$userLocation}";

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
            $tableName = "products_{$userLocation}";

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
            $insertData = [];
            $updateData = [];

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

                // SKU Validation
                if (!$sku) {
                    $errors[] = "Row {$rowNumber}: SKU is required.";
                    continue;
                }
                if (!preg_match('/^\d+$/', $sku)) {
                    $errors[] = "Row {$rowNumber}: SKU must be numeric.";
                    continue;
                }
                if (in_array($formattedSku, $seenCsvSkus)) {
                    $errors[] = "Row {$rowNumber}: Duplicate SKU '{$sku}' found in CSV.";
                    continue;
                }
                $seenCsvSkus[] = $formattedSku;

                // Description Validation
                if (!$description) {
                    $errors[] = "Row {$rowNumber}: Product Description is required.";
                    continue;
                }

                // Allocation Validation
                if ($allocationPerCase === '') {
                    $errors[] = "Row {$rowNumber}: Store Allocation is required.";
                    continue;
                }
                if (!is_numeric($allocationPerCase)) {
                    $errors[] = "Row {$rowNumber}: Store Allocation must be numeric.";
                    continue;
                }
                if ($allocationPerCase <= 0) {
                    $errors[] = "Row {$rowNumber}: Store Allocation must be greater than 0.";
                    continue;
                }

                // Case Pack Validation
                $casePackNumbers = [];
                if ($casePackRaw !== '') {
                    $casePackNumbers = array_filter(array_map('trim', explode('|', $casePackRaw)));
                    foreach ($casePackNumbers as $num) {
                        if (!is_numeric($num)) {
                            $errors[] = "Row {$rowNumber}: Case Pack value '{$num}' must be numeric.";
                            continue 2;
                        }
                        if ($num <= 0) {
                            $errors[] = "Row {$rowNumber}: Case Pack value '{$num}' must be greater than 0.";
                            continue 2;
                        }
                    }
                }

                // Merge Case Pack with DB
                if (isset($existingProducts[$formattedSku]) && $existingProducts[$formattedSku]->case_pack) {
                    $existingNumbers = array_map('trim', explode('|', $existingProducts[$formattedSku]->case_pack));
                    $allNumbers = array_unique(array_merge($existingNumbers, $casePackNumbers));
                    $casePack = implode(' | ', $allNumbers);
                } else {
                    $casePack = implode(' | ', $casePackNumbers);
                }

                // SRP Validation
                $srp = preg_replace('/[^0-9.]/', '', $srpRaw);
                if ($srpRaw === '') {
                    $errors[] = "Row {$rowNumber}: SRP is required.";
                    continue;
                }
                if ($srp === '' || !is_numeric($srp)) {
                    $errors[] = "Row {$rowNumber}: SRP must be numeric.";
                    continue;
                }
                if ($srp <= 0) {
                    $errors[] = "Row {$rowNumber}: SRP must be greater than 0.";
                    continue;
                }

                // CBC Scheme Validation
                if ($cashBankCardScheme && !preg_match('/^\d+\+\d+$/', $cashBankCardScheme)) {
                    $errors[] = "Row {$rowNumber}: CBC Scheme must be in 'number+number' format.";
                    continue;
                }

                // PO15 Scheme Validation
                if ($po15Scheme && !preg_match('/^\d+\+\d+$/', $po15Scheme)) {
                    $errors[] = "Row {$rowNumber}: PO15 Scheme must be in 'number+number' format.";
                    continue;
                }

                // Discount Validation
                if ($discountScheme && !preg_match('/^\d+%?$/', $discountScheme)) {
                    $errors[] = "Row {$rowNumber}: Discount must be numeric with optional '%'.";
                    continue;
                }

                // Freebie SKU Validation
                $freebieSku = trim($freebieSkuRaw);
                if ($freebieSku && !preg_match('/^\d+([\/|\|\s]+\d+)*$/', $freebieSku)) {
                    $errors[] = "Row {$rowNumber}: Freebie SKU must be numeric or multiple separated by '/', '|', or spaces.";
                    continue;
                }

                // Build Record
                $record = [
                    'sku' => $formattedSku,
                    'description' => $description,
                    'allocation_per_case' => intval($allocationPerCase),
                    'case_pack' => $casePack !== '' ? $casePack : null,
                    'srp' => floatval($srp),
                    'cash_bank_card_scheme' => $cashBankCardScheme,
                    'po15_scheme' => $po15Scheme,
                    'discount_scheme' => $discountScheme,
                    'freebie_sku' => $freebieSku,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if (isset($existingProducts[$formattedSku])) {
                    $updateData[] = $record;
                } else {
                    $insertData[] = $record;
                }
            }

            // Upsert all valid data
            $allData = array_merge($insertData, $updateData);
            if (!empty($allData)) {
                DB::table($tableName)->upsert($allData, ['sku'], [
                    'description',
                    'allocation_per_case',
                    'case_pack',
                    'srp',
                    'cash_bank_card_scheme',
                    'po15_scheme',
                    'discount_scheme',
                    'freebie_sku',
                    'updated_at'
                ]);
            }

            $summary = "Import complete: " . count($insertData) . " inserted, " . count($updateData) . " updated.";
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
            $userLocation = strtolower(auth()->user()->user_location); // e.g. 'f2'
            $tableName = "products_{$userLocation}";

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

    public function wmsUpdate(Request $request)
    {
        set_time_limit(1800);

        $user = auth()->user();
        $location = $user && $user->user_location ? strtolower($user->user_location) : null;

        // Pass location as option
        Artisan::call('products:update-allocations', [
            '--location' => $location
        ]);

        $output = Artisan::output();

        return response()->json([
            'status'  => 'success',
            'message' => 'Allocations updated successfully.',
            'output'  => $output,
        ]);
    }


}