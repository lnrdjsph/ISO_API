<div class="order-row group relative rounded-xl border border-gray-200 bg-white shadow-md transition-all duration-300 hover:shadow-lg" data-row-index="{{ $index }}">
    <!-- Row Header (Collapsible) -->
    <div class="flex cursor-pointer items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50 px-6 py-3 transition-colors hover:bg-gray-100" data-toggle-row>
        <div class="flex items-center gap-3">
            <div class="item-number flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 text-sm font-bold text-white shadow-sm">
                {{ $index + 1 }}
            </div>
            <h3 class="font-medium text-gray-800">Item No. {{ $index + 1 }}</h3>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs text-gray-400">Click to expand/collapse</span>
            <button type="button" class="toggle-collapse text-gray-400 transition-transform hover:text-gray-600">
                <svg class="collapse-icon h-5 w-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Remove Button -->
    <button type="button"
        class="remove-row absolute right-4 top-4 rounded-full bg-white p-1.5 text-gray-400 opacity-0 transition-all duration-200 hover:bg-red-50 hover:text-red-500 group-hover:opacity-100">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22m-5-4H6a2 2 0 00-2 2v0a2 2 0 002 2h12a2 2 0 002-2v0a2 2 0 00-2-2z" />
        </svg>
    </button>

    <!-- Collapsible Content -->
    <div class="order-content">
        <!-- Editable Side (Visible when expanded) -->
        <div class="editable-side space-y-6 p-6 transition-all duration-300">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Sale Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sale Type</label>
                    <select name="orders[{{ $index }}][sale_type]"
                        class="sale-type mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled {{ empty(old("orders.$index.sale_type", $order['sale_type'] ?? '')) ? 'selected' : '' }}>Select Sale Type</option>
                        <option value="Freebie" {{ old("orders.$index.sale_type", $order['sale_type'] ?? '') === 'Freebie' ? 'selected' : '' }}>Freebie</option>
                        <option value="Discount" {{ old("orders.$index.sale_type", $order['sale_type'] ?? '') === 'Discount' ? 'selected' : '' }}>Discount</option>
                    </select>
                </div>

                <!-- Product Search -->
                <div class="relative md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Main Product
                        <span class="group relative ml-1 inline-block">
                            <svg class="inline h-4 w-4 cursor-help text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16h.01M12 8v4" />
                            </svg>
                            <span class="absolute bottom-full left-1/2 z-10 mb-2 hidden w-64 -translate-x-1/2 rounded-lg bg-gray-900 px-3 py-2 text-xs text-white group-hover:block">
                                Start typing SKU or description (min. 3 characters). Select from dropdown.
                            </span>
                        </span>
                    </label>
                    <input type="text" class="product-search w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter SKU, Description, or Sub-Department" autocomplete="off"
                        value="{{ old("orders.$index.display") }}">
                    <ul class="search-results absolute z-10 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"></ul>
                    <input type="hidden" name="orders[{{ $index }}][sku]" class="sku-hidden" value="{{ old("orders.$index.sku") }}">
                    <input type="hidden" name="orders[{{ $index }}][item_description]" class="desc-hidden" value="{{ old("orders.$index.item_description") }}">
                    <input type="hidden" name="orders[{{ $index }}][display]" value="{{ old("orders.$index.display") }}">
                </div>
            </div>

            <!-- Main Item Fields -->
            <div class="main-item grid grid-cols-2 gap-4 md:grid-cols-3" data-index="{{ $index }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price per Piece</label>
                    <input type="number" step="0.01" name="orders[{{ $index }}][price_per_pc]"
                        value="{{ old("orders.$index.price_per_pc") }}"
                        class="price-per-pc mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="0.00">
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700">Pieces per Case</label>
                    <input type="text" name="orders[{{ $index }}][qty_per_pc]"
                        value="{{ old("orders.$index.qty_per_pc") }}"
                        class="qty-per-pc mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="0">
                    <ul class="qty-results absolute z-10 mt-1 hidden max-h-40 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"></ul>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Case/s Ordered</label>
                    <input type="number" name="orders[{{ $index }}][qty_per_cs]"
                        value="{{ old("orders.$index.qty_per_cs") }}"
                        class="qty-cs mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="0">
                </div>
            </div>

            <!-- Discount Field (Hidden by default) -->
            <div class="discount-field hidden" data-index="{{ $index }}">
                <div class="rounded-lg bg-yellow-50 p-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Discount
                        <span class="group relative ml-1 inline-block">
                            <svg class="inline h-4 w-4 cursor-help text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16h.01M12 8v4" />
                            </svg>
                            <span class="absolute bottom-full left-1/2 z-10 mb-2 hidden w-64 -translate-x-1/2 rounded-lg bg-gray-900 px-3 py-2 text-xs text-white group-hover:block">
                                Use number (e.g., 100) for fixed amount, or number with % (e.g., 10%) for percentage.
                            </span>
                        </span>
                    </label>
                    <input type="text" name="orders[{{ $index }}][discount]"
                        value="{{ old("orders.$index.discount") }}"
                        class="discount mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="0 or 10%">
                </div>
            </div>

            <!-- Freebie Section (Hidden by default) -->
            <div class="freebie-grid hidden space-y-4" data-index="{{ $index }}">
                <div class="rounded-lg bg-green-50 p-4">
                    <h4 class="mb-3 text-sm font-medium text-green-800">Freebie Information</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Scheme</label>
                            <input type="text" name="orders[{{ $index }}][scheme]"
                                value="{{ old("orders.$index.scheme") }}"
                                class="scheme-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="e.g., 1+1">
                        </div>
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700">Freebie Product</label>
                            <input type="text" class="freebie-search w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter Freebie SKU or Description" autocomplete="off"
                                value="{{ old("orders.$index.freebie_sku") && old("orders.$index.freebie_description") ? old("orders.$index.freebie_sku") . ' - ' . old("orders.$index.freebie_description") : '' }}">
                            <ul class="search-results absolute z-10 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"></ul>
                            <input type="hidden" name="orders[{{ $index }}][freebie_sku]" class="freebie-sku-hidden" value="{{ old("orders.$index.freebie_sku") }}">
                            <input type="hidden" name="orders[{{ $index }}][freebie_description]" class="freebie-desc-hidden"
                                value="{{ old("orders.$index.freebie_description") }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Freebie Price per Piece</label>
                            <input type="number" step="0.01" name="orders[{{ $index }}][freebie_price_per_pc]"
                                value="{{ old("orders.$index.freebie_price_per_pc") }}"
                                class="freebie-price-per-pc mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Freebie Pieces per Case</label>
                            <input type="text" name="orders[{{ $index }}][freebie_qty_per_pc]"
                                value="{{ old("orders.$index.freebie_qty_per_pc") }}"
                                class="freebie-qty-per-pc mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="0">
                            <ul class="qty-results absolute z-10 mt-1 hidden max-h-40 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"></ul>
                        </div>
                    </div>
                    <input type="hidden" name="orders[{{ $index }}][freebie_price]" class="freebie-price-hidden">
                    <input type="hidden" name="orders[{{ $index }}][freebies_per_cs]" class="computed-freebies">
                </div>
            </div>

            <!-- Remarks -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Item Comments</label>
                <input type="text" name="orders[{{ $index }}][remarks]"
                    value="{{ old("orders.$index.remarks", 'For RMS Approval') }}"
                    list="remarks-suggestions"
                    class="mt-1 block w-full rounded-lg border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <datalist id="remarks-suggestions">
                    <option value="For RMS Approval">
                    <option value="For SO (Special Order)">
                    <option value="Urgent">
                    <option value="Priority">
                    <option value="Fragile">
                    <option value="Express Delivery">
                </datalist>
            </div>
        </div>

        <!-- Readonly Side (Summary) -->
        <div class="readonly-side hidden border-t border-gray-100 bg-gray-50 p-6 transition-all duration-300 md:block">
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h4 class="mb-3 text-sm font-medium text-gray-700">Item Summary</h4>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Price</span>
                        <span class="font-medium text-gray-900">₱<span class="price-display">0.00</span></span>
                        <input type="hidden" name="orders[{{ $index }}][price]" class="computed-price">
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Freebies</span>
                        <span class="font-medium text-green-600"><span class="freebies-cs-display">0</span> case(s)</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-2">
                        <span class="text-sm font-medium text-gray-700">Total Cases</span>
                        <span class="font-bold text-gray-900"><span class="total-qty-display">0</span></span>
                        <input type="hidden" name="orders[{{ $index }}][total_qty]" class="computed-total-qty">
                    </div>
                    <div class="freebie-block flex hidden justify-between">
                        <span class="text-sm text-green-600">Freebie Amount</span>
                        <span class="font-medium text-green-600">₱<span class="freebie-amount-display">0.00</span></span>
                        <input type="hidden" name="orders[{{ $index }}][freebie_amount]" class="computed-freebie-amount">
                    </div>
                    <div class="flex justify-between rounded-md bg-indigo-50 p-2">
                        <span class="text-sm font-bold text-indigo-700">Total Payable</span>
                        <span class="font-bold text-indigo-700">₱<span class="amount-display">0.00</span></span>
                        <input type="hidden" name="orders[{{ $index }}][amount]" class="computed-amount">
                    </div>
                </div>
                <div class="mt-3 text-xs text-gray-500">
                    <div class="breakdown-price"></div>
                    <div class="breakdown-amount"></div>
                    <div class="breakdown-total-qty"></div>
                    <div class="breakdown-freebie-amount"></div>
                    <div class="breakdown-freebie-qty"></div>
                </div>
            </div>
        </div>
    </div>
</div>
