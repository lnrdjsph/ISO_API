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




    public function sof_submit(Request $request)
    {
        $validated = $request->validate([
            'channel_order' => 'required|string',
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

        foreach ($request->input('orders', []) as $index => $order) {
            $validated['orders'][$index] = validator($order, [
                'sku' => 'required|string',
                'item_description' => 'required|string',
                'price_per_pc' => 'required|numeric',
                'price' => 'required|numeric',
                'qty_per_cs' => 'nullable|integer',
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
                'discount' => 'nullable|string'
                
            ])->validate();
        }
                // Save main order info

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
        ]);

        // Save each item
        foreach ($validated['orders'] as $item) {
            // Determine scheme based on sale_type
            $scheme = ($item['sale_type'] ?? '') === 'Discount' ? 'Discount' : ($item['scheme'] ?? null);

            // Save main item
            $order->items()->create([
                'sku' => $item['sku'] ?? null,
                'item_description' => $item['item_description'] ?? null,
                'scheme' => $scheme,
                'price_per_pc' => $item['price_per_pc'] ?? 0,
                'price' => $item['price'] ?? 0,
                'qty_per_pc' => $item['qty_per_pc'] ?? 0,
                'qty_per_cs' => $item['qty_per_cs'] ?? 0,
                'freebies_per_cs' => 0,
                'total_qty' => $item['qty_per_cs'] ?? 0,
                'amount' => $item['amount'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'remarks' => $item['remarks'] ?? null,
                'store_order_no' => $item['store_order_no'] ?? null,
                'item_type' => 'MAIN',
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



        return redirect()->route('forms.sof_submit')->with('success', 'Order created successfully.');
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
                'freebie_sku'
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
}
