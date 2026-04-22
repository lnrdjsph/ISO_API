@extends('layouts.app')

@section('content')
    @php
        abort_unless(auth()->user()->role === 'super admin', 403);

        // Colour palettes — one per warehouse code and region key
        $whColors = [
            '80001' => ['bg' => 'bg-violet-100', 'text' => 'text-violet-800', 'header' => 'bg-violet-600', 'border' => 'border-violet-200', 'light' => 'bg-violet-50'],
            '80041' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'header' => 'bg-orange-500', 'border' => 'border-orange-200', 'light' => 'bg-orange-50'],
            '80051' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'header' => 'bg-blue-600', 'border' => 'border-blue-200', 'light' => 'bg-blue-50'],
            '80071' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-800', 'header' => 'bg-sky-500', 'border' => 'border-sky-200', 'light' => 'bg-sky-50'],
            '80131' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-800', 'header' => 'bg-teal-600', 'border' => 'border-teal-200', 'light' => 'bg-teal-50'],
            '80141' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'header' => 'bg-amber-500', 'border' => 'border-amber-200', 'light' => 'bg-amber-50'],
            '80181' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-800', 'header' => 'bg-rose-500', 'border' => 'border-rose-200', 'light' => 'bg-rose-50'],
            '80191' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'header' => 'bg-emerald-600', 'border' => 'border-emerald-200', 'light' => 'bg-emerald-50'],
            '80211' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'header' => 'bg-indigo-600', 'border' => 'border-indigo-200', 'light' => 'bg-indigo-50'],
        ];

        $regionColors = [
            'lz' => ['pill' => 'bg-amber-100 text-amber-800', 'card' => 'bg-amber-50 border-amber-200'],
            'ntc' => ['pill' => 'bg-blue-100 text-blue-800', 'card' => 'bg-blue-50 border-blue-200'],
            'stc' => ['pill' => 'bg-green-100 text-green-800', 'card' => 'bg-green-50 border-green-200'],
            'vs' => ['pill' => 'bg-pink-100 text-pink-800', 'card' => 'bg-pink-50 border-pink-200'],
            'ctc' => ['pill' => 'bg-violet-100 text-violet-800', 'card' => 'bg-violet-50 border-violet-200'],
        ];

        $defaultWh = ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'header' => 'bg-gray-500', 'border' => 'border-gray-200', 'light' => 'bg-gray-50'];
        $defaultRegion = ['pill' => 'bg-gray-100 text-gray-700', 'card' => 'bg-gray-50 border-gray-200'];

        $warehouseMap = $warehouses->keyBy('warehouse_code');
        $regionMap = $regions->keyBy('region_key');
        $whToStores = $stores->whereNotNull('warehouse_code')->groupBy('warehouse_code');

        // Pre-built region→store_codes map for JS
        $regionStoreCodes = $stores->groupBy('region_key')->map(fn($g) => $g->pluck('store_code'));
    @endphp

    <style nonce="{{ $cspNonce ?? '' }}">
        .settings-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: .85rem;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            transition: color .2s, border-color .2s;
            letter-spacing: .01em;
        }

        .settings-tab:hover {
            color: #111827;
        }

        .settings-tab.active {
            color: #1d4ed8;
            border-bottom-color: #1d4ed8;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .modal {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity .25s ease, visibility .25s ease;
        }

        .modal.open {
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }

        .modal-box {
            transform: translateY(16px) scale(.98);
            transition: transform .25s ease;
        }

        .modal.open .modal-box {
            transform: translateY(0) scale(1);
        }

        .settings-table th {
            background: #f9fafb;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #6b7280;
            padding: 10px 16px;
        }

        .settings-table td {
            padding: 12px 16px;
            font-size: .85rem;
            border-top: 1px solid #f3f4f6;
            color: #374151;
        }

        .settings-table tbody tr:hover td {
            background: #f9fafb;
        }

        .store-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #f3f4f6;
            background: #fafafa;
            transition: background .15s;
        }

        .store-row:hover {
            background: #f0f4ff;
            border-color: #c7d2fe;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .status-active {
            background: #22c55e;
        }

        .status-pending {
            background: #f59e0b;
        }

        .status-inactive {
            background: #d1d5db;
        }

        .badge-soon {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 999px;
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .info-card {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%);
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .mapping-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            transition: box-shadow .2s;
            overflow: hidden;
        }

        .mapping-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08);
        }
    </style>

    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-xl bg-gradient-to-br from-gray-900 to-indigo-900 p-3 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
                    <p class="mt-0.5 text-sm text-gray-500">Manage stores, warehouses, regions, and platform configuration</p>
                </div>
            </div>
            <div class="hidden items-center gap-3 lg:flex">
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center shadow-sm">
                    <div class="text-xl font-bold text-indigo-700">{{ $stores->whereIn('status', ['active', 'pending'])->count() }}</div>
                    <div class="text-xs text-gray-500">Enrolled Stores</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center shadow-sm">
                    <div class="text-xl font-bold text-emerald-600">{{ $warehouses->where('is_active', true)->count() }}</div>
                    <div class="text-xs text-gray-500">Warehouses</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-center shadow-sm">
                    <div class="text-xl font-bold text-amber-600">{{ $regions->count() }}</div>
                    <div class="text-xs text-gray-500">Regions</div>
                </div>
            </div>
        </div>

        {{-- TAB BAR --}}
        <div class="mb-6 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex border-b border-gray-200 px-2">
                <button class="settings-tab active" data-tab="stores">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Store Management
                </button>
                <button class="settings-tab" data-tab="mapping">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Warehouse Mapping
                </button>
                <button class="settings-tab" data-tab="regions">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    Region Config
                </button>
                <button class="settings-tab" data-tab="warehouses">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Warehouses
                </button>
                <button class="settings-tab" data-tab="audit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Config Audit
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════
             TAB 1 · STORE MANAGEMENT
        ══════════════════════════════ --}}
        <div id="tab-stores" class="tab-panel active">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Enrolled Stores</h2>
                    <p class="text-sm text-gray-500">Enrolling a store creates its <code class="rounded bg-gray-100 px-1 text-xs">products_{code}</code> table automatically</p>
                </div>
                <button onclick="openModal('addStoreModal')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow transition-colors hover:bg-indigo-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Enroll New Store
                </button>
            </div>

            {{-- Filters --}}
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <input type="text" id="storeSearch" placeholder="Search name or code…"
                    class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                <select id="storeRegionFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Regions</option>
                    @foreach ($regions as $r)
                        <option value="{{ $r->region_key }}">{{ $r->label }}</option>
                    @endforeach
                </select>
                <select id="storeWhFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Warehouses</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->warehouse_code }}">{{ $wh->name }}</option>
                    @endforeach
                </select>
                <select id="storeStatusFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="inactive">Inactive</option>
                </select>
                <span id="storeCount" class="ml-auto text-xs font-medium text-gray-400"></span>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="settings-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Code</th>
                            <th class="text-left">Store Name</th>
                            <th class="text-left">Region</th>
                            <th class="text-left">Warehouse</th>
                            <th class="text-left">Facility</th>
                            <th class="text-left">Products Table</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="storeTableBody">
                        @forelse ($stores as $store)
                            @php
                                $wh = $warehouseMap->get($store->warehouse_code);
                                $reg = $regionMap->get($store->region_key);
                                $whC = $wh ? $whColors[$store->warehouse_code] ?? $defaultWh : $defaultWh;
                                $regC = $reg ? $regionColors[$store->region_key] ?? $defaultRegion : $defaultRegion;
                            @endphp
                            <tr data-code="{{ $store->store_code }}"
                                data-name="{{ strtolower($store->display_name) }}"
                                data-region="{{ $store->region_key }}"
                                data-wh="{{ $store->warehouse_code }}"
                                data-status="{{ $store->status }}">
                                <td><span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-bold text-gray-700">{{ $store->store_code }}</span></td>
                                <td class="font-medium text-gray-900">{{ $store->display_name }}</td>
                                <td>
                                    @if ($reg)
                                        <span class="{{ $regC['pill'] }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $reg->label }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($wh)
                                        <span
                                            class="{{ $whC['bg'] }} {{ $whC['text'] }} inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide">
                                            <span class="status-dot status-active"></span>{{ $wh->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Not assigned</span>
                                    @endif
                                </td>
                                <td><span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-600">{{ $wh?->facility_id ?? '—' }}</span></td>
                                <td><span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-500">products_{{ $store->store_code }}</span></td>
                                <td>
                                    @if ($store->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700"><span class="status-dot status-active"></span>Active</span>
                                    @elseif ($store->status === 'pending')
                                        <span class="badge-soon"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>Not Yet Started</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400"><span class="status-dot status-inactive"></span>Inactive</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            onclick="openEditStore('{{ $store->store_code }}','{{ addslashes($store->display_name) }}','{{ addslashes($store->short_name) }}','{{ $store->region_key }}','{{ $store->warehouse_code }}','{{ $store->status }}')"
                                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50">Edit</button>
                                        @if ($store->status !== 'inactive')
                                            <button onclick="confirmDeactivate('{{ $store->store_code }}','{{ addslashes($store->display_name) }}')"
                                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-500 transition-colors hover:bg-red-50">Deactivate</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-gray-400">No stores enrolled yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="info-card mt-4 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-indigo-800">
                    Enrolling a store creates the <code class="rounded bg-indigo-100 px-1">products_{code}</code> table in the database immediately.
                    Deactivating sets status to <strong>inactive</strong> but preserves all product data.
                </p>
            </div>
        </div>

        {{-- ══════════════════════════════
             TAB 2 · WAREHOUSE MAPPING
        ══════════════════════════════ --}}
        <div id="tab-mapping" class="tab-panel">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-gray-900">Store → Warehouse Mapping</h2>
                <p class="text-sm text-gray-500">Which stores are served by each warehouse</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($warehouses as $wh)
                    @php
                        $whC = $whColors[$wh->warehouse_code] ?? $defaultWh;
                        $whStores = $whToStores->get($wh->warehouse_code, collect());
                    @endphp
                    <div class="mapping-card">
                        <div class="{{ $whC['header'] }} flex items-center justify-between px-4 py-3">
                            <div>
                                <div class="text-sm font-bold text-white">{{ $wh->name }}</div>
                                <div class="text-xs text-white/70">Facility: <strong class="text-white">{{ $wh->facility_id }}</strong> · Code: <strong
                                        class="font-mono text-white">{{ $wh->warehouse_code }}</strong></div>
                            </div>
                            <div class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-bold text-white">{{ $whStores->count() }} {{ Str::plural('store', $whStores->count()) }}</div>
                        </div>
                        <div class="space-y-1.5 p-3">
                            @forelse ($whStores as $s)
                                @php
                                    $reg = $regionMap->get($s->region_key);
                                    $regC = $reg ? $regionColors[$s->region_key] ?? $defaultRegion : $defaultRegion;
                                @endphp
                                <div class="store-row">
                                    <div class="flex items-center gap-2.5">
                                        <span class="status-dot {{ $s->status === 'active' ? 'status-active' : ($s->status === 'pending' ? 'status-pending' : 'status-inactive') }}"></span>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800">{{ $s->display_name }}</div>
                                            <div class="font-mono text-xs text-gray-400">{{ $s->store_code }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        @if ($reg)
                                            <span class="{{ $regC['pill'] }} rounded-full px-2 py-0.5 text-xs font-semibold">{{ $reg->label }}</span>
                                        @endif
                                        @if ($s->status === 'pending')
                                            <span class="badge-soon">Soon</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="py-3 text-center text-sm text-gray-400">No stores assigned.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            @php $unmapped = $stores->whereNull('warehouse_code'); @endphp
            @if ($unmapped->count() > 0)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="mb-2 text-sm font-bold text-amber-800">{{ $unmapped->count() }} store(s) not assigned to any warehouse</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($unmapped as $s)
                            <span class="rounded-lg border border-amber-200 bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $s->store_code }} · {{ $s->display_name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3">
                    <h3 class="text-sm font-bold text-gray-800">Full Mapping Matrix</h3>
                </div>
                <table class="settings-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Code</th>
                            <th class="text-left">Store Name</th>
                            <th class="text-left">Warehouse</th>
                            <th class="text-left">Facility</th>
                            <th class="text-left">Region</th>
                            <th class="text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stores as $s)
                            @php
                                $wh = $warehouseMap->get($s->warehouse_code);
                                $reg = $regionMap->get($s->region_key);
                                $whC = $wh ? $whColors[$s->warehouse_code] ?? $defaultWh : $defaultWh;
                                $regC = $reg ? $regionColors[$s->region_key] ?? $defaultRegion : $defaultRegion;
                            @endphp
                            <tr>
                                <td><span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-bold text-gray-700">{{ $s->store_code }}</span></td>
                                <td class="font-medium text-gray-900">{{ $s->display_name }}</td>
                                <td>
                                    @if ($wh)
                                        <span
                                        class="{{ $whC['bg'] }} {{ $whC['text'] }} inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase">{{ $wh->name }}</span>@else<span
                                            class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td><span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-600">{{ $wh?->facility_id ?? '—' }}</span></td>
                                <td>
                                    @if ($reg)
                                    <span class="{{ $regC['pill'] }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $reg->label }}</span>@else<span
                                            class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td><span class="status-dot {{ $s->status === 'active' ? 'status-active' : ($s->status === 'pending' ? 'status-pending' : 'status-inactive') }}"></span><span
                                        class="ml-1 text-xs capitalize text-gray-600">{{ $s->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════
             TAB 3 · REGION CONFIG
        ══════════════════════════════ --}}
        <div id="tab-regions" class="tab-panel">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Region Configuration</h2>
                    <p class="text-sm text-gray-500">Manager regions and their assigned stores</p>
                </div>
                <button onclick="openAddRegionModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition-colors hover:bg-indigo-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Region
                </button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($regions as $region)
                    @php
                        $regStores = $stores->where('region_key', $region->region_key);
                        $regC = $regionColors[$region->region_key] ?? $defaultRegion;
                    @endphp
                    <div class="{{ $regC['card'] }} rounded-xl border p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <span
                                    class="{{ $regC['pill'] }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider">{{ $region->region_key }}</span>
                                <div class="mt-1 font-bold text-gray-900">{{ $region->label }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-800">{{ $regStores->count() }}</div>
                                <div class="text-xs text-gray-500">stores</div>
                            </div>
                        </div>
                        <div class="space-y-1.5 border-t border-black/5 pt-3">
                            @foreach ($regStores as $s)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-700">{{ $s->display_name }}</span>
                                    <span class="rounded bg-white/80 px-1.5 py-0.5 font-mono text-xs text-gray-500">{{ $s->store_code }}</span>
                                </div>
                            @endforeach
                        </div>
                        <button onclick="openEditRegion('{{ $region->region_key }}','{{ addslashes($region->label) }}')"
                            class="mt-3 w-full rounded-lg border border-black/10 bg-white/60 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-white">
                            Manage Stores
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════
             TAB 4 · WAREHOUSES
        ══════════════════════════════ --}}
        <div id="tab-warehouses" class="tab-panel">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Warehouse Registry</h2>
                    <p class="text-sm text-gray-500">Fulfillment warehouses and Oracle WMS facility IDs</p>
                </div>
                <button onclick="openAddWarehouseModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow transition-colors hover:bg-indigo-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Warehouse
                </button>
            </div>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($warehouses as $wh)
                    @php
                        $whC = $whColors[$wh->warehouse_code] ?? $defaultWh;
                        $whStores = $whToStores->get($wh->warehouse_code, collect());
                    @endphp
                    <div class="{{ $whC['light'] }} {{ $whC['border'] }} rounded-xl border p-5">
                        <div class="mb-4 flex items-start justify-between">
                            <div>
                                <div class="mb-1 text-xs font-bold uppercase tracking-wider text-gray-400">Warehouse</div>
                                <h3 class="font-bold leading-tight text-gray-900">{{ $wh->name }}</h3>
                            </div>
                            <span class="rounded-lg border border-gray-200 bg-white px-2 py-1 font-mono text-xs font-bold text-gray-600">{{ $wh->warehouse_code }}</span>
                        </div>
                        <div class="mb-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-white/70 p-3 text-center">
                                <div class="text-xl font-bold text-gray-800">{{ $whStores->count() }}</div>
                                <div class="text-xs text-gray-500">Stores Served</div>
                            </div>
                            <div class="rounded-lg bg-white/70 p-3 text-center">
                                <div class="font-mono text-xl font-bold text-gray-800">{{ $wh->facility_id }}</div>
                                <div class="text-xs text-gray-500">WMS Facility</div>
                            </div>
                        </div>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="status-dot {{ $wh->is_active ? 'status-active' : 'status-inactive' }}"></span>
                            <span class="text-xs text-gray-500">{{ $wh->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <button onclick="openEditWarehouse('{{ $wh->warehouse_code }}','{{ addslashes($wh->name) }}','{{ $wh->facility_id }}')"
                            class="w-full rounded-lg border border-gray-200 bg-white py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50">
                            Edit
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════
             TAB 5 · CONFIG AUDIT
        ══════════════════════════════ --}}
        <div id="tab-audit" class="tab-panel">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-gray-900">Configuration Audit Log</h2>
                <p class="text-sm text-gray-500">Last 50 changes to stores, warehouses, and regions</p>
            </div>
            @if ($auditLogs->isEmpty())
                <div class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <p class="font-semibold text-gray-600">No audit logs yet</p>
                    <p class="mt-1 text-sm text-gray-400">Changes appear here once you enroll or edit stores, warehouses, or regions.</p>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="settings-table w-full">
                        <thead>
                            <tr>
                                <th class="text-left">When</th>
                                <th class="text-left">User</th>
                                <th class="text-left">Entity</th>
                                <th class="text-left">ID</th>
                                <th class="text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($auditLogs as $log)
                                @php
                                    $ac = match ($log->action) {
                                        'created' => 'bg-green-100 text-green-700',
                                        'updated' => 'bg-blue-100 text-blue-700',
                                        'deactivated' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                                    <td class="text-sm text-gray-700">{{ $log->user?->name ?? 'System' }}</td>
                                    <td><span class="text-sm capitalize text-gray-700">{{ $log->entity_type }}</span></td>
                                    <td><span class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs font-bold text-gray-700">{{ $log->entity_id }}</span></td>
                                    <td><span class="{{ $ac }} rounded-full px-2.5 py-0.5 text-xs font-bold">{{ ucfirst($log->action) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>{{-- end max-w container --}}

    {{-- ══════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════ --}}

    {{-- ADD STORE --}}
    <div id="addStoreModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="modal-box w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Enroll New Store</h2>
                <button onclick="closeModal('addStoreModal')" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form action="{{ route('settings.stores.enroll') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Store Code <span class="text-red-500">*</span></label>
                        <input name="store_code" type="text" placeholder="e.g. 2030" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                        <p class="mt-1 text-xs text-gray-400">Numeric RMS store code</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">WooCommerce Short Name <span class="text-red-500">*</span></label>
                        <input name="short_name" type="text" placeholder="e.g. Metro New Store" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Display Name <span class="text-red-500">*</span></label>
                    <input name="display_name" type="text" placeholder="e.g. Metro New Store Cebu" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Warehouse <span class="text-red-500">*</span></label>
                        <select name="warehouse_code" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="" disabled selected>Select warehouse</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->warehouse_code }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Region <span class="text-red-500">*</span></label>
                        <select name="region_code" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="" disabled selected>Select region</option>
                            @foreach ($regions as $r)
                                <option value="{{ $r->region_key }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Go-Live Status</label>
                    <div class="mt-1 flex gap-4">
                        <label class="flex cursor-pointer items-center gap-2"><input type="radio" name="go_live_status" value="active" checked class="text-indigo-600" /><span
                                class="text-sm text-gray-700">Active</span></label>
                        <label class="flex cursor-pointer items-center gap-2"><input type="radio" name="go_live_status" value="pending" class="text-indigo-600" /><span
                                class="text-sm text-gray-700">Not Yet Started</span></label>
                    </div>
                </div>
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-xs text-indigo-800">
                    <strong>ℹ</strong> Saving creates the <code class="rounded bg-indigo-100 px-1">products_{store_code}</code> table in the database automatically.
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('addStoreModal')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700">Enroll Store</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT STORE --}}
    <div id="editStoreModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="modal-box w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Edit Store</h2>
                <button onclick="closeModal('editStoreModal')" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form id="editStoreForm" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <span class="text-xs font-semibold text-gray-500">Store Code</span>
                    <span id="editStoreCodeDisplay" class="rounded bg-gray-200 px-2 py-0.5 font-mono text-sm font-bold text-gray-800"></span>
                    <input type="hidden" name="store_code" id="editStoreCode" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Display Name <span class="text-red-500">*</span></label>
                    <input id="editStoreName" name="display_name" type="text" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">WooCommerce Short Name</label>
                    <input id="editStoreShortName" name="short_name" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Warehouse</label>
                        <select id="editStoreWh" name="warehouse_code"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->warehouse_code }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Region</label>
                        <select id="editStoreRegion" name="region_code"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach ($regions as $r)
                                <option value="{{ $r->region_key }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Status</label>
                    <select id="editStoreStatus" name="go_live_status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="active">Active</option>
                        <option value="pending">Not Yet Started</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('editStoreModal')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ADD / EDIT WAREHOUSE --}}
    <div id="warehouseModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="modal-box w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
                <h2 id="whModalTitle" class="text-xl font-bold text-gray-900">Add Warehouse</h2>
                <button onclick="closeModal('warehouseModal')" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form id="whForm" method="POST" class="space-y-4">
                @csrf
                <div id="whMethodField"></div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Warehouse Code <span class="text-red-500">*</span></label>
                    <input id="whCode" name="warehouse_code" type="text" placeholder="e.g. 80201" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Warehouse Name <span class="text-red-500">*</span></label>
                    <input id="whName" name="warehouse_name" type="text" placeholder="e.g. Silangan Warehouse" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Oracle WMS Facility ID <span class="text-red-500">*</span></label>
                    <input id="whFacility" name="facility_id" type="text" placeholder="e.g. SL" maxlength="5" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm uppercase shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-400">2–5 uppercase letters used in Oracle WMS</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('warehouseModal')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" id="whSubmitBtn" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700">Add Warehouse</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ADD / EDIT REGION --}}
    <div id="regionModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="modal-box w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
                <h2 id="regionModalTitle" class="text-xl font-bold text-gray-900">Add Region</h2>
                <button onclick="closeModal('regionModal')" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <form id="regionForm" method="POST" class="space-y-4">
                @csrf
                <div id="regionMethodField"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Region Key <span class="text-red-500">*</span></label>
                        <input id="regionKey" name="region_key" type="text" placeholder="e.g. ctc" maxlength="10" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 font-mono text-sm lowercase shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-gray-700">Display Label <span class="text-red-500">*</span></label>
                        <input id="regionLabel" name="region_label" type="text" placeholder="e.g. Central Cebu" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-700">Assign Stores</label>
                    <div class="max-h-52 space-y-1.5 overflow-y-auto rounded-lg border border-gray-200 p-3">
                        @foreach ($stores->whereIn('status', ['active', 'pending']) as $s)
                            <label class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 hover:bg-gray-50">
                                <input type="checkbox" name="store_codes[]" value="{{ $s->store_code }}" class="region-store-check rounded text-indigo-600" />
                                <span class="text-sm text-gray-700">{{ $s->display_name }}</span>
                                <span class="ml-auto font-mono text-xs text-gray-400">{{ $s->store_code }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal('regionModal')"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" id="regionSubmitBtn" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700">Add Region</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         SCRIPTS
    ══════════════════════════════════════════════════════ --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        // Make functions globally available for inline onclick handlers		
        window.regionStoreCodes = @json($regionStoreCodes);
        window.openModal = function(id) {
            document.getElementById(id).classList.add('open');
        }

        window.closeModal = function(id) {
            document.getElementById(id).classList.remove('open');
        }

        window.openEditStore = function(code, name, shortName, region, whCode, status) {
            document.getElementById('editStoreCodeDisplay').textContent = code;
            document.getElementById('editStoreCode').value = code;
            document.getElementById('editStoreName').value = name;
            document.getElementById('editStoreShortName').value = shortName;
            document.getElementById('editStoreWh').value = whCode;
            document.getElementById('editStoreRegion').value = region;
            document.getElementById('editStoreStatus').value = status;
            document.getElementById('editStoreForm').action = '/settings/stores/' + code;
            openModal('editStoreModal');
        }

        window.confirmDeactivate = function(code, name) {
            Swal.fire({
                title: 'Deactivate Store?',
                html: `<p class="text-sm text-gray-600">You are about to deactivate:</p>
                   <p class="mt-1 font-semibold text-gray-900">${name} <span class="font-mono text-xs text-gray-500">(${code})</span></p>
                   <p class="mt-2 text-xs text-gray-400">Status set to <strong>inactive</strong>. Product data is preserved.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, deactivate',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch(`/settings/stores/${code}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success || data.message) {
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Store deactivated successfully'
                            });
                            const row = document.querySelector(`#storeTableBody tr[data-code="${code}"]`);
                            if (row) row.remove();
                            filterStoreTable();
                        } else {
                            Toast.fire({
                                icon: 'error',
                                title: data.message || 'Something went wrong'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Toast.fire({
                            icon: 'error',
                            title: 'Something went wrong. Please try again.'
                        });
                    });
            });
        }

        window.openAddWarehouseModal = function() {
            document.getElementById('whModalTitle').textContent = 'Add Warehouse';
            document.getElementById('whSubmitBtn').textContent = 'Add Warehouse';
            document.getElementById('whCode').value = '';
            document.getElementById('whCode').readOnly = false;
            document.getElementById('whName').value = '';
            document.getElementById('whFacility').value = '';
            document.getElementById('whMethodField').innerHTML = '';
            document.getElementById('whForm').action = "{{ route('settings.warehouses.store') }}";
            openModal('warehouseModal');
        }

        window.openEditWarehouse = function(code, name, facility) {
            document.getElementById('whModalTitle').textContent = 'Edit Warehouse';
            document.getElementById('whSubmitBtn').textContent = 'Save Changes';
            document.getElementById('whCode').value = code;
            document.getElementById('whCode').readOnly = true;
            document.getElementById('whName').value = name;
            document.getElementById('whFacility').value = facility;
            document.getElementById('whMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('whForm').action = '/settings/warehouses/' + code;
            openModal('warehouseModal');
        }

        window.openAddRegionModal = function() {
            document.getElementById('regionModalTitle').textContent = 'Add Region';
            document.getElementById('regionSubmitBtn').textContent = 'Add Region';
            document.getElementById('regionKey').value = '';
            document.getElementById('regionKey').readOnly = false;
            document.getElementById('regionLabel').value = '';
            document.getElementById('regionMethodField').innerHTML = '';
            document.getElementById('regionForm').action = "{{ route('settings.regions.store') }}";
            document.querySelectorAll('.region-store-check').forEach(cb => cb.checked = false);
            openModal('regionModal');
        }

        window.openEditRegion = function(key, label) {
            document.getElementById('regionModalTitle').textContent = 'Edit Region · ' + key;
            document.getElementById('regionSubmitBtn').textContent = 'Save Changes';
            document.getElementById('regionKey').value = key;
            document.getElementById('regionKey').readOnly = true;
            document.getElementById('regionLabel').value = label;
            document.getElementById('regionMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('regionForm').action = '/settings/regions/' + key;

            // Safe check for regionStoreCodes
            const codes = (window.regionStoreCodes && window.regionStoreCodes[key]) ? window.regionStoreCodes[key] : [];
            document.querySelectorAll('.region-store-check').forEach(cb => {
                cb.checked = codes.includes(cb.value);
            });
            openModal('regionModal');
        }

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Toast
            window.Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });

            // Modal close on background click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            // Escape key closes modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal.open').forEach(modal => closeModal(modal.id));
                }
            });

            // Tab switching
            document.querySelectorAll('.settings-tab').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all tabs
                    document.querySelectorAll('.settings-tab').forEach(tab => tab.classList.remove('active'));
                    // Add active class to clicked tab
                    this.classList.add('active');
                    // Hide all tab panels
                    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
                    // Show selected tab panel
                    const tabId = 'tab-' + this.dataset.tab;
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Store table filter function
            window.filterStoreTable = function() {
                const search = document.getElementById('storeSearch').value.toLowerCase();
                const region = document.getElementById('storeRegionFilter').value;
                const wh = document.getElementById('storeWhFilter').value;
                const status = document.getElementById('storeStatusFilter').value;
                let visible = 0;

                document.querySelectorAll('#storeTableBody tr').forEach(row => {
                    const code = row.dataset.code || '';
                    const name = row.dataset.name || '';
                    const rowRegion = row.dataset.region || '';
                    const rowWh = row.dataset.wh || '';
                    const rowStatus = row.dataset.status || '';

                    const matchesSearch = !search || code.includes(search) || name.includes(search);
                    const matchesRegion = !region || rowRegion === region;
                    const matchesWh = !wh || rowWh === wh;
                    const matchesStatus = !status || rowStatus === status;

                    const isVisible = matchesSearch && matchesRegion && matchesWh && matchesStatus;
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visible++;
                });

                const totalRows = document.querySelectorAll('#storeTableBody tr').length;
                const countElement = document.getElementById('storeCount');
                if (countElement) {
                    countElement.textContent = visible + ' of ' + totalRows + ' stores';
                }
            }

            // Attach filter event listeners
            const searchInput = document.getElementById('storeSearch');
            const regionFilter = document.getElementById('storeRegionFilter');
            const whFilter = document.getElementById('storeWhFilter');
            const statusFilter = document.getElementById('storeStatusFilter');

            if (searchInput) searchInput.addEventListener('input', filterStoreTable);
            if (regionFilter) regionFilter.addEventListener('change', filterStoreTable);
            if (whFilter) whFilter.addEventListener('change', filterStoreTable);
            if (statusFilter) statusFilter.addEventListener('change', filterStoreTable);

            // Initial filter
            filterStoreTable();

            // Flash messages
            @if (session('success'))
                Toast.fire({
                    icon: 'success',
                    title: @json(session('success'))
                });
            @endif

            @if (session('error'))
                Toast.fire({
                    icon: 'error',
                    title: @json(session('error'))
                });
            @endif

            @if ($errors->any())
                Toast.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: @json($errors->first())
                });
            @endif
        });
    </script>

@endsection
