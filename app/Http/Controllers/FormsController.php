<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function checkAllocationStock(array $orders)
    {
        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $userLocation;

        // Group orders by SKU
        $skuTotals = [];
        foreach ($orders as $item) {
            $sku = strtoupper($item['sku']);
            $skuTotals[$sku] = ($skuTotals[$sku] ?? 0) + $item['total_qty'];
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

            if (($product->allocation_per_case ?? 0) < $requiredQty) {
                throw new \Exception("Insufficient stock for SKU {$sku}. Required: {$requiredQty}, Available: {$product->allocation_per_case}");
            }
        }

        return true; // all good
    }



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
        $this->checkAllocationStock($validated['orders']);
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

        $this->deductAllocationStock($order->id);

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
        $query = strtolower($request->query('query'));
        $keywords = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $userLocation;

        $results = DB::connection('mysql')
            ->table($tableName)
            ->select(
                'sku',
                'description',
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
                            ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$word}%"]);
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


    public function deductAllocationStock($orderId)
    {
        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $userLocation;

        // Get order with items
        $order = Order::with('items')->findOrFail($orderId);

        foreach ($order->items as $item) {
            // Find product by SKU in location-specific table
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', strtoupper($item->sku))
                ->first();

            if ($product) {
                // Deduct grand total qty directly (no case conversion)
                $newAllocation = max(0, ($product->allocation_per_case ?? 0) - $item->total_qty);

                DB::connection('mysql')
                    ->table($tableName)
                    ->where('id', $product->id)
                    ->update([
                        'allocation_per_case' => $newAllocation,
                        'updated_at' => now(),
                    ]);
            }
        }

        return true;
    }


public function getCardInfo(Request $request)
{
    $request->validate([
        'card_no' => 'required|string',
    ]);

    try {
        $cardNo = $request->input('card_no');

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

                foreach ($items as $item) {
                    $itemArr = array_change_key_case((array) $item, CASE_LOWER);
                    if (($itemArr['cnct_line_typ'] ?? null) === 'MOBILE_1') {
                        $mobile = $itemArr['cnct_val'] ?? null;
                        break;
                    }
                }

                $firstArr = array_change_key_case((array) $first, CASE_LOWER);

                return [
                    'name_on_card' => $firstArr['name_on_card'] ?? null,
                    'mobile_1' => $mobile,
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
