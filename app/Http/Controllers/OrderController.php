<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items')->get();
        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.show', compact('order'));
    }


    public function create()
    {
        return view('orders.create'); // your form view
    }

    public function store(Request $request)
    {
        $request->validate([
            'channel_order' => 'required|string',
            'time_order' => 'required',
            'payment_center' => 'nullable|string',
            'mode_payment' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'mode_dispatching' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'address' => 'nullable|string',
            'landmark' => 'nullable|string',
            'orders' => 'required|array',
            'orders.*.sku' => 'nullable|string',
            'orders.*.item_description' => 'nullable|string',
            'orders.*.price_per_pc' => 'nullable|numeric',
            'orders.*.price' => 'nullable|numeric',
            'orders.*.order_per_cs' => 'nullable|string',
            'orders.*.total_qty' => 'nullable|integer',
            'orders.*.amount' => 'nullable|numeric',
            'orders.*.remarks' => 'nullable|string',
            'orders.*.store_order_no' => 'nullable|string',
        ]);

        $order = Order::create([
            'channel_order' => $request->channel_order,
            'time_order' => $request->time_order,
            'payment_center' => $request->payment_center,
            'mode_payment' => $request->mode_payment,
            'payment_date' => $request->payment_date,
            'mode_dispatching' => $request->mode_dispatching,
            'delivery_date' => $request->delivery_date,
            'address' => $request->address,
            'landmark' => $request->landmark,
        ]);

        // Save order items
        foreach ($request->orders as $item) {
            if (!empty($item['sku']) || !empty($item['item_description'])) {
                $order->items()->create([
                    'sku' => $item['sku'],
                    'item_description' => $item['item_description'],
                    'price_per_pc' => $item['price_per_pc'] ?? 0,
                    'price' => $item['price'] ?? 0,
                    // 'order_per_cs' => $item['order_per_cs'],
                    'total_qty' => $item['total_qty'] ?? 0,
                    'amount' => $item['amount'] ?? 0,
                    'remarks' => $item['remarks'],
                    'store_order_no' => $item['store_order_no'],
                ]);
            }
        }

        return redirect()->route('orders.index')->with('success', 'Order created successfully.');
    }
}
