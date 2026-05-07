@extends('layouts.app')

@section('content')
    <div class="">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header Section -->
            <div class="mb-6">
                <div class="flex items-center space-x-3">
                    <div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-2 shadow-lg sm:p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-white sm:h-8 sm:w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Sales Order List</h1>
                        <p class="mt-0.5 hidden text-gray-600 sm:block">List of all B2B sales orders submitted for processing and fulfillment.</p>
                    </div>
                </div>
            </div>
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('orders.index') }}" class="ajax-form mb-4">

                <!-- Always-visible row: Search + Filter toggle + Apply + Reset -->
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    {{-- Search Input - Responsive width --}}
                    <div class="relative min-w-[200px] max-w-sm flex-1">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search orders..."
                            class="w-full rounded-md border border-gray-300 py-1.5 pl-7 pr-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="absolute left-2 top-2 h-3.5 w-3.5 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 103.5 10.5a7.5 7.5 0 0013.15 6.15z" />
                        </svg>
                    </div>

                    {{-- Filter toggle button --}}
                    <button
                        type="button"
                        id="filter-toggle"
                        class="relative flex flex-shrink-0 items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                        </svg>
                        <span class="hidden sm:inline">Filters</span>
                        {{-- Active filter badge --}}
                        @php
                            $activeFilters = collect(['store_code', 'channel', 'status', 'start_date', 'end_date'])
                                ->filter(fn($k) => request($k))
                                ->count();
                        @endphp
                        @if ($activeFilters > 0)
                            <span class="absolute -right-1.5 -top-1.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-indigo-600 text-[9px] font-bold text-white">
                                {{ $activeFilters }}
                            </span>
                        @endif
                    </button>

                    <button
                        type="submit"
                        class="flex-shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        Apply
                    </button>
                    <a
                        href="{{ route('orders.index') }}"
                        class="flex-shrink-0 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-200">
                        Reset
                    </a>
                </div>

                <!-- Collapsible filter panel -->
                <div
                    id="filter-panel"
                    class="{{ $activeFilters > 0 ? '' : 'hidden' }} mt-2 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-3">

                    <div class="flex flex-wrap items-end gap-3">
                        {{-- Store --}}
                        <div class="min-w-[140px] flex-1">
                            <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-gray-500">Store</label>
                            <select
                                name="store_code"
                                class="w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">All Stores</option>
                                @foreach ($storeLocations as $code => $label)
                                    <option
                                        value="{{ $code }}"
                                        {{ request('store_code') == $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Channel --}}
                        <div class="min-w-[120px] flex-1">
                            <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-gray-500">Channel</label>
                            <select
                                name="channel"
                                class="w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">All</option>
                                @foreach ($channels as $channel)
                                    <option
                                        value="{{ $channel }}"
                                        {{ request('channel') == $channel ? 'selected' : '' }}>
                                        {{ $channel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="min-w-[120px] flex-1">
                            <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-gray-500">Status</label>
                            <select
                                name="status"
                                class="w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">All</option>
                                @foreach ($statuses as $status)
                                    <option
                                        value="{{ $status }}"
                                        {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst(strtolower($status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date range --}}
                        <div class="min-w-[240px] flex-[2]">
                            <label class="mb-0.5 block text-[10px] font-semibold uppercase tracking-wide text-gray-500">Date Range</label>
                            <div class="flex items-center gap-2">
                                <input
                                    type="date"
                                    name="start_date"
                                    value="{{ request('start_date') }}"
                                    class="flex-1 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <span class="text-xs text-gray-400">to</span>
                                <input
                                    type="date"
                                    name="end_date"
                                    value="{{ request('end_date') }}"
                                    class="flex-1 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <script nonce="{{ $cspNonce ?? '' }}">
                (function() {
                    const btn = document.getElementById('filter-toggle');
                    const panel = document.getElementById('filter-panel');
                    if (!btn || !panel) return;

                    btn.addEventListener('click', function() {
                        panel.classList.toggle('hidden');
                    });
                })();
            </script>


            <div class="space-y-6 rounded-xl bg-white shadow-lg">
                <!-- Search Bar -->

                <div class="relative">
                    <div id="orders-loading" class="absolute inset-0 z-10 flex hidden items-center justify-center bg-white/70">
                        <div class="h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
                    </div>

                    <div id="orders-table">
                        @include('orders.partials.table')
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between p-4" style="margin-top:-65px;">
                        <!-- Rows per page -->
                        <form method="GET" action="{{ route('orders.index') }}" class="ajax-form flex items-center space-x-2">

                            @foreach (request()->except('per_page', 'page') as $key => $value)
                                <input
                                    type="hidden"
                                    name="{{ $key }}"
                                    value="{{ $value }}">
                            @endforeach

                            <label
                                for="per_page"
                                class="text-sm text-gray-600">Rows per page:</label>
                            <select
                                name="per_page"
                                id="per_page"
                                class="rounded border-0 px-8 py-1 text-sm">
                                <option
                                    value="10"
                                    {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option
                                    value="25"
                                    {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                <option
                                    value="50"
                                    {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option
                                    value="100"
                                    {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </form>

                        <!-- Pagination -->
                        {{-- <div>
                        {{ $orders->links('pagination::tailwind') }}
                    </div> --}}
                    </div>


                </div>

            </div>
        </div>

        <script nonce="{{ $cspNonce ?? '' }}">
            function fetchOrders(url) {
                const overlay = document.getElementById('orders-loading');
                overlay?.classList.remove('hidden');

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('orders-table').innerHTML = html;

                        // Update browser URL without reloading
                        window.history.pushState({}, '', url);

                        // Re-bind rows per page listener
                        const perPage = document.getElementById('per_page');
                        if (perPage) {
                            perPage.addEventListener('change', function() {
                                const form = this.closest('form');

                                // Include all hidden inputs for filters
                                const query = new URLSearchParams(new FormData(form)).toString();

                                // Also include main filter form values if you want
                                const mainForm = document.querySelector('.ajax-form');
                                if (mainForm && mainForm !== form) {
                                    new FormData(mainForm).forEach((value, key) => {
                                        if (!query.includes(key + '=')) query.append(key, value);
                                    });
                                }

                                fetchOrders(form.action + '?' + query);
                            });
                        }
                    })
                    .catch(err => console.error(err))
                    .finally(() => overlay?.classList.add('hidden'));
            }

            document.getElementById('per_page')?.dispatchEvent(new Event('change', {
                bubbles: true
            }));



            // Handle browser back/forward buttons
            window.addEventListener('popstate', () => {
                fetchOrders(window.location.href);
            });

            // Pagination clicks
            document.addEventListener('click', function(e) {
                const link = e.target.closest('nav[aria-label="Pagination Navigation"] a');
                if (!link) return;

                e.preventDefault();
                fetchOrders(link.href);
            });

            // AJAX forms (filters)
            document.querySelectorAll('.ajax-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const url = this.action + '?' + new URLSearchParams(new FormData(this));
                    fetchOrders(url);
                });
            });

            // Rows per page
            document.getElementById('per_page')?.addEventListener('change', function() {
                const form = this.closest('form');
                const url = form.action + '?' + new URLSearchParams(new FormData(form));
                fetchOrders(url);
            });
        </script>
    @endsection
