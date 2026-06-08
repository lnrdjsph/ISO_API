{{-- ═══ 04 · ORDER DETAILS ═══ --}}
<x-guide.section id="order-details" number="04" title="Order Details"
    roles="personnel manager admin whmanager whpersonnel">

    <p class="text-sm text-gray-600">View, edit, and act on an individual order. Opens when you click any row in the Orders List.</p>

    <div data-roles="whmanager whpersonnel">
        <x-guide.callout type="info" title="Read-only for warehouse:">
            You can view every panel and track item fulfilment, but all fields are locked and the Order Actions menu is
            hidden. Your job here is to <strong>monitor</strong> progress, not edit the order.
        </x-guide.callout>
    </div>

    {{-- Info panels --}}
    <h3 id="od-panels" class="mt-5 text-sm font-semibold text-gray-900">Information Panels</h3>
    <x-guide.screenshot src="od-info-panels.png" height="h-48" caption="Customer, Payment, Delivery, and Order panels" />

    <div class="mt-4 space-y-4">
        <div>
            <h4 class="text-sm font-medium text-gray-800">Customer Information</h4>
            <div class="space-y-1">
                <x-guide.field name="MBC Card No.">The customer's 16-digit loyalty card used to identify them.</x-guide.field>
                <x-guide.field name="Customer Name">Full name of the customer who placed the order.</x-guide.field>
                <x-guide.field name="Contact Number">Phone number for delivery coordination or follow-up.</x-guide.field>
                <x-guide.field name="Email">Email address for order notifications.</x-guide.field>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-800">Payment Information</h4>
            <div class="space-y-1">
                <x-guide.field name="Payment Center">Store where payment is processed, auto-assigned from the requesting store.</x-guide.field>
                <x-guide.field name="Mode of Payment">PO15% (purchase order terms) or Cash / Bank Card.</x-guide.field>
                <x-guide.field name="Payment Date">Expected date of payment from the customer.</x-guide.field>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-800">Delivery / Dispatch</h4>
            <div class="space-y-1">
                <x-guide.field name="Delivery / Pick-up Date">When the customer receives or collects the order.</x-guide.field>
                <x-guide.field name="Mode of Dispatching">Customer Pick-up, Pick-up at Warehouse, or Delivery Direct to Customer.</x-guide.field>
                <x-guide.field name="Address / Landmark">Delivery location. Shown only for direct delivery orders.</x-guide.field>
            </div>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-800">Order Information</h4>
            <div class="space-y-1">
                <x-guide.field name="SOF Order ID">System-generated order number (e.g. SOF202603-001). Read-only.</x-guide.field>
                <x-guide.field name="Requesting Store">Store that created the order. Read-only.</x-guide.field>
                <x-guide.field name="Requested By">Store personnel who created the order. Read-only.</x-guide.field>
                <x-guide.field name="Channel of Order">Sales channel (E-Commerce, Store, ISO Retail, or Wholesale).</x-guide.field>
                <x-guide.field name="Serving Warehouse">The warehouse handling the order. Can't be edited.</x-guide.field>
                <x-guide.field name="Order Status">The order's current stage. Can't be edited directly — it changes through the Order Actions menu.</x-guide.field>
            </div>
        </div>
    </div>
    <p class="mt-3 text-sm text-gray-500" data-roles="personnel manager admin">
        Shaded fields can't be edited. When you change an editable field it turns <strong>yellow</strong> — click
        <strong>Update</strong> to save.
    </p>

    {{-- Order actions --}}
    <div id="od-actions" class="mt-6 border-t border-gray-100 pt-5" data-roles="personnel manager admin">
        <h3 class="text-sm font-semibold text-gray-900">Order Actions Menu</h3>
        <p class="mt-1 text-sm text-gray-500">The <strong>Order Actions</strong> dropdown (top-right, above the panels) changes the order's status. Available actions depend on your role and the current status.</p>

        <div data-roles="manager">
            <div class="mt-4 rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4">
                <h4 class="mb-2 text-sm font-semibold text-blue-900">Where to find it</h4>
                <p class="mb-3 text-sm text-blue-800">Look for the <strong>Order Actions</strong> button in the top-right corner. For managers it offers <strong>Approve Order</strong> and <strong>Reject Order</strong>.</p>
                <x-guide.screenshot src="manager-order-actions.png" height="h-auto" caption="Manager Order Actions — Approve & Reject" />
            </div>
        </div>

        <div data-roles="personnel" class="mt-4">
            <h4 class="mb-2 text-sm font-medium text-gray-800">Personnel Actions</h4>
            <div class="space-y-2">
                @foreach ([
                    ['Request For Approval', 'Sends the order to your manager for review (they get an email). The order moves to "For Approval." If it was rejected before, the stock is set aside again.'],
                    ['Generate SO#', 'Becomes available once the order is approved. Creates the warehouse reference numbers so the warehouse can start preparing the items.'],
                    ['Cancel Order', 'Cancels the whole order — you\'ll be asked for a reason. The set-aside stock is returned and the reason is saved in Order Notes.'],
                    ['Complete Order', 'Marks the order finished once all items have been received. The order moves to "Completed."'],
                    ['Restore Order', 'Re-opens a cancelled order back to "New Order" so it can go for approval again.'],
                ] as $a)
                    <div class="rounded-lg bg-gray-50 px-4 py-2.5">
                        <p class="text-sm font-medium text-gray-800">{{ $a[0] }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $a[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div data-roles="manager" class="mt-4">
            <h4 class="mb-2 text-sm font-medium text-gray-800">Manager Actions</h4>
            <div class="space-y-2">
                @foreach ([
                    ['Approve Order', 'Approves the order after your review. You must attach a supporting document (PDF, Word, or image — up to 5MB). The person who submitted it gets an email, and the order moves to "Approved."'],
                    ['Reject Order', 'Sends the order back with a reason (required). The reason is saved in Order Notes, the set-aside stock is returned, and the staff member can fix it and resubmit.'],
                    ['Cancel Order', 'Cancels the whole order — a reason is required. The set-aside stock is returned (unless it was already rejected).'],
                    ['Complete Order', 'Marks the order finished.'],
                    ['Restore Order', 'Re-opens a cancelled order back to "New Order."'],
                ] as $a)
                    <div class="rounded-lg bg-gray-50 px-4 py-2.5">
                        <p class="text-sm font-medium text-gray-800">{{ $a[0] }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $a[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Warehouse: fulfilment monitoring lives in its own section --}}
    <div class="mt-6 border-t border-gray-100 pt-5" data-roles="whmanager whpersonnel">
        <h3 class="text-sm font-semibold text-gray-900">Watching an Order</h3>
        <p class="mt-1 text-sm text-gray-500">
            Open an approved order and watch the Ordered Items table — each line shows a delivery stage that moves from
            Processing to Picking, Shipped, and Received. See <strong>Fulfilment Tracking</strong> below for the full
            flow.
        </p>
        <x-guide.screenshot src="od-warehouse-items.png" placeholder
            caption="Ordered Items table with each item's delivery stage (view-only for warehouse)" />
    </div>

    {{-- Invoice / print / notes --}}
    <div id="od-invoice" class="mt-6 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-900">Invoice, Printing, Comments &amp; Notes</h3>
        <p class="mt-1 text-sm text-gray-500">The Invoice sidebar recalculates live as items change. Print buttons generate downloadable PDFs:</p>
        <div class="mt-2 space-y-1">
            <x-guide.field name="Print SOF">The full Sales Order Form as a landscape A4 PDF.</x-guide.field>
            <x-guide.field name="Print Invoice">The invoice with totals as a portrait A4 PDF.</x-guide.field>
            <x-guide.field name="Print Freebies Form">Main products paired with their freebies (only for orders with Freebie items).</x-guide.field>
            <x-guide.field name="Print Order Slip">An order slip for the warehouse and dispatch team (free-item-only lines are left off).</x-guide.field>
        </div>
        <p class="mt-3 text-sm text-gray-500">There are three places for notes on an order — two you write yourself, and one the system keeps for you:</p>
        <div class="mt-2 space-y-1">
            <x-guide.field name="Order Comments">A free-text box for a note about the <strong>whole order</strong> (optional, up to 1,800 characters) — for example, a special instruction. Type your note and click Update to save.</x-guide.field>
            <x-guide.field name="Item Comments">A note you can add to a <strong>single product line</strong> in the Ordered Items table — handy for line-by-line instructions. (This column used to be called "Remarks.")</x-guide.field>
            <x-guide.field name="Order Notes">An <strong>automatic</strong> history of everything that's happened to the order — each entry shows what changed, when, and who did it. You don't type here; the system fills it in, including approval and rejection reasons. It's your history and proof trail.</x-guide.field>
        </div>
        <x-guide.callout type="tip" data-roles="personnel manager admin">Order Comments and Item Comments are things you type, then save with <strong>Update</strong> (the button lights up when you change something). Order Notes is read-only — the system writes it.</x-guide.callout>
    </div>
</x-guide.section>
