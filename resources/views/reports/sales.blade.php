@extends('layouts.app')

@section('title', 'Sales Overview')

@section('content')
		<div class="space-y-6 p-6">
				<!-- Header Section -->
				<div class="mb-8">
						<div class="flex items-center space-x-4">
								<div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
										<svg
												class="h-5 w-5 flex-shrink-0"
												fill="none"
												stroke="white"
												viewBox="0 0 24 24"
										>
												<!-- Axes -->
												<path
														stroke-linecap="round"
														stroke-linejoin="round"
														stroke-width="2"
														d="M4 20h16M4 4v16"
												/>
												<!-- Line Chart -->
												<path
														stroke-linecap="round"
														stroke-linejoin="round"
														stroke-width="2"
														d="M4 14l4-4 4 3 6-6 2 2"
												/>
										</svg>
								</div>
								<div>
										<h1 class="text-3xl font-bold text-gray-900">Sales Overview</h1>
										<p class="mt-1 text-gray-600">Comprehensive view of sales performance and freebie distributions across stores.</p>
								</div>
						</div>
				</div>

				<!-- Filters -->
				<div class="flex items-center justify-between">
						<form
								method="GET"
								action="{{ route('reports.sales') }}"
								class="flex gap-2"
						>
								@php
										$allStoreLocations = [
										    'f2' => 'F2 - Metro Wholesalemart Colon',
										    's10' => 'S10 - Metro Maasin',
										    's17' => 'S17 - Metro Tacloban',
										    's19' => 'S19 - Metro Bay-Bay',
										    'f18' => 'F18 - Metro Alang-Alang',
										    'f19' => 'F19 - Metro Hilongos',
										    's8' => 'S8 - Metro Toledo',
										    'h8' => 'H8 - Super Metro Antipolo',
										    'h9' => 'H9 - Super Metro Carcar',
										    'h10' => 'H10 - Super Metro Bogo',
										];
								@endphp
								<select
										name="store"
										class="rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
								>
										<option value="">All Stores</option>
										@foreach ($stores as $s)
												@php
														$storeCode = strtolower($s->requesting_store);
														$storeName = $allStoreLocations[$storeCode] ?? $s->requesting_store;
												@endphp
												<option
														value="{{ $s->requesting_store }}"
														{{ request('store') == $s->requesting_store ? 'selected' : '' }}
												>
														{{ $storeName }}
												</option>
										@endforeach
								</select>

								<input
										type="date"
										name="from"
										value="{{ request('from') }}"
										class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
								>
								<input
										type="date"
										name="to"
										value="{{ request('to') }}"
										class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
								>
								<button
										type="submit"
										class="rounded bg-indigo-600 px-3 py-1 text-white hover:bg-indigo-700"
								>
										Filter
								</button>
								<!-- Export Button (anywhere) -->

						</form>
						<button
								onclick="showModal()"
								class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700"
						>
								Export CSV
						</button>
				</div>

				<!-- KPI Cards -->
				<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
						<!-- Sales KPIs -->
						<div class="rounded-lg border-l-4 border-indigo-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Total Sales + Freebies Amount</p>
								<h2 class="text-xl font-bold text-gray-800">
										₱{{ number_format(($totals->total_sales ?? 0) + ($freebie_totals->total_freebies_value ?? 0), 2) }}
								</h2>
						</div>
						<div class="rounded-lg border-l-4 border-blue-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Total Sales</p>
								<h2 class="text-xl font-bold text-gray-800">
										₱{{ number_format($totals->total_sales ?? 0, 2) }}
								</h2>
						</div>

						<div class="rounded-lg border-l-4 border-red-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Freebies Amount</p>
								<h2 class="text-xl font-bold text-gray-800">
										₱{{ number_format($freebie_totals->total_freebies_value ?? 0, 2) }}
								</h2>
						</div>

						<div class="rounded-lg border-l-4 border-green-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Total Orders</p>
								<h2 class="text-xl font-bold text-gray-800">
										{{ $totals->total_orders ?? 0 }}
								</h2>
						</div>
						{{-- <div class="rounded-lg border-l-4 border-purple-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Avg Item Amount</p>
								<h2 class="text-xl font-bold text-gray-800">
										₱{{ number_format($totals->avg_item_amount ?? 0, 2) }}
								</h2>
						</div> --}}

						<!-- Freebies KPIs -->
						{{-- <div class="rounded-lg border-l-4 border-orange-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Freebies Quantity</p>
								<h2 class="text-xl font-bold text-gray-800">
										{{ number_format($freebie_totals->total_freebies_qty ?? 0) }}
								</h2>
						</div>

						<div class="rounded-lg border-l-4 border-teal-500 bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Orders w/ Freebies</p>
								<h2 class="text-xl font-bold text-gray-800">
										{{ $freebie_totals->orders_with_freebies ?? 0 }}
								</h2>
						</div> --}}
				</div>

				<!-- Charts Row 1: Time Series -->
				<div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
						<!-- Combined Sales & Freebies Over Time -->
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Sales Per Store Over Time</h3>
								<div
										id="salesByStoreOverTimeChart"
										class="h-64"
								></div>
						</div>
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Daily Performance Metrics</h3>
								<div
										id="performanceChart"
										class="h-64"
								></div>
						</div>
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Sales & Freebies by Store</h3>
								<div
										id="storeComparisonChart"
										class="h-64"
								></div>
						</div>
				</div>


				<!-- Charts Row 2: Product Analysis -->
				{{-- <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Top Performing Stores</h3>
								<div
										id="topStoresChart"
										class="h-64"
								></div>
						</div>


				</div> --}}

				<!-- Tables Row -->
				<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
						<!-- Top Products Table -->
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Top Selling Products</h3>
								<div class="overflow-x-auto">
										<table class="min-w-full border text-sm">
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
																		<td class="px-3 py-2 text-right">
																				{{ number_format($product->total_qty ?? 0) }}
																		</td>
																		<td class="px-3 py-2 text-right">
																				₱{{ number_format($product->total_sales ?? 0, 2) }}
																		</td>

																</tr>
														@empty
																<tr>
																		<td
																				colspan="4"
																				class="px-3 py-2 text-center text-gray-500"
																		>No products found</td>
																</tr>
														@endforelse
												</tbody>
										</table>
								</div>
						</div>

						<!-- Top Freebies Table -->
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Top Freebie Products</h3>
								<div class="overflow-x-auto">
										<table class="min-w-full border text-sm">
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
																		<td class="px-3 py-2 text-right">
																				{{ number_format($freebie->total_qty ?? 0) }}
																		</td>
																		<td class="px-3 py-2 text-right">
																				₱{{ number_format($freebie->total_value ?? 0, 2) }}
																		</td>

																</tr>
														@empty
																<tr>
																		<td
																				colspan="4"
																				class="px-3 py-2 text-center text-gray-500"
																		>No freebies found</td>
																</tr>
														@endforelse
												</tbody>
										</table>
								</div>
						</div>
				</div>
		</div>

		<!-- Export Modal -->
		<div
				id="exportModal"
				class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black bg-opacity-50"
		>
				<div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
						<h2 class="mb-4 text-lg font-semibold text-gray-800">Export Settings</h2>

						<form
								method="GET"
								action="{{ route('reports.sales.export') }}"
						>
								<!-- Date Range -->
								<div class="mb-3">
										<label class="block text-sm font-medium text-gray-700">Date Range</label>
										<select
												id="dateRangeType"
												name="date_range_type"
												class="w-full rounded border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
										>
												<option value="this_year">This Year</option>
												<option
														value="this_month"
														selected
												>This Month</option>
												<option value="today">Today</option>
												<option value="custom">Custom</option>
										</select>

										<!-- Custom Date Fields -->
										<div
												id="customDateFields"
												class="mt-2 hidden"
										>
												<div class="flex gap-2">
														<input
																type="date"
																name="from"
																class="w-1/2 rounded border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
														>
														<input
																type="date"
																name="to"
																class="w-1/2 rounded border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
														>
												</div>
												<p class="mt-1 text-xs text-gray-500">Choose your custom date range.</p>
										</div>
								</div>


								<script>
										document.addEventListener("DOMContentLoaded", () => {
												const select = document.getElementById("dateRangeType");
												const customFields = document.getElementById("customDateFields");

												function toggleCustomFields() {
														customFields.classList.toggle("hidden", select.value !== "custom");
												}

												select.addEventListener("change", toggleCustomFields);
												toggleCustomFields(); // init
										});
								</script>

								<!-- Store Dropdown -->
								<div class="mb-3">
										<label class="block text-sm font-medium text-gray-700">Store</label>
										<select
												name="store"
												class="w-full rounded border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
										>
												<option value="">All Stores</option>
												@php
														$stores = [
														    'f2' => 'F2 - Metro Wholesalemart Colon',
														    's10' => 'S10 - Metro Maasin',
														    's17' => 'S17 - Metro Tacloban',
														    's19' => 'S19 - Metro Bay-Bay',
														    'f18' => 'F18 - Metro Alang-Alang',
														    'f19' => 'F19 - Metro Hilongos',
														    's8' => 'S8 - Metro Toledo',
														    'h8' => 'H8 - Super Metro Antipolo',
														    'h9' => 'H9 - Super Metro Carcar',
														    'h10' => 'H10 - Super Metro Bogo',
														];
												@endphp
												@foreach ($stores as $key => $label)
														<option value="{{ $key }}">{{ $label }}</option>
												@endforeach
										</select>
								</div>

								<!-- Channel Dropdown -->
								<div class="mb-3">
										<label class="block text-sm font-medium text-gray-700">Channel Order</label>
										<select
												name="channel_order"
												class="w-full rounded border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
										>
												<option value="">All Channels</option>
												@foreach (['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $channel)
														<option value="{{ $channel }}">{{ $channel }}</option>
												@endforeach
										</select>
								</div>

								<div class="mb-3">
										<label class="block text-sm font-medium text-gray-700">Columns</label>
										<div class="space-y-3 text-sm">

												{{-- Always included (disabled but still looks like toggle) --}}
												<div class="flex items-center justify-between">
														<span>Order # </span>
														{{-- <label class="relative inline-flex cursor-not-allowed items-center">
																<input
																		type="checkbox"
																		checked
																		disabled
																		class="sr-only"
																>
																<div class="h-6 w-11 rounded-full bg-green-500 opacity-60"></div>
																<div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow"></div>
														</label> --}}
														<input
																type="hidden"
																name="columns[]"
																value="sof_id"
														>
												</div>

												<div class="flex items-center justify-between">
														<span>Customer </span>
														{{-- <label class="relative inline-flex cursor-not-allowed items-center">
																<input
																		type="checkbox"
																		checked
																		disabled
																		class="sr-only"
																>
																<div class="h-6 w-11 rounded-full bg-green-500 opacity-60"></div>
																<div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow"></div>
														</label> --}}
														<input
																type="hidden"
																name="columns[]"
																value="customer_name"
														>
												</div>

												{{-- Toggleable columns --}}
												@foreach ([
										'channel_order' => 'Channel',
										'time_order' => 'Order Date',
										'requesting_store' => 'Store',
										'grand_total' => 'Grand Total',
										'total_payable' => 'Total Payable',
										'total_freebies' => 'Total Freebies',
						] as $key => $label)
														<div class="flex items-center justify-between">
																<span>{{ $label }}</span>
																<label class="relative inline-flex cursor-pointer items-center">
																		<input
																				type="checkbox"
																				name="columns[]"
																				value="{{ $key }}"
																				checked
																				class="peer sr-only"
																		>
																		<div class="peer h-6 w-11 rounded-full bg-gray-200 transition-colors peer-checked:bg-blue-500"></div>
																		<div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></div>
																</label>
														</div>
												@endforeach
										</div>
								</div>



								<!-- Actions -->
								<div class="mt-4 flex justify-end gap-2">
										<button
												type="button"
												onclick="hideModal()"
												class="rounded bg-gray-300 px-3 py-1 hover:bg-gray-400"
										>
												Cancel
										</button>
										<button
												type="submit"
												class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700"
										>
												Export
										</button>
								</div>
						</form>
				</div>
		</div>

		<script>
				function showModal() {
						document.getElementById('exportModal').classList.remove('hidden');
				}

				function hideModal() {
						document.getElementById('exportModal').classList.add('hidden');
				}
		</script>
		<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
		<script>
				var storeSalesOptions = {
						chart: {
								type: 'line',
								height: 400
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
				};

				new ApexCharts(document.querySelector("#salesByStoreOverTimeChart"), storeSalesOptions).render();

				console.log("By store over time:", @json($by_store_over_time));

				// 📊 2. Combined Sales & Freebies by Store (Overlapping Vertical Bar)
				var storeNames = @json($by_store->pluck('store_name'));
				var salesData = @json($by_store->pluck('total_sales')->map(fn($v) => (float) $v));

				// Match freebies data to sales stores (fill missing with 0)
				var freebiesData = storeNames.map(function(storeName) {
						var freebieStore = @json($freebies_by_store->pluck('store_name')).indexOf(storeName);
						return freebieStore >= 0 ? @json($freebies_by_store->pluck('total_amount')->map(fn($v) => (float) $v))[freebieStore] : 0;
				});

				var storeComparisonOptions = {
						chart: {
								type: 'bar',
								height: 400,
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
								}
						],
						plotOptions: {
								bar: {
										horizontal: false,
										columnWidth: '70%',
										endingShape: 'rounded',
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
												fontSize: '11px'
										}
								}
						},
						yaxis: {
								title: {
										text: 'Amount (₱)'
								},
								labels: {
										formatter: function(val) {
												return '₱' + (val / 1000).toFixed(0) + 'K';
										}
								}
						},
						colors: ['#4F46E5', '#F59E0B'],
						fill: {
								opacity: [0.6, 1], // Sales bar semi-transparent, freebies bar solid
								type: ['solid', 'solid']
						},
						tooltip: {
								shared: true,
								intersect: false,
								y: {
										formatter: function(val, opts) {
												if (opts.seriesIndex === 1) {
														// Calculate percentage for freebies
														var totalSales = salesData[opts.dataPointIndex];
														var percentage = totalSales > 0 ? ((val / totalSales) * 100).toFixed(1) : 0;
														return '₱' + val.toLocaleString() + ' (' + percentage + '% of total sales)';
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
						},
						states: {
								hover: {
										filter: {
												type: 'lighten',
												value: 0.1
										}
								}
						},
						// Make bars overlap by using same position
						chart: {
								type: 'bar',
								height: 400,
								toolbar: {
										show: true
								},
								animations: {
										enabled: true,
										easing: 'easeinout',
										speed: 800
								}
						}
				};

				// Custom CSS approach for true overlapping
				const chartContainer = document.querySelector("#storeComparisonChart");
				chartContainer.style.position = 'relative';

				// Add custom CSS for overlapping bars
				const style = document.createElement('style');
				style.textContent = `
            #storeComparisonChart .apexcharts-series[seriesName="Freebies Value"] {
                mix-blend-mode: multiply;
            }
            #storeComparisonChart .apexcharts-series[seriesName="Total Sales"] rect {
                opacity: 0.7 !important;
            }
            #storeComparisonChart .apexcharts-series[seriesName="Freebies Value"] rect {
                opacity: 1 !important;
            }
        `;
				document.head.appendChild(style);

				new ApexCharts(document.querySelector("#storeComparisonChart"), storeComparisonOptions).render();

				// 📊 3. Top Performing Stores (Horizontal Bar with Rankings)
				var storeNames = @json($by_store->take(8)->pluck('store_name'));
				var storeSales = @json(
						$by_store->take(8)->pluck('total_sales')->map(function ($v) {
										return (float) $v;
								}));

				var storePerformanceData = storeNames.map(function(name, index) {
						return {
								store: name,
								sales: storeSales[index],
								rank: index + 1
						};
				});

				var topStoresOptions = {
						chart: {
								type: 'bar',
								height: 400,
								toolbar: {
										show: false
								}
						},
						plotOptions: {
								bar: {
										horizontal: true,
										barHeight: '70%',
										distributed: true
								}
						},
						series: [{
								name: 'Sales',
								data: storePerformanceData.map(function(item) {
										return {
												x: item.store,
												y: item.sales,
												fillColor: item.rank <= 3 ? '#10B981' : '#6B7280' // Top 3 in green, others in gray
										};
								})
						}],
						dataLabels: {
								enabled: true,
								textAnchor: 'start',
								style: {
										colors: ['#fff'],
										fontWeight: 'bold'
								},
								formatter: function(val, opts) {
										return '₱' + val.toLocaleString();
								},
								offsetX: 10
						},
						colors: ['#10B981'],
						xaxis: {
								type: 'numeric',
								labels: {
										formatter: function(val) {
												return '₱' + (val / 1000).toFixed(0) + 'K';
										}
								}
						},
						yaxis: {
								labels: {
										style: {
												fontSize: '11px'
										}
								}
						},
						tooltip: {
								y: {
										formatter: function(val) {
												return '₱' + val.toLocaleString();
										}
								}
						},
						legend: {
								show: false
						},
						grid: {
								borderColor: '#f1f5f9',
								strokeDashArray: 3
						}
				};
				// new ApexCharts(document.querySelector("#topStoresChart"), topStoresOptions).render();

				// 📊 4. Daily Performance Metrics (Mixed Chart)
				var performanceOptions = {
						chart: {
								type: 'line',
								height: 400,
								toolbar: {
										show: true
								}
						},
						series: [{
										name: 'Orders Count',
										type: 'column',
										data: @json(
												$sales_by_day->map(function ($day) use ($freebies_by_day) {
														$freebieDay = $freebies_by_day->where('day', $day['day'])->first();
														return ($freebieDay['qty'] ?? 0) > 0 ? 1 : 0; // Simplified: 1 if freebies given, 0 otherwise
												}))
								},
								{
										name: 'Sales Trend',
										type: 'line',
										data: @json($sales_by_day->pluck('sales')->map(fn($v) => $v / 1000)) // Scale down for better visualization
								}
						],
						xaxis: {
								categories: @json($sales_by_day->pluck('day')),
								labels: {
										rotate: -45
								}
						},
						yaxis: [{
										title: {
												text: "Orders with Freebies"
										}
								},
								{
										opposite: true,
										title: {
												text: "Sales (K)"
										},
										labels: {
												formatter: val => "₱" + val.toFixed(0) + "K"
										}
								}
						],
						colors: ['#10B981', '#6366F1'],
						stroke: {
								width: [0, 2]
						},
						tooltip: {
								y: {
										formatter: function(val, opts) {
												if (opts.seriesIndex === 0) return val.toString();
												return "₱" + (val * 1000).toLocaleString();
										}
								}
						}
				};
				new ApexCharts(document.querySelector("#performanceChart"), performanceOptions).render();
		</script>

@endsection
