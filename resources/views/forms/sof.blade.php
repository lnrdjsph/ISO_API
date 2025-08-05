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
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-6">
            <!-- Request Details -->
            <section class="bg-white p-4 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold mb-4">Request Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm">Requesting Store</label>
                        <input value="{{ old('requesting_store', 'Test Store') }}" type="text" name="requesting_store" readonly 
                            class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm">Requested By</label>
                        <input value="{{ old('requested_by', 'Personnel Sample') }}" type="text" name="requested_by"  readonly
                            class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm">Date & Time of Order</label>
                        <input value="{{ old('time_order') }}" type="datetime-local" name="time_order" value="{{ $currentDateTime }}" 
                            class="w-full p-2 rounded border border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Channel of Order</label>
                        <select name="channel_order" class="w-full p-2 rounded border border-gray-300 text-sm">
                            <option disabled {{ old('channel_order') ? '' : 'selected' }}>Select channel</option>
                            @foreach(['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
                                <option value="{{ $option }}" {{ old('channel_order') == $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>

                    </div>
                </div>
            </section>

            <!-- Customer Info -->
            <section class="bg-white p-4 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 text-sm">MBC Card Number</label>
                        <input value="{{ old('mbc_card_no') }}" type="text" name="mbc_card_no"  maxlength="16" inputmode="numeric" pattern="\d*"
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Enter customer MBC Number">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Customer Name</label>
                        <input value="{{ old('customer_name') }}" type="text" name="customer_name" 
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Enter customer Name">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm">Contact Number</label>
                        <input value="{{ old('contact_number') }}" type="tel" name="contact_number" pattern="[0-9]{11}"  maxlength="12"
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="11-digit contact number">
                    </div>
                </div>
            </section>

            <!-- Payment Info -->
            <section class="bg-white p-4 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold mb-4">Payment Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Alpine.js CDN (include in your <head> if not yet) -->
                    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

                    <div x-data="{
                            open: false,
                            search: '',
                            options: [
                                'S10-MAASIN',
                                'S17-TACLOBAN',
                                'S19-METRO BAY-BAY',
                                'F18-ALANG-ALANG',
                                'F19-HILONGOS',
                                'S8-TOLEDO',
                                'H9-CARCAR',
                                'H10-BOGO'
                            ],
                            select(option) {
                                this.search = option;
                                this.open = false;
                            },
                            filtered() {
                                return this.options.filter(o => o.toLowerCase().includes(this.search.toLowerCase()));
                            }
                        }"
                        x-init="search = '{{ old('payment_center') }}'"
                        class="relative"
                    >
                        <label class="block mb-1 text-sm">Payment Center</label>
                        <input
                            value="{{ old('payment_center') }}"
                            x-model="search"
                            @input="open = true"
                            @click="open = true"
                            @keydown.escape="open = false"
                            type="text"
                            name="payment_center"
                            placeholder="Select or type Payment Center"
                            class="w-full p-2 rounded border border-gray-300 text-sm"
                            autocomplete="off"
                        >

                        <ul x-show="open && filtered().length"
                            @click.outside="open = false"
                            class="absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 shadow-lg max-h-40 overflow-auto text-sm"
                        >
                            <template x-for="option in filtered()" :key="option">
                                <li
                                    @click="select(option)"
                                    class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                                    x-text="option"
                                ></li>
                            </template>
                        </ul>
                    </div>


                    
                    <div>
                        <label class="block mb-1 text-sm">Mode of Payment</label>

                        <select name="mode_payment" 
                            class="w-full p-2 rounded border border-gray-300 text-sm">
                            
                            <option value="" disabled {{ old('mode_payment') ? '' : 'selected' }}>Select or type payment mode</option>
                            <option value="PO15%" {{ old('mode_payment') == 'PO15%' ? 'selected' : '' }}>PO15%</option>
                            <option value="Cash" {{ old('mode_payment') == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Bank Card" {{ old('mode_payment') == 'Bank Card' ? 'selected' : '' }}>Bank Card</option>
                            <option value="Online Payment" {{ old('mode_payment') == 'Online Payment' ? 'selected' : '' }}>Online Payment</option>
                        </select>
                    </div>



                    <div>
                        <label class="block mb-1 text-sm">Payment Date</label>
                        <input value="{{ old('payment_date') }}" type="date" name="payment_date" 
                            class="w-full p-2 rounded border border-gray-300 text-sm">
                    </div>
                </div>
            </section>

            <!-- Dispatch Info -->
            <section class="bg-white p-4 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold mb-4">Dispatch Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm">Mode of Dispatching</label>
    
                        <select name="mode_dispatching" 
                            class="w-full p-2 rounded border border-gray-300 text-sm dispatch-controller"
                            data-hide-value="Customer Pick-up" data-target=".delivery-group">
                            
                            <option value="" disabled {{ old('mode_dispatching') ? '' : 'selected' }}>Select dispatch mode</option>
                            <option value="Customer Pick-up" {{ old('mode_dispatching') == 'Customer Pick-up' ? 'selected' : '' }}>Customer Pick-up</option>
                            <option value="Delivery Direct to Customer" {{ old('mode_dispatching') == 'Delivery Direct to Customer' ? 'selected' : '' }}>Delivery Direct to Customer</option>
                            {{-- <option value="Courier" {{ old('mode_dispatching') == 'Courier' ? 'selected' : '' }}>Courier</option>
                            <option value="Other" {{ old('mode_dispatching') == 'Other' ? 'selected' : '' }}>Other</option> --}}
                        </select>
                    </div>


                    <div>
                        <label class="block mb-1 text-sm">Delivery/Pick-up Date</label>
                        <input value="{{ old('delivery_date') }}" type="date" name="delivery_date" 
                            class="w-full p-2 rounded border border-gray-300 text-sm">
                    </div>
                </div>

                <!-- Delivery Details -->
                <div class="delivery-group overflow-hidden transition-all duration-200 ease-in-out opacity-0 max-h-0 hidden mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-sm">Address</label>
                            <input value="{{ old('address') }}" type="text" name="address"
                                class="w-full p-2 rounded border border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="block mb-1 text-sm">Landmark</label>
                            <input value="{{ old('landmark') }}" type="text" name="landmark"
                                class="w-full p-2 rounded border border-gray-300 text-sm">
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg space-y-6 mt-6">

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
                    <div class="order-row relative border rounded-xl bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-100 shadow-sm hover:shadow-md space-y-6 bg-white transition-all duration-300 ease-in-out overflow-hidden max-h-[1000px] opacity-100 mb-6">
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
                                            <div class="relative w-full">
                                                <label class="block text-sm font-medium mb-1">Main Product</label>
                                                <input type="text"
                                                    class="product-search w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300"
                                                    placeholder="Enter SKU or Description"
                                                    autocomplete="off"
                                                    value="{{ old("orders.$i.sku") && old("orders.$i.item_description") ? old("orders.$i.sku") . ' - ' . old("orders.$i.item_description") : '' }}" />

                                                <ul class="search-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>

                                                <!-- Hidden Fields -->
                                                <input type="hidden" name="orders[{{ $i }}][sku]" class="sku-hidden" value="{{ old("orders.$i.sku") }}">
                                                <input type="hidden" name="orders[{{ $i }}][item_description]" class="desc-hidden" value="{{ old("orders.$i.item_description") }}">
                                            </div>

                                            <!-- Price/PCS -->
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Price/PC</label>
                                                <input type="number" step="0.01" name="orders[{{ $i }}][price_per_pc]"
                                                    value="{{ old("orders.$i.price_per_pc") }}"
                                                    class="w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  
                                                    style="text-align: left;" placeholder="0.00"/>
                                            </div>
                                            
                                        </div>

                                        <!-- Other Inputs -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                            <!-- QTY/PCS -->
                                            <div>
                                            <label class="block text-sm font-medium mb-1">QTY/PC</label>
                                            <input type="number" name="orders[{{ $i }}][qty_per_pc]"
                                                    value="{{ old("orders.$i.qty_per_pc") }}"
                                                    class="qty-pcs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  style="text-align: left;" placeholder="0"/>
                                            </div>

                                            <!-- QTY/CS -->
                                            <div>
                                                <label class="block text-sm font-medium mb-1">QTY/CS</label>
                                                <input type="number" name="orders[{{ $i }}][qty_per_cs]"
                                                    value="{{ old("orders.$i.qty_per_cs") }}"
                                                    class="qty-cs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" style="text-align: left;"  placeholder="0" />
                                                    
                                            </div>

                                        </div>

                                        <!-- Scheme Type and Scheme Inputs -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Scheme Type</label>
                                                <select name="orders[{{ $i }}][scheme_type]" 
                                                        class="scheme-type w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300">
                                                    <option value="SAME_FREEBIE" {{ old("orders.$i.scheme_type") == 'SAME_FREEBIE' ? 'selected' : '' }}>
                                                        Freebie: Same Product (default)
                                                    </option>
                                                    <option value="DIFF_FREEBIE" {{ old("orders.$i.scheme_type") == 'DIFF_FREEBIE' ? 'selected' : '' }}>
                                                        Freebie: Different Product
                                                    </option>
                                                </select>
                                            </div>


                                            <div>
                                                <label class="block text-sm font-medium mb-1">Scheme</label>
                                                <input type="text" name="orders[{{ $i }}][scheme]" 
                                                    value="{{ old("orders.$i.scheme") }}"
                                                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" placeholder="0"/>
                                            </div>
                                        </div>

                                        <!-- Freebie Section (Hidden by Default) -->
                                        <div class="freebie-grid mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 hidden" data-index="{{ $i }}">
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
                                                    class="w-full p-2 border border-gray-300 rounded text-left focus:outline-none focus:ring-gray-900 focus:border-gray-300"
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
                                                    class="qty-pcs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  style="text-align: left;" placeholder="0"/>
                                            </div>

                                            <!-- Freebie QTY/CS-->
                                            <div>
                                                <label class="block text-sm font-medium mb-1">Freebie QTY/CS</label>
                                                        <input type="text" name="orders[{{ $i }}][freebies_per_cs]"
                                                        value="{{ old("orders.$i.freebies_per_cs") }}"  
                                                        class="computed-freebies w-full p-2 bg-green-50 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" style="text-align: left;"  placeholder="0"  readonly/>
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
                                                <input type="text" name="orders[{{ $i }}][store_order_no]"
                                                    value="{{ old("orders.$i.store_order_no") }}"
                                                    class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" placeholder="Please Input Store Order No." /> 
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
    attachSchemeTypeListener(row); 
});


function attachSchemeTypeListener(row) {
    const select = row.querySelector('.scheme-type');
    const freebieSection = row.querySelector('.freebie-grid');
    const skuHidden = freebieSection?.querySelector('.freebie-sku-hidden');
    const descHidden = freebieSection?.querySelector('.freebie-desc-hidden');
    const searchInput = freebieSection?.querySelector('.freebie-search');
    const priceInput = freebieSection?.querySelector('input[name$="[freebie_price_per_pc]"]');

    if (select) {
        select.addEventListener('change', function () {
            if (this.value === 'DIFF_FREEBIE') {
                freebieSection?.classList.remove('hidden');
            } else {
                freebieSection?.classList.add('hidden');
                if (skuHidden) skuHidden.value = '';
                if (descHidden) descHidden.value = '';
                if (searchInput) searchInput.value = '';
                if (priceInput) priceInput.value = '';
            }
        });

        // Trigger initial state
        select.dispatchEvent(new Event('change'));
    }
}


// Listen to input events on QTY and Price fields
document.addEventListener('DOMContentLoaded', function () {
  const allRows = document.querySelectorAll('.order-row');
  allRows.forEach(row => calculateRowTotals(row));
});
document.addEventListener('input', function (e) {
  const row = e.target.closest('.order-row');
  if (!row) return;

  calculateRowTotals(row);
});

function calculateRowTotals(row) {
  const schemeInput = row.querySelector('input[name*="[scheme]"]');
  const qtyPcsInput = row.querySelector('.qty-pcs');
  const qtyCsInput = row.querySelector('.qty-cs');
  const totalQtyInput = row.querySelector('.total-qty');
  const pricePerPcInput = row.querySelector('input[name*="[price_per_pc]"]');

  const totalQtyDisplay = row.querySelector('.total-qty-display');
//   const freebiesDisplay = row.querySelector('.freebies-cs-display');
  const priceDisplay = row.querySelector('.price-display');
  const amountDisplay = row.querySelector('.amount-display');

  const breakdownPrice = row.querySelector('.breakdown-price');
  const breakdownAmount = row.querySelector('.breakdown-amount');
  const breakdownTotalQty = row.querySelector('.breakdown-total-qty');

  // Freebie readonly targets
  const freebieAmountDisplay = row.querySelector('.freebie-amount-display');
  const breakdownFreebieAmount = row.querySelector('.breakdown-freebie-amount');

  // Freebie inputs
  const freebiePriceInput = row.querySelector('input[name*="[freebie_price_per_pc]"]');
  const freebieQtyPcInput = row.querySelector('input[name*="[freebie_qty_per_pc]"]');
  const freebieQtyCsInput = row.querySelector('input[name*="[freebies_per_cs]"]');
  const freebiePriceInputField = row.querySelector('input[name*="[freebie_price]"]');


  // Hidden fields
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

  const freebiePrice = freebiePricePc * freebieQtyPc;

  // ✅ Calculate freebie amount using freebie QTY/CS
    let freebieAmount = 0;

    // Check scheme type
    const schemeTypeSelect = row.querySelector('select[name*="[scheme_type]"]');
    const schemeType = schemeTypeSelect?.value || 'SAME_FREEBIE';

    if (schemeType === 'SAME_FREEBIE') {
    // Use main product's price per pc
    freebieAmount = pricePerPc * qtyPcs * freebies;
    } else {
    // Use different freebie product price and qty
    freebieAmount = freebiePricePc * freebieQtyPc * freebiesPerCs;
    }

  // Update hidden fields
  if (totalQtyHidden) totalQtyHidden.value = totalCases;
  if (freebiesHidden) freebiesHidden.value = freebies;
  if (priceHidden) priceHidden.value = safeFixed(pricePerCase);
  if (amountHidden) amountHidden.value = safeFixed(totalAmount);
  if (freebieAmountHidden) freebieAmountHidden.value = safeFixed(freebieAmount);
  if (freebiePriceInputField) freebiePriceInputField.value = safeFixed(freebiePrice);


  // UI display
  if (totalQtyInput) totalQtyInput.value = totalCases;
  if (totalQtyDisplay) totalQtyDisplay.textContent = totalCases;
//   if (freebiesDisplay) freebiesDisplay.textContent = freebies;
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

                    const cleanedQuery = query.replace(/[^a-z0-9]/gi, '');
                    const filtered = data.filter(item => {
                        const cleanSku = item.sku.replace(/[^a-z0-9]/gi, '').toLowerCase();
                        const cleanDescription = item.description.replace(/[^a-z0-9]/gi, '').toLowerCase();
                        return cleanSku.includes(cleanedQuery) || cleanDescription.includes(cleanedQuery);
                    });

                    if (filtered.length === 0) {
                        resultList.append(`<li class="px-6 py-4 text-gray-500 text-center">No products found</li>`);
                    } else {
                        filtered.forEach(product => {
                            resultList.append(`
                                <li class="product-item px-4 py-2 hover:bg-gray-100 cursor-pointer transition-all"
                                    data-sku="${product.sku}"
                                    data-name="${product.description}">
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

// ✅ On product select (handles both product/freebie types)
$(document).on('click', '.product-item', function () {
    const selected = $(this);
    const container = selected.closest('.relative');

    const sku = selected.data('sku');
    const name = selected.data('descriiption');

    // Detect target input type
    const isFreebie = container.find('.freebie-search').length > 0;

    if (isFreebie) {
        container.find('.freebie-search').val(`${sku} - ${name}`);
        container.find('.freebie-sku-hidden').val(sku);
        container.find('.freebie-desc-hidden').val(name);
    } else {
        container.find('.product-search').val(`${sku} - ${name}`);
        container.find('.sku-hidden').val(sku);
        container.find('.desc-hidden').val(name);
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
        return el ? el.value || el.textContent || '' : '';
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

            // Scheme type check
            const schemeTypeSelect = container.querySelector('select[name*="[scheme_type]"]');
            const schemeType = schemeTypeSelect ? schemeTypeSelect.value : 'SAME_FREEBIE';
            const isDiffFreebie = schemeType === 'DIFF_FREEBIE';

            // Freebie Fields
            const freebieSku = format(getInputValue('input[name*="[freebie_sku]"]', container));
            const freebieDesc = format(getInputValue('input[name*="[freebie_description]"]', container));
            const freebiePrice = format(getInputValue('input[name*="[freebie_price_per_pc]"]', container));
            const freebiePriceTotal = format(getInputValue('input[name*="[freebie_price]"]', container));
            const freebieQtyPc = format(getInputValue('input[name*="[freebie_qty_per_pc]"]', container));
            const freebieQtyCS = format(getInputValue('input[name*="[freebies_per_cs]"]', container));
            const freebieQty = freebies;
            const freebieAmount = format(getInputValue('input[name*="[freebie_amount]"]', container));

            // Always show freebie row
            let usedFreebieSku, usedFreebieDesc, usedFreebiePrice, usedFreebiePriceTotal;
            let usedFreebieQtyPc, usedFreebieQty, usedFreebieAmount;

            if (isDiffFreebie) {
                usedFreebieSku = freebieSku;
                usedFreebieDesc = freebieDesc;
                usedFreebiePrice = freebiePrice;
                usedFreebiePriceTotal = freebiePriceTotal;
                usedFreebieQtyPc = freebieQtyPc;
                usedFreebieQty = freebieQty;
                usedFreebieAmount = freebieAmount;
            } else {
                usedFreebieSku = sku;
                usedFreebieDesc = desc;
                usedFreebiePrice = pricePerPc;
                usedFreebiePriceTotal = price;
                usedFreebieQtyPc = qtyPcs;
                usedFreebieQty = freebies;
                usedFreebieAmount = freebieAmount;
            }

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

            // Freebie Row (always shown)
            const freebieRow = document.createElement('tr');
            freebieRow.classList.add('bg-green-50');
            freebieRow.innerHTML = `
                <td class="border px-2 py-1">${usedFreebieSku}</td>
                <td class="border px-2 py-1">${usedFreebieDesc}</td>
                <td class="border px-2 py-1 text-center">Freebie</td>
                <td class="border px-2 py-1 text-center">${usedFreebiePrice}</td>
                <td class="border px-2 py-1 text-center">${usedFreebiePriceTotal}</td>
                <td class="border px-2 py-1 text-center">${usedFreebieQtyPc}</td>
                <td class="border px-2 py-1 text-center">-</td>
                <td class="border px-2 py-1 text-center">${usedFreebieQty}</td>
                <td class="border px-2 py-1 text-center">${usedFreebieQty}</td>
                <td class="border px-2 py-1 text-center">${usedFreebieAmount}</td>
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


</script>


@endsection


