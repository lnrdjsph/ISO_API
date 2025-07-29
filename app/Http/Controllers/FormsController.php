<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;

class FormsController extends Controller
{



    public function sof()
    {
        $orders = Order::with('items')->get();
        return view('forms.sof', compact('orders'));
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
            'contact_number' => 'required|string|regex:/^[0-9]{11}$/',

            // Order items
            'orders' => 'required|array',
        ]);

        foreach ($request->input('orders', []) as $index => $order) {
            $validated['orders'][$index] = validator($order, [
                'sku' => 'required|string',
                'item_description' => 'required|string',
                'price_per_pc' => 'required|numeric',
                'price' => 'required|numeric',
                'qty_per_cs' => 'required|integer',
                'qty_per_pc' => 'required|integer',
                'scheme' => 'required|string',
                'total_qty' => 'required|integer',
                'freebies_per_cs' => 'required|integer',
                'amount' => 'required|numeric',
                'remarks' => 'required|string',
                'store_order_no' => 'required|string',
            ])->validate();
        }
                // Save main order info

        // Clean up mode_payment to ensure it's a string or array
        $modePaymentDecoded = collect(json_decode($validated['mode_payment'], true))
            ->pluck('value')
            ->filter()
            ->values()
            ->toArray();

        // Overwrite cleaned value back into $validated
        $validated['mode_payment'] = implode(', ', $modePaymentDecoded); // or store as array if DB field is JSON

        $order = Order::create([
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
            // Save main item
            $order->items()->create([
                'sku' => $item['sku'] ?? null,
                'item_description' => $item['item_description'] ?? null,
                'scheme' => $item['scheme'] ?? null,
                'price_per_pc' => $item['price_per_pc'] ?? 0,
                'price' => $item['price'] ?? 0,
                'qty_per_pc' => $item['qty_per_pc'] ?? 0,
                'qty_per_cs' => $item['qty_per_cs'] ?? 0,
                'freebies_per_cs' => 0,
                'total_qty' => $item['qty_per_cs'] ?? 0,
                'amount' => $item['amount'] ?? 0,
                'remarks' => $item['remarks'] ?? null,
                'store_order_no' => $item['store_order_no'] ?? null,
                'item_type' => 'PRODUCT',
            ]);

            // Save freebie as separate item
            $schemeType = $item['scheme_type'] ?? 'SAME_FREEBIE';
            $isDiffFreebie = $schemeType === 'DIFF_FREEBIE';

            $order->items()->create([
                'sku' => $isDiffFreebie ? ($item['freebie_sku'] ?? null) : ($item['sku'] ?? null),
                'item_description' => $isDiffFreebie ? ($item['freebie_description'] ?? null) : ($item['item_description'] ?? null),
                'price_per_pc' => $isDiffFreebie ? ($item['freebies_price_per_pc'] ?? 0) : ($item['price_per_pc'] ?? 0),
                'price' => $isDiffFreebie ? ($item['freebies_price'] ?? 0) : ($item['price'] ?? 0),
                'qty_per_pc' => $isDiffFreebie ? ($item['freebies_qty_per_pc'] ?? 0) : ($item['qty_per_pc'] ?? 0),
                'qty_per_cs' => 0,
                'freebies_per_cs' => $item['freebies_per_cs'] ?? 0,
                'total_qty' => $item['freebies_per_cs'] ?? 0,
                'amount' => $item['freebie_amount'] ?? 0,
                'remarks' => $item['remarks'] ?? null,
                'store_order_no' => $item['store_order_no'] ?? null,
                'item_type' => 'FREEBIE',
            ]);
        }


        return redirect()->route('forms.sof_submit')->with('success', 'Order created successfully.');
    }


    public function rof()
    {
        // Logic for rendering ROF form
        return view('forms.rof');
    }
}
