@extends('layouts.app')

@section('content')
		<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
				<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
						<div class="mb-8">
								<div class="flex items-center space-x-4">
										<div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
												<svg
														xmlns="http://www.w3.org/2000/svg"
														fill="none"
														viewBox="0 0 24 24"
														stroke="white"
														stroke-width="2"
														class="h-7 w-7 flex-shrink-0"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z"
														/>
												</svg>
										</div>
										<div>
												<h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
												<p class="mt-1 text-gray-600">Welcome! Choose a section below.

												</p>
										</div>
								</div>
						</div>

						<!-- Cards Grid -->
						<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

								<!-- Orders Card -->
								<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
										<div class="mb-3 flex items-center justify-between">
												<h2 class="text-2xl font-semibold text-gray-800">Orders</h2>
												<div class="rounded-full bg-blue-100 p-2 text-blue-600">
														<svg
																class="h-5 w-5 flex-shrink-0"
																fill="none"
																stroke="currentColor"
																viewBox="0 0 24 24"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		stroke-width="2"
																		d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"
																/>
														</svg>
												</div>
										</div>
										<ul class="space-y-2 text-sm text-gray-600">
												<li class="flex items-center gap-2">
														<a
																href="{{ route('orders.index') }}"
																class="pe-2 text-xl decoration-2 hover:text-blue-600 hover:underline"
														>
																View Sales Order List
														</a>

														<div class="flex gap-2">
																@if ($pendingCount > 0)
																		<div class="group relative">
																				<span class="flex items-center rounded-full bg-yellow-500 px-3 py-0.5 text-xs font-semibold text-white shadow">
																						{{ $pendingCount }}
																				</span>
																				<div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
																						Pending Orders
																				</div>
																		</div>
																@endif

																@if ($cancelledCount > 0)
																		<div class="group relative">
																				<span class="flex items-center rounded-full bg-red-500 px-3 py-0.5 text-xs font-semibold text-white shadow">
																						{{ $cancelledCount }}
																				</span>
																				<div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
																						Cancelled Orders
																				</div>
																		</div>
																@endif

																@if ($completedCount > 0)
																		<div class="group relative">
																				<span class="flex items-center rounded-full bg-green-600 px-3 py-0.5 text-xs font-semibold text-white shadow">
																						{{ $completedCount }}
																				</span>
																				<div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
																						Completed Orders
																				</div>
																		</div>
																@endif
														</div>
												</li>



												<li>
														<a
																href="#"
																class="text-xl decoration-2 hover:text-blue-600 hover:underline"
														>
																View Request Order List
														</a>
												</li>
										</ul>

								</div>

								<!-- Forms Card -->
								<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
										<div class="mb-3 flex items-center justify-between">
												<h2 class="text-2xl font-semibold text-gray-800">Forms</h2>
												<div class="rounded-full bg-indigo-100 p-2 text-indigo-600">
														<svg
																class="h-5 w-5 flex-shrink-0"
																fill="none"
																stroke="currentColor"
																viewBox="0 0 24 24"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		stroke-width="2"
																		d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
																/>
														</svg>
												</div>
										</div>
										<ul class="space-y-2 text-sm text-gray-600">
												<li><a
																href="{{ route('forms.sof') }}"
																class="text-xl decoration-2 hover:text-indigo-600 hover:underline"
														>Sales Order Form</a></li>
												<li><a
																href="{{ route('forms.rof') }}"
																class="text-xl decoration-2 hover:text-indigo-600 hover:underline"
														>Request Order Form</a></li>
												{{-- <li><a href="{{ route('forms.feedback') }}" class="hover:text-indigo-600 hover:underline decoration-2 text-xl">Feedback Form</a></li> --}}
										</ul>
								</div>

								<!-- Products Card -->
								<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
										<div class="mb-3 flex items-center justify-between">
												<h2 class="text-2xl font-semibold text-gray-800">Products</h2>
												<div class="rounded-full bg-green-100 p-2 text-green-600">
														<svg
																class="h-5 w-5 flex-shrink-0"
																fill="none"
																stroke="currentColor"
																viewBox="0 0 24 24"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		stroke-width="2"
																		d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
																/>
														</svg>
												</div>
										</div>
										<ul class="space-y-2 text-sm text-gray-600">
												<li><a
																href="{{ route('products.index') }}"
																class="text-xl decoration-2 hover:text-green-600 hover:underline"
														>All Products</a></li>
												<li><a
																href="{{ route('products.create') }}"
																class="text-xl decoration-2 hover:text-green-600 hover:underline"
														>Add New Product</a></li>
												<li><a
																href="{{ route('products.import.show') }}"
																class="text-xl decoration-2 hover:text-green-600 hover:underline"
														>Import Products (CSV)</a></li>
										</ul>
								</div>

						</div>
				</div>
		</div>
@endsection
