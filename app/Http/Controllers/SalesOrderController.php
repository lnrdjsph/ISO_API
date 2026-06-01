<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\LocationConfig;
use Illuminate\Support\Facades\Auth;

class SalesOrderController extends Controller
{
    /**
     * Display the Sales Order Form.
     */
    public function create()
    {
        $userLocation = auth()->user()->user_location ?? null;
        $isSuperAdmin = strtolower(Auth::user()?->role ?? '') === 'super admin';

        $locationMap = LocationConfig::stores(); // ['4002' => 'Metro Wholesalemart Colon', ...]
        $regionStores = LocationConfig::regionStores($userLocation ?? '');
        $hasRegion = !empty($regionStores);

        // Determine dropdown stores based on role & region
        $dropdownStores = $isSuperAdmin
            ? $locationMap
            : array_intersect_key($locationMap, array_flip($regionStores));

        // Resolve user warehouse
        $userWarehouse = $userLocation
            ? LocationConfig::warehouseForStore($userLocation)
            : null;

        $warehouseMap = LocationConfig::warehouses();

        // Determine warehouse options based on region
        if ($hasRegion) {
            $warehouseMapForRegion = LocationConfig::warehousesForRegion($userLocation);
        } else {
            $warehouseMapForRegion = $isSuperAdmin
                ? $warehouseMap
                : array_filter($warehouseMap, fn($code) => $code === LocationConfig::warehouseForStore($userLocation), ARRAY_FILTER_USE_KEY);
        }

        // Generate next SOF ID
        $currentMonth = now()->format('Ym');
        $latest = Order::where('sof_id', 'like', "SOF{$currentMonth}-%")
            ->orderBy('sof_id', 'desc')
            ->first();

        $nextNumber = $latest
            ? str_pad((int) substr($latest->sof_id, -3) + 1, 3, '0', STR_PAD_LEFT)
            : '001';

        $nextSofId = "SOF{$currentMonth}-{$nextNumber}";

        // Current date for payment_date default
        $currentDate = now()->format('Y-m-d');
        $currentDateTime = now()->format('Y-m-d\TH:i');

        return view('forms.sales-order.index', compact(
            'nextSofId',
            'locationMap',
            'warehouseMap',
            'warehouseMapForRegion',
            'userWarehouse',
            'userLocation',
            'isSuperAdmin',
            'hasRegion',
            'regionStores',
            'dropdownStores',
            'currentDate',
            'currentDateTime'
        ));
    }

