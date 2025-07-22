@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Sales Order List</h1>
                        <p class="text-gray-600 mt-1">List of all B2B sales orders submitted for processing and fulfillment.</p>
                    </div>
            </div>
        </div>


        <div class="bg-white p-6 rounded-xl shadow-lg space-y-6">
            <!-- Search Bar -->
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('orders.index') }}" class="w-full max-w-sm">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by customer or order number"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium text-gray-700">Order #</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Customer</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Channel</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Order Date</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Delivery Date</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Status</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($orders as $order)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $order->id }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $order->customer_name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $order->channel_order }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($order->time_order)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full {{ $order->order_status === 'Delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $order->order_status ?? 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('orders.show', $order->id) }}"
                                        class="inline-block text-indigo-600 hover:text-indigo-800 font-medium">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-gray-500">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $orders->withQueryString()->links('pagination::tailwind') }}
            </div>
            
        </div>

  </div>
</div>

@endsection