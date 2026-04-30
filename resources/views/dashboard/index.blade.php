@extends('layouts.app')

@section('content')
    <div class="">
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
                            class="h-7 w-7 flex-shrink-0">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z" />
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
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-1 lg:grid-cols-2">

                <!-- Orders Card -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-2xl font-semibold text-gray-800">Orders</h2>
                        <div class="rounded-full bg-blue-100 p-2 text-blue-600">
                            <svg
                                class="h-5 w-5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            @if (auth()->user()->role === 'manager')
                                {{-- Manager View --}}
                                <a
                                    href="{{ route('orders.index') }}"
                                    class="pe-2 text-xl decoration-2 hover:text-blue-600 hover:underline">
                                    View Sales Orders for Approval
                                </a>

                                <div class="flex gap-2">
                                    @if ($forApprovalCount > 0)
                                        <div class="group relative">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-500 text-xs font-semibold text-white shadow">
                                                {{ $forApprovalCount }}
                                            </span>
                                            <div
                                                class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                                For Approval
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            @else
                                {{-- Normal User View --}}
                                <a
                                    href="{{ route('orders.index') }}"
                                    class="pe-2 text-xl decoration-2 hover:text-blue-600 hover:underline">
                                    View Sales Order List
                                </a>

                                <div class="flex gap-2">
                                    @if ($newOrderCount > 0)
                                        <div class="group relative">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-xs font-semibold text-white shadow">
                                                {{ $newOrderCount }}
                                            </span>
                                            <div
                                                class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                                New Orders
                                            </div>
                                        </div>
                                    @endif

                                    @if ($pendingCount > 0)
                                        <div class="group relative">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-500 text-xs font-semibold text-white shadow">
                                                {{ $pendingCount }}
                                            </span>
                                            <div
                                                class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                                Pending Orders
                                            </div>
                                        </div>
                                    @endif
                                    @if ($forApprovalCount > 0)
                                        <div class="group relative">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-500 text-xs font-semibold text-white shadow">
                                                {{ $forApprovalCount }}
                                            </span>
                                            <div
                                                class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
                                                For Approval Orders
                                            </div>
                                        </div>
                                    @endif
                                    {{-- @if ($approvedCount > 0)
																				<div class="group relative">
																						<span class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-500 text-xs font-semibold text-white shadow">
																								{{ $approvedCount }}
																						</span>
																						<div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
																								Approved Orders
																						</div>
																				</div>
																		@endif --}}

                                    {{-- @if ($cancelledCount > 0)
																				<div class="group relative">
																						<span class="flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs font-semibold text-white shadow">
																								{{ $cancelledCount }}
																						</span>
																						<div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
																								Cancelled Orders
																						</div>
																				</div>
																		@endif --}}

                                    {{-- @if ($completedCount > 0)
																				<div class="group relative">
																						<span class="flex items-center rounded-full bg-green-600 px-3 py-0.5 text-xs font-semibold text-white shadow">
																								{{ $completedCount }}
																						</span>
																						<div class="absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100">
																								Completed Orders
																						</div>
																				</div>
																		@endif --}}
                                </div>
                            @endif
                        </li>


                        {{-- View Request Order List & Generate Freebies Form removed:
                             not present in sidebar navigation --}}
                    </ul>

                </div>


                {{-- ══ Forms: same gate as sidebar (!manager) ══ --}}
                @if (!in_array(auth()->user()->role, ['manager']))
                    <!-- Forms Card -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-gray-800">Forms</h2>
                            <div class="rounded-full bg-indigo-100 p-2 text-indigo-600">
                                <svg
                                    class="h-5 w-5 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a
                                    href="{{ route('forms.sof') }}"
                                    class="text-xl decoration-2 hover:text-indigo-600 hover:underline">Sales Order Form</a></li>
                            {{-- Request Order Form commented out — matches sidebar --}}
                            {{-- <li><a href="{{ route('forms.rof') }}" class="text-xl decoration-2 hover:text-indigo-600 hover:underline">Request Order Form</a></li> --}}
                            {{-- <li><a href="{{ route('forms.feedback') }}" class="hover:text-indigo-600 hover:underline decoration-2 text-xl">Feedback Form</a></li> --}}
                        </ul>
                    </div>
                @endif

                {{-- ══ Products: same gate as sidebar (super admin, warehouse personnel, warehouse admin, store personnel) ══ --}}
                @if (in_array(auth()->user()->role, ['super admin', 'warehouse personnel', 'warehouse admin', 'store personnel']))
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-gray-800">Products</h2>
                            <div class="rounded-full bg-green-100 p-2 text-green-600">
                                <svg
                                    class="h-5 w-5 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a
                                    href="{{ route('products.index') }}"
                                    class="text-xl decoration-2 hover:text-green-600 hover:underline">All Products</a></li>
                            {{-- Add New & Import: same gate as sidebar (!store personnel) --}}
                            @if (!in_array(auth()->user()->role, ['store personnel']))
                                <li><a
                                        href="{{ route('products.create') }}"
                                        class="text-xl decoration-2 hover:text-green-600 hover:underline">Add New Product</a></li>
                                <li><a
                                        href="{{ route('products.import.show') }}"
                                        class="text-xl decoration-2 hover:text-green-600 hover:underline">Import Products (CSV)</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                <!-- Reports Card — visible to all roles (same as sidebar) -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-2xl font-semibold text-gray-800">Reports</h2>
                        <div class="rounded-full bg-orange-100 p-2 text-orange-600">
                            <svg
                                class="h-5 w-5 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
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
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a
                                href="{{ route('reports.sales') }}"
                                class="text-xl decoration-2 hover:text-green-600 hover:underline">Sales Overview</a></li>
                        <li><a
                                href="{{ route('reports.orders') }}"
                                class="text-xl decoration-2 hover:text-green-600 hover:underline">Sales Orders Report </a></li>
                        <li><a
                                href="{{ route('reports.payments') }}"
                                class="text-xl decoration-2 hover:text-green-600 hover:underline">Payments Overview</a></li>
                    </ul>
                </div>

                {{-- ══ Admin: same gate as sidebar (super admin only) ══ --}}
                @if (auth()->user()->role === 'super admin')
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-gray-800">Admin</h2>
                            <div class="rounded-full bg-purple-100 p-2 text-purple-600">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="h-5 w-5 flex-shrink-0">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a
                                    href="{{ route('users.index') }}"
                                    class="text-xl decoration-2 hover:text-purple-600 hover:underline">User Management</a></li>
                            <li><a
                                    href="{{ route('settings.index') }}"
                                    class="text-xl decoration-2 hover:text-purple-600 hover:underline">System Settings</a></li>
                            <li><a
                                    href="{{ route('logs') }}"
                                    class="text-xl decoration-2 hover:text-purple-600 hover:underline">Logs</a></li>
                        </ul>
                    </div>

                    {{-- ══ Others: same gate as sidebar (super admin only) ══ --}}
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-2xl font-semibold text-gray-800">Others</h2>
                            <div class="rounded-full bg-yellow-100 p-2 text-yellow-600">
                                <svg
                                    class="h-5 w-5 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                        </div>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a
                                    href="{{ route('others.inventory.form') }}"
                                    class="text-xl decoration-2 hover:text-yellow-600 hover:underline">Inventory Form</a></li>
                            <li><a
                                    href="{{ route('others.filemanager.index') }}"
                                    class="text-xl decoration-2 hover:text-yellow-600 hover:underline">File Manager</a></li>
                        </ul>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
