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
    <form method="POST" action="{{ route('forms.sof_submit') }}" class="bg-white p-6 rounded-xl shadow-lg space-y-6">
      @csrf
      
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
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" />
                    <style>
                    .tagify__dropdown__item:hover {
                        background-color: #f3f4f6; /* Tailwind gray-100 */
                        color: black;
                    }
                    </style>
                    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
                    <label class="block mb-1 text-sm">Mode of Payment</label>
                    <input name="mode_payment" placeholder="Select or type payment mode" value="{{ old('mode_payment') }}" class="w-full p-2 rounded border border-gray-300 text-sm">
                </div>

                <script>
                new Tagify(document.querySelector('input[name=mode_payment]'), {
                    whitelist: ["PO15%", "Cash", "Bank Card", "Online Payment"],
                    dropdown: {
                        enabled: 0,
                        maxItems: 10
                    }
                });
                </script>



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
              <div class="order-row relative border p-6 rounded-xl bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-100 shadow-sm hover:shadow-md space-y-6 bg-white transition-all duration-300 ease-in-out overflow-hidden max-h-[1000px] opacity-100 py-4 mb-6">
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

                    <div class="">
                    <!-- Combined Grid: Left = Editable Inputs, Right = Readonly Invoice Style -->
                        <div class="order-content grid grid-cols-1 md:grid-cols-2 gap-6 ">

                            <!-- LEFT SIDE: Editable Inputs -->
                            <div class="editable-side space-y-4 transition-all duration-300 ease-in-out max-h-[2000px] opacity-100 py-4 mb-6">
                                <!-- SKU + Description -->
                                <div class="product-row grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- SKU -->
                                    <div class="relative w-full">
                                        <label class="block text-sm font-medium mb-1" >SKU</label>
                                        <input type="text" name="orders[{{ $i }}][sku]"
                                            value="{{ old("orders.$i.sku") }}"
                                            class="sku-input w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" 
                                            placeholder="Please input SKU" autocomplete="off" />
                                        <ul class="sku-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>
                                    </div>

                                    <!-- Item Description -->
                                    <div class="relative w-full">
                                        <label class="block text-sm font-medium mb-1">Item Description</label>
                                        <input type="text" name="orders[{{ $i }}][item_description]"
                                            value="{{ old("orders.$i.item_description") }}"
                                            class="desc-input w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" placeholder="Please Input Item Description"autocomplete="off" />
                                        <ul class="desc-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>
                                    </div>
                                </div>

                                <!-- Other Inputs -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Price/PCS -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Price/PC</label>
                                        <input type="number" step="0.01" name="orders[{{ $i }}][price_per_pc]"
                                            value="{{ old("orders.$i.price_per_pc") }}"
                                            class="w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  
                                            style="text-align: left;" placeholder="0"/>
                                    </div>
                                    <!-- QTY/PCS -->
                                    <div>
                                    <label class="block text-sm font-medium mb-1">QTY/PC</label>
                                    <input type="number" name="orders[{{ $i }}][qty_per_pc]"
                                            value="{{ old("orders.$i.qty_per_pc") }}"
                                            class="qty-pcs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300"  style="text-align: left;" placeholder="0"/>
                                    </div>


                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <!-- Scheme -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Scheme</label>
                                        <input type="text" name="orders[{{ $i }}][scheme]"
                                            value="{{ old("orders.$i.scheme") }}" id="schemeInput" 
                                        class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" placeholder="0"/>
                                    </div>
                                    <!-- Total QTY -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Total QTY/CS</label>
                                        <input type="number" name="orders[{{ $i }}][total_qty]"
                                            value="{{ old("orders.$i.total_qty") }}"
                                            class="total-qty w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" style="text-align: left;"  placeholder="0" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Remarks -->
                                    <div>
                                        <label class="block text-sm font-medium mb-1">Remarks</label>
                                        <input type="text" name="orders[{{ $i }}][remarks]"
                                            value="{{ old("orders.$i.remarks") }}"
                                            class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" placeholder="Please Input Remarks" />
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
                            <div class="readonly-side w-full md:col-span-1 bg-gray-50 border border-gray-200 rounded p-4 h-full flex flex-col justify-between transition-all duration-500">
                                <div class="space-y-2">
                                    
                                    <!-- Price -->
                                    <div class="flex justify-between items-start">
                                    <label class="block text-sm text-gray-600">Price</label>
                                    <div class="text-right text-black font-medium">
                                        ₱<span class="price-display">0.00</span>
                                        <input type="hidden" name="orders[{{ $i }}][price]"
                                            value="{{ old("orders.$i.price") }}" 
                                            class="computed-price"/>
                                        <div class="text-xs text-gray-500 breakdown-price"></div>
                                    </div>
                                    </div>

                                    <!-- QTY/CS -->
                                    <div class="flex justify-between items-start">
                                    <label class="block text-sm text-gray-600">QTY/CS</label>
                                    <div class="text-right text-black font-medium">
                                        <span class="qty-cs-display">0</span>
                                        <input type="hidden" name="orders[{{ $i }}][qty_per_cs]"
                                            value="{{ old("orders.$i.qty_per_cs") }}" 
                                            class="computed-qty-cs"  />
                                    </div>
                                    </div>

                                    <!-- Freebies/CS -->
                                    <div class="flex justify-between items-start">
                                    <label class="block text-sm text-gray-600">Freebies/CS</label>
                                    <div class="text-right text-black font-medium">
                                        <span class="freebies-cs-display">0</span>
                                        <input type="hidden" name="orders[{{ $i }}][freebies_per_cs]"
                                            value="{{ old("orders.$i.freebies_per_cs") }}"  
                                            class="computed-freebies" />
                                    </div>
                                    </div>
                                </div>

                                <!-- Total Amount -->
                                <div class="flex justify-between items-start pt-2">
                                    <label class="block text-sm text-gray-600">Total Amount</label>
                                    <div class="text-right text-black font-bold">
                                    ₱<span class="amount-display">0.00</span>
                                    <input type="hidden" name="orders[{{ $i }}][amount]"
                                            value="{{ old("orders.$i.amount") }}"   
                                            class="computed-amount" />
                                    <div class="text-xs text-gray-500 breakdown-amount"></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
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
    </form>
  </div>
