{{--
    Reusable product row.
    Variables: $rowIndex (int|string), $isFirst (bool)
--}}
<div class="product-row relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
    style="border-left: 3px solid #6366f1"
    data-row="{{ $rowIndex }}">

    {{-- Remove button — top right --}}
    <button type="button"
        class="btn-remove-row absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-lg text-gray-300 transition hover:bg-red-50 hover:text-red-500"
        title="Remove" {{ $isFirst ? 'style=display:none' : '' }}>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <div class="p-5">

        {{-- Row number + Primary fields --}}
        <div class="mb-5 flex items-start gap-4">

            {{-- Number badge --}}
            <div class="mt-1 flex-shrink-0">
                <span data-row-badge
                    class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                    {{ $isFirst ? 1 : '__NUM__' }}
                </span>
            </div>

            {{-- SKU + Description --}}
            <div class="min-w-0 flex-1 grid grid-cols-1 gap-3 sm:grid-cols-3">

                {{-- SKU --}}
                <div>
                    <label for="sku_{{ $rowIndex }}" data-row-idx
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sku[]"
                        id="sku_{{ $rowIndex }}" data-row-idx
                        placeholder="e.g. 102806178"
                        required
                        class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 font-mono text-sm font-semibold text-indigo-700 placeholder-gray-400 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                </div>

                {{-- Description --}}
                <div class="sm:col-span-2">
                    <label for="description_{{ $rowIndex }}" data-row-idx
                        class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="description[]"
                        id="description_{{ $rowIndex }}" data-row-idx
                        placeholder="Enter product description"
                        required
                        class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-800 placeholder-gray-400 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                </div>

            </div>
        </div>

        {{-- Divider --}}
        <div class="mb-4 flex items-center gap-3 pl-11">
            <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Optional details</span>
            <div class="flex-1 border-t border-gray-100"></div>
        </div>

        {{-- Secondary fields --}}
        <div class="pl-11 grid grid-cols-2 gap-x-3 gap-y-4 sm:grid-cols-4 lg:grid-cols-7">
            @foreach ([
                ['case_pack',           'Case Pack',   'number', null,   '24',         'Pieces per case'],
                ['srp',                 'SRP (₱)',     'number', '0.01', '99.99',      'Suggested retail price'],
                ['allocation_per_case', 'Allocation',  'number', null,   '100',        'Units per store'],
                ['cbc_scheme',          'C/BC',        'text',   null,   '12+1',       'Cash/Bank/Card scheme'],
                ['po15_scheme',         'PO15%',       'text',   null,   '10+1',       'PO15% scheme'],
                ['discount_scheme',     'Discount',    'text',   null,   '10%',        'Discount scheme'],
                ['freebie_sku',         'Freebie SKU', 'text',   null,   '102806178',  'Freebie product SKU'],
            ] as [$name, $label, $type, $step, $placeholder, $hint])
                <div class="group space-y-1">
                    <label for="{{ $name }}_{{ $rowIndex }}" data-row-idx
                        class="block text-xs font-medium text-gray-500"
                        title="{{ $hint }}">
                        {{ $label }}
                    </label>
                    <input
                        type="{{ $type }}"
                        name="{{ $name }}[]"
                        id="{{ $name }}_{{ $rowIndex }}" data-row-idx
                        placeholder="{{ $placeholder }}"
                        @if($step) step="{{ $step }}" @endif
                        class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-xs text-gray-700 placeholder-gray-300 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                </div>
            @endforeach
        </div>

    </div>
</div>
