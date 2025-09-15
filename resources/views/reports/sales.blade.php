@extends('layouts.app')

@section('title', 'Sales Report Dashboard')

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
										<h1 class="text-3xl font-bold text-gray-900">Sales Report</h1>
										<p class="mt-1 text-gray-600">View and analyze sales performance across stores and products.</p>

								</div>
						</div>
				</div>
				<div class="flex items-center justify-between">
						{{-- <h1 class="text-2xl font-bold text-gray-800">Sales Report Dashboard</h1> --}}
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
						</form>
				</div>

				<!-- KPI Cards -->
				<div class="grid grid-cols-1 gap-4 md:grid-cols-4">
						<div class="rounded-lg bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Total Sales</p>
								<h2 class="text-xl font-bold text-gray-800">
										₱{{ number_format($totals->total_sales ?? 0, 2) }}
								</h2>
						</div>
						<div class="rounded-lg bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Total Orders</p>
								<h2 class="text-xl font-bold text-gray-800">
										{{ $totals->total_orders ?? 0 }}
								</h2>
						</div>
						<div class="rounded-lg bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Average Item Amount</p>
								<h2 class="text-xl font-bold text-gray-800">
										₱{{ number_format($totals->avg_item_amount ?? 0, 2) }}
								</h2>
						</div>
						<div class="rounded-lg bg-white p-4 shadow">
								<p class="text-sm text-gray-500">Top Product</p>
								<h2 class="text-xl font-bold text-gray-800">
										{{ $top_products->first()->item_description ?? 'N/A' }}
								</h2>
						</div>
				</div>

				<!-- Charts -->
				<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Sales Over Time</h3>
								<div
										id="salesChart"
										class="h-64"
								></div> <!-- ✅ div instead of canvas -->
						</div>
						<div class="rounded-lg bg-white p-4 shadow">
								<h3 class="mb-3 font-semibold text-gray-700">Sales by Store</h3>
								<div
										id="storeChart"
										class="h-64"
								></div>
						</div>
				</div>

				<!-- Top Products Table -->
				<div class="rounded-lg bg-white p-4 shadow">
						<h3 class="mb-3 font-semibold text-gray-700">Top Products</h3>
						<div class="overflow-x-auto">
								<table class="min-w-full border text-sm">
										<thead class="bg-gray-100 text-gray-700">
												<tr>
														<th class="border px-3 py-2 text-left">SKU</th>
														<th class="border px-3 py-2 text-left">Description</th>
														<th class="border px-3 py-2 text-right">Total Qty</th>
														<th class="border px-3 py-2 text-right">Total Sales</th>
												</tr>
										</thead>
										<tbody>
												@forelse ($top_products as $product)
														<tr class="border-b hover:bg-gray-50">
																<td class="px-3 py-2">{{ $product->sku }}</td>
																<td class="px-3 py-2">{{ $product->item_description }}</td>
																<td class="px-3 py-2 text-right">{{ $product->total_qty }}</td>
																<td class="px-3 py-2 text-right">₱{{ number_format($product->total_sales, 2) }}</td>
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
		</div>

		<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

		<script>
				// 📈 Sales Over Time
				var salesSeries = @json($sales_by_day->pluck('sales'));
				var salesCategories = @json($sales_by_day->pluck('day'));

				if (!salesSeries || !salesSeries.length) {
						salesSeries = [0];
						salesCategories = ['No Data'];
				}

				var salesOptions = {
						chart: {
								type: 'area',
								height: 350,
								toolbar: {
										show: false
								}
						},
						series: [{
								name: 'Sales',
								data: salesSeries
						}],
						xaxis: {
								categories: salesCategories,
								labels: {
										rotate: -45
								}
						},
						stroke: {
								curve: 'smooth',
								width: 3
						},
						fill: {
								type: 'gradient',
								gradient: {
										shadeIntensity: 1,
										opacityFrom: 0.5,
										opacityTo: 0,
										stops: [0, 90, 100]
								}
						},
						colors: ['#4F46E5'],
						tooltip: {
								y: {
										formatter: val => '₱' + val.toLocaleString()
								}
						}
				};
				new ApexCharts(document.querySelector("#salesChart"), salesOptions).render();

				// 🏬 Sales by Store
				var storeSeries = @json($by_store->pluck('total_sales')->map(fn($v) => (float) $v));
				var storeLabels = @json($by_store->pluck('store_name'));

				if (!storeSeries.length) {
						storeSeries = [0];
						storeLabels = ['No Data'];
				}

				var storeOptions = {
						chart: {
								type: 'donut',
								height: 350
						},
						series: storeSeries,
						labels: storeLabels,
						colors: ['#6366F1', '#F59E0B', '#10B981', '#EF4444', '#3B82F6'],
						legend: {
								position: 'bottom',
								labels: {
										colors: '#374151'
								}
						},
						dataLabels: {
								enabled: true,
								formatter: function(val, opts) {
										let value = opts.w.globals.series[opts.seriesIndex] || 0;
										return '₱' + value.toLocaleString();
								}
						},
						tooltip: {
								y: {
										formatter: val => '₱' + val.toLocaleString()
								}
						}
				};
				new ApexCharts(document.querySelector("#storeChart"), storeOptions).render();
		</script>

@endsection