</div>

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
            input.name = input.name.replace(/\[\d+\]/, `[${rowIndex}]`);
            input.removeAttribute('data-selected');
        });

        // Reset output display spans
        newRow.querySelector('.price-display').textContent = '0.00';
        newRow.querySelector('.amount-display').textContent = '0.00';
        newRow.querySelector('.qty-cs-display').textContent = '0';
        newRow.querySelector('.freebies-cs-display').textContent = '0';

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
        const content = row.querySelector('.order-content .editable-side');
        const readonly = row.querySelector('.order-content .readonly-side');
        const icon = toggle.querySelector('.collapse-icon');

        if (!content || !readonly || !icon) return;

        const collapsed = content.classList.contains('max-h-0');

        // Toggle editable-side visibility
        content.classList.toggle('max-h-0', !collapsed);
        content.classList.toggle('opacity-0', !collapsed);
        content.classList.toggle('py-0', !collapsed);
        content.classList.toggle('mb-0', !collapsed);
        content.classList.toggle('hidden', !collapsed); // ← new

        content.classList.toggle('max-h-[2000px]', collapsed);
        content.classList.toggle('opacity-100', collapsed);
        content.classList.toggle('py-4', collapsed);
        content.classList.toggle('mb-6', collapsed);
        content.classList.toggle('block', collapsed); // ← new

        // Make readonly full-width if collapsed
        readonly.classList.toggle('md:col-span-2', !collapsed); // ← new
        readonly.classList.toggle('md:col-span-1', collapsed);  // ← new

        icon.classList.toggle('rotate-180');
    });
}


    document.querySelectorAll('.order-row').forEach(row => {
        attachCollapseListener(row);
    });


</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Listen to input events on QTY and Price fields
document.addEventListener('input', function (e) {
  const row = e.target.closest('.order-row');
  if (!row) return;

  calculateRowTotals(row);
});

