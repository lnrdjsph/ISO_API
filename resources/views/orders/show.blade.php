@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Sales Order Details</h1>
                        <p class="text-gray-600 mt-1">Review detailed information about the selected order.</p>
                    </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 bg-white p-6 border rounded-xl shadow-sm mb-6">

            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="space-y-4">
                    <h3 class="text-md font-semibold text-gray-700 mb-2">Customer Info</h3>

                    <div>
                        <p class="text-sm text-gray-600 mb-1">MBC Card No</p>
                        <p class="font-medium text-gray-900">{{ $order->mbc_card_no ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 mb-1">Customer Name</p>
                        <p class="font-medium text-gray-900">{{ $order->customer_name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 mb-1">Contact Number</p>
                        <p class="font-medium text-gray-900">{{ $order->contact_number ?? '-' }}</p>
                    </div>
                </div>
            

                <div class="space-y-4">
                    <h3 class="text-md font-semibold text-gray-700 mb-2">Payment Info</h3>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Payment Center</p>
                        <p class="font-medium text-gray-900">{{ $order->payment_center ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Mode of Payment</p>
                        <p class="font-medium text-gray-900">{{ $order->mode_payment ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Payment Date</p>
                        <p class="font-medium text-gray-900">{{ $order->payment_date ? \Carbon\Carbon::parse($order->payment_date)->format('F j, Y') : '-' }}</p>
                    </div>
                </div>
            </div>
            
        
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="space-y-4">
                    <h3 class="text-md font-semibold text-gray-700 mb-2">Delivery Info</h3>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Mode of Dispatching</p>
                        <p class="font-medium text-gray-900">{{ $order->mode_dispatching ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Delivery/Pickup Date</p>
                        <p class="font-medium text-gray-900">{{ $order->delivery_date ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Address</p>
                        <p class="font-medium text-gray-900">{{ $order->address ?? '-' }}</p>
                    </div>

                    @if(!empty($order->landmark))
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Landmark</p>
                            <p class="font-medium text-gray-900">{{ $order->landmark }}</p>
                        </div>
                    @endif
                </div>


                <div class="space-y-4">
                    <h3 class="text-md font-semibold text-gray-700 mb-2">Order Info</h3>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Requesting Store</p>
                        <p class="font-medium text-gray-900">{{ $order->requesting_store }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Requested By</p>
                        <p class="font-medium text-gray-900">{{ $order->requested_by }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Channel Order</p>
                        <p class="font-medium text-gray-900">{{ $order->channel_order }}</p>
                    </div>                
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Date & Time of Order</p>
                        <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($order->time_order)->format('F j, Y - h:i A') }}</p>
                    </div>                
                </div>
            </div>
        </div>

        <div class="overflow-x-auto bg-white p-4 border rounded-xl shadow-sm">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Ordered Items</h2>
            <table class="min-w-full text-sm text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-xs uppercase">
                    <tr>
                        <th rowspan="2" class="border px-2 py-1 text-left">SKU</th>
                        <th rowspan="2" class="border px-2 py-1 text-left">Item Description</th>
                        <th rowspan="2" class="border px-2 py-1 text-center">Scheme</th>
                        <th rowspan="2" class="border px-2 py-1 text-center">Price/PC</th>
                        <th rowspan="2" class="border px-2 py-1 text-center">Price</th>
                        <th colspan="3" class="border px-2 py-1 text-center">Order in Cases</th>
                        <th rowspan="2" class="border px-2 py-1 text-center">Total Qty</th>
                        <th rowspan="2" class="border px-2 py-1 text-center">Amount</th>
                        <th rowspan="2" class="border px-2 py-1">Remarks</th>
                        <th rowspan="2" class="border px-2 py-1">Store Order No.</th>
                    </tr>
                    <tr>
                        <th class="border p-1 text-center">QTY/PC</th>
                        <th class="border p-1 text-center">QTY/CS</th>
                        <th class="border p-1 text-center">Freebies</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($order->items as $item)
                        <tr class="hover:bg-gray-50 transition {{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="border p-2">{{ $item->sku }}</td>
                            <td class="border p-2">{{ $item->item_description }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->scheme }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ number_format($item->price_per_pc, 2) }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ number_format($item->price, 2) }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->qty_per_pc }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->qty_per_cs }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->freebies_per_cs }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->total_qty }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ number_format($item->amount, 2) }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->remarks }}</td>
                            <td class="border p-2 text-center" contenteditable="true">{{ $item->store_order_no }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="border px-4 py-4 text-center text-gray-500">No items found for this order.</td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
 

        <div class="mt-6">
            <a href="{{ route('orders.index') }}"
            class="inline-block px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm rounded-md">
                &larr; Back to Orders
            </a>
        </div>
    </div>
</div>
@endsection
