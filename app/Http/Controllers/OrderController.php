<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $query = Order::query();

        // Optional: eager load items if needed
        $query->with('items');

        // Search by customer name or order id
        if ($search = $request->input('search')) {
            $query->where('customer_name', 'like', "%{$search}%")
                ->orWhere('id', 'like', "%{$search}%");
        }

        $orders = $query->orderByDesc('created_at')->paginate(10);

        return view('orders.orders', compact('orders'));
    }


    public function create()
    {
        $orders = Order::with('items')->get();
        return view('orders.create', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.show', compact('order'));
    }




    public function store(Request $request)
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
            $order->items()->create([
                'sku' => $item['sku'] ?? null,
                'item_description' => $item['item_description'] ?? null,
                'scheme' => $item['scheme'] ?? null,
                'price_per_pc' => $item['price_per_pc'] ?? 0,
                'price' => $item['price'] ?? 0,
                'qty_per_pc' => $item['qty_per_pc'] ?? 0,
                'qty_per_cs' => $item['qty_per_cs'] ?? 0,
                'freebies_per_cs' => $item['freebies_per_cs'] ?? 0,
                'total_qty' => $item['total_qty'] ?? 0,
                'amount' => $item['amount'] ?? 0,
                'remarks' => $item['remarks'] ?? null,
                'store_order_no' => $item['store_order_no'] ?? null,
            ]);
        }

        return redirect()->route('orders.create')->with('success', 'Order created successfully.');
    }



}
