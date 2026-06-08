{{-- ═══ 03 · ORDERS LIST ═══ --}}
<x-guide.section id="orders-list" number="03" title="Orders List"
    roles="personnel manager admin whmanager whpersonnel">

    <p class="text-sm text-gray-600">Track all submitted sales orders with filters and search. Found under <strong>Transactions → Sales Order List</strong>.</p>

    <div data-roles="manager">
        <x-guide.callout type="info" title="Manager view:">
            You see only orders from stores in your assigned region. The Status filter offers three options —
            <strong>For Approval</strong>, <strong>Approved</strong>, and <strong>Rejected</strong> — and the Store
            dropdown lists only your region's stores.
        </x-guide.callout>
    </div>
    <div data-roles="whmanager whpersonnel">
        <x-guide.callout type="info" title="Warehouse view:">
            You see only <strong>Approved</strong> and <strong>Completed</strong> orders for the stores your warehouse
            serves. The list is read-only — open any order to monitor its fulfilment progress.
        </x-guide.callout>
    </div>
    <div data-roles="admin">
        <x-guide.callout type="note">Super Admins see orders from <strong>all stores</strong>, with every status and a full store dropdown.</x-guide.callout>
    </div>

    <h3 id="orders-filters" class="mt-5 text-sm font-semibold text-gray-900">Filtering &amp; Searching</h3>
    <x-guide.screenshot src="orders-filter-bar.png" height="h-64" caption="Filter by keyword, store, channel, status, and date range" />
    <p class="mt-2 text-sm text-gray-500">
        Narrow results by <strong>keyword</strong> (customer name, SOF ID, store code), <strong>store</strong>,
        <strong>channel</strong>, <strong>status</strong>, or <strong>date range</strong>. Click <strong>Apply</strong>
        to filter or <strong>Reset</strong> to clear. Results update instantly — the page doesn't reload.
    </p>

    <div id="orders-table" class="mt-5 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-900">Order Table</h3>
        <div data-roles="personnel admin">
            <x-guide.screenshot src="orders-table-personnel.png" caption="Orders with status badges and channel indicators" />
        </div>
        <div data-roles="manager">
            <x-guide.screenshot src="orders-table-manager.png" caption="Manager order table — region-scoped, three statuses" />
        </div>
        <div data-roles="whmanager whpersonnel">
            <x-guide.screenshot src="orders-table-warehouse.png" placeholder
                caption="Warehouse order table — approved &amp; completed orders for your warehouse's stores" />
        </div>
        <p class="mt-2 text-sm text-gray-500">
            Each row shows the SOF ID, Customer Name, Store, Channel, Status (color-coded badge), and key dates. Click
            any row to open the Order Details page.
        </p>
    </div>
</x-guide.section>
