<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Customer Information</h2>
        <p class="text-sm text-gray-500">Customer details for this order</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">MBC Card Number</label>
                <input type="text" id="mbc_card_no" name="mbc_card_no" maxlength="16" inputmode="numeric" pattern="\d*"
                    value="{{ old('mbc_card_no') }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Enter 16-digit card number">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer Name</label>
                <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" pattern="[0-9]{11,12}" maxlength="12"
                    value="{{ old('contact_number') }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </div>
</div>