function calculateRowTotals(row) {
  const schemeInput = row.querySelector('input[name*="[scheme]"]');
  const qtyPcsInput = row.querySelector('.qty-pcs');
  const totalQtyInput = row.querySelector('.total-qty');
  const pricePerPcInput = row.querySelector('input[name*="[price_per_pc]"]');

  const qtyCsDisplay = row.querySelector('.qty-cs-display');
  const freebiesCsDisplay = row.querySelector('.freebies-cs-display');
  const priceDisplay = row.querySelector('.price-display');
  const amountDisplay = row.querySelector('.amount-display');

  const breakdownPrice = row.querySelector('.breakdown-price');
  const breakdownAmount = row.querySelector('.breakdown-amount');

  // Hidden inputs
  const qtyCsHidden = row.querySelector('.computed-qty-cs');
  const freebiesHidden = row.querySelector('.computed-freebies');
  const priceHidden = row.querySelector('.computed-price');
  const amountHidden = row.querySelector('.computed-amount');

  const schemeValue = schemeInput?.value || "1+1";
  const pricePerPc = parseFloat(pricePerPcInput?.value) || 0;
  const qtyPcs = parseInt(qtyPcsInput?.value) || 0;
  const totalQty = parseInt(totalQtyInput?.value) || 0;

  let [base = 1, free = 1] = schemeValue.replace(/[^0-9+]/g, '').split('+').map(n => parseInt(n) || 0);
  const setCount = base + free;

  const fullSets = Math.floor(totalQty / setCount);
  const qtyCs = fullSets * base;
  const freebies = fullSets * free;

  const price = qtyPcs * pricePerPc;
  const amount = qtyPcs * qtyCs * pricePerPc;

  // First: update hidden input values
  if (qtyCsHidden) qtyCsHidden.value = qtyCs;
  if (freebiesHidden) freebiesHidden.value = freebies;
  if (priceHidden) priceHidden.value = price.toFixed(2);
  if (amountHidden) amountHidden.value = amount.toFixed(2);

  // Then: update visible displays
  qtyCsDisplay.textContent = qtyCs;
  freebiesCsDisplay.textContent = freebies;
  priceDisplay.textContent = price.toFixed(2);
  amountDisplay.textContent = amount.toFixed(2);

  if (breakdownPrice) {
    breakdownPrice.textContent = `₱${pricePerPc.toFixed(2)} x ${qtyCs}`;
  }

  if (breakdownAmount) {
    breakdownAmount.textContent = `${qtyCs}cs × ${qtyPcs}pcs × ₱${pricePerPc.toFixed(2)}`;
  }
}



function formatCurrency(value) {
  return parseFloat(value || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}


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

$(document).on('keyup focus', '.sku-input, .desc-input', function () {
    clearTimeout(debounceTimeout);

    const input = $(this);
    const query = input.val().trim().toLowerCase();
    const isSkuInput = input.hasClass('sku-input');

    const resultList = input.siblings(isSkuInput ? '.sku-results' : '.desc-results');

    const productRow = input.closest('.product-row');
    const skuInput = productRow.find('.sku-input');
    const descInput = productRow.find('.desc-input');

    // Compare input with previously selected value
    const lastSelectedSku = skuInput.data('selected');
    const lastSelectedDesc = descInput.data('selected');

    if (isSkuInput && skuInput.val() !== lastSelectedSku) {
        descInput.val(''); // Clear description if SKU was changed
        descInput.removeData('selected');
    }

    if (!isSkuInput && descInput.val() !== lastSelectedDesc) {
        skuInput.val(''); // Clear SKU if Description was changed
        skuInput.removeData('selected');
    }

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
                url: '{{ route("products.search") }}',
                data: { query: query },
                success: function (data) {
                    input.removeClass('animate-pulse');
                    resultList.empty();

                    let cleanedQuery = query.replace(/[^a-z0-9]/gi, '');

                    let filtered = data.filter(item => {
                        let cleanSku = item.sku.replace(/[^a-z0-9]/gi, '').toLowerCase();
                        let cleanName = item.name.replace(/[^a-z0-9]/gi, '').toLowerCase();
                        return cleanSku.includes(cleanedQuery) || cleanName.includes(cleanedQuery);
                    });

                    if (filtered.length === 0) {
                        resultList.append(`<li class="px-6 py-4 text-gray-500 text-center">No products found</li>`);
                    } else {
                        filtered.forEach(product => {
                            resultList.append(`
                                <li class="product-item px-4 py-2 hover:bg-gray-100 cursor-pointer transition-all" 
                                    data-sku="${product.sku}" 
                                    data-name="${product.name}">
                                    <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded mr-2">${product.sku}</span>
                                    ${product.name}
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

// Handle item selection
$(document).on('click', '.product-item', function () {
    const item = $(this);
    const productRow = item.closest('.product-row');

    const selectedSku = item.data('sku');
    const selectedName = item.data('name');

    const skuInput = productRow.find('.sku-input');
    const descInput = productRow.find('.desc-input');

    // Update input values
    skuInput.val(selectedSku);
    descInput.val(selectedName);

    // Store selected values
    skuInput.data('selected', selectedSku);
    descInput.data('selected', selectedName);

    // Hide both dropdowns
    productRow.find('.sku-results, .desc-results').empty().addClass('hidden');
});

// Hide dropdown when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('.sku-input, .desc-input, .sku-results, .desc-results').length) {
        $('.sku-results, .desc-results').empty().addClass('hidden');
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
</script>


@endsection


