@php
    $stocksValue  = (int)($product->allocation_per_case ?? 0);
    $casePack     = $product->case_pack ?? '';
    $caseNums     = !empty($casePack)
        ? array_filter(array_map('intval', array_map('trim', explode('|', $casePack))))
        : [];
    $minCase      = !empty($caseNums) ? min($caseNums) : 0;
    $updatedAt    = $product->updated_at ? \Carbon\Carbon::parse($product->updated_at) : null;
    $relTime      = $updatedAt ? $updatedAt->diffForHumans() : null;

    // Stock status colour for CSS custom property (used by mobile left-border rule in CSS)
    if ($stocksValue === 0) {
        $statusColor = '#ef4444';
    } elseif ($minCase > 0 && $stocksValue <= $minCase * 10) {
        $statusColor = '#f97316';
    } else {
        $statusColor = '#22c55e';
    }
@endphp

<tr class="animate-fade-in product-row opacity-0 products-tr"
    data-product-id="{{ $product->id }}"
    style="animation-delay:{{ ($loop->index % 25) * 30 }}ms;--status-color:{{ $statusColor }}">

    {{-- Checkbox / row number --}}
    @if (!$isPersonnel)
        <td class="w-9 px-3 py-2.5" data-label="">
            <input type="checkbox"
                class="product-checkbox h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
                value="{{ $product->id }}" id="product-{{ $product->id }}">
        </td>
    @else
        <td class="w-9 px-2 py-2.5 text-center text-[10px] text-gray-400" data-label="#">
            {{ $loop->iteration }}
        </td>
    @endif

    {{-- SKU + Description --}}
    <td class="border-l-2 border-indigo-100 px-3 py-2.5" colspan="2" data-label="Product">
        <div class="flex flex-col gap-0.5">
            <span class="font-mono text-[10px] font-bold tracking-[.04em] text-indigo-600">{{ $product->sku }}</span>
            <span class="text-[11px] font-medium leading-[1.35] text-gray-900">{{ $product->description }}</span>
        </div>
    </td>

    {{-- Sub-department --}}
    <td class="px-2.5 py-2.5" data-label="Sub-Dept">
        <div class="flex flex-col gap-px">
            <span class="text-[10px] font-semibold text-gray-700">{{ $product->department_code }}</span>
            @if ($product->department)
                <span class="text-[9px] leading-[1.3] text-gray-400">{{ str_replace('Basic Grocery 1 - ', '', $product->department) }}</span>
            @endif
        </div>
    </td>

    {{-- Price --}}
    <td class="whitespace-nowrap px-2.5 py-2.5" data-label="Price">
        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-px text-[11px] font-semibold text-blue-800">
            ₱{{ number_format($product->srp ?? 0, 2) }}
        </span>
    </td>

    {{-- WMS Actual (super admin only) --}}
    @if ($isSuperAdmin)
        <td class="whitespace-nowrap border-l border-slate-100 px-2.5 py-2.5" data-label="WMS Actual">
            <x-stock-badge :value="(int)($product->warehouse_actual_allocation ?? 0)" :case-pack="$casePack" />
        </td>
    @endif

    {{-- WMS Virtual --}}
    <td class="whitespace-nowrap px-2.5 py-2.5 {{ $isSuperAdmin ? '' : 'border-l border-slate-100' }}" data-label="WMS Virtual">
        <x-stock-badge :value="(int)($product->warehouse_allocation ?? 0)" :case-pack="$casePack" />
    </td>

    {{-- Store Stocks with tooltip --}}
    <td class="border-l border-slate-100 px-2.5 py-2.5" data-label="Stocks">
        <div class="group relative inline-block">
            <x-stock-badge :value="$stocksValue" :case-pack="$casePack" />
            {{-- Tooltip --}}
            <div class="pointer-events-none absolute bottom-[calc(100%+6px)] left-1/2 z-50 hidden min-w-[200px] -translate-x-1/2 rounded-lg bg-slate-900 px-2.5 py-2 text-[10px] text-slate-100 shadow-lg group-hover:block">
                <div class="mb-1.5 flex gap-3">
                    <span><span class="text-slate-400">Current: </span><strong>{{ $stocksValue }}</strong></span>
                    <span><span class="text-slate-400">Allocated: </span><strong>{{ $product->initial_allocation_per_case ?? 0 }}</strong></span>
                </div>
                <div class="border-t border-slate-700 pt-1.5 text-[9px] text-slate-500">
                    {{ $relTime ? 'Updated ' . $relTime : 'Never updated' }}
                </div>
                <div class="absolute left-1/2 top-full -translate-x-1/2 border-[5px] border-transparent border-t-slate-900"></div>
            </div>
        </div>
    </td>

    {{-- Store Allocation --}}
    <td class="whitespace-nowrap px-2.5 py-2.5" data-label="Alloc.">
        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-px text-[10px] font-medium text-blue-700">
            {{ $product->initial_allocation_per_case ?? 0 }}
        </span>
    </td>

    {{-- Case Pack --}}
    <td class="whitespace-nowrap px-2.5 py-2.5" data-label="Case Pack">
        @if (!empty($product->case_pack))
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-px text-[10px] font-medium text-gray-700">
                {{ $product->case_pack }}
            </span>
        @else
            <span class="text-[11px] text-gray-300">–</span>
        @endif
    </td>

    {{-- Schemes --}}
    <td class="whitespace-nowrap border-l border-slate-100 px-2.5 py-2.5" data-label="C/BC">
        @if (!empty($product->cash_bank_card_scheme))
            <span class="text-[11px] font-medium text-violet-700">{{ $product->cash_bank_card_scheme }}</span>
        @else
            <span class="text-[11px] text-gray-300">–</span>
        @endif
    </td>
    <td class="whitespace-nowrap px-2.5 py-2.5" data-label="PO15%">
        @if (!empty($product->po15_scheme))
            <span class="text-[11px] font-medium text-violet-700">{{ $product->po15_scheme }}</span>
        @else
            <span class="text-[11px] text-gray-300">–</span>
        @endif
    </td>
    <td class="whitespace-nowrap px-2.5 py-2.5" data-label="Discount">
        @if (!empty($product->discount_scheme))
            <span class="text-[11px] font-medium text-violet-700">{{ $product->discount_scheme }}</span>
        @else
            <span class="text-[11px] text-gray-300">–</span>
        @endif
    </td>

    {{-- Freebie SKU with tooltip --}}
    <td class="border-l border-slate-100 px-3 py-2.5" data-label="Freebie">
        @if (!empty($product->freebie_sku))
            <div class="group relative inline-block">
                <span class="inline-flex cursor-default items-center rounded-full border border-purple-200 bg-purple-50 px-2 py-px text-[10px] font-medium text-purple-800">
                    {{ $product->freebie_sku }}
                </span>
                @if (!empty($product->freebie_description))
                    <div class="pointer-events-none absolute bottom-[calc(100%+6px)] right-0 z-50 hidden max-w-[220px] break-words rounded-lg bg-slate-900 px-2.5 py-2 text-[10px] leading-snug text-slate-100 shadow-lg group-hover:block">
                        {{ $product->freebie_description }}
                        <div class="absolute right-2.5 top-full border-[5px] border-transparent border-t-slate-900"></div>
                    </div>
                @endif
            </div>
        @else
            <span class="text-[11px] text-gray-300">–</span>
        @endif
    </td>
</tr>
