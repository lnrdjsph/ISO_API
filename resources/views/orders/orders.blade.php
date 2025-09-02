@extends('layouts.app')

@section('content')
		<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
				<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

						<!-- Header Section -->
						<div class="mb-8">
								<div class="flex items-center space-x-4">
										<div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
												<svg
														xmlns="http://www.w3.org/2000/svg"
														class="h-8 w-8 text-white"
														fill="none"
														viewBox="0 0 24 24"
														stroke="currentColor"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"
														/>
												</svg>
										</div>
										<div>
												<h1 class="text-3xl font-bold text-gray-900">Sales Order List</h1>
												<p class="mt-1 text-gray-600">List of all B2B sales orders submitted for processing and fulfillment.</p>
										</div>
								</div>
						</div><!-- Modern Filter Bar -->
						<form
								method="GET"
								action="{{ route('orders.index') }}"
								class="mb-6 flex flex-wrap items-center gap-4"
						>

								<!-- Search -->
								<div class="relative">
										<input
												type="text"
												name="search"
												value="{{ request('search') }}"
												placeholder="Search orders..."
												class="w-56 rounded-lg border border-gray-300 py-2 pl-10 pr-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
										>
										<svg
												xmlns="http://www.w3.org/2000/svg"
												class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
												fill="none"
												viewBox="0 0 24 24"
												stroke="currentColor"
										>
												<path
														stroke-linecap="round"
														stroke-linejoin="round"
														stroke-width="2"
														d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z"
												/>
										</svg>
								</div>

								<!-- Channel -->
								<select
										name="channel"
										class="w-44 rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
								>
										<option value="">All Channels</option>
										@foreach ($channels as $channel)
												<option
														value="{{ $channel }}"
														{{ request('channel') == $channel ? 'selected' : '' }}
												>
														{{ $channel }}
												</option>
										@endforeach
								</select>

								<!-- Status -->
								<select
										name="status"
										class="w-44 rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
								>
										<option value="">All Statuses</option>
										@foreach ($statuses as $status)
												<option
														value="{{ $status }}"
														{{ request('status') == $status ? 'selected' : '' }}
												>
														{{ ucfirst(strtolower($status)) }}
												</option>
										@endforeach
								</select>

								<!-- Date Range -->
								<input
										type="date"
										name="start_date"
										value="{{ request('start_date') }}"
										class="w-40 rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
								>
								<span class="text-gray-500">to</span>
								<input
										type="date"
										name="end_date"
										value="{{ request('end_date') }}"
										class="w-40 rounded-lg border border-gray-300 px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
								>

								<!-- Apply -->
								<button
										type="submit"
										class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-md transition hover:bg-indigo-700"
								>
										Apply
								</button>

								<!-- Reset -->
								<a
										href="{{ route('orders.index') }}"
										class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-200"
								>
										Reset
								</a>
						</form>

						<div class="space-y-6 rounded-xl bg-white shadow-lg">
								<!-- Search Bar -->


								<!-- Table -->
								<div class="overflow-x-auto rounded-xl">
										<table class="min-w-full divide-y divide-gray-200 rounded-xl text-sm">
												<thead class="bg-gray-50 text-left">
														<tr>
																<th class="px-4 py-3 font-medium text-gray-700">Order #</th>
																<th class="px-4 py-3 font-medium text-gray-700">Customer</th>
																<th class="px-4 py-3 font-medium text-gray-700">Channel</th>
																<th class="px-4 py-3 font-medium text-gray-700">Order Date</th>
																<th class="px-4 py-3 font-medium text-gray-700">Delivery Date</th>
																<th class="px-4 py-3 font-medium text-gray-700">Status</th>
																<th class="px-4 py-3 text-center font-medium text-gray-700">Actions</th>
														</tr>
												</thead>
												<tbody class="divide-y divide-gray-100 bg-white">
														@forelse($orders as $order)
																<tr class="animate-fade-in transition-all duration-200 hover:bg-indigo-100/60">
																		<td class="whitespace-nowrap px-4 py-3">{{ $order->sof_id }}</td>
																		<td class="whitespace-nowrap px-4 py-3">{{ $order->customer_name }}</td>
																		<td class="whitespace-nowrap px-4 py-3">{{ $order->channel_order }}</td>
																		<td class="whitespace-nowrap px-4 py-3">
																				{{ \Carbon\Carbon::parse($order->time_order)->format('Y-m-d H:i') }}</td>
																		<td class="whitespace-nowrap px-4 py-3">
																				{{ \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') }}</td>
																		<td class="whitespace-nowrap px-4 py-3">
																				@php
																						$status = ucwords(strtolower($order->order_status ?? 'Pending'));
																						$statusClass = match ($status) {
																						    'Delivered' => 'bg-green-100 text-green-800',
																						    'Archived' => 'bg-gray-200 text-gray-700',
																						    'Cancelled' => 'bg-red-200 text-red-700',
																						    default => 'bg-yellow-100 text-yellow-800',
																						};
																				@endphp

																				<span class="{{ $statusClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
																						{{ $status }}
																				</span>

																		</td>
																		<td class="px-4 py-3 text-center">
																				<a
																						href="{{ route('orders.show', $order->id) }}"
																						class="inline-block font-medium text-indigo-600 hover:text-indigo-800"
																				>
																						View
																				</a>
																		</td>
																</tr>
														@empty
																<tr>
																		<td
																				colspan="7"
																				class="px-4 py-4 text-center text-gray-500"
																		>No orders found.</td>
																</tr>
														@endforelse
												</tbody>
										</table>
								</div>

								<!-- Pagination -->
								<div class="flex items-center justify-between p-4">
										<!-- Rows per page -->
										<form
												method="GET"
												action="{{ route('orders.index') }}"
												class="flex items-center space-x-2"
										>
												@foreach (request()->except('per_page', 'page') as $key => $value)
														<input
																type="hidden"
																name="{{ $key }}"
																value="{{ $value }}"
														>
												@endforeach

												<label
														for="per_page"
														class="text-sm text-gray-600"
												>Rows per page:</label>
												<select
														name="per_page"
														id="per_page"
														onchange="this.form.submit()"
														class="rounded border-0 px-8 py-1 text-sm"
												>
														<option
																value="10"
																{{ $perPage == 10 ? 'selected' : '' }}
														>10</option>
														<option
																value="25"
																{{ $perPage == 25 ? 'selected' : '' }}
														>25</option>
														<option
																value="50"
																{{ $perPage == 50 ? 'selected' : '' }}
														>50</option>
														<option
																value="100"
																{{ $perPage == 100 ? 'selected' : '' }}
														>100</option>
												</select>
										</form>

										<!-- Pagination -->
										<div>
												{{ $orders->links('pagination::tailwind') }}
										</div>
								</div>


						</div>

				</div>
		</div>
@endsection
