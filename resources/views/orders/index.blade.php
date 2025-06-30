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
          <p class="text-gray-600 mt-1">Fill out the form to create a new order record.</p>
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
        <ul class="mt-2 list-disc list-inside text-red-700">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Order Form -->
    <form method="POST" action="{{ route('orders.store') }}" class="bg-white p-6 rounded-xl shadow-lg space-y-6">
      @csrf
      
<!-- Request Details -->
<section class="bg-white p-4 rounded-lg shadow-sm mb-6">
    <h2 class="text-lg font-semibold mb-4">Request Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1 text-sm">Requesting Store</label>
            <input type="text" name="requesting_store" required readonly value="Test Store"
                class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
        </div>

        <div>
            <label class="block mb-1 text-sm">Requested By</label>
            <input type="text" name="requested_by" required readonly value="Personnel Sample"
                class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
        </div>

        <div>
            <label class="block mb-1 text-sm">Date & Time of Order</label>
            <input type="datetime-local" name="time_order" value="{{ $currentDateTime }}" readonly required
                class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
        </div>
    </div>
</section>

<!-- Customer Info -->
<section class="bg-white p-4 rounded-lg shadow-sm mb-6">
    <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1 text-sm">Customer Name</label>
            <input type="text" name="customer_name" required
                class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Enter customer name">
        </div>

        <div>
            <label class="block mb-1 text-sm">Contact Number</label>
            <input type="tel" name="contact_number" pattern="[0-9]{11}" required
                class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="11-digit contact number">
        </div>

        <div>
            <label class="block mb-1 text-sm">Channel of Order</label>
            <select name="channel_order" required
                class="w-full p-2 rounded border border-gray-300 text-sm">
                <option disabled selected>Select channel</option>
                @foreach(['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<!-- Payment Info -->
<section class="bg-white p-4 rounded-lg shadow-sm mb-6">
    <h2 class="text-lg font-semibold mb-4">Payment Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1 text-sm">Payment Center</label>
            <input type="text" name="payment_center" required
                class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Payment center">
        </div>

        <div>
            <label class="block mb-1 text-sm">Mode of Payment</label>
            <select name="mode_payment" required
                class="w-full p-2 rounded border border-gray-300 text-sm">
                <option disabled selected>Select payment mode</option>
                <option value="GCash">GCash</option>
                <option value="PayMaya">PayMaya</option>
                <option value="Card">Card</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 text-sm">Payment Date</label>
            <input type="date" name="payment_date" required
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
            <select name="mode_dispatching" required
                class="w-full p-2 rounded border border-gray-300 text-sm dispatch-controller"
                data-hide-value="Pick-up" data-target=".delivery-group">
                <option value=""disabled selected>Select dispatch mode</option>
                <option value="Pick-up">Pick-up</option>
                <option value="Delivery">Delivery</option>
                <option value="Courier">Courier</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div>
            <label class="block mb-1 text-sm">Delivery/Pick-up Date</label>
            <input type="date" name="delivery_date" required
                class="w-full p-2 rounded border border-gray-300 text-sm">
        </div>
    </div>

    <!-- Delivery Details -->
    <div class="delivery-group overflow-hidden transition-all duration-200 ease-in-out opacity-0 max-h-0 hidden mt-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 text-sm">Address</label>
                <input type="text" name="address"
                    class="w-full p-2 rounded border border-gray-300 text-sm">
            </div>

            <div>
                <label class="block mb-1 text-sm">Landmark</label>
                <input type="text" name="landmark"
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
          <div id="order-items" class="space-y-6">
              <!-- Sample Row -->
              <div class="order-row relative border p-6 rounded-xl bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-100 shadow-sm hover:shadow-md space-y-6 bg-white transition-all duration-300 ease-in-out overflow-hidden max-h-[1000px] opacity-100 py-4 mb-6">
                  <!-- Remove Button (Top-Right Trash Icon) -->
                  <div class="flex items-center space-x-3 mb-4">
                      <div class="item-number w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                          1
                      </div>
                      <h3 class="text-lg font-medium text-gray-900">Item No. 1</h3>
                  </div>
                  
                  <button type="button"
                      class="remove-row absolute top-0 right-6 text-red-500 hover:text-red-700 transition transform hover:scale-110">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22m-5-4H6a2 2 0 00-2 2v0a2 2 0 002 2h12a2 2 0 002-2v0a2 2 0 00-2-2z" />
                      </svg>
                  </button>

                  <!-- Row 1 and Row 2 as Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="product-row col-span-2 flex space-x-4">
                        <!-- SKU -->
                        <div class="relative w-full">
                            <label class="block text-sm font-medium mb-1">SKU</label>
                            <input type="text" class="sku-input w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" autocomplete="off" />
                            <ul class="sku-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>
                        </div>

                        <!-- Item Description -->
                        <div class="relative w-full">
                            <label class="block text-sm font-medium mb-1">Item Description</label>
                            <input type="text" class="desc-input w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" autocomplete="off" />
                            <ul class="desc-results absolute z-10 bg-white border border-gray-200 rounded shadow mt-1 hidden max-h-60 overflow-y-auto"></ul>
                        </div>
                    </div>

                      <!-- Scheme -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Scheme</label>
                          <input type="text" name="orders[0][scheme]"
                              class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                      <!-- Price/PCS -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Price/PCS</label>
                          <input type="number" step="0.01" name="orders[0][price_per_pc]"
                              class="w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                      <!-- Price -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Price</label>
                          <input type="number" step="0.01" name="orders[0][price]"
                              class="w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                      <!-- QTY/PCS -->
                      <div>
                          <label class="block text-sm font-medium mb-1">QTY/PCS</label>
                          <input type="number" name="orders[0][qty_pcs]" class="qty-pcs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                      <!-- QTY/CS -->
                      <div>
                          <label class="block text-sm font-medium mb-1">QTY/CS</label>
                          <input type="number" name="orders[0][qty_cs]" class="qty-cs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                      <!-- Freebies/CS -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Freebies/CS</label>
                          <input type="number" name="orders[0][freebies_cs]" class="freebies-cs w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                      <!-- Total QTY -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Total QTY</label>
                          <input type="number" name="orders[0][total_qty]" class="total-qty w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" readonly />
                      </div>
                      <!-- Amount -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Amount</label>
                          <input type="number" step="0.01" name="orders[0][amount]" class="amount w-full p-2 border border-gray-300 rounded text-right focus:outline-none focus:ring-gray-900 focus:border-gray-300" readonly />
                      </div>
                  </div>


                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <!-- Remarks -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Remarks</label>
                          <input type="text" name="orders[0][remarks]"
                              class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                      <!-- Store Order No. -->
                      <div>
                          <label class="block text-sm font-medium mb-1">Store Order No. (SO#)</label>
                          <input type="text" name="orders[0][store_order_no]"
                              class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-gray-900 focus:border-gray-300" />
                      </div>
                  </div>
              </div>
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
      <div class="text-right">
        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:shadow-lg hover:scale-[1.02] transition">Submit Order</button>
      </div>
    </form>
  </div>
</div>

<script>
let rowIndex = 1;

document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.getElementById('add-row-btn');
    const productCounter = document.getElementById('product-counter');

    addButton.addEventListener('click', function () {
        const container = document.getElementById('order-items');
        const newRow = container.children[0].cloneNode(true);

        // Reset input values and update input names
        newRow.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.name = input.name.replace(/\d+/, rowIndex);
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

        rowIndex++;

        updateRemoveButtonsState();
        updateCounter();
        updateRowNumbers();
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
            removeButton.classList.add('hidden');
        } else {
            removeButton.classList.remove('hidden');
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



</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Listen to input events on QTY and Price fields
document.addEventListener('input', function (e) {
    if (e.target.matches('.qty-pcs, .qty-cs, .freebies-cs, input[name*="[price_per_pc]"]')) {
        const row = e.target.closest('.order-row');

        // Get input values in the same row
        const qtyPcs = parseInt(row.querySelector('.qty-pcs').value) || 0;
        const qtyCs = parseInt(row.querySelector('.qty-cs').value) || 0;
        const freebiesCs = parseInt(row.querySelector('.freebies-cs').value) || 0;
        const pricePerPc = parseFloat(row.querySelector('input[name*="[price_per_pc]"]').value) || 0;

        // Compute total quantity and amount
        const totalQty = qtyPcs + (qtyCs * freebiesCs);
        const amount = totalQty * pricePerPc;

        // Update the row summary
        row.querySelector('.total-qty').value = totalQty;
        row.querySelector('.amount').value = amount.toFixed(2);
    }
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

</script>


@endsection


