@php
    $currentSort      = request('sort', 'description');
    $currentDirection = request('direction', 'asc');

    $sortUrl = fn(string $col): string => route('products.index', array_merge(
        request()->except(['page', 'direction', 'sort']),
        [
            'sort'      => $col,
            'direction' => ($currentSort === $col && $currentDirection === 'asc') ? 'desc' : 'asc',
        ]
    ));

    $sortLinkClass = fn(string $col): string =>
        'inline-flex items-center gap-0.5 text-[9px] font-semibold uppercase tracking-[.07em] no-underline whitespace-nowrap '
        . ($currentSort === $col ? 'text-white' : 'text-slate-400');

    $sortIconClass = fn(string $col): string =>
        'w-2.5 h-2.5 shrink-0 transition-transform duration-150 '
        . ($currentSort === $col && $currentDirection === 'desc' ? 'rotate-180' : '');
@endphp

<thead class="sticky top-0 z-10">
    <tr class="bg-slate-800">
        {{-- Checkbox / Row# --}}
        @if (!$isPersonnel)
            <th class="w-9 px-3 py-2.5">
                <input type="checkbox" id="select-all"
                    class="h-3.5 w-3.5 rounded border-gray-500 bg-gray-700 focus:ring-2 focus:ring-indigo-400">
            </th>
        @else
            <th class="w-9 px-2 py-2.5 text-center text-[9px] font-medium uppercase tracking-[.06em] text-gray-500">#</th>
        @endif

        {{-- SKU + Description --}}
        <th class="min-w-[200px] px-3 py-2.5 text-left" colspan="2">
            <div class="flex items-center gap-2.5">
                <a href="{{ $sortUrl('sku') }}" class="{{ $sortLinkClass('sku') }}">
                    SKU
                    <svg class="{{ $sortIconClass('sku') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </a>
                <span class="text-[9px] text-slate-600">/</span>
                <a href="{{ $sortUrl('description') }}" class="{{ $sortLinkClass('description') }}">
                    Description
                    <svg class="{{ $sortIconClass('description') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </a>
            </div>
        </th>

        {{-- Sub-dept --}}
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">Sub-dept</th>

        {{-- Price --}}
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">Price</th>

        {{-- WMS columns --}}
        @if ($isSuperAdmin)
            <th class="whitespace-nowrap border-l border-slate-700 px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">
                <a href="{{ $sortUrl('warehouse_actual_allocation') }}" class="{{ $sortLinkClass('warehouse_actual_allocation') }}">
                    WMS Actual
                    <svg class="{{ $sortIconClass('warehouse_actual_allocation') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </a>
            </th>
        @endif
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400 {{ $isSuperAdmin ? '' : 'border-l border-slate-700' }}">
            <a href="{{ $sortUrl('warehouse_allocation') }}" class="{{ $sortLinkClass('warehouse_allocation') }}">
                WMS Virtual
                <svg class="{{ $sortIconClass('warehouse_allocation') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </a>
        </th>

        {{-- Store inventory --}}
        <th class="whitespace-nowrap border-l border-slate-700 px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">
            <a href="{{ $sortUrl('allocation_per_case') }}" class="{{ $sortLinkClass('allocation_per_case') }}">
                Stocks
                <svg class="{{ $sortIconClass('allocation_per_case') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </a>
        </th>
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">Alloc.</th>
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">Case pack</th>

        {{-- Schemes --}}
        <th class="whitespace-nowrap border-l border-slate-700 px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-500">C/BC</th>
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-500">PO15%</th>
        <th class="whitespace-nowrap px-2.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-500">Discount</th>

        {{-- Freebie --}}
        <th class="whitespace-nowrap border-l border-slate-700 px-3 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-500">Freebie</th>
    </tr>
</thead>
