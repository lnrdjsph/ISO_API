@php
    $selectedPaymentCenter = old('payment_center', $hasRegion || $isSuperAdmin ? '' : $userLocation);
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Payment Information</h2>
        <p class="text-sm text-gray-500">Payment method and details</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Payment Center -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Payment Center</label>
                @if ($isSuperAdmin || $hasRegion)
                    <select name="payment_center" id="payment_center"
                        class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled {{ $selectedPaymentCenter === '' ? 'selected' : '' }}>Select Payment Center</option>
                        @foreach ($dropdownStores as $code => $name)
                            <option value="{{ $code }}" {{ $selectedPaymentCenter === $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="payment_center" value="{{ $selectedPaymentCenter }}">
                    <input type="text" value="{{ $locationMap[$selectedPaymentCenter] ?? $selectedPaymentCenter }}" readonly
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 text-gray-700 shadow-sm">
                @endif
            </div>

            <!-- Mode of Payment -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Mode of Payment</label>
                <select name="mode_payment"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="" disabled {{ old('mode_payment') ? '' : 'selected' }}>Select payment mode</option>
                    <option value="PO15%" {{ old('mode_payment') == 'PO15%' ? 'selected' : '' }}>PO15%</option>
                    <option value="Cash / Bank Card" {{ old('mode_payment') == 'Cash / Bank Card' ? 'selected' : '' }}>Cash / Bank Card</option>
                </select>
            </div>

            <!-- Payment Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Payment Date</label>
                <input type="date" name="payment_date" value="{{ old('payment_date', $currentDate) }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </div>
</div>
