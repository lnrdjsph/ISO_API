<!-- Table -->
<span class="orders-summary hidden">{{ $orders->total() }}</span>
<style>
    /* ── Orders list: mobile card layout ── */
    .mobile-view-link {
        display: none;
    }

    @media (max-width: 767px) {
        .orders-list-table thead {
            display: none !important;
        }

        .orders-list-table tbody tr {
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 0.875rem;
            margin-bottom: 0.75rem;
            padding: 0.625rem 0.875rem 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            background: white;
        }

        .orders-list-table tbody tr:hover {
            background: #eef2ff;
        }

        /* Every td: horizontal label → value row */
        .orders-list-table td {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: none !important;
            padding: 0.25rem 0;
            font-size: 0.78rem;
            color: #111827;
            border-bottom: 1px dashed #f3f4f6 !important;
            white-space: normal;
        }

        .orders-list-table td:last-child {
            border-bottom: none !important;
        }

        /* Label via ::before */
        .orders-list-table td[data-label]::before {
            content: attr(data-label);
            flex-shrink: 0;
            min-width: 5.5rem;
            font-size: 0.62rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* Order # — prominent header-like row */
        .orders-list-table td[data-label="Order #"] {
            font-weight: 700;
            font-size: 0.85rem;
            color: #4f46e5;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid #e0e7ff !important;
            margin-bottom: 0.15rem;
            justify-content: space-between;
            align-items: center;
            flex-wrap: nowrap;
        }

        .orders-list-table td[data-label="Order #"] span {
            flex: 1 1 auto;
            min-width: 0;
        }

        /* Actions — pill button, right-aligned, no label */
        .orders-list-table td[data-label="Actions"] {
            justify-content: flex-end;
            border-bottom: none !important;
            padding-top: 0.4rem;
            display: none !important;
        }

        .orders-list-table td[data-label="Actions"]::before {
            display: none;
        }

        .orders-list-table td[data-label="Actions"] a {
            display: inline-flex;
            align-items: center;
            background: #4f46e5;
            color: white;
            padding: 0.3rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            text-decoration: none;
        }

        .orders-list-table td[data-label="Actions"] a:hover {
            background: #4338ca;
        }

        .mobile-view-link {
            display: inline-flex;
            align-items: center;
            background: #4f46e5;
            color: white;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            text-decoration: none;
        }

        .mobile-view-link:hover {
            background: #4338ca;
        }
    }

    /* Pagination: center on small screens */
    @media (max-width: 640px) {
        nav[aria-label="Pagination Navigation"] {
            flex-wrap: wrap;
            gap: 0.25rem;
            justify-content: center;
        }
    }
</style>

<div class="mb-4 overflow-x-auto rounded-xl">
    <table class="orders-list-table min-w-full divide-y divide-gray-200 rounded-xl text-sm">
        <thead class="bg-gray-50 text-left">
            <tr>
                <th class="px-4 py-3 font-medium text-gray-700">Order #</th>
                <th class="px-4 py-3 font-medium text-gray-700">Customer</th>
                @if (auth()->user()->role === 'store manager' || auth()->user()->role === 'super admin')
                    <th class="px-4 py-3 font-medium text-gray-700">Requesting Store</th>
                @endif
                <th class="px-4 py-3 font-medium text-gray-700">Order Date</th>
                <th class="px-4 py-3 font-medium text-gray-700">Channel</th>
                <th class="px-4 py-3 font-medium text-gray-700">Status</th>
                <th class="px-4 py-3 text-center font-medium text-gray-700">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse($orders as $order)
                <tr class="animate-fade-in transition-all duration-200 hover:bg-indigo-100/60">
                    <td class="whitespace-nowrap px-4 py-3" data-label="Order #">
                        <span>{{ $order->sof_id }}</span>
                        <a href="{{ route('orders.show', $order->id) }}" class="mobile-view-link">View →</a>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3" data-label="Customer">{{ $order->customer_name }}</td>

                    @if (auth()->user()->role === 'store manager' || auth()->user()->role === 'super admin')
                        @php
                            // Use LocationConfig to get the store name – no more hardcoded array
                            $storeName = \App\Support\LocationConfig::storeName($order->requesting_store, 'Unknown Store');
                        @endphp
                        <td class="whitespace-nowrap px-4 py-3" data-label="Store">{{ $storeName }}</td>
                    @endif

                    <td class="whitespace-nowrap px-4 py-3" data-label="Date">
                        {{ \Carbon\Carbon::parse($order->time_order)->format('Y-m-d H:i') }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3" data-label="Channel">
                        @php
                            $channel = strtolower(trim($order->channel_order ?? ''));
                            $channelDisplay = ucwords($channel ?: 'Unknown');
                            $channelClass = match ($channel) {
                                'e-commerce', 'ecommerce', 'online' => 'bg-yellow-100 text-green-800',
                                'wholesale', 'wholesaler' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="{{ $channelClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
                            {{ $channelDisplay }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3" data-label="Status">
                        @php
                            $status = ucwords(strtolower($order->order_status ?? 'New Order'));
                            $statusClass = match ($status) {
                                'Completed' => 'bg-green-200 text-green-800',
                                'Archived' => 'bg-gray-200 text-gray-800',
                                'Cancelled' => 'bg-red-200 text-red-800',
                                'Pending' => 'bg-yellow-200 text-yellow-800',
                                'Rejected' => 'bg-orange-200 text-orange-800',
                                'For Approval' => 'bg-purple-100 text-purple-800',
                                'Approved' => 'bg-green-100 text-green-800',
                                default => 'bg-blue-100 text-blue-800',
                            };
                        @endphp
                        <span class="{{ $statusClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
                            {{ $status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center" data-label="Actions">
                        <a href="{{ route('orders.show', $order->id) }}" class="inline-block font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                            View →
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
<div class="flex flex-wrap items-center justify-end gap-2 p-4">
    {{ $orders->links('pagination::tailwind') }}
</div>
