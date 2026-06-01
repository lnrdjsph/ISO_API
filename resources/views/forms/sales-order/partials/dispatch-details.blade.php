<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Dispatch Details</h2>
        <p class="text-sm text-gray-500">Delivery or pickup information</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Delivery/Pick-up Date</label>
                <input type="date" name="delivery_date" id="delivery_date" value="{{ old('delivery_date') }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Mode of Dispatching</label>
                <select name="mode_dispatching" id="mode_dispatching"
                    class="required-input dispatch-controller mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="" disabled {{ old('mode_dispatching') ? '' : 'selected' }}>Select Mode</option>
                    <option value="Customer Pick-up" {{ old('mode_dispatching') === 'Customer Pick-up' ? 'selected' : '' }}>Customer Pick-up</option>
                    <option value="Delivery Direct to Customer" {{ old('mode_dispatching') === 'Delivery Direct to Customer' ? 'selected' : '' }}>Delivery Direct to Customer</option>
                </select>
            </div>
        </div>

        <!-- Conditional Delivery Address -->
        <div class="delivery-group mt-6 hidden transition-all duration-300">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
                <h3 class="mb-4 text-sm font-medium text-gray-700">Delivery Address</h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Street Address</label>
                        <input type="text" name="address" id="address" value="{{ old('address') }}"
                            class="delivery-field mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Landmark (Optional)</label>
                        <input type="text" name="landmark" id="landmark" value="{{ old('landmark') }}"
                            class="delivery-field mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
