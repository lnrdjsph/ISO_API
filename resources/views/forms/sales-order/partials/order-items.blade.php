<div class="order-item-form hidden">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
        <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Order Items</h2>
                    <p class="text-sm text-gray-500">Add products, freebies, or discounts</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">Total Products:</span>
                    <span id="product-counter" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">1</span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div id="order-items" class="space-y-6">
                @php $orders = old('orders', [[]]); @endphp
                @foreach ($orders as $i => $order)
                    @include('forms.sales-order.partials.order-row', ['index' => $i, 'order' => $order])
                @endforeach
            </div>
            <button type="button" id="add-row-btn"
                class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-white px-6 py-4 text-gray-600 transition-all duration-200 hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="font-medium">Add Another Item</span>
            </button>
        </div>
    </div>
</div>
