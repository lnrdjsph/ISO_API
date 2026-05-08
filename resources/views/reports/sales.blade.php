@extends('layouts.app')

@section('title', 'Sales Overview')

@section('content')
    <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center space-x-3">
            <div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-3 shadow-lg">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20h16M4 4v16" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 14l4-4 4 3 6-6 2 2" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Sales Overview</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Sales performance and freebie distributions across stores.</p>
            </div>
        </div>

        <!-- ── Filter Card ───────────────────────────────────────── -->
        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
            <form id="salesFilterForm" method="GET" action="{{ route('reports.sales') }}">

                {{-- Primary filter row --}}
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Store dropdown --}}
                    <select name="store"
                        class="h-8 min-w-0 flex-shrink-0 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                        <option value="">All Stores</option>
                        @foreach ($stores as $s)
                            <option value="{{ $s->requesting_store }}"
                                {{ request('store') == $s->requesting_store ? 'selected' : '' }}>
                                {{ $allStoreLocations[$s->requesting_store] ?? $s->requesting_store }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Merged date range pill --}}
                    <div
                        class="flex h-8 flex-shrink-0 items-center overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 sm:h-9">
                        <svg class="ml-2 h-3.5 w-3.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <input id="salesFrom" type="date" name="from" value="{{ request('from') }}"
                            class="w-28 border-0 bg-transparent py-0 pl-1.5 pr-0 text-xs text-gray-700 focus:outline-none focus:ring-0 sm:w-32">
                        <span class="px-1 text-xs font-medium text-gray-300">—</span>
                        <input id="salesTo" type="date" name="to" value="{{ request('to') }}"
                            class="w-28 border-0 bg-transparent py-0 pl-0 pr-2 text-xs text-gray-700 focus:outline-none focus:ring-0 sm:w-32">
                    </div>

                    {{-- Actions --}}
                    <button type="submit"
                        class="h-8 flex-shrink-0 rounded-md bg-indigo-600 px-3 text-xs font-medium text-white transition hover:bg-indigo-700 sm:h-9">
                        Filter
                    </button>
                    <button type="button" id="showExportModalBtn"
                        class="h-8 flex-shrink-0 rounded-md bg-emerald-600 px-3 text-xs font-medium text-white transition hover:bg-emerald-700 sm:h-9">
                        Export CSV
                    </button>
                </div>

                {{-- Quick range row --}}
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-gray-400">Quick:</span>
                    @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $r => $lbl)
                        <button type="button" data-range="{{ $r }}"
                            class="sales-quick-range rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs text-gray-600 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 active:scale-95">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>

            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-lg border-l-4 border-indigo-500 bg-white p-3 shadow sm:p-4">
                <p class="text-xs text-gray-500">Total Sales + Freebies</p>
                <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">
                    ₱{{ number_format(($totals->total_sales ?? 0) + ($freebie_totals->total_freebies_value ?? 0), 2) }}
                </h2>
            </div>
            <div class="rounded-lg border-l-4 border-blue-500 bg-white p-3 shadow sm:p-4">
                <p class="text-xs text-gray-500">Total Sales</p>
                <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">
                    ₱{{ number_format($totals->total_sales ?? 0, 2) }}
                </h2>
            </div>
            <div class="rounded-lg border-l-4 border-red-500 bg-white p-3 shadow sm:p-4">
                <p class="text-xs text-gray-500">Freebies Amount</p>
                <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">
                    ₱{{ number_format($freebie_totals->total_freebies_value ?? 0, 2) }}
                </h2>
            </div>
            <div class="rounded-lg border-l-4 border-green-500 bg-white p-3 shadow sm:p-4">
                <p class="text-xs text-gray-500">Total Orders</p>
                <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">
                    {{ $totals->total_orders ?? 0 }}
                </h2>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Sales Per Store Over Time</h3>
                <div id="salesByStoreOverTimeChart" class="min-h-[260px]"></div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Daily Performance Metrics</h3>
                <div id="performanceChart" class="min-h-[260px]"></div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Sales & Freebies by Store</h3>
                <div id="storeComparisonChart" class="min-h-[260px]"></div>
            </div>
        </div>

        <!-- Tables -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Top Selling Products</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full border text-xs sm:text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="border px-3 py-2 text-left">SKU</th>
                                <th class="border px-3 py-2 text-left">Description</th>
                                <th class="border px-3 py-2 text-right">Qty</th>
                                <th class="border px-3 py-2 text-right">Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($top_products as $product)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-3 py-2">{{ $product->sku ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $product->item_description ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($product->total_qty ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right">₱{{ number_format($product->total_sales ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">No products found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Top Freebie Products</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full border text-xs sm:text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="border px-3 py-2 text-left">SKU</th>
                                <th class="border px-3 py-2 text-left">Product</th>
                                <th class="border px-3 py-2 text-right">Qty</th>
                                <th class="border px-3 py-2 text-right">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($top_freebies as $freebie)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-3 py-2">{{ $freebie->sku ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $freebie->item_description ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($freebie->total_qty ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right">₱{{ number_format($freebie->total_value ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">No freebies found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div id="exportModal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Export Settings</h2>
                <button type="button" id="hideExportModalBtn"
                    class="rounded-full p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="GET" action="{{ route('reports.sales.export') }}">
                <div class="mb-3">
                    <label class="mb-1 block text-xs font-medium text-gray-700">Date Range</label>
                    <select id="dateRangeType" name="date_range_type"
                        class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="this_year">This Year</option>
                        <option value="this_month" selected>This Month</option>
                        <option value="today">Today</option>
                        <option value="custom">Custom</option>
                    </select>
                    <div id="customDateFields" class="mt-2 hidden">
                        <div class="flex items-center overflow-hidden rounded-md border border-gray-300 bg-white">
                            <input type="date" name="from"
                                class="flex-1 border-0 bg-transparent px-2 py-1.5 text-xs focus:outline-none focus:ring-0">
                            <span class="px-2 text-xs text-gray-400">→</span>
                            <input type="date" name="to"
                                class="flex-1 border-0 bg-transparent px-2 py-1.5 text-xs focus:outline-none focus:ring-0">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="mb-1 block text-xs font-medium text-gray-700">Store</label>
                    <select name="store" class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">All Stores</option>
                        @foreach ($allStoreLocations as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="mb-1 block text-xs font-medium text-gray-700">Channel Order</label>
                    <select name="channel_order" class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">All Channels</option>
                        @foreach (['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $channel)
                            <option value="{{ $channel }}">{{ $channel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-xs font-medium text-gray-700">Columns</label>
                    <div class="space-y-2">
                        @foreach (['sof_id' => 'Order #', 'customer_name' => 'Customer'] as $k => $l)
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $l }} <span class="text-gray-400">(always)</span></span>
                                <input type="hidden" name="columns[]" value="{{ $k }}">
                            </div>
                        @endforeach
                        @foreach ([
            'channel_order' => 'Channel',
            'time_order' => 'Order Date',
            'requesting_store' => 'Store',
            'grand_total' => 'Grand Total',
            'total_payable' => 'Total Payable',
            'total_freebies' => 'Total Freebies',
        ] as $key => $label)
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-700">{{ $label }}</span>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="columns[]" value="{{ $key }}" checked class="peer sr-only">
                                    <div class="peer h-5 w-9 rounded-full bg-gray-200 transition-colors peer-checked:bg-blue-500"></div>
                                    <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="hideExportModalBtn2"
                        class="rounded-md border border-gray-200 px-4 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-md bg-emerald-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                        Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            var modal = document.getElementById('exportModal');
            var showBtn = document.getElementById('showExportModalBtn');

            function open() {
                modal.classList.remove('hidden');
            }

            function close() {
                modal.classList.add('hidden');
            }
            showBtn.addEventListener('click', open);
            document.getElementById('hideExportModalBtn').addEventListener('click', close);
            document.getElementById('hideExportModalBtn2').addEventListener('click', close);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) close();
            });

            var sel = document.getElementById('dateRangeType');
            var cf = document.getElementById('customDateFields');

            function toggle() {
                cf.classList.toggle('hidden', sel.value !== 'custom');
            }
            sel.addEventListener('change', toggle);
            toggle();

            var form = document.getElementById('salesFilterForm');
            var fromIn = document.getElementById('salesFrom');
            var toIn = document.getElementById('salesTo');

            function fmt(d) {
                return d.toISOString().split('T')[0];
            }

            document.querySelectorAll('.sales-quick-range').forEach(function(btn) {
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
                    fromIn.value = from;
                    toIn.value = to;
                    form.submit();
                });
            });
        })();
    </script>

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            new ApexCharts(document.querySelector('#salesByStoreOverTimeChart'), {
                chart: {
                    type: 'line',
                    height: 280
                },
                series: [
                    @foreach ($by_store_over_time as $store => $rows)
                        {
                            name: "{{ $allStoreLocations[$store] ?? $store }}",
                            data: @json($rows->pluck('sales'))
                        },
                    @endforeach
                ],
                xaxis: {
                    categories: @json($sales_by_day->pluck('day'))
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                legend: {
                    position: 'bottom'
                }
            }).render();

            var storeNames = @json($by_store->pluck('store_name'));
            var salesData = @json($by_store->pluck('total_sales')->map(fn($v) => (float) $v));
            var freebiesData = storeNames.map(function(n) {
                var idx = @json($freebies_by_store->pluck('store_name')).indexOf(n);
                return idx >= 0 ? @json($freebies_by_store->pluck('total_amount')->map(fn($v) => (float) $v))[idx] : 0;
            });

            new ApexCharts(document.querySelector('#storeComparisonChart'), {
                chart: {
                    type: 'bar',
                    height: 280,
                    toolbar: {
                        show: true
                    }
                },
                series: [{
                        name: 'Total Sales',
                        data: salesData,
                        type: 'bar'
                    },
                    {
                        name: 'Freebies Value',
                        data: freebiesData,
                        type: 'bar'
                    },
                ],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '70%',
                        borderRadius: 4
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: storeNames,
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '10px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount (₱)'
                    },
                    labels: {
                        formatter: function(v) {
                            return '₱' + (v / 1000).toFixed(0) + 'K';
                        }
                    }
                },
                colors: ['#4F46E5', '#F59E0B'],
                fill: {
                    opacity: [0.6, 1],
                    type: ['solid', 'solid']
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val, opts) {
                            if (opts.seriesIndex === 1) {
                                var pct = salesData[opts.dataPointIndex] > 0 ? ((val / salesData[opts.dataPointIndex]) * 100).toFixed(1) : 0;
                                return '₱' + val.toLocaleString() + ' (' + pct + '% of sales)';
                            }
                            return '₱' + val.toLocaleString();
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left'
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3
                }
            }).render();

            new ApexCharts(document.querySelector('#performanceChart'), {
                chart: {
                    type: 'line',
                    height: 280,
                    toolbar: {
                        show: true
                    }
                },
                series: [{
                        name: 'Orders Count',
                        type: 'column',
                        data: @json(
                            $sales_by_day->map(function ($day) use ($freebies_by_day) {
                                $fd = $freebies_by_day->where('day', $day['day'])->first();
                                return ($fd['qty'] ?? 0) > 0 ? 1 : 0;
                            }))
                    },
                    {
                        name: 'Sales Trend',
                        type: 'line',
                        data: @json($sales_by_day->pluck('sales')->map(fn($v) => $v / 1000))
                    },
                ],
                xaxis: {
                    categories: @json($sales_by_day->pluck('day')),
                    labels: {
                        rotate: -45
                    }
                },
                yaxis: [{
                        title: {
                            text: 'Orders w/ Freebies'
                        }
                    },
                    {
                        opposite: true,
                        title: {
                            text: 'Sales (K)'
                        },
                        labels: {
                            formatter: function(v) {
                                return '₱' + v.toFixed(0) + 'K';
                            }
                        }
                    },
                ],
                colors: ['#10B981', '#6366F1'],
                stroke: {
                    width: [0, 2]
                },
                tooltip: {
                    y: {
                        formatter: function(val, opts) {
                            return opts.seriesIndex === 0 ? val.toString() : '₱' + (val * 1000).toLocaleString();
                        }
                    }
                }
            }).render();
        });
    </script>
@endsection