    /**
     * Handle Sales Order submission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'channel_order' => 'required|string',
            'warehouse' => 'required|string',
            'time_order' => 'required',
            'payment_center' => 'required|string',
            'mode_payment' => 'required|string',
            'payment_date' => 'required|date',
            'mode_dispatching' => 'required|string',
            'delivery_date' => 'required|date',
            'address' => 'nullable|string',
            'landmark' => 'nullable|string',
            'requesting_store' => 'required|string',
            'requested_by' => 'required|string',
            'mbc_card_no' => 'required|digits:16',
            'customer_name' => 'required|string',
            'contact_number' => 'required|string|regex:/^[0-9]{11,12}$/',
            'email' => 'required|email',
            'comment' => 'nullable|string|max:1800',
            'orders' => 'required|array',
        ]);

        // Duplicate SKU check
        $skus = [];
        foreach ($request->input('orders', []) as $index => $order) {
            if (!empty($order['sku'])) {
                $sku = trim($order['sku']);
                if (in_array($sku, $skus)) {
                    return back()->withErrors([
                        'duplicate_sku' => "Duplicate SKU detected: '{$sku}' appears more than once."
                    ])->withInput();
                }
                $skus[] = $sku;
            }
        }

        // Validate each order item
        foreach ($request->input('orders', []) as $index => $order) {
            $this->validateOrderItem($order, $index);
        }

        try {
            DB::beginTransaction();

            // Check stock availability
            $this->checkAllocationStock($validated['orders'], $validated['warehouse'], $validated['requesting_store']);

            // Generate final SOF ID
            $nextSofId = $this->generateSofId();

            // Create main order
            $order = Order::create([
                'sof_id' => $nextSofId,
                'channel_order' => $validated['channel_order'],
                'warehouse' => $validated['warehouse'],
                'time_order' => $validated['time_order'],
                'payment_center' => $validated['payment_center'],
                'mode_payment' => $validated['mode_payment'],
                'payment_date' => $validated['payment_date'],
                'mode_dispatching' => $validated['mode_dispatching'],
                'delivery_date' => $validated['delivery_date'],
                'address' => $validated['address'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
                'requesting_store' => $validated['requesting_store'],
                'requested_by' => $validated['requested_by'],
                'mbc_card_no' => $validated['mbc_card_no'],
                'customer_name' => $validated['customer_name'],
                'contact_number' => $validated['contact_number'],
                'email' => $validated['email'],
                'order_status' => 'new order',
                'comment' => $validated['comment'] ?? null,
            ]);

            // Save order items
            foreach ($validated['orders'] as $item) {
                $this->saveOrderItem($order, $item);
            }

            // Deduct stock
            $this->deductAllocationStock($order->id, $validated['warehouse'], $validated['requesting_store']);

            DB::commit();

            return redirect()->route('forms.sales-order.create')->with('success', 'Order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['stock_error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Search products (AJAX).
     */
    public function search(Request $request)
    {
        $query = strtolower($request->query('query'));
        $query = str_replace(['/', '|', ','], ' ', $query);
        $keywords = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        $storeCode = $request->query('store_code') ?? $request->input('requesting_store');
        if (!$storeCode) {
            $storeCode = strtolower(auth()->user()->user_location);
        }
        $storeCode = $this->resolveStoreCodeForTable($storeCode);

        $tableName = 'products_' . strtolower($storeCode);

        $results = DB::connection('mysql')
            ->table($tableName)
            ->select(
                'sku',
                'description',
                'department',
                'department_code',
                'srp',
                'case_pack',
                'allocation_per_case',
                'cash_bank_card_scheme',
                'po15_scheme',
                'freebie_sku',
                'discount_scheme'
            )
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere(function ($sub) use ($word) {
                        $sub->whereRaw('LOWER(description) LIKE ?', ["%{$word}%"])
                            ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$word}%"])
                            ->orWhereRaw('LOWER(department) LIKE ?', ["%{$word}%"])
                            ->orWhereRaw('LOWER(department_code) LIKE ?', ["%{$word}%"]);
                    });
                }
            })
            ->whereNull('archived_at')
            ->get();

        return response()->json($results);
    }

    /**
     * Get customer info by MBC card number (AJAX).
     */
    public function getCardInfo(Request $request)
    {
        $request->validate(['card_no' => 'required|string']);
        $cardNo = $request->input('card_no');

        // Test cards
        if ($cardNo === '9999999999999999') {
            return response()->json([
                "status" => "200",
                "data" => ['name_on_card' => 'test biboy', 'mobile_1' => '09999999999', 'email_1' => 'test.biboy@test.com']
            ]);
        }
        if ($cardNo === '1111111111111111') {
            return response()->json([
                "status" => "200",
                "data" => ['name_on_card' => 'test gene', 'mobile_1' => '09111111111', 'email_1' => 'test.gene@test.com']
            ]);
        }

        try {
            $user = DB::connection('oracle_mbc')
                ->table('VDC_P_CRD.CRD_DM_CRD AS CRD')
                ->leftJoin('VDC_P_CRD.CMN_DM_CNTC_DET AS CNTC', 'CRD.CUST_SERIAL_NO', '=', 'CNTC.CNCT_REF')
                ->leftJoin('VDC_P_CRD.CRD_DM_CUST AS CUST', 'CRD.CUST_SERIAL_NO', '=', 'CUST.CUST_SERIAL_NO')
                ->select(
                    DB::raw("CUST.FIRST_NAME || ' ' || CUST.LAST_NAME AS NAME_ON_CARD"),
                    'CRD.CARD_NO',
                    'CNTC.CNCT_LINE_TYP',
                    'CNTC.CNCT_VAL'
                )
                ->where('CRD.CARD_NO', $cardNo)
                ->where('CRD.CARD_TYP', 'LLTY')
                ->whereIn('CRD.PRODUCT_TYP', ['INST_CUST_CARD', 'INST_LOY'])
                ->where('CRD.STATUS_CODE', '1')
                ->get()
                ->groupBy('CARD_NO')
                ->map(function ($items) {
                    $first = $items->first();
                    $mobile = $email = null;
                    foreach ($items as $item) {
                        if ($item->CNCT_LINE_TYP === 'MOBILE_1') $mobile = $item->CNCT_VAL;
                        if ($item->CNCT_LINE_TYP === 'EMAIL_1') $email = $item->CNCT_VAL;
                        if ($mobile && $email) break;
                    }
                    return [
                        'name_on_card' => $first->NAME_ON_CARD,
                        'mobile_1' => $mobile,
                        'email_1' => $email,
                    ];
                })->first();

            if (!$user) {
                return response()->json(['status' => '404', 'message' => 'Card not found'], 404);
            }

            return response()->json(['status' => '200', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['status' => '500', 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- Private Helper Methods ----------
    private function generateSofId()
    {
        $currentMonth = now()->format('Ym');
        $latest = Order::where('sof_id', 'like', "SOF{$currentMonth}-%")
            ->orderBy('sof_id', 'desc')
            ->first();

        $nextNumber = $latest ? str_pad((int) substr($latest->sof_id, -3) + 1, 3, '0', STR_PAD_LEFT) : '001';
        return "SOF{$currentMonth}-{$nextNumber}";
    }

    private function validateOrderItem($item, $index)
    {
        validator($item, [
            'sku' => 'required|string',
            'item_description' => 'required|string',
            'price_per_pc' => 'required|numeric',
            'price' => 'required|numeric',
            'qty_per_cs' => 'required|integer',
            'qty_per_pc' => 'required|integer',
            'total_qty' => 'required|integer',
            'amount' => 'required|numeric',
            'remarks' => 'required|string',
        ], [
            'sku.required' => "SKU is required for item no. " . ($index + 1),
            'item_description.required' => "Item description required for item no. " . ($index + 1),
            // ... other messages
        ])->validate();
    }

    private function saveOrderItem(Order $order, array $item)
    {
        $scheme = ($item['sale_type'] ?? '') === 'Discount' ? 'Discount' : ($item['scheme'] ?? null);
        $pricePerPc = $item['price_per_pc'] ?? 0;
        $qtyPerPc = $item['qty_per_pc'] ?? 0;
        $discountRaw = $item['discount'] ?? 0;
        $discountType = str_ends_with((string)$discountRaw, '%') ? 'percent' : 'amount';
        $discountValue = $discountType === 'percent' ? (float) rtrim($discountRaw, '%') : (float) $discountRaw;

        $rawPrice = $pricePerPc * $qtyPerPc;
        $finalPrice = $rawPrice;
        if ($discountValue > 0) {
            $finalPrice = $discountType === 'percent' ? $rawPrice - ($rawPrice * $discountValue / 100) : max(0, $rawPrice - $discountValue);
        }

        $qtyPerCs = $item['qty_per_cs'] ?? 0;
        $totalAmount = $finalPrice * $qtyPerCs;

        $order->items()->create([
            'sku' => $item['sku'] ?? null,
            'item_description' => $item['item_description'] ?? null,
            'scheme' => $scheme,
            'price_per_pc' => $pricePerPc,
            'price' => $finalPrice,
            'qty_per_pc' => $qtyPerPc,
            'qty_per_cs' => $qtyPerCs,
            'freebies_per_cs' => 0,
            'total_qty' => $qtyPerCs,
            'discount' => $discountRaw,
            'amount' => $totalAmount,
            'remarks' => $item['remarks'] ?? null,
            'store_order_no' => $item['store_order_no'] ?? null,
            'item_type' => $discountValue > 0 ? 'DISCOUNT' : 'MAIN',
        ]);

        // Freebie item if applicable
        if (!empty($item['freebies_per_cs']) && ($item['sale_type'] ?? '') === 'Freebie') {
            $freebiePriceTotal = ($item['freebie_price_per_pc'] ?? 0) * ($item['freebie_qty_per_pc'] ?? 0) * ($item['freebies_per_cs'] ?? 0);
            $order->items()->create([
                'sku' => $item['freebie_sku'] ?? $item['sku'],
                'item_description' => $item['freebie_description'] ?? $item['item_description'],
                'scheme' => 'Freebie',
                'price_per_pc' => $item['freebie_price_per_pc'] ?? 0,
                'price' => $item['freebie_price'] ?? 0,
                'qty_per_pc' => $item['freebie_qty_per_pc'] ?? 0,
                'qty_per_cs' => 0,
                'freebies_per_cs' => $item['freebies_per_cs'],
                'total_qty' => $item['freebies_per_cs'],
                'amount' => $freebiePriceTotal,
                'remarks' => $item['remarks'] ?? null,
                'store_order_no' => $item['store_order_no'] ?? null,
                'item_type' => 'FREEBIE',
            ]);
        }
    }

    public function checkAllocationStock(array $orders, $warehouseName, ?string $requestingStore = null)
    {
        $location  = $requestingStore ?? auth()->user()->user_location;
        $storeCode = $this->resolveStoreCodeForTable($location);
        $tableName = 'products_' . $storeCode;
        $warehouseCode = $warehouseName;

        // Group orders by SKU and track qty_per_pc for each
        $skuTotals = [];
        $skuQtyPerPc = []; // Track qty_per_pc for WMS calculation

        foreach ($orders as $item) {
            // Handle both array and object (OrderItem model)
            $sku = is_array($item) ? strtoupper($item['sku']) : strtoupper($item->sku);
            $totalQty = is_array($item) ? ($item['total_qty'] ?? 0) : ($item->total_qty ?? 0);

            // For RAW INPUT: Check if total_qty already includes freebies
            if (is_array($item) && !empty($item['freebies_per_cs']) && ($item['sale_type'] ?? '') == 'Freebie') {
                // Get the main quantity (qty_per_cs) instead of total_qty
                // because total_qty might already include freebies in the form
                $mainQty = $item['qty_per_cs'] ?? 0;
                $freebieQty = $item['freebies_per_cs'] ?? 0;

                // Store qty_per_pc for main item
                if (isset($item['qty_per_pc'])) {
                    $skuQtyPerPc[$sku] = $item['qty_per_pc'];
                }

                // Add main quantity
                $skuTotals[$sku] = ($skuTotals[$sku] ?? 0) + $mainQty;

                // Add freebie quantity (might be same or different SKU)
                $freebieSku = strtoupper($item['freebie_sku'] ?? $item['sku']);
                $skuTotals[$freebieSku] = ($skuTotals[$freebieSku] ?? 0) + $freebieQty;

                // Store qty_per_pc for freebie item
                if (isset($item['freebie_qty_per_pc'])) {
                    $skuQtyPerPc[$freebieSku] = $item['freebie_qty_per_pc'];
                } elseif (isset($item['qty_per_pc'])) {
                    // Fallback to main qty_per_pc if freebie_qty_per_pc not provided
                    $skuQtyPerPc[$freebieSku] = $item['qty_per_pc'];
                }
            } else {
                // For OrderItem models or non-freebie items, use total_qty directly
                $skuTotals[$sku] = ($skuTotals[$sku] ?? 0) + $totalQty;

                // Store qty_per_pc if available
                if (is_array($item) && isset($item['qty_per_pc'])) {
                    $skuQtyPerPc[$sku] = $item['qty_per_pc'];
                } elseif (!is_array($item) && isset($item->qty_per_pc)) {
                    $skuQtyPerPc[$sku] = $item->qty_per_pc;
                }
            }
        }

        // Validate stock
        foreach ($skuTotals as $sku => $requiredQty) {
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', $sku)
                ->first();

            if (!$product) {
                throw new \Exception("Product with SKU {$sku} not found in {$tableName}.");
            }

            // Check allocation_per_case
            if (($product->allocation_per_case ?? 0) < $requiredQty) {
                throw new \Exception("Insufficient stock for SKU {$sku} in allocation_per_case. Required: {$requiredQty}, Available: {$product->allocation_per_case}");
            }

            // Check wms_virtual_allocation from product_wms_allocations table
            // Use qty_per_pc from input if available, otherwise from product table
            $qtyPerPc = $skuQtyPerPc[$sku] ?? $product->qty_per_pc ?? 0;

            if ($qtyPerPc == 0) {
                throw new \Exception("Product with SKU {$sku} has qty_per_pc = 0. Cannot calculate WMS requirement.");
            }

            // Convert cases to pieces: requiredQty (cases) × qty_per_pc (pieces per case)
            $requiredWmsQty = $requiredQty * $qtyPerPc;

            $wmsAllocation = DB::connection('mysql')
                ->table('product_wms_allocations')
                ->where('sku', $sku)
                ->where('warehouse_code', $warehouseCode)
                ->first();

            if (!$wmsAllocation) {
                throw new \Exception("No WMS allocation found for SKU {$sku} in warehouse {$warehouseCode}.");
            }

            $availableWmsQty = $wmsAllocation->wms_virtual_allocation ?? 0;
            $warehouseLabel = strtoupper(LocationConfig::warehouseName($warehouseCode, 'UNKNOWN WAREHOUSE'));


            if ($availableWmsQty < $requiredWmsQty) {
                throw new \Exception(
                    "Insufficient stock for SKU {$sku} in warehouse {$warehouseCode} - {$warehouseName}. " .
                        "Required: {$requiredWmsQty} pieces ({$requiredQty} cases × {$qtyPerPc} pcs), " .
                        "Available: {$availableWmsQty} pieces"
                );
            }
        }

        return true; // all good
    }

    public function deductAllocationStock($orderId, $warehouseName, ?string $requestingStore = null)
    {
        $location  = $requestingStore ?? auth()->user()->user_location;
        $storeCode = $this->resolveStoreCodeForTable($location);
        $tableName = 'products_' . $storeCode;
        $warehouseCode = $warehouseName;

        // Load order and items
        $order = Order::with('items')->findOrFail($orderId);

        // Normalize items: handle cases where $order might be a Collection of orders or a single model
        if ($order instanceof \Illuminate\Database\Eloquent\Collection || $order instanceof \Illuminate\Support\Collection) {
            // $order is a collection of orders; collect all items across them
            $items = $order->pluck('items')->flatten();
        } else {
            // $order is a single model; get relation collection safely
            $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        }

        foreach ($items as $item) {
            // Find product in location table
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', strtoupper($item->sku))
                ->first();

            if ($product) {
                $itemType = strtoupper(trim($item->item_type ?? 'MAIN'));

                /**
                 * --------------------------------------------
                 * 1) Deduct allocation_per_case ONLY for MAIN
                 * --------------------------------------------
                 */
                if ($itemType !== 'FREEBIE') {

                    $casesDeduction = $item->total_qty;
                    $currentCaseAllocation = $product->allocation_per_case ?? 0;
                    $newCaseAllocation = max(0, $currentCaseAllocation - $casesDeduction);

                    DB::connection('mysql')
                        ->table($tableName)
                        ->where('id', $product->id)
                        ->update([
                            'allocation_per_case' => $newCaseAllocation,
                            'updated_at' => now(),
                        ]);

                    Log::info("Case Deduction (MAIN ONLY) - SKU: {$item->sku}, Type: {$itemType}, Cases Deducted: {$casesDeduction}");
                } else {

                    Log::info("Skipped allocation_per_case deduction (FREEBIE) - SKU: {$item->sku}");
                }


                /** ----------------------------------------------------------
                 * 2) Deduct wms_virtual_allocation by (total_qty × qty_per_pc)
                 *    This converts cases/freebies to pieces for WMS tracking
                 * ---------------------------------------------------------- */

                // Get qty_per_pc from item, fallback to product
                $qtyPerPc = $item->qty_per_pc ?? $product->qty_per_pc ?? 0;

                // If qty_per_pc is still 0, log warning and skip WMS deduction
                if ($qtyPerPc == 0) {
                    Log::warning("qty_per_pc is 0 for SKU: {$item->sku}, Item ID: {$item->id}, Type: {$itemType}. Item qty_per_pc: " . ($item->qty_per_pc ?? 'null') . ", Product qty_per_pc: " . ($product->qty_per_pc ?? 'null') . ". Skipping WMS deduction.");
                    continue;
                }

                // Calculate pieces deduction: total_qty × pieces per case
                $piecesDeduction = $item->total_qty * $qtyPerPc;

                // Get current wms allocation
                $wmsAllocation = DB::connection('mysql')
                    ->table('product_wms_allocations')
                    ->where('sku', strtoupper($item->sku))
                    ->where('warehouse_code', $warehouseCode)
                    ->first();

                if (!$wmsAllocation) {
                    Log::warning("WMS allocation record not found for SKU: {$item->sku}, Warehouse: {$warehouseCode}, Type: {$itemType}");
                    continue;
                }

                $currentWmsPieces = $wmsAllocation->wms_virtual_allocation ?? 0;
                $newWmsPieces = max(0, $currentWmsPieces - $piecesDeduction);

                // Update wms_virtual_allocation
                $updated = DB::connection('mysql')
                    ->table('product_wms_allocations')
                    ->where('sku', strtoupper($item->sku))
                    ->where('warehouse_code', $warehouseCode)
                    ->update([
                        'wms_virtual_allocation' => $newWmsPieces,
                        'updated_at' => now(),
                    ]);

                Log::info("WMS Pieces Deduction - SKU: {$item->sku}, Type: {$itemType}, Warehouse: {$warehouseCode}, Qty: {$item->total_qty}, Pieces/Unit: {$qtyPerPc}, Total Pieces Deducted: {$piecesDeduction}, Previous: {$currentWmsPieces}, New Balance: {$newWmsPieces}, Rows Updated: {$updated}");
            } else {
                Log::warning("Product not found in {$tableName} for SKU: {$item->sku}");
            }
        }

        return true;
    }

    private function resolveStoreCodeForTable(string $storeOrRegion): string
    {
        $location = strtolower($storeOrRegion);
        $regionStores = LocationConfig::regionStores($location);
        return !empty($regionStores) ? $regionStores[0] : $location;
    }
}
