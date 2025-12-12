<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// use Illuminate\Support\Facades\Validator;

class FormsController extends Controller
{



    public function sof()
    {
        $orders = Order::with('items')->get();

        $today = now()->format('Ymd');

        // Find latest sof_id for today
        $latest = Order::where('sof_id', 'like', "SOF{$today}-%")
            ->orderBy('sof_id', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->sof_id, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }

        $nextSofId = "SOF{$today}-{$nextNumber}";

        return view('forms.sof', compact('orders', 'nextSofId'));
    }



protected array $warehouseMap = [
    '80141' => 'Silangan Warehouse',
    '80001' => 'Central Warehouse',
    '80041' => 'Procter Warehouse',
    '80051' => 'Opao-ISO Warehouse',
    '80071' => 'Big Blue Warehouse',
    '80131' => 'Lower Tingub Warehouse',
    '80211' => 'Sta. Rosa Warehouse',
    '80181' => 'Bacolod Depot',
    '80191' => 'Tacloban Depot',
];



public function sof_submit(Request $request){
    $validated = $request->validate([
        'channel_order' => 'required|string',
        'warehouse' => 'required|string',  //added 23-10-2025
        'time_order' => 'required',
        'payment_center' => 'required|string',
        'mode_payment' => 'required|string',
        'payment_date' => 'required|date',
        'mode_dispatching' => 'required|string',
        'delivery_date' => 'required|date',
        'address' => 'nullable|string',
        'landmark' => 'nullable|string',

        // New: Customer + Requesting Info
        'requesting_store' => 'required|string',
        'requested_by' => 'required|string',
        'mbc_card_no' => 'required|digits:16',
        'customer_name' => 'required|string',
        'contact_number' => 'required|string|regex:/^[0-9]{11,12}$/',
        'email' => 'required|email',
        

        // Order items
        'orders' => 'required|array',
    ]);

    // === NEW: Check for duplicate SKUs ===
    $skus = [];
    foreach ($request->input('orders', []) as $index => $order) {
        if (!empty($order['sku'])) {
            $sku = trim($order['sku']);
            if (in_array($sku, $skus)) {
                return back()->withErrors([
                    'duplicate_sku' => "Duplicate SKU detected: '{$sku}' appears more than once in the order. Each SKU must be unique."
                ])->withInput();
            }
            $skus[] = $sku;
        }
    }
    // === END: SKU uniqueness check ===

    foreach ($request->input('orders', []) as $index => $order) {
        validator(
            $order,
            [
                'sku' => 'required|string',
                'item_description' => 'required|string',
                'price_per_pc' => 'required|numeric',
                'price' => 'required|numeric',
                'qty_per_cs' => 'required|integer',
                'qty_per_pc' => 'required|integer',
                'scheme' => 'nullable|string',
                'total_qty' => 'required|integer',
                'freebies_per_cs' => 'nullable|integer',
                'amount' => 'required|numeric',
                'remarks' => 'required|string',
                'store_order_no' => 'nullable|numeric',

                'freebie_sku' => 'nullable|string',
                'freebie_description' => 'nullable|string',
                'freebie_price_per_pc' => 'nullable|numeric',
                'freebie_price' => 'nullable|numeric',
                'freebie_qty_per_pc' => 'nullable|integer',

                'sale_type' => 'nullable|string',
                'discount' => 'nullable|string',
            ],
            [
                'sku.required' => "SKU is required for item no. " . ($index + 1),
                'item_description.required' => "Item description is required for item no. " . ($index + 1),
                'price_per_pc.required' => "Price/PC is required for item no. " . ($index + 1),
                'price_per_pc.numeric' => "Price/PC must be a number for item no. " . ($index + 1),
                'price.required' => "Price is required for item no. " . ($index + 1),
                'price.numeric' => "Price must be a number for item no. " . ($index + 1),
                'qty_per_cs.required' => "QTY/CS is required for item no. " . ($index + 1),
                'qty_per_cs.integer' => "QTY/CS must be a whole number for item no. " . ($index + 1),
                'qty_per_pc.required' => "QTY/PC is required for item no. " . ($index + 1),
                'qty_per_pc.integer' => "QTY/PC must be a whole number for item no. " . ($index + 1),
                'total_qty.required' => "Total quantity is required for item no. " . ($index + 1),
                'total_qty.integer' => "Total quantity must be a whole number for item no. " . ($index + 1),
                'amount.required' => "Amount is required for item no. " . ($index + 1),
                'amount.numeric' => "Amount must be a number for item no. " . ($index + 1),
                'remarks.required' => "Remarks are required for item no. " . ($index + 1),
            ]
        )->validate();
    }

    try{
        DB::beginTransaction();
        // Save main order info
        $this->checkAllocationStock($validated['orders'], $validated['warehouse']);
        // === Generate SOF ID ===
        $today = now()->format('Ymd');
        $latest = Order::where('sof_id', 'like', "SOF{$today}-%")
            ->orderBy('sof_id', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->sof_id, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }

        $nextSofId = "SOF{$today}-{$nextNumber}";

        $order = Order::create([
            'sof_id' => $nextSofId, // <-- add this
            'channel_order' => $validated['channel_order'],
            'warehouse' => $validated['warehouse'], //added 23-10-2025
            'time_order' => $validated['time_order'],
            'payment_center' => $validated['payment_center'],
            'mode_payment' => $validated['mode_payment'],
            'payment_date' => $validated['payment_date'],
            'mode_dispatching' => $validated['mode_dispatching'],
            'delivery_date' => $validated['delivery_date'],
            'address' => $validated['address'] ?? null,
            'landmark' => $validated['landmark'] ?? null,

            // Save customer + request info
            'requesting_store' => $validated['requesting_store'],
            'requested_by' => $validated['requested_by'],
            'mbc_card_no' => $validated['mbc_card_no'],
            'customer_name' => $validated['customer_name'],
            'contact_number' => $validated['contact_number'],
            'email' => $validated['email'],
            'order_status' => 'new order',
        ]);

        // Save each item
        foreach ($validated['orders'] as $item) {
            // Determine scheme based on sale_type
            $scheme = ($item['sale_type'] ?? '') === 'Discount' ? 'Discount' : ($item['scheme'] ?? null);

            // Save main item
        $pricePerPc = $item['price_per_pc'] ?? 0;
        $qtyPerPc   = $item['qty_per_pc'] ?? 0;
        $discount   = $item['discount'] ?? 0;
        $discountType = $item['discount_type'] ?? 'amount'; // 'percent' or 'amount'

        // Base price per case
        $pricePerPc = $item['price_per_pc'] ?? 0;
        $qtyPerPc   = $item['qty_per_pc'] ?? 0;
        $discountRaw = $item['discount'] ?? 0;

        $rawPrice = $pricePerPc * $qtyPerPc;

        // Parse discount
        $discount = 0;
        $discountType = 'amount';

        if (is_string($discountRaw)) {
            $discountStr = trim($discountRaw);
            if (str_ends_with($discountStr, '%')) {
                $discountType = 'percent';
                $discount = (float) rtrim($discountStr, '%');
            } else {
                $discount = (float) $discountStr;
            }
        } else {
            $discount = (float) $discountRaw;
        }

        // Apply discount
        $finalPrice = $rawPrice;
        if ($discount > 0) {
            if ($discountType === 'percent') {
                $finalPrice = $rawPrice - ($rawPrice * ($discount / 100));
            } else {
                $finalPrice = $rawPrice - $discount;
            }
        }

        // Prevent negative
        $finalPrice = max($finalPrice, 0);

        // Total amount
        $qtyPerCs = $item['qty_per_cs'] ?? 0;
        $totalAmount = $finalPrice * $qtyPerCs;

        // Create item
        $order->items()->create([
            'sku' => $item['sku'] ?? null,
            'item_description' => $item['item_description'] ?? null,
            'scheme' => $scheme,
            'price_per_pc' => $pricePerPc,
            'price' => $finalPrice, // discounted case price
            'qty_per_pc' => $qtyPerPc,
            'qty_per_cs' => $qtyPerCs,
            'freebies_per_cs' => 0,
            'total_qty' => $qtyPerCs,
            'discount' => $discountRaw, // keep original input for reference
            'amount' => $totalAmount, // discounted total
            'remarks' => $item['remarks'] ?? null,
            'store_order_no' => $item['store_order_no'] ?? null,
            'item_type' => $discount > 0 ? 'DISCOUNT' : 'MAIN',
        ]);




            if (!empty($item['freebies_per_cs']) && ($item['sale_type'] ?? '') == 'Freebie') {
                $order->items()->create([
                    'sku' => $item['freebie_sku'] ?? $item['sku'] ?? null,
                    'item_description' => $item['freebie_description'] ?? $item['item_description'] ?? null,
                    'scheme' => 'Freebie',
                    'price_per_pc' => $item['freebie_price_per_pc'] ?? $item['price_per_pc'] ?? 0,
                    'price' => $item['freebie_price'] ?? $item['price'] ?? 0,
                    'qty_per_pc' => $item['freebie_qty_per_pc'] ?? $item['qty_per_pc'] ?? 0,
                    'qty_per_cs' => 0,
                    'freebies_per_cs' => $item['freebies_per_cs'] ?? 0,
                    'total_qty' => $item['freebies_per_cs'] ?? 0,
                    'amount' => (
                                !empty($item['freebie_price_per_pc']) &&
                                !empty($item['freebie_qty_per_pc']) &&
                                !empty($item['freebies_per_cs'])
                            ) ? (
                                $item['freebie_price_per_pc'] * 
                                $item['freebie_qty_per_pc'] * 
                                $item['freebies_per_cs']
                            ) : (
                                $item['price_per_pc'] * 
                                $item['qty_per_pc'] * 
                                $item['freebies_per_cs']
                            ),
                    'remarks' => $item['remarks'] ?? null,
                    'store_order_no' => $item['store_order_no'] ?? null,
                    'item_type' => 'FREEBIE',
                ]);
            }



        }

        $this->deductAllocationStock($order->id, $validated['warehouse']); // Pass warehouse

        DB::commit();

        return redirect()->route('forms.sof_submit')->with('success', 'Order created successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['stock_error' => $e->getMessage()])
         ->withInput();
    }
}

    
public function search(Request $request)
{
    // normalize query
    $query = strtolower($request->query('query'));

    // treat "/" as space
    $query = str_replace(['/', '|', ','], ' ', $query);

    // split into keywords
    $keywords = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

    $userLocation = strtolower(auth()->user()->user_location);
    $tableName = 'products_' . $userLocation;

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
                $q->orWhere(function ($subQ) use ($word) {
                    $subQ->whereRaw('LOWER(description) LIKE ?', ["%{$word}%"])
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


    public function rof()
    {
        // Logic for rendering ROF form
        return view('forms.rof');
    }


// public function checkAllocationStock(array $orders)
// {
//     $userLocation = auth()->user()->user_location;
//     $tableName = 'products_' . strtolower($userLocation);
//     $warehouseCode = $this->getWarehouseCodeByLocation($userLocation);

//     // Group orders by SKU
//     $skuTotals = [];
//     foreach ($orders as $item) {
//         $sku = strtoupper($item['sku']);
//         $skuTotals[$sku] = ($skuTotals[$sku] ?? 0) + $item['total_qty'];
//     }

//     // Validate stock
//     foreach ($skuTotals as $sku => $requiredQty) {
//         $product = DB::connection('mysql')
//             ->table($tableName)
//             ->where('sku', $sku)
//             ->first();

//         if (!$product) {
//             throw new \Exception("Product with SKU {$sku} not found in {$tableName}.");
//         }

//         // Check allocation_per_case
//         if (($product->allocation_per_case ?? 0) < $requiredQty) {
//             throw new \Exception("Insufficient stock for SKU {$sku} in allocation_per_case. Required: {$requiredQty}, Available: {$product->allocation_per_case}");
//         }

//         // Check wms_virtual_allocation from product_wms_allocations table
//         $requiredWmsQty = $requiredQty * ($product->qty_per_pc ?? 1);
        
//         $wmsAllocation = DB::connection('mysql')
//             ->table('product_wms_allocations')
//             ->where('sku', $sku)
//             ->where('warehouse_code', $warehouseCode)
//             ->first();

//         $availableWmsQty = $wmsAllocation->wms_virtual_allocation ?? 0;
        
//         if ($availableWmsQty < $requiredWmsQty) {
//             throw new \Exception("Insufficient stock for SKU {$sku} in warehouse {$warehouseCode}. Required: {$requiredWmsQty}, Available: {$availableWmsQty}");
//         }
//     }

//     return true; // all good
// }

// public function deductAllocationStock($orderId)
// {
//     $userLocation = auth()->user()->user_location;
//     $tableName = 'products_' . strtolower($userLocation);
//     $warehouseCode = $this->getWarehouseCodeByLocation($userLocation);

//     // Load order and items
//     $order = Order::with('items')->findOrFail($orderId);

//     // Normalize items: handle cases where $order might be a Collection of orders or a single model
//     if ($order instanceof \Illuminate\Database\Eloquent\Collection || $order instanceof \Illuminate\Support\Collection) {
//         // $order is a collection of orders; collect all items across them
//         $items = $order->pluck('items')->flatten();
//     } else {
//         // $order is a single model; get relation collection safely
//         $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
//     }

//     foreach ($items as $item) {
//         // Skip freebie items - they don't deduct from stock
//         if (($item->item_type ?? '') === 'FREEBIE') {
//             Log::info("Skipping freebie item - SKU: {$item->sku}, Item Type: {$item->item_type}");
//             continue;
//         }

//         // Find product in location table
//         $product = DB::connection('mysql')
//             ->table($tableName)
//             ->where('sku', strtoupper($item->sku))
//             ->first();

//         if ($product) {
//             /** --------------------------------------------
//              * 1) Deduct allocation_per_case by total_qty (number of cases)
//              * -------------------------------------------- */
//             $casesDeduction = $item->total_qty; // Number of cases to deduct
//             $currentCaseAllocation = $product->allocation_per_case ?? 0;
//             $newCaseAllocation = max(0, $currentCaseAllocation - $casesDeduction);

//             // Update allocation_per_case
//             DB::connection('mysql')
//                 ->table($tableName)
//                 ->where('id', $product->id)
//                 ->update([
//                     'allocation_per_case' => $newCaseAllocation,
//                     'updated_at' => now(),
//                 ]);

//             Log::info("Case Deduction - SKU: {$item->sku}, Cases Deducted: {$casesDeduction}, Previous: {$currentCaseAllocation}, New Balance: {$newCaseAllocation}");

//             /** ----------------------------------------------------------
//              * 2) Deduct wms_virtual_allocation by (total_qty × qty_per_pc)
//              *    This converts cases to pieces for WMS tracking
//              * ---------------------------------------------------------- */
            
//             // Get qty_per_pc from item, fallback to product
//             $qtyPerPc = $item->qty_per_pc ?? $product->qty_per_pc ?? 0;
            
//             // If qty_per_pc is still 0, log warning and skip WMS deduction
//             if ($qtyPerPc == 0) {
//                 Log::warning("qty_per_pc is 0 for SKU: {$item->sku}, Item ID: {$item->id}. Skipping WMS deduction.");
//                 continue;
//             }

//             // Calculate pieces deduction: cases × pieces per case
//             $piecesDeduction = $item->total_qty * $qtyPerPc;

//             // Get current wms allocation
//             $wmsAllocation = DB::connection('mysql')
//                 ->table('product_wms_allocations')
//                 ->where('sku', strtoupper($item->sku))
//                 ->where('warehouse_code', $warehouseCode)
//                 ->first();

//             if (!$wmsAllocation) {
//                 Log::warning("WMS allocation record not found for SKU: {$item->sku}, Warehouse: {$warehouseCode}");
//                 continue;
//             }

//             $currentWmsPieces = $wmsAllocation->wms_virtual_allocation ?? 0;
//             $newWmsPieces = max(0, $currentWmsPieces - $piecesDeduction);

//             // Update wms_virtual_allocation
//             $updated = DB::connection('mysql')
//                 ->table('product_wms_allocations')
//                 ->where('sku', strtoupper($item->sku))
//                 ->where('warehouse_code', $warehouseCode)
//                 ->update([
//                     'wms_virtual_allocation' => $newWmsPieces,
//                     'updated_at' => now(),
//                 ]);

//             Log::info("WMS Pieces Deduction - SKU: {$item->sku}, Warehouse: {$warehouseCode}, Cases: {$item->total_qty}, Pieces/Case: {$qtyPerPc}, Total Pieces Deducted: {$piecesDeduction}, Previous: {$currentWmsPieces}, New Balance: {$newWmsPieces}, Rows Updated: {$updated}");
//         } else {
//             Log::warning("Product not found in {$tableName} for SKU: {$item->sku}");
//         }
//     }

//     return true;
// }

// Freebies included
public function checkAllocationStock(array $orders, $warehouseName)
{
    $userLocation = auth()->user()->user_location;
    $tableName = 'products_' . strtolower($userLocation);
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
        $warehouseName = strtoupper($this->warehouseMap[$warehouseCode] ?? 'UNKNOWN WAREHOUSE');

        
        if ($availableWmsQty < $requiredWmsQty) {
            throw new \Exception(
                "Insufficient stock for SKU {$sku} in warehouse {$warehouseCode} - {$warehouseName}. ".
                "Required: {$requiredWmsQty} pieces ({$requiredQty} cases × {$qtyPerPc} pcs), ".
                "Available: {$availableWmsQty} pieces"
            );
        }
    }

    return true; // all good
}

public function deductAllocationStock($orderId, $warehouseName)
{
    $userLocation = auth()->user()->user_location;
    $tableName = 'products_' . strtolower($userLocation);
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
            /** --------------------------------------------
             * 1) Deduct allocation_per_case by total_qty
             *    Works for both MAIN items and FREEBIE items since
             *    freebies are stored as separate OrderItem records
             * -------------------------------------------- */
            $casesDeduction = $item->total_qty;
            $currentCaseAllocation = $product->allocation_per_case ?? 0;
            $newCaseAllocation = max(0, $currentCaseAllocation - $casesDeduction);

            // Update allocation_per_case
            DB::connection('mysql')
                ->table($tableName)
                ->where('id', $product->id)
                ->update([
                    'allocation_per_case' => $newCaseAllocation,
                    'updated_at' => now(),
                ]);

            $itemType = $item->item_type ?? 'MAIN';
            Log::info("Case Deduction - SKU: {$item->sku}, Type: {$itemType}, Cases Deducted: {$casesDeduction}, Previous: {$currentCaseAllocation}, New Balance: {$newCaseAllocation}");

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

/**
 * Map user location to warehouse code
 * 
 * @param string $location
 * @return string
 */
// private function getWarehouseCodeByLocation(string $location): string
// {
//     $locationToWarehouse = [
//         '4002' => '80181',
//         '6012' => '80211',
//         '2010' => '80001',
//         '2017' => '80041',
//         '3018' => '80051',
//         '3019' => '80071',
//         '2008' => '80131',
//         '6009' => '80141',
//         '6010' => '80191',
//     ];

//     $warehouseCode = $locationToWarehouse[$location] ?? null;

//     if (!$warehouseCode) {
//         throw new \Exception("Warehouse code not found for location: {$location}");
//     }

//     return $warehouseCode;
// }



    public function getCardInfo(Request $request)
    {
        $request->validate([
            'card_no' => 'required|string',
        ]);

        try {
            $cardNo = $request->input('card_no');

            // Test card for development
            if ($cardNo === '9999999999999999') {
                return response()->json([
                    "message" => "success",
                    "status" => "200",
                    "data" => [
                        'name_on_card' => 'test biboy',
                        'mobile_1' => '09999999999',
                        'email_1' => 'test.biboy@test.com'
                    ]
                ]);
            }
            if ($cardNo === '1111111111111111') {
                return response()->json([
                    "message" => "success",
                    "status" => "200",
                    "data" => [
                        'name_on_card' => 'test gene',
                        'mobile_1' => '09111111111',
                        'email_1' => 'test.gene@test.com'
                    ]
                ]);
            }

            $user = DB::connection('oracle_mbc')->table('VDC_P_CRD.CRD_DM_CRD AS CRD')
                ->leftJoin('VDC_P_CRD.CMN_DM_CNTC_DET AS CNTC', 'CRD.CUST_SERIAL_NO', '=', 'CNTC.CNCT_REF')
                ->select('CRD.NAME_ON_CARD', 'CNTC.CNCT_LINE_TYP', 'CNTC.CNCT_VAL')
                ->where('CRD.CARD_NO', $cardNo)
                ->where('CRD.CARD_TYP', 'LLTY')
                ->whereIn('CRD.PRODUCT_TYP', ['INST_CUST_CARD', 'INST_LOY'])
                ->where('CRD.STATUS_CODE', '1')
                ->get()
                ->groupBy(function($item) {
                    return strtoupper($item->CARD_NO ?? '');
                })
                ->map(function ($items) {
                    $first = $items->first();
                    $mobile = null;
                    $email = null;

                    foreach ($items as $item) {
                        $itemArr = array_change_key_case((array) $item, CASE_LOWER);

                        if (($itemArr['cnct_line_typ'] ?? null) === 'MOBILE_1') {
                            $mobile = $itemArr['cnct_val'] ?? null;
                        }

                        if (($itemArr['cnct_line_typ'] ?? null) === 'EMAIL_1') {
                            $email = $itemArr['cnct_val'] ?? null;
                        }

                        // Stop early if both found
                        if ($mobile && $email) {
                            break;
                        }
                    }

                    $firstArr = array_change_key_case((array) $first, CASE_LOWER);

                    return [
                        'name_on_card' => $firstArr['name_on_card'] ?? null,
                        'mobile_1' => $mobile,
                        'email_1' => $email,
                    ];
                })
                ->first();


            if (!$user) {
                return response()->json([
                    "message" => "We couldn't find a record for the MBC card number you entered.",
                    "status" => "404",
                ], 404);
            }

            return response()->json([
                "message" => "success",
                "status" => "200",
                "data" => $user
            ]);

        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                "message" => "Database error: " . $ex->getMessage(),
                "status" => "500",
            ], 500);
        }
    }


}
