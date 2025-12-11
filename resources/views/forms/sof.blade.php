@extends('layouts.app')

@section('content')
    @php
        date_default_timezone_set('Asia/Manila');

        // For <input type="date">
        $currentDate = now()->format('Y-m-d');

        // For <input type="datetime-local">
        $currentDateTime = now()->format('Y-m-d\TH:i');
    @endphp

    <div class="">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center space-x-4">
                    <div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Sales Order Form</h1>
                        <p class="mt-1 text-gray-600">Fill out the form to create a new sales order record.</p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 shadow-sm">
                    <p class="font-medium text-green-800">✅ {{ session('success') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                    <p class="font-medium text-red-800">❌ Please fix the following errors:</p>
                    <div class="mt-2 max-h-48 overflow-y-auto pr-2">
                        <ul class="list-inside list-disc space-y-1 text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Order Form -->
            <form
                method="POST"
                action="{{ route('forms.sof_submit') }}"
                id="order-form">
                @csrf
                <div class="order-form rounded-xl bg-white p-6 shadow-lg">
                    <!-- Request Details -->
                    <section class="bg-white p-4">
                        <h2 class="mb-4 text-lg font-semibold">Request Details</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="relative mb-6 w-full">
                                <input
                                    id="sof_id"
                                    value="{{ old('sof_id', $nextSofId) }}"
                                    type="text"
                                    name="sof_id"
                                    readonly
                                    class="peer w-full cursor-not-allowed rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 placeholder-transparent focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    placeholder="Enter SOF Order ID" />
                                <label
                                    for="sof_id"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    SOF Order ID
                                </label>
                            </div>

                            @php
                                $locationMap = [
                                    '4002' => 'F2 - Metro Wholesalemart Colon',
                                    '2010' => 'S10 - Metro Maasin',
                                    '2017' => 'S17 - Metro Tacloban',
                                    '2019' => 'S19 - Metro Bay-Bay',
                                    '3018' => 'F18 - Metro Alang-Alang',
                                    '3019' => 'F19 - Metro Hilongos',
                                    '2008' => 'S8 - Metro Toledo',
                                    '6012' => 'H8 - Super Metro Antipolo',
                                    '6009' => 'H9 - Super Metro Carcar',
                                    '6010' => 'H10 - Super Metro Bogo',
                                ];

                                $userLocation = auth()->user()->user_location ?? null;
                                $mappedLocation = $locationMap[$userLocation] ?? null;
                            @endphp
                            <div class="relative mb-6 w-full">
                                <input
                                    id="requesting_store"
                                    value="{{ old('requesting_store', $userLocation) }}"
                                    type="text"
                                    name="requesting_store"
                                    class="peer hidden w-full cursor-not-allowed rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 placeholder-transparent focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    placeholder="Requesting Store" />
                                <input
                                    id="requesting_store_view"
                                    value="{{ old('requesting_store_view', $mappedLocation) }}"
                                    type="text"
                                    name="requesting_store_view"
                                    readonly
                                    class="peer w-full cursor-not-allowed rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 placeholder-transparent focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    placeholder="Requesting Store" />
                                <label
                                    for="requesting_store"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Requesting Store
                                </label>
                            </div>

                            <div class="relative mb-6 w-full">
                                <input
                                    id="requested_by"
                                    value="{{ old('requested_by', auth()->user()->id ?? 'Personnel Sample') }}"
                                    type="text"
                                    name="requested_by"
                                    readonly
                                    class="peer hidden w-full cursor-not-allowed rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 placeholder-transparent focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    placeholder="Requested By" />
                                <input
                                    id="requested_by_view"
                                    value="{{ old('requested_by_view', auth()->user()->name ?? 'Personnel Sample') }}"
                                    type="text"
                                    name="requested_by_view"
                                    readonly
                                    class="peer w-full cursor-not-allowed rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 placeholder-transparent focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    placeholder="Requested By" />
                                <label
                                    for="requested_by"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Requested By
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="relative w-full">
                                <select
                                    name="channel_order"
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900">
                                    <option
                                        disabled
                                        {{ old('channel_order') ? '' : 'selected' }}
                                        value="">Select channel</option>
                                    @foreach (['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
                                        <option
                                            value="{{ $option }}"
                                            {{ old('channel_order') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <label
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Channel of Order
                                </label>
                            </div>

                            @php
                                $warehouseMap = [
                                    '80141' => 'Silangan Warehouse',
                                    '80181' => 'Bacolod Depot',
                                ];

                                // Warehouse mapping
                                $locationToWarehouse = [
                                    '4002' => '80181',
                                    '2010' => '80181', //bacolod
                                    '2017' => '80181', //bacolod
                                    '2019' => '80181', //bacolod
                                    '3018' => '80181', //bacolod
                                    '3019' => '80141', // Silangan
                                    '2008' => '80141', // Silangan
                                    '6009' => '80141', // Silangan
                                    '6010' => '80141', // Silangan
                                    '6012' => '80141', // Silangan
                                ];

                                $isPersonnel = Str::contains(strtolower(Auth::user()->role), 'personnel');

                                // Determine selected warehouse CODE
                                $selectedWarehouseCode = old('warehouse'); // old input takes priority

                                if (!$selectedWarehouseCode && isset($locationToWarehouse[$userLocation])) {
                                    // Fallback to user location mapping if no old input
                                    $selectedWarehouseCode = $locationToWarehouse[$userLocation];
                                }
                            @endphp

                            <div class="relative mb-6 w-full">
                                @if ($isPersonnel)
                                    <!-- Hidden input to store the actual code -->
                                    <input type="hidden" name="warehouse" value="{{ $selectedWarehouseCode }}" />

                                    <!-- Readonly display showing the name -->
                                    <input
                                        id="warehouse_display"
                                        value="{{ $selectedWarehouseCode ? $warehouseMap[$selectedWarehouseCode] ?? $selectedWarehouseCode : '' }}"
                                        type="text"
                                        readonly
                                        class="peer w-full cursor-not-allowed rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 placeholder-transparent focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        placeholder="Warehouse" />
                                @else
                                    <!-- Default select for non-personnel -->
                                    <select
                                        id="warehouse"
                                        name="warehouse"
                                        class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900">
                                        <option value="" disabled {{ $selectedWarehouseCode == '' ? 'selected' : '' }}>Select Warehouse</option>
                                        @foreach ($warehouseMap as $code => $name)
                                            <option value="{{ $code }}" {{ $selectedWarehouseCode == $code ? 'selected' : '' }}>
                                                {{ $code }} - {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                <label
                                    for="warehouse"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Warehouse
                                </label>
                            </div>








                            <div class="relative w-full">
                                <input
                                    value="{{ old('time_order', date('Y-m-d\TH:i')) }}"
                                    type="datetime-local"
                                    name="time_order"
                                    class="datepicker required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    placeholder=" " />
                                <label
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Date & Time of Order
                                </label>
                            </div>



                        </div>
                    </section>

                    <!-- Customer Info -->
                    <section class="bg-white p-4">
                        <h2 class="mb-4 text-lg font-semibold">Customer Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="relative">
                                <input
                                    id="mbc_card_no"
                                    name="mbc_card_no"
                                    value="{{ old('mbc_card_no') }}"
                                    type="text"
                                    maxlength="16"
                                    inputmode="numeric"
                                    pattern="\d*"
                                    required
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    placeholder="MBC Card Number">
                                <label
                                    for="mbc_card_no"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    MBC Card Number
                                </label>
                            </div>

                            <!-- Customer Name -->
                            <div class="relative">
                                <input
                                    id="customer_name"
                                    name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    type="text"
                                    required
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    placeholder="Customer Name"
                                    maxlength="100">
                                <label
                                    for="customer_name"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Customer Name
                                </label>
                            </div>

                            <!-- Contact Number -->
                            <div class="relative">
                                <input
                                    id="contact_number"
                                    name="contact_number"
                                    value="{{ old('contact_number') }}"
                                    type="tel"
                                    pattern="[0-9]{11,12}"
                                    maxlength="12"
                                    required
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    placeholder="Contact Number">
                                <label
                                    for="contact_number"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Contact Number
                                </label>
                            </div>

                            <!-- Email -->
                            <div class="relative">
                                <input
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    type="email"
                                    required
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    placeholder="Customer Email">
                                <label
                                    for="email"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Cutomer Email
                                </label>
                            </div>
                        </div>
                    </section>
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const mbcInput = document.getElementById('mbc_card_no');
                            const customerName = document.getElementById('customer_name');
                            const contactNumber = document.getElementById('contact_number');
                            const emailInput = document.getElementById('email');


                            mbcInput.addEventListener('input', async function() {
                                const cardNo = mbcInput.value.trim();

                                if (cardNo.length === 16) {
                                    try {
                                        const response = await fetch('{{ route('forms.get_card_info') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                card_no: cardNo
                                            })
                                        });

                                        const data = await response.json();

                                        if (response.ok && data.status === "200") {
                                            // Fill values
                                            customerName.value = data.data.name_on_card ?? '';
                                            contactNumber.value = data.data.mobile_1 ?? '';
                                            emailInput.value = data.data.email_1 ?? '';

                                            // Conditionally apply background highlight
                                            if (customerName.value) customerName.classList.add('bg-indigo-50');
                                            else customerName.classList.remove('bg-indigo-50');

                                            if (contactNumber.value) contactNumber.classList.add('bg-indigo-50');
                                            else contactNumber.classList.remove('bg-indigo-50');

                                            if (emailInput.value) emailInput.classList.add('bg-indigo-50');
                                            else emailInput.classList.remove('bg-indigo-50');

                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Success',
                                                text: 'Customer information filled!',
                                                timer: 1500,
                                                showConfirmButton: false
                                            });
                                        } else {
                                            // Clear values and remove highlight
                                            customerName.value = '';
                                            contactNumber.value = '';
                                            emailInput.value = '';
                                            customerName.classList.remove('bg-indigo-50');
                                            contactNumber.classList.remove('bg-indigo-50');
                                            emailInput.classList.remove('bg-indigo-50');

                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Card Not Found',
                                                text: data.message || `Error ${response.status}: ${response.statusText}`,
                                            });
                                        }


                                    } catch (err) {
                                        console.error(err);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Network/Error',
                                            text: err.message || 'Unknown error occurred',
                                        });
                                    }
                                }
                            });

                            // Trigger warning on blur if input is not 16 digits
                            mbcInput.addEventListener('blur', function() {
                                const cardNo = mbcInput.value.trim();
                                if (cardNo.length > 0 && cardNo.length < 16) {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Incomplete',
                                        text: 'MBC Card number must be 16 digits.',
                                    });
                                    customerName.value = '';
                                    contactNumber.value = '';
                                    customerName.classList.remove('bg-indigo-50');
                                    contactNumber.classList.remove('bg-indigo-50');
                                }
                            });
                        });
                    </script>





                    <!-- Payment Info -->
                    <section class="bg-white p-4">
                        <h2 class="mb-4 text-lg font-semibold">Payment Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            @php
                                use Illuminate\Support\Str;

                                $paymentCenters = [
                                    '4002' => 'F2 - Metro Wholesalemart Colon',
                                    '2010' => 'S10 - Metro Maasin',
                                    '2017' => 'S17 - Metro Tacloban',
                                    '2019' => 'S19 - Metro Bay-Bay',
                                    '3018' => 'F18 - Metro Alang-Alang',
                                    '3019' => 'F19 - Metro Hilongos',
                                    '2008' => 'S8 - Metro Toledo',
                                    '6012' => 'H8 - Super Metro Antipolo',
                                    '6009' => 'H9 - Super Metro Carcar',
                                    '6010' => 'H10 - Super Metro Bogo',
                                ];

                                $userLocation = auth()->user()->user_location ?? null;
                                $isPersonnel = Str::contains(strtolower(auth()->user()->role), 'personnel');

                                // Resolve selected payment center
                                $selectedPaymentCenter = old('payment_center');

                                if (!$selectedPaymentCenter && isset($paymentCenters[$userLocation])) {
                                    $selectedPaymentCenter = $paymentCenters[$userLocation];
                                }
                            @endphp

                            <div class="relative mb-6 w-full">
                                @if ($isPersonnel)
                                    <!-- Hidden value (submitted) -->
                                    <input type="hidden" name="payment_center" value="{{ $selectedPaymentCenter }}">

                                    <!-- Readonly display -->
                                    <input
                                        id="payment_center_display"
                                        type="text"
                                        readonly
                                        value="{{ $selectedPaymentCenter }}"
                                        class="peer w-full rounded-md border border-gray-300 bg-indigo-50 p-3 pt-5 text-sm text-gray-700 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        placeholder="Payment Center">
                                @else
                                    <!-- Editable select -->
                                    <select
                                        name="payment_center"
                                        id="payment_center"
                                        class="required-input peer block w-full appearance-none rounded-md border border-gray-300 bg-indigo-50 px-3 pb-2 pt-6 text-sm text-gray-900 focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900">

                                        <option value="" disabled {{ !$selectedPaymentCenter ? 'selected' : '' }}>
                                            Select Payment Center
                                        </option>

                                        @foreach ($paymentCenters as $code => $name)
                                            <option value="{{ $name }}" {{ $selectedPaymentCenter === $name ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                <label
                                    for="payment_center"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all">
                                    Payment Center
                                </label>
                            </div>







                            <!-- Mode of Payment -->
                            <div class="relative">
                                <select
                                    name="mode_payment"
                                    x-data
                                    x-model="$el.value"
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900">
                                    <option
                                        value=""
                                        disabled
                                        {{ old('mode_payment') ? '' : 'selected' }}>Select or type payment mode</option>
                                    <option
                                        value="PO15%"
                                        {{ old('mode_payment') == 'PO15%' ? 'selected' : '' }}>PO15%</option>
                                    <option
                                        value="Cash / Bank Card"
                                        {{ old('mode_payment') == 'Cash' ? 'selected' : '' }}>Cash / Bank Card</option>
                                </select>
                                <label
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-500"
                                    :class="$el.value ? 'top-1 text-xs text-blue-600' : ''">
                                    Mode of Payment
                                </label>
                            </div>

                            <!-- Payment Date -->
                            <div class="relative">
                                <input
                                    type="date"
                                    name="payment_date"
                                    value="{{ old('payment_date', $currentDate) }}"
                                    x-data="{ value: '{{ old('payment_date') }}' }"
                                    x-model="value"
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900">
                                <label
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                                    :class="value ? 'top-1 text-xs text-blue-600' : ''">
                                    Payment Date
                                </label>
                            </div>

                        </div>
                    </section>

                    <!-- Dispatch Info -->
                    <section class="bg-white p-4">
                        <h2 class="mb-4 text-lg font-semibold">Dispatch Details</h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">


                            <!-- Delivery/Pick-up Date -->
                            <div class="relative">
                                <input
                                    type="date"
                                    name="delivery_date"
                                    id="delivery_date"
                                    value="{{ old('delivery_date') }}"
                                    class="required-input peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900" />
                                <label
                                    for="delivery_date"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Delivery/Pick-up Date
                                </label>
                            </div>
                            <!-- Mode of Dispatching -->
                            <div class="relative">
                                <select
                                    name="mode_dispatching"
                                    id="mode_dispatching"
                                    class="required-input dispatch-controller peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                                    autocomplete="off"
                                    data-hide-value="Customer Pick-up"
                                    data-target=".delivery-group">
                                    <option
                                        value=""
                                        disabled
                                        {{ old('mode_dispatching') ? '' : 'selected' }}>
                                        Select Mode of Dispatch
                                    </option>
                                    <option
                                        value="Customer Pick-up"
                                        {{ old('mode_dispatching') === 'Customer Pick-up' ? 'selected' : '' }}>
                                        Customer Pick-up
                                    </option>
                                    <option
                                        value="Delivery Direct to Customer"
                                        {{ old('mode_dispatching') === 'Delivery Direct to Customer' ? 'selected' : '' }}>
                                        Delivery Direct to Customer
                                    </option>
                                </select>

                                <label
                                    for="mode_dispatching"
                                    class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                    Mode of Dispatching
                                </label>
                            </div>
                        </div>

                        <!-- Delivery Details (Initially Hidden) -->
                        <div class="delivery-group mt-4 hidden max-h-0 overflow-hidden opacity-0 transition-all duration-200 ease-in-out">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="relative">
                                    <input
                                        value="{{ old('address') }}"
                                        type="text"
                                        name="address"
                                        id="address"
                                        autocomplete="street-address"
                                        class="delivery-field peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900" />

                                    <label
                                        value="{{ old('address') }}"
                                        for="address"
                                        class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                        Address
                                    </label>
                                </div>

                                <div class="relative">
                                    <input
                                        value="{{ old('landmark') }}"
                                        type="text"
                                        name="landmark"
                                        id="landmark"
                                        class="delivery-field peer block w-full appearance-none rounded-md border border-gray-300 px-3 pb-2 pt-6 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900" />
                                    <label
                                        for="landmark"
                                        class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                        Landmark
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="order-item-form mt-6 space-y-6 rounded-xl bg-white p-6 shadow-lg">
                    {{-- <div class="order-item-form mt-6 hidden space-y-6 rounded-xl bg-white p-6 shadow-lg"> --}}
                    {{-- test --}}
                    <div class="bg-white pt-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="rounded-lg bg-blue-100 p-2">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 text-blue-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500">Products:</span>
                                <span
                                    id="product-counter"
                                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">1</span>
                            </div>
                        </div>
                    </div>
                    <!-- Order Items Table -->
                    <div class="space-y-6 overflow-x-auto">
                        @php
                            $orders = old('orders', [[]]); // fallback: 1 empty row
                        @endphp
                        <div
                            id="order-items"
                            class="space-y-6">
                            <!-- Sample Row -->
                            @foreach ($orders as $i => $order)
                                <div
                                    class="order-row relative mb-6 max-h-[1000px] space-y-6 overflow-hidden rounded-xl rounded-xl border border border-gray-100 bg-white bg-gradient-to-r from-blue-50/50 to-indigo-50/50 p-6 opacity-100 shadow-sm transition-all duration-300 ease-in-out hover:shadow-md">
                                    <!-- Remove Button (Top-Right Trash Icon) -->
                                    <div class="mb-4 flex items-center space-x-3">
                                        <div
                                            class="item-number flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 text-sm font-semibold text-white">
                                            1
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">Item No. 1</h3>
                                        <button
                                            type="button"
                                            class="toggle-collapse ml-auto text-gray-500 hover:text-gray-700">
                                            <svg
                                                class="collapse-icon h-5 w-5 transition-transform duration-200"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        class="remove-row absolute right-6 top-0 transform text-red-500 transition hover:scale-110 hover:text-red-700">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-6 w-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22m-5-4H6a2 2 0 00-2 2v0a2 2 0 002 2h12a2 2 0 002-2v0a2 2 0 00-2-2z" />
                                        </svg>
                                    </button>

                                    <!-- Combined Grid: Left = Editable Inputs, Right = Readonly Invoice Style -->
                                    <div class="order-content grid grid-cols-1 gap-6 md:grid-cols-3">

                                        <!-- LEFT SIDE: Editable Inputs -->
                                        <div class="editable-side max-h-[2000px] space-y-4 opacity-100 transition-all duration-300 ease-in-out md:col-span-2">
                                            <!-- SKU + Description -->
                                            <div class="product-row grid grid-cols-1 gap-4 md:grid-cols-3">


                                                <!-- Sale Type Dropdown -->
                                                <div class="col-span-1">
                                                    <label class="mb-1 block text-sm font-medium">Sale Type</label>
                                                    <select
                                                        name="orders[{{ $i }}][sale_type]"
                                                        class="sale-type w-full rounded border border-gray-300 p-2 focus:border-gray-300 focus:outline-none focus:ring-gray-900">
                                                        @php
                                                            $selectedSaleType = old("orders.$i.sale_type", $order['sale_type'] ?? '');
                                                        @endphp
                                                        <option
                                                            value=""
                                                            {{ empty($selectedSaleType) ? 'selected' : '' }}
                                                            disabled>Select Sale Type</option>
                                                        <option
                                                            value="Freebie"
                                                            {{ $selectedSaleType === 'Freebie' ? 'selected' : '' }}>Freebie</option>
                                                        <option
                                                            value="Discount"
                                                            {{ $selectedSaleType === 'Discount' ? 'selected' : '' }}>Discount</option>

                                                    </select>
                                                </div>



                                                <!-- Unified Search Input -->
                                                <div
                                                    class="relative col-span-2 w-full"
                                                    x-data="{ value: '{{ old("orders.$i.sku") && old("orders.$i.item_description") ? old("orders.$i.sku") . ' - ' . old("orders.$i.item_description") : '' }}' }">
                                                    <label class="mb-1 flex items-center gap-1 text-sm font-medium">
                                                        Main Product

                                                        <div class="group relative">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 cursor-pointer text-gray-400"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <circle
                                                                    cx="12"
                                                                    cy="12"
                                                                    r="10"
                                                                    stroke-width="2" />
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 16h.01M12 8v4" />
                                                            </svg>

                                                            <span
                                                                class="absolute left-full top-1/2 z-20 ml-2 hidden w-72 -translate-y-1/2 whitespace-normal rounded bg-white px-3 py-2 text-sm text-gray-800 shadow-lg group-hover:block">
                                                                <b>How to select a product:</b><br><br>
                                                                • Start typing the <b>SKU</b> (e.g., <code>12345</code>) or <b>description</b> (e.g., <code>Coke 1.5L</code>).<br>
                                                                • A dropdown list will appear with matching registered products.<br>
                                                                • Click on a product from the list to select it.<br><br>
                                                                <i>Tip:</i> For faster results, type at least <b>3 characters</b>.
                                                            </span>
                                                        </div>
                                                    </label>


                                                    <input
                                                        type="text"
                                                        x-model="value"
                                                        :class="value === '' ? 'bg-yellow-50' : 'bg-white'"
                                                        class="product-search w-full rounded border border-gray-300 p-2 focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="Enter SKU, Description, or Sub-Department"
                                                        autocomplete="off"
                                                        name="orders[{{ $i }}][display]"
                                                        value="{{ old("orders.$i.display") }}">

                                                    <ul class="search-results absolute z-10 mt-1 hidden max-h-60 overflow-y-auto rounded border border-gray-200 bg-white shadow"></ul>

                                                    <!-- Hidden Fields -->
                                                    <input
                                                        type="hidden"
                                                        name="orders[{{ $i }}][sku]"
                                                        class="sku-hidden"
                                                        value="{{ old("orders.$i.sku") }}">
                                                    <input
                                                        type="hidden"
                                                        name="orders[{{ $i }}][item_description]"
                                                        class="desc-hidden"
                                                        value="{{ old("orders.$i.item_description") }}">
                                                </div>



                                            </div>


                                            <div
                                                class="main-item grid grid-cols-1 gap-4 md:grid-cols-3"
                                                data-index="{{ $i }}">

                                                <!-- Price/PCS -->
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Price/PC</label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        name="orders[{{ $i }}][price_per_pc]"
                                                        value="{{ old("orders.$i.price_per_pc") }}"
                                                        class="price-per-pc w-full rounded border border-gray-300 p-2 text-right focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        style="text-align: left;"
                                                        placeholder="0.00" />
                                                </div>

                                                <!-- QTY/PCS -->
                                                <div class="relative">
                                                    <label class="mb-1 block text-sm font-medium">QTY/PC</label>
                                                    <input
                                                        type="text"
                                                        name="orders[{{ $i }}][qty_per_pc]"
                                                        value="{{ old("orders.$i.qty_per_pc") }}"
                                                        class="qty-per-pc w-full rounded border border-gray-300 p-2 text-right focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        style="text-align: left;"
                                                        placeholder="0" />
                                                    <ul class="glow-effect qty-results absolute z-10 mt-1 flex hidden flex-wrap gap-2 rounded border border-gray-200 bg-white p-2 shadow"></ul>

                                                </div>

                                                <!-- QTY/CS -->
                                                <div x-data="{ qty: '{{ old("orders.$i.qty_per_cs") }}' }">
                                                    <label class="mb-1 block text-sm font-medium">QTY/CS</label>
                                                    <input
                                                        type="number"
                                                        x-model="qty"
                                                        :class="qty === '' ? 'bg-yellow-50' : 'bg-white'"
                                                        name="orders[{{ $i }}][qty_per_cs]"
                                                        class="qty-cs w-full rounded border border-gray-300 p-2 text-right focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="0"
                                                        style="text-align: left;"
                                                        value="{{ old("orders.$i.qty_per_cs") }}">
                                                </div>
                                                <!-- Discount Field -->
                                                <div
                                                    class="discount-field hidden"
                                                    data-index="{{ $i }}">
                                                    <label class="mb-1 flex items-center gap-1 text-sm font-medium">
                                                        Discount

                                                        <div class="group relative">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 cursor-pointer text-gray-400"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <circle
                                                                    cx="12"
                                                                    cy="12"
                                                                    r="10"
                                                                    stroke-width="2" />
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 16h.01M12 8v4" />
                                                            </svg>

                                                            <span
                                                                class="z-100 absolute left-full top-1/2 ml-2 hidden w-72 -translate-y-1/2 whitespace-normal rounded bg-white px-3 py-2 text-sm text-gray-800 shadow-lg group-hover:block">
                                                                <b>How to apply discount:</b><br><br>
                                                                • Enter a number <b>without %</b> → Deducts that <u>exact amount</u>.<br>
                                                                &nbsp;&nbsp;Example: <code>100</code> = ₱100 off<br><br>
                                                                • Enter a number <b>with %</b> → Deducts that <u>percentage of the item price</u>.<br>
                                                                &nbsp;&nbsp;Example: <code>10%</code> = 10% off the price<br><br>
                                                                <i>Tip:</i> Use whole numbers only (no symbols except %).
                                                            </span>
                                                        </div>
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="orders[{{ $i }}][discount]"
                                                        value="{{ old("orders.$i.discount") }}"
                                                        x-model="discount"
                                                        :class="discount === '' ? 'bg-yellow-50' : 'bg-white'"
                                                        class="discount w-full rounded border border-gray-300 p-2 focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="0"
                                                        min="0"
                                                        max="100"
                                                        step="0.01">
                                                </div>
                                            </div>


                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">



                                            </div>
                                            <!-- Freebie Section (Hidden by Default) -->
                                            <div
                                                class="freebie-grid grid hidden grid-cols-1 gap-4 md:grid-cols-3"
                                                data-index="{{ $i }}">

                                                {{-- Scheme --}}
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Scheme</label>
                                                    <input
                                                        type="text"
                                                        name="orders[{{ $i }}][scheme]"
                                                        value="{{ old("orders.$i.scheme") }}"
                                                        class="scheme-input w-full rounded border border-gray-300 p-2 focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="0" />
                                                </div>
                                                <!-- Freebie Product -->
                                                <div class="relative col-span-2 w-full">
                                                    <label class="mb-1 block text-sm font-medium">Freebie Product</label>
                                                    <input
                                                        type="text"
                                                        class="freebie-search w-full rounded border border-gray-300 p-2 focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="Enter Freebie SKU or Description"
                                                        autocomplete="off"
                                                        value="{{ old("orders.$i.freebie_sku") && old("orders.$i.freebie_description") ? old("orders.$i.freebie_sku") . ' - ' . old("orders.$i.freebie_description") : '' }}" />
                                                    <ul class="search-results absolute z-10 mt-1 hidden max-h-60 overflow-y-auto rounded border border-gray-200 bg-white shadow"></ul>

                                                    <input
                                                        type="hidden"
                                                        name="orders[{{ $i }}][freebie_sku]"
                                                        class="freebie-sku-hidden"
                                                        value="{{ old("orders.$i.freebie_sku") }}">
                                                    <input
                                                        type="hidden"
                                                        name="orders[{{ $i }}][freebie_description]"
                                                        class="freebie-desc-hidden"
                                                        value="{{ old("orders.$i.freebie_description") }}">
                                                </div>

                                                <!-- Freebies Price/PCS -->
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Freebie Price/PC</label>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        name="orders[{{ $i }}][freebie_price_per_pc]"
                                                        value="{{ old("orders.$i.freebie_price_per_pc") }}"
                                                        class="freebie-price-per-pc w-full rounded border border-gray-300 p-2 text-left focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="0.00" />
                                                </div>
                                                <!-- Frebies Price -->
                                                <div class="hidden">
                                                    <label class="mb-1 block text-sm font-medium">Freebie Price</label>
                                                    <input
                                                        type="hidden"
                                                        step="0.01"
                                                        name="orders[{{ $i }}][freebie_price]"
                                                        value="{{ old("orders.$i.freebie_price") }}"
                                                        class="w-full rounded border border-gray-300 p-2 text-left focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        placeholder="0.00" />
                                                </div>

                                                <!-- Freebies QTY/PCS -->
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Freebie QTY/PC</label>
                                                    <input
                                                        type="text"
                                                        name="orders[{{ $i }}][freebie_qty_per_pc]"
                                                        value="{{ old("orders.$i.freebie_qty_per_pc") }}"
                                                        class="qty-pcs freebie-qty-per-pc w-full rounded border border-gray-300 p-2 text-right focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        style="text-align: left;"
                                                        placeholder="0" />
                                                    <ul class="glow-effect qty-results absolute z-10 mt-1 flex hidden flex-wrap gap-2 rounded border border-gray-200 bg-white p-2 shadow"></ul>
                                                </div>

                                                <!-- Freebie QTY/CS-->
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Freebie QTY/CS</label>
                                                    <input
                                                        type="text"
                                                        name="orders[{{ $i }}][freebies_per_cs]"
                                                        value="{{ old("orders.$i.freebies_per_cs") }}"
                                                        class="computed-freebies w-full rounded border border-gray-300 bg-green-50 p-2 text-right focus:border-gray-300 focus:outline-none focus:ring-gray-900"
                                                        style="text-align: left; cursor: not-allowed;"
                                                        placeholder="0"
                                                        readonly />
                                                </div>
                                            </div>




                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-1">

                                                <!-- Remarks Dropdown -->
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium">Remarks</label>
                                                    @php
                                                        // Default now set to "For SO (Special Order)"
                                                        $selectedRemarks = old("orders.$i.remarks", $order['remarks'] ?? 'For RMS Approval');
                                                    @endphp

                                                    <select
                                                        name="orders[{{ $i }}][remarks]"
                                                        class="w-full rounded border border-gray-300 p-2 focus:border-gray-300 focus:outline-none focus:ring-gray-900">

                                                        <option value="" disabled {{ $selectedRemarks === '' ? 'selected' : '' }}>
                                                            Select remarks
                                                        </option>
                                                        <option value="For RMS Approval"
                                                            {{ $selectedRemarks === 'For RMS Approval' ? 'selected' : '' }}>
                                                            For RMS Approval
                                                        </option>

                                                        <option value="For SO (Special Order)"
                                                            {{ $selectedRemarks === 'For SO (Special Order)' ? 'selected' : '' }}>
                                                            For SO (Special Order)
                                                        </option>


                                                    </select>


                                                </div>

                                                <!-- Store Order No. -->
                                                {{-- <div>
                                                    <label class="mb-1 block text-sm font-medium">Store Order No. (SO#)</label>
                                                    <input
                                                        type="number"
                                                        name="orders[{{ $i }}][store_order_no]"
                                                        value="{{ old("orders.$i.store_order_no") }}"
                                                        class="w-full appearance-none rounded border border-gray-300 p-2 [-moz-appearance:textfield] focus:border-gray-300 focus:outline-none focus:ring-gray-900 [&::-webkit-inner-spin-button]:m-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-outer-spin-button]:appearance-none"
                                                        placeholder="Enter Store Order No."
                                                        inputmode="numeric"
                                                        pattern="\d*"
                                                        min="0"
                                                        step="0" />
                                                </div> --}}
                                            </div>
                                        </div>

                                        <!-- RIGHT SIDE: Readonly Invoice Style -->
                                        <div
                                            class="readonly-side flex h-full w-full flex-col justify-between rounded border border-gray-200 bg-white p-4 pb-0 transition-all duration-300 md:col-span-1">
                                            <div class="space-y-2">

                                                <!-- Price -->
                                                <div class="flex items-start justify-between">
                                                    <label class="block text-sm text-gray-600">Price</label>
                                                    <div class="text-right font-medium text-black">
                                                        <span class="price-display">0.00</span>
                                                        <input
                                                            type="hidden"
                                                            name="orders[{{ $i }}][price]"
                                                            value="{{ old("orders.$i.price") }}"
                                                            class="computed-price" />
                                                        <div class="breakdown-price text-xs text-gray-500"></div>
                                                    </div>
                                                </div>

                                                <!-- Total QTY -->
                                                <div class="flex items-start justify-between">
                                                    <label class="block text-sm text-gray-600">Total QTY/CS</label>
                                                    <div class="text-right font-medium text-black">
                                                        <span class="total-qty-display">0</span>
                                                        <input
                                                            type="hidden"
                                                            name="orders[{{ $i }}][total_qty]"
                                                            value="{{ old("orders.$i.total_qty") }}"
                                                            class="computed-total-qty" />
                                                        <div class="breakdown-total-qty text-xs text-gray-500"></div>
                                                    </div>
                                                </div>

                                                {{-- 
																								<!-- Freebies/CS -->
																								<div class="flex justify-between items-start">
																										<label class="block text-sm text-green-600">Freebies</label>
																										<div class="text-right text-black font-medium">
																												<span class="freebies-cs-display block text-green-600">0</span>
																												<input type="hidden" name="orders[{{ $i }}][freebies_per_cs]"
																														value="{{ old("orders.$i.freebies_per_cs") }}"  
																														class="computed-freebies" />
																										</div>
																								</div> --}}



                                            </div>
                                            <div>
                                                <!-- Freebie Amount -->
                                                <div class="freebie-block mx-[-1rem] flex items-start justify-between bg-green-50 px-4 py-2">
                                                    <label class="block text-sm text-green-600">Freebie Amount</label>
                                                    <div class="text-right font-medium text-black">
                                                        <span class="freebie-amount-display block text-green-600">0.00</span>
                                                        <input
                                                            type="hidden"
                                                            name="orders[{{ $i }}][freebie_amount]"
                                                            class="computed-freebie-amount" />
                                                        <div class="breakdown-freebie-amount text-xs text-gray-500"></div>
                                                    </div>
                                                </div>

                                                <script>
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        document.querySelectorAll('.freebie-block').forEach(function(container) {
                                                            const input = container.querySelector('.computed-freebie-amount');
                                                            const span = container.querySelector('.freebie-amount-display');

                                                            // Function to toggle visibility
                                                            function toggleVisibility() {
                                                                const amount = parseFloat(input.value) || parseFloat(span.textContent) || 0;
                                                                container.classList.toggle('hidden', amount === 0);
                                                            }

                                                            // Initial check
                                                            toggleVisibility();

                                                            // Listen for input changes
                                                            input.addEventListener('input', toggleVisibility);

                                                            // Observe changes in span text
                                                            const observer = new MutationObserver(toggleVisibility);
                                                            observer.observe(span, {
                                                                childList: true,
                                                                characterData: true,
                                                                subtree: true
                                                            });
                                                        });
                                                    });
                                                </script>

                                                <!-- Total Amount -->
                                                <div class="mx-[-1rem] flex items-start justify-between bg-blue-50 px-4 py-2">
                                                    <label class="block text-sm text-indigo-600">Total Amount</label>
                                                    <div class="text-right font-bold text-black text-blue-600">
                                                        <span class="amount-display">0.00</span>
                                                        <input
                                                            type="hidden"
                                                            name="orders[{{ $i }}][amount]"
                                                            value="{{ old("orders.$i.amount") }}"
                                                            class="computed-amount" />
                                                        <div class="breakdown-amount text-xs text-gray-500"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <table
                                        id="summary-table"
                                        class="summary-table hidden w-full overflow-hidden rounded border border-gray-300 bg-white text-sm shadow-sm">
                                        <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                                            <tr>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-left">SKU</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-left">Item Description</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-center">Scheme</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-center">Price/PC</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-center">Price</th>
                                                <th
                                                    colspan="4"
                                                    class="border px-2 py-1 text-center">Order in Cases</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-center">discount</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-center">Amount</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-left">Remarks</th>
                                                <th
                                                    rowspan="2"
                                                    class="border px-2 py-1 text-left">Store Order No.</th>
                                            </tr>
                                            <tr>
                                                <th class="border px-2 py-1 text-center">QTY/PC</th>
                                                <th class="border px-2 py-1 text-center">QTY/CS</th>
                                                <th class="border px-2 py-1 text-center">Freebies</th>
                                                <th class="border px-2 py-1 text-center">Total QTY</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            id="summary-body"
                                            class="summary-body divide-y divide-gray-200">
                                            <!-- JS will inject rows here -->
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>

                        <!-- Add Row Button -->
                        <div class="mt-8">
                            <button
                                type="button"
                                id="add-row-btn"
                                class="animate-surpriseBounce group inline-flex w-full items-center justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-3 text-gray-600 transition-all duration-200 hover:border-gray-400 hover:bg-blue-50 hover:text-blue-600">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="mr-2 h-5 w-5 transition-transform duration-200 group-hover:scale-110"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="font-medium">Add Another Item</span>
                            </button>
                        </div>
                    </div>

                    <!-- Totals and Signatures -->
                    {{-- <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-6 md:space-y-0">
												<div class="text-xl font-bold text-indigo-600">₱ 0.00</div>
												<div class="space-y-4 text-sm text-gray-600">
												<div>
														<p><strong>EMMA M. BARING 02/17/25</strong></p>
														<p>MS. MARINETH PAITON</p>
												</div>
												<div class="flex space-x-12">
														<div>
														<p>Prepared by:</p>
														<p class="text-xs">(printed name, signature & date)</p>
														</div>
														<div>
														<p>Approved by:</p>
														<p class="text-xs">(printed name, signature & date)</p>
														</div>
												</div>
												</div>
										</div> --}}

                    <!-- Submit Button -->
                    <button
                        id="submitBtn"
                        type="submit"
                        class="flex items-center justify-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 font-medium text-white transition transition duration-1000 hover:scale-[1.02] hover:scale-[1.02] hover:shadow-lg">
                        Submit Order
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- <script>
document.getElementById('order-form').addEventListener('submit', function (e) {
    e.preventDefault(); // prevent actual submission

    const form = e.target;
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
        // group array-style names like orders[0][sku]
        if (key.includes('[')) {
            const matches = key.match(/^([^\[]+)\[([0-9]+)\]\[([^\]]+)\]$/);
            if (matches) {
                const group = matches[1];
                const index = matches[2];
                const field = matches[3];

                if (!data[group]) data[group] = [];
                if (!data[group][index]) data[group][index] = {};
                data[group][index][field] = value;
            } else {
                data[key] = value;
            }
        } else {
            data[key] = value;
        }
    }

    console.log('Form Data:', data);
});
</script> --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.classList.remove('hover:shadow-lg', 'hover:scale-[1.02]');
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

            submitBtn.innerHTML = `
      <svg class="w-5 h-5 mr-2 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
           viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      Processing…
    `;
        });
        if (!window.orderScriptInitialized) {
            window.orderScriptInitialized = true;
            let rowIndex = 1;

            document.addEventListener('DOMContentLoaded', function() {
                const addButton = document.getElementById('add-row-btn');
                const productCounter = document.getElementById('product-counter');

                // Only add event listener once
                if (addButton && !addButton.hasAttribute('data-listener-attached')) {
                    addButton.setAttribute('data-listener-attached', 'true');

                    addButton.addEventListener('click', function() {
                        const container = document.getElementById('order-items');
                        const newRow = container.children[0].cloneNode(true);
                        console.log('Adding new order row (with highlight reset)');
                        // Reset input values and update input names
                        newRow.querySelectorAll('input').forEach(input => {
                            input.value = '';
                            input.name = input.name.replace(/\[\d+]/g, `[${rowIndex}]`);
                            input.removeAttribute('data-selected');
                        });

                        newRow.querySelectorAll('select').forEach(select => {
                            select.name = select.name.replace(/\[\d+]/g, `[${rowIndex}]`);
                        });

                        // Reset remarks select to default
                        const remarksSelect = newRow.querySelector('select[name^="orders"][name$="[remarks]"]');
                        if (remarksSelect) {
                            remarksSelect.value = "For RMS Approval";
                            remarksSelect.name = remarksSelect.name.replace(/\[\d+]/g, `[${rowIndex}]`);
                        }

                        // Reset sale type select
                        const saleTypeSelect = newRow.querySelector('select[name^="orders"][name$="[sale_type]"]');
                        if (saleTypeSelect) {
                            saleTypeSelect.selectedIndex = 0; // Reset to first option
                            saleTypeSelect.name = saleTypeSelect.name.replace(/\[\d+]/g, `[${rowIndex}]`);
                        }

                        // IMPORTANT: Update data-index attributes for new row
                        newRow.querySelectorAll('[data-index]').forEach(element => {
                            element.setAttribute('data-index', rowIndex);
                        });

                        // Reset selects and update names

                        // Reset output display spans
                        newRow.querySelector('.price-display').textContent = '0.00';
                        newRow.querySelector('.amount-display').textContent = '0.00';
                        newRow.querySelector('.total-qty-display').textContent = '0';

                        // Reset summary table
                        const summaryTableBody = newRow.querySelector('.summary-body');
                        if (summaryTableBody) {
                            summaryTableBody.innerHTML = '';
                        }
                        newRow.querySelector('.breakdown-price').textContent = '';
                        newRow.querySelector('.breakdown-amount').textContent = '';

                        // Hide any open dropdowns
                        newRow.querySelectorAll('.sku-results, .desc-results').forEach(ul => {
                            ul.innerHTML = '';
                            ul.classList.add('hidden');
                        });

                        // Reset sections to hidden state
                        const freebieSection = newRow.querySelector('.freebie-grid');
                        const discountField = newRow.querySelector('.discount-field');
                        if (freebieSection) freebieSection.classList.add('hidden');
                        if (discountField) discountField.classList.add('hidden');

                        // Apply initial hidden state for animation
                        newRow.classList.add('overflow-hidden', 'transition-all', 'duration-300', 'ease-in-out');
                        newRow.classList.remove('max-h-[1000px]', 'opacity-100', 'py-4', 'mb-6');
                        newRow.classList.add('max-h-0', 'opacity-0', 'py-0', 'mb-0');

                        container.appendChild(newRow);

                        // Force reflow to properly trigger animation
                        void newRow.offsetWidth;

                        // Trigger the visible state
                        newRow.classList.replace('max-h-0', 'max-h-[1000px]');
                        newRow.classList.replace('opacity-0', 'opacity-100');
                        newRow.classList.replace('py-0', 'py-4');
                        newRow.classList.replace('mb-0', 'mb-6');

                        rowIndex = document.querySelectorAll('.order-row').length;

                        updateRemoveButtonsState();
                        updateCounter();
                        updateRowNumbers();
                        attachCollapseListener(newRow);

                        // IMPORTANT: Attach sale type listener to new row
                        attachSaleTypeListener(newRow);
                    });
                }

                // Remove row functionality
                document.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-row')) {
                        const allRows = document.querySelectorAll('.order-row');
                        if (allRows.length > 1) {
                            const row = e.target.closest('.order-row');

                            // Animate to hidden state
                            row.classList.replace('max-h-[1000px]', 'max-h-0');
                            row.classList.replace('opacity-100', 'opacity-0');
                            row.classList.replace('py-4', 'py-0');
                            row.classList.replace('mb-6', 'mb-0');

                            // Remove after transition
                            row.addEventListener('transitionend', () => {
                                row.remove();
                                updateRemoveButtonsState();
                                updateCounter();
                                updateRowNumbers();
                            }, {
                                once: true
                            });
                        }
                    }
                });

                // Initialize existing rows
                document.querySelectorAll('.order-row').forEach(row => {
                    attachCollapseListener(row);
                    attachSaleTypeListener(row);
                    calculateRowTotals(row);

                    // 🆕 Initialize discount field visibility on page load
                    const saleTypeSelect = row.querySelector('.sale-type');
                    const discountField = row.querySelector('.discount-field');

                    if (discountField) {
                        // Hide by default, only show if sale type is "Discount"
                        if (saleTypeSelect && saleTypeSelect.value === 'Discount') {
                            discountField.classList.remove('hidden');
                        } else {
                            discountField.classList.add('hidden');
                        }
                    }
                });

                // Initialize remove buttons state and counter
                updateRemoveButtonsState();
                updateCounter();
                updateRowNumbers();

                // Mode payment change listener
                document.querySelectorAll('.mode-payment').forEach(select => {
                    select.addEventListener('change', function() {
                        document.querySelectorAll('.order-row').forEach(row => {
                            calculateRowTotals(row);
                            const selectedSKU = row.querySelector('.sku-hidden')?.value;
                            if (selectedSKU) {
                                const matchedProduct = document.querySelector(`.product-item[data-sku="${selectedSKU}"]`);
                                if (matchedProduct) {
                                    matchedProduct.click();
                                }
                            }
                        });
                    });
                });

                // Form field highlighting
                const orderForm = document.querySelector(".order-form");
                if (orderForm) {
                    const fields = orderForm.querySelectorAll("input, select, textarea");

                    function toggleHighlight(el) {
                        if (el.value && (el.value.trim() !== "" || el.value === "0")) {
                            el.classList.add("bg-indigo-50");
                        } else {
                            el.classList.remove("bg-indigo-50");
                        }
                    }

                    fields.forEach(el => {
                        toggleHighlight(el);
                        el.addEventListener("input", () => toggleHighlight(el));
                        el.addEventListener("change", () => toggleHighlight(el));
                    });
                }
            });
        }
        // Event listeners for calculations
        ['input', 'change'].forEach(eventType => {
            document.addEventListener(eventType, function(e) {
                if (!e.target.closest('.order-row')) return;
                const row = e.target.closest('.order-row');
                calculateRowTotals(row);
            });
        });

        // Disable delete if only one row
        function updateRemoveButtonsState() {
            const allRows = document.querySelectorAll('.order-row');
            allRows.forEach(row => {
                const removeButton = row.querySelector('.remove-row');
                if (allRows.length === 1) {
                    removeButton.classList.add('opacity-25', 'cursor-not-allowed');
                } else {
                    removeButton.classList.remove('opacity-25', 'cursor-not-allowed');
                }
            });
        }

        // Update product counter badge
        function updateCounter() {
            const allRows = document.querySelectorAll('.order-row');
            const productCounter = document.getElementById('product-counter');
            if (productCounter) {
                productCounter.textContent = allRows.length;
            }
        }

        // Update row labels: Product #1, Product #2, etc.
        function updateRowNumbers() {
            const allRows = document.querySelectorAll('.order-row');
            allRows.forEach((row, index) => {
                const numberBadge = row.querySelector('.item-number');
                const title = row.querySelector('h3');
                const productNumber = index + 1;

                if (numberBadge) numberBadge.textContent = productNumber;
                if (title) title.textContent = `Item No. ${productNumber}`;
            });
        }

        function attachCollapseListener(row) {
            const toggle = row.querySelector('.toggle-collapse');
            if (!toggle) return;

            toggle.addEventListener('click', () => {
                const editable = row.querySelector('.editable-side');
                const readonly = row.querySelector('.readonly-side');
                const table = row.querySelector('#summary-table');
                const icon = toggle.querySelector('.collapse-icon');

                if (!editable || !readonly || !table || !icon) return;

                const isCollapsed = editable.classList.contains('hidden');

                if (isCollapsed) {
                    // Expand: show form, hide table
                    editable.classList.remove('hidden');
                    editable.classList.add('block', 'max-h-[2000px]', 'opacity-100');

                    readonly.classList.remove('hidden');
                    readonly.classList.add('block', 'md:col-span-1');

                    table.classList.add('hidden');
                } else {
                    // Collapse: hide form, show table
                    editable.classList.remove('block', 'max-h-[2000px]', 'opacity-100');
                    editable.classList.add('hidden');

                    readonly.classList.remove('block', 'md:col-span-1');
                    readonly.classList.add('hidden');

                    table.classList.remove('hidden');
                }

                icon.classList.toggle('rotate-180');
            });
        }

        // Individual row sale type listener
        function attachSaleTypeListener(row) {
            const select = row.querySelector('.sale-type');
            if (!select) return;

            let lastValue = select.value; // keep track of previous sale type

            select.addEventListener('change', function() {
                const indexMatch = this.name.match(/orders\[(\d+)\]/);
                if (!indexMatch) return;

                const freebieSection = row.querySelector('.freebie-grid');
                const mainItemSection = row.querySelector('.main-item');
                const discountField = row.querySelector('.discount-field');

                if (!freebieSection || !mainItemSection || !discountField) return;

                // 🛑 Only clear when switching away from Freebie/Discount
                if (lastValue !== this.value) {
                    if (this.value !== 'Freebie') {
                        freebieSection.querySelectorAll('input').forEach(i => {
                            if (i.type !== 'hidden') i.value = '';
                        });
                    }
                    if (this.value !== 'Discount') {
                        const discountInput = discountField.querySelector('input');
                        if (discountInput) discountInput.value = '';
                    }
                }

                // 🔄 Always hide first
                freebieSection.classList.add('hidden');
                discountField.classList.add('hidden');
                mainItemSection.classList.remove('md:grid-cols-3', 'md:grid-cols-4');

                // ✅ Then apply correct mode
                if (this.value === 'Freebie') {
                    freebieSection.classList.remove('hidden');
                    mainItemSection.classList.add('md:grid-cols-3');
                } else if (this.value === 'Discount') {
                    discountField.classList.remove('hidden');
                    mainItemSection.classList.add('md:grid-cols-4');
                } else {
                    mainItemSection.classList.add('md:grid-cols-3');
                    freebieSection.classList.add('hidden');
                }

                lastValue = this.value;
            });

            // 🔹 Only fire change if no value (new row)
            if (!select.value) {
                select.dispatchEvent(new Event('change'));
            }
        }






        function calculateRowTotals(row) {
            // === Inputs ===
            const saleTypeInput = row.querySelector('select[name*="[sale_type]"]');
            const saleType = saleTypeInput?.value || 'Freebie'; // Match HTML options: 'Freebie' or 'Discount'

            const discountInput = row.querySelector('input[name*="[discount]"]');
            const discountValue = parseFloat(discountInput?.value) || 0;

            const schemeInput = row.querySelector('input[name*="[scheme]"]');
            const qtyPcsInput = row.querySelector('.qty-per-pc');
            const qtyCsInput = row.querySelector('.qty-cs');
            const pricePerPcInput = row.querySelector('input[name*="[price_per_pc]"]');

            const totalQtyDisplay = row.querySelector('.total-qty-display');
            const priceDisplay = row.querySelector('.price-display');
            const amountDisplay = row.querySelector('.amount-display');

            const breakdownPrice = row.querySelector('.breakdown-price');
            const breakdownAmount = row.querySelector('.breakdown-amount');
            const breakdownTotalQty = row.querySelector('.breakdown-total-qty');
            const breakdownFreebieAmount = row.querySelector('.breakdown-freebie-amount');

            const freebieAmountDisplay = row.querySelector('.freebie-amount-display');

            const freebiePriceInput = row.querySelector('input[name*="[freebie_price_per_pc]"]');
            const freebieQtyPcInput = row.querySelector('input[name*="[freebie_qty_per_pc]"]');
            const freebieQtyCsInput = row.querySelector('input[name*="[freebies_per_cs]"]');
            const freebiePriceInputField = row.querySelector('input[name*="[freebie_price]"]');

            const freebieAmountHidden = row.querySelector('input[name*="[freebie_amount]"]');
            const totalQtyHidden = row.querySelector('.computed-total-qty');
            const freebiesHidden = row.querySelector('.computed-freebies');
            const priceHidden = row.querySelector('.computed-price');
            const amountHidden = row.querySelector('.computed-amount');

            // === Extract Values ===
            const schemeValue = schemeInput?.value || "1+0";
            const pricePerPc = parseFloat(pricePerPcInput?.value) || 0;
            const qtyPcs = parseInt(qtyPcsInput?.value) || 0;
            const qtyCs = parseInt(qtyCsInput?.value) || 0;

            const freebiePricePc = parseFloat(freebiePriceInput?.value) || 0;
            const freebieQtyPc = parseInt(freebieQtyPcInput?.value) || 0;

            const safeFixed = (num) => isNaN(num) ? '0.00' : num.toFixed(2);

            // === Scheme Breakdown ===
            let [base, free] = schemeValue.replace(/[^0-9+]/g, '').split('+').map(n => parseInt(n) || 0);
            if (base === 0) base = 1;
            const fullSets = Math.floor(qtyCs / base);
            const freebies = fullSets * free;

            // === Base Calculations ===
            let pricePerCase = pricePerPc * qtyPcs;
            let originalPricePerCase = pricePerCase; // Keep original for breakdown
            let totalAmount = 0;
            let freebieAmount = 0;
            let totalCases = qtyCs;
            let actualFreebies = 0;

            // === Sale Type Specific Calculations ===
            if (saleType === 'Freebie') {
                // Freebie logic: Add free cases to total quantity
                actualFreebies = freebies;
                totalCases = qtyCs + actualFreebies;
                totalAmount = pricePerCase * qtyCs; // Only pay for ordered cases

                // Calculate freebie amount (value of free items)
                if (actualFreebies > 0) {
                    freebieAmount = freebiePricePc * freebieQtyPc * actualFreebies;
                }

            } else if (saleType === 'Discount') {
                // Discount logic: Reduce price per case, no freebies
                actualFreebies = 0;
                totalCases = qtyCs; // No additional cases

                if (discountValue > 0) {
                    const discountText = discountInput?.value || '';
                    if (discountText.includes('%')) {
                        // Percentage discount - remove % and apply as percentage
                        const percentValue = parseFloat(discountText.replace('%', '')) || 0;
                        pricePerCase = originalPricePerCase * (1 - percentValue / 100);
                    } else {
                        // Fixed amount discount
                        pricePerCase = Math.max(0, originalPricePerCase - discountValue);
                    }
                }
                totalAmount = pricePerCase * qtyCs;
                freebieAmount = 0; // No freebie value for discounts
            }

            // === Update Hidden Fields ===
            if (totalQtyHidden) totalQtyHidden.value = totalCases;
            if (freebiesHidden) freebiesHidden.value = actualFreebies;
            if (priceHidden) priceHidden.value = safeFixed(pricePerCase);
            if (amountHidden) amountHidden.value = safeFixed(totalAmount);
            if (freebieAmountHidden) freebieAmountHidden.value = safeFixed(freebieAmount);
            if (freebiePriceInputField) freebiePriceInputField.value = safeFixed(freebiePricePc * freebieQtyPc);

            // Update freebies per cs field in freebie section
            if (freebieQtyCsInput) freebieQtyCsInput.value = actualFreebies;

            // === UI Display Updates ===
            if (totalQtyDisplay) totalQtyDisplay.textContent = totalCases;
            if (priceDisplay) priceDisplay.textContent = `₱${safeFixed(pricePerCase)}`;
            if (amountDisplay) amountDisplay.textContent = `₱${safeFixed(totalAmount)}`;

            // === Breakdown Updates ===
            if (breakdownPrice) {
                if (saleType === 'Discount' && discountValue > 0) {
                    const discountText = discountInput?.value || '';
                    const isPercentage = discountText.includes('%');
                    const discountAmount = originalPricePerCase - pricePerCase;

                    if (isPercentage) {
                        const percentValue = parseFloat(discountText.replace('%', '')) || 0;
                        breakdownPrice.textContent = `₱${safeFixed(originalPricePerCase)} - ${percentValue}% = ₱${safeFixed(pricePerCase)}`;
                    } else {
                        breakdownPrice.textContent = `₱${safeFixed(originalPricePerCase)} - ₱${safeFixed(discountValue)} = ₱${safeFixed(pricePerCase)}`;
                    }
                } else {
                    breakdownPrice.textContent = `₱${safeFixed(pricePerPc)} × ${qtyPcs}pcs`;
                }
            }

            if (breakdownAmount) {
                if (saleType === 'Discount' && discountValue > 0) {
                    const discountText = discountInput?.value || '';
                    const isPercentage = discountText.includes('%');
                    const discountLabel = isPercentage ?
                        `${parseFloat(discountText.replace('%', ''))}% discount` :
                        `₱${discountValue} discount`;
                    breakdownAmount.textContent = `${qtyCs}cs × ₱${safeFixed(pricePerCase)} = ₱${safeFixed(totalAmount)} (After ${discountLabel})`;
                } else {
                    breakdownAmount.textContent = `${qtyCs}cs × ₱${safeFixed(pricePerCase)} = ₱${safeFixed(totalAmount)}`;
                }
            }

            if (breakdownTotalQty) {
                if (saleType === 'Freebie' && actualFreebies > 0) {
                    breakdownTotalQty.textContent = `${qtyCs}cs + ${actualFreebies}cs (free)`;
                } else {
                    breakdownTotalQty.textContent = `${qtyCs}cs`;
                }
            }

            // === Freebie Amount Display ===
            if (freebieAmountDisplay) {
                if (saleType === 'Freebie' && freebieAmount > 0) {
                    freebieAmountDisplay.textContent = `₱${safeFixed(freebieAmount)}`;
                    freebieAmountDisplay.style.display = '';
                } else {
                    freebieAmountDisplay.textContent = '';
                    freebieAmountDisplay.style.display = 'none';
                }
            }

            if (breakdownFreebieAmount) {
                if (saleType === 'Freebie' && actualFreebies > 0 && freebieAmount > 0) {
                    breakdownFreebieAmount.textContent = `₱${safeFixed(freebiePricePc)} × ${freebieQtyPc}pcs × ${actualFreebies}cs (free items value)`;
                } else if (saleType === 'Discount' && discountValue > 0) {
                    const discountText = discountInput?.value || '';
                    const isPercentage = discountText.includes('%');
                    const totalSavings = (originalPricePerCase - pricePerCase) * qtyCs;

                    if (isPercentage) {
                        const percentValue = parseFloat(discountText.replace('%', '')) || 0;
                        breakdownFreebieAmount.textContent = `Total savings (${percentValue}%): ₱${safeFixed(totalSavings)}`;
                    } else {
                        breakdownFreebieAmount.textContent = `Total savings (₱${discountValue}/case): ₱${safeFixed(totalSavings)}`;
                    }
                } else {
                    breakdownFreebieAmount.textContent = '';
                }
            }

            // === Toggle Freebie Section Visibility ===
            const freebieGrid = row.querySelector('.freebie-grid');
            if (freebieGrid) {
                if (saleType === 'Freebie') {
                    freebieGrid.classList.remove('hidden');
                } else {
                    freebieGrid.classList.add('hidden');
                }
            }

            // === Show/Hide Freebie Amount Row ===
            const freebieAmountRow = freebieAmountDisplay?.closest('.flex');
            if (freebieAmountRow) {
                if (saleType === 'Freebie' && freebieAmount > 0) {
                    freebieAmountRow.style.display = '';
                } else if (saleType === 'Discount') {
                    freebieAmountRow.style.display = 'none';
                }
            }

            console.log('Calculation Results:', {
                saleType,
                qtyCs,
                actualFreebies,
                totalCases,
                originalPricePerCase: safeFixed(originalPricePerCase),
                pricePerCase: safeFixed(pricePerCase),
                totalAmount: safeFixed(totalAmount),
                freebieAmount: safeFixed(freebieAmount),
                discountValue
            });
        }





        function formatCurrency(value) {
            return parseFloat(value || 0).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const formatInputs = document.querySelectorAll(
                'input[name*="[price_per_pc]"], input[name*="[freebie_price_per_pc]"]');

            formatInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const value = parseFloat(this.value);
                    if (!isNaN(value)) {
                        this.value = value.toFixed(2);
                    } else {
                        this.value = ''; // Clear invalid input
                    }
                });
            });
        });




        // Validate scheme input format on blur per row
        document.addEventListener("blur", function(e) {
            if (!e.target.matches('input[name*="[scheme]"]')) return;

            let value = e.target.value;

            // Remove invalid characters
            value = value.replace(/[^0-9+]/g, "");

            const parts = value.split("+").filter(Boolean);

            if (parts.length === 0 || value === "0") {
                value = "";
            } else if (parts.length === 1) {
                value = `${parts[0]}+1`;
            } else if (parts.length >= 2) {
                value = `${parts[0]}+${parts[1]}`;
            }

            e.target.value = value;

            // Recalculate after correction
            const row = e.target.closest('.order-row');
            if (row) calculateRowTotals(row);
        }, true); // useCapture = true to catch blur

        // Restrict input to numbers and +
        document.addEventListener("input", function(e) {
            if (!e.target.matches('input[name*="[scheme]"]')) return;
            e.target.value = e.target.value.replace(/[^0-9+]/g, "");
        });


        let debounceTimeout;

        // 🔍 Product search on keyup/focus (main + freebie)
        $(document).on('keyup focus', '.product-search, .freebie-search', function() {
            clearTimeout(debounceTimeout);

            const input = $(this);
            const query = input.val().trim().toLowerCase();
            const resultList = input.siblings('.search-results');

            if (query.length >= 2) {
                debounceTimeout = setTimeout(() => {
                    input.addClass('animate-pulse');
                    resultList.removeClass('hidden').html(`
                <li class="px-6 py-4 text-gray-600 flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Searching...
                </li>
            `);

                    $.ajax({
                        url: '{{ route('forms.sof_search') }}',
                        data: {
                            query: query
                        },
                        success: function(data) {
                            input.removeClass('animate-pulse');
                            resultList.removeClass('glow-effect');
                            // Trigger reflow to restart animation
                            void resultList[0].offsetWidth;

                            // Add glow class
                            resultList.addClass('glow-effect');
                            resultList.empty();

                            const cleanedQuery = query.replace(/[^a-z0-9]/gi, ' ').toLowerCase().trim();
                            const queryWords = cleanedQuery.split(/\s+/);

                            const filtered = data.filter(item => {
                                const combined = `
                                    ${item.sku}
                                    ${item.description}
                                    ${item.department ?? ''}
                                    ${item.department_code ?? ''}
                                `.replace(/[^a-z0-9]/gi, ' ').toLowerCase();

                                return queryWords.some(word => combined.includes(word));
                            });

                            if (filtered.length === 0) {
                                resultList.append(`<li class="px-6 py-4 text-gray-500 text-center">No products found</li>`);
                            } else {
                                filtered.forEach(product => {
                                    resultList.append(`
                                <li class="product-item px-4 py-2 hover:bg-gray-100 cursor-pointer transition-all"
                                    data-sku="${product.sku}"
                                    data-description="${product.description}"
                                    data-srp="${product.srp}"
                                    data-case_pack="${product.case_pack}"
                                    data-allocation_per_case="${product.allocation_per_case}"
                                    data-cash_bank_card_scheme="${product.cash_bank_card_scheme}"
                                    data-po15_scheme="${product.po15_scheme}"
                                    data-freebie_sku="${product.freebie_sku}"
                                    data-discount_scheme="${product.discount_scheme}"
 
                                    >
                                    <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded mr-2">${product.sku}</span>
                                    ${product.description}
                                </li>
                            `);
                                });
                            }
                        },
                        error: function() {
                            input.removeClass('animate-pulse');
                            resultList.html(`<li class="px-6 py-4 text-red-600 text-center">Search failed</li>`);
                        }
                    });
                }, 300);
            } else {
                resultList.empty().addClass('hidden');
            }
        });

        $(document).on('click', '.product-item', function() {
            const selected = $(this);
            const row = selected.closest('.order-row');
            const sku = selected.data('sku');
            const description = selected.data('description');
            const pricePerPc = selected.data('srp') || '';
            const casePack = selected.data('case_pack') || '';
            const allocationPerCase = selected.data('allocation_per_case') || '';
            const cashBankCardScheme = selected.data('cash_bank_card_scheme') || '';
            const po15Scheme = selected.data('po15_scheme') || '';
            const freebieSku = selected.data('freebie_sku') || '';
            const discount = selected.data('discount_scheme') || '';

            const container = selected.closest('.relative');
            const searchInput = container.find('.product-search, .freebie-search');
            const isFreebie = searchInput.hasClass('freebie-search');

            function setValue(input, value, force = false) {
                if (force || !input.val()) {
                    input.val(value);
                }
                if (input.hasClass('qty-per-pc') || input.hasClass('freebie-qty-per-pc')) {
                    input.trigger('input');
                }
            }

            // === Reset fields based on type ===
            if (isFreebie) {
                // ONLY reset Freebie Price/PC and Freebie QTY/PC
                row.find('.freebie-sku-hidden').val('');
                row.find('.freebie-desc-hidden').val('');
                row.find('[name*="[freebie_price_per_pc]"]').val('');
                row.find('[name*="[freebie_qty_per_pc]"]').val('');
            } else {
                // Reset normal product fields
                row.find('.sku-hidden').val('');
                row.find('.desc-hidden').val('');
                row.find('[name*="[price_per_pc]"]').val('');
                row.find('[name*="[qty_per_pc]"]').val('');
                row.find('[name*="[discount]"]').val('').prop('readonly', false);
                row.find('[name*="[scheme]"]').val('');
            }

            if (isFreebie) {
                // ✅ FREEBIE item - Force update the visible input
                searchInput.val(`${sku} - ${description}`);
                row.find('[name*="[freebie_sku]"]').val(sku);
                row.find('[name*="[freebie_description]"]').val(description);
                setValue(row.find('[name*="[freebie_price_per_pc]"]'), pricePerPc);
                setValue(row.find('[name*="[freebie_qty_per_pc]"]'), casePack);

                // Lock discount fields
                row.find('[name*="[discount]"]').val('').prop('readonly', true);
                // row.find('[name*="[scheme]"]').val('');

            } else {
                // ✅ Normal PRODUCT
                setValue(searchInput, `${sku} - ${description}`, true);
                row.find('.sku-hidden').val(sku);
                row.find('.desc-hidden').val(description);
                setValue(row.find('[name*="[price_per_pc]"]'), pricePerPc);
                setValue(row.find('[name*="[qty_per_pc]"]'), casePack);

                const itemType = row.find('input[name*="[item_type]"]').val();

                if (itemType !== 'FREEBIE') {
                    setValue(row.find('[name*="[discount]"]'), discount);

                    const paymentMode = $('[name="mode_payment"]').val();
                    let scheme = '';
                    if (paymentMode === 'PO15%') {
                        scheme = po15Scheme || '';
                    } else if (paymentMode === 'Cash / Bank Card') {
                        scheme = cashBankCardScheme || '';
                    }
                    setValue(row.find('[name*="[scheme]"]'), scheme);

                } else {
                    row.find('[name*="[discount]"]').val('').prop('readonly', true);
                    row.find('[name*="[scheme]"]').val('');
                }

                row.find('.qty-per-pc').trigger('input');

                // Auto-populate freebie SKU if available
                if (freebieSku) {
                    const freebieInput = row.find('.freebie-search');
                    const resultList = freebieInput.siblings('.search-results');

                    freebieInput.val(freebieSku);
                    resultList.empty().removeClass('hidden');
                    freebieInput.trigger('keyup');
                }
            }

            container.find('.search-results').empty().addClass('hidden');
        });






        $(document).ready(function() {
            function showResults(input) {
                const val = input.val();
                const resultList = input.siblings('.qty-results');

                // Clear and show list if needed
                resultList.empty();

                if (val.includes('|')) {
                    const parts = val.split('|').map(p => p.trim()).filter(p => p !== "");
                    parts.forEach(p => {
                        resultList.append(`
<li class="px-3 py-1 border rounded-lg cursor-pointer hover:bg-gray-100 transition whitespace-nowrap"
    data-value="${p}">
    ${p}
</li>
                `);
                    });
                    resultList.removeClass('hidden');
                } else {
                    resultList.addClass('hidden');
                }
            }

            // Handle input, focus, and blur for both types
            $(document).on('input blur focus', '.qty-per-pc, .freebie-qty-per-pc', function() {
                showResults($(this));
            });

            // Initialize on page load
            $('.qty-per-pc, .freebie-qty-per-pc').each(function() {
                showResults($(this));
            });

            // Click to select a suggestion
            $(document).on('click', '.qty-results li', function() {
                const value = $(this).data('value');
                const input = $(this).closest('div').find('input.qty-per-pc, input.freebie-qty-per-pc');
                input.val(value);
                $(this).parent().addClass('hidden').empty();
            });

            // Allow only numbers, spaces, and |
            $(document).on('input', '.qty-per-pc, .freebie-qty-per-pc', function() {
                let val = $(this).val();
                val = val.replace(/[^0-9| ]/g, '');
                $(this).val(val);
            });
        });








        // function updateDateTimeLocalInput() {
        //     const now = new Date();
        //     const datetimeLocal = now.toISOString().slice(0,16); // 'YYYY-MM-DDTHH:MM'
        //     document.getElementById('time_order').value = datetimeLocal;
        // }

        // // Update on load
        // updateDateTimeLocalInput();

        // // Update every minute
        // setInterval(updateDateTimeLocalInput, 60000); // 60000ms = 1 minute

        document.querySelector('input[name="mbc_card_no"]').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 16);
        });

        const input = document.querySelector('.computed-freebie-amount');
        if (input) {
            const observer = new MutationObserver(() => {
                console.log('Freebie amount changed to:', input.value);
            });

            observer.observe(input, {
                attributes: true,
                attributeFilter: ['value']
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            const orderWrapper = document.getElementById('order-items');

            function debounce(fn, delay) {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), delay);
                };
            }

            function getInputValue(selector, container) {
                const el = container.querySelector(selector);
                return el ? el.value : '';
            }


            function updateTable() {
                const orderRows = document.querySelectorAll('.order-row');

                orderRows.forEach(row => {
                    const container = row.querySelector('.order-content');
                    const summaryBody = row.querySelector('.summary-body');
                    if (!container || !summaryBody) return;

                    summaryBody.innerHTML = ''; // Clear previous summary

                    const format = (val) => val?.toString().trim() ? val : '-';

                    // Main Product Fields
                    const sku = format(getInputValue('input[name*="[sku]"]', container));
                    const desc = format(getInputValue('input[name*="[item_description]"]', container));
                    const scheme = format(getInputValue('input[name*="[scheme]"]', container));
                    const pricePerPc = format(getInputValue('input[name*="[price_per_pc]"]', container));
                    const price = format(getInputValue('input[name*="[price]"]', container));
                    const qtyPcs = format(getInputValue('input[name*="[qty_per_pc]"]', container));
                    const qtyCs = format(getInputValue('input[name*="[qty_per_cs]"]', container));
                    const freebies = format(getInputValue('input[name*="[freebies_per_cs]"]', container));
                    const totalQty = format(getInputValue('input[name*="[total_qty]"]', container));
                    const discount = format(getInputValue('input[name*="[discount]"]', container));
                    const amount = format(getInputValue('input[name*="[amount]"]', container));
                    const remarksSelect = container.querySelector('select[name*="[remarks]"]');
                    const remarksVal = (remarksSelect && remarksSelect.options[remarksSelect.selectedIndex].disabled) ? '' :
                        remarksSelect?.value || '';
                    const remarks = format(remarksVal);
                    const soNumber = format(getInputValue('input[name*="[store_order_no]"]', container));

                    const saleTypeSelect = container.querySelector('select[name*="[sale_type]"]');
                    const saleTypeVal = (saleTypeSelect && saleTypeSelect.options[saleTypeSelect.selectedIndex].disabled) ? '' :
                        saleTypeSelect?.value || '';
                    const saleType = format(saleTypeVal);
                    // Freebie Fields (always different freebie now)
                    const freebieSku = format(getInputValue('input[name*="[freebie_sku]"]', container));
                    const freebieDesc = format(getInputValue('input[name*="[freebie_description]"]', container));
                    const freebiePrice = format(getInputValue('input[name*="[freebie_price_per_pc]"]', container));
                    const freebiePriceTotal = format(getInputValue('input[name*="[freebie_price]"]', container));
                    const freebieQtyPc = format(getInputValue('input[name*="[freebie_qty_per_pc]"]', container));
                    const freebieQty = freebies;
                    const freebieAmount = format(getInputValue('input[name*="[freebie_amount]"]', container));



                    // Main Product Row
                    const productRow = document.createElement('tr');
                    const discountDisplay = (saleType.toUpperCase() === 'DISCOUNT') ? discount : '-';
                    productRow.innerHTML = `
                <td class="border px-2 py-1">${sku}</td>
                <td class="border px-2 py-1">${desc}</td>
                <td class="border px-2 py-1 text-center">${scheme}</td>
                <td class="border px-2 py-1 text-center">${pricePerPc}</td>
                <td class="border px-2 py-1 text-center">${price}</td>
                <td class="border px-2 py-1 text-center">${qtyPcs}</td>
                <td class="border px-2 py-1 text-center">${qtyCs}</td>
                <td class="border px-2 py-1 text-center">-</td>
                <td class="border px-2 py-1 text-center">${qtyCs}</td>
                <td class="border px-2 py-1 text-center">${discountDisplay}</td>
                <td class="border px-2 py-1 text-center">${amount}</td>
                <td class="border px-2 py-1">${remarks}</td>
                <td class="border px-2 py-1">${soNumber}</td>
            `;
                    summaryBody.appendChild(productRow);

                    // Freebie Row (only if sale type is NOT DISCOUNT)
                    if (saleType.toUpperCase() !== 'DISCOUNT') {
                        const freebieRow = document.createElement('tr');
                        freebieRow.classList.add('bg-green-50');
                        freebieRow.innerHTML = `
        <td class="border px-2 py-1">${freebieSku}</td>
        <td class="border px-2 py-1">${freebieDesc}</td>
        <td class="border px-2 py-1 text-center">Freebie</td>
        <td class="border px-2 py-1 text-center">${freebiePrice}</td>
        <td class="border px-2 py-1 text-center">${freebiePriceTotal}</td>
        <td class="border px-2 py-1 text-center">${freebieQtyPc}</td>
        <td class="border px-2 py-1 text-center">-</td>
        <td class="border px-2 py-1 text-center">${freebieQty}</td>
        <td class="border px-2 py-1 text-center">${freebieQty}</td>
        <td class="border px-2 py-1 text-center">0.00</td>
        <td class="border px-2 py-1">${remarks}</td>
        <td class="border px-2 py-1">${soNumber}</td>
    `;
                        summaryBody.appendChild(freebieRow);
                    }

                });
            }



            const debouncedUpdate = debounce(updateTable, 200);

            orderWrapper.addEventListener('input', debouncedUpdate);
            orderWrapper.addEventListener('change', updateTable);

            updateTable(); // Initial load
        });

        document.addEventListener("DOMContentLoaded", function() {
            const modeDispatching = document.getElementById("mode_dispatching");
            const deliveryGroup = document.querySelector(".delivery-group");
            const dispatchSelect = document.querySelector(".dispatch-controller");
            const orderItemForm = document.querySelector('.order-item-form');
            const headerOffset = 80; // adjust if sticky header present
            let alertShown = false;

            function toggleDeliveryGroup() {
                const value = modeDispatching.value.trim().toLowerCase();
                const shouldShow = value === "delivery direct to customer".toLowerCase();

                if (shouldShow) {
                    deliveryGroup.classList.remove("hidden", "opacity-0", "max-h-0");
                    deliveryGroup.classList.add("opacity-100", "max-h-screen");
                } else {
                    deliveryGroup.classList.add("opacity-0", "max-h-0");
                    deliveryGroup.classList.remove("opacity-100", "max-h-screen");
                    setTimeout(() => {
                        if (!shouldShow) deliveryGroup.classList.add("hidden");
                    }, 200);
                }
            }

            function updateRequiredFields() {
                const selectedValue = dispatchSelect.value;
                const shouldRequire = selectedValue === "Delivery Direct to Customer";

                const deliveryFields = document.querySelectorAll(".delivery-field");
                deliveryFields.forEach(input => {
                    input.classList.toggle("required-input", shouldRequire);
                });

                checkForm(); // recheck when requirements change
            }

            function scrollToElement(element, offset = 80) {
                const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({
                    top,
                    behavior: 'smooth'
                });
            }

            function allRequiredFilled() {
                const requiredInputs = document.querySelectorAll('.order-form .required-input, .required-input');
                return Array.from(requiredInputs).every(input => {
                    if (input.tagName.toLowerCase() === 'select') {
                        return input.value && input.value.trim() !== '';
                    }
                    if (input.type === 'date') {
                        return input.value && input.value.trim() !== '';
                    }
                    return input.value.trim() !== '';
                });
            }

            function checkForm() {
                if (allRequiredFilled()) {
                    orderItemForm.classList.remove('hidden');

                    // Smooth scroll directly (no Swal)
                    setTimeout(() => scrollToElement(orderItemForm, headerOffset), 200);
                } else {
                    orderItemForm.classList.add('hidden');
                }
            }
            // test




            // function checkForm() {
            // if (allRequiredFilled()) {
            // orderItemForm.classList.remove('hidden');

            // if (!alertShown) {
            // alertShown = true;
            // setTimeout(() => {
            // Swal.fire({
            // icon: 'success',
            // title: 'Info Complete',
            // text: 'Proceed to add order items.',
            // confirmButtonColor: '#3085d6'
            // }).then(() => {
            // setTimeout(() => scrollToElement(orderItemForm, headerOffset), 200);
            // });
            // }, 200);
            // }
            // } else {
            // // test
            // // orderItemForm.classList.add('');
            // orderItemForm.classList.add('hidden');
            // alertShown = false; // reset so alert can be shown again
            // }
            // }

            // Initial runs
            toggleDeliveryGroup();
            updateRequiredFields();
            checkForm();

            // Event listeners
            modeDispatching.addEventListener("change", () => {
                toggleDeliveryGroup();
                updateRequiredFields();
            });

            dispatchSelect.addEventListener("change", updateRequiredFields);

            // Check form only when a required field loses focus
            document.addEventListener('blur', function(e) {
                if (e.target.classList.contains('required-input')) {
                    checkForm();
                }
            }, true); // use capture phase to catch blur events
        });

        // Add this function to your existing JavaScript code

        function setupInputHighlighting() {
            // Store all monitored elements for continuous checking
            let monitoredElements = new Set();

            // Function to check and update highlight for a specific input/select
            function updateInputHighlight(element) {
                const hasValue = element.value && element.value.trim() !== '';

                if (hasValue) {
                    // Add indigo background highlight
                    element.classList.add('bg-indigo-50', 'border-indigo-300');
                    element.classList.remove('bg-white', 'bg-yellow-50');
                } else {
                    // Remove indigo highlight and restore original classes
                    element.classList.remove('bg-indigo-50', 'border-indigo-300');

                    // Restore original background based on element type or data attributes
                    if (
                        element.hasAttribute('x-model') &&
                        (
                            element.getAttribute('x-model').includes('qty') ||
                            element.getAttribute('x-model').includes('discount')
                        )
                    ) {
                        // For Alpine.js controlled inputs that should be yellow when empty
                        element.classList.add('bg-yellow-50');
                    } else {
                        element.classList.add('bg-white');
                    }
                }
            }

            // Function to check all monitored elements
            function checkAllElements() {
                monitoredElements.forEach(element => {
                    // Make sure element is still in the DOM
                    if (document.contains(element)) {
                        updateInputHighlight(element);
                    } else {
                        // Remove from monitoring if element is no longer in DOM
                        monitoredElements.delete(element);
                    }
                });
            }

            // Function to setup event listeners for a container
            function setupContainerListeners(container) {
                // Select all inputs and selects within the container
                const elements = container.querySelectorAll('input[type="text"], input[type="number"], select, input[name*="display"], input[class*="search"]');

                elements.forEach(element => {
                    // Skip hidden inputs and readonly inputs
                    if (element.type === 'hidden' || element.hasAttribute('readonly')) {
                        return;
                    }

                    // Add to monitored elements set
                    monitoredElements.add(element);

                    // Initial highlight check
                    updateInputHighlight(element);

                    // Add comprehensive event listeners
                    const events = ['input', 'change', 'blur', 'keyup', 'paste', 'focus'];
                    events.forEach(eventType => {
                        element.addEventListener(eventType, () => {
                            // Small delay to ensure programmatic changes are captured
                            setTimeout(() => updateInputHighlight(element), 10);
                        });
                    });

                    // Use MutationObserver to watch for programmatic value changes
                    const observer = new MutationObserver(() => {
                        updateInputHighlight(element);
                    });

                    observer.observe(element, {
                        attributes: true,
                        attributeFilter: ['value', 'data-selected']
                    });

                    // Store observer reference for cleanup if needed
                    element._highlightObserver = observer;
                });
            }

            // Continuous monitoring with interval
            setInterval(checkAllElements, 500); // Check every 500ms

            // Also check on any click or focus events in the document
            document.addEventListener('click', () => {
                setTimeout(checkAllElements, 50);
            });

            document.addEventListener('focusin', () => {
                setTimeout(checkAllElements, 50);
            });

            // Setup listeners for existing rows
            document.querySelectorAll('.order-row').forEach(row => {
                setupContainerListeners(row);
            });

            // Setup listeners for dynamically added rows
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('order-row')) {
                            setupContainerListeners(node);
                        }
                    });
                });
                // Also check all elements after DOM changes
                setTimeout(checkAllElements, 100);
            });

            // Observe the order-items container for new rows
            const orderItemsContainer = document.getElementById('order-items');
            if (orderItemsContainer) {
                observer.observe(orderItemsContainer, {
                    childList: true,
                    subtree: true
                });
            }

            // Watch for form changes that might affect values
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('input', () => {
                    setTimeout(checkAllElements, 50);
                });

                form.addEventListener('change', () => {
                    setTimeout(checkAllElements, 50);
                });
            }

            // Expose function globally for manual triggering
            window.checkInputHighlights = checkAllElements;
        }

        // Updated version of your existing DOMContentLoaded listener
        document.addEventListener('DOMContentLoaded', function() {
            const addButton = document.getElementById('add-row-btn');
            const productCounter = document.getElementById('product-counter');

            // Initialize input highlighting
            setupInputHighlighting();

            addButton.addEventListener('click', function() {
                const container = document.getElementById('order-items');
                const newRow = container.children[0].cloneNode(true);
                console.log('Adding new order row (with highlight reset)');
                // Reset input values and update input names
                newRow.querySelectorAll('input').forEach(input => {
                    input.value = '';
                    input.name = input.name.replace(/\[\d+]/g, `[${rowIndex}]`);
                    input.removeAttribute('data-selected');


                    // Reset background classes for new row
                    input.classList.remove('bg-indigo-50', 'border-indigo-300');
                    if (
                        input.hasAttribute('x-model') &&
                        (
                            input.getAttribute('x-model').includes('qty') ||
                            input.getAttribute('x-model').includes('discount')
                        )
                    ) {
                        input.classList.add('bg-yellow-50');
                    } else if (!input.hasAttribute('readonly') && input.type !== 'hidden') {
                        input.classList.add('bg-white');
                    }
                });

                // Reset select elements
                newRow.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                    select.classList.remove('bg-indigo-50', 'border-indigo-300');
                    select.classList.add('bg-white');
                });

                // Reset output display spans
                newRow.querySelector('.price-display').textContent = '0.00';
                newRow.querySelector('.amount-display').textContent = '0.00';
                newRow.querySelector('.total-qty-display').textContent = '0';

                // Reset summary table
                const summaryTableBody = newRow.querySelector('.summary-body');
                if (summaryTableBody) {
                    summaryTableBody.innerHTML = '';
                }
                newRow.querySelector('.breakdown-price').textContent = '';
                newRow.querySelector('.breakdown-amount').textContent = '';

                // Hide any open dropdowns
                newRow.querySelectorAll('.sku-results, .desc-results, .search-results').forEach(ul => {
                    ul.innerHTML = '';
                    ul.classList.add('hidden');
                });

                // Apply initial hidden state for animation
                newRow.classList.add('overflow-hidden', 'transition-all', 'duration-300', 'ease-in-out');
                newRow.classList.remove('max-h-[1000px]', 'opacity-100', 'py-4', 'mb-6');
                newRow.classList.add('max-h-0', 'opacity-0', 'py-0', 'mb-0');

                container.appendChild(newRow);

                // Force reflow to properly trigger animation
                void newRow.offsetWidth;

                // Trigger the visible state
                newRow.classList.replace('max-h-0', 'max-h-[1000px]');
                newRow.classList.replace('opacity-0', 'opacity-100');
                newRow.classList.replace('py-0', 'py-4');
                newRow.classList.replace('mb-0', 'mb-6');

                rowIndex = document.querySelectorAll('.order-row').length;

                updateRemoveButtonsState();
                updateCounter();
                updateRowNumbers();
                attachCollapseListener(newRow);

                // Setup highlighting for the new row
                setTimeout(() => {
                    setupContainerListeners(newRow);
                    // Force a check after the row is fully set up
                    setTimeout(() => {
                        if (window.checkInputHighlights) {
                            window.checkInputHighlights();
                        }
                    }, 200);
                }, 100);
            });

            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-row')) {
                    const allRows = document.querySelectorAll('.order-row');
                    if (allRows.length > 1) {
                        const row = e.target.closest('.order-row');

                        // Animate to hidden state
                        row.classList.replace('max-h-[1000px]', 'max-h-0');
                        row.classList.replace('opacity-100', 'opacity-0');
                        row.classList.replace('py-4', 'py-0');
                        row.classList.replace('mb-6', 'mb-0');

                        // Remove after transition
                        row.addEventListener('transitionend', () => {
                            row.remove();
                            updateRemoveButtonsState();
                            updateCounter();
                            updateRowNumbers();
                        }, {
                            once: true
                        });
                    }
                }
            });

            updateRemoveButtonsState();
            updateCounter();
            updateRowNumbers();
        });

        // Helper function to manually trigger highlight update (useful for product selection dropdowns)
        function triggerHighlightUpdate(element) {
            const event = new CustomEvent('product-selected');
            element.dispatchEvent(event);
        }
    </script>

    <style>
        @keyframes glow {

            0%,
            100% {
                box-shadow: 0 0 5px 2px rgba(59, 130, 246, 0);
                /* transparent */
            }

            50% {
                box-shadow: 0 0 10px 4px rgba(59, 130, 246, 0.8);
                /* blue glow */
            }
        }

        .glow-effect {
            animation: glow 1.5s ease-in-out infinite alternate;
            border-radius: 0.375rem;
        }
    </style>

@endsection
