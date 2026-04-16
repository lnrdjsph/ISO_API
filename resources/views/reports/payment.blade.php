@extends('layouts.app')

@section('title', 'Payments Overview')

@section('content')
    <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                <div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 p-3 shadow-lg">
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
                    <h1 class="text-3xl font-bold text-gray-900">Payments Overview</h1>
                    <p class="mt-1 text-gray-600">Comprehensive analysis of payments, sales trends, and performance metrics.</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center justify-between">
            <form
                method="GET"
                action="{{ route('reports.payments') }}"
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
                            $storeCode = strtolower($s);
                            $storeName = $allStoreLocations[$storeCode] ?? $s;
                        @endphp
                        <option
                            value="{{ $s }}"
                            {{ $selected_store == $s ? 'selected' : '' }}>
                            {{ $storeName }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="mode_payment"
                    class="rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Modes</option>
                    @foreach ($modes as $m)
                        <option
                            value="{{ $m }}"
                            {{ $selected_mode == $m ? 'selected' : '' }}>
                            {{ $m ?? 'Unspecified' }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="date"
                    name="from"
                    value="{{ request('from') }}"
                    class="w-32 rounded-md border px-2 py-1.5 text-xs shadow-sm">
                <input
                    type="date"
                    name="to"
                    value="{{ request('to') }}"
                    class="w-32 rounded-md border px-2 py-1.5 text-xs shadow-sm">

                <button
                    type="submit"
                    class="rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700">Filter</button>
            </form>

            {{-- <button
								onclick="showModal()"
								class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700"
						>Export CSV</button> --}}
        </div>

        <!-- Enhanced KPI Cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border-l-4 border-indigo-500 bg-white p-4 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Sales</p>
                        <h2 class="text-xl font-bold text-gray-800">₱{{ number_format($totals->total_sales ?? 0, 2) }}</h2>
                    </div>
                    <div class="text-right">
                        @if ($totals->sales_growth > 0)
                            <span class="text-xs text-green-600">↗ {{ $totals->sales_growth }}%</span>
                        @elseif($totals->sales_growth < 0)
                            <span class="text-xs text-red-600">↘ {{ abs($totals->sales_growth) }}%</span>
                        @else
                            <span class="text-xs text-gray-500">— 0%</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Orders</p>
                        <h2 class="text-xl font-bold text-gray-800">{{ number_format($totals->total_orders ?? 0) }}</h2>
                    </div>
                    <div class="text-right">
                        @if ($totals->orders_growth > 0)
                            <span class="text-xs text-green-600">↗ {{ $totals->orders_growth }}%</span>
                        @elseif($totals->orders_growth < 0)
                            <span class="text-xs text-red-600">↘ {{ abs($totals->orders_growth) }}%</span>
                        @else
                            <span class="text-xs text-gray-500">— 0%</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-lg border-l-4 border-purple-500 bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Avg Order Value</p>
                <h2 class="text-xl font-bold text-gray-800">₱{{ number_format($totals->avg_order_value ?? 0, 2) }}</h2>
            </div>

            <div class="rounded-lg border-l-4 border-orange-500 bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Items Sold</p>
                <h2 class="text-xl font-bold text-gray-800">{{ number_format($totals->total_items_sold ?? 0) }}</h2>
            </div>
        </div>

        <!-- Main Charts Grid -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold text-gray-700">Sales Trend by Payment Mode</h3>
                <div
                    id="paymentTrendChart"
                    class="h-64"></div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold text-gray-700">Payment Mode Distribution</h3>
                <div
                    id="paymentByModeChart"
                    class="h-64"></div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold text-gray-700">Sales by Store & Payment Mode</h3>
                <div
                    id="salesByStoreChart"
                    class="h-64"></div>
            </div>

            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold text-gray-700">Hourly Sales by Payment Mode</h3>
                <div
                    id="hourlyDistributionChart"
                    class="h-64"></div>
            </div>
        </div>

        {{-- <!-- Top Products Table -->
				<div class="rounded-lg bg-white p-4 shadow">
						<h3 class="mb-4 font-semibold text-gray-700">Top Products</h3>
						<div class="overflow-x-auto">
								<table class="min-w-full divide-y divide-gray-200">
										<thead class="bg-gray-50">
												<tr>
														<th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
														<th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Sales</th>
														<th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Quantity</th>
												</tr>
										</thead>
										<tbody class="divide-y divide-gray-200 bg-white">
												@forelse($top_products as $product)
														<tr class="hover:bg-gray-50">
																<td class="px-4 py-2 text-sm text-gray-900">{{ $product->product }}</td>
																<td class="px-4 py-2 text-right text-sm font-medium text-gray-900">₱{{ number_format($product->total_sales, 2) }}</td>
																<td class="px-4 py-2 text-right text-sm text-gray-500">{{ number_format($product->total_qty) }}</td>
														</tr>
												@empty
														<tr>
																<td
																		colspan="3"
																		class="px-4 py-8 text-center text-gray-500"
																>No product data available</td>
														</tr>
												@endforelse
										</tbody>
								</table>
						</div>
				</div>

				<!-- Debug Info -->
				@if (config('app.debug'))
						<div class="mt-4 rounded bg-gray-100 p-4">
								<details>
										<summary class="cursor-pointer font-bold">Debug Data</summary>
										<pre class="mt-2 overflow-x-auto text-xs">{{ json_encode(
										    [
										        'totals' => $totals,
										        'by_mode_count' => $by_mode->count(),
										        'by_store_count' => $by_store->count(),
										        'hourly_data_sample' => $hourly_data->take(5),
										        'top_products_count' => $top_products->count(),
										    ],
										    JSON_PRETTY_PRINT,
										) }}</pre>
								</details>
						</div>
				@endif --}}
    </div>

    {{-- <script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Debug data
            console.log('By Mode Data:', @json($by_mode));
            console.log('By Store Data:', @json($by_store));
            console.log('Hourly Data:', @json($hourly_data));

            // 📊 By Mode of Payment Chart
            try {
                const byModeData = @json($by_mode);
                if (Array.isArray(byModeData) && byModeData.length > 0) {
                    const paymentByModeOptions = {
                        chart: {
                            type: 'donut',
                            height: 350
                        },
                        series: byModeData.map(item => parseFloat(item.total_sales) || 0),
                        labels: byModeData.map(item => item.mode_payment || 'Unknown'),
                        legend: {
                            position: 'bottom'
                        },
                        dataLabels: {
                            formatter: function(val) {
                                return val.toFixed(1) + '%';
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
                                                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                return '₱' + total.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    };
                    new ApexCharts(document.querySelector("#paymentByModeChart"), paymentByModeOptions).render();
                } else {
                    document.querySelector("#paymentByModeChart").innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500">No payment mode data available</div>';
                }
            } catch (error) {
                console.error('Error creating payment by mode chart:', error);
            }

            // 📊 Daily Trend Chart with Payment Mode Comparison
            try {
                const salesByDayByMode = @json($sales_by_day_by_mode);
                const paymentModes = @json($payment_modes);

                if (Object.keys(salesByDayByMode).length > 0 && paymentModes.length > 0) {
                    // Create series data for each payment mode
                    const series = paymentModes.map((mode, index) => {
                        const modeData = salesByDayByMode[mode] || [];
                        const colors = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

                        return {
                            name: mode + ' (Sales)',
                            type: 'line',
                            data: modeData.map(item => parseFloat(item.sales) || 0),
                            color: colors[index % colors.length]
                        };
                    });

                    const categories = salesByDayByMode[paymentModes[0]]?.map(item => item.day) || [];

                    const paymentTrendOptions = {
                        chart: {
                            type: 'line',
                            height: 350,
                            toolbar: {
                                show: true
                            }
                        },
                        series: series,
                        xaxis: {
                            categories: categories,
                            labels: {
                                rotate: -45
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Sales (₱)'
                            },
                            labels: {
                                formatter: function(val) {
                                    return '₱' + (val || 0).toLocaleString();
                                }
                            }
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'right'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return '₱' + (val || 0).toLocaleString();
                                }
                            }
                        }
                    };
                    new ApexCharts(document.querySelector("#paymentTrendChart"), paymentTrendOptions).render();
                } else {
                    document.querySelector("#paymentTrendChart").innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500">No sales trend data available</div>';
                }
            } catch (error) {
                console.error('Error creating sales trend chart:', error);
                document.querySelector("#paymentTrendChart").innerHTML = '<div class="flex items-center justify-center h-64 text-red-500">Error loading chart</div>';
            }

            // 📊 Sales by Store with Payment Mode Breakdown
            try {
                const byStoreModeData = @json($by_store_mode);
                const paymentModes = @json($payment_modes);

                if (Object.keys(byStoreModeData).length > 0 && paymentModes.length > 0) {
                    // Get all stores
                    const stores = Object.keys(byStoreModeData);
                    const colors = ['#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#4F46E5'];

                    // Create series data for each payment mode
                    const storeSeries = paymentModes.map((mode, index) => {
                        const data = stores.map(store => {
                            const storeData = byStoreModeData[store];
                            const modeData = storeData ? storeData.find(item => item.mode_payment === mode) : null;
                            return parseFloat(modeData?.total_sales || 0);
                        });

                        return {
                            name: mode,
                            data: data,
                            color: colors[index % colors.length]
                        };
                    });

                    const salesByStoreOptions = {
                        chart: {
                            type: 'bar',
                            height: 350,
                            stacked: true
                        },
                        series: storeSeries,
                        xaxis: {
                            categories: stores,
                            labels: {
                                rotate: -45
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Sales (₱)'
                            },
                            labels: {
                                formatter: function(val) {
                                    return '₱' + (val || 0).toLocaleString();
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
                            position: 'bottom',
                            horizontalAlign: 'right'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return '₱' + (val || 0).toLocaleString();
                                }
                            }
                        }
                    };
                    new ApexCharts(document.querySelector("#salesByStoreChart"), salesByStoreOptions).render();
                } else {
                    document.querySelector("#salesByStoreChart").innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500">No store data available</div>';
                }
            } catch (error) {
                console.error('Error creating sales by store chart:', error);
                document.querySelector("#salesByStoreChart").innerHTML = '<div class="flex items-center justify-center h-64 text-red-500">Error loading chart</div>';
            }

            // 📊 Hourly Distribution with Payment Mode Comparison
            try {
                const hourlyDataByMode = @json($hourly_data_by_mode);
                const paymentModes = @json($payment_modes);

                if (Object.keys(hourlyDataByMode).length > 0 && paymentModes.length > 0) {
                    // Create series data for each payment mode
                    const hourlySeries = paymentModes.map((mode, index) => {
                        const modeData = hourlyDataByMode[mode] || [];
                        const colors = ['#F59E0B', '#10B981', '#4F46E5', '#EF4444', '#8B5CF6'];

                        return {
                            name: mode,
                            data: modeData.map(item => parseFloat(item.sales) || 0),
                            color: colors[index % colors.length]
                        };
                    });

                    const hourCategories = hourlyDataByMode[paymentModes[0]]?.map(item => item.hour) || [];

                    const hourlyOptions = {
                        chart: {
                            type: 'area',
                            height: 350,
                            stacked: false
                        },
                        series: hourlySeries,
                        xaxis: {
                            categories: hourCategories,
                            title: {
                                text: 'Hour of Day'
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Sales (₱)'
                            },
                            labels: {
                                formatter: function(val) {
                                    return '₱' + (val || 0).toLocaleString();
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
                            position: 'bottom',
                            horizontalAlign: 'right'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return '₱' + (val || 0).toLocaleString();
                                }
                            }
                        }
                    };
                    new ApexCharts(document.querySelector("#hourlyDistributionChart"), hourlyOptions).render();
                } else {
                    document.querySelector("#hourlyDistributionChart").innerHTML = '<div class="flex items-center justify-center h-64 text-gray-500">No hourly data available</div>';
                }
            } catch (error) {
                console.error('Error creating hourly distribution chart:', error);
            }
        });
    </script>
@endsection
