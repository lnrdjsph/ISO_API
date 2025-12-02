@extends('layouts.app')

@section('content')
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
        $order->requesting_store = $locationMap[strtolower($order->requesting_store)] ?? $order->requesting_store;
    @endphp
    <style>
        .search-results {
            position: absolute;
            top: 100%;
            /* place directly below the td */
            left: 0;
            z-index: 9999;
            max-height: 15rem;
            /* max height */
            z-index: 9999;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
            min-width: 250px;
            max-width: 400px;
            white-space: nowrap;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table,
        tbody,
        tr,
        td {
            overflow: visible !important;
        }

        <style>.search-results {
            position: absolute;
            z-index: 9999;
            top: 100%;
            left: 0;
            max-height: 15rem;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
        }
    </style>
    </style>
    <form
        method="POST"
        action="{{ route('orders.update', $order->id) }}">
        @csrf
        @method('PUT')
        <div class="">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <!-- Header Section -->
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="rounded-md bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900">Sales Order Details</h1>
                            <p class="text-sm text-gray-600">Review detailed information about the selected order.</p>
                        </div>
                    </div>

                    <!-- Back Link -->
                    <a
                        href="{{ route('orders.index') }}"
                        class="text-md inline-flex items-center pe-2 font-medium text-gray-700 hover:underline">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-2 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Order List
                    </a>
                </div>




                <input
                    type="hidden"
                    name="id"
                    value="{{ $order->id }}">
                <div class="grid grid-cols-6 gap-2">
                    <div class="col-span-5 grid gap-2">
                        <!-- Customer and Payment Info -->
                        <div class="grid grid-cols-1 rounded rounded-xl border bg-white p-4 shadow-sm md:grid-cols-2">

                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="space-y-2 pe-6">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Customer Info</h3>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">MBC Card No</p>
                                        <input
                                            type="text"
                                            name="mbc_card_no"
                                            value="{{ $order->mbc_card_no ?? '' }}"
                                            data-original="{{ $order->mbc_card_no ?? '' }}"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            autocomplete="off"
                                            maxlength="16">
                                    </div>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Customer Name</p>
                                        <input
                                            type="text"
                                            name="customer_name"
                                            value="{{ $order->customer_name ?? '' }}"
                                            data-original="{{ $order->customer_name ?? '' }}"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            autocomplete="off"
                                            maxlength="100">
                                    </div>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Contact Number</p>
                                        <input
                                            type="text"
                                            name="contact_number"
                                            value="{{ $order->contact_number ?? '' }}"
                                            data-original="{{ $order->contact_number ?? '' }}"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            autocomplete="off"
                                            placeholder="-"
                                            maxlength="12">
                                    </div>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Email</p>
                                        <input
                                            type="email"
                                            name="email"
                                            value="{{ $order->email ?? '' }}"
                                            data-original="{{ $order->email ?? '' }}"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            autocomplete="off"
                                            placeholder="-"
                                            maxlength="100">
                                    </div>

                                </div>

                                <div class="space-y-2 pe-6">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Payment Info</h3>

                                    <!-- Payment Center -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Payment Center</p>
                                        <select
                                            name="payment_center"
                                            class="w-full appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="background-image: none;">
                                            <option
                                                value=""
                                                disabled
                                                {{ $order->payment_center ? '' : 'selected' }}>
                                                Select Payment Center
                                            </option>

                                            @foreach ($locationMap as $code => $label)
                                                <option
                                                    value="{{ $label }}"
                                                    {{ $order->payment_center === $label ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>


                                    <!-- Mode of Payment -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Mode of Payment</p>
                                        <select
                                            name="mode_payment"
                                            class="w-full appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="background-image: none;">
                                            <option
                                                value=""
                                                disabled
                                                {{ $order->mode_payment ? '' : 'selected' }}>Select or type payment mode</option>

                                            <option
                                                value="PO15%"
                                                {{ $order->mode_payment === 'PO15%' ? 'selected' : '' }}>PO15%</option>
                                            <option
                                                value="Cash / Bank Card"
                                                {{ $order->mode_payment === 'Cash / Bank Card' ? 'selected' : '' }}>Cash / Bank Card</option>
                                        </select>
                                    </div>

                                    <!-- Payment Date -->
                                    <div class="relative">
                                        <p class="mb-0.5 text-xs text-gray-600">Payment Date</p>
                                        <input
                                            type="date"
                                            name="payment_date"
                                            value="{{ $order->payment_date ? \Carbon\Carbon::parse($order->payment_date)->format('Y-m-d') : '' }}"
                                            class="payment-date relative w-full cursor-pointer appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="padding-right: 50%;">
                                    </div>

                                </div>

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="space-y-2 pe-6">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Delivery Info</h3>

                                    <!-- Mode of Dispatching -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Mode of Dispatching</p>
                                        <select
                                            name="mode_dispatching"
                                            class="w-full appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0">
                                            <option
                                                value=""
                                                disabled
                                                {{ !$order->mode_dispatching ? 'selected' : '' }}>Select Mode of Dispatch</option>
                                            <option
                                                value="Customer Pick-up"
                                                {{ old('mode_dispatching', $order->mode_dispatching) == 'Customer Pick-up' ? 'selected' : '' }}>
                                                Customer Pick-up
                                            </option>
                                            <option
                                                value="Delivery Direct to Customer"
                                                {{ old('mode_dispatching', $order->mode_dispatching) == 'Delivery Direct to Customer' ? 'selected' : '' }}>
                                                Delivery Direct to Customer
                                            </option>
                                        </select>
                                    </div>

                                    <style>
                                        /* Hide native dropdown arrow */
                                        select[name="mode_dispatching"]::-ms-expand {
                                            display: none;
                                        }

                                        select[name="mode_dispatching"] {
                                            background-image: none;
                                        }
                                    </style>

                                    <!-- Delivery/Pickup Date -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Delivery/Pickup Date</p>
                                        <input
                                            type="date"
                                            name="delivery_date"
                                            value="{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '' }}"
                                            class="delivery-date w-full cursor-pointer appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="padding-right: 50%;">
                                    </div>

                                    <!-- Address -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Address</p>
                                        <input
                                            type="text"
                                            name="address"
                                            value="{{ $order->address ?? '' }}"
                                            placeholder="-"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0">
                                    </div>

                                    <!-- Landmark -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Landmark</p>
                                        <input
                                            type="text"
                                            name="landmark"
                                            value="{{ $order->landmark ?? '' }}"
                                            placeholder="-"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0">
                                    </div>
                                </div>

                                <div class="space-y-2 border-l pe-6 ps-4">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Order Info</h3>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">SOF Order ID</p>
                                        <p class="text-xs font-medium text-gray-900">{{ $order->sof_id }}</p>
                                    </div>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Requesting Store & Personnel</p>
                                        <p class="text-xs font-medium text-gray-900">{{ $order->requesting_store }} -
                                            {{ \App\Models\User::find($order->requested_by)?->name ?? $order->requested_by }}</p>
                                    </div>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Channel Order</p>
                                        <p class="text-xs font-medium text-gray-900">{{ $order->channel_order }}</p>
                                    </div>
                                    @php
                                        $warehouseMap = [
                                            '80141' => 'Silangan Warehouse',
                                            '80001' => 'Central Warehouse',
                                            '80041' => 'Procter Warehouse',
                                            '80051' => 'Opao-ISO Warehouse',
                                            '80071' => 'Big Blue Warehouse',
                                            '80131' => 'Lower Tingub Warehouse',
                                            '80211' => 'Sta. Rosa Warehouse',
                                            '80181' => 'Bacolod Depot',
                                            '80191' => 'Tacloban Depot',
                                            // '80???' => 'Arellano Warehouse',
                                        ];
                                        $order->warehouse = $warehouseMap[$order->warehouse] ?? $order->warehouse;

                                    @endphp
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Warehouse</p>
                                        <p class="text-xs font-medium text-gray-900">{{ ucwords($order->warehouse) }}</p>
                                    </div>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Date & Time of Order</p>
                                        <p class="text-xs font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($order->time_order)->format('F j, Y - h:i A') }}</p>
                                    </div>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Order Status</p>
                                        <p class="text-xs font-medium text-gray-900">{{ ucwords($order->order_status) }}</p>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="relative overflow-x-auto overflow-y-visible rounded-xl border bg-white p-4 pb-24 shadow-sm">

                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-700">Ordered Items</h2>

                                {{-- <script>
																		document.getElementById('generateSOButton').addEventListener('click', function() {
																				const sofId = "{{ $order->sof_id }}";

																				fetch('/oracle/transfer', {
																								method: 'POST',
																								headers: {
																										'Content-Type': 'application/json',
																										'X-CSRF-TOKEN': '{{ csrf_token() }}'
																								},
																								body: JSON.stringify({
																										sof_id: sofId
																								})
																						})
																						.then(response => response.json())
																						.then(data => {
																								console.log(data);
																								alert('✅ TSF generated: ' + data.generated_tsf_no);
																						})
																						.catch(error => {
																								console.error(error);
																								alert('❌ Failed to generate SO.');
																						});
																		});
																</script> --}}



                            </div>

                            <table class="min-w-full border border-gray-200 text-xs text-gray-700">
                                <thead class="bg-gray-100 text-xs uppercase">
                                    <tr>
                                        <th
                                            rowspan="2"
                                            class="border px-2 py-1 text-center">No.</th>
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
                                            rowspan="2"
                                            class="border px-2 py-1 text-center">Discount</th>
                                        <th
                                            colspan="4"
                                            class="border px-2 py-1 text-center">Order in Cases</th>


                                        <th
                                            rowspan="2"
                                            class="border px-2 py-1 text-center">Amount</th>
                                        <th
                                            rowspan="2"
                                            class="border px-2 py-1">Remarks</th>
                                        <th
                                            rowspan="2"
                                            class="border px-2 py-1">Store Order No. (SO#)</th>

                                        <th rowspan="2" class="border px-2 py-1">Item Status</th>

                                    </tr>
                                    <tr>
                                        <th class="border p-1 text-center">QTY/PC</th>
                                        <th class="border p-1 text-center">QTY/CS</th>
                                        <th class="border p-1 text-center">Freebies</th>
                                        <th class="border p-1 text-center">Total QTY</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($order->items as $item)
                                        <tr
                                            data-index="{{ $loop->index }}"
                                            class="@if ($item->item_type === 'FREEBIE') bg-green-50 @else bg-white @endif transition hover:bg-indigo-50">


                                            {{-- hidden input for item_type --}}
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][item_type]"
                                                value="{{ $item->item_type }}">
                                            {{-- hidden input for item id --}}
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][id]"
                                                value="{{ $item->id }}">
                                            {{-- Number column --}}
                                            <td class="border p-2 text-center font-medium">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td
                                                class="relative border p-2"
                                                contenteditable="false"
                                                data-field="sku"
                                                contenteditable-search="true"
                                                style="position: relative;">
                                                {{ $item->sku }}
                                                <ul
                                                    class="search-results absolute z-50 hidden max-h-60 overflow-y-auto rounded border bg-white shadow"
                                                    style="min-width: 250px; max-width: 400px; white-space: nowrap; top: 100%; left: 0;">
                                                </ul>

                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][sku]"
                                                    value="{{ $item->sku }}"
                                                    class="sku-hidden" />
                                            </td>

                                            <td
                                                class="relative border p-2"
                                                contenteditable="false"
                                                data-field="item_description"
                                                contenteditable-search="true"
                                                style="position: relative;">
                                                {{ $item->item_description }}
                                                <ul
                                                    class="search-results absolute z-50 hidden max-h-60 overflow-y-auto rounded border bg-white shadow"
                                                    style="min-width: 250px; max-width: 400px; white-space: nowrap; top: 100%; left: 0;">
                                                </ul>

                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][item_description]"
                                                    value="{{ $item->item_description }}"
                                                    class="desc-hidden" />
                                            </td>

                                            <td
                                                class="border p-2 text-center"
                                                @if (in_array($item->scheme, ['Freebie', 'Discount'])) contenteditable="false" @else contenteditable="true" @endif
                                                data-field="scheme">
                                                {{ $item->scheme }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][scheme]"
                                                    value="{{ $item->scheme }}">
                                            </td>


                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="true"
                                                data-field="price_per_pc">{{ number_format($item->price_per_pc, 2) }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][price_per_pc]"
                                                    value="{{ $item->price_per_pc }}">
                                            </td>

                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="false"
                                                data-field="price">{{ number_format($item->price, 2) }}</td>
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][price]"
                                                value="{{ $item->price }}">
                                            <td
                                                class="border p-2 text-center"
                                                @if ($item->item_type == 'DISCOUNT') contenteditable="true" @endif
                                                data-field="discount">

                                                {{-- Always show numeric value only --}}
                                                {{ $item->item_type === 'DISCOUNT' ? $item->discount ?? 0 : 0 }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][discount]"
                                                    value="{{ $item->discount ?? 0 }}">
                                            </td>



                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="true"
                                                data-field="qty_per_pc">{{ $item->qty_per_pc }}</td>
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][qty_per_pc]"
                                                value="{{ $item->qty_per_pc }}">


                                            <td
                                                class="border p-2 text-center"
                                                @if ($item->item_type !== 'FREEBIE') contenteditable="true" @else contenteditable="false" @endif
                                                data-field="qty_per_cs">
                                                {{ $item->item_type !== 'FREEBIE' ? ($item->qty_per_cs == 0 ? '-' : $item->qty_per_cs) : 'N/A' }}

                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][qty_per_cs]"
                                                    value="{{ $item->item_type !== 'FREEBIE' ? $item->qty_per_cs : 0 }}" />
                                            </td>



                                            <td
                                                class="border p-2 text-center"
                                                @if ($item->item_type === 'FREEBIE') contenteditable="true" @else contenteditable="false" @endif
                                                data-field="freebies_per_cs">
                                                @if ($item->item_type === 'DISCOUNT')
                                                    N/A
                                                @else
                                                    {!! $item->freebies_per_cs == 0 ? '<span class="text-green-400">▼</span>' : $item->freebies_per_cs !!}
                                                @endif
                                            </td>


                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="false"
                                                data-field="total_qty">{{ $item->total_qty }}</td>
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][total_qty]"
                                                value="{{ $item->total_qty }}">


                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="false"
                                                data-field="amount">{{ number_format($item->amount, 2) }}</td>
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][amount]"
                                                value="{{ $item->amount }}">

                                            <td class="remark-cell border p-2 text-center">
                                                <div class="relative">
                                                    <select
                                                        name="items[{{ $loop->index }}][remarks]"
                                                        class="w-full cursor-pointer appearance-none border-none bg-transparent px-2 py-0 text-left text-xs transition-all duration-200 ease-in-out focus:outline-none focus:ring-0"
                                                        style="-webkit-appearance:none; -moz-appearance:none; appearance:none; background-image:none;">

                                                        <option value="For SO (Special Order)"
                                                            {{ $item->remarks === 'For SO (Special Order)' ? 'selected' : '' }}>
                                                            For SO (Special Order)
                                                        </option>

                                                        <option value="For RMS Approval"
                                                            {{ $item->remarks === 'For RMS Approval' ? 'selected' : '' }}>
                                                            For RMS Approval
                                                        </option>
                                                    </select>
                                                </div>
                                            </td>



                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="false"
                                                data-field="store_order_no">{{ $item->store_order_no }}</td>

                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][store_order_no]"
                                                value="{{ $item->store_order_no }}">


                                            {{-- Store Order No Column with API call --}}
                                            <td class="relative border p-2 text-center"
                                                contenteditable="false"
                                                data-field="store_order_no"
                                                data-item-index="{{ $loop->index }}"
                                                @if (!empty($item->store_order_no)) data-store-order-no="{{ $item->store_order_no }}" data-load-status="true" @endif>
                                                @if (!empty($item->store_order_no))
                                                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium">
                                                        <svg class="mr-1 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <div class="relative inline-block">
                                                        <div class="peer inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                                            N/A
                                                        </div>
                                                        <div
                                                            class="pointer-events-none absolute right-full top-1/2 z-50 mr-2 w-max -translate-y-1/2 whitespace-normal break-words rounded bg-gray-800 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
                                                            No store order number available
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>


                                        </tr>
                                    @empty
                                        <tr>
                                            <td
                                                colspan="12"
                                                class="border px-4 py-4 text-center text-gray-500">No items found for this order.</td>
                                        </tr>
                                    @endforelse
                                    <script>
                                        (function() {
                                            // Get all TDs with store order numbers
                                            const tds = document.querySelectorAll('td[data-field="store_order_no"][data-load-status="true"]');

                                            tds.forEach((td) => {
                                                const storeOrderNo = td.dataset.storeOrderNo;

                                                // Use Laravel route helper
                                                const url = "{{ route('order.status', ['storeOrderNo' => '__STORE_ORDER_NO__']) }}".replace('__STORE_ORDER_NO__', storeOrderNo);

                                                fetch(url)
                                                    .then(async response => {
                                                        const text = await response.text();

                                                        // Check if response is empty
                                                        if (!text) {
                                                            throw new Error('Empty response from server');
                                                        }

                                                        // Try to parse JSON
                                                        let data;
                                                        try {
                                                            data = JSON.parse(text);
                                                        } catch (parseError) {
                                                            throw new Error('Invalid response format');
                                                        }

                                                        return data;
                                                    })
                                                    .then(data => {
                                                        const status = data?.status ?? 'Unknown';

                                                        // Determine badge color and description
                                                        let badgeClass = 'bg-gray-100 text-gray-800';
                                                        let description = '';

                                                        if (status === 'Received') {
                                                            badgeClass = 'bg-green-100 text-green-800';
                                                            description = 'Order has been received by the store';
                                                        } else if (status === 'Shipped') {
                                                            badgeClass = 'bg-blue-100 text-blue-800';
                                                            description = 'Order is currently in transit to the store';
                                                        } else if (status === 'Processing') {
                                                            badgeClass = 'bg-yellow-100 text-yellow-800';
                                                            description = 'Order is being processed for shipment';
                                                        } else if (status === 'Pending') {
                                                            badgeClass = 'bg-orange-100 text-orange-800';
                                                            description = 'Order is pending for shipment';
                                                        } else if (status === 'Not Found') {
                                                            badgeClass = 'bg-red-100 text-red-800';
                                                            description = 'Order not found in the system';
                                                        } else if (status === 'N/A') {
                                                            badgeClass = 'bg-gray-100 text-gray-800';
                                                            description = 'No order number provided';
                                                        } else if (status === 'Error') {
                                                            badgeClass = 'bg-red-100 text-red-800';
                                                            description = 'An error occurred while checking the order status';
                                                        } else {
                                                            badgeClass = 'bg-gray-100 text-gray-800';
                                                            description = 'Order status is unknown';
                                                        }

                                                        td.innerHTML = `
                    <div class="relative inline-block">
                        <div class="peer inline-flex items-center rounded-full ${badgeClass} px-3 py-1 text-xs font-medium">
                            ${status}
                        </div>
                        <div class="pointer-events-none absolute right-full top-1/2 z-[100000] mr-2 w-max -translate-y-1/2 whitespace-normal break-words rounded bg-gray-800 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
                            ${description}
                        </div>
                    </div>
                `;
                                                    })
                                                    .catch(error => {
                                                        td.innerHTML = `
                    <div class="relative inline-block">
                        <div class="peer inline-flex items-center rounded-full bg-red-100 text-red-800 px-3 py-1 text-xs font-medium">
                            Error
                        </div>
                        <div class="pointer-events-none absolute right-full top-1/2 z-[100000] mr-2 w-max -translate-y-1/2 whitespace-normal break-words rounded bg-gray-800 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
                            Failed to load order status. Please try again later.
                        </div>
                    </div>
                `;
                                                    });
                                            });
                                        })();
                                    </script>
                                </tbody>

                            </table>

                            <div class="my-4 flex items-center justify-end gap-2">
                                {{-- @php
                                    $hasStoreOrderNo = false;
                                @endphp

                                @forelse ($order->items as $item)
                                    @if (!empty($item->store_order_no))
                                        @php $hasStoreOrderNo = true; @endphp
                                    @endif
                                @empty
                                @endforelse --}}

                                {{-- @if ($hasStoreOrderNo)
                                    <button
                                        type="button"
                                        id="compareBOLButton"
                                        class="items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-xs font-medium text-white shadow-sm transition duration-200 hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50">
                                        Compare to BOL
                                    </button>
                                @endif --}}


                                @if ($order->order_status === 'approved')
                                    <button
                                        type="button"
                                        id="generateSOButton"
                                        class="items-center justify-center rounded-md bg-green-700 px-3 py-2 text-xs font-medium text-white shadow-sm transition duration-200 hover:bg-green-800 focus:outline-none focus:ring-1 focus:ring-green-600 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50">
                                        Generate SO#
                                    </button>
                                    <meta
                                        name="csrf-token"
                                        content="{{ csrf_token() }}">
                                @endif
                            </div>

                        </div>


                    </div>
                    <div class="relative col-span-1 grid">
                        <div class="relative grid grid-cols-1 gap-2">



                            <div class="relative flex h-full flex-col justify-between gap-2 space-y-2 rounded-lg border bg-white p-3 shadow-sm">

                                <!-- Top: Order Actions -->
                                <div>
                                    <label
                                        for="orderAction"
                                        class="mb-2 block text-xs font-medium text-gray-700">
                                        Order Actions
                                    </label>

                                    @if ($order->order_status !== 'completed')
                                        <select
                                            id="orderAction"
                                            class="w-full rounded-md border-gray-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Select Action --</option>

                                            @if (Auth::user()->role !== 'manager')
                                                @if ($order->order_status !== 'cancelled')
                                                    <option value="cancel">Cancel Order</option>
                                                @endif

                                                @if ($order->order_status === 'cancelled')
                                                    <option value="restore">Restore Order</option>
                                                @endif

                                                @if (!in_array($order->order_status, ['for approval', 'approved', 'rejected']))
                                                    <option value="for approval">Request For Approval</option>
                                                @endif

                                                @if ($order->order_status === 'approved')
                                                    <option value="complete">Complete Order</option>
                                                @endif

                                                {{-- @if ($order->order_status !== 'processing')
																								<option value="processing">Mark as Processing</option>
																						@endif

																						@if ($order->order_status !== 'completed')
																								<option value="completed">Mark as Completed</option>
																						@endif --}}
                                            @endif

                                            @if (in_array(Auth::user()->role, ['manager', 'super admin']))
                                                @if (in_array($order->order_status, ['for approval', 'approved', 'rejected']))
                                                    <option value="approve">Approve Order</option>
                                                @endif

                                                @if (in_array($order->order_status, ['for approval', 'approved', 'rejected']))
                                                    <option value="rejected">Reject Order</option>
                                                @endif
                                            @endif
                                        </select>
                                    @endif
                                    @if ($order->approval_document)
                                        <div class="mt-4 rounded-md border border-dashed border-gray-400 bg-gray-50 p-4">
                                            <h3 class="mb-2 text-sm font-semibold text-gray-700">Approval Document</h3>
                                            <a
                                                href="{{ asset('storage/' . $order->approval_document) }}"
                                                onclick="event.preventDefault(); previewApprovalDocument(this.href);"
                                                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                                📂 View / Download
                                            </a>

                                            <script>
                                                function previewApprovalDocument(url) {
                                                    const fileExtension = url.split('.').pop().toLowerCase();
                                                    let content = '';

                                                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                                                        content =
                                                            `<img src="${url}" style="width:100%; max-height:600px; object-fit:contain; cursor: zoom-in;" onclick="this.style.transform=this.style.transform==='scale(2)' ? 'scale(1)' : 'scale(3)'; this.style.transition='transform 0.3s';">`;
                                                    } else {
                                                        content = `<iframe src="${url}" width="100%" height="600px" frameborder="0"></iframe>`;
                                                    }

                                                    Swal.fire({
                                                        title: 'Approval Document Preview',
                                                        html: content,
                                                        width: '80%',
                                                        showCloseButton: true,
                                                        showConfirmButton: true,
                                                        confirmButtonText: 'Download',
                                                        scrollbarPadding: false,
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            window.open(url, '_blank');
                                                        }
                                                    });
                                                }
                                            </script>

                                        </div>
                                    @endif

                                    <!-- Print Buttons -->
                                    <div class="mt-4 grid w-full grid-cols-2 gap-2">
                                        <a
                                            href="{{ route('orders.print.sof', $order->id) }}"
                                            target="_blank"
                                            class="flex w-full items-center justify-center rounded-md border bg-white px-1 py-1 text-center text-xs font-medium text-blue-700 shadow-sm hover:bg-blue-50">
                                            Print SOF
                                        </a>
                                        <a
                                            href="{{ route('orders.print.sof_invoice', $order->id) }}"
                                            target="_blank"
                                            class="flex w-full items-center justify-center rounded-md border bg-white px-1 py-1 text-center text-xs font-medium text-green-700 shadow-sm hover:bg-green-50">
                                            Print Invoice
                                        </a>
                                        @php
                                            $hasFreebies = \DB::table('order_items')->where('order_id', $order->id)->where('item_type', 'FREEBIE')->exists();
                                        @endphp

                                        @if ($hasFreebies)
                                            <a
                                                href="{{ route('orders.print.freebies', $order->id) }}"
                                                target="_blank"
                                                class="flex w-full items-center justify-center rounded-md border bg-white px-1 py-1 text-center text-xs font-medium text-purple-700 shadow-sm hover:bg-purple-50">
                                                Print Freebies Form
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('orders.print.order_slip', $order->id) }}"
                                            target="_blank"
                                            class="flex w-full items-center justify-center rounded-md border bg-white px-1 py-1 text-center text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                                            Print Order Slip
                                        </a>
                                    </div>




                                </div>

                                <!-- Middle: Mock Notes Box -->
                                <div class="mt-3 flex-1 space-y-2">

                                    <div class="mt-3 flex-1 space-y-2">
                                        <h3 class="text-xs font-semibold text-gray-600">📄 Order Notes</h3>

                                        <div class="rounded-md border border-gray-200 bg-gray-50 p-2">
                                            <div class="custom-scrollbar max-h-48 space-y-2 overflow-y-auto pr-1">
                                                @forelse ($order->notes as $note)
                                                    <div class="rounded-md border border-gray-200 bg-white p-2 text-xs">
                                                        <div class="flex justify-between">
                                                            <span class="font-medium text-gray-700">{{ strtoupper($note->status) }}</span>
                                                            <span class="text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                                                        </div>
                                                        <p class="mt-1 text-gray-600">{{ $note->note ?? '—' }}</p>
                                                        <p class="mt-1 italic text-gray-400">By: {{ $note->user->name ?? 'System' }}</p>
                                                    </div>
                                                @empty
                                                    <div class="flex h-20 items-center justify-center rounded-md bg-gray-50 text-xs text-gray-400">
                                                        No notes yet.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>



                                </div>

                                @if (session('success'))
                                    <div class="rounded-md border border-green-400 bg-green-100 px-3 py-2 text-xs text-green-700">
                                        ✅ {{ session('success') }}
                                    </div>
                                @endif
                                <div class="relative">
                                    @if ($errors->any())
                                        <div class="rounded-md border border-red-400 bg-red-100 px-3 py-2 text-xs text-red-700">
                                            <strong>⚠ Whoops!</strong> Problems:
                                            <ul class="mt-1 list-disc pl-4">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if (Auth::user()->role !== 'manager')
                                        <div
                                            id="changesCounter"
                                            class="hidden text-center text-xs text-gray-600">
                                            <span
                                                id="changesCount"
                                                class="font-semibold">0</span> field(s) modified
                                        </div>
                                        <!-- Bottom: Submit Button -->
                                        <button
                                            type="submit"
                                            id="submitButton"
                                            class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-xs font-medium text-white shadow-sm transition duration-200 hover:bg-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50"
                                            disabled>
                                            <span id="submitButtonText">Update</span>
                                        </button>
                                    @endif
                                </div>
                            </div>



                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const actionSelect = document.getElementById('orderAction');
                                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                    const existingIdInput = document.querySelector('input[name="id"]');
                                    const orderId = existingIdInput ? existingIdInput.value : '';

                                    actionSelect.addEventListener('change', function() {
                                        const action = this.value;
                                        if (!action) return;

                                        let actionText = '';
                                        let confirmColor = '#3085d6';

                                        switch (action) {
                                            case 'cancel':
                                                actionText = 'This order will be cancelled!';
                                                confirmColor = '#B91C1C';
                                                break;
                                                // complete
                                            case 'complete':
                                                actionText = 'This order will be marked as Completed!';
                                                confirmColor = '#16A34A';
                                                break;

                                            case 'restore':
                                                actionText = 'This order will be restored!';
                                                confirmColor = '#16A34A';
                                                break;
                                            case 'for approval':
                                                actionText = 'Send this order for manager approval?';
                                                confirmColor = '#2563EB';
                                                break;
                                            case 'approve':
                                                Swal.fire({
                                                    title: 'Approve Order',
                                                    html: `
            <div style="text-align:center; font-size:14px; color:#444;">



                <!-- Upload Section -->
                <div id="uploadSection">
                    <p style="margin-bottom:12px;">Upload approval document (PDF, Word, or Image):</p>

                    <div id="uploadBox"
                        style="border:2px dashed #2563EB; border-radius:8px;
                               padding:20px; background:#f9fafb; cursor:pointer;
                               transition:0.2s ease-in-out;">
                        <input type="file" id="approvalFile"
                            accept=".pdf,.doc,.docx,image/*"
                            style="display:none;" />

                        <label for="approvalFile"
                            style="cursor:pointer; display:block; color:#2563EB; font-weight:500; font-size:13px;">
                            Click to select a file
                        </label>

                        <p id="fileName"
                            style="margin-top:8px; font-size:12px; color:#666; font-style:italic;">
                            No file chosen
                        </p>
                    </div>
                </div>
                                <!-- Checkbox -->
                <div class="mt-2">
                    <label style="font-size:13px; cursor:pointer;">
                        <input type="checkbox" id="noDocumentCheckbox" style="margin-right:6px;">
                        No approval document needed
                    </label>
                </div>
            </div>
        `,
                                                    icon: 'info',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#16A34A',
                                                    cancelButtonColor: '#aaa',
                                                    confirmButtonText: 'Approve',

                                                    didOpen: () => {
                                                        const noDocCheckbox = document.getElementById('noDocumentCheckbox');
                                                        const uploadSection = document.getElementById('uploadSection');
                                                        const uploadBox = document.getElementById('uploadBox');
                                                        const fileInput = document.getElementById('approvalFile');
                                                        const fileName = document.getElementById('fileName');

                                                        // Toggle upload section
                                                        noDocCheckbox.addEventListener('change', () => {
                                                            uploadSection.style.display = noDocCheckbox.checked ? 'none' : 'block';
                                                        });

                                                        // Clicking the box opens file picker
                                                        uploadBox.addEventListener('click', () => !noDocCheckbox.checked && fileInput.click());

                                                        // Show selected file name
                                                        fileInput.addEventListener('change', () => {
                                                            fileName.textContent = fileInput.files.length ?
                                                                fileInput.files[0].name :
                                                                'No file chosen';
                                                        });

                                                        // Drag & drop
                                                        uploadBox.addEventListener('dragover', (e) => {
                                                            if (!noDocCheckbox.checked) {
                                                                e.preventDefault();
                                                                uploadBox.style.background = "#eef2ff";
                                                            }
                                                        });

                                                        uploadBox.addEventListener('dragleave', () => {
                                                            uploadBox.style.background = "#f9fafb";
                                                        });

                                                        uploadBox.addEventListener('drop', (e) => {
                                                            if (!noDocCheckbox.checked) {
                                                                e.preventDefault();
                                                                uploadBox.style.background = "#f9fafb";
                                                                if (e.dataTransfer.files.length) {
                                                                    fileInput.files = e.dataTransfer.files;
                                                                    fileName.textContent = e.dataTransfer.files[0].name;
                                                                }
                                                            }
                                                        });
                                                    },

                                                    preConfirm: () => {
                                                        const noDocCheckbox = document.getElementById('noDocumentCheckbox');
                                                        const file = document.getElementById('approvalFile').files[0];

                                                        // If checkbox is checked → skip validation
                                                        if (noDocCheckbox.checked) {
                                                            return {
                                                                skip: true,
                                                                file: null
                                                            };
                                                        }

                                                        // Otherwise require a file
                                                        if (!file) {
                                                            Swal.showValidationMessage('Please upload a document or check the box.');
                                                            return false;
                                                        }

                                                        return {
                                                            skip: false,
                                                            file: file
                                                        };
                                                    }
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        const file = result.value.file ?? null;
                                                        submitForm('approve', null, file);
                                                    } else {
                                                        actionSelect.value = '';
                                                    }
                                                });

                                                return;





                                            case 'rejected':
                                                actionText = 'Please provide a note before rejecting this order.';
                                                confirmColor = '#B91C1C';
                                                break;
                                            case 'processing':
                                                actionText = 'This order will be marked as Processing!';
                                                confirmColor = '#2563EB';
                                                break;
                                            case 'completed':
                                                actionText = 'This order will be marked as Completed!';
                                                confirmColor = '#2563EB';
                                                break;
                                        }


                                        // Handle rejection or cancellation with a textarea prompt
                                        if (action === 'rejected' || action === 'cancel') {
                                            let title = action === 'rejected' ? 'Reject Order' : 'Cancel Order';
                                            let confirmBtn = action === 'rejected' ? 'Reject' : 'Cancel Order';


                                            Swal.fire({
                                                title: title,
                                                text: actionText,
                                                icon: 'warning',
                                                input: 'textarea',
                                                inputPlaceholder: action === 'rejected' ?
                                                    'Enter rejection note...' : 'Enter cancellation reason...',
                                                inputValidator: (value) => {
                                                    if (!value) {
                                                        return 'You must provide a note!';
                                                    }
                                                },
                                                showCancelButton: true,
                                                confirmButtonColor: confirmColor,
                                                cancelButtonColor: '#aaa',
                                                cancelButtonText: 'Close',
                                                confirmButtonText: confirmBtn
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    submitForm(action, result.value); // pass note
                                                } else {
                                                    this.value = '';
                                                }
                                            });
                                        } else {
                                            // Normal flow
                                            Swal.fire({
                                                title: 'Are you sure?',
                                                text: actionText,
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: confirmColor,
                                                cancelButtonColor: '#aaa',
                                                confirmButtonText: 'Yes, Proceed',
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    submitForm(action);
                                                } else {
                                                    this.value = '';
                                                }
                                            });
                                        }
                                    });

                                    function submitForm(action, note = null, file = null) {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.enctype = 'multipart/form-data'; // ✅ needed for file upload
                                        form.style.display = 'none';

                                        switch (action) {
                                            case 'cancel':
                                                form.action = "{{ route('orders.cancel') }}";
                                                if (note) {
                                                    const noteInput = document.createElement('input');
                                                    noteInput.type = 'hidden';
                                                    noteInput.name = 'note';
                                                    noteInput.value = note;
                                                    form.appendChild(noteInput);
                                                }
                                                break;
                                            case 'complete':
                                                form.action = "{{ route('orders.complete') }}";
                                                break;

                                            case 'restore':
                                                form.action = "{{ route('orders.restore') }}";
                                                break;

                                            case 'for approval':
                                                form.action = "{{ route('orders.for_approval') }}";
                                                break;

                                            case 'approve':
                                                form.action = "{{ route('orders.approve') }}";
                                                if (file) {
                                                    const fileInput = document.createElement('input');
                                                    fileInput.type = 'file';
                                                    fileInput.name = 'attachment';

                                                    // ✅ Attach file object
                                                    const dataTransfer = new DataTransfer();
                                                    dataTransfer.items.add(file);
                                                    fileInput.files = dataTransfer.files;

                                                    form.appendChild(fileInput);
                                                }
                                                break;

                                            case 'rejected':
                                                form.action = "{{ route('orders.reject') }}";
                                                if (note) {
                                                    const noteInput = document.createElement('input');
                                                    noteInput.type = 'hidden';
                                                    noteInput.name = 'note';
                                                    noteInput.value = note;
                                                    form.appendChild(noteInput);
                                                }
                                                break;

                                            default: // processing, completed
                                                form.action = "{{ route('orders.archive') }}";
                                                const statusInput = document.createElement('input');
                                                statusInput.type = 'hidden';
                                                statusInput.name = 'status';
                                                statusInput.value = action;
                                                form.appendChild(statusInput);
                                                break;
                                        }

                                        // CSRF token
                                        const inputCsrf = document.createElement('input');
                                        inputCsrf.type = 'hidden';
                                        inputCsrf.name = '_token';
                                        inputCsrf.value = csrfToken;
                                        form.appendChild(inputCsrf);

                                        // Order ID
                                        const orderIdInput = document.createElement('input');
                                        orderIdInput.type = 'hidden';
                                        orderIdInput.name = 'id';
                                        orderIdInput.value = orderId;
                                        form.appendChild(orderIdInput);

                                        document.body.appendChild(form);
                                        form.submit();
                                    }

                                });
                            </script>



                            <div class="relative rounded-xl border bg-white p-4 pb-24 shadow-sm">
                                <div class="mb-4 items-center justify-between">
                                    <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-700">INVOICE</h2>
                                </div>

                                <div class="mb-4 items-center justify-between border-t border-gray-200 pt-4">
                                    <p class="text-center text-lg font-extrabold text-blue-600">
                                        {{-- ₱<span id="totalAmount">0.00</span> --}}
                                    </p>
                                </div>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex justify-between text-xs">
                                        <span>Grand Total</span>
                                        <span class="font-semibold">₱<span id="totalAmount">{{ number_format($order->items->sum('amount'), 2) }}</span></span>
                                    </div>

                                    @php
                                        $freebieTotal = $order->items->where('item_type', 'FREEBIE')->sum('amount') ?? 0;
                                        $discountTotal = $order->items->where('item_type', 'DISCOUNT')->sum('amount') ?? 0;
                                    @endphp

                                    <div
                                        id="freebieRow"
                                        class="{{ $freebieTotal > 0 ? '' : 'hidden' }} flex justify-between text-xs text-green-600">
                                        <span>Total Freebies Amount</span>
                                        <span class="font-semibold">- ₱<span id="freebieTotal">{{ number_format($freebieTotal, 2) }}</span></span>
                                    </div>

                                    <div
                                        id="discountRow"
                                        class="{{ $discountTotal > 0 ? '' : 'hidden' }} flex justify-between text-xs text-green-600">
                                        <span>Discount Savings</span>
                                        <span class="font-semibold">- ₱<span id="discountTotal">{{ number_format($discountTotal, 2) }}</span></span>
                                    </div>


                                    <hr>

                                    <div class="flex justify-between text-xs text-blue-600">
                                        <span>Total Payable Amount</span>
                                        <span class="font-semibold">₱<span id="mainTotal">{{ number_format($order->items->sum('amount') - $freebieTotal - $discountTotal, 2) }}</span></span>
                                    </div>
                                </div>



                            </div>



                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
    @if (request()->routeIs('orders.show'))
        {{-- script jquery --}}
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        {{-- script for input change detection --}}
        <!-- Complete Order Editing System -->


        <!-- Enhanced JavaScript System -->
        <script>
            $(document).ready(function() {
                // Change Detection System
                let hasChanges = false;
                let changesCount = 0;
                const submitButton = $('#submitButton');
                const submitButtonText = $('#submitButtonText');
                const changesCounter = $('#changesCounter');
                const changesCountElement = $('#changesCount');

                // Order Calculation System
                class OrderCalculationSystem {
                    constructor() {
                        this.initializeEventListeners();
                        this.calculateAllRows();
                    }

                    initializeEventListeners() {
                        $(document).on(
                            "input blur keyup",
                            [
                                'td[data-field="price_per_pc"]',
                                'td[data-field="qty_per_pc"]',
                                'td[data-field="qty_per_cs"]',
                                'td[data-field="freebies_per_cs"]',
                                'td[data-field="scheme"]',
                                'td[data-field="discount"]'
                            ].join(","),
                            (e) => {
                                const row = e.target.closest("tr");
                                this.calculateRowTotals(row);
                                setTimeout(() => updateOrderTotal(), 50);
                            }
                        );

                        $(document).on(
                            "focus",
                            'td[data-field="qty_per_cs"], td[data-field="freebies_per_cs"]',
                            function() {
                                if ($(this).text().trim() === "-") {
                                    $(this).text("0");
                                }
                            }
                        );

                        $(document).on(
                            "blur",
                            'td[data-field="qty_per_cs"], td[data-field="freebies_per_cs"]',
                            function() {
                                const value = $(this).text().trim();
                                if (value === "" || value === "0") {
                                    $(this).text("-");
                                }
                            }
                        );
                    }

                    calculateAllRows() {
                        const rows = $("tbody tr[data-index]").toArray();
                        let pendingFreebieQty = 0;

                        for (let i = 0; i < rows.length; i++) {
                            const row = rows[i];
                            const index = $(row).data("index");
                            if (index === undefined) continue;

                            const itemTypeInput = row.querySelector(`input[name="items[${index}][item_type]"]`);
                            const itemType = itemTypeInput?.value || "MAIN";

                            if (itemType === "MAIN") {
                                // ... existing MAIN calculation logic ...
                                const schemeInput = row.querySelector(`input[name="items[${index}][scheme]"]`);
                                const schemeValue = schemeInput?.value || "1+0";

                                const cells = {
                                    pricePerPc: row.querySelector('[data-field="price_per_pc"]'),
                                    qtyPerPc: row.querySelector('[data-field="qty_per_pc"]'),
                                    qtyPerCs: row.querySelector('[data-field="qty_per_cs"]'),
                                    freebiesPerCs: row.querySelector('[data-field="freebies_per_cs"]'),
                                    totalQty: row.querySelector('[data-field="total_qty"]'),
                                    price: row.querySelector('[data-field="price"]'),
                                    amount: row.querySelector('[data-field="amount"]'),
                                    discount: row.querySelector('[data-field="discount"]'),
                                };

                                const inputs = {
                                    pricePerPc: row.querySelector(`input[name="items[${index}][price_per_pc]"]`),
                                    qtyPerPc: row.querySelector(`input[name="items[${index}][qty_per_pc]"]`),
                                    qtyPerCs: row.querySelector(`input[name="items[${index}][qty_per_cs]"]`),
                                    freebiesPerCs: row.querySelector(`input[name="items[${index}][freebies_per_cs]"]`),
                                    totalQty: row.querySelector(`input[name="items[${index}][total_qty]"]`),
                                    price: row.querySelector(`input[name="items[${index}][price]"]`),
                                    amount: row.querySelector(`input[name="items[${index}][amount]"]`),
                                    discount: row.querySelector(`input[name="items[${index}][discount]"]`),
                                };

                                const values = this.extractValues(cells);
                                const calculations = this.performCalculations(values, schemeValue);

                                // Update MAIN row display with total qty = qty_per_cs (exclude freebies here)
                                // calculations.totalQty = values.qtyPerCs;

                                this.updateRowDisplay(cells, inputs, calculations);


                                pendingFreebieQty = calculations.freebies;
                            } else if (itemType === "FREEBIE") {
                                // For FREEBIE rows: Do NOT modify anything, keep as original
                                // So skip updating any fields, including freebiesPerCs and totalQty
                                pendingFreebieQty = 0; // clear pending freebies to avoid incorrect assign
                            }
                        }
                    }


                    calculateRowTotals(row) {
                        if (!row) return;
                        const index = $(row).data("index");
                        if (index === undefined) return;

                        const itemTypeInput = row.querySelector(`input[name="items[${index}][item_type]"]`);
                        const itemType = itemTypeInput?.value || "MAIN";

                        // Common cells & inputs
                        const cells = {
                            pricePerPc: row.querySelector('[data-field="price_per_pc"]'),
                            qtyPerPc: row.querySelector('[data-field="qty_per_pc"]'),
                            qtyPerCs: row.querySelector('[data-field="qty_per_cs"]'),
                            freebiesPerCs: row.querySelector('[data-field="freebies_per_cs"]'),
                            totalQty: row.querySelector('[data-field="total_qty"]'),
                            price: row.querySelector('[data-field="price"]'),
                            amount: row.querySelector('[data-field="amount"]'),
                            discount: row.querySelector('[data-field="discount"]'),
                        };

                        const inputs = {
                            pricePerPc: row.querySelector(`input[name="items[${index}][price_per_pc]"]`),
                            qtyPerPc: row.querySelector(`input[name="items[${index}][qty_per_pc]"]`),
                            qtyPerCs: row.querySelector(`input[name="items[${index}][qty_per_cs]"]`),
                            freebiesPerCs: row.querySelector(`input[name="items[${index}][freebies_per_cs]"]`),
                            totalQty: row.querySelector(`input[name="items[${index}][total_qty]"]`),
                            price: row.querySelector(`input[name="items[${index}][price]"]`),
                            amount: row.querySelector(`input[name="items[${index}][amount]"]`),
                            discount: row.querySelector(`input[name="items[${index}][discount]"]`),
                        };

                        const values = this.extractValues(cells);

                        if (itemType === "FREEBIE") {
                            // Force qty_per_cs = freebies_per_cs for calculation
                            values.qtyPerCs = values.freebiesPerCs;
                            values.freebiesPerCs = 0; // avoid double counting
                        }

                        const calculations = this.performCalculations(values);

                        this.updateRowDisplay(cells, inputs, calculations);
                    }


                    extractValues(cells) {
                        return {
                            pricePerPc: this.parseNumeric(cells.pricePerPc?.textContent),
                            qtyPerPc: this.parseInteger(cells.qtyPerPc?.textContent),
                            qtyPerCs: this.parseInteger(cells.qtyPerCs?.textContent),
                            freebiesPerCs: this.parseInteger(cells.freebiesPerCs?.textContent),
                            discount: cells.discount?.textContent.trim() || ""
                        };
                    }

                    parseNumeric(text) {
                        if (!text) return 0;
                        const cleaned = text.replace(/[₱,\s-]/g, "");
                        const num = parseFloat(cleaned);
                        return isNaN(num) ? 0 : num;
                    }

                    parseInteger(text) {
                        if (!text || text === "-" || text.toUpperCase() === "N/A") return 0;
                        const cleaned = text.replace(/[,\s-]/g, "");
                        const num = parseInt(cleaned);
                        return isNaN(num) ? 0 : num;
                    }


                    performCalculations(values) {
                        const calculations = {
                            price: 0,
                            totalQty: 0,
                            amount: 0,
                            freebies: 0
                        };

                        // --- 1. Base price per case ---
                        const basePrice = values.pricePerPc * values.qtyPerPc;

                        // --- 2. Discount parsing ---
                        let discountValue = 0;
                        if (values.discount) {
                            if (values.discount.toString().endsWith("%")) {
                                const percent = parseFloat(values.discount);
                                if (!isNaN(percent)) {
                                    discountValue = (percent / 100) * basePrice;
                                }
                            } else {
                                discountValue = parseFloat(values.discount) || 0;
                            }
                        }

                        // Final discounted price per case
                        calculations.price = basePrice - discountValue;

                        // --- 3. total_qty = qty_per_cs + freebies_per_cs ---
                        calculations.totalQty = 0;
                        if (!isNaN(values.qtyPerCs)) calculations.totalQty += values.qtyPerCs;
                        if (!isNaN(values.freebiesPerCs)) calculations.totalQty += values.freebiesPerCs;

                        // --- 4. amount = discounted price * total_qty ---
                        calculations.amount = calculations.price * calculations.totalQty;

                        // freebies tracking (optional)
                        calculations.freebies = values.freebiesPerCs;

                        return calculations;
                    }








                    updateRowDisplay(cells, inputs, calc) {
                        const safeFixed = (num) => (isNaN(num) ? "0.00" : num.toFixed(2));

                        if (cells.totalQty) cells.totalQty.textContent = calc.totalQty;
                        if (cells.price) cells.price.textContent = safeFixed(calc.price);
                        if (cells.amount) cells.amount.textContent = safeFixed(calc.amount);

                        if (cells.freebiesPerCs) {
                            const currentText = cells.freebiesPerCs.textContent.trim();
                            // Only update if freebies > 0
                            if (calc.freebies > 0) {
                                cells.freebiesPerCs.textContent = calc.freebies;
                                if (inputs.freebiesPerCs) inputs.freebiesPerCs.value = calc.freebies;
                            } else {
                                // Leave as is if blank or '-'
                            }
                        }

                        if (inputs.totalQty) inputs.totalQty.value = calc.totalQty;
                        if (inputs.price) inputs.price.value = safeFixed(calc.price);
                        if (inputs.amount) inputs.amount.value = safeFixed(calc.amount);
                        if (inputs.discount) inputs.discount.value = cells.discount?.textContent.trim() || "";
                    }

                    updateFreebieRow(row, freebieQty) {
                        const index = $(row).data("index");
                        if (index === undefined) return;

                        const pricePerPcCell = row.querySelector('[data-field="price_per_pc"]');
                        const pricePerPcInput = row.querySelector(`input[name="items[${index}][price_per_pc]"]`);
                        const freebiesPerCsCell = row.querySelector('[data-field="freebies_per_cs"]');
                        const freebiesPerCsInput = row.querySelector(`input[name="items[${index}][freebies_per_cs]"]`);
                        const totalQtyCell = row.querySelector('[data-field="total_qty"]');
                        const totalQtyInput = row.querySelector(`input[name="items[${index}][total_qty]"]`);

                        let unitPrice = 0;
                        if (pricePerPcCell) unitPrice = this.parseNumeric(pricePerPcCell.textContent);
                        else if (pricePerPcInput) unitPrice = parseFloat(pricePerPcInput.value) || 0;

                        // Update freebies qty
                        if (freebieQty > 0) {
                            if (freebiesPerCsCell) freebiesPerCsCell.textContent = freebieQty;
                            if (freebiesPerCsInput) freebiesPerCsInput.value = freebieQty;
                            if (totalQtyCell) totalQtyCell.textContent = freebieQty;
                            if (totalQtyInput) totalQtyInput.value = freebieQty;
                        }

                        // --- Calculate amount for freebies ---
                        const freebieAmount = unitPrice * freebieQty;

                        const amountCell = row.querySelector('[data-field="amount"]');
                        const amountInput = row.querySelector(`input[name="items[${index}][amount]"]`);
                        if (amountCell) amountCell.textContent = freebieAmount.toFixed(2);
                        if (amountInput) amountInput.value = freebieAmount.toFixed(2);

                        const priceCell = row.querySelector('[data-field="price"]');
                        const priceInput = row.querySelector(`input[name="items[${index}][price]"]`);
                        if (priceCell) priceCell.textContent = unitPrice.toFixed(2);
                        if (priceInput) priceInput.value = unitPrice.toFixed(2);
                    }




                    handleFreebieItem(row, index) {
                        const freebiesPerCsCell = row.querySelector('[data-field="freebies_per_cs"]');
                        const freebieQty = this.parseInteger(freebiesPerCsCell?.textContent);

                        const pricePerPcCell = row.querySelector('[data-field="price_per_pc"]');
                        const unitPrice = this.parseNumeric(pricePerPcCell?.textContent);

                        const freebieAmount = unitPrice * freebieQty;

                        const amountCell = row.querySelector('[data-field="amount"]');
                        const amountInput = row.querySelector(`input[name="items[${index}][amount]"]`);
                        if (amountCell) amountCell.textContent = freebieAmount.toFixed(2);
                        if (amountInput) amountInput.value = freebieAmount.toFixed(2);
                    }



                    getMainAmountTotal() {
                        let total = 0;
                        $("tbody tr[data-index]").each((_, row) => {
                            const index = $(row).data("index");
                            if (index === undefined) return;

                            const itemType = row.querySelector(`input[name="items[${index}][item_type]"]`)?.value || "MAIN";

                            if (itemType === "MAIN" || itemType === "DISCOUNT") {
                                const amountInput = row.querySelector(`input[name="items[${index}][amount]"]`);
                                if (amountInput) total += parseFloat(amountInput.value) || 0;
                            }
                        });
                        return total;
                    }
                    getDiscountTotal() {
                        let totalSavings = 0;

                        $("tbody tr[data-index]").each((_, row) => {
                            const index = $(row).data("index");
                            if (index === undefined) return;

                            const itemType = row.querySelector(`input[name="items[${index}][item_type]"]`)?.value || "MAIN";
                            if (itemType !== "DISCOUNT") return;

                            const price = parseFloat(row.querySelector(`input[name="items[${index}][price]"]`)?.value) || 0;
                            const totalQty = parseFloat(row.querySelector(`input[name="items[${index}][total_qty]"]`)?.value) || 0;
                            const discount = (row.querySelector(`input[name="items[${index}][discount]"]`)?.value || "").trim();

                            const originalPrice = price;
                            let discountedPrice = originalPrice;

                            // Apply discount
                            if (discount.endsWith("%")) {
                                const percent = parseFloat(discount.replace("%", "")) || 0;
                                discountedPrice = originalPrice * (1 - percent / 100);
                            } else if (discount !== "") {
                                const flat = parseFloat(discount) || 0;
                                discountedPrice = originalPrice - flat;
                            }

                            // Savings = (original - discounted) × total qty
                            const savings = (originalPrice - discountedPrice) * totalQty;
                            totalSavings += savings;
                        });

                        return totalSavings;
                    }



                    getFreebieAmountTotal() {
                        let total = 0;
                        $("tbody tr[data-index]").each((_, row) => {
                            const index = $(row).data("index");
                            if (index === undefined) return;

                            const itemType = row.querySelector(`input[name="items[${index}][item_type]"]`)?.value || "MAIN";

                            if (itemType === "FREEBIE") {
                                const amountInput = row.querySelector(`input[name="items[${index}][amount]"]`);
                                if (amountInput) total += parseFloat(amountInput.value) || 0;
                            }
                        });
                        return total;
                    }
                }




                // Initialize calculation system
                const orderCalculator = new OrderCalculationSystem();

                // Track all form elements for changes
                const trackableElements = $(
                    'input[type="text"], input[type="date"], select:not(#orderAction), td[contenteditable="true"]'
                );

                function initializeOriginalValues() {
                    // For regular form inputs
                    trackableElements.filter('input, select').each(function() {
                        const $element = $(this);
                        const originalValue = $element.val() || '';
                        $element.data('original', originalValue);
                        console.log('Input original:', $element.attr('name'), originalValue); // Debug log
                    });

                    // For contenteditable elements
                    trackableElements.filter('[contenteditable]').each(function() {
                        const $element = $(this);
                        const originalValue = $element.text().trim();
                        $element.data('original-value', originalValue);
                        console.log('Contenteditable original:', $element.data('field'), originalValue); // Debug log
                    });
                }

                function updateSubmitButtonState() {
                    if (hasChanges && changesCount > 0) {
                        submitButton.prop('disabled', false);
                        submitButton.removeClass('bg-blue-600 hover:bg-blue-700')
                            .addClass('bg-green-600 hover:bg-green-700');
                        submitButtonText.text(`Update`);
                        changesCounter.removeClass('hidden');
                        changesCountElement.text(changesCount);
                    } else {
                        submitButton.prop('disabled', true);
                        submitButton.removeClass('bg-green-600 hover:bg-green-700')
                            .addClass('bg-blue-600 hover:bg-blue-700');
                        submitButtonText.text('Update');
                        changesCounter.addClass('hidden');
                    }
                }

                function checkElementChange(element) {
                    const $element = $(element);
                    let currentValue, originalValue;

                    if ($element.is('[contenteditable]')) {
                        currentValue = $element.text().trim();
                        originalValue = $element.data('original-value');

                        // Handle undefined original value
                        if (originalValue === undefined) {
                            console.warn('Missing original value for contenteditable:', $element.data('field'));
                            originalValue = currentValue; // Set it to current to avoid false positives
                            $element.data('original-value', originalValue);
                        }
                    } else {
                        currentValue = $element.val() || '';
                        originalValue = $element.data('original');

                        // Handle undefined original value
                        if (originalValue === undefined) {
                            console.warn('Missing original value for input:', $element.attr('name'));
                            originalValue = currentValue; // Set it to current to avoid false positives
                            $element.data('original', originalValue);
                        }
                    }

                    // FIXED: Normalize values for comparison (handle empty, null, undefined)
                    const normalizeValue = (val) => {
                        if (val === null || val === undefined) return '';
                        if (typeof val === 'string') return val.trim();
                        return String(val).trim();
                    };

                    const normalizedCurrent = normalizeValue(currentValue);
                    const normalizedOriginal = normalizeValue(originalValue);

                    const hasChanged = normalizedCurrent !== normalizedOriginal;

                    // Debug logging
                    console.log('Checking change:', {
                        element: $element.attr('name') || $element.data('field'),
                        current: normalizedCurrent,
                        original: normalizedOriginal,
                        hasChanged: hasChanged,
                        wasMarkedChanged: $element.data('is-changed')
                    });

                    if (hasChanged) {
                        if (!$element.data('is-changed')) {
                            $element.data('is-changed', true);
                            if ($element.is('[contenteditable]')) {
                                $element.addClass('bg-yellow-100 border border-yellow-300 rounded');
                            } else {
                                if ($element.is('select')) {
                                    const td = $element.closest('td');
                                    td.addClass('bg-yellow-100 border border-yellow-300 rounded');
                                } else {
                                    $element.removeClass('bg-transparent').addClass('bg-yellow-100 rounded');
                                }
                            }
                            changesCount++;
                        }
                    } else {
                        if ($element.data('is-changed')) {
                            $element.data('is-changed', false);
                            if ($element.is('[contenteditable]')) {
                                $element.removeClass('bg-yellow-100 border border-yellow-300 rounded');
                            } else {
                                if ($element.is('select')) {
                                    const td = $element.closest('td');
                                    td.removeClass('bg-yellow-100 border border-yellow-300 rounded');
                                } else {
                                    $element.removeClass('bg-yellow-100 rounded').addClass('bg-transparent');
                                }
                            }
                            changesCount = Math.max(0, changesCount - 1); // Prevent negative count
                        }
                    }

                    hasChanges = changesCount > 0;
                    updateSubmitButtonState();
                }

                function initializeChangeDetection() {
                    // First, initialize all original values
                    initializeOriginalValues();

                    // Wait a moment for DOM to settle, then check for any existing changes
                    setTimeout(() => {
                        // Reset all change indicators first
                        trackableElements.each(function() {
                            $(this).data('is-changed', false);
                            $(this).removeClass('bg-yellow-100 border border-yellow-300 rounded');
                        });

                        changesCount = 0;
                        hasChanges = false;

                        // Now check each element - this should not trigger highlights if nothing changed
                        // Only uncomment the line below if you want to check for pre-existing changes
                        trackableElements.each(function() {
                            checkElementChange(this);
                        });

                        updateSubmitButtonState();
                        console.log('Change detection initialized');
                    }, 100);
                }


                function updateOrderTotal() {
                    const mainTotal = Number(orderCalculator.getMainAmountTotal() || 0);
                    const freebieTotal = Number(orderCalculator.getFreebieAmountTotal() || 0);
                    const discountTotal = Number(orderCalculator.getDiscountTotal() || 0);
                    const grandTotal = mainTotal + freebieTotal + discountTotal;

                    $("#totalAmount").text(grandTotal.toLocaleString("en-US", {
                        minimumFractionDigits: 2
                    }));
                    $("#mainTotal").text(mainTotal.toLocaleString("en-US", {
                        minimumFractionDigits: 2
                    }));

                    if (freebieTotal !== 0) {
                        $("#freebieRow").removeClass("hidden");
                        $("#freebieTotal").text(freebieTotal.toLocaleString("en-US", {
                            minimumFractionDigits: 2
                        }));
                    } else {
                        $("#freebieRow").addClass("hidden");
                    }

                    if (discountTotal !== 0) {
                        $("#discountRow").removeClass("hidden");
                        $("#discountTotal").text(discountTotal.toLocaleString("en-US", {
                            minimumFractionDigits: 2
                        }));
                    } else {
                        $("#discountRow").addClass("hidden");
                    }
                }




                // Initialize original values for contenteditable elements
                $('td[contenteditable="true"]').each(function() {
                    $(this).data('original-value', $(this).text().trim());
                });

                // Listen for changes on regular form inputs
                trackableElements.filter('input, select').on('change input keyup', function() {
                    checkElementChange(this);
                });

                // Listen for changes on contenteditable elements
                trackableElements.filter('[contenteditable]').on('input blur keyup', function() {
                    checkElementChange(this);

                    const $this = $(this);
                    const row = $this.closest('tr');

                    // Update hidden input
                    const index = row.data('index');
                    const field = $this.data('field');
                    if (index !== undefined && field) {
                        const hiddenInput = $(`input[name="items[${index}][${field}]"]`);
                        if (hiddenInput.length) {
                            let value = $this.text().trim();
                            if (value === '-') value = '0';
                            hiddenInput.val(value);
                        }
                    }

                    // 🔥 Re-run row + total calculations
                    orderCalculator.calculateRowTotals(row[0]);
                    updateOrderTotal();
                });


                // Handle product search functionality (from your existing code)
                let debounceTimeout;

                $(document).on('keyup focus', '[contenteditable-search="true"]', function() {
                    const inputCell = $(this);
                    clearTimeout(inputCell.data('debounceTimeout'));

                    const query = inputCell.text().trim().toLowerCase();
                    const resultList = inputCell.children('.search-results').first();

                    if (query.length >= 2) {
                        const timer = setTimeout(() => {
                            inputCell.addClass('animate-pulse');
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
                                    query
                                },
                                success: function(data) {
                                    inputCell.removeClass('animate-pulse');
                                    resultList.empty();

                                    if (!data.length) {
                                        resultList.append('<li class="px-6 py-4 text-gray-500 text-center">No products found</li>');
                                        return;
                                    }

                                    data.forEach(product => {
                                        resultList.append(`
                                <li class="product-item px-4 py-2 hover:bg-gray-100 cursor-pointer transition-all"
                                    data-sku="${product.sku}"
                                    data-description="${product.description}">
                                    <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded mr-2">${product.sku}</span>
                                    ${product.description}
                                </li>
                            `);
                                    });
                                },
                                error: function() {
                                    inputCell.removeClass('animate-pulse');
                                    resultList.html('<li class="px-6 py-4 text-red-600 text-center">Search failed</li>');
                                }
                            });
                        }, 300);

                        inputCell.data('debounceTimeout', timer);
                    } else {
                        resultList.empty().addClass('hidden');
                    }
                });

                $(document).on('click', '.product-item', function() {
                    const selected = $(this);
                    const sku = selected.data('sku');
                    const description = selected.data('description');

                    const resultList = selected.closest('.search-results');
                    const inputCell = resultList.parent();
                    const currentRow = inputCell.closest('tr');

                    // Update SKU
                    const skuCell = currentRow.find('[data-field="sku"]');
                    skuCell.text(sku);
                    currentRow.find('.sku-hidden').val(sku);

                    // Update Description
                    const descCell = currentRow.find('[data-field="item_description"]');
                    descCell.text(description);
                    currentRow.find('.desc-hidden').val(description);

                    resultList.empty().addClass('hidden');

                    // Trigger change detection for updated fields
                    checkElementChange(skuCell[0]);
                    checkElementChange(descCell[0]);

                    inputCell.focus();
                });

                // Hide dropdown when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('[contenteditable-search="true"], .search-results').length) {
                        $('.search-results').empty().addClass('hidden');
                    }
                });


                // Confirm navigation away with unsaved changes
                window.addEventListener('beforeunload', function(e) {
                    if (hasChanges) {
                        e.preventDefault();
                        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                        return e.returnValue;
                    }
                });

                // Handle form submission
                // Simple fix - just add this to your existing form submit handler

                $('form').on('submit', function(e) {
                    if (!hasChanges) {
                        e.preventDefault();
                        alert('No changes detected to save.');
                        return false;
                    }

                    // SIMPLE FIX: Remove the beforeunload event listener during submission
                    window.onbeforeunload = null;

                    // Also clear hasChanges to be extra safe
                    hasChanges = false;

                    // Show loading state
                    submitButton.prop('disabled', true);
                    submitButtonText.text('Saving...');
                    submitButton.prepend(
                        '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>'
                    );

                    return true;
                });

                // Reset changes tracking after successful submission
                @if (session('success'))
                    hasChanges = false;
                    changesCount = 0;
                    updateSubmitButtonState();

                    trackableElements.each(function() {
                        const $element = $(this);
                        $element.data('is-changed', false);
                        if ($element.is('[contenteditable]')) {
                            $element.removeClass('bg-yellow-100 border border-yellow-300 rounded');
                        } else {
                            $element.removeClass('bg-yellow-100 rounded').addClass('bg-transparent');
                        }
                    });
                @endif

                // Initial calculations and change detection
                setTimeout(() => {
                    orderCalculator.calculateAllRows();
                    updateOrderTotal();

                    trackableElements.each(function() {
                        checkElementChange(this);
                    });
                }, 100);
            });

            document.addEventListener('DOMContentLoaded', function() {
                const cancelBtn = document.getElementById('cancelButton');
                if (cancelBtn && !cancelBtn.dataset.swalBound) {
                    cancelBtn.dataset.swalBound = true; // prevent double binding
                    cancelBtn.addEventListener('click', function() {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "This order will be Cancelled!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#B91C1C',
                            cancelButtonColor: '#aaa',
                            cancelButtonText: 'No, keep it',
                            confirmButtonText: 'Yes, cancel it!',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = '{{ route('orders.cancel') }}';
                                form.style.display = 'none';

                                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                const inputCsrf = document.createElement('input');
                                inputCsrf.type = 'hidden';
                                inputCsrf.name = '_token';
                                inputCsrf.value = csrfToken;
                                form.appendChild(inputCsrf);

                                const existingIdInput = document.querySelector('input[name="id"]');
                                const orderId = existingIdInput ? existingIdInput.value : '';

                                const orderIdInput = document.createElement('input');
                                orderIdInput.type = 'hidden';
                                orderIdInput.name = 'id';
                                orderIdInput.value = orderId;
                                form.appendChild(orderIdInput);

                                document.body.appendChild(form);
                                form.submit();
                            }
                        });
                    });
                }
            });


            if (restoreBtn) {
                restoreBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This order will be restored!",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#aaa',
                        confirmButtonText: 'Yes, restore it!',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ route('orders.restore') }}';
                            form.style.display = 'none';

                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            const inputCsrf = document.createElement('input');
                            inputCsrf.type = 'hidden';
                            inputCsrf.name = '_token';
                            inputCsrf.value = csrfToken;
                            form.appendChild(inputCsrf);

                            const existingIdInput = document.querySelector('input[name="id"]');
                            const orderId = existingIdInput ? existingIdInput.value : '';

                            const orderIdInput = document.createElement('input');
                            orderIdInput.type = 'hidden';
                            orderIdInput.name = 'id';
                            orderIdInput.value = orderId;
                            form.appendChild(orderIdInput);

                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }

            if (completeBtn) {
                completeBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This order will be marked as Complete!",
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#10B981',
                        cancelButtonColor: '#aaa',
                        cancelButtonText: 'No, go back',
                        confirmButtonText: 'Yes, complete it!',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '{{ route('orders.complete') }}';
                            form.style.display = 'none';

                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            const inputCsrf = document.createElement('input');
                            inputCsrf.type = 'hidden';
                            inputCsrf.name = '_token';
                            inputCsrf.value = csrfToken;
                            form.appendChild(inputCsrf);

                            const existingIdInput = document.querySelector('input[name="id"]');
                            const orderId = existingIdInput ? existingIdInput.value : '';

                            const orderIdInput = document.createElement('input');
                            orderIdInput.type = 'hidden';
                            orderIdInput.name = 'id';
                            orderIdInput.value = orderId;
                            form.appendChild(orderIdInput);

                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }


            function lockFieldsByStatus(orderStatus) {
                const lockStatuses = ["approved", "completed", "for approval", "cancelled"];

                if (lockStatuses.includes(orderStatus)) {
                    // Lock all inputs
                    $('input').each(function() {
                        $(this).attr('readonly', true)
                            .addClass('pointer-events-none opacity-100');
                    });

                    // Lock all selects EXCEPT #orderAction
                    $('select').not('#orderAction').each(function() {
                        $(this).addClass('pointer-events-none opacity-100');
                    });

                    // Lock contenteditable fields
                    $('[contenteditable="true"]').each(function() {
                        $(this).attr('contenteditable', 'false')
                            .addClass('pointer-events-none opacity-100');
                    });
                }
            }

            $(document).ready(function() {
                lockFieldsByStatus("{{ $order->order_status }}");
            });




            document.getElementById('generateSOButton').addEventListener('click', async () => {
                const sofId = "{{ $order->sof_id }}";
                const url = "{{ route('oracle.transfer') }}";
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const executeTransfer = async () => {
                    Swal.fire({
                        title: 'Processing Transfer...',
                        html: `
                <div style="text-align: left;">
                    <p>📤 Sending order to Oracle RIB</p>
                    <p>🔄 Processing departments...</p>
                    <p>⏳ Please wait, this may take a few moments</p>
                </div>
                `,
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                sof_id: sofId
                            })
                        });

                        const text = await response.text();
                        console.log('Raw response:', text);

                        let data = {};
                        try {
                            data = JSON.parse(text);
                        } catch (parseError) {
                            console.error('JSON parse error:', parseError);
                            throw new Error('Invalid response from server');
                        }

                        Swal.close();

                        // Handle success with detailed summary
                        if (response.ok && data.responses) {
                            const summary = data.summary || {};
                            const responses = data.responses || {};
                            const deptKeys = Object.keys(responses);

                            // Build detailed HTML summary
                            let htmlContent = `
                    <div style="text-align: left; max-height: 500px; overflow-y: auto;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                            <h4 style="margin: 0 0 10px 0;">📊 Transfer Summary</h4>
                            <p style="margin: 5px 0;"><strong>Total Departments:</strong> ${summary.total_departments || deptKeys.length}</p>
                            <p style="margin: 5px 0; color: #28a745;"><strong>✅ Successful:</strong> ${summary.successful || 0}</p>
                            <p style="margin: 5px 0; color: #dc3545;"><strong>⚠️ Failed:</strong> ${summary.failed || 0}</p>
                        </div>
                `;

                            deptKeys.forEach(dept => {
                                const resp = responses[dept];
                                const status = resp.status || 'unknown';
                                const tsfNo = resp.tsf_no || 'N/A';
                                const itemCount = resp.item_count || 0;

                                // Status styling
                                let statusIcon = '❓';
                                let statusColor = '#6c757d';
                                let statusText = 'Unknown';

                                switch (status) {
                                    case 'success':
                                        statusIcon = '✅';
                                        statusColor = '#28a745';
                                        statusText = 'Success';
                                        break;
                                    case 'rib_errors':
                                        statusIcon = '⚠️';
                                        statusColor = '#ffc107';
                                        statusText = 'RIB Errors';
                                        break;
                                    case 'verification_failed':
                                        statusIcon = '❌';
                                        statusColor = '#dc3545';
                                        statusText = 'Verification Failed';
                                        break;
                                    case 'processing_failed':
                                        statusIcon = '❌';
                                        statusColor = '#dc3545';
                                        statusText = 'Processing Failed';
                                        break;
                                }

                                htmlContent += `
                        <div style="border: 2px solid ${statusColor}; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: white;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: ${statusColor};">
                                    ${statusIcon} Department: ${dept}
                                </h5>
                                <span style="background: ${statusColor}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                                    ${statusText}
                                </span>
                            </div>
                            
                            <div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                ${status === 'success' ? `<p style="margin: 3px 0;"><strong>TSF Number:</strong> <code>${tsfNo}</code></p>` : ''}
                                <p style="margin: 3px 0;"><strong>Items:</strong> ${itemCount}</p>
                            </div>
                        `;

                                // Success details
                                if (status === 'success' && resp.details && resp.details.length > 0) {
                                    htmlContent += `
                            <div style="margin-top: 10px; padding: 10px; background: #d4edda; border-radius: 5px; border-left: 4px solid #28a745;">
                                <strong>✓ Verification Details:</strong>
                                <ul style="margin: 5px 0; padding-left: 20px;">
                        `;
                                    resp.details.forEach(detail => {
                                        htmlContent += `<li style="font-size: 13px;">${detail}</li>`;
                                    });
                                    htmlContent += `</ul></div>`;
                                }

                                // Error details
                                if (resp.errors && resp.errors.length > 0) {
                                    htmlContent += `
                            <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border-radius: 5px; border-left: 4px solid #dc3545;">
                                <strong>⚠️ Issue Details:</strong>
                                <ul style="margin: 5px 0; padding-left: 20px;">
                        `;
                                    resp.errors.forEach(err => {
                                        const errorMsg = err.message || 'Unknown error';
                                        const attempts = err.attempts ? ` (${err.attempts} verification attempts)` : '';

                                        // Simplify error type labels for users
                                        let typeLabel = '';
                                        switch (err.type) {
                                            case 'rib_failure':
                                                typeLabel = 'Oracle Processing Error';
                                                break;
                                            case 'verification_failure':
                                                typeLabel = 'Verification Issue';
                                                break;
                                            case 'processing_failure':
                                                typeLabel = 'Processing Error';
                                                break;
                                            default:
                                                typeLabel = 'Error';
                                        }

                                        htmlContent += `
                                <li style="font-size: 13px; margin: 5px 0;">
                                    <strong>${typeLabel}:</strong> ${errorMsg}${attempts}
                                </li>
                            `;
                                    });
                                    htmlContent += `</ul></div>`;
                                }

                                // Verification info (only show meaningful info)
                                if (resp.verification && !resp.verification.exists && status === 'verification_failed') {
                                    const verifyAttempts = resp.verification.attempt || 0;

                                    htmlContent += `
                            <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                                <strong>🔍 Database Check:</strong>
                                <p style="margin: 5px 0; font-size: 13px;">
                                    The TSF could not be confirmed in the Oracle database after ${verifyAttempts} attempt(s).
                                </p>
                                <p style="margin: 5px 0; font-size: 13px; color: #856404;">
                                    <em>Note: The transfer may still be processing. Please check Oracle RMS directly or contact IT support.</em>
                                </p>
                            </div>
                        `;
                                }

                                htmlContent += `</div>`;
                            });

                            htmlContent += `</div>`;

                            // Show appropriate dialog based on overall success
                            if (data.success) {
                                await Swal.fire({
                                    icon: 'success',
                                    title: '✅ Transfer Complete',
                                    html: htmlContent,
                                    confirmButtonText: 'OK',
                                    width: '800px',
                                    customClass: {
                                        popup: 'swal-wide'
                                    }
                                });
                                location.reload();
                            } else {
                                const result = await Swal.fire({
                                    icon: 'warning',
                                    title: '⚠️ Transfer Completed with Issues',
                                    html: htmlContent,
                                    showCancelButton: true,
                                    confirmButtonText: 'Retry Failed Items',
                                    cancelButtonText: 'Close',
                                    reverseButtons: true,
                                    width: '800px',
                                    customClass: {
                                        popup: 'swal-wide'
                                    }
                                });

                                if (result.isConfirmed) {
                                    await executeTransfer();
                                }
                            }

                        } else if (data.error_type === 'no_items') {
                            // No items to process
                            await Swal.fire({
                                icon: 'info',
                                title: 'No Items to Process',
                                text: data.message || 'All items have already been processed.',
                                confirmButtonText: 'OK'
                            });

                        } else {
                            // General error
                            const errorMsg = data.message || 'An unexpected error occurred.';
                            const result = await Swal.fire({
                                icon: 'error',
                                title: 'Transfer Failed',
                                html: `
                        <div style="text-align: left;">
                            <p><strong>Error:</strong></p>
                            <pre style="background: #f8d7da; padding: 10px; border-radius: 5px; text-align: left; white-space: pre-wrap; word-wrap: break-word;">${errorMsg}</pre>
                        </div>
                    `,
                                showCancelButton: true,
                                confirmButtonText: 'Retry',
                                cancelButtonText: 'Cancel',
                                reverseButtons: true
                            });

                            if (result.isConfirmed) {
                                await executeTransfer();
                            }
                        }

                    } catch (err) {
                        Swal.close();
                        console.error('Transfer failed:', err);

                        const result = await Swal.fire({
                            icon: 'error',
                            title: '🔥 Connection Failed',
                            html: `
                    <div style="text-align: left;">
                        <p><strong>Error Type:</strong> Network/Connection Issue</p>
                        <p><strong>Message:</strong></p>
                        <pre style="background: #f8d7da; padding: 10px; border-radius: 5px; text-align: left; white-space: pre-wrap;">${err.message || 'Server unreachable or timeout'}</pre>
                        <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                            This could be due to:
                            <ul style="margin: 5px 0; padding-left: 20px;">
                                <li>Network timeout</li>
                                <li>Server overload</li>
                                <li>Connection interrupted</li>
                            </ul>
                        </p>
                    </div>
                `,
                            showCancelButton: true,
                            confirmButtonText: 'Retry',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            width: '600px'
                        });

                        if (result.isConfirmed) {
                            await executeTransfer();
                        }
                    }
                };

                await executeTransfer();
            });
        </script>

        <!-- Enhanced CSS for better visual feedback -->
        <style>
            /* Minimal custom scrollbar */
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background-color: rgba(100, 116, 139, 0.4);
                /* slate-500 w/ opacity */
                border-radius: 9999px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background-color: rgba(100, 116, 139, 0.6);
            }

            /* Firefox */
            .custom-scrollbar {
                scrollbar-width: thin;
                scrollbar-color: rgba(100, 116, 139, 0.4) transparent;
            }

            /* Smooth transitions for all form elements */
            input,
            select,
            td[contenteditable] {
                transition: all 0.2s ease;
            }

            /* Enhanced visual feedback for changed elements */
            .bg-yellow-100 {
                background-color: #fef3c7 !important;
                animation: highlight-pulse 2s ease-in-out;
            }

            @keyframes highlight-pulse {

                0%,
                100% {
                    background-color: #fef3c7;
                }

                50% {
                    background-color: #fde68a;
                }
            }

            /* Submit button enhanced states */
            #submitButton {
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            #submitButton:before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            #submitButton:not(:disabled):hover:before {
                left: 100%;
            }

            #submitButton:disabled {
                transform: none;
            }

            #submitButton:not(:disabled):hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }


            /* Search results enhanced styling */
            .search-results {
                backdrop-filter: blur(10px);
                background-color: rgba(255, 255, 255, 0.95);
                border: 1px solid rgba(209, 213, 219, 0.8);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }

            /* Contenteditable focus styling */
            td[contenteditable="true"]:focus {
                outline: 2px solid #3b82f6;
                outline-offset: -2px;
                background-color: #eff6ff;
            }

            /* Loading animation for submit button */
            .animate-spin {
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .flex.items-center.justify-between {
                    flex-direction: column;
                    gap: 1rem;
                }


                #submitButton {
                    order: 2;
                    width: 100%;
                }

                .inline-block {
                    order: 3;
                }
            }
        </style>
    @endif
@endsection
