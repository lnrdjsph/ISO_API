@extends('layouts.app')

@section('title', 'Freebies Report Dashboard')

@section('content')
    <div class="space-y-6 p-6">
        <!-- Header -->
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                <div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
                    <svg
                        class="h-5 w-5 flex-shrink-0"
                        fill="none"
                        stroke="white"
                        viewBox="0 0 24 24">
                        <!-- Axes -->
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 20h16M4 4v16" />
                        <!-- Line Chart -->
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 14l4-4 4 3 6-6 2 2" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Freebies Report</h1>
                    <p class="mt-1 text-gray-600">View and filter reports of all redeemed freebies across stores.</p>

                </div>
            </div>
        </div>
        <div class="flex items-center justify-between">
            {{-- <h1 class="text-2xl font-bold text-gray-800">Freebies Report Dashboard</h1> --}}
            <form
                method="GET"
                action="{{ route('reports.freebies') }}"
                class="flex gap-2">
                @php
                    $allStoreLocations = [
                        '4002' => 'F2 - Metro Wholesalemart Colon',
                        '2010' => 'S10 - Metro Maasin',
                        '2017' => 'S17 - Metro Tacloban',
                        '2019' => 'S19 - Metro Bay-Bay',
                        '3018' => 'F18 - Metro Alang-Alang',
                        '3019' => 'F19 - Metro Hilongos',
                        '2008' => 'S8 - Metro Toledo',
                        '6012' => 'H8 - Super Metro Antipolo',
                        '6009' => 'H9 - Super Metro Carcar',
                        '6010' => 'H10 - Super Metro Bogo',
                    ];
                @endphp
                <select
                    name="store"
                    class="rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Stores</option>
                    @foreach ($stores as $s)
                        @php
                            $storeCode = strtolower($s->requesting_store);
                            $storeName = $allStoreLocations[$storeCode] ?? $s->requesting_store;
                        @endphp
                        <option
                            value="{{ $s->requesting_store }}"
                            {{ request('store') == $s->requesting_store ? 'selected' : '' }}>
                            {{ $storeName }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="date"
                    name="from"
                    value="{{ request('from') }}"
                    class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <input
                    type="date"
                    name="to"
                    value="{{ request('to') }}"
                    class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <button
                    type="submit"
                    class="rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700">
                    Filter
                </button>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Total Freebies Quantity</p>
                <h2 class="text-xl font-bold text-gray-800">{{ number_format($totals->total_freebies_qty ?? 0) }}</h2>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Total Freebies Value</p>
                <h2 class="text-xl font-bold text-gray-800">₱{{ number_format($totals->total_freebies_value ?? 0, 2) }}</h2>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Orders with Freebies</p>
                <h2 class="text-xl font-bold text-gray-800">{{ $totals->orders_with_freebies ?? 0 }}</h2>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold text-gray-700">Freebies Over Time</h3>
                <div
                    id="freebiesChart"
                    class="h-64"></div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold text-gray-700">Freebies by Store</h3>
                <div
                    id="storeChart"
                    class="h-64"></div>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-lg bg-white p-4 shadow">
            <h3 class="mb-3 font-semibold text-gray-700">Top Freebie Products</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="border px-3 py-2 text-left">SKU</th>
                            <th class="border px-3 py-2 text-left">Product</th>
                            <th class="border px-3 py-2 text-right">Total Qty</th>
                            <th class="border px-3 py-2 text-right">Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($top_freebies as $freebie)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-3 py-2">{{ $freebie->sku ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $freebie->item_description ?? 'N/A' }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($freebie->total_qty ?? 0) }}</td>
                                <td class="px-3 py-2 text-right">₱{{ number_format($freebie->total_value ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // 📊 Freebies Over Time (Line Chart with Qty + Amount)
        var freebiesOptions = {
            chart: {
                type: 'line',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                    name: 'Freebies Qty',
                    data: @json($byDay->pluck('qty'))
                },
                {
                    name: 'Freebies Amount',
                    data: @json($byDay->pluck('amount'))
                }
            ],
            xaxis: {
                categories: @json($byDay->pluck('day')),
                labels: {
                    rotate: -45
                }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#10B981', '#3B82F6'],
            yaxis: [{
                    title: {
                        text: "Quantity"
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: "Amount (₱)"
                    },
                    labels: {
                        formatter: val => "₱" + val.toFixed(2)
                    }
                }
            ],
            tooltip: {
                y: {
                    formatter: function(val, opts) {
                        return opts.seriesIndex === 1 ? "₱" + val.toFixed(2) : val;
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#freebiesChart"), freebiesOptions).render();

        // 📊 Freebies by Store (Donut Chart with Amount)
        var storeOptions = {
            chart: {
                type: 'donut',
                height: 300
            },
            series: @json($byStore->pluck('total_amount')->map(fn($v) => (float) $v)),
            labels: @json($byStore->pluck('store_name')),
            colors: ['#6366F1', '#F59E0B', '#10B981', '#EF4444', '#3B82F6'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: val => "₱" + val.toFixed(2)
                }
            }
        };
        new ApexCharts(document.querySelector("#storeChart"), storeOptions).render();
    </script>

@endsection
