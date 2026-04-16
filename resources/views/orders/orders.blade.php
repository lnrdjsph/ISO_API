@extends('layouts.app')

@section('content')
    <div class="">
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
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Sales Order List</h1>
                        <p class="mt-1 text-gray-600">List of all B2B sales orders submitted for processing and fulfillment.</p>
                    </div>
                </div>
            </div><!-- Modern Filter Bar -->
            <form method="GET" action="{{ route('orders.index') }}" class="ajax-form mb-4 flex flex-wrap items-center gap-2 text-xs">

                <!-- Search -->
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search..."
                        class="w-72 rounded-md border border-gray-300 py-1.5 pl-8 pr-3 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
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

                <!-- Store -->
                <select
                    name="store_code"
                    class="w-40 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Stores</option>
                    @foreach ($storeLocations as $code => $label)
                        <option
                            value="{{ $code }}"
                            {{ request('store_code') == $code ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <!-- Channel -->
                <select
                    name="channel"
                    class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Channels</option>
                    @foreach ($channels as $channel)
                        <option
                            value="{{ $channel }}"
                            {{ request('channel') == $channel ? 'selected' : '' }}>
                            {{ $channel }}
                        </option>
                    @endforeach
                </select>

                <!-- Status -->
                <select
                    name="status"
                    class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status }}"
                            {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst(strtolower($status)) }}
                        </option>
                    @endforeach
                </select>

                <!-- Date Range -->
                <input
                    type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <span class="text-gray-500">to</span>
                <input
                    type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">

                <!-- Apply -->
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-700">
                    Apply
                </button>

                <!-- Reset -->
                <a
                    href="{{ route('orders.index') }}"
                    class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-200">
                    Reset
                </a>
            </form>


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
