@extends('layouts.app')

@section('content')
@php date_default_timezone_set('Asia/Manila'); $currentDateTime = now()->format('Y-m-d\TH:i'); @endphp
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
  <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">

    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex items-center space-x-4">
        <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
            </svg>
        </div>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Sales Order Form</h1>
          <p class="text-gray-600 mt-1">Fill out the form to create a new sales order record.</p>
        </div>
      </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
      <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl shadow-sm">
        <p class="text-green-800 font-medium">✅ {{ session('success') }}</p>
      </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl shadow-sm">
        <p class="text-red-800 font-medium">❌ Please fix the following errors:</p>
        <div class="max-h-48 overflow-y-auto pr-2 mt-2">
            <ul class="list-disc list-inside text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif


    <!-- Order Form -->
    <form method="POST" action="{{ route('forms.sof_submit') }}" id="order-form">
      @csrf
        <div class="order-form bg-white p-6 rounded-xl shadow-lg ">
            <!-- Request Details -->
            <section class="bg-white p-4  ">
                <h2 class="text-lg font-semibold mb-4">Request Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative w-full mb-6">
                        <input 
                            value="{{ old('requesting_store', 'Test Store') }}" 
                            type="text" 
                            name="requesting_store" 
                            readonly
                            class="peer w-full p-3 pt-5 text-sm bg-indigo-50 text-gray-700 border border-gray-300 rounded-md cursor-not-allowed placeholder-transparent focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                            placeholder="Requesting Store"
                        />
                        <label 
                            for="requesting_store"
                            class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                        >
                            Requesting Store
                        </label>
                    </div>

                    <div class="relative w-full mb-6">
                        <input 
                            value="{{ old('requested_by', auth()->user()->name ?? 'Personnel Sample') }}" 
                            type="text" 
                            name="requested_by" 
                            readonly
                            class="peer w-full p-3 pt-5 text-sm bg-indigo-50 text-gray-700 border border-gray-300 rounded-md cursor-not-allowed placeholder-transparent focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                            placeholder="Requested By"
                        />
                        <label 
                            for="requested_by"
                            class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                        >
                            Requested By
                        </label>
                    </div>


                    <div class="relative w-full">
                        <select
                            name="channel_order"
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        >
                            <option disabled {{ old('channel_order') ? '' : 'selected' }} value="">Select channel</option>
                            @foreach(['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
                                <option value="{{ $option }}" {{ old('channel_order') == $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                        <label
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                        >
                            Channel of Order
                        </label>
                    </div>
                    
                    <div class="relative w-full">
                        <input
                            value="{{ old('time_order') }}"
                            type="datetime-local"
                            name="time_order"
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            placeholder=" "
                        />
                        <label
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                        >
                            Date & Time of Order
                        </label>
                    </div>



                </div>
            </section>

            <!-- Customer Info -->
            <section class="bg-white p-4  ">
                <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            placeholder="MBC Card Number"
                        >
                        <label 
                            for="mbc_card_no"
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
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
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            placeholder="Customer Name"
                        >
                        <label 
                            for="customer_name"
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
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
                            pattern="[0-9]{11}"
                            maxlength="12"
                            required
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            placeholder="Contact Number"
                        >
                        <label 
                            for="contact_number"
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                            Contact Number
                        </label>
                    </div>

                </div>
            </section>

            <!-- Payment Info -->
            <section class="bg-white p-4  ">
                <h2 class="text-lg font-semibold mb-4">Payment Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <input
                            list="payment-centers"
                            name="payment_center"
                            id="payment_center"
                            placeholder=" "
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            autocomplete="off"
                            value="{{ old('payment_center') }}"
                        >

                        <label
                            for="payment_center"
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                        >
                            Payment Center
                        </label>

                        <datalist id="payment-centers">
                            <option value="S10-MAASIN">
                            <option value="S17-TACLOBAN">
                            <option value="S19-METRO BAY-BAY">
                            <option value="F18-ALANG-ALANG">
                            <option value="F19-HILONGOS">
                            <option value="S8-TOLEDO">
                            <option value="H9-CARCAR">
                            <option value="H10-BOGO">
                        </datalist>
                    </div>


                    
                        <!-- Mode of Payment -->
                    <div class="relative">
                        <select name="mode_payment"
                            x-data
                            x-model="$el.value"
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        >
                            <option value="" disabled {{ old('mode_payment') ? '' : 'selected' }}>Select or type payment mode</option>
                            <option value="PO15%" {{ old('mode_payment') == 'PO15%' ? 'selected' : '' }}>PO15%</option>
                            <option value="Cash" {{ old('mode_payment') == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Bank Card" {{ old('mode_payment') == 'Bank Card' ? 'selected' : '' }}>Bank Card</option>
                        </select>
                        <label
                            class="absolute left-3 top-1.5 text-xs text-gray-500 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-500"
                            :class="$el.value ? 'top-1 text-xs text-blue-600' : ''"
                        >
                            Mode of Payment
                        </label>
                    </div>


                        <!-- Payment Date -->
                    <div class="relative">
                        <input type="date" name="payment_date"
                            x-data="{ value: '{{ old('payment_date') }}' }"
                            x-model="value"
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        >
                        <label
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600"
                            :class="value ? 'top-1 text-xs text-blue-600' : ''"
                        >
                            Payment Date
                        </label>
                    </div>

                </div>
            </section>

            <!-- Dispatch Info -->
            <section class="bg-white p-4">
                <h2 class="text-lg font-semibold mb-4">Dispatch Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Mode of Dispatching -->
                    <div class="relative">
                        <select
                            name="mode_dispatching"
                            id="mode_dispatching"
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            autocomplete="off"
                            data-hide-value="Customer Pick-up"
                            data-target=".delivery-group"
                        >
                            <option value="" disabled {{ old('mode_dispatching') ? '' : 'selected' }}> Select Mode of Dispatch</option>
                            <option value="Customer Pick-up" {{ old('mode_dispatching') == 'Customer Pick-up' ? 'selected' : '' }}>Customer Pick-up</option>
                            <option value="Delivery Direct to Customer" {{ old('mode_dispatching') == 'Delivery Direct to Customer' ? 'selected' : '' }}>Delivery Direct to Customer</option>
                        </select>
                        <label for="mode_dispatching"
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                            Mode of Dispatching
                        </label>
                    </div>

                    <!-- Delivery/Pick-up Date -->
                    <div class="relative">
                        <input
                            type="date"
                            name="delivery_date"
                            id="delivery_date"
                            value="{{ old('delivery_date') }}"
                            class="required-input peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                        />
                        <label for="delivery_date"
                            class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                            Delivery/Pick-up Date
                        </label>
                    </div>
                </div>

                <!-- Delivery Details (Initially Hidden) -->
                <div class="delivery-group overflow-hidden transition-all duration-200 ease-in-out opacity-0 max-h-0 hidden mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <input
                                value="{{ old('address') }}"
                                type="text"
                                name="address"
                                id="address"
                                class="peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            />
                            <label for="address"
                                class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                Address
                            </label>
                        </div>

                        <div class="relative">
                            <input
                                value="{{ old('landmark') }}"
                                type="text"
                                name="landmark"
                                id="landmark"
                                class="peer block w-full appearance-none border border-gray-300 rounded-md px-3 pt-6 pb-2 text-sm text-gray-900 placeholder-transparent focus:border-gray-600 focus:outline-none focus:ring-1 focus:ring-gray-900"
                            />
                            <label for="landmark"
                                class="absolute left-3 top-1.5 text-gray-500 text-xs transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-gray-600">
                                Landmark
                            </label>
                        </div>
                    </div>
                </div>
            </section>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modeDispatching = document.getElementById("mode_dispatching");
        const deliveryGroup = document.querySelector(".delivery-group");

        function toggleDeliveryGroup() {
            const value = modeDispatching.value.trim().toLowerCase();
            const shouldShow = value === "delivery direct to customer".toLowerCase();

            if (shouldShow) {
                deliveryGroup.classList.remove("hidden", "opacity-0", "max-h-0");
                deliveryGroup.classList.add("opacity-100", "max-h-screen");
            } else {
                deliveryGroup.classList.add("opacity-0", "max-h-0");
                deliveryGroup.classList.remove("opacity-100", "max-h-screen");
                // Delay hiding completely after transition
                setTimeout(() => {
                    if (!shouldShow) deliveryGroup.classList.add("hidden");
                }, 200);
            }
        }

        // Initial state on load
        toggleDeliveryGroup();

        // Toggle on change
        modeDispatching.addEventListener("change", toggleDeliveryGroup);
    });
</script>

        </div>

        <div class="order-item-form hidden bg-white p-6 rounded-xl shadow-lg space-y-6 mt-6">

            <div class="bg-white pt-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Products:</span>
                        <span id="product-counter" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">1</span>
                    </div>
                </div>
            </div>
            <!-- Order Items Table -->
            <div class="overflow-x-auto space-y-6">
                    @php
                        $orders = old('orders', [ [] ]); // fallback: 1 empty row
                    @endphp
                <div id="order-items" class="space-y-6">
                    <!-- Sample Row -->
                    @foreach ($orders as $i => $order)
                    <div class="order-row relative border rounded-xl bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md space-y-6 bg-white transition-all duration-300 ease-in-out overflow-hidden max-h-[1000px] opacity-100 mb-6">
                        <!-- Remove Button (Top-Right Trash Icon) -->
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="item-number w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                1
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Item No. 1</h3>
                                <button type="button" class="ml-auto toggle-collapse text-gray-500 hover:text-gray-700">
                                    <svg class="h-5 w-5 collapse-icon transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                        </div>
                        
                        <button type="button"
                            class="remove-row absolute top-0 right-6 text-red-500 hover:text-red-700 transition transform hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22m-5-4H6a2 2 0 00-2 2v0a2 2 0 002 2h12a2 2 0 002-2v0a2 2 0 00-2-2z" />
                            </svg>
                        </button>

                        <!-- Combined Grid: Left = Editable Inputs, Right = Readonly Invoice Style -->
                        <div class="order-content grid grid-cols-1 md:grid-cols-3 gap-6 ">

                            <!-- LEFT SIDE: Editable Inputs -->
                            <div class="editable-side md:col-span-2  space-y-4 transition-all duration-300 ease-in-out max-h-[2000px] opacity-100 ">
                                <!-- SKU + Description -->
                                <div class="product-row grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Unified Search Input -->
                                    <div class="relative w-full" x-data="{ value: '{{ old("orders.$i.sku") && old("orders.$i.item_description") ? old("orders.$i.sku") . ' - ' . old("orders.$i.item_description") : '' }}' }">
                                        <label class="block text-sm font-medium mb-1">Main Product</label>
                                        <input type="text"
                                            x-model="value"
                                            :class="value === '' ? 'bg-yellow-50' : 'bg-white'"
                                            class="product-search w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                            placeholder="Enter SKU or Description"
                                            autocomplete="off"
                                            name="orders[{{ $i }}][display]"
                                        >

                                        <ul class="search-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>

                                        <!-- Hidden Fields -->
                                        <input type="hidden" name="orders[{{ $i }}][sku]" class="sku-hidden" value="{{ old("orders.$i.sku") }}">
                                        <input type="hidden" name="orders[{{ $i }}][item_description]" class="desc-hidden" value="{{ old("orders.$i.item_description") }}">
                                    </div>

                                                                        <!-- QTY/CS -->
                                    <div x-data="{ qty: '{{ old("orders.$i.qty_per_cs") }}' }">
                                        <label class="block text-sm font-medium mb-1">QTY/CS</label>
                                        <input type="number"
                                            x-model="qty"
                                            :class="qty === '' ? 'bg-yellow-50' : 'bg-white'"
                                            name="orders[{{ $i }}][qty_per_cs]"
                                            class="qty-cs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                            placeholder="0" style="text-align: left;">
                                    </div>

                                    
                                </div>

                                <!-- Other Inputs -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Scheme</label>
                                        <input type="text" name="orders[{{ $i }}][scheme]" 
                                            value="{{ old("orders.$i.scheme") }}"
                                            class="scheme-input w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" placeholder="0"/>
                                    </div>


                                    <!-- Price/PCS -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Price/PC</label>
                                        <input type="number" step="0.01" name="orders[{{ $i }}][price_per_pc]"
                                            value="{{ old("orders.$i.price_per_pc") }}"
                                            class="price-per-pc w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  
                                            style="text-align: left;" placeholder="0.00"/>
                                    </div>

                                    <!-- QTY/PCS -->
                                    <div>
                                    <label class="block text-sm font-medium mb-1">QTY/PC</label>
                                    <input type="number" name="orders[{{ $i }}][qty_per_pc]"
                                            value="{{ old("orders.$i.qty_per_pc") }}"
                                            class="qty-pcs qty-per-pc w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  style="text-align: left;" placeholder="0"/>
                                    </div>
                                </div>


                                <!-- Freebie Section (Hidden by Default) -->
                                <div class="freebie-grid mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 " data-index="{{ $i }}">
                                    <!-- Freebie Product -->
                                    <div class="relative w-full">
                                        <label class="block text-sm font-medium mb-1">Freebie Product</label>
                                        <input type="text"
                                            class="freebie-search w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                            placeholder="Enter Freebie SKU or Description"
                                            autocomplete="off"
                                            value="{{ old("orders.$i.freebie_sku") && old("orders.$i.freebie_description") ? old("orders.$i.freebie_sku") . ' - ' . old("orders.$i.freebie_description") : '' }}" />
                                        <ul class="search-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>

                                        <input type="hidden" name="orders[{{ $i }}][freebie_sku]" class="freebie-sku-hidden" value="{{ old("orders.$i.freebie_sku") }}">
                                        <input type="hidden" name="orders[{{ $i }}][freebie_description]" class="freebie-desc-hidden" value="{{ old("orders.$i.freebie_description") }}">
                                    </div>

                                    <!-- Freebies Price/PCS -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Freebie Price/PC</label>
                                        <input type="number" step="0.01" name="orders[{{ $i }}][freebie_price_per_pc]"
                                            value="{{ old("orders.$i.freebie_price_per_pc") }}"
                                            class="freebie-price-per-pc w-full p-2 border border-gray-300 rounded text-left focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                            placeholder="0.00" />
                                    </div>
                                    <!-- Frebies Price -->
                                    <div class="hidden">
                                        <label class="block text-sm font-medium mb-1">Freebie Price</label>
                                        <input type="hidden" step="0.01" name="orders[{{ $i }}][freebie_price]"
                                            value="{{ old("orders.$i.freebie_price") }}"
                                            class="w-full p-2 border border-gray-300 rounded text-left focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                            placeholder="0.00" />
                                    </div>

                                    <!-- Freebies QTY/PCS -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Freebie QTY/PC</label>
                                        <input type="number" name="orders[{{ $i }}][freebie_qty_per_pc]"
                                            value="{{ old("orders.$i.freebie_qty_per_pc") }}"
                                            class="qty-pcs freebie-qty-per-pc w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  style="text-align: left;" placeholder="0"/>
                                    </div>

                                    <!-- Freebie QTY/CS-->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Freebie QTY/CS</label>
                                                <input type="text" name="orders[{{ $i }}][freebies_per_cs]"
                                                value="{{ old("orders.$i.freebies_per_cs") }}"  
                                                class="computed-freebies w-full p-2 bg-gray-50 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" style="text-align: left;"  placeholder="0"  readonly/>
                                    </div>
                                </div>



                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <!-- Remarks Dropdown -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Remarks</label>
                                        <select name="orders[{{ $i }}][remarks]" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300">
                                            @php
                                                $selectedRemarks = old("orders.$i.remarks", $order['remarks'] ?? '');
                                            @endphp
                                            <option value="" disabled hidden {{ $selectedRemarks === '' ? 'selected' : '' }}>Select remarks</option>
                                            <option value="For SO (Special Order)" {{ $selectedRemarks === 'For SO (Special Order)' ? 'selected' : '' }}>For SO (Special Order)</option>
                                            <option value="For RMS Approval" {{ $selectedRemarks === 'For RMS Approval' ? 'selected' : '' }}>For RMS Approval</option>
                                        </select>

                                    </div>


                                    <!-- Store Order No. -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Store Order No. (SO#)</label>
                                        <input type="number" name="orders[{{ $i }}][store_order_no]"
                                            value="{{ old("orders.$i.store_order_no") }}"
                                            class="appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none
                                                [&::-webkit-outer-spin-button]:m-0 [&::-webkit-inner-spin-button]:m-0
                                                [-moz-appearance:textfield]
                                                w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                                placeholder="Please Input Store Order No."
                                                inputmode="numeric" pattern="\d*" min="0" step="0" />
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT SIDE: Readonly Invoice Style -->
                            <div class="readonly-side w-full md:col-span-1 bg-white border border-gray-200 rounded p-4 h-full flex flex-col justify-between transition-all duration-300">
                                <div class="space-y-2">
                                    
                                    <!-- Price -->
                                    <div class="flex justify-between items-start">
                                    <label class="block text-sm text-gray-600">Price</label>
                                    <div class="text-right text-black font-medium">
                                        <span class="price-display">0.00</span>
                                        <input type="hidden" name="orders[{{ $i }}][price]"
                                            value="{{ old("orders.$i.price") }}" 
                                            class="computed-price"/>
                                        <div class="text-xs text-gray-500 breakdown-price"></div>
                                    </div>
                                    </div>


                                    <!-- Total QTY -->
                                    <div class="flex justify-between items-start">
                                    <label class="block text-sm text-gray-600">Total QTY/CS</label>
                                    <div class="text-right text-black font-medium">
                                        <span class="total-qty-display">0</span>
                                        <input type="hidden" name="orders[{{ $i }}][total_qty]" 
                                            value="{{ old("orders.$i.total_qty") }}" 
                                            class="computed-total-qty" />
                                        <div class="text-xs text-gray-500 breakdown-total-qty"></div>
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

                                    <!-- Freebie Amount -->
                                    <div class="flex justify-between items-start">
                                    <label class="block text-sm text-green-600">Freebie Amount</label>
                                    <div class="text-right text-black font-medium">
                                        <span class="freebie-amount-display block text-green-600">0.00</span>
                                        <input type="hidden" name="orders[{{ $i }}][freebie_amount]" class="computed-freebie-amount" />
                                        <div class="text-xs text-gray-500 breakdown-freebie-amount"></div>
                                    </div>
                                    </div>

                                </div>

                                <!-- Total Amount -->
                                <div class="flex justify-between items-start pt-2">
                                    <label class="block text-sm text-indigo-600">Total Amount</label>
                                    <div class="text-right text-black font-bold text-blue-600">
                                    <span class="amount-display">0.00</span>
                                    <input type="hidden" name="orders[{{ $i }}][amount]"
                                            value="{{ old("orders.$i.amount") }}"   
                                            class="computed-amount" />
                                    <div class="text-xs text-gray-500 breakdown-amount"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="summary-table" class="summary-table hidden w-full bg-white text-sm border border-gray-300 shadow-sm rounded overflow-hidden">
                            <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                                <tr>
                                    <th rowspan="2" class="border px-2 py-1 text-left">SKU</th>
                                    <th rowspan="2" class="border px-2 py-1 text-left">Item Description</th>
                                    <th rowspan="2" class="border px-2 py-1 text-center">Scheme</th>
                                    <th rowspan="2" class="border px-2 py-1 text-center">Price/PC</th>
                                    <th rowspan="2" class="border px-2 py-1 text-center">Price</th>
                                    <th colspan="3" class="border px-2 py-1 text-center">Order in Cases</th>
                                    <th rowspan="2" class="border px-2 py-1 text-center">Total Qty</th>
                                    <th rowspan="2" class="border px-2 py-1 text-center">Amount</th>
                                    <th rowspan="2" class="border px-2 py-1 text-left">Remarks</th>
                                    <th rowspan="2" class="border px-2 py-1 text-left">Store Order No.</th>
                                </tr>
                                <tr>
                                    <th class="border px-2 py-1 text-center">QTY/PC</th>
                                    <th class="border px-2 py-1 text-center">QTY/CS</th>
                                    <th class="border px-2 py-1 text-center">Freebies</th>
                                </tr>
                            </thead>
                            <tbody id="summary-body" class=" summary-body divide-y divide-gray-200">
                                <!-- JS will inject rows here -->
                            </tbody>
                        </table>
                    </div>
                    @endforeach
                </div>

                <!-- Add Row Button -->
                <div class="mt-8">
                    <button type="button" id="add-row-btn"
                        class="w-full inline-flex justify-center items-center px-6 py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-600 hover:border-gray-400 hover:text-blue-600 transition-all duration-200 group hover:bg-blue-50 animate-surpriseBounce">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform duration-200"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
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
                <button id="submitBtn" type="submit"
                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:shadow-lg hover:scale-[1.02] transition duration-1000 flex items-center justify-center hover:scale-[1.02] transition">
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

let rowIndex = 1;

document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.getElementById('add-row-btn');
    const productCounter = document.getElementById('product-counter');

    addButton.addEventListener('click', function () {
        const container = document.getElementById('order-items');
        const newRow = container.children[0].cloneNode(true);

        // Reset input values and update input names
        // Reset input fields and update names
        newRow.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.name = input.name.replace(/\[\d+]/g, `[${rowIndex}]`);
            input.removeAttribute('data-selected');
        });

        // Reset output display spans
        newRow.querySelector('.price-display').textContent = '0.00';
        newRow.querySelector('.amount-display').textContent = '0.00';
        newRow.querySelector('.total-qty-display').textContent = '0';
        // newRow.querySelector('.freebies-cs-display').textContent = '0';

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
        attachSchemeTypeListener(newRow);
    });

    document.addEventListener('click', function (e) {
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
                }, { once: true });
            }
        }
    });



    updateRemoveButtonsState();
    updateCounter();
    updateRowNumbers();
    
});

