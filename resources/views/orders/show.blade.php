@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-xl font-bold mb-4">Order Details - ID: {{ $order->id }}</h1>

    <div class="mb-6">
        <p><strong>Channel Order:</strong> {{ $order->channel_order }}</p>
        <p><strong>Time Order:</strong> {{ $order->time_order }}</p>
        <p><strong>Payment Center:</strong> {{ $order->payment_center ?? '-' }}</p>
        <p><strong>Mode Payment:</strong> {{ $order->mode_payment ?? '-' }}</p>
        <p><strong>Payment Date:</strong> {{ $order->payment_date ?? '-' }}</p>
        <p><strong>Mode Dispatching:</strong> {{ $order->mode_dispatching ?? '-' }}</p>
        <p><strong>Delivery Date:</strong> {{ $order->delivery_date ?? '-' }}</p>
        <p><strong>Address:</strong> {{ $order->address ?? '-' }}</p>
        <p><strong>Landmark:</strong> {{ $order->landmark ?? '-' }}</p>
    </div>

    <table class="min-w-full border border-gray-300 text-sm">
        <thead class="bg-slate-100 text-left">
            <tr>
                <th class="border px-2 py-1">SKU</th>
                <th class="border px-2 py-1">Item Description</th>
                <th class="border px-2 py-1 text-right">Price / PC</th>
                <th class="border px-2 py-1 text-right">Price</th>
                {{-- <th class="border px-2 py-1 text-center">Order / CS</th> --}}
                <th class="border px-2 py-1 text-right">Total Qty</th>
                <th class="border px-2 py-1 text-right">Amount</th>
                <th class="border px-2 py-1">Remarks</th>
                <th class="border px-2 py-1">Store Order No.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td class="border px-2 py-1">{{ $item->sku }}</td>
                    <td class="border px-2 py-1">{{ $item->item_description }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($item->price_per_pc, 2) }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($item->price, 2) }}</td>
                    {{-- <td class="border px-2 py-1 text-center">{{ $item->order_per_cs }}</td> --}}
                    <td class="border px-2 py-1 text-right">{{ $item->total_qty }}</td>
                    <td class="border px-2 py-1 text-right">{{ number_format($item->amount, 2) }}</td>
                    <td class="border px-2 py-1">{{ $item->remarks }}</td>
                    <td class="border px-2 py-1">{{ $item->store_order_no }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="border px-2 py-4 text-center">No items found for this order.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">
        <a href="{{ route('orders.index') }}" class="text-indigo-600 hover:underline">&larr; Back to Orders</a>
    </div>
</div>
@endsection
