@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

    <!-- Header Section -->
    <div class="mb-8">
      <div class="flex items-center space-x-4">
        <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Submit Order</h1>
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

      <!-- Order Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="font-semibold block mb-2">Channel of Order:</label>
          <div class="flex flex-wrap gap-4">
            @foreach(['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
              <label class="inline-flex items-center space-x-2">
                <input type="radio" name="channel_order" value="{{ $option }}" required class="accent-indigo-500">
                <span>{{ $option }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div>
          <label class="font-semibold block mb-2">Time of Order:</label>
          <input type="time" name="time_order" class="w-full p-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" required>
        </div>
      </div>

      <!-- Payment Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach([
          'Payment Center' => 'payment_center',
          'Mode of Payment' => 'mode_payment',
          'Payment Date' => 'payment_date',
          'Mode of Dispatching' => 'mode_dispatching',
          'Delivery/Pick-up Date' => 'delivery_date'
        ] as $label => $name)
          <div>
            <label class="font-semibold block mb-2">{{ $label }}:</label>
            <input
              type="{{ str_contains($name, 'date') ? 'date' : 'text' }}"
              name="{{ $name }}"
              required
              class="w-full p-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
            >
          </div>
        @endforeach
      </div>


      <!-- Address -->
      <div>
        <label class="font-semibold block mb-2">Address:</label>
        <textarea name="address" rows="2" class="w-full p-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
      </div>

      <div>
        <label class="font-semibold block mb-2">Landmark:</label>
        <input type="text" name="landmark" class="w-full p-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
      </div>

      <!-- Order Items Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 text-sm">
            <thead class="bg-slate-100 text-left">
                <tr>
                    <th class="border px-2 py-1" rowspan="2">SKU</th>
                    <th class="border px-2 py-1" rowspan="2">ITEM DESCRIPTION</th>
                    <th class="border px-2 py-1" rowspan="2">SCHEME</th>
                    <th class="border px-2 py-1" rowspan="2">PRICE/PCS</th>
                    <th class="border px-2 py-1" rowspan="2">PRICE</th>
                    <th class="border px-2 py-1 text-center" colspan="3">ORDER /CS</th>
                    <th class="border px-2 py-1" rowspan="2">TOTAL QTY</th>
                    <th class="border px-2 py-1" rowspan="2">AMOUNT</th>
                    <th class="border px-2 py-1" rowspan="2">REMARKS</th>
                    <th class="border px-2 py-1" rowspan="2">STORE ORDER NO.(SO#)</th>
                </tr>
                <tr class="bg-slate-50 text-center">
                    <th class="border px-2 py-1">QTY/PCS</th>
                    <th class="border px-2 py-1">QTY/CS</th>
                    <th class="border px-2 py-1">FREEBIES/CS</th>
                </tr>
            </thead>
        <tbody>
            @for ($i = 0; $i < 3; $i++)
            <tr>
                <td class="border px-2 py-1">
                <input type="text" name="orders[{{ $i }}][sku]" class="w-full p-1 border rounded text-sm" />
                </td>
                <td class="border px-2 py-1">
                <input type="text" name="orders[{{ $i }}][item_description]" class="w-full p-1 border rounded text-sm" />
                </td>
                <td class="border px-2 py-1">
                <input type="text" name="orders[{{ $i }}][scheme]" class="w-full p-1 border rounded text-sm" />
                </td>
                <td class="border px-2 py-1 text-right">
                <input type="number" step="0.01" name="orders[{{ $i }}][price_per_pc]" class="w-full p-1 border rounded text-sm text-right" />
                </td>
                <td class="border px-2 py-1 text-right">
                <input type="number" step="0.01" name="orders[{{ $i }}][price]" class="w-full p-1 border rounded text-sm text-right" />
                </td>
                
                {{-- ORDER /CS Sub-columns --}}
                <td class="border px-2 py-1 text-center">
                <input type="number" name="orders[{{ $i }}][qty_pcs]" class="w-full p-1 border rounded text-sm text-right" />
                </td>
                <td class="border px-2 py-1 text-center">
                <input type="number" name="orders[{{ $i }}][qty_cs]" class="w-full p-1 border rounded text-sm text-right" />
                </td>
                <td class="border px-2 py-1 text-center">
                <input type="number" name="orders[{{ $i }}][freebies_cs]" class="w-full p-1 border rounded text-sm text-right" />
                </td>

                <td class="border px-2 py-1 text-right">
                <input type="number" name="orders[{{ $i }}][total_qty]" class="w-full p-1 border rounded text-sm text-right" />
                </td>
                <td class="border px-2 py-1 text-right">
                <input type="number" step="0.01" name="orders[{{ $i }}][amount]" class="w-full p-1 border rounded text-sm text-right" />
                </td>
                <td class="border px-2 py-1">
                <input type="text" name="orders[{{ $i }}][remarks]" class="w-full p-1 border rounded text-sm" />
                </td>
                <td class="border px-2 py-1">
                <input type="text" name="orders[{{ $i }}][store_order_no]" class="w-full p-1 border rounded text-sm" />
                </td>
            </tr>
            @endfor
        </tbody>
        </table>

      </div>

      <!-- Totals and Signatures -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-6 md:space-y-0">
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
      </div>

      <!-- Submit Button -->
      <div class="text-right">
        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:shadow-lg hover:scale-[1.02] transition">Submit Order</button>
      </div>
    </form>
  </div>
</div>
@endsection
