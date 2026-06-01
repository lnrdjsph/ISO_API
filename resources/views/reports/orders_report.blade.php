@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-6 flex items-center space-x-3">
            <div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-3 shadow-lg">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20h16M4 4v16" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14l4-4 4 3 6-6 2 2" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Sales Order Report</h1>
                <p class="text-xs text-gray-500 sm:text-sm">B2B2C sales orders report for accounting and financial review.</p>
            </div>
        </div>

        <!-- ── Filter Card ───────────────────────────────────────── -->
        <div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
            <form id="ordersFilterForm" method="GET" action="{{ route('reports.orders') }}">

                {{-- Row 1: Search + Dropdowns --}}
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Search --}}
                    <div class="relative min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search orders…"
                            class="h-8 w-40 rounded-md border border-gray-300 pl-8 pr-3 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9 sm:w-56">
                    </div>

                    {{-- Store --}}
                    <select name="store_code"
                        class="h-8 min-w-0 flex-shrink-0 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                        <option value="">All Stores</option>
                        @foreach ($storeLocations as $code => $label)
                            <option value="{{ $code }}" {{ request('store_code') == $code ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Channel --}}
                    <select name="channel"
                        class="h-8 min-w-0 flex-shrink-0 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                        <option value="">All Channels</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel }}" {{ request('channel') == $channel ? 'selected' : '' }}>
                                {{ $channel }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Status --}}
                    <select name="status"
                        class="h-8 min-w-0 flex-shrink-0 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ ucfirst(strtolower($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Row 2: Date range + actions --}}
                <div class="mt-2 flex flex-wrap items-center gap-2">

                    {{-- Merged date range pill --}}
                    <div
                        class="flex h-8 flex-shrink-0 items-center overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 sm:h-9">
                        <svg class="ml-2 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <input id="ordersStartDate" type="date" name="start_date" value="{{ request('start_date') }}"
                            class="w-28 border-0 bg-transparent py-0 pl-1.5 pr-0 text-xs text-gray-700 focus:outline-none focus:ring-0 sm:w-32">
                        <span class="px-1 text-xs font-medium text-gray-300">—</span>
                        <input id="ordersEndDate" type="date" name="end_date" value="{{ request('end_date') }}"
                            class="w-28 border-0 bg-transparent py-0 pl-0 pr-2 text-xs text-gray-700 focus:outline-none focus:ring-0 sm:w-32">
                    </div>

                    <button type="submit"
                        class="h-8 flex-shrink-0 rounded-md bg-indigo-600 px-3 text-xs font-medium text-white transition hover:bg-indigo-700 sm:h-9">
                        Apply
                    </button>
                    <a href="{{ route('reports.orders') }}"
                        class="flex h-8 flex-shrink-0 items-center rounded-md border border-gray-200 bg-white px-3 text-xs text-gray-600 transition hover:bg-gray-50 sm:h-9">
                        Reset
                    </a>
                    <a href="{{ route('reports.orders.export', request()->query()) }}" id="exportBtn"
                        class="flex h-8 flex-shrink-0 items-center rounded-md bg-emerald-600 px-3 text-xs font-medium text-white transition hover:bg-emerald-700 active:scale-95 sm:h-9">
                        Export CSV
                    </a>
                </div>

                {{-- Quick range row --}}
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-gray-400">Quick:</span>
                    @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $r => $lbl)
                        <button type="button" data-range="{{ $r }}"
                            class="orders-quick-range rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs text-gray-600 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 active:scale-95">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>

            </form>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col gap-4 lg:grid lg:grid-cols-5 lg:gap-4">

            <!-- Table Card -->
            <div class="flex min-h-[400px] flex-col rounded-xl bg-white shadow-lg lg:col-span-4">
                <div class="overflow-x-auto rounded-t-xl">
                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <thead class="bg-gray-50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-700">Order #</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Customer</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Channel</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Order Date</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Requesting Store</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Payable Amount</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Freebies Amount</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Grand Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($orders as $order)
                                <tr class="transition-colors hover:bg-indigo-50/60">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $order->sof_id }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $order->customer_name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $order->channel_order }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        {{ \Carbon\Carbon::parse($order->time_order)->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        {{ $allStoreLocations[$order->requesting_store] ?? 'Unknown Store' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-green-600">
                                        ₱{{ number_format($order->payable_amount, 2) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-green-600">
                                        ₱{{ number_format($order->freebies_amount, 2) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-green-600">
                                        ₱{{ number_format($order->grand_total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No orders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 p-4">
                    <form method="GET" action="{{ route('reports.orders') }}" class="flex items-center gap-2">
                        @foreach (request()->except('per_page', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label for="per_page" class="text-xs text-gray-500">Rows:</label>
                        <select name="per_page" id="per_page" data-auto-submit="true"
                            class="h-7 rounded border-gray-200 px-1 text-xs">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="overflow-x-auto">
                        {{ $orders->links('pagination::tailwind') }}
                    </div>
                </div>
            </div>

            <!-- Totals Sidebar -->
            <div class="rounded-xl bg-white p-5 shadow-lg lg:col-span-1">
                <h2 class="mb-4 text-sm font-bold text-green-800 sm:text-base">Orders Totals</h2>
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-4 lg:flex-col">
                    <div class="flex-1 rounded-lg bg-gray-50 p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Payable Amount</p>
                        <p class="mt-1 text-lg font-bold text-green-700 sm:text-xl">
                            ₱{{ number_format($totals['payable'], 2) }}
                        </p>
                    </div>
                    <div class="flex-1 rounded-lg bg-gray-50 p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Freebies Amount</p>
                        <p class="mt-1 text-lg font-bold text-green-700 sm:text-xl">
                            ₱{{ number_format($totals['freebies'], 2) }}
                        </p>
                    </div>
                    <div class="flex-1 rounded-lg bg-gray-50 p-3 shadow-sm">
                        <p class="text-xs text-gray-500">Grand Total</p>
                        <p class="mt-1 text-lg font-bold text-green-700 sm:text-xl">
                            ₱{{ number_format($totals['grand'], 2) }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            // Export button loading state
            var exportBtn = document.getElementById('exportBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    exportBtn.classList.add('opacity-50', 'pointer-events-none');
                    exportBtn.textContent = 'Exporting…';
                    setTimeout(function() {
                        exportBtn.classList.remove('opacity-50', 'pointer-events-none');
                        exportBtn.textContent = 'Export CSV';
                    }, 3000);
                });
            }

            // Per-page auto-submit
            var perPage = document.querySelector('[data-auto-submit="true"]');
            if (perPage) {
                perPage.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            }

            // Quick Range
            var form = document.getElementById('ordersFilterForm');
            var startIn = document.getElementById('ordersStartDate');
            var endIn = document.getElementById('ordersEndDate');

            function fmt(d) {
                return d.toISOString().split('T')[0];
            }

            document.querySelectorAll('.orders-quick-range').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var r = this.dataset.range,
                        t = new Date(),
                        from, to;
                    if (r === 'today') {
                        from = to = fmt(t);
                    } else if (r === 'week') {
                        var diff = t.getDay() === 0 ? -6 : 1 - t.getDay();
                        var mon = new Date(t);
                        mon.setDate(t.getDate() + diff);
                        var sun = new Date(mon);
                        sun.setDate(mon.getDate() + 6);
                        from = fmt(mon);
                        to = fmt(sun);
                    } else if (r === 'month') {
                        from = fmt(new Date(t.getFullYear(), t.getMonth(), 1));
                        to = fmt(new Date(t.getFullYear(), t.getMonth() + 1, 0));
                    } else if (r === 'year') {
                        from = fmt(new Date(t.getFullYear(), 0, 1));
                        to = fmt(new Date(t.getFullYear(), 11, 31));
                    }
                    startIn.value = from;
                    endIn.value = to;
                    form.submit();
                });
            });
        })();
    </script>
@endsection
