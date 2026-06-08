@php
    $stocksValue  = (int)($product->allocation_per_case ?? 0);
    $casePack     = $product->case_pack ?? '';
    $caseNums     = !empty($casePack)
        ? array_filter(array_map('intval', array_map('trim', explode('|', $casePack))))
        : [];
    $minCase      = !empty($caseNums) ? min($caseNums) : 0;
    $updatedAt    = $product->updated_at ? \Carbon\Carbon::parse($product->updated_at) : null;
    $relTime      = $updatedAt ? $updatedAt->diffForHumans() : null;

    // Stock status for left border on mobile
    if ($stocksValue === 0) {
        $statusColor = '#ef4444';
    } elseif ($minCase > 0 && $stocksValue < $minCase) {
        $statusColor = '#f97316';
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
        <td style="padding:10px 8px 10px 12px;width:36px" data-label="">
            <input type="checkbox"
                class="product-checkbox h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
                value="{{ $product->id }}" id="product-{{ $product->id }}">
        </td>
    @else
        <td style="padding:10px 8px;width:36px;text-align:center;font-size:10px;color:#9ca3af" data-label="#">
            {{ $loop->iteration }}
        </td>
    @endif

    {{-- SKU + Description — primary identity column --}}
    <td style="padding:10px 12px;border-left:2px solid #e0e7ff" colspan="2" data-label="Product">
        <div style="display:flex;flex-direction:column;gap:2px">
            <span style="font-family:var(--font-mono,monospace);font-size:10px;font-weight:700;color:#4f46e5;letter-spacing:.04em">{{ $product->sku }}</span>
            <span style="font-size:11px;font-weight:500;color:#111827;line-height:1.35">{{ $product->description }}</span>
        </div>
    </td>

    {{-- Sub-department --}}
    <td style="padding:10px 10px" data-label="Sub-Dept">
        <div style="display:flex;flex-direction:column;gap:1px">
            <span style="font-size:10px;font-weight:600;color:#374151">{{ $product->department_code }}</span>
            @if ($product->department)
                <span style="font-size:9px;color:#9ca3af;line-height:1.3">{{ str_replace('Basic Grocery 1 - ', '', $product->department) }}</span>
            @endif
        </div>
    </td>

    {{-- Price --}}
    <td style="padding:10px 10px;white-space:nowrap" data-label="Price">
        <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af">
            ₱{{ number_format($product->srp ?? 0, 2) }}
        </span>
    </td>

    {{-- WMS Actual (super admin only) --}}
    @if ($isSuperAdmin)
        <td style="padding:10px 10px;white-space:nowrap;border-left:1px solid #f1f5f9" data-label="WMS Actual">
            <x-stock-badge :value="(int)($product->warehouse_actual_allocation ?? 0)" :case-pack="$casePack" />
        </td>
    @endif

    {{-- WMS Virtual --}}
    <td style="padding:10px 10px;white-space:nowrap;{{ $isSuperAdmin ? '' : 'border-left:1px solid #f1f5f9' }}" data-label="WMS Virtual">
        <x-stock-badge :value="(int)($product->warehouse_allocation ?? 0)" :case-pack="$casePack" />
    </td>

    {{-- Store Stocks --}}
    <td style="padding:10px 10px;border-left:1px solid #f1f5f9" data-label="Stocks">
        <div class="group" style="position:relative;display:inline-block">
            <x-stock-badge :value="$stocksValue" :case-pack="$casePack" />
            <div class="hidden group-hover:block"
                style="position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#0f172a;color:#f8fafc;border-radius:8px;padding:8px 10px;font-size:10px;white-space:nowrap;z-index:50;box-shadow:0 4px 12px rgba(0,0,0,.25);pointer-events:none;min-width:200px">
                <div style="display:flex;gap:12px;margin-bottom:5px">
                    <span><span style="color:#94a3b8">Current: </span><strong>{{ $stocksValue }}</strong></span>
                    <span><span style="color:#94a3b8">Allocated: </span><strong>{{ $product->initial_allocation_per_case ?? 0 }}</strong></span>
                </div>
                <div style="border-top:1px solid #1e293b;padding-top:5px;font-size:9px;color:#64748b">
                    {{ $relTime ? 'Updated ' . $relTime : 'Never updated' }}
                </div>
                <div style="position:absolute;left:50%;top:100%;transform:translateX(-50%);border:5px solid transparent;border-top-color:#0f172a"></div>
            </div>
        </div>
    </td>

    {{-- Store Allocation --}}
    <td style="padding:10px 10px;white-space:nowrap" data-label="Alloc.">
        <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 8px;font-size:10px;font-weight:500;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8">
            {{ $product->initial_allocation_per_case ?? 0 }}
        </span>
    </td>

    {{-- Case Pack --}}
    <td style="padding:10px 10px;white-space:nowrap" data-label="Case Pack">
        @if (!empty($product->case_pack))
            <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 8px;font-size:10px;font-weight:500;background:#f9fafb;border:1px solid #e5e7eb;color:#374151">
                {{ $product->case_pack }}
            </span>
        @else
            <span style="color:#d1d5db;font-size:11px">–</span>
        @endif
    </td>

    {{-- Schemes — de-emphasised, plain text not badges --}}
    <td style="padding:10px 10px;white-space:nowrap;border-left:1px solid #f1f5f9" data-label="C/BC">
        @if (!empty($product->cash_bank_card_scheme))
            <span style="font-size:11px;font-weight:500;color:#6d28d9">{{ $product->cash_bank_card_scheme }}</span>
        @else
            <span style="color:#d1d5db;font-size:11px">–</span>
        @endif
    </td>
    <td style="padding:10px 10px;white-space:nowrap" data-label="PO15%">
        @if (!empty($product->po15_scheme))
            <span style="font-size:11px;font-weight:500;color:#6d28d9">{{ $product->po15_scheme }}</span>
        @else
            <span style="color:#d1d5db;font-size:11px">–</span>
        @endif
    </td>
    <td style="padding:10px 10px;white-space:nowrap" data-label="Discount">
        @if (!empty($product->discount_scheme))
            <span style="font-size:11px;font-weight:500;color:#6d28d9">{{ $product->discount_scheme }}</span>
        @else
            <span style="color:#d1d5db;font-size:11px">–</span>
        @endif
    </td>

    {{-- Freebie SKU --}}
    <td style="padding:10px 12px 10px 10px;border-left:1px solid #f1f5f9" data-label="Freebie">
        @if (!empty($product->freebie_sku))
            <div class="group" style="position:relative;display:inline-block">
                <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 8px;font-size:10px;font-weight:500;background:#faf5ff;border:1px solid #e9d5ff;color:#7e22ce;cursor:default">
                    {{ $product->freebie_sku }}
                </span>
                @if (!empty($product->freebie_description))
                    <div class="hidden group-hover:block"
                        style="position:absolute;bottom:calc(100% + 6px);right:0;background:#0f172a;color:#f8fafc;border-radius:8px;padding:8px 10px;font-size:10px;line-height:1.4;max-width:220px;white-space:normal;word-break:break-word;z-index:50;box-shadow:0 4px 12px rgba(0,0,0,.25);pointer-events:none">
                        {{ $product->freebie_description }}
                        <div style="position:absolute;right:10px;top:100%;border:5px solid transparent;border-top-color:#0f172a"></div>
                    </div>
                @endif
            </div>
        @else
            <span style="color:#d1d5db;font-size:11px">–</span>
        @endif
    </td>
</tr>
