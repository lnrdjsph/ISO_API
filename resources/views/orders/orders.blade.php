@extends('layouts.app')

@section('content')
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═══ Header with clickable status flow button ═══ --}}
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

            @php
                $currentUser = auth()->user();
                $showStatusFlowButton = false;
                if ($currentUser) {
                    if (method_exists($currentUser, 'hasAnyRole')) {
                        $showStatusFlowButton = $currentUser->hasAnyRole(['store personnel', 'super admin']);
                    } else {
                        $role = strtolower($currentUser->role ?? '');
                        $showStatusFlowButton = in_array($role, ['store personnel', 'store_personnel', 'super admin'], true);
                    }
                }
            @endphp

            @if ($showStatusFlowButton)
                <button type="button" id="statusFlowBtn"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm transition hover:bg-gray-50 hover:shadow-md">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Order Status Flow</span>
                </button>
            @endif
        </div>

        {{-- ═══ Filters (exactly as original) ═══ --}}
        <form method="GET" action="{{ route('orders.index') }}" id="orders-filter-form" class="ajax-form mb-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
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

                <select name="store_code" data-filter data-label="Store"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Stores</option>
                    @foreach ($storeLocations as $code => $label)
                        <option value="{{ $code }}" {{ request('store_code') == $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="channel" data-filter data-label="Channel"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Channels</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel }}" {{ request('channel') == $channel ? 'selected' : '' }}>{{ $channel }}</option>
                    @endforeach
                </select>

                <select name="status" data-filter data-label="Status"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(strtolower($status)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2">
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
                <div class="inline-flex items-center gap-1.5">
                    <input type="date" name="start_date" data-filter value="{{ request('start_date') }}"
                        class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <span class="text-xs text-gray-400">→</span>
                    <input type="date" name="end_date" data-filter value="{{ request('end_date') }}"
                        class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <span class="ml-auto text-xs text-gray-400" id="inline-total-count"></span>
            </div>
        </form>

        {{-- Active filter chips --}}
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

                <div class="flex items-center justify-between p-4" style="margin-top:-65px;">
                    <form method="GET" action="{{ route('orders.index') }}" class="ajax-form flex items-center space-x-2">
                        @foreach (request()->except('per_page', 'page') as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label for="per_page" class="text-sm text-gray-600">Rows per page:</label>
                        <select name="per_page" id="per_page" class="rounded border-0 px-8 py-1 text-sm">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script nonce="{{ $cspNonce ?? '' }}">
        // ========== Enhanced Status Flow Modal ==========
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('statusFlowBtn');
            if (btn) btn.addEventListener('click', showStatusFlowModal);
        });

        function showStatusFlowModal() {
            Swal.fire({
                title: 'Order Status Flow',
                html: `
            <div class="status-flow-container" style="font-family: system-ui, -apple-system, sans-serif;">
                <!-- Main flow -->
                <div class="flow-steps" style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 0.5rem; margin: 0.5rem 0 1rem;">
                    <!-- New Order (Blue) -->
                    <div class="step" style="flex: 1; min-width: 90px; text-align: center; background: #eff6ff; border-radius: 1rem; padding: 0.6rem 0.4rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto; box-shadow: 0 2px 4px rgba(59,130,246,0.2);">
                            <svg style="width: 22px; height: 22px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: #1e3a8a;">New Order</div>
                        <div style="font-size: 0.7rem; color: #3b82f6;">Pending review</div>
                    </div>
                    <div class="arrow" style="color: #94a3b8; font-size: 1.2rem; font-weight: 300;">→</div>
                    <!-- For Approval (Purple) -->
                    <div class="step" style="flex: 1; min-width: 90px; text-align: center; background: #f3e8ff; border-radius: 1rem; padding: 0.6rem 0.4rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="width: 40px; height: 40px; background: #9333ea; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto; box-shadow: 0 2px 4px rgba(147,51,234,0.2);">
                            <svg style="width: 22px; height: 22px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: #4c1d95;">For Approval</div>
                        <div style="font-size: 0.7rem; color: #9333ea;">Awaiting manager</div>
                    </div>
                    <div class="arrow" style="color: #94a3b8; font-size: 1.2rem; font-weight: 300;">→</div>
                    <!-- Approved (Green) -->
                    <div class="step" style="flex: 1; min-width: 90px; text-align: center; background: #dcfce7; border-radius: 1rem; padding: 0.6rem 0.4rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="width: 40px; height: 40px; background: #22c55e; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto; box-shadow: 0 2px 4px rgba(34,197,94,0.2);">
                            <svg style="width: 22px; height: 22px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: #14532d;">Approved</div>
                        <div style="font-size: 0.7rem; color: #16a34a;">Cleared for processing</div>
                    </div>
                    <div class="arrow" style="color: #94a3b8; font-size: 1.2rem; font-weight: 300;">→</div>
                    <!-- Completed (Dark Green) -->
                    <div class="step" style="flex: 1; min-width: 90px; text-align: center; background: #bbf7d0; border-radius: 1rem; padding: 0.6rem 0.4rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="width: 40px; height: 40px; background: #059669; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 6px auto; box-shadow: 0 2px 4px rgba(5,150,105,0.2);">
                            <svg style="width: 22px; height: 22px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: #064e3b;">Completed</div>
                        <div style="font-size: 0.7rem; color: #059669;">Fulfilled & closed order</div>
                    </div>
                </div>

                <!-- Terminal statuses (Rejected / Cancelled) -->
                <div style="border-top: 1px solid #e2e8f0; margin: 0.5rem 0 0.5rem; padding-top: 0.8rem;">
                    <div style="display: flex; justify-content: center; gap: 1.2rem; flex-wrap: wrap;">
                        <!-- Rejected (Orange) -->
                        <div style="text-align: center; background: #ffedd5; border-radius: 1rem; padding: 0.4rem 1rem; min-width: 100px;">
                            <div style="width: 34px; height: 34px; background: #f97316; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto; box-shadow: 0 2px 4px rgba(249,115,22,0.2);">
                                <svg style="width: 18px; height: 18px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div style="font-weight: 600; font-size: 0.8rem; color: #9a3412;">Rejected</div>
                            <div style="font-size: 0.65rem; color: #c2410c;">Order declined by manager</div>
                        </div>
                        <!-- Cancelled (Red) -->
                        <div style="text-align: center; background: #fee2e2; border-radius: 1rem; padding: 0.4rem 1rem; min-width: 100px;">
                            <div style="width: 34px; height: 34px; background: #ef4444; border-radius: 9999px; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px auto; box-shadow: 0 2px 4px rgba(239,68,68,0.2);">
                                <svg style="width: 18px; height: 18px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div style="font-weight: 600; font-size: 0.8rem; color: #991b1b;">Cancelled</div>
                            <div style="font-size: 0.65rem; color: #dc2626;">Order voided or archived</div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                @media (max-width: 550px) {
                    .flow-steps { flex-direction: column; gap: 0.25rem !important; }
                    .arrow { transform: rotate(90deg); margin: 0.25rem 0; }
                    .step { width: 80%; margin: 0 auto; }
                }
            </style>
        `,
                showCloseButton: true,
                showConfirmButton: false,
                width: 'auto',
                customClass: {
                    popup: 'compact-swal rounded-2xl',
                    title: 'text-lg font-bold'
                },
                didOpen: () => {
                    const style = document.createElement('style');
                    style.textContent = `
                @media (max-width: 480px) {
                    .compact-swal {
                        width: 92% !important;
                        max-width: 400px !important;
                        padding: 1rem !important;
                    }
                    .compact-swal .swal2-title {
                        font-size: 1.2rem !important;
                        padding-bottom: 0 !important;
                    }
                    .compact-swal .swal2-html-container {
                        padding: 0 !important;
                    }
                }
            `;
                    document.head.appendChild(style);
                }
            });
        }

        // ========== Original AJAX Filtering Script (summarySpan references removed) ==========
        (function() {
            const form = document.getElementById('orders-filter-form');
            const action = form.getAttribute('action');
            const searchInput = document.getElementById('orders-search');
            const searchClear = document.getElementById('orders-search-clear');
            const searchSpin = document.getElementById('orders-search-spinner');
            const perPage = document.getElementById('per_page');
            const overlay = document.getElementById('orders-loading');
            const chipsBox = document.getElementById('active-filters');
            const paginationInfoSpan = document.getElementById('pagination-info');

            const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            function getState() {
                const state = {};
                new FormData(form).forEach((v, k) => {
                    if (v !== '' && v != null) state[k] = v;
                });
                if (perPage && perPage.value) state.per_page = perPage.value;
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

            form.addEventListener('submit', (e) => e.preventDefault());

            if (perPage) {
                perPage.addEventListener('change', function() {
                    applyFilters();
                });
            }

            document.addEventListener('click', function(e) {
                const link = e.target.closest('nav[aria-label="Pagination Navigation"] a');
                if (!link) return;
                e.preventDefault();
                fetchOrders(link.href);
            });

            window.addEventListener('popstate', () => fetchOrders(window.location.href));

            // When restored from bfcache (back/forward), re-fetch so the table
            // matches the URL (e.g. ?page=2) instead of showing stale cached content.
            window.addEventListener('pageshow', (e) => {
                if (e.persisted) fetchOrders(window.location.href);
            });

            renderChips(getState());
            setTimeout(() => {
                const paginationElem = document.querySelector('#orders-table .pagination-info-text');
                if (paginationElem && paginationInfoSpan) paginationInfoSpan.innerText = paginationElem.innerText;
            }, 100);
        })();
    </script>
@endsection

<!-- Your existing table partial and styles remain exactly as before -->
