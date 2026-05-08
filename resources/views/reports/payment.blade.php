@extends('layouts.app')

@section('title', 'Payments Overview')

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
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Payments Overview</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Payments, sales trends, and performance metrics.</p>
            </div>
        </div>

        <!-- ── Filter Card ───────────────────────────────────────── -->
        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
            <form id="paymentFilterForm" method="GET" action="{{ route('reports.payments') }}">

                {{-- Primary filter row --}}
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Store --}}
                    <select name="store"
                        class="h-8 min-w-0 flex-shrink-0 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                        <option value="">All Stores</option>
                        @foreach ($stores as $s)
                            <option value="{{ $s }}" {{ $selected_store == $s ? 'selected' : '' }}>
                                {{ $allStoreLocations[$s] ?? $s }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Payment mode --}}
                    <select name="mode_payment"
                        class="h-8 min-w-0 flex-shrink-0 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                        <option value="">All Modes</option>
                        @foreach ($modes as $m)
                            <option value="{{ $m }}" {{ $selected_mode == $m ? 'selected' : '' }}>
                                {{ $m ?? 'Unspecified' }}
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
                        <input id="paymentFrom" type="date" name="from" value="{{ request('from') }}"
                            class="w-28 border-0 bg-transparent py-0 pl-1.5 pr-0 text-xs text-gray-700 focus:outline-none focus:ring-0 sm:w-32">
                        <span class="px-1 text-xs font-medium text-gray-300">—</span>
                        <input id="paymentTo" type="date" name="to" value="{{ request('to') }}"
                            class="w-28 border-0 bg-transparent py-0 pl-0 pr-2 text-xs text-gray-700 focus:outline-none focus:ring-0 sm:w-32">
                    </div>

                    <button type="submit"
                        class="h-8 flex-shrink-0 rounded-md bg-indigo-600 px-3 text-xs font-medium text-white transition hover:bg-indigo-700 sm:h-9">
                        Filter
                    </button>
                </div>

                {{-- Quick range row --}}
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span class="text-xs text-gray-400">Quick:</span>
                    @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $r => $lbl)
                        <button type="button" data-range="{{ $r }}"
                            class="payment-quick-range rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs text-gray-600 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 active:scale-95">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>

            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-lg border-l-4 border-indigo-500 bg-white p-3 shadow sm:p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500">Total Sales</p>
                        <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">₱{{ number_format($totals->total_sales ?? 0, 2) }}</h2>
                    </div>
                    @if ($totals->sales_growth > 0)
                        <span class="text-xs text-green-600">↗ {{ $totals->sales_growth }}%</span>
                    @elseif ($totals->sales_growth < 0)
                        <span class="text-xs text-red-600">↘ {{ abs($totals->sales_growth) }}%</span>
                    @else
                        <span class="text-xs text-gray-400">— 0%</span>
                    @endif
                </div>
            </div>
            <div class="rounded-lg border-l-4 border-green-500 bg-white p-3 shadow sm:p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500">Total Orders</p>
                        <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">{{ number_format($totals->total_orders ?? 0) }}</h2>
                    </div>
                    @if ($totals->orders_growth > 0)
                        <span class="text-xs text-green-600">↗ {{ $totals->orders_growth }}%</span>
                    @elseif ($totals->orders_growth < 0)
                        <span class="text-xs text-red-600">↘ {{ abs($totals->orders_growth) }}%</span>
                    @else
                        <span class="text-xs text-gray-400">— 0%</span>
                    @endif
                </div>
            </div>
            <div class="rounded-lg border-l-4 border-purple-500 bg-white p-3 shadow sm:p-4">
                <p class="text-xs text-gray-500">Avg Order Value</p>
                <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">₱{{ number_format($totals->avg_order_value ?? 0, 2) }}</h2>
            </div>
            <div class="rounded-lg border-l-4 border-orange-500 bg-white p-3 shadow sm:p-4">
                <p class="text-xs text-gray-500">Items Sold</p>
                <h2 class="mt-0.5 text-base font-bold text-gray-800 sm:text-xl">{{ number_format($totals->total_items_sold ?? 0) }}</h2>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Sales Trend by Payment Mode</h3>
                <div id="paymentTrendChart" class="min-h-[260px]"></div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Payment Mode Distribution</h3>
                <div id="paymentByModeChart" class="min-h-[260px]"></div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Sales by Store & Payment Mode</h3>
                <div id="salesByStoreChart" class="min-h-[260px]"></div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Hourly Sales by Payment Mode</h3>
                <div id="hourlyDistributionChart" class="min-h-[260px]"></div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            var form = document.getElementById('paymentFilterForm');
            var fromIn = document.getElementById('paymentFrom');
            var toIn = document.getElementById('paymentTo');

            function fmt(d) {
                return d.toISOString().split('T')[0];
            }

            document.querySelectorAll('.payment-quick-range').forEach(function(btn) {
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

            // Payment Mode Donut
            try {
                var byModeData = @json($by_mode);
                if (Array.isArray(byModeData) && byModeData.length > 0) {
                    new ApexCharts(document.querySelector('#paymentByModeChart'), {
                        chart: {
                            type: 'donut',
                            height: 280
                        },
                        series: byModeData.map(function(i) {
                            return parseFloat(i.total_sales) || 0;
                        }),
                        labels: byModeData.map(function(i) {
                            return i.mode_payment || 'Unknown';
                        }),
                        legend: {
                            position: 'bottom'
                        },
                        dataLabels: {
                            formatter: function(v) {
                                return v.toFixed(1) + '%';
                            }
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            formatter: function(w) {
                                                var t = w.globals.seriesTotals.reduce(function(a, b) {
                                                    return a + b;
                                                }, 0);
                                                return '₱' + t.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }).render();
                } else {
                    document.querySelector('#paymentByModeChart').innerHTML =
                        '<div class="flex h-64 items-center justify-center text-sm text-gray-400">No payment mode data</div>';
                }
            } catch (e) {
                console.error(e);
            }

            // Daily Trend by Payment Mode
            try {
                var salesByDayByMode = @json($sales_by_day_by_mode);
                var paymentModes = @json($payment_modes);
                var modeColors = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

                if (Object.keys(salesByDayByMode).length > 0 && paymentModes.length > 0) {
                    new ApexCharts(document.querySelector('#paymentTrendChart'), {
                        chart: {
                            type: 'line',
                            height: 280,
                            toolbar: {
                                show: true
                            }
                        },
                        series: paymentModes.map(function(mode, idx) {
                            return {
                                name: mode + ' (Sales)',
                                type: 'line',
                                data: (salesByDayByMode[mode] || []).map(function(i) {
                                    return parseFloat(i.sales) || 0;
                                }),
                                color: modeColors[idx % modeColors.length]
                            };
                        }),
                        xaxis: {
                            categories: (salesByDayByMode[paymentModes[0]] || []).map(function(i) {
                                return i.day;
                            }),
                            labels: {
                                rotate: -45
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Sales (₱)'
                            },
                            labels: {
                                formatter: function(v) {
                                    return '₱' + (v || 0).toLocaleString();
                                }
                            }
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(v) {
                                    return '₱' + (v || 0).toLocaleString();
                                }
                            }
                        }
                    }).render();
                } else {
                    document.querySelector('#paymentTrendChart').innerHTML =
                        '<div class="flex h-64 items-center justify-center text-sm text-gray-400">No trend data</div>';
                }
            } catch (e) {
                console.error(e);
                document.querySelector('#paymentTrendChart').innerHTML =
                    '<div class="flex h-64 items-center justify-center text-sm text-red-400">Error loading chart</div>';
            }

            // Sales by Store & Payment Mode
            try {
                var byStoreModeData = @json($by_store_mode);
                var paymentModes2 = @json($payment_modes);
                var storeColors = ['#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#4F46E5'];

                if (Object.keys(byStoreModeData).length > 0 && paymentModes2.length > 0) {
                    var stores2 = Object.keys(byStoreModeData);
                    new ApexCharts(document.querySelector('#salesByStoreChart'), {
                        chart: {
                            type: 'bar',
                            height: 280,
                            stacked: true
                        },
                        series: paymentModes2.map(function(mode, idx) {
                            return {
                                name: mode,
                                color: storeColors[idx % storeColors.length],
                                data: stores2.map(function(store) {
                                    var sd = byStoreModeData[store];
                                    var md = sd ? sd.find(function(i) {
                                        return i.mode_payment === mode;
                                    }) : null;
                                    return parseFloat((md && md.total_sales) || 0);
                                })
                            };
                        }),
                        xaxis: {
                            categories: stores2,
                            labels: {
                                rotate: -45,
                                style: {
                                    fontSize: '10px'
                                }
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Sales (₱)'
                            },
                            labels: {
                                formatter: function(v) {
                                    return '₱' + (v || 0).toLocaleString();
                                }
                            }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '70%'
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(v) {
                                    return '₱' + (v || 0).toLocaleString();
                                }
                            }
                        }
                    }).render();
                } else {
                    document.querySelector('#salesByStoreChart').innerHTML =
                        '<div class="flex h-64 items-center justify-center text-sm text-gray-400">No store data</div>';
                }
            } catch (e) {
                console.error(e);
                document.querySelector('#salesByStoreChart').innerHTML =
                    '<div class="flex h-64 items-center justify-center text-sm text-red-400">Error loading chart</div>';
            }

            // Hourly Distribution
            try {
                var hourlyDataByMode = @json($hourly_data_by_mode);
                var paymentModes3 = @json($payment_modes);
                var hourlyColors = ['#F59E0B', '#10B981', '#4F46E5', '#EF4444', '#8B5CF6'];

                if (Object.keys(hourlyDataByMode).length > 0 && paymentModes3.length > 0) {
                    new ApexCharts(document.querySelector('#hourlyDistributionChart'), {
                        chart: {
                            type: 'area',
                            height: 280,
                            stacked: false
                        },
                        series: paymentModes3.map(function(mode, idx) {
                            return {
                                name: mode,
                                color: hourlyColors[idx % hourlyColors.length],
                                data: (hourlyDataByMode[mode] || []).map(function(i) {
                                    return parseFloat(i.sales) || 0;
                                })
                            };
                        }),
                        xaxis: {
                            categories: (hourlyDataByMode[paymentModes3[0]] || []).map(function(i) {
                                return i.hour;
                            }),
                            title: {
                                text: 'Hour of Day'
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Sales (₱)'
                            },
                            labels: {
                                formatter: function(v) {
                                    return '₱' + (v || 0).toLocaleString();
                                }
                            }
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 0.5,
                                opacityFrom: 0.7,
                                opacityTo: 0.3
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(v) {
                                    return '₱' + (v || 0).toLocaleString();
                                }
                            }
                        }
                    }).render();
                } else {
                    document.querySelector('#hourlyDistributionChart').innerHTML =
                        '<div class="flex h-64 items-center justify-center text-sm text-gray-400">No hourly data</div>';
                }
            } catch (e) {
                console.error(e);
            }
        });
    </script>
@endsection
