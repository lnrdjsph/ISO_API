@extends('layouts.app')

@section('content')
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═══ Header (matching Activity Log) ═══ --}}
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-3 shadow-lg">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Sales Orders</h1>
                    <p class="mt-1 text-gray-600">Manage and track all B2B orders</p>
                </div>
            </div>
            <div id="orders-summary" class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                <span class="text-xs font-medium text-gray-500">Total Orders</span>
                <p class="text-xl font-bold text-gray-900">--</p>
            </div>
        </div>

        {{-- ═══ Filters (Activity Log style) ═══ --}}
        <form method="GET" action="{{ route('orders.index') }}" id="orders-filter-form" class="ajax-form mb-6">

            {{-- Primary row: Search + Store + Channel + Status --}}
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                {{-- Search (full width, prominent) --}}
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                        </svg>
                    </div>
                    <input type="text" name="search" id="orders-search" value="{{ request('search') }}"
                        placeholder="Search by order #, customer, or store..."
                        autocomplete="off"
                        class="w-full rounded-xl border-0 bg-white py-3 pl-11 pr-11 text-sm text-gray-800 shadow-sm ring-1 ring-inset ring-gray-200 transition placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500">
                    <button type="button" id="orders-search-clear"
                        class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div id="orders-search-spinner" class="absolute inset-y-0 right-0 hidden items-center pr-3.5">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-indigo-600"></div>
                    </div>
                </div>

                {{-- Store dropdown --}}
                <select name="store_code" data-filter data-label="Store"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Stores</option>
                    @foreach ($storeLocations as $code => $label)
                        <option value="{{ $code }}" {{ request('store_code') == $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                {{-- Channel dropdown --}}
                <select name="channel" data-filter data-label="Channel"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Channels</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel }}" {{ request('channel') == $channel ? 'selected' : '' }}>{{ $channel }}</option>
                    @endforeach
                </select>

                {{-- Status dropdown --}}
                <select name="status" data-filter data-label="Status"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(strtolower($status)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Secondary row: Date range presets + custom range (inline) --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Quick date presets as pill buttons --}}
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5">
                    <button type="button" id="preset-alltime"
                        class="{{ !request('start_date') && !request('end_date') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                        All time
                    </button>
                    <button type="button" data-preset="today"
                        class="{{ request('start_date') == now()->toDateString() && request('end_date') == now()->toDateString() ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                        Today
                    </button>
                    <button type="button" data-preset="7d"
                        class="{{ request('start_date') == now()->subDays(6)->toDateString() && request('end_date') == now()->toDateString() ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                        Last 7 days
                    </button>
                    <button type="button" data-preset="30d"
                        class="{{ request('start_date') == now()->subDays(29)->toDateString() && request('end_date') == now()->toDateString() ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                        Last 30 days
                    </button>
                    <button type="button" data-preset="month"
                        class="{{ request('start_date') == now()->startOfMonth()->toDateString() && request('end_date') == now()->toDateString() ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                        This month
                    </button>
                </div>

                {{-- Custom date range (inline) --}}
                <div class="inline-flex items-center gap-1.5">
                    <input type="date" name="start_date" data-filter value="{{ request('start_date') }}"
                        class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <span class="text-xs text-gray-400">→</span>
                    <input type="date" name="end_date" data-filter value="{{ request('end_date') }}"
                        class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                </div>

                {{-- Optional: total records count (mobile right alignment) --}}
                <span class="ml-auto text-xs text-gray-400" id="inline-total-count"></span>
            </div>
        </form>

        {{-- Active filter chips (removable) --}}
        <div id="active-filters" class="mb-5 flex flex-wrap items-center gap-2"></div>


        {{-- ═══ Table Card ═══ --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
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
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            const form = document.getElementById('orders-filter-form');
            const action = form.getAttribute('action');
            const searchInput = document.getElementById('orders-search');
            const searchClear = document.getElementById('orders-search-clear');
            const searchSpin = document.getElementById('orders-search-spinner');
            const perPage = document.getElementById('per_page');
            const overlay = document.getElementById('orders-loading');
            const chipsBox = document.getElementById('active-filters');
            const summarySpan = document.querySelector('#orders-summary p');
            const paginationInfoSpan = document.getElementById('pagination-info');

            const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            function getState() {
                const state = {};
                // Collect all filters from the main form
                new FormData(form).forEach((v, k) => {
                    if (v !== '' && v != null) state[k] = v;
                });
                // Add per_page value from the static select element
                if (perPage && perPage.value) {
                    state.per_page = perPage.value;
                }
                return state;
            }

            function buildUrl(state) {
                const params = new URLSearchParams(state);
                const qs = params.toString();
                return qs ? action + '?' + qs : action;
            }

            function fetchOrders(url, useSearchSpinner) {
                if (useSearchSpinner && searchSpin) {
                    searchSpin.classList.remove('hidden');
                    searchSpin.classList.add('flex');
                } else {
                    overlay && overlay.classList.remove('hidden');
                    overlay && overlay.classList.add('flex');
                }

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('orders-table').innerHTML = html;
                        window.history.pushState({}, '', url);

                        const summaryElem = document.querySelector('#orders-table .orders-summary');
                        if (summaryElem && summarySpan) summarySpan.innerText = summaryElem.innerText;
                        const paginationElem = document.querySelector('#orders-table .pagination-info-text');
                        if (paginationElem && paginationInfoSpan) paginationInfoSpan.innerText = paginationElem.innerText;
                    })
                    .catch(err => console.error(err))
                    .finally(() => {
                        overlay && overlay.classList.add('hidden');
                        overlay && overlay.classList.remove('flex');
                        searchSpin && searchSpin.classList.add('hidden');
                        searchSpin && searchSpin.classList.remove('flex');
                    });
            }

            function applyFilters(useSearchSpinner) {
                const state = getState();
                fetchOrders(buildUrl(state), useSearchSpinner);
                renderChips(state);
            }

            function fmtDate(val) {
                const p = val.split('-');
                if (p.length !== 3) return val;
                return MONTHS[parseInt(p[1], 10) - 1] + ' ' + parseInt(p[2], 10) + ', ' + p[0];
            }

            function makeChip(label, onRemove) {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1 rounded-full bg-indigo-50 py-0.5 pl-2 pr-1 text-[11px] font-medium text-indigo-700 ring-1 ring-inset ring-indigo-100';
                const text = document.createElement('span');
                text.textContent = label;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('aria-label', 'Remove');
                btn.className = 'flex h-3.5 w-3.5 items-center justify-center rounded-full text-indigo-400 hover:bg-indigo-200';
                btn.innerHTML =
                    '<svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';
                btn.addEventListener('click', onRemove);
                chip.appendChild(text);
                chip.appendChild(btn);
                return chip;
            }

            function renderChips(state) {
                chipsBox.innerHTML = '';

                form.querySelectorAll('select[data-filter]').forEach(sel => {
                    if (sel.value) {
                        const opt = sel.options[sel.selectedIndex];
                        chipsBox.appendChild(makeChip(sel.dataset.label + ': ' + opt.text.trim(), () => {
                            sel.value = '';
                            applyFilters();
                        }));
                    }
                });

                const start = form.querySelector('[name="start_date"]');
                const end = form.querySelector('[name="end_date"]');
                if (start && start.value) {
                    chipsBox.appendChild(makeChip('From ' + fmtDate(start.value), () => {
                        start.value = '';
                        applyFilters();
                    }));
                }
                if (end && end.value) {
                    chipsBox.appendChild(makeChip('To ' + fmtDate(end.value), () => {
                        end.value = '';
                        applyFilters();
                    }));
                }

                const activeTotal = chipsBox.children.length + (searchInput.value ? 1 : 0);
                if (activeTotal > 1) {
                    const clearAll = document.createElement('a');
                    clearAll.href = '#';
                    clearAll.textContent = 'Clear all';
                    clearAll.className = 'ml-1 text-[11px] font-medium text-gray-500 hover:text-gray-700 underline';
                    clearAll.addEventListener('click', (e) => {
                        e.preventDefault();
                        resetAll();
                    });
                    chipsBox.appendChild(clearAll);
                }
            }

            function resetAll() {
                form.reset();
                searchInput.value = '';
                form.querySelectorAll('[data-filter]').forEach(el => el.value = '');
                searchClear.classList.add('hidden');
                applyFilters();
            }

            // Event listeners
            let searchTimer;
            searchInput.addEventListener('input', function() {
                searchClear.classList.toggle('hidden', !this.value);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => applyFilters(true), 300);
            });
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimer);
                    applyFilters(true);
                }
            });
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                searchClear.classList.add('hidden');
                applyFilters();
                searchInput.focus();
            });

            form.querySelectorAll('[data-filter]').forEach(el => {
                el.addEventListener('change', () => applyFilters());
            });

            function ymd(d) {
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            // Handle "All time" button (clears both date inputs)
            const allTimeBtn = document.getElementById('preset-alltime');
            if (allTimeBtn) {
                allTimeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const start = form.querySelector('[name="start_date"]');
                    const end = form.querySelector('[name="end_date"]');
                    start.value = '';
                    end.value = '';
                    applyFilters();
                });
            }

            // Handle other presets (Today, Last 7 days, etc.)
            document.querySelectorAll('[data-preset]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const start = form.querySelector('[name="start_date"]');
                    const end = form.querySelector('[name="end_date"]');
                    const today = new Date();
                    let from = new Date();

                    switch (this.dataset.preset) {
                        case 'today':
                            from = today;
                            break;
                        case '7d':
                            from.setDate(today.getDate() - 6);
                            break;
                        case '30d':
                            from.setDate(today.getDate() - 29);
                            break;
                        case 'month':
                            from = new Date(today.getFullYear(), today.getMonth(), 1);
                            break;
                        default:
                            return;
                    }
                    start.value = ymd(from);
                    end.value = ymd(today);
                    applyFilters();
                });
            });

            // Prevent full form submission (AJAX only)
            form.addEventListener('submit', (e) => e.preventDefault());

            // Per page change
            if (perPage) {
                perPage.addEventListener('change', function() {
                    applyFilters();
                });
            }

            // Pagination links (delegation)
            document.addEventListener('click', function(e) {
                const link = e.target.closest('nav[aria-label="Pagination Navigation"] a');
                if (!link) return;
                e.preventDefault();
                fetchOrders(link.href);
            });

            // Browser back/forward
            window.addEventListener('popstate', () => fetchOrders(window.location.href));

            // Initial render
            renderChips(getState());
            setTimeout(() => {
                const summaryElem = document.querySelector('#orders-table .orders-summary');
                if (summaryElem && summarySpan) summarySpan.innerText = summaryElem.innerText;
                const paginationElem = document.querySelector('#orders-table .pagination-info-text');
                if (paginationElem && paginationInfoSpan) paginationInfoSpan.innerText = paginationElem.innerText;
            }, 100);
        })();
    </script>
@endsection
