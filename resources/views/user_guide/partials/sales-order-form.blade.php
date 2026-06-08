{{-- ═══ 02 · SALES ORDER FORM (personnel & admin) ═══ --}}
<x-guide.section id="sales-order-form" number="02" title="Sales Order Form (SOF)" roles="personnel admin">

    <p class="text-sm text-gray-600">
        Create new B2B sales orders. The form has two parts: <strong>header information</strong> and
        <strong>order items</strong>. The items section only unlocks once the header is complete.
    </p>

    {{-- Step 1 --}}
    <h3 id="sof-header" class="mt-6 text-sm font-semibold text-gray-900">Step 1 — Fill Order Information</h3>
    <x-guide.screenshot src="sof-header.png" caption="Header — Request Details, Customer Info, Payment, and Dispatch" />

    <div class="mt-4 space-y-4">
        <div>
            <h4 class="text-sm font-medium text-gray-800">Request Details</h4>
            <p class="mb-2 mt-1 text-xs text-gray-400">Fields with a shaded background are auto-filled and read-only.</p>
            <div class="space-y-1">
                <x-guide.field name="SOF Order ID">Auto-generated order number in the format <strong>SOF + Year/Month + Sequence</strong> (e.g. SOF202603-001). Read-only.</x-guide.field>
                <x-guide.field name="Requesting Store">Your assigned store (e.g. "F2 – Metro Wholesalemart Colon"). Read-only — set from your account.</x-guide.field>
                <x-guide.field name="Requested By">Your name as the logged-in user creating the order. Read-only.</x-guide.field>
                <x-guide.field name="Channel of Order">Select the sales channel: <strong>E-Commerce</strong>, <strong>Store</strong>, <strong>ISO Retail</strong>, or <strong>Wholesale</strong>.</x-guide.field>
                <x-guide.field name="Serving Warehouse">The warehouse that will fulfil this order, auto-assigned from your store location. Read-only.</x-guide.field>
                <x-guide.field name="Date &amp; Time of Order">Pre-filled with the current date and time. Adjustable for backdated orders.</x-guide.field>
            </div>
        </div>

        <div>
            <h4 class="text-sm font-medium text-gray-800">Customer Information</h4>
            <div class="space-y-1">
                <x-guide.field name="MBC Card Number">The customer's 16-digit MBC loyalty card. A valid card auto-fills name, contact, and email; otherwise enter details manually.</x-guide.field>
                <x-guide.field name="Customer Name">Full name. Auto-filled from MBC lookup, or enter manually.</x-guide.field>
                <x-guide.field name="Contact Number">Phone number. Auto-filled from MBC lookup, or enter manually.</x-guide.field>
                <x-guide.field name="Customer Email">Email address. Auto-filled from MBC lookup, or enter manually.</x-guide.field>
            </div>
            <x-guide.callout type="warning">MBC Card must be exactly <strong>16 digits</strong>. Only numbers (0–9) are accepted — letters and symbols are ignored.</x-guide.callout>
        </div>

        <div>
            <h4 class="text-sm font-medium text-gray-800">Payment Information</h4>
            <div class="space-y-1">
                <x-guide.field name="Payment Center">Auto-assigned to match your store. Read-only for store personnel.</x-guide.field>
                <x-guide.field name="Mode of Payment">Select <strong>PO15%</strong> (purchase order, 15% terms) or <strong>Cash / Bank Card</strong> (paid right away). This affects the deals — discounts and free items — offered on the items.</x-guide.field>
                <x-guide.field name="Payment Date">Pre-filled with today's date. Editable to the customer's preferred date.</x-guide.field>
            </div>
        </div>

        <div>
            <h4 class="text-sm font-medium text-gray-800">Dispatch Details</h4>
            <div class="space-y-1">
                <x-guide.field name="Delivery / Pick-up Date">Expected date for delivery or pick-up.</x-guide.field>
                <x-guide.field name="Mode of Dispatching">Choose one of three: <strong>Customer Pick-up</strong> (collected at the store), <strong>Pick-up at Warehouse</strong> (collected at the serving warehouse), or <strong>Delivery Direct to Customer</strong> (Address &amp; Landmark fields appear and are required).</x-guide.field>
                <x-guide.field name="Address">Delivery address. Appears only for "Delivery Direct to Customer".</x-guide.field>
                <x-guide.field name="Landmark">A nearby landmark to aid delivery. Appears only for "Delivery Direct to Customer".</x-guide.field>
            </div>
        </div>
    </div>

    <x-guide.callout type="danger">The Order Items section only appears after <strong>all required header fields</strong> are complete. If any required field is empty, the items section stays hidden.</x-guide.callout>

    {{-- Step 2 --}}
    <div id="sof-items" class="mt-6 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-900">Step 2 — Add Order Items</h3>
        <x-guide.screenshot src="sof-order-items.png" height="h-56"
            caption="Order Items — sale type, product search, quantities, and the live breakdown" />
        <x-guide.steps :items="[
            'Select <strong>Sale Type</strong>: <strong>Freebie</strong> (buy X get Y free — shows Scheme and Freebie Product fields) or <strong>Discount</strong> (fixed amount or percentage off — shows a Discount field).',
            'Search a product by typing at least 2 characters of the <strong>SKU</strong>, <strong>product name</strong>, or <strong>sub-department</strong>, then pick from the dropdown.',
            'Enter the number of <strong>Cases Ordered</strong>. The breakdown on the right updates as you type — Price per Case, free items, Total Cases, Freebie Amount, and Total Payable.',
        ]" />
    </div>

    {{-- Step 3 --}}
    <div id="sof-submit" class="mt-6 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-900">Step 3 — Submit</h3>
        <p class="mt-1 text-sm text-gray-500">
            Use <strong>+ Add Another Item</strong> for more products. Click <strong>Submit Order</strong> to create
            the order — the button switches to "Processing…" and disables to prevent a double submission.
        </p>
    </div>
</x-guide.section>