// Disable delete if only one row
function updateRemoveButtonsState() {
    const allRows = document.querySelectorAll('.order-row');
    allRows.forEach(row => {
        const removeButton = row.querySelector('.remove-row');
        if (allRows.length === 1) {
            removeButton.classList.add('opacity-25',  'cursor-not-allowed');
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



document.querySelectorAll('.order-row').forEach(row => {
    attachCollapseListener(row);
    // attachSchemeTypeListener(row); 
});


// function attachSchemeTypeListener(row) {
//     const select = row.querySelector('.scheme-type');
//     const freebieSection = row.querySelector('.freebie-grid');
//     const skuHidden = freebieSection?.querySelector('.freebie-sku-hidden');
//     const descHidden = freebieSection?.querySelector('.freebie-desc-hidden');
//     const searchInput = freebieSection?.querySelector('.freebie-search');
//     const priceInput = freebieSection?.querySelector('input[name$="[freebie_price_per_pc]"]');

//     if (select) {
//         select.addEventListener('change', function () {
//             if (this.value === 'DIFF_FREEBIE') {
//                 freebieSection?.classList.remove('hidden');
//             } else {
//                 freebieSection?.classList.add('hidden');
//                 if (skuHidden) skuHidden.value = '';
//                 if (descHidden) descHidden.value = '';
//                 if (searchInput) searchInput.value = '';
//                 if (priceInput) priceInput.value = '';
//             }
//         });

//         // Trigger initial state
//         select.dispatchEvent(new Event('change'));
//     }
// }
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.order-row').forEach(row => calculateRowTotals(row));
});

// Listen to input events on QTY and Price fields
document.addEventListener('DOMContentLoaded', function () {
  const allRows = document.querySelectorAll('.order-row');
  allRows.forEach(row => calculateRowTotals(row));
});

['input', 'change'].forEach(eventType => {
  document.addEventListener(eventType, function (e) {
    if (!e.target.closest('.order-row')) return;

    const row = e.target.closest('.order-row');
    calculateRowTotals(row);
  });
});

document.addEventListener('DOMContentLoaded', function () {
  // Recalculate when payment mode changes
  document.querySelectorAll('.mode-payment').forEach(select => {
    select.addEventListener('change', function () {
      document.querySelectorAll('.order-row').forEach(row => {
        calculateRowTotals(row);

        // Simulate .product-item logic if SKU is already selected
        const selectedSKU = row.querySelector('.sku-hidden')?.value;
        if (selectedSKU) {
          const matchedProduct = document.querySelector(`.product-item[data-sku="${selectedSKU}"]`);
          if (matchedProduct) {
            matchedProduct.click(); // trigger the product selection logic
          }
        }
      });
    });
  });
});




function calculateRowTotals(row) {
  const schemeInput = row.querySelector('input[name*="[scheme]"]');
  const qtyPcsInput = row.querySelector('.qty-pcs');
  const qtyCsInput = row.querySelector('.qty-cs');
  const totalQtyInput = row.querySelector('.total-qty');
  const pricePerPcInput = row.querySelector('input[name*="[price_per_pc]"]');

  const totalQtyDisplay = row.querySelector('.total-qty-display');
  const priceDisplay = row.querySelector('.price-display');
  const amountDisplay = row.querySelector('.amount-display');

  const breakdownPrice = row.querySelector('.breakdown-price');
  const breakdownAmount = row.querySelector('.breakdown-amount');
  const breakdownTotalQty = row.querySelector('.breakdown-total-qty');

  const freebieAmountDisplay = row.querySelector('.freebie-amount-display');
  const breakdownFreebieAmount = row.querySelector('.breakdown-freebie-amount');

  const freebiePriceInput = row.querySelector('input[name*="[freebie_price_per_pc]"]');
  const freebieQtyPcInput = row.querySelector('input[name*="[freebie_qty_per_pc]"]');
  const freebieQtyCsInput = row.querySelector('input[name*="[freebies_per_cs]"]');
  const freebiePriceInputField = row.querySelector('input[name*="[freebie_price]"]');

  const freebieAmountHidden = row.querySelector('input[name*="[freebie_amount]"]');
  const totalQtyHidden = row.querySelector('.computed-total-qty');
  const freebiesHidden = row.querySelector('.computed-freebies');
  const priceHidden = row.querySelector('.computed-price');
  const amountHidden = row.querySelector('.computed-amount');

  // Extract values
  const schemeValue = schemeInput?.value || "1+0";
  const pricePerPc = parseFloat(pricePerPcInput?.value) || 0;
  const qtyPcs = parseInt(qtyPcsInput?.value) || 0;
  const qtyCs = parseInt(qtyCsInput?.value) || 0;

  const freebiePricePc = parseFloat(freebiePriceInput?.value) || 0;
  const freebieQtyPc = parseInt(freebieQtyPcInput?.value) || 0;
  const freebiesPerCs = parseInt(freebieQtyCsInput?.value) || 0;

  const safeFixed = (num) => isNaN(num) ? '0.00' : num.toFixed(2);

  // Scheme breakdown
  let [base, free] = schemeValue.replace(/[^0-9+]/g, '').split('+').map(n => parseInt(n) || 0);
  if (base === 0) base = 1;
  const fullSets = Math.floor(qtyCs / base);
  const freebies = fullSets * free;
  const totalCases = qtyCs + freebies;

  const pricePerCase = pricePerPc * qtyPcs;
  const totalAmount = pricePerCase * qtyCs;

  // ✅ Always calculate freebie amount using freebie fields
  const freebieAmount = freebiePricePc * freebieQtyPc * freebiesPerCs;

  // Update hidden fields
  if (totalQtyHidden) totalQtyHidden.value = totalCases;
  if (freebiesHidden) freebiesHidden.value = freebies;
  if (priceHidden) priceHidden.value = safeFixed(pricePerCase);
  if (amountHidden) amountHidden.value = safeFixed(totalAmount);
  if (freebieAmountHidden) freebieAmountHidden.value = safeFixed(freebieAmount);
  if (freebiePriceInputField) freebiePriceInputField.value = safeFixed(freebiePricePc * freebieQtyPc);

  // UI display
  if (totalQtyInput) totalQtyInput.value = totalCases;
  if (totalQtyDisplay) totalQtyDisplay.textContent = totalCases;
  if (priceDisplay) priceDisplay.textContent = `₱${safeFixed(pricePerCase)}`;
  if (amountDisplay) amountDisplay.textContent = `₱${safeFixed(totalAmount)}`;
  if (freebieAmountDisplay) freebieAmountDisplay.textContent = `₱${safeFixed(freebieAmount)}`;

  // Breakdown
  if (breakdownPrice)
    breakdownPrice.textContent = `₱${safeFixed(pricePerPc)} × ${qtyPcs}pcs`;

  if (breakdownAmount)
    breakdownAmount.textContent = `${qtyCs}cs × ₱${safeFixed(pricePerCase)}`;

  if (breakdownTotalQty)
    breakdownTotalQty.textContent = `${qtyCs}cs + ${freebies}cs`;

  if (breakdownFreebieAmount)
    breakdownFreebieAmount.textContent = `₱${safeFixed(freebiePricePc)} × ${freebieQtyPc}pcs × ${freebiesPerCs}cs`;
}




function formatCurrency(value) {
  return parseFloat(value || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

document.addEventListener('DOMContentLoaded', function () {
  const formatInputs = document.querySelectorAll('input[name*="[price_per_pc]"], input[name*="[freebie_price_per_pc]"]');

  formatInputs.forEach(input => {
    input.addEventListener('blur', function () {
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
document.addEventListener("blur", function (e) {
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
document.addEventListener("input", function (e) {
    if (!e.target.matches('input[name*="[scheme]"]')) return;
    e.target.value = e.target.value.replace(/[^0-9+]/g, "");
});


let debounceTimeout;

// 🔍 Product search on keyup/focus (main + freebie)
$(document).on('keyup focus', '.product-search, .freebie-search', function () {
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
                url: '{{ route("forms.sof_search") }}',
                data: { query: query },
                success: function (data) {
                    input.removeClass('animate-pulse');
                    resultList.empty();

                    const cleanedQuery = query.replace(/[^a-z0-9]/gi, ' ').toLowerCase().trim();
                    const queryWords = cleanedQuery.split(/\s+/);

                    const filtered = data.filter(item => {
                        const combined = `${item.sku} ${item.description}`.replace(/[^a-z0-9]/gi, ' ').toLowerCase();
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
 
                                    >
                                    <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded mr-2">${product.sku}</span>
                                    ${product.description}
                                </li>
                            `);
                        });
                    }
                },
                error: function () {
                    input.removeClass('animate-pulse');
                    resultList.html(`<li class="px-6 py-4 text-red-600 text-center">Search failed</li>`);
                }
            });
        }, 300);
    } else {
        resultList.empty().addClass('hidden');
    }
});

$(document).on('click', '.product-item', function () {
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

    const container = selected.closest('.relative');
    const searchInput = container.find('.product-search, .freebie-search');
    const isFreebie = searchInput.hasClass('freebie-search');

    if (isFreebie) {
        // 🧃 Freebie selection
        row.find('.freebie-search').val(`${sku} - ${description}`);
        row.find('.freebie-sku-hidden').val(sku);
        row.find('.freebie-desc-hidden').val(description);
        row.find('.freebie-price-per-pc').val(pricePerPc).trigger('input');
        row.find('.freebie-qty-per-pc').val(casePack).trigger('input');
    } else {
        // 🛒 Product selection
        row.find('.product-search').val(`${sku} - ${description}`);
        row.find('.sku-hidden').val(sku);
        row.find('.desc-hidden').val(description);
        row.find('.price-per-pc').val(pricePerPc);
        row.find('.qty-per-pc').val(casePack);

        const paymentMode = $('[name="mode_payment"]').val();

        let scheme = '';
        if (paymentMode === 'PO15%') {
            scheme = po15Scheme || '';
        } else if (paymentMode === 'Cash' || paymentMode === 'Bank Card') {
            scheme = cashBankCardScheme || '';
        }

        row.find('.scheme-input').val(scheme);

        // 🎁 Freebie auto-fill (loopback)
// 🎁 Freebie auto-fill (loopback) via triggering search
        if (freebieSku) {
            const freebieInput = row.find('.freebie-search');
            const resultList = freebieInput.siblings('.search-results');

            freebieInput.val(freebieSku); // set freebie SKU
            resultList.empty().removeClass('hidden'); // show result list immediately

            // manually trigger keyup event to reuse search logic
            freebieInput.trigger('keyup');
        }

    }

    container.find('.search-results').empty().addClass('hidden');
});




// ✅ Hide dropdown on outside click
$(document).on('click', function (e) {
    if (!$(e.target).closest('.product-search, .freebie-search, .search-results').length) {
        $('.search-results').empty().addClass('hidden');
    }
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

document.querySelector('input[name="mbc_card_no"]').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 16);
});

const input = document.querySelector('.computed-freebie-amount');
if (input) {
  const observer = new MutationObserver(() => {
    console.log('Freebie amount changed to:', input.value);
  });

  observer.observe(input, { attributes: true, attributeFilter: ['value'] });
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
            const amount = format(getInputValue('input[name*="[amount]"]', container));
            const remarksSelect = container.querySelector('select[name*="[remarks]"]');
            const remarksVal = (remarksSelect && remarksSelect.options[remarksSelect.selectedIndex].disabled) ? '' : remarksSelect?.value || '';
            const remarks = format(remarksVal);
            const soNumber = format(getInputValue('input[name*="[store_order_no]"]', container));

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
                <td class="border px-2 py-1 text-center">${amount}</td>
                <td class="border px-2 py-1">${remarks}</td>
                <td class="border px-2 py-1">${soNumber}</td>
            `;
            summaryBody.appendChild(productRow);

            // Freebie Row
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
                <td class="border px-2 py-1 text-center">${freebieAmount}</td>
                <td class="border px-2 py-1">${remarks}</td>
                <td class="border px-2 py-1">${soNumber}</td>
            `;
            summaryBody.appendChild(freebieRow);
        });
    }



    const debouncedUpdate = debounce(updateTable, 200);

    orderWrapper.addEventListener('input', debouncedUpdate);
    orderWrapper.addEventListener('change', updateTable);

    updateTable(); // Initial load
});


    document.addEventListener('DOMContentLoaded', function () {
        const dispatchSelect = document.querySelector('.dispatch-controller');
        const deliveryFields = document.querySelectorAll('.delivery-field');

        function updateRequiredFields() {
            const selectedValue = dispatchSelect.value;
            const shouldRequire = selectedValue === 'Delivery Direct to Customer';

            deliveryFields.forEach(input => {
                input.classList.toggle('required-input', shouldRequire);
            });
        }

        // Initial run
        updateRequiredFields();

        // Watch for changes
        dispatchSelect.addEventListener('change', updateRequiredFields);
    });

document.addEventListener('DOMContentLoaded', function () {
    const requiredInputs = document.querySelectorAll('.order-form .required-input');
    const orderItemForm = document.querySelector('.order-item-form');
    const headerOffset = 80; // adjust if sticky header present
    let alertShown = false;

    function scrollToElement(element, offset = 80) {
        const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top, behavior: 'smooth' });
    }

    function allRequiredFilled() {
        return Array.from(requiredInputs).every(input => input.value.trim() !== '');
    }

    function checkForm() {
        if (allRequiredFilled()) {
            orderItemForm.classList.remove('hidden');

            if (!alertShown) {
                alertShown = true;

                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Info Complete',
                        text: 'Proceed to add order items.',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        setTimeout(() => scrollToElement(orderItemForm, headerOffset), 200);
                    });
                }, 200);
            }
        } else {
            orderItemForm.classList.add('hidden');
            alertShown = false; // reset so alert can be shown again
        }
    }

    // Initial check
    checkForm();

    requiredInputs.forEach(input => {
        input.addEventListener('input', checkForm);
    });
});




</script>


@endsection


