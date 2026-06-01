@extends('layouts.app')

@section('content')
    @php
        $order->requesting_store = \App\Support\LocationConfig::storeName($order->requesting_store, $order->requesting_store);

        // --- LOCKING LOGIC ---
        $hasAnyTransferNo = $order->items->contains(function ($item) {
            return !empty($item->store_order_no) && $item->remarks !== 'Item Cancelled';
        });

        $partialUnlock =
            in_array(strtolower($order->order_status), ['approved', 'for approval']) &&
            !$hasAnyTransferNo &&
            !str_contains(strtolower(Auth::user()->role), 'warehouse') &&
            Auth::user()->role !== 'super admin';

        $isFullyLocked =
            (in_array(strtolower($order->order_status), ['approved', 'completed', 'for approval', 'cancelled']) && Auth::user()->role !== 'super admin') ||
            str_contains(strtolower(Auth::user()->role), 'warehouse') ||
            $hasAnyTransferNo;

        // For the items table – this will now work because $hasAnyTransferNo is defined
        $itemsLocked = $isFullyLocked;
        if (Auth::user()->role === 'super admin') {
            $itemsLocked = false;
        }
    @endphp
    <style nonce="{{ $cspNonce ?? '' }}">
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 9999;
            max-height: 15rem;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            min-width: 250px;
            max-width: 400px;
            white-space: nowrap;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table,
        tbody,
        tr,
        td {
            overflow: visible !important;
        }

        /* ── Approve-Order Modal (CSP-safe: no inline styles) ── */
        .swal-approve-wrap {
            text-align: center;
            font-size: 14px;
            color: #444;
        }

        .swal-upload-title {
            margin-bottom: 12px;
            font-weight: 500;
        }

        .swal-upload-hint {
            margin-bottom: 12px;
            font-size: 12px;
            color: #666;
        }

        .swal-upload-box {
            border: 2px dashed #2563EB;
            border-radius: 8px;
            padding: 20px;
            background: #f9fafb;
            cursor: pointer;
            transition: background 0.2s ease-in-out;
        }

        .swal-upload-box--hover {
            background: #eef2ff;
        }

        .swal-upload-file {
            display: none;
        }

        .swal-upload-label {
            cursor: pointer;
            display: block;
            color: #2563EB;
            font-weight: 500;
            font-size: 13px;
        }

        .swal-upload-filename {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
            font-style: italic;
        }

        .swal-upload-required {
            margin-top: 8px;
            font-size: 11px;
            color: #DC2626;
        }

        /* ── Approve modal: mode grid responsive ── */
        .sw-mode-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
        }

        @media (max-width: 400px) {
            .sw-mode-grid {
                grid-template-columns: 1fr;
            }
        }

        .order-items-table {
            min-width: 950px;
            width: 100%;
        }

        /* ── Scroll hint badge shown on small screens ── */
        .table-scroll-hint {
            display: none;
        }

        @media (max-width: 1024px) {
            .table-scroll-hint {
                display: flex;
            }
        }

        /* ── Action panel: auto height when stacked on mobile ── */
        .actions-panel {
            height: auto;
        }

        @media (min-width: 1024px) {
            .actions-panel {
                height: 100%;
            }
        }

        /* ── Customer/Payment columns: spacing when stacked ── */
        .info-col-left {
            padding-bottom: 1rem;
        }

        @media (min-width: 768px) {
            .info-col-left {
                padding-bottom: 0;
            }
        }

        /* ══════════════════════════════════════════════
                                                                                                                                                                                                                                                                                                                                   INFO SECTIONS — uniform mobile layout
                                                                                                                                                                                                                                                                                                                                   Below 768 px: sections stack cleanly; each
                                                                                                                                                                                                                                                                                                                                   field becomes a horizontal label → value row
                                                                                                                                                                                                                                                                                                                                   with a subtle underline separator.
                                                                                                                                                                                                                                                                                                                                   ══════════════════════════════════════════════ */
        @media (max-width: 767px) {

            /* Strip desktop right-padding & left-border from sections */
            .info-section {
                padding-right: 0 !important;
                padding-left: 0 !important;
                border-left: none !important;
                padding-bottom: 0.875rem;
                margin-bottom: 0.875rem;
                border-bottom: 1px solid #f3f4f6;
            }

            .info-section:last-child {
                border-bottom: none;
                padding-bottom: 0;
                margin-bottom: 0;
            }

            /* Section heading — indigo accent bar */
            .info-section>h3 {
                font-size: 0.62rem !important;
                font-weight: 700 !important;
                letter-spacing: 0.07em !important;
                text-transform: uppercase !important;
                color: #4f46e5 !important;
                padding-bottom: 0.3rem;
                margin-bottom: 0.5rem !important;
                border-bottom: 2px solid #e0e7ff;
            }

            /* Each field wrapper → horizontal flex row */
            .info-section>div {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                min-height: 1.875rem;
                padding: 0.25rem 0;
                border-bottom: 1px dashed #f3f4f6;
            }

            .info-section>div:last-child {
                border-bottom: none;
            }

            /* Label <p> — fixed-width, left-aligned, uppercase */
            .info-section>div>p:first-child {
                flex-shrink: 0;
                min-width: 6.75rem;
                max-width: 6.75rem;
                margin-bottom: 0 !important;
                font-size: 0.65rem !important;
                font-weight: 600;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: 0.045em;
                line-height: 1.3;
                text-align: left;
            }

            /* Value inputs */
            .info-section>div>input,
            .info-section>div>select {
                flex: 1;
                min-width: 0;
                font-size: 0.78rem !important;
                font-weight: 500;
                color: #111827;
                border: none !important;
                border-bottom: 1px solid #e5e7eb !important;
                border-radius: 0 !important;
                padding: 0.1rem 0.15rem !important;
                background: transparent !important;
                width: auto;
            }

            .info-section>div>input:focus,
            .info-section>div>select:focus {
                border-bottom-color: #6366f1 !important;
                outline: none !important;
                box-shadow: none !important;
                ring: none !important;
            }

            /* Read-only <p> values */
            .info-section>div>p.text-gray-900,
            .info-section>div>p.font-medium {
                flex: 1;
                min-width: 0;
                font-size: 0.78rem;
                font-weight: 500;
                color: #111827;
                margin-bottom: 0;
            }

            /* Badge/span values */
            .info-section>div>p>span,
            .info-section>div>span {
                font-size: 0.7rem;
            }

            /* Nested .relative wrapper (date pickers etc.) */
            .info-section>div>.relative {
                flex: 1;
                min-width: 0;
            }

            .info-section>div>.relative>input {
                padding-right: 0 !important;
                width: 100%;
            }

            /* Nested <p> label inside .relative (Payment Date) */
            .info-section>div>.relative>p:first-child {
                display: none;
                /* label already shown via the parent div > p */
            }

            /* Date inputs: remove the desktop padding-right hack */
            .info-section>div>input[type="date"],
            .info-section>div.relative>input[type="date"] {
                padding-right: 0 !important;
                -webkit-appearance: none;
                appearance: auto;
                width: 100%;
            }

            /* The wrapping .relative div used by Payment Date — flex it too */
            .info-section>div.relative {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                min-height: 1.875rem;
                padding: 0.25rem 0;
                border-bottom: 1px dashed #f3f4f6;
            }

            .info-section>div.relative>p:first-child {
                flex-shrink: 0;
                min-width: 6.75rem;
                max-width: 6.75rem;
                margin-bottom: 0 !important;
                font-size: 0.65rem !important;
                font-weight: 600;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: 0.045em;
                text-align: left;
            }

            .info-section>div.relative>input {
                flex: 1;
                min-width: 0;
                font-size: 0.78rem !important;
                font-weight: 500;
                color: #111827;
                border: none !important;
                border-bottom: 1px solid #e5e7eb !important;
                border-radius: 0 !important;
                padding: 0.1rem 0.15rem !important;
                background: transparent !important;
            }

            .info-section>div.relative>input:focus {
                border-bottom-color: #6366f1 !important;
                outline: none !important;
                box-shadow: none !important;
            }

            /* Selects with background-image inline style — override */
            .info-section>div>select[style] {
                background-image: none !important;
            }

            /* Order Info: hidden input for payment_center (read-only display) */
            .info-section>div>p.text-gray-900 {
                flex: 1;
                min-width: 0;
                font-size: 0.78rem;
                font-weight: 500;
                color: #111827;
                margin-bottom: 0;
            }

        }

        /* end info-section @media */

        /* ── Swal: clamp to viewport on small screens ── */
        @media (max-width: 640px) {
            .swal2-popup {
                width: 95vw !important;
                max-width: 95vw !important;
                padding: 1rem !important;
            }

            .swal2-title {
                font-size: 1.1rem !important;
            }

            .swal2-html-container {
                font-size: 0.85rem !important;
                max-height: 55vh;
                overflow-y: auto;
            }

            .swal2-actions {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }

        /* Underline effect when component is editable */
        .order-details-component.editable input:not([type="hidden"]),
        .order-details-component.editable select,
        .order-details-component.editable textarea,
        .order-details-component.editable td[contenteditable="true"] {
            border-bottom: 1px solid #dbe2eb !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            background: transparent !important;
            border-radius: 0 !important;
            padding: 0.25rem 0.125rem !important;
            transition: border-color 0.2s;
        }

        .order-details-component.editable input:focus,
        .order-details-component.editable select:focus,
        .order-details-component.editable textarea:focus,
        .order-details-component.editable td[contenteditable="true"]:focus {
            border-bottom-color: #3b82f6 !important;
            outline: none;
        }

        .order-details-component.editable td[contenteditable="true"] {
            min-width: 60px;
            cursor: text;
        }

        /* Style select dropdown arrow for editable mode */
        .order-details-component.editable select {
            background-color: transparent;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%236b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 0.25rem center;
            padding-right: 1.25rem;
        }
    </style>
    <form
        method="POST"
        action="{{ route('orders.update', $order->id) }}">
        @csrf
        @method('PUT')
        <div class="">
            <div class="order-details-component mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <!-- Header Section -->
                <!-- Header Section -->
                <div class="mb-4 flex flex-wrap items-center justify-between gap-y-3 sm:mb-8">
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
                            <p class="hidden text-sm text-gray-600 sm:block">Review detailed information about the selected order.</p>
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
                        <span class="hidden sm:inline">Back to Order List</span>
                    </a>
                </div>




                <input
                    type="hidden"
                    name="id"
                    value="{{ $order->id }}">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
                    <div class="col-span-1 grid gap-3 lg:col-span-5">
                        <!-- Customer and Payment Info -->
                        <div class="grid grid-cols-1 rounded rounded-xl border bg-white p-4 shadow-sm md:grid-cols-2">

                            <div class="grid grid-cols-1 border-b pb-4 md:grid-cols-2 md:border-b-0 md:pb-0">
                                <div class="info-section info-col-left space-y-2 pe-6">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Customer Info</h3>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">MBC Card No</p>
                                        <input
                                            type="text" {{ $isFullyLocked ? 'readonly' : '' }}
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
                                            type="text" {{ $isFullyLocked ? 'readonly' : '' }}
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
                                            type="text" {{ $isFullyLocked ? 'readonly' : '' }}
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
                                            type="email" {{ $isFullyLocked ? 'readonly' : '' }}
                                            name="email"
                                            value="{{ $order->email ?? '' }}"
                                            data-original="{{ $order->email ?? '' }}"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            autocomplete="off"
                                            placeholder="-"
                                            maxlength="100">
                                    </div>

                                </div>

                                <div class="info-section space-y-2 pe-6">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Payment Info</h3>

                                    <!-- Payment Center -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Payment Center</p>
                                        <select
                                            name="payment_center"
                                            {{ $isFullyLocked ? 'disabled' : '' }}
                                            class="w-full appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="background-image: none;">
                                            <option
                                                value=""
                                                disabled
                                                {{ $order->payment_center ? '' : 'selected' }}>
                                                Select Payment Center
                                            </option>

                                            @foreach (\App\Support\LocationConfig::stores() as $code => $label)
                                                <option
                                                    value="{{ $code }}"
                                                    {{ (string) $order->payment_center === (string) $code ? 'selected' : '' }}>
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
                                            {{ $isFullyLocked ? 'readonly' : '' }}
                                            name="payment_date"
                                            id="payment_date_inline"
                                            value="{{ $order->payment_date ? \Carbon\Carbon::parse($order->payment_date)->format('Y-m-d') : '' }}"
                                            class="payment-date relative w-full cursor-pointer appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="padding-right: 50%;">
                                    </div>

                                    <script nonce="{{ $cspNonce ?? '' }}">
                                        (function() {
                                            const paymentDateInput = document.getElementById('payment_date_inline');
                                            if (!paymentDateInput) return;

                                            // If the field is locked (readonly), do nothing – no validation, no Swal.
                                            if (paymentDateInput.hasAttribute('readonly') || paymentDateInput.disabled) {
                                                return;
                                            }

                                            // Get today's date in YYYY-MM-DD format
                                            const today = new Date();
                                            const year = today.getFullYear();
                                            const month = String(today.getMonth() + 1).padStart(2, '0');
                                            const day = String(today.getDate()).padStart(2, '0');
                                            const todayFormatted = `${year}-${month}-${day}`;

                                            paymentDateInput.setAttribute('min', todayFormatted);

                                            let lastValidValue = paymentDateInput.value;

                                            function isValidDate(dateValue) {
                                                return !dateValue || dateValue >= todayFormatted;
                                            }

                                            paymentDateInput.addEventListener('blur', function() {
                                                if (paymentDateInput.value && paymentDateInput.value < todayFormatted) {
                                                    if (typeof Swal !== 'undefined') {
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Invalid Date',
                                                            text: 'Please select today or a future date. Past dates are not allowed for payment date.',
                                                            confirmButtonColor: '#3085d6',
                                                            confirmButtonText: 'OK'
                                                        });
                                                    }
                                                    paymentDateInput.value = lastValidValue;
                                                } else {
                                                    lastValidValue = paymentDateInput.value;
                                                }
                                            });

                                            paymentDateInput.addEventListener('change', function() {
                                                if (paymentDateInput.value && paymentDateInput.value >= todayFormatted) {
                                                    lastValidValue = paymentDateInput.value;
                                                }
                                            });

                                            // Initial validation for pre-filled past date
                                            if (paymentDateInput.value && paymentDateInput.value < todayFormatted) {
                                                if (typeof Swal !== 'undefined') {
                                                    Swal.fire({
                                                        icon: 'warning',
                                                        title: 'Invalid Pre-filled Date',
                                                        text: 'The pre-filled payment date is in the past. Please select a valid date.',
                                                        confirmButtonColor: '#3085d6',
                                                        confirmButtonText: 'OK'
                                                    });
                                                }
                                                paymentDateInput.value = '';
                                                lastValidValue = '';
                                            }
                                        })();
                                    </script>

                                </div>

                            </div>

                            <div class="grid grid-cols-1 border-t pt-4 md:grid-cols-2 md:border-t-0 md:pt-0">
                                <div class="info-section info-col-left space-y-2 pe-6">
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

                                    <style nonce="{{ $cspNonce ?? '' }}">
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
                                            {{ $isFullyLocked ? 'readonly' : '' }}
                                            name="delivery_date"
                                            id="delivery_date_inline"
                                            value="{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '' }}"
                                            class="delivery-date w-full cursor-pointer appearance-none border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0"
                                            style="padding-right: 50%;">
                                    </div>

                                    <script nonce="{{ $cspNonce ?? '' }}">
                                        (function() {
                                            const deliveryDateInput = document.getElementById('delivery_date_inline');
                                            if (!deliveryDateInput) return;

                                            // If the field is locked (readonly or disabled), skip all validation
                                            if (deliveryDateInput.hasAttribute('readonly') || deliveryDateInput.disabled) {
                                                return;
                                            }

                                            // Get today's date in YYYY-MM-DD format
                                            const today = new Date();
                                            const year = today.getFullYear();
                                            const month = String(today.getMonth() + 1).padStart(2, '0');
                                            const day = String(today.getDate()).padStart(2, '0');
                                            const todayFormatted = `${year}-${month}-${day}`;

                                            // Set the min attribute to disable past dates in the date picker UI
                                            deliveryDateInput.setAttribute('min', todayFormatted);

                                            // Store the last valid value
                                            let lastValidValue = deliveryDateInput.value;

                                            // Validate when leaving the input (blur event)
                                            deliveryDateInput.addEventListener('blur', function() {
                                                if (deliveryDateInput.value && deliveryDateInput.value < todayFormatted) {
                                                    if (typeof Swal !== 'undefined') {
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Invalid Date',
                                                            text: 'Please select today or a future date. Past dates are not allowed for delivery/pickup.',
                                                            confirmButtonColor: '#3085d6',
                                                            confirmButtonText: 'OK'
                                                        });
                                                    }
                                                    deliveryDateInput.value = lastValidValue;
                                                } else {
                                                    lastValidValue = deliveryDateInput.value;
                                                }
                                            });

                                            // Update last valid value on change
                                            deliveryDateInput.addEventListener('change', function() {
                                                if (deliveryDateInput.value && deliveryDateInput.value >= todayFormatted) {
                                                    lastValidValue = deliveryDateInput.value;
                                                }
                                            });

                                            // Initial validation in case the pre-filled value is a past date
                                            if (deliveryDateInput.value && deliveryDateInput.value < todayFormatted) {
                                                if (typeof Swal !== 'undefined') {
                                                    Swal.fire({
                                                        icon: 'warning',
                                                        title: 'Invalid Pre-filled Date',
                                                        text: 'The pre-filled delivery/pickup date is in the past. Please select a valid date.',
                                                        confirmButtonColor: '#3085d6',
                                                        confirmButtonText: 'OK'
                                                    });
                                                }
                                                deliveryDateInput.value = '';
                                                lastValidValue = '';
                                            }
                                        })();
                                    </script>

                                    <!-- Address -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Address</p>
                                        <input
                                            type="text" {{ $isFullyLocked ? 'readonly' : '' }}
                                            name="address"
                                            value="{{ $order->address ?? '' }}"
                                            placeholder="-"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0">
                                    </div>

                                    <!-- Landmark -->
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Landmark</p>
                                        <input
                                            type="text" {{ $isFullyLocked ? 'readonly' : '' }}
                                            name="landmark"
                                            value="{{ $order->landmark ?? '' }}"
                                            placeholder="-"
                                            class="w-full border-none bg-transparent p-0 text-xs font-medium text-gray-900 focus:ring-0">
                                    </div>
                                </div>

                                <div class="info-section space-y-2 border-l pe-6 ps-4">
                                    <h3 class="mb-0.5 text-xs font-semibold text-gray-700">Order Info</h3>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">SOF Order ID</p>
                                        <p class="text-xs font-medium text-gray-900">{{ $order->sof_id }}</p>
                                    </div>
                                    @php
                                        $channel = strtolower(trim($order->channel_order ?? ''));
                                        $channelClass = match ($channel) {
                                            'e-commerce', 'ecommerce', 'online' => 'bg-yellow-100 text-green-800',
                                            'wholesale', 'wholesaler' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Channel Order</p>
                                        <span class="{{ $channelClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
                                            {{ ucwords($channel ?: 'Unknown') }}
                                        </span>
                                    </div>

                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Order Status</p>
                                        <p class="text-xs font-medium text-gray-900">
                                            @php
                                                $status = ucwords(strtolower($order->order_status ?? 'New Order'));
                                                $statusClass = match ($status) {
                                                    'Completed' => 'bg-green-200 text-green-800',
                                                    'Archived' => 'bg-gray-200 text-gray-800',
                                                    'Cancelled' => 'bg-red-200 text-red-800',
                                                    'Pending' => 'bg-yellow-200 text-yellow-800',
                                                    'Rejected' => 'bg-orange-200 text-orange-800',
                                                    'For Approval' => 'bg-purple-100 text-purple-800',
                                                    'Approved' => 'bg-green-100 text-green-800',
                                                    default => 'bg-blue-100 text-blue-800',
                                                };
                                            @endphp

                                            <span class="{{ $statusClass }} inline-block rounded-lg px-2 py-1 text-xs font-medium">
                                                {{ $status }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Requesting Store & Personnel</p>
                                        <p class="text-xs font-medium text-gray-900">{{ $order->requesting_store }} -
                                            {{ \App\Models\User::find($order->requested_by)?->name ?? $order->requested_by }}</p>
                                    </div>


                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Warehouse</p>
                                        <p class="text-xs font-medium text-gray-900">
                                            {{ ucwords(\App\Support\LocationConfig::warehouseName($order->warehouse, $order->warehouse)) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-600">Date & Time of Order</p>
                                        <p class="text-xs font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($order->time_order)->format('F j, Y - h:i A') }}</p>
                                    </div>


                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border bg-white p-4 shadow-sm">
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Order Comments</label>
                            <textarea
                                name="comment"
                                {{ $isFullyLocked ? 'readonly' : '' }}
                                rows="2"
                                maxlength="1800"
                                data-original="{{ $order->comment ?? '' }}"
                                placeholder="Enter comment or notes for this order (optional)"
                                class="w-full resize-none rounded-lg border border-gray-200 p-2 text-xs text-gray-900 placeholder-gray-400 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-300">{{ $order->comment ?? '' }}</textarea>
                            <p class="mt-1 text-xs text-gray-400">Max 1,800 characters.</p>
                        </div>

                        <div class="relative overflow-x-auto overflow-y-visible rounded-xl border bg-white p-4 pb-24 shadow-sm">

                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
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

                            {{-- scroll hint for tablets/phones --}}
                            <div class="table-scroll-hint mb-2 w-fit items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-600">
                                ← Scroll table to see all columns →
                            </div>

                            <table class="order-items-table border border-gray-200 text-xs text-gray-700">
                                <thead class="bg-gray-100 text-xs uppercase">
                                    <tr>
                                        <th rowspan="2" class="border px-2 py-1 text-center">
                                            <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500">
                                        </th>
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
                                            class="border px-2 py-1">Item Comments</th>
                                        <th
                                            rowspan="2"
                                            class="border px-2 py-1">Transfer Number</th>

                                        <th rowspan="2" class="border px-2 py-1">Item Status</th>

                                    </tr>
                                    <tr>
                                        <th class="border p-1 text-center">QTY/PC</th>
                                        <th class="border p-1 text-center">QTY/CS</th>
                                        <th class="border p-1 text-center">Freebies</th>
                                        <th class="border p-1 text-center">Total QTY</th>
                                    </tr>
                                </thead>
                                @php
                                    $itemsLocked =
                                        in_array(strtolower($order->order_status), ['approved', 'for approval', 'completed', 'cancelled']) ||
                                        $hasAnyTransferNo ||
                                        str_contains(strtolower(Auth::user()->role), 'warehouse');
                                    // But super admin can always edit (optional, remove if you want super admin to also lock)
                                    if (Auth::user()->role === 'super admin') {
                                        $itemsLocked = false;
                                    }
                                @endphp
                                <tbody>
                                    @forelse ($order->items as $item)
                                        <tr
                                            data-index="{{ $loop->index }}"
                                            data-item-type="{{ $item->item_type }}"
                                            class="@if ($item->remarks === 'Item Cancelled') bg-red-50 @elseif ($item->item_type === 'FREEBIE') bg-green-50 @else bg-white @endif transition hover:bg-indigo-50">


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
                                            {{-- Checkbox column --}}
                                            <td class="td-checkbox border p-2 text-center">
                                                <input type="checkbox" name="items[{{ $loop->index }}][cancel]" value="1"
                                                    class="item-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500">
                                            </td>
                                            <td
                                                class="relative border p-2"
                                                contenteditable="false"
                                                data-field="sku"
                                                data-label="SKU"
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
                                                data-label="Description"
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
                                                @if (in_array($item->scheme, ['Freebie', 'Discount'])) contenteditable="false" @else contenteditable="{{ $itemsLocked ? 'false' : 'true' }}" @endif
                                                data-field="scheme"
                                                data-label="Scheme">
                                                {{ $item->scheme }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][scheme]"
                                                    value="{{ $item->scheme }}">
                                            </td>


                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="{{ $itemsLocked ? 'false' : 'true' }}"
                                                data-field="price_per_pc"
                                                data-label="Price/PC">{{ number_format($item->price_per_pc, 2) }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][price_per_pc]"
                                                    value="{{ $item->price_per_pc }}">
                                            </td>

                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="false"
                                                data-field="price"
                                                data-label="Price">{{ number_format($item->price, 2) }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][price]"
                                                    value="{{ $item->price }}">
                                            </td>
                                            <td
                                                class="border p-2 text-center"
                                                @if ($item->item_type == 'DISCOUNT') contenteditable="{{ $itemsLocked ? 'false' : 'true' }}" @endif
                                                data-field="discount"
                                                data-label="Discount">

                                                {{-- Always show numeric value only --}}
                                                {{ $item->item_type === 'DISCOUNT' ? $item->discount ?? 0 : 0 }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][discount]"
                                                    value="{{ $item->discount ?? 0 }}">
                                            </td>



                                            <td
                                                class="numeric-only border p-2 text-center"
                                                data-max="9"
                                                contenteditable="{{ $itemsLocked ? 'false' : 'true' }}"
                                                data-field="qty_per_pc"
                                                data-label="QTY/PC">{{ $item->qty_per_pc }}
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][qty_per_pc]"
                                                    value="{{ $item->qty_per_pc }}">
                                            </td>


                                            <td
                                                class="numeric-only border p-2 text-center"
                                                data-max="9"
                                                @if ($item->item_type !== 'FREEBIE') contenteditable="{{ $itemsLocked ? 'false' : 'true' }}" @else contenteditable="false" @endif
                                                data-field="qty_per_cs"
                                                data-label="QTY/CS">
                                                {{ $item->item_type !== 'FREEBIE' ? ($item->qty_per_cs == 0 ? '0' : $item->qty_per_cs) : '0' }}

                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][qty_per_cs]"
                                                    value="{{ $item->item_type !== 'FREEBIE' ? $item->qty_per_cs : 0 }}" />
                                            </td>



                                            <td
                                                class="numeric-only border p-2 text-center"
                                                data-max="9"
                                                @if ($item->item_type === 'FREEBIE') contenteditable="{{ $itemsLocked ? 'false' : 'true' }}" @else contenteditable="false" @endif
                                                data-field="freebies_per_cs"
                                                data-label="Freebies">
                                                @if ($item->item_type === 'DISCOUNT')
                                                    N/A
                                                @else
                                                    {!! $item->freebies_per_cs == 0 ? '0' : $item->freebies_per_cs !!}
                                                @endif
                                                <input
                                                    type="hidden"
                                                    name="items[{{ $loop->index }}][freebies_per_cs]"
                                                    value="{{ $item->item_type === 'FREEBIE' ? $item->freebies_per_cs : 0 }}">
                                            </td>


                                            <td
                                                class="numeric-only border p-2 text-center"
                                                contenteditable="false"
                                                data-max="9"
                                                data-field="total_qty"
                                                data-label="Total QTY">{{ $item->total_qty }}</td>
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][total_qty]"
                                                value="{{ $item->total_qty }}">


                                            <td
                                                class="border p-2 text-center"
                                                contenteditable="false"
                                                data-field="amount"
                                                data-label="Amount">{{ number_format($item->amount, 2) }}</td>
                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][amount]"
                                                value="{{ $item->amount }}">

                                            <td class="remark-cell border p-2 text-center" data-label="Remarks">
                                                <div class="relative">
                                                    <input
                                                        type="text" {{ $isFullyLocked ? 'readonly' : '' }}
                                                        name="items[{{ $loop->index }}][remarks]"
                                                        value="{{ old('items.' . $loop->index . '.remarks', $item->remarks ?? '') }}"
                                                        class="w-full border-none bg-transparent px-2 py-0 text-left text-xs transition-all duration-200 ease-in-out focus:outline-none focus:ring-0" />
                                                </div>
                                            </td>


                                            {{-- Store Order No Column with BOL underneath --}}
                                            <td class="border p-2 text-center align-top"
                                                contenteditable="false"
                                                data-field="store_order_no"
                                                data-label="Transfer No.">

                                                <div class="flex flex-col items-center space-y-1 py-1">
                                                    {{-- Store Order Number --}}
                                                    <div class="text-sm font-semibold text-blue-600">
                                                        {{ $item->store_order_no }}
                                                    </div>

                                                    {{-- BOL Container - Only show if store_order_no exists --}}
                                                    @if (!empty($item->store_order_no))
                                                        <div class="bol-container w-full text-xs"
                                                            data-tsf="{{ $item->store_order_no }}"
                                                            data-sku="{{ $item->sku ?? '' }}">
                                                            <div class="flex items-center justify-center">
                                                                <span class="mr-1 font-medium text-gray-600">BOL:</span>
                                                                <span class="bol-loading inline-flex items-center text-gray-400">
                                                                    <svg class="mr-1 h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                        <path class="opacity-75" fill="currentColor"
                                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                        </path>
                                                                    </svg>
                                                                    Loading...
                                                                </span>
                                                                <span class="bol-value hidden font-semibold text-green-600"></span>
                                                                <span class="bol-na hidden font-semibold text-gray-400">N/A</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <input type="hidden" name="items[{{ $loop->index }}][store_order_no]" value="{{ $item->store_order_no }}">
                                            </td>


                                            {{-- Store Order No Column with API call --}}
                                            <td class="relative border p-2 text-center"
                                                contenteditable="false"
                                                data-label="Status"
                                                data-item-index="{{ $loop->index }}"
                                                @if (!empty($item->store_order_no)) data-store-order-no="{{ $item->store_order_no }}" 
                                                data-load-status="true" @endif>
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

                                    <script nonce="{{ $cspNonce ?? '' }}">
                                        (function() {
                                            // Select/Deselect All functionality
                                            const selectAllCheckbox = document.getElementById('select-all');
                                            const itemCheckboxes = document.querySelectorAll('.item-checkbox');

                                            selectAllCheckbox.addEventListener('change', function() {
                                                itemCheckboxes.forEach(checkbox => {
                                                    checkbox.checked = this.checked;
                                                });
                                            });

                                            // Update select-all state when individual checkboxes change
                                            itemCheckboxes.forEach(checkbox => {
                                                checkbox.addEventListener('change', function() {
                                                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                                                    const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);

                                                    selectAllCheckbox.checked = allChecked;
                                                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                                                });
                                            });

                                            // Get all TDs with store order numbers
                                            const tds = document.querySelectorAll('td[data-load-status="true"]');

                                            tds.forEach((td) => {
                                                const storeOrderNo = td.dataset.storeOrderNo;
                                                const skuInput = td.closest('tr')?.querySelector('.sku-hidden');
                                                const sku = skuInput ? skuInput.value : null;

                                                const url =
                                                    "{{ route('order.status', [
                                                        'storeOrderNo' => '__ORDER__',
                                                        'sku' => '__SKU__',
                                                    ]) }}"
                                                    .replace('__ORDER__', storeOrderNo)
                                                    .replace('__SKU__', sku);

                                                fetch(url)
                                                    .then(async response => {
                                                        const text = await response.text();
                                                        if (!text) {
                                                            throw new Error('Empty response from server');
                                                        }
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
                                                            badgeClass = 'bg-purple-100 text-purple-800';
                                                            description = 'Order is pending for shipment';
                                                        }
                                                        //picking
                                                        else if (status === 'Picking') {
                                                            badgeClass = 'bg-orange-100 text-orange-800';
                                                            description = 'Order is being picked at the warehouse';
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
                                    <script nonce="{{ $cspNonce ?? '' }}">
                                        (function() {
                                            // Get all TDs with store order numbers
                                            const tds = document.querySelectorAll('td[data-load-status="true"]');
                                            let statusPromises = []; // Track all status fetch promises

                                            tds.forEach((td) => {
                                                const storeOrderNo = td.dataset.storeOrderNo;
                                                const skuInput = td.closest('tr')?.querySelector('.sku-hidden');
                                                const sku = skuInput ? skuInput.value : null;

                                                if (!storeOrderNo || !sku) {
                                                    // Resolve immediately for items without data
                                                    statusPromises.push(Promise.resolve());
                                                    return;
                                                }

                                                const url = "{{ route('order.status', ['storeOrderNo' => '__ORDER__', 'sku' => '__SKU__']) }}"
                                                    .replace('__ORDER__', storeOrderNo)
                                                    .replace('__SKU__', sku);

                                                const promise = fetch(url)
                                                    .then(async response => {
                                                        const text = await response.text();
                                                        if (!text) throw new Error('Empty response from server');
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
                                                        } else if (status === 'Picking') {
                                                            badgeClass = 'bg-orange-100 text-orange-800';
                                                            description = 'Order is being picked at the warehouse';
                                                        } else if (status === 'Pending') {
                                                            badgeClass = 'bg-purple-100 text-purple-800';
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

                                                        // Store status in dataset for later checking
                                                        td.dataset.itemStatus = status;
                                                        return {
                                                            status,
                                                            storeOrderNo,
                                                            sku
                                                        };
                                                    })
                                                    .catch(error => {
                                                        console.error('Status fetch error:', error);
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
                                                        td.dataset.itemStatus = 'Error';
                                                        return {
                                                            status: 'Error',
                                                            storeOrderNo,
                                                            sku
                                                        };
                                                    });

                                                statusPromises.push(promise);
                                            });

                                            // Wait for ALL statuses to load, then check if order can be completed
                                            Promise.all(statusPromises).then(() => {
                                                console.log('All statuses loaded, checking completion eligibility...');
                                                checkAndUpdateCompleteOrderOption();
                                            });

                                            // Function to check if all items are ready for completion
                                            function checkAndUpdateCompleteOrderOption() {
                                                const completeOption = document.getElementById('complete-order-option');
                                                if (!completeOption) return;

                                                // Get all rows with items
                                                const rows = document.querySelectorAll('tbody tr[data-index]');
                                                let allItemsReady = true;
                                                const pendingItems = [];
                                                let totalActiveItems = 0;

                                                rows.forEach(row => {
                                                    // Check if item is cancelled
                                                    const remarksInput = row.querySelector('input[name*="[remarks]"]');
                                                    const isCancelled = remarksInput && remarksInput.value === 'Item Cancelled';

                                                    if (isCancelled) {
                                                        return; // Skip cancelled items
                                                    }

                                                    totalActiveItems++;

                                                    // Get the status cell
                                                    const statusCell = row.querySelector('td[data-label="Status"]');
                                                    const status = statusCell?.dataset?.itemStatus || statusCell?.textContent?.trim() || 'Unknown';

                                                    // Get store order number
                                                    const storeOrderNoInput = row.querySelector('input[name*="[store_order_no]"]');
                                                    const storeOrderNo = storeOrderNoInput?.value;
                                                    const sku = row.querySelector('.sku-hidden')?.value || 'Unknown SKU';

                                                    // Check if item is ready
                                                    if (!storeOrderNo || storeOrderNo === '') {
                                                        allItemsReady = false;
                                                        pendingItems.push(`${sku} - No transfer number generated`);
                                                    } else if (!['Received', 'Shipped'].includes(status)) {
                                                        allItemsReady = false;
                                                        pendingItems.push(`${sku} - Status: ${status} (needs Received or Shipped)`);
                                                    }
                                                });

                                                // Show/hide and enable/disable the complete option
                                                if (totalActiveItems === 0) {
                                                    // No active items to complete
                                                    completeOption.style.display = 'none';
                                                } else if (allItemsReady) {
                                                    completeOption.style.display = 'block';
                                                    completeOption.disabled = false;
                                                    completeOption.textContent = 'Complete Order ✅';
                                                    completeOption.title = 'All items are ready for completion';

                                                    // Optional: Add visual indicator to the select
                                                    const orderActionSelect = document.getElementById('orderAction');
                                                    if (orderActionSelect) {
                                                        orderActionSelect.style.borderColor = '#10b981';
                                                        orderActionSelect.style.backgroundColor = '#f0fdf4';
                                                    }

                                                    console.log('✅ Order is ready for completion');
                                                } else {
                                                    completeOption.style.display = 'block';
                                                    completeOption.disabled = true;
                                                    completeOption.textContent = `Complete Order (${pendingItems.length} pending)`;
                                                    completeOption.title = `Cannot complete order:\n• ${pendingItems.join('\n• ')}`;
                                                    completeOption.style.opacity = '0.6';

                                                    console.log(`⏳ Order not ready: ${pendingItems.length} items pending`);
                                                }
                                            }
                                        })();
                                    </script>
                                    <script nonce="{{ $cspNonce ?? '' }}">
                                        // BOL Fetcher - Only updates the BOL under store order number
                                        document.addEventListener('DOMContentLoaded', function() {
                                            if (typeof jQuery === 'undefined') {
                                                console.error('jQuery not loaded – CSP may be blocking it. Reloading...');

                                                return;
                                            }
                                            // Find all BOL containers
                                            document.querySelectorAll('.bol-container').forEach(container => {
                                                const tsf = container.dataset.tsf;
                                                const sku = container.dataset.sku;

                                                if (tsf && sku && tsf !== 'N/A' && tsf !== '') {
                                                    // Use your order.status route since it already returns bol_number
                                                    const url = "{{ route('order.status', ['storeOrderNo' => '__TSF__', 'sku' => '__SKU__']) }}"
                                                        .replace('__TSF__', tsf)
                                                        .replace('__SKU__', sku);

                                                    fetch(url)
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            console.log('Order data for BOL:', data);

                                                            const loadingSpan = container.querySelector('.bol-loading');
                                                            const bolValueSpan = container.querySelector('.bol-value');
                                                            const bolNaSpan = container.querySelector('.bol-na');

                                                            // Hide loading
                                                            if (loadingSpan) loadingSpan.classList.add('hidden');

                                                            // Check if BOL number exists in the response
                                                            if (data.bol_number) {
                                                                bolValueSpan.textContent = data.bol_number;
                                                                bolValueSpan.classList.remove('hidden');
                                                            } else {
                                                                bolNaSpan.classList.remove('hidden');
                                                            }
                                                        })
                                                        .catch(error => {
                                                            console.error('Error fetching BOL:', error);
                                                            const loadingSpan = container.querySelector('.bol-loading');
                                                            const bolNaSpan = container.querySelector('.bol-na');

                                                            if (loadingSpan) loadingSpan.classList.add('hidden');
                                                            if (bolNaSpan) bolNaSpan.classList.remove('hidden');
                                                        });
                                                }
                                            });
                                        });
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

                                <!-- Add SweetAlert2 CDN in your layout/head section -->
                                {{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}

                                <!-- Add this button above or below your table -->
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span id="selected-count" class="hidden text-sm text-gray-600">
                                            <span class="font-semibold">0</span> items selected
                                        </span>
                                        <button type="button" id="cancel-items-btn"
                                            class="hidden items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Cancel Selected Items
                                        </button>

                                    </div>
                                </div>

                                <script nonce="{{ $cspNonce ?? '' }}">
                                    // hide all checkbox if order status is approved or completed

                                    (function() {
                                        const cancelBtn = document.getElementById('cancel-items-btn');
                                        const selectedCountSpan = document.getElementById('selected-count');
                                        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
                                        const selectAllCheckbox = document.getElementById('select-all');
                                        const orderStatus = '{{ strtolower($order->order_status) }}';
                                        const lockedStatuses = ['approved', 'completed'];

                                        // Hide checkboxes if order status is approved or completed
                                        if (lockedStatuses.includes(orderStatus)) {
                                            itemCheckboxes.forEach(checkbox => {
                                                checkbox.closest('td').style.display = 'none';
                                            });
                                            if (selectAllCheckbox) {
                                                selectAllCheckbox.closest('th').style.display = 'none';
                                            }
                                            cancelBtn.style.display = 'none';
                                            selectedCountSpan.style.display = 'none';
                                            return; // Exit early if status is locked
                                        }

                                        // Update selected count and button visibility
                                        function updateSelectedCount() {
                                            const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;

                                            if (checkedCount > 0) {
                                                // Show button and count
                                                cancelBtn.classList.remove('hidden');
                                                cancelBtn.classList.add('inline-flex');
                                                selectedCountSpan.classList.remove('hidden');
                                                selectedCountSpan.innerHTML = `<span class="font-semibold">${checkedCount}</span> items selected`;
                                            } else {
                                                // Hide button and count
                                                cancelBtn.classList.add('hidden');
                                                cancelBtn.classList.remove('inline-flex');
                                                selectedCountSpan.classList.add('hidden');
                                            }
                                        }

                                        // Listen to checkbox changes
                                        itemCheckboxes.forEach(checkbox => {
                                            checkbox.addEventListener('change', updateSelectedCount);
                                        });

                                        // Also update when select-all changes
                                        if (selectAllCheckbox) {
                                            selectAllCheckbox.addEventListener('change', updateSelectedCount);
                                        }

                                        // Show confirmation modal when cancel button is clicked
                                        cancelBtn.addEventListener('click', function() {
                                            const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;

                                            if (checkedCount === 0) {
                                                Swal.fire({
                                                    icon: 'warning',
                                                    title: 'No Items Selected',
                                                    text: 'Please select at least one item to cancel.',
                                                    confirmButtonColor: '#3b82f6'
                                                });
                                                return;
                                            }

                                            // Show confirmation dialog
                                            Swal.fire({
                                                title: 'Cancel Items?',
                                                html: `Are you sure you want to cancel <strong>${checkedCount}</strong> selected item(s)?<br><span class="text-sm text-gray-600">This action cannot be undone.</span>`,
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#dc2626',
                                                cancelButtonColor: '#6b7280',
                                                confirmButtonText: 'Yes, Cancel Items',
                                                cancelButtonText: 'No, Keep Items',
                                                reverseButtons: true,
                                                focusCancel: true
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    cancelItems();
                                                }
                                            });
                                        });

                                        // Function to cancel items
                                        function cancelItems() {
                                            // Show loading state
                                            Swal.fire({
                                                title: 'Processing...',
                                                html: 'Cancelling selected items',
                                                allowOutsideClick: false,
                                                allowEscapeKey: false,
                                                allowEnterKey: false,
                                                showConfirmButton: false,
                                                didOpen: () => {
                                                    Swal.showLoading();
                                                }
                                            });

                                            // Collect selected item IDs
                                            const selectedItems = [];
                                            itemCheckboxes.forEach((checkbox, index) => {
                                                if (checkbox.checked) {
                                                    const row = checkbox.closest('tr');
                                                    const itemIdInput = row.querySelector('input[name*="[id]"]');
                                                    if (itemIdInput) {
                                                        selectedItems.push(itemIdInput.value);
                                                    }
                                                }
                                            });

                                            // Prepare form data
                                            const formData = new FormData();
                                            selectedItems.forEach(itemId => {
                                                formData.append('item_ids[]', itemId);
                                            });

                                            // Get order ID from the page (adjust selector as needed)
                                            const orderId = '{{ $order->id }}'; // Or get it from a data attribute
                                            formData.append('order_id', orderId);

                                            // Add CSRF token
                                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                            if (csrfToken) {
                                                formData.append('_token', csrfToken);
                                            }

                                            // Make POST request
                                            fetch('{{ route('orders.cancel-items') }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'X-CSRF-TOKEN': csrfToken,
                                                        'Accept': 'application/json',
                                                    },
                                                    body: formData
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        // Show success message
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Success!',
                                                            text: data.message || 'Items cancelled successfully!',
                                                            confirmButtonColor: '#10b981',
                                                            timer: 2000,
                                                            timerProgressBar: true
                                                        }).then(() => {
                                                            // Reload the page or update the UI
                                                            window.location.reload();
                                                        });
                                                    } else {
                                                        // Show error message
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Failed',
                                                            text: data.message || 'Failed to cancel items. Please try again.',
                                                            confirmButtonColor: '#3b82f6'
                                                        });
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: 'An error occurred while cancelling items. Please try again.',
                                                        confirmButtonColor: '#3b82f6'
                                                    });
                                                });
                                        }

                                        // Initialize count
                                        updateSelectedCount();
                                    })();
                                </script>

                                <style nonce="{{ $cspNonce ?? '' }}">
                                    /* Optional: Customize SweetAlert2 styles */
                                    .swal2-popup {
                                        font-family: inherit;
                                    }

                                    .swal2-title {
                                        font-size: 1.5rem;
                                        font-weight: 600;
                                    }

                                    .swal2-html-container {
                                        font-size: 0.95rem;
                                        line-height: 1.5;
                                    }
                                </style>
                                @php
                                    $hasEmptyStoreOrderNo = $order->items->contains(function ($item) {
                                        return empty($item->store_order_no) && $item->remarks !== 'Item Cancelled';
                                    });
                                @endphp

                                @if ($order->order_status === 'approved' && $hasEmptyStoreOrderNo && !str_contains(strtolower(Auth::user()->role), 'warehouse'))
                                    <button
                                        type="button"
                                        id="generateSOButton"
                                        class="items-center justify-center rounded-md bg-green-700 px-3 py-2 text-xs font-medium text-white shadow-sm transition duration-200 hover:bg-green-800 focus:outline-none focus:ring-1 focus:ring-green-600 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50">
                                        Generate Transfer Number
                                    </button>
                                    <meta
                                        name="csrf-token"
                                        content="{{ csrf_token() }}">
                                @endif
                            </div>

                        </div>


                    </div>
                    <div class="relative col-span-1 grid lg:col-span-1">
                        <div class="relative grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">


                            <div class="actions-panel relative flex flex-col gap-2 space-y-2 rounded-lg border bg-white p-3 shadow-sm sm:col-span-2 lg:col-span-1 lg:justify-between">

                                <!-- Top: Order Actions -->
                                <div>
                                    <label
                                        for="orderAction"
                                        class="mb-2 block text-xs font-medium text-gray-700">
                                        Order Actions
                                    </label>

                                    @if ($order->order_status !== 'completed' && !str_contains(strtolower(Auth::user()->role), 'warehouse'))
                                        <select
                                            id="orderAction"
                                            class="w-full rounded-md border-gray-300 px-3 py-2 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Select Action --</option>

                                            @if (!str_contains(strtolower(Auth::user()->role), 'store manager') && !str_contains(strtolower(Auth::user()->role), 'warehouse'))
                                                @if ($order->order_status !== 'cancelled')
                                                    <option value="cancel">Cancel Order</option>
                                                @endif

                                                @if ($order->order_status === 'cancelled')
                                                    <option value="restore">Restore Order</option>
                                                @endif

                                                @if (!in_array($order->order_status, ['for approval', 'approved', 'rejected']))
                                                    <option value="for approval">Request For Approval</option>
                                                @endif

                                                {{-- approved order status and all item status is received or shipped --}}
                                                @if ($order->order_status === 'approved')
                                                    <option value="complete" id="complete-order-option" style="display: none;">Complete Order</option>
                                                @endif

                                                {{-- @if ($order->order_status !== 'processing')
																								<option value="processing">Mark as Processing</option>
																						@endif

																						@if ($order->order_status !== 'completed')
																								<option value="completed">Mark as Completed</option>
																						@endif --}}
                                            @endif

                                            @if (in_array(Auth::user()->role, ['store manager', 'super admin']))
                                                @if (in_array($order->order_status, ['for approval', 'rejected']))
                                                    <option value="approve">Approve Order</option>
                                                @endif

                                                @if (in_array($order->order_status, ['for approval', 'approved']))
                                                    <option value="rejected">Reject Order</option>
                                                @endif
                                            @endif
                                        </select>
                                    @endif
                                    @if ($order->approval_document)
                                        <div class="mt-4 rounded border border-dashed border-gray-300 bg-gray-50 px-2 py-4">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="pl-1 text-gray-600">Approved Document</span>
                                                <a href="{{ asset('storage/' . $order->approval_document) }}"
                                                    class="approval-doc-view rounded bg-indigo-600 px-2 py-2 text-xs text-white hover:bg-indigo-700"
                                                    data-doc-url="{{ asset('storage/' . $order->approval_document) }}">
                                                    View
                                                </a>
                                            </div>
                                        </div>

                                        <script nonce="{{ $cspNonce ?? '' }}">
                                            document.addEventListener('click', function(e) {
                                                const link = e.target.closest('.approval-doc-view');
                                                if (!link) return;
                                                e.preventDefault();
                                                previewApprovalDocument(link.dataset.docUrl);
                                            });

                                            function previewApprovalDocument(url) {
                                                const ext = url.split('?')[0].split('.').pop().toLowerCase();
                                                let content = '';

                                                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                                                    content = `<img id="swalDocImg" src="${url}"
                 style="max-height:400px;width:auto;cursor:zoom-in;display:block;margin:0 auto;">`;
                                                } else if (ext === 'pdf') {
                                                    const h = Math.min(window.innerHeight * 0.75, 500);
                                                    content = `<iframe src="${url}" height="${h}px" width="100%"></iframe>`;
                                                } else if (['doc', 'docx'].includes(ext)) {
                                                    content = `
                    <div class="text-center p-4">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">Word Document</p>
                        <p class="text-xs text-gray-500 mt-1">Click Download to view this file</p>
                    </div>
                `;
                                                } else {
                                                    content = `
                    <div class="text-center p-4">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">File: .${ext.toUpperCase()}</p>
                        <p class="text-xs text-gray-500 mt-1">Preview not available</p>
                    </div>
                `;
                                                }

                                                Swal.fire({
                                                    title: ext === 'pdf' ? 'PDF Document' : (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext) ? 'Image' : 'Document'),
                                                    html: content,
                                                    width: Math.min(window.innerWidth * 0.95, 700) + 'px',
                                                    showConfirmButton: true,
                                                    confirmButtonText: '📥 Download',
                                                    confirmButtonColor: '#4f46e5',
                                                    showCloseButton: true,
                                                    allowOutsideClick: false,
                                                    didOpen: () => {
                                                        const img = document.getElementById('swalDocImg');
                                                        if (img) {
                                                            img.onerror = () => {
                                                                img.insertAdjacentHTML('afterend',
                                                                    '<p style="color:#ef4444;text-align:center;margin-top:8px">Failed to load image</p>');
                                                                img.style.display = 'none';
                                                            };
                                                            img.addEventListener('click', () => {
                                                                img.style.transition = 'transform 0.2s';
                                                                img.style.transform = img.style.transform === 'scale(1.5)' ? 'scale(1)' : 'scale(1.5)';
                                                            });
                                                        }
                                                    }
                                                }).then((r) => r.isConfirmed && window.open(url, '_blank'));
                                            }
                                        </script>
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
                                                        <p class="mt-1 text-gray-600">{!! $note->note ?? '—' !!}</p>
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

                                    @if (Auth::user()->role !== 'store manager' || (isset($partialUnlock) && $partialUnlock))
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



                            <script nonce="{{ $cspNonce ?? '' }}">
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
                                                    title: 'Approve SOF order',
                                                    html: `
        <style>
            .sw-mode-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
            .sw-mode-card{border:1.5px solid #e5e7eb;border-radius:8px;padding:14px 10px;cursor:pointer;text-align:center;background:#f9fafb;transition:all .15s}
            .sw-mode-card:hover{background:#fff}
            .sw-mode-card.active{border-color:#16A34A;background:#f0fdf4}
            .sw-panel{display:none}.sw-panel.active{display:block}
            .sw-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:9px 12px;font-size:12px;color:#1e40af;margin-bottom:12px;text-align:left}
            .sw-label{font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;text-align:left}
            .sw-req{color:#dc2626}
            .sw-drop{border:1.5px dashed #d1d5db;border-radius:8px;padding:16px;text-align:center;cursor:pointer;background:#f9fafb;transition:all .15s}
            .sw-drop:hover,.sw-drop.over{border-color:#16A34A;background:#f0fdf4}
            .sw-badge{display:none;align-items:center;gap:8px;padding:6px 10px;border-radius:6px;background:#f3f4f6;font-size:12px;margin-top:6px;text-align:left}
            .sw-badge-name{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#111}
            .sw-badge-rm{cursor:pointer;color:#9ca3af;font-size:16px;line-height:1}
            .sw-sig-tabs{display:grid;grid-template-columns:1fr 1fr;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;margin-bottom:8px}
            .sw-sig-tab{padding:8px;font-size:12px;font-weight:500;text-align:center;cursor:pointer;border:none;background:#f9fafb;color:#6b7280}
            .sw-sig-tab:not(:last-child){border-right:1px solid #e5e7eb}
            .sw-sig-tab.active{background:#fff;color:#111}
            .sw-canvas-wrap{position:relative;border:1.5px solid #2563EB;border-radius:8px;background:#f9fafb}
            #swSigCanvas{display:block;width:100%;height:120px;cursor:crosshair;touch-action:none;border-radius:6px}
            .sw-clear{position:absolute;top:5px;right:7px;font-size:11px;color:#6b7280;background:#fff;border:1px solid #e5e7eb;border-radius:4px;padding:2px 8px;cursor:pointer}
            .sw-sig-hint{font-size:11px;color:#9ca3af;text-align:center;margin-top:3px}
            .sw-prev-wrap{position:relative;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;background:#f9fafb;margin-top:6px}
            .sw-prev-wrap img{display:block;width:100%;max-height:100px;object-fit:contain}
            .sw-rm-sig{position:absolute;top:4px;right:4px;background:#fff;border:1px solid #e5e7eb;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:13px;line-height:20px;text-align:center;color:#6b7280}
        </style>
        <div style="text-align:left;font-size:13px">
            <p style="font-size:12px;color:#6b7280;margin-bottom:14px;text-align:center">
                The SOF PDF will serve as the approval document
            </p>

            <div class="sw-mode-grid">
                <div class="sw-mode-card" id="swModeA">
                    <div style="font-size:22px;margin-bottom:6px">📄</div>
                    <p style="font-size:13px;font-weight:600;color:#111">Upload signed SOF</p>
                    <p style="font-size:11px;color:#9ca3af;margin-top:2px">Printed, signed &amp; scanned</p>
                </div>
                <div class="sw-mode-card" id="swModeB">
                    <div style="font-size:22px;margin-bottom:6px">✍️</div>
                    <p style="font-size:13px;font-weight:600;color:#111">Sign digitally</p>
                    <p style="font-size:11px;color:#9ca3af;margin-top:2px">Embedded into the SOF PDF</p>
                </div>
            </div>

            <div class="sw-panel" id="swPanelA">
                <div class="sw-info">📋 Upload the signed and scanned copy of this order's SOF PDF.</div>
                <p class="sw-label">Signed SOF PDF <span class="sw-req">*</span></p>
                <div class="sw-drop" id="swScanDrop">
                    <div style="font-size:22px;margin-bottom:4px">📄</div>
                    <p style="font-size:13px;font-weight:500;color:#2563EB">Click or drag signed SOF here</p>
                    <p style="font-size:11px;color:#9ca3af;margin-top:2px">PDF only — max 10 MB</p>
                    <input type="file" id="swScanFile" accept=".pdf" style="display:none">
                </div>
                <div class="sw-badge" id="swScanBadge">
                    📎 <span class="sw-badge-name" id="swScanName"></span>
                    <span class="sw-badge-rm" id="swScanBadgeRm">×</span>
                </div>
            </div>

            <div class="sw-panel" id="swPanelB">
                <div class="sw-info">✍️ Your signature will be embedded above the <strong>Approved by</strong> line in the SOF PDF.</div>
                <p class="sw-label">Signature <span class="sw-req">*</span></p>
                <div class="sw-sig-tabs">
                    <button class="sw-sig-tab active" id="swTabDBtn" type="button">✏️ Draw</button>
                    <button class="sw-sig-tab" id="swTabUBtn" type="button">🖼️ Upload image</button>
                </div>

                <div class="sw-panel active" id="swSigDrawPanel">
                    <div class="sw-canvas-wrap">
                        <canvas id="swSigCanvas"></canvas>
                        <button class="sw-clear" type="button" id="swClearCanvas">Clear</button>
                    </div>
                    <p class="sw-sig-hint">Draw with mouse or finger</p>
                </div>

                <div class="sw-panel" id="swSigUploadPanel">
                    <div class="sw-drop" id="swSigDrop">
                        <div style="font-size:22px;margin-bottom:4px">🖼️</div>
                        <p style="font-size:13px;font-weight:500;color:#2563EB">Click or drop signature image</p>
                        <p style="font-size:11px;color:#9ca3af;margin-top:2px">PNG or JPG — transparent background recommended</p>
                        <input type="file" id="swSigFile" accept="image/*" style="display:none">
                    </div>
                    <div class="sw-prev-wrap" id="swSigPreview" style="display:none">
                        <img id="swSigImg" alt="Signature preview">
                        <span class="sw-rm-sig" id="swRmSig">×</span>
                    </div>
                </div>
            </div>
        </div>
        `,
                                                    icon: 'info',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#16A34A',
                                                    cancelButtonColor: '#aaa',
                                                    confirmButtonText: 'Approve',
                                                    width: Math.min(window.innerWidth * 0.95, 500) + 'px',

                                                    didOpen: () => {
                                                        window._swMode = null;
                                                        window._swSigPad = null;
                                                        window._swSigTab = 'draw';

                                                        // ── helpers ──────────────────────────────────────────
                                                        function swMode(mode) {
                                                            window._swMode = mode;
                                                            ['A', 'B'].forEach(x => {
                                                                const isActive = (mode === 'scan' && x === 'A') || (mode === 'digital' && x === 'B');
                                                                document.getElementById('swMode' + x).classList.toggle('active', isActive);
                                                                document.getElementById('swPanel' + x).classList.toggle('active', isActive);
                                                            });
                                                            if (mode === 'digital' && !window._swSigPad) {
                                                                const canvas = document.getElementById('swSigCanvas');
                                                                const dpr = window.devicePixelRatio || 1;
                                                                canvas.width = canvas.offsetWidth * dpr;
                                                                canvas.height = 120 * dpr;
                                                                canvas.style.height = '120px';
                                                                canvas.getContext('2d').scale(dpr, dpr);
                                                                window._swSigPad = new SignaturePad(canvas, {
                                                                    penColor: '#1e3a8a',
                                                                    backgroundColor: 'rgba(0,0,0,0)',
                                                                });
                                                                document.getElementById('swClearCanvas')
                                                                    .addEventListener('click', () => window._swSigPad.clear());
                                                            }
                                                        }

                                                        function swSigTab(tab) {
                                                            window._swSigTab = tab;
                                                            document.getElementById('swTabDBtn').classList.toggle('active', tab === 'draw');
                                                            document.getElementById('swTabUBtn').classList.toggle('active', tab === 'upload');
                                                            document.getElementById('swSigDrawPanel').classList.toggle('active', tab === 'draw');
                                                            document.getElementById('swSigUploadPanel').classList.toggle('active', tab === 'upload');
                                                        }

                                                        function swClearScan() {
                                                            document.getElementById('swScanFile').value = '';
                                                            document.getElementById('swScanBadge').style.display = 'none';
                                                        }

                                                        function swClearSig() {
                                                            document.getElementById('swSigFile').value = '';
                                                            document.getElementById('swSigPreview').style.display = 'none';
                                                            document.getElementById('swSigDrop').style.display = 'block';
                                                            document.getElementById('swSigImg').src = '';
                                                        }

                                                        // ── mode card clicks ──────────────────────────────────
                                                        document.getElementById('swModeA').addEventListener('click', () => swMode('scan'));
                                                        document.getElementById('swModeB').addEventListener('click', () => swMode('digital'));

                                                        // ── signature tab clicks ──────────────────────────────
                                                        document.getElementById('swTabDBtn').addEventListener('click', () => swSigTab('draw'));
                                                        document.getElementById('swTabUBtn').addEventListener('click', () => swSigTab('upload'));

                                                        // ── scan drop zone ────────────────────────────────────
                                                        const scanDrop = document.getElementById('swScanDrop');
                                                        const scanFile = document.getElementById('swScanFile');
                                                        scanDrop.addEventListener('click', () => scanFile.click());
                                                        scanDrop.addEventListener('dragover', e => {
                                                            e.preventDefault();
                                                            scanDrop.classList.add('over');
                                                        });
                                                        scanDrop.addEventListener('dragleave', () => scanDrop.classList.remove('over'));
                                                        scanDrop.addEventListener('drop', e => {
                                                            e.preventDefault();
                                                            scanDrop.classList.remove('over');
                                                            const f = e.dataTransfer.files[0];
                                                            if (f) {
                                                                document.getElementById('swScanBadge').style.display = 'flex';
                                                                document.getElementById('swScanName').textContent = f.name;
                                                            }
                                                        });
                                                        scanFile.addEventListener('change', function() {
                                                            if (this.files[0]) {
                                                                document.getElementById('swScanBadge').style.display = 'flex';
                                                                document.getElementById('swScanName').textContent = this.files[0].name;
                                                            }
                                                        });
                                                        document.getElementById('swScanBadgeRm').addEventListener('click', swClearScan);

                                                        // ── signature drop zone ───────────────────────────────
                                                        const sigDrop = document.getElementById('swSigDrop');
                                                        const sigFile = document.getElementById('swSigFile');
                                                        sigDrop.addEventListener('click', () => sigFile.click());
                                                        sigDrop.addEventListener('dragover', e => {
                                                            e.preventDefault();
                                                            sigDrop.classList.add('over');
                                                        });
                                                        sigDrop.addEventListener('dragleave', () => sigDrop.classList.remove('over'));
                                                        sigDrop.addEventListener('drop', e => {
                                                            e.preventDefault();
                                                            sigDrop.classList.remove('over');
                                                            const f = e.dataTransfer.files[0];
                                                            if (f) {
                                                                document.getElementById('swSigImg').src = URL.createObjectURL(f);
                                                                document.getElementById('swSigPreview').style.display = 'block';
                                                                sigDrop.style.display = 'none';
                                                            }
                                                        });
                                                        sigFile.addEventListener('change', function() {
                                                            if (!this.files[0]) return;
                                                            document.getElementById('swSigImg').src = URL.createObjectURL(this.files[0]);
                                                            document.getElementById('swSigPreview').style.display = 'block';
                                                            sigDrop.style.display = 'none';
                                                        });
                                                        document.getElementById('swRmSig').addEventListener('click', swClearSig);
                                                    },

                                                    preConfirm: () => {
                                                        const mode = window._swMode;
                                                        if (!mode) {
                                                            Swal.showValidationMessage('Please choose an approval method.');
                                                            return false;
                                                        }

                                                        if (mode === 'scan') {
                                                            const f = document.getElementById('swScanFile').files[0];
                                                            if (!f) {
                                                                Swal.showValidationMessage('Please upload the signed SOF PDF.');
                                                                return false;
                                                            }
                                                            return {
                                                                mode: 'scan',
                                                                file: f,
                                                                signature: null
                                                            };
                                                        }

                                                        if (window._swSigTab === 'draw') {
                                                            if (!window._swSigPad || window._swSigPad.isEmpty()) {
                                                                Swal.showValidationMessage('Please draw your signature.');
                                                                return false;
                                                            }
                                                            return {
                                                                mode: 'digital',
                                                                file: null,
                                                                signature: window._swSigPad.toDataURL('image/png')
                                                            };
                                                        }

                                                        const img = document.getElementById('swSigImg');
                                                        if (!img.src || img.src === window.location.href) {
                                                            Swal.showValidationMessage('Please upload a signature image.');
                                                            return false;
                                                        }
                                                        return fetch(img.src)
                                                            .then(r => r.blob())
                                                            .then(blob => new Promise(resolve => {
                                                                const reader = new FileReader();
                                                                reader.onloadend = () => resolve({
                                                                    mode: 'digital',
                                                                    file: null,
                                                                    signature: reader.result
                                                                });
                                                                reader.readAsDataURL(blob);
                                                            }));
                                                    }

                                                }).then(result => {
                                                    if (!result.isConfirmed) {
                                                        actionSelect.value = '';
                                                        return;
                                                    }

                                                    const {
                                                        mode,
                                                        file,
                                                        signature
                                                    } = result.value;
                                                    const formData = new FormData();
                                                    formData.append('_token', csrfToken);
                                                    formData.append('id', orderId);
                                                    formData.append('approval_mode', mode);
                                                    if (file) formData.append('scanned_sof', file);
                                                    if (signature) formData.append('approval_signature', signature);

                                                    Swal.fire({
                                                        title: 'Processing…',
                                                        text: mode === 'digital' ? 'Embedding signature into SOF PDF…' : 'Uploading signed SOF…',
                                                        allowOutsideClick: false,
                                                        didOpen: () => Swal.showLoading(),
                                                    });

                                                    fetch("{{ route('orders.approve') }}", {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': csrfToken,
                                                                'X-Requested-With': 'XMLHttpRequest',
                                                                'Accept': 'application/json',
                                                            },
                                                            body: formData,
                                                        })
                                                        .then(async response => {
                                                            const data = await response.json().catch(() => ({}));
                                                            if (!response.ok) throw new Error(data.message ?? 'Server error ' + response.status);
                                                            Swal.fire({
                                                                icon: 'success',
                                                                title: 'Approved!',
                                                                text: data.message ?? 'Order approved.',
                                                                timer: 2000,
                                                                showConfirmButton: false,
                                                            }).then(() => {
                                                                window.location.href = data.redirect ?? window.location.href;
                                                            });
                                                        })
                                                        .catch(err => {
                                                            Swal.fire('Failed', err.message, 'error');
                                                            actionSelect.value = '';
                                                        });
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



                            <div class="relative rounded-xl border bg-white p-4 shadow-sm sm:col-span-2 lg:col-span-1 lg:pb-8">
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
        {{-- script for input change detection --}}
        <!-- Complete Order Editing System -->


        <!-- Enhanced JavaScript System -->
        <script nonce="{{ $cspNonce ?? '' }}">
            $(document).ready(function() {
                // ========================================
                // CONSTANTS & CONFIGURATION
                // ========================================
                const $container = $('.order-details-component');
                const LOCK_STATUSES = ["approved", "completed", "for approval", "cancelled"];
                const ORDER_STATUS = "{{ $order->order_status }}".toLowerCase();
                const USER_ROLE = "{{ Auth::user()->role }}".toLowerCase();

                // Does any non-cancelled item already have a transfer number?
                // @php
                    //     $order->requesting_store = \App\Support\LocationConfig::storeName($order->requesting_store, $order->requesting_store);

                    //     // Add these lines:
                    //     $hasAnyTransferNo = $order->items->contains(function ($item) {
                    //         return !empty($item->store_order_no) && $item->remarks !== 'Item Cancelled';
                    //     });
                    //     $partialUnlock = in_array(strtolower($order->order_status), ['approved', 'for approval'])
                    //                      && !$hasAnyTransferNo
                    //                      && !str_contains(strtolower(Auth::user()->role), 'warehouse')
                    //                      && Auth::user()->role !== 'super admin';
                    //
                @endphp


                const HAS_TRANSFER_NO = {{ $hasAnyTransferNo ? 'true' : 'false' }};
                const IS_WAREHOUSE = USER_ROLE.includes('warehouse');

                // Store personnel get a partial unlock on an approved order ONLY while no
                // transfer number exists yet — limited to Customer / Payment / Delivery info.
                const PARTIAL_UNLOCK = ['approved', 'for approval'].includes(ORDER_STATUS) &&
                    !HAS_TRANSFER_NO &&
                    !IS_WAREHOUSE &&
                    USER_ROLE !== 'super admin';

                const IS_LOCKED = (LOCK_STATUSES.includes(ORDER_STATUS) && USER_ROLE !== 'super admin') ||
                    IS_WAREHOUSE ||
                    HAS_TRANSFER_NO; // ← any transfer number = fully locked

                const ITEMS_LOCKED = IS_LOCKED; // table follows the same lock


                if (!IS_LOCKED) {
                    $container.addClass('editable');
                } else {
                    $container.removeClass('editable');
                }
                // Change Detection Variables
                let hasChanges = false;
                let changesCount = 0;
                const submitButton = $('#submitButton');
                const submitButtonText = $('#submitButtonText');
                const changesCounter = $('#changesCounter');
                const changesCountElement = $('#changesCount');

                console.log('🚀 Initializing order page');
                console.log('📊 Order Status:', ORDER_STATUS);
                console.log('🔒 Is Locked:', IS_LOCKED);

                // ========================================
                // ORDER CALCULATION SYSTEM
                // ========================================
                class OrderCalculationSystem {

                    constructor() {
                        if (!ITEMS_LOCKED) {
                            this.initializeEventListeners();
                        }
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
                                if (IS_LOCKED) return;
                                const row = e.target.closest("tr");
                                this.calculateRowTotals(row);
                                setTimeout(() => updateOrderTotal(), 50);
                            }
                        );

                        $(document).on(
                            "focus",
                            'td[data-field="qty_per_cs"], td[data-field="freebies_per_cs"]',
                            function() {
                                if (IS_LOCKED) return;
                                if ($(this).text().trim() === "-") {
                                    $(this).text("0");
                                }
                            }
                        );

                        $(document).on(
                            "blur",
                            'td[data-field="qty_per_cs"], td[data-field="freebies_per_cs"]',
                            function() {
                                if (IS_LOCKED) return;
                                const value = $(this).text().trim();
                                if (value === "" || value === "0") {
                                    $(this).text("0");
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

                            // Check if item is cancelled
                            const remarksSelect = row.querySelector(`select[name="items[${index}][remarks]"]`);
                            const isCancelled = remarksSelect?.value === "Item Cancelled";

                            if (itemType === "MAIN") {
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
                                    freebiesPerCsNext: row.querySelector(`input[name="items[${index + 1}][freebies_per_cs]"]`),
                                    totalQty: row.querySelector(`input[name="items[${index}][total_qty]"]`),
                                    price: row.querySelector(`input[name="items[${index}][price]"]`),
                                    amount: row.querySelector(`input[name="items[${index}][amount]"]`),
                                    discount: row.querySelector(`input[name="items[${index}][discount]"]`),
                                };

                                const values = this.extractValues(cells);
                                const calculations = this.performCalculations(values, schemeValue, isCancelled);

                                this.updateRowDisplay(cells, inputs, calculations);
                                pendingFreebieQty = calculations.freebies;
                            } else if (itemType === "FREEBIE") {
                                pendingFreebieQty = 0;
                            }
                        }
                    }

                    calculateRowTotals(row) {
                        if (!row) return;
                        const index = $(row).data("index");
                        if (index === undefined) return;

                        const itemTypeInput = row.querySelector(`input[name="items[${index}][item_type]"]`);
                        const itemType = itemTypeInput?.value || "MAIN";

                        // Check if item is cancelled
                        const remarksSelect = row.querySelector(`select[name="items[${index}][remarks]"]`);
                        const isCancelled = remarksSelect?.value === "Item Cancelled";

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
                            values.qtyPerCs = values.freebiesPerCs;
                            values.freebiesPerCs = 0;
                        }

                        const calculations = this.performCalculations(values, null, isCancelled);
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

                    performCalculations(values, schemeValue, isCancelled = false) {
                        const calculations = {
                            price: 0,
                            totalQty: 0,
                            amount: 0,
                            freebies: 0
                        };

                        // If item is cancelled, set amount to 0
                        if (isCancelled) {
                            calculations.price = 0;
                            calculations.totalQty = 0;
                            calculations.amount = 0;
                            calculations.freebies = 0;
                            return calculations;
                        }

                        const basePrice = values.pricePerPc * values.qtyPerPc;

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

                        calculations.price = basePrice - discountValue;
                        calculations.totalQty = 0;
                        if (!isNaN(values.qtyPerCs)) calculations.totalQty += values.qtyPerCs;
                        if (!isNaN(values.freebiesPerCs)) calculations.totalQty += values.freebiesPerCs;
                        calculations.amount = calculations.price * calculations.totalQty;
                        calculations.freebies = values.freebiesPerCs;

                        return calculations;
                    }

                    updateRowDisplay(cells, inputs, calc) {
                        const safeFixed = (num) => (isNaN(num) ? "0.00" : num.toFixed(2));

                        if (cells.totalQty) cells.totalQty.textContent = calc.totalQty;
                        if (cells.price) cells.price.textContent = safeFixed(calc.price);
                        if (cells.amount) cells.amount.textContent = safeFixed(calc.amount);

                        if (cells.freebiesPerCs) {
                            if (calc.freebies > 0) {
                                cells.freebiesPerCs.textContent = calc.freebies;
                                if (inputs.freebiesPerCs) inputs.freebiesPerCs.value = calc.freebies;
                            }
                        }

                        if (inputs.totalQty) inputs.totalQty.value = calc.totalQty;
                        if (inputs.price) inputs.price.value = safeFixed(calc.price);
                        if (inputs.amount) inputs.amount.value = safeFixed(calc.amount);
                        if (inputs.discount) inputs.discount.value = cells.discount?.textContent.trim() || "";
                    }

                    getMainAmountTotal() {
                        let total = 0;
                        $("tbody tr[data-index]").each((_, row) => {
                            const index = $(row).data("index");
                            if (index === undefined) return;

                            const itemType = row.querySelector(`input[name="items[${index}][item_type]"]`)?.value || "MAIN";

                            // Check if item is cancelled
                            const remarksSelect = row.querySelector(`select[name="items[${index}][remarks]"]`);
                            const isCancelled = remarksSelect?.value === "Item Cancelled";

                            // Skip cancelled items
                            if (isCancelled) return;

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

                            // Check if item is cancelled
                            const remarksSelect = row.querySelector(`select[name="items[${index}][remarks]"]`);
                            const isCancelled = remarksSelect?.value === "Item Cancelled";

                            // Skip cancelled items
                            if (isCancelled) return;

                            const price = parseFloat(row.querySelector(`input[name="items[${index}][price]"]`)?.value) || 0;
                            const totalQty = parseFloat(row.querySelector(`input[name="items[${index}][total_qty]"]`)?.value) || 0;
                            const discount = (row.querySelector(`input[name="items[${index}][discount]"]`)?.value || "").trim();

                            const originalPrice = price;
                            let discountedPrice = originalPrice;

                            if (discount.endsWith("%")) {
                                const percent = parseFloat(discount.replace("%", "")) || 0;
                                discountedPrice = originalPrice * (1 - percent / 100);
                            } else if (discount !== "") {
                                const flat = parseFloat(discount) || 0;
                                discountedPrice = originalPrice - flat;
                            }

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

                            // Check if item is cancelled
                            const remarksSelect = row.querySelector(`select[name="items[${index}][remarks]"]`);
                            const isCancelled = remarksSelect?.value === "Item Cancelled";

                            // Skip cancelled items
                            if (isCancelled) return;

                            if (itemType === "FREEBIE") {
                                const amountInput = row.querySelector(`input[name="items[${index}][amount]"]`);
                                if (amountInput) total += parseFloat(amountInput.value) || 0;
                            }
                        });
                        return total;
                    }
                }
                // Standalone freebie calculator script
                // ========================================
                // FREEBIE CALCULATOR SYSTEM
                // ========================================
                function initializeFreebieCalculator() {
                    console.log('🎯 Initializing Freebie Calculator');

                    // Listen for ANY change on contenteditable cells in MAIN rows
                    $(document).on('blur input keyup paste', 'td[contenteditable="true"]', function(e) {
                        if (typeof IS_LOCKED !== 'undefined' && IS_LOCKED) return;

                        const cell = $(this);
                        const field = cell.data('field');
                        const row = cell.closest("tr");
                        const index = row.data("index");

                        // Only proceed if it's scheme or qty_per_cs
                        if (field !== 'scheme' && field !== 'qty_per_cs') return;
                        if (index === undefined) return;

                        // Check if this is a MAIN item
                        const itemTypeInput = row[0].querySelector(`input[name="items[${index}][item_type]"]`);
                        const itemType = itemTypeInput?.value || "MAIN";

                        console.log(`🔍 Field changed: ${field}, Item Type: ${itemType}, Row Index: ${index}`);

                        if (itemType === "MAIN") {
                            // Update the hidden input first
                            const hiddenInput = row.find(`input[name="items[${index}][${field}]"]`);
                            if (hiddenInput.length) {
                                let value = cell.text().trim();
                                if (value === '-' || value === 'N/A') value = '0';
                                hiddenInput.val(value);
                                console.log(`✏️ Updated hidden input [${field}]: ${value}`);
                            }

                            // Trigger freebie update
                            setTimeout(() => {
                                updateFreebieRowFromMain(row[0]);
                            }, 50);
                        }
                    });

                    // ALSO listen to the Order Calculation System's changes
                    $(document).on('DOMSubtreeModified', 'td[data-field="qty_per_cs"], td[data-field="scheme"]', function(e) {
                        if (typeof IS_LOCKED !== 'undefined' && IS_LOCKED) return;

                        const cell = $(this);
                        const row = cell.closest("tr");
                        const index = row.data("index");

                        if (index === undefined) return;

                        const itemTypeInput = row[0].querySelector(`input[name="items[${index}][item_type]"]`);
                        const itemType = itemTypeInput?.value || "MAIN";

                        if (itemType === "MAIN") {
                            updateFreebieRowFromMain(row[0]);
                        }
                    });
                }

                function updateFreebieRowFromMain(mainRow) {
                    const mainIndex = $(mainRow).data("index");
                    if (mainIndex === undefined) {
                        console.error('❌ Main row has no index');
                        return;
                    }

                    console.log(`\n📦 Processing MAIN row index: ${mainIndex}`);

                    // Get MAIN item values - try both from cell and hidden input
                    const schemeCell = mainRow.querySelector(`td[data-field="scheme"]`);
                    const qtyPerCsCell = mainRow.querySelector(`td[data-field="qty_per_cs"]`);

                    const schemeInput = mainRow.querySelector(`input[name="items[${mainIndex}][scheme]"]`);
                    const qtyPerCsInput = mainRow.querySelector(`input[name="items[${mainIndex}][qty_per_cs]"]`);

                    // Get values from cell text, fallback to hidden input
                    let schemeValue = schemeCell?.textContent.trim() || schemeInput?.value || "1+0";
                    let qtyPerCsText = qtyPerCsCell?.textContent.trim() || qtyPerCsInput?.value || "0";

                    // Handle "-" or "N/A"
                    if (qtyPerCsText === '-' || qtyPerCsText === 'N/A') qtyPerCsText = '0';

                    const qtyPerCsValue = parseInt(qtyPerCsText);

                    console.log(`   Scheme: ${schemeValue}`);
                    console.log(`   Qty/CS: ${qtyPerCsValue}`);

                    // Calculate freebies
                    const freebieCount = calculateFreebies(schemeValue, qtyPerCsValue);

                    console.log(`   💎 Calculated Freebies: ${freebieCount}`);

                    // Find the next FREEBIE row
                    let nextRow = mainRow.nextElementSibling;

                    if (!nextRow) {
                        console.warn('⚠️ No next row found after MAIN row');
                        return;
                    }

                    const nextIndex = $(nextRow).data("index");
                    if (nextIndex === undefined) {
                        console.error('❌ Next row has no index');
                        return;
                    }

                    const nextItemTypeInput = nextRow.querySelector(`input[name="items[${nextIndex}][item_type]"]`);
                    const nextItemType = nextItemTypeInput?.value || "MAIN";

                    console.log(`   Next row index: ${nextIndex}, Type: ${nextItemType}`);

                    if (nextItemType === "FREEBIE") {
                        console.log(`   ✅ Found FREEBIE row, updating...`);

                        // 1. Update freebies_per_cs hidden input
                        const freebiesInput = nextRow.querySelector(`input[name="items[${nextIndex}][freebies_per_cs]"]`);
                        if (freebiesInput) {
                            const oldValue = freebiesInput.value;
                            freebiesInput.value = freebieCount;
                            console.log(`      freebies_per_cs input: ${oldValue} → ${freebieCount}`);
                        } else {
                            console.error('      ❌ freebies_per_cs input not found!');
                        }

                        // 2. Update freebies_per_cs cell display
                        const freebiesCell = nextRow.querySelector('[data-field="freebies_per_cs"]');
                        if (freebiesCell) {
                            const oldText = freebiesCell.textContent;
                            freebiesCell.textContent = freebieCount > 0 ? freebieCount : "0";
                            console.log(`      freebies_per_cs cell: ${oldText} → ${freebiesCell.textContent}`);

                            // Mark as changed
                            if (typeof checkElementChange === 'function') {
                                checkElementChange(freebiesCell);
                            }
                        } else {
                            console.error('      ❌ freebies_per_cs cell not found!');
                        }

                        // 3. ✅ UPDATE TOTAL_QTY instead of qty_per_cs
                        const totalQtyInput = nextRow.querySelector(`input[name="items[${nextIndex}][total_qty]"]`);
                        if (totalQtyInput) {
                            const oldValue = totalQtyInput.value;
                            totalQtyInput.value = freebieCount;
                            console.log(`      total_qty input: ${oldValue} → ${freebieCount}`);
                        } else {
                            console.error('      ❌ total_qty input not found!');
                        }

                        // 4. ✅ UPDATE TOTAL_QTY cell display
                        const totalQtyCell = nextRow.querySelector('[data-field="total_qty"]');
                        if (totalQtyCell) {
                            const oldText = totalQtyCell.textContent;
                            totalQtyCell.textContent = freebieCount;
                            console.log(`      total_qty cell: ${oldText} → ${freebieCount}`);

                            // Mark as changed
                            if (typeof checkElementChange === 'function') {
                                checkElementChange(totalQtyCell);
                            }
                        } else {
                            console.error('      ❌ total_qty cell not found!');
                        }

                        // 5. Keep qty_per_cs as "N/A" for FREEBIE items (don't update it)
                        console.log(`      ℹ️ Leaving qty_per_cs as "N/A" for FREEBIE item`);

                        // 6. Recalculate row totals
                        if (typeof window.orderCalcSystem !== 'undefined' && window.orderCalcSystem.calculateRowTotals) {
                            console.log(`      🧮 Recalculating FREEBIE row amounts...`);
                            window.orderCalcSystem.calculateRowTotals(nextRow);
                        }

                        // 7. Update order total
                        setTimeout(() => {
                            if (typeof updateOrderTotal === 'function') {
                                updateOrderTotal();
                                console.log(`      💰 Order total updated`);
                            }
                        }, 50);

                        console.log(`   ✅ FREEBIE row update complete\n`);
                    } else {
                        console.warn(`   ⚠️ Next row is NOT a FREEBIE (it's ${nextItemType})\n`);
                    }
                }

                function calculateFreebies(scheme, qty) {
                    if (!scheme || qty <= 0) {
                        console.log(`   ⚠️ Invalid input: scheme="${scheme}", qty=${qty}`);
                        return 0;
                    }

                    // Parse scheme like "15+1"
                    const match = scheme.toString().match(/(\d+)\+(\d+)/);
                    if (!match) {
                        console.log(`   ⚠️ Scheme "${scheme}" doesn't match pattern X+Y`);
                        return 0;
                    }

                    const mainRatio = parseInt(match[1]); // 15
                    const freebieRatio = parseInt(match[2]); // 1

                    if (mainRatio <= 0) {
                        console.log(`   ⚠️ Invalid mainRatio: ${mainRatio}`);
                        return 0;
                    }

                    // Calculate: floor(35 / 15) * 1 = floor(2.33) * 1 = 2 * 1 = 2
                    const result = Math.floor(qty / mainRatio) * freebieRatio;

                    console.log(`   📊 Formula: floor(${qty} / ${mainRatio}) × ${freebieRatio} = ${result}`);

                    return result;
                }

                // Initialize on page load
                $(document).ready(function() {
                    console.log('📅 Document ready - initializing freebie calculator');

                    if (!ITEMS_LOCKED) {
                        initializeFreebieCalculator();

                        // Initialize all freebies after a delay
                        setTimeout(() => {
                            console.log('🔄 Running initial freebie calculation for all MAIN rows...');
                            $container.find('tbody tr[data-index]').each(function() {
                                const index = $(this).data("index");
                                const itemTypeInput = this.querySelector(`input[name="items[${index}][item_type]"]`);
                                const itemType = itemTypeInput?.value || "MAIN";

                                if (itemType === "MAIN") {
                                    updateFreebieRowFromMain(this);
                                }
                            });
                            console.log('✅ Initial freebie calculation complete\n');
                        }, 300);
                    } else {
                        console.log('🔒 Order is locked - skipping freebie calculator');
                    }
                });
                // ========================================
                // INITIALIZE ORDER CALCULATOR
                // ========================================
                const orderCalculator = new OrderCalculationSystem();

                // ========================================
                // LOCK FUNCTION
                // ========================================
                function lockFieldsByStatus() {
                    if (!IS_LOCKED && !ITEMS_LOCKED) return;
                    console.log('🔒 Locking fields...');

                    if (IS_LOCKED) {
                        // Lock inputs (except info-panel inputs when partial-unlocked)
                        $container.find('input:not([type="hidden"])' + (PARTIAL_UNLOCK ? ':not(.info-section input)' : ''))
                            .prop('readonly', true).css({
                                'pointer-events': 'none',
                                'cursor': 'default'
                            });

                        // Lock comment textarea (skip when partial-unlocked)
                        if (!PARTIAL_UNLOCK) {
                            $container.find('textarea[name="comment"]').prop('readonly', true).css({
                                'pointer-events': 'none',
                                'cursor': 'default',
                                'resize': 'none',
                                'opacity': '0.7'
                            });
                        }

                        // Lock selects (keep info-panel selects when partial-unlocked)
                        $container.find('select').not('#orderAction')
                            .filter(function() {
                                return !(PARTIAL_UNLOCK && $(this).closest('.info-section').length);
                            })
                            .prop('disabled', true).css({
                                'pointer-events': 'none',
                                'cursor': 'default',
                                'opacity': '1',
                                'background-color': 'transparent'
                            });
                    }

                    // Item-table cells ALWAYS lock when this function runs (ITEMS_LOCKED or IS_LOCKED)
                    $container.find('td[contenteditable="true"]').attr('contenteditable', 'false').css({
                        'pointer-events': 'none',
                        'cursor': 'default'
                    }).off();

                    $container.find('td[contenteditable-search="true"]').removeAttr('contenteditable-search').attr('contenteditable', 'false').css({
                        'pointer-events': 'none',
                        'cursor': 'default'
                    }).off();

                    // Submit button
                    if (PARTIAL_UNLOCK || !IS_LOCKED) {
                        submitButton.removeClass('hidden');
                    } else {
                        submitButton.prop('disabled', true).addClass('hidden');
                        changesCounter.addClass('hidden');
                    }
                }

                // ========================================
                // CHANGE DETECTION FUNCTIONS
                // ========================================
                const trackableElements = $container.find(
                    'input[type="text"], input[type="date"], input[type="email"], textarea[name="comment"], select:not(#orderAction), td[contenteditable="true"]'
                );

                function initializeOriginalValues() {
                    if (IS_LOCKED && !PARTIAL_UNLOCK) return;

                    trackableElements.filter('input, select, textarea').each(function() {
                        const $element = $(this);
                        const originalValue = $element.val() || '';
                        $element.data('original', originalValue);
                    });

                    trackableElements.filter('[contenteditable]').each(function() {
                        const $element = $(this);
                        const originalValue = $element.text().trim();
                        $element.data('original-value', originalValue);
                    });
                }

                function updateSubmitButtonState() {
                    if (IS_LOCKED && !PARTIAL_UNLOCK) return;

                    if (hasChanges && changesCount > 0) {
                        submitButton.prop('disabled', false)
                            .removeClass('bg-blue-600 hover:bg-blue-700')
                            .addClass('bg-green-600 hover:bg-green-700');
                        submitButtonText.text('Update');
                        changesCounter.removeClass('hidden');
                        changesCountElement.text(changesCount);
                    } else {
                        submitButton.prop('disabled', true)
                            .removeClass('bg-green-600 hover:bg-green-700')
                            .addClass('bg-blue-600 hover:bg-blue-700');
                        submitButtonText.text('Update');
                        changesCounter.addClass('hidden');
                    }
                }

                function checkElementChange(element) {
                    if (IS_LOCKED && !PARTIAL_UNLOCK) return;

                    const $element = $(element);

                    if ($element.prop('readonly') || $element.prop('disabled') ||
                        $element.attr('contenteditable') === 'false') {
                        return;
                    }

                    let currentValue, originalValue;

                    if ($element.is('[contenteditable]')) {
                        currentValue = $element.text().trim();
                        originalValue = $element.data('original-value');

                        if (originalValue === undefined) {
                            originalValue = currentValue;
                            $element.data('original-value', originalValue);
                        }
                    } else {
                        currentValue = $element.val() || '';
                        originalValue = $element.data('original');

                        if (originalValue === undefined) {
                            originalValue = currentValue;
                            $element.data('original', originalValue);
                        }
                    }

                    const normalizeValue = (val) => {
                        if (val === null || val === undefined) return '';
                        if (typeof val === 'string') return val.trim();
                        return String(val).trim();
                    };

                    const normalizedCurrent = normalizeValue(currentValue);
                    const normalizedOriginal = normalizeValue(originalValue);
                    const hasChanged = normalizedCurrent !== normalizedOriginal;

                    if (hasChanged) {
                        if (!$element.data('is-changed')) {
                            $element.data('is-changed', true);
                            if ($element.is('[contenteditable]')) {
                                $element.addClass('bg-yellow-100 border border-yellow-300 rounded');
                            } else if ($element.is('select')) {
                                $element.closest('td').addClass('bg-yellow-100 border border-yellow-300 rounded');
                            } else {
                                $element.removeClass('bg-transparent').addClass('bg-yellow-100 rounded');
                            }
                            changesCount++;
                        }
                    } else {
                        if ($element.data('is-changed')) {
                            $element.data('is-changed', false);
                            if ($element.is('[contenteditable]')) {
                                $element.removeClass('bg-yellow-100 border border-yellow-300 rounded');
                            } else if ($element.is('select')) {
                                $element.closest('td').removeClass('bg-yellow-100 border border-yellow-300 rounded');
                            } else {
                                $element.removeClass('bg-yellow-100 rounded').addClass('bg-transparent');
                            }
                            changesCount = Math.max(0, changesCount - 1);
                        }
                    }

                    hasChanges = changesCount > 0;
                    updateSubmitButtonState();
                }

                function initializeChangeDetection() {
                    if (IS_LOCKED && !PARTIAL_UNLOCK) {
                        console.log('⏭️  Skipping change detection - order is locked');
                        return;
                    }

                    console.log('🔍 Initializing change detection');
                    initializeOriginalValues();

                    setTimeout(() => {
                        trackableElements.each(function() {
                            $(this).data('is-changed', false);
                            $(this).removeClass('bg-yellow-100 border border-yellow-300 rounded');
                        });

                        changesCount = 0;
                        hasChanges = false;
                        updateSubmitButtonState();
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

                // ========================================
                // EVENT LISTENERS (Only if NOT locked)
                // ========================================
                if (!IS_LOCKED) {
                    trackableElements.filter('input, select, textarea').on('change input keyup', function() {
                        checkElementChange(this);
                    });

                    trackableElements.filter('[contenteditable]').on('input blur keyup', function() {
                        if (ITEMS_LOCKED) return;
                        checkElementChange(this);

                        const $this = $(this);
                        const row = $this.closest('tr');
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

                        orderCalculator.calculateRowTotals(row[0]);
                        updateOrderTotal();
                    });

                    // Product search functionality
                    $container.on('input blur keyup', 'td[data-field="price_per_pc"]', function(e) {
                        if (ITEMS_LOCKED) return;
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

                    $container.on('click', '.product-item', function() {
                        const selected = $(this);
                        const sku = selected.data('sku');
                        const description = selected.data('description');

                        const resultList = selected.closest('.search-results');
                        const inputCell = resultList.parent();
                        const currentRow = inputCell.closest('tr');

                        const skuCell = currentRow.find('[data-field="sku"]');
                        skuCell.text(sku);
                        currentRow.find('.sku-hidden').val(sku);

                        const descCell = currentRow.find('[data-field="item_description"]');
                        descCell.text(description);
                        currentRow.find('.desc-hidden').val(description);

                        resultList.empty().addClass('hidden');

                        checkElementChange(skuCell[0]);
                        checkElementChange(descCell[0]);

                        inputCell.focus();
                    });

                    $(document).on('click', function(e) {
                        if (!$(e.target).closest('.order-details-component').length) return;
                        if (!$(e.target).closest('[contenteditable-search="true"], .search-results').length) {
                            $container.find('.search-results').empty().addClass('hidden');
                        }
                    });

                    window.addEventListener('beforeunload', function(e) {
                        if (hasChanges) {
                            e.preventDefault();
                            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                            return e.returnValue;
                        }
                    });


                }
                // Partial unlock: track only info-panel field edits (item table stays locked)
                if (IS_LOCKED && PARTIAL_UNLOCK) {
                    $container.find('.info-section')
                        .find('input:not([type="hidden"]), select')
                        .add($container.find('textarea[name="comment"]'))
                        .on('change input keyup', function() {
                            checkElementChange(this);
                        });
                }


                function syncAllHiddenInputs() {
                    console.log('🔄 Syncing all contenteditable cells to hidden inputs...');

                    $("tbody tr[data-index]").each(function() {
                        const row = $(this);
                        const index = row.data('index');

                        if (index === undefined) return;

                        // List of fields to sync
                        const fieldsToSync = [
                            'sku',
                            'item_description',
                            'scheme',
                            'price_per_pc',
                            'price',
                            'discount',
                            'qty_per_pc',
                            'qty_per_cs',
                            'freebies_per_cs',
                            'total_qty',
                            'amount',
                            'store_order_no'
                        ];

                        fieldsToSync.forEach(field => {
                            const cell = row.find(`[data-field="${field}"]`);
                            // Look for hidden input both inside the cell AND as a sibling
                            let hiddenInput = cell.find(`input[name="items[${index}][${field}]"]`);
                            if (!hiddenInput.length) {
                                hiddenInput = row.find(`input[name="items[${index}][${field}]"]`);
                            }

                            if (cell.length) {
                                // If hidden input doesn't exist, create it
                                if (!hiddenInput.length) {
                                    console.warn(`⚠️ Hidden input not found for ${field}[${index}], creating it...`);
                                    hiddenInput = $(`<input type="hidden" name="items[${index}][${field}]" />`);
                                    cell.append(hiddenInput);
                                }

                                // Get text content, excluding any child elements (like the hidden input)
                                let value = cell.clone().children().remove().end().text().trim();

                                // Handle special cases
                                if (value === '-' || value === 'N/A') {
                                    value = '0';
                                }

                                // Remove formatting from numbers
                                if (['price_per_pc', 'price', 'amount'].includes(field)) {
                                    value = value.replace(/[₱,\s]/g, '');
                                }

                                hiddenInput.val(value);
                                console.log(`✓ Synced ${field}[${index}]: "${value}"`);
                            } else {
                                console.error(`❌ Cell not found for ${field}[${index}]`);
                            }
                        });
                    });

                    console.log('✅ All hidden inputs synced');
                }

                // ========================================
                // FORM SUBMISSION
                // ========================================
                $container.find('form').on('submit', function(e) {
                    if (IS_LOCKED && !PARTIAL_UNLOCK) return true;

                    if (!hasChanges) {
                        e.preventDefault();
                        alert('No changes detected to save.');
                        return false;
                    }

                    // CRITICAL: Sync all contenteditable values to hidden inputs
                    syncAllHiddenInputs();

                    // Log all form data for debugging
                    console.log('📝 Form data being submitted:');
                    const formData = new FormData(this);
                    for (let pair of formData.entries()) {
                        if (pair[0].includes('items[')) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }
                    }

                    window.onbeforeunload = null;
                    hasChanges = false;

                    submitButton.prop('disabled', true);
                    submitButtonText.text('Saving...');
                    submitButton.prepend(
                        '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>'
                    );

                    return true;
                });
                // Add this temporarily right before your form submit handler
                // $('form').on('submit', function(e) {
                //     e.preventDefault(); // Prevent actual submission temporarily

                //     console.log('=== FORM VALIDATION CHECK ===');
                //     const itemsData = {};

                //     $("tbody tr[data-index]").each(function() {
                //         const row = $(this);
                //         const index = row.data('index');

                //         itemsData[index] = {
                //             price: row.find(`input[name="items[${index}][price]"]`).val(),
                //             price_per_pc: row.find(`input[name="items[${index}][price_per_pc]"]`).val(),
                //             amount: row.find(`input[name="items[${index}][amount]"]`).val(),
                //             sku: row.find(`input[name="items[${index}][sku]"]`).val(),
                //             freebies_per_cs: row.find(`input[name="items[${index}][freebies_per_cs]"]`).val(),
                //         };
                //     });

                //     console.table(itemsData);

                //     // Check for missing required fields
                //     Object.keys(itemsData).forEach(index => {
                //         const item = itemsData[index];
                //         if (!item.price) console.error(`❌ Item ${index} missing PRICE!`);
                //         if (!item.price_per_pc) console.error(`❌ Item ${index} missing PRICE_PER_PC!`);
                //         if (!item.sku) console.error(`❌ Item ${index} missing SKU!`);
                //     });

                //     // Remove this e.preventDefault() once you verify data is correct
                // });

                // ========================================
                // RESET AFTER SUCCESSFUL SUBMISSION
                // ========================================
                @if (session('success'))
                    hasChanges = false;
                    changesCount = 0;
                    updateSubmitButtonState();

                    trackableElements.each(function() {
                        $(this).data('is-changed', false)
                            .removeClass('bg-yellow-100 border border-yellow-300 rounded');
                    });
                @endif

                // ========================================
                // INITIALIZATION SEQUENCE
                // ========================================
                setTimeout(() => {
                    console.log('⚙️ Running initialization sequence...');

                    // Step 1: Calculate everything
                    orderCalculator.calculateAllRows();
                    updateOrderTotal();

                    // Step 2: lock and/or enable change detection
                    if (IS_LOCKED || ITEMS_LOCKED) {
                        lockFieldsByStatus();
                        // Enable change detection for the still-editable fields:
                        //  - PARTIAL_UNLOCK: info panel on an approved order
                        //  - !IS_LOCKED: status is editable, only the table is locked
                        if (PARTIAL_UNLOCK || !IS_LOCKED) {
                            initializeChangeDetection();
                        }
                    } else {
                        initializeChangeDetection();
                        trackableElements.each(function() {
                            checkElementChange(this);
                        });
                    }

                    // Item-table cells always lock when ITEMS_LOCKED (transfer number OR locked status)
                    $container.find('td[contenteditable="true"]').attr('contenteditable', 'false').css({
                        'pointer-events': 'none',
                        'cursor': 'default'
                    }).off();

                    $container.find('td[contenteditable-search="true"]').removeAttr('contenteditable-search').attr('contenteditable', 'false').css({
                        'pointer-events': 'none',
                        'cursor': 'default'
                    }).off();

                    // Submit button visibility unchanged
                    if (PARTIAL_UNLOCK || !IS_LOCKED) {
                        submitButton.removeClass('hidden');
                    } else {
                        submitButton.prop('disabled', true).addClass('hidden');
                        changesCounter.addClass('hidden');
                    }

                    console.log('✅ Initialization complete');
                }, 150);
            });

            // ========================================
            // GENERATE SO BUTTON (Outside document.ready - uses addEventListener)
            // ========================================
            document.addEventListener('DOMContentLoaded', () => {
                const generateBtn = document.getElementById('generateSOButton');
                if (!generateBtn) return; // Button not rendered
                generateBtn.addEventListener('click', async () => {
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
                                        width: Math.min(window.innerWidth * 0.95, 800) + 'px',
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
                                        width: Math.min(window.innerWidth * 0.95, 800) + 'px',
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
                                width: Math.min(window.innerWidth * 0.95, 600) + 'px'
                            });

                            if (result.isConfirmed) {
                                await executeTransfer();
                            }
                        }
                    };

                    await executeTransfer();
                });
            });
        </script>
        <script nonce="{{ $cspNonce ?? '' }}">
            document.addEventListener('beforeinput', function(e) {
                const el = e.target;

                if (!el.classList.contains('numeric-only')) return;

                // Allow delete, backspace, undo, redo
                if (e.inputType.startsWith('delete') || e.inputType === 'historyUndo' || e.inputType === 'historyRedo') {
                    return;
                }

                // Block non-numeric input
                if (!/^\d+$/.test(e.data)) {
                    e.preventDefault();
                    return;
                }

                const max = parseInt(el.dataset.max || 6, 10);
                const current = el.textContent.replace(/\D/g, '');

                // Block if max length reached
                if (current.length >= max) {
                    e.preventDefault();
                }
            });
        </script>



        <script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

        <!-- Enhanced CSS for better visual feedback -->
        <style nonce="{{ $cspNonce ?? '' }}">
            /* Minimal custom scrollbar */
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background-color: rgba(100, 116, 139, 0.4);
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

            /* Loading animation */
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

            /* ══════════════════════════════════════════════
                                                                                                                                                                                                                                                                                                                                       MOBILE CARD LAYOUT — items table (2-column grid)
                                                                                                                                                                                                                                                                                                                                       Below 1024 px: table rows become cards with a 2-column
                                                                                                                                                                                                                                                                                                                                       form-like grid. Labels sit above their values, aligned
                                                                                                                                                                                                                                                                                                                                       left. All JS (data-field, contenteditable, hidden inputs)
                                                                                                                                                                                                                                                                                                                                       is untouched — only CSS display changes.
                                                                                                                                                                                                                                                                                                                                       ══════════════════════════════════════════════ */
            @media (max-width: 1023px) {

                /* ── 1. Kill horizontal scroll; table fills width ── */
                .order-items-table {
                    min-width: 0 !important;
                    width: 100%;
                    border: none;
                    border-collapse: separate;
                    border-spacing: 0;
                }

                /* ── 2. Hide desktop header ── */
                .order-items-table thead {
                    display: none !important;
                }

                /* ── 3. Each row → 2-column card ── */
                .order-items-table tbody tr[data-index] {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    column-gap: 0.75rem;
                    row-gap: 0;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.875rem;
                    margin-bottom: 1rem;
                    padding: 0.625rem 0.75rem 0.75rem;
                    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
                    background: white;
                    overflow: visible;
                }

                /* Row colour variants */
                .order-items-table tbody tr[data-index].bg-red-50 {
                    background: #fef2f2 !important;
                    border-color: #fca5a5;
                }

                .order-items-table tbody tr[data-index].bg-green-50 {
                    background: #f0fdf4 !important;
                    border-color: #86efac;
                }

                /* ── 4. Every cell: flex column — label on top, value below ── */
                .order-items-table td {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    border: none;
                    padding: 0.45rem 0 0.1rem;
                    font-size: 0.78rem;
                    color: #111827;
                    position: relative;
                    overflow: visible;
                    min-width: 0;
                }

                /* ── 5. Label above value (via ::before + data-label) ── */
                .order-items-table td[data-label]::before {
                    display: block;
                    content: attr(data-label);
                    font-size: 0.6rem;
                    font-weight: 700;
                    color: #9ca3af;
                    text-transform: uppercase;
                    letter-spacing: 0.07em;
                    text-align: left;
                    margin-bottom: 0.2rem;
                    line-height: 1;
                    white-space: nowrap;
                }

                /* ── 6. Cells that always span both columns ── */
                .order-items-table td.td-checkbox,
                .order-items-table td[data-field="sku"],
                .order-items-table td[data-field="item_description"],
                .order-items-table td[data-field="amount"],
                .order-items-table td.remark-cell {
                    grid-column: span 2;
                }

                /* ── 7. Checkbox header row ── */
                .order-items-table td.td-checkbox {
                    flex-direction: row;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0 0 0.5rem;
                    margin-bottom: 0.1rem;
                    border-bottom: 1px solid #f3f4f6;
                }

                .order-items-table td.td-checkbox::before {
                    display: none;
                }

                /* Item-type badge (from data-item-type on tr) */
                tr[data-index][data-item-type="FREEBIE"] td.td-checkbox::after {
                    content: "FREEBIE";
                    font-size: 0.6rem;
                    font-weight: 700;
                    letter-spacing: 0.05em;
                    padding: 0.15rem 0.5rem;
                    border-radius: 9999px;
                    background: #dcfce7;
                    color: #166534;
                }

                tr[data-index][data-item-type="DISCOUNT"] td.td-checkbox::after {
                    content: "DISCOUNT";
                    font-size: 0.6rem;
                    font-weight: 700;
                    letter-spacing: 0.05em;
                    padding: 0.15rem 0.5rem;
                    border-radius: 9999px;
                    background: #dbeafe;
                    color: #1e40af;
                }

                tr[data-index][data-item-type="MAIN"] td.td-checkbox::after {
                    content: "MAIN ITEM";
                    font-size: 0.6rem;
                    font-weight: 700;
                    letter-spacing: 0.05em;
                    padding: 0.15rem 0.5rem;
                    border-radius: 9999px;
                    background: #f3f4f6;
                    color: #374151;
                }

                /* ── 8. Section separators ── */
                /* Before pricing */
                .order-items-table td[data-field="scheme"] {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                /* Price/PC sits next to Scheme — share the same separator row */
                .order-items-table td[data-field="price_per_pc"] {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                /* Before quantities */
                .order-items-table td[data-field="qty_per_pc"] {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                .order-items-table td[data-field="qty_per_cs"] {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                /* Before footer */
                .order-items-table td.remark-cell {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                .order-items-table td[data-field="store_order_no"] {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                .order-items-table td[data-label="Status"] {
                    border-top: 1px solid #f0f0f0;
                    padding-top: 0.55rem;
                    margin-top: 0.25rem;
                }

                /* ── 9. Amount row — highlighted total ── */
                .order-items-table td[data-field="amount"] {
                    background: #eff6ff;
                    border: 1.5px solid #bfdbfe;
                    border-radius: 0.5rem;
                    padding: 0.45rem 0.65rem 0.5rem;
                    margin-top: 0.35rem;
                    font-size: 1rem;
                    font-weight: 700;
                    color: #1d4ed8;
                }

                .order-items-table td[data-field="amount"]::before {
                    color: #3b82f6;
                    font-size: 0.6rem;
                }

                /* ── 10. Editable cells look like inputs ── */
                .order-items-table td[contenteditable="true"] {
                    border: 1.5px solid #e5e7eb !important;
                    border-radius: 0.4rem;
                    background: #f9fafb;
                    padding: 0.35rem 0.5rem !important;
                    cursor: text;
                    width: 100%;
                    box-sizing: border-box;
                    min-height: 2.25rem;
                    transition: border-color 0.15s, box-shadow 0.15s;
                    /* label sits INSIDE the bordered box */
                }

                .order-items-table td[contenteditable="true"]:focus {
                    border-color: #3b82f6 !important;
                    background: #fff;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
                }

                /* Non-editable value cells: slightly dimmer */
                .order-items-table td[contenteditable="false"][data-label]:not(.td-checkbox) {
                    color: #374151;
                }

                /* ── 11. Remarks select — full width, native mobile arrow ── */
                .order-items-table td.remark-cell .relative {
                    width: 100%;
                }

                .order-items-table td.remark-cell select {
                    width: 100%;
                    border: 1.5px solid #e5e7eb;
                    border-radius: 0.4rem;
                    padding: 0.35rem 0.5rem;
                    background: #f9fafb;
                    font-size: 0.78rem;
                    color: #111827;
                    -webkit-appearance: auto;
                    appearance: auto;
                    background-image: none !important;
                    cursor: pointer;
                    min-height: 2.25rem;
                }

                .order-items-table td.remark-cell select:focus {
                    border-color: #3b82f6;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
                }

                /* ── 12. Search results: anchor below the cell ── */
                .order-items-table td[data-field="sku"] .search-results,
                .order-items-table td[data-field="item_description"] .search-results {
                    top: 100%;
                    left: 0;
                    min-width: 100%;
                    max-width: 100%;
                    white-space: normal;
                }

                /* ── 13. Status tooltip: flip above the badge ── */
                .order-items-table td[data-label="Status"] .peer~div {
                    right: auto !important;
                    left: 0 !important;
                    top: auto !important;
                    bottom: 115% !important;
                    transform: none !important;
                    margin-right: 0 !important;
                }

                /* ── 14. Hide scroll hint — table is cards on mobile ── */
                .table-scroll-hint {
                    display: none !important;
                }

            }

            /* end @media */
        </style>
    @endif
@endsection
