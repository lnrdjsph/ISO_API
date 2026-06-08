{{-- ═══ 01 · DASHBOARD ═══ --}}
<x-guide.section id="dashboard" number="01" title="Dashboard"
    roles="personnel manager admin whmanager whpersonnel">

    <p class="text-sm leading-relaxed text-gray-600">
        Your home base. The Dashboard opens the moment you sign in and shows, at a glance, how your orders are doing,
        the money involved, and anything that needs your attention. <strong>The panels you see — and even which
            summary cards appear — depend on your role.</strong> The overview below shows exactly what's on
        <em>your</em> dashboard.
    </p>

    {{-- ── Role screenshots ── --}}
    <div data-roles="personnel">
        <x-guide.screenshot src="dashboard-personnel.png" height="h-auto"
            caption="Store Personnel Dashboard — Summary cards, Quick Actions, Attention Required, Inventory Snapshot, Recent Orders" />
    </div>
    <div data-roles="manager">
        <x-guide.screenshot src="dashboard-manager.png" height="h-auto"
            caption="Manager Dashboard — region-scoped Summary cards, Attention Required, and Recent Orders" />
    </div>
    <div data-roles="admin">
        <x-guide.screenshot src="dashboard-full.png" height="h-auto"
            caption="Super Admin Dashboard — Summary cards, revenue strip, Store Performance, Activity Feed" />
    </div>
    <div data-roles="whmanager whpersonnel">
        <x-guide.screenshot src="dashboard-warehouse.png" height="h-auto" placeholder
            caption="Warehouse Dashboard — 3 Summary cards (Total / Approved / Completed) for the stores your warehouse serves" />
    </div>

    {{-- ── Per-role layout overview ── --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">What's on Your Dashboard</h3>
    <p class="mt-1 text-sm text-gray-500">Panels appear top-to-bottom in this order:</p>
    @php
        $layouts = [
            'personnel' => ['Time Range filter', 'Summary cards (5)', 'Quick Actions', 'Attention Required', 'Inventory Snapshot', 'Recent Orders'],
            'manager' => ['Time Range filter', 'Summary cards (5)', 'Quick Actions', 'Attention Required', 'Recent Orders'],
            'admin' => ['Time Range filter', 'Summary cards (5)', 'Revenue strip', 'Quick Actions', 'Store Performance', 'Recent Orders', 'Activity Feed', 'Cancelled / Rejected alert'],
            'whmanager' => ['Time Range filter', 'Summary cards (3)', 'Quick Actions', 'Recent Orders'],
        ];
        $layouts['whpersonnel'] = $layouts['whmanager'];
    @endphp
    @foreach ($layouts as $token => $panels)
        <div data-roles="{{ $token }}">
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($panels as $p)
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $p }}</span>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- ── Time range filter ── --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">Time Range Filter</h3>
    <p class="mt-1 text-sm text-gray-500">
        Every dashboard opens with a date filter — <strong>Today</strong>, <strong>This Month</strong>,
        <strong>Last 30 Days</strong>, <strong>This Year</strong>, or <strong>All Time</strong>. Picking one changes
        the date range used by the cards and totals; the range you chose is shown next to the page title.
    </p>

    {{-- ── Summary cards ── --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">Summary Cards</h3>
    <p class="mt-1 text-sm text-gray-500">
        The top row shows live order counts. Each card displays the <strong>count</strong>, the
        <strong>peso value</strong> of those orders, and what <strong>% of total</strong> they make up. The set of
        cards differs by role:
    </p>
    @php
        $dot = fn(
            $c,
            $l,
        ) => "<span class='inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700'><span class='$c inline-block h-2 w-2 rounded-full'></span>$l</span>";
    @endphp
    <div data-roles="personnel">
        <x-guide.table :headers="['Card', 'Shows']" :rows="[
            [$dot('bg-gray-400', 'Total Orders'), 'Every order you have created, any status.'],
            [$dot('bg-blue-500', 'New Orders'), 'Submitted but not yet sent for approval.'],
            [$dot('bg-purple-500', 'For Approval'), 'Sent to your manager, waiting for a decision.'],
            [$dot('bg-green-500', 'Approved'), 'Approved — ready to send to the warehouse.'],
            [$dot('bg-teal-500', 'Completed'), 'Done — fully delivered and received.'],
        ]" />
    </div>
    <div data-roles="manager admin">
        <x-guide.table :headers="['Card', 'Shows']" :rows="[
            [$dot('bg-gray-400', 'Total Orders'), 'All the orders you can see.'],
            [$dot('bg-purple-500', 'For Approval'), 'Waiting for the manager to review.'],
            [$dot('bg-green-500', 'Approved'), 'Approved orders.'],
            [$dot('bg-teal-500', 'Completed'), 'Orders that are done.'],
            [$dot('bg-orange-500', 'Rejected'), 'Sent back to be fixed.'],
        ]" />
        <div data-roles="admin">
            <x-guide.callout type="note" title="Super Admin extras:">
                Below the summary cards is a second strip — <strong>Today</strong>, <strong>This Week</strong>,
                <strong>All-time Revenue</strong>, <strong>MTD Revenue</strong>, and <strong>MTD Freebies Value</strong>.
            </x-guide.callout>
        </div>
    </div>
    <div data-roles="whmanager whpersonnel">
        <x-guide.table :headers="['Card', 'Shows']" :rows="[
            [$dot('bg-gray-400', 'Total Orders'), 'Approved and completed orders for the stores your warehouse serves.'],
            [$dot('bg-green-500', 'Approved'), 'Approved and now being prepared.'],
            [$dot('bg-teal-500', 'Completed'), 'Delivered and received by the store.'],
        ]" />
        <x-guide.callout type="info" title="View-only:">
            There's no New Order or For Approval card — those steps happen before the order reaches the warehouse.
        </x-guide.callout>
    </div>

    {{-- ── Quick Actions ── --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">Quick Actions</h3>
    <p class="mt-1 text-sm text-gray-500">Shortcut buttons to the areas you use most. Your set:</p>
    <div data-roles="personnel">
        <p class="mt-2 text-sm text-gray-600">New Sales Order · Sales Orders · Products · Sales Overview · Orders Report · Payments</p>
    </div>
    <div data-roles="manager whmanager whpersonnel">
        <p class="mt-2 text-sm text-gray-600">Sales Orders · Sales Overview · Orders Report · Payments</p>
    </div>
    <div data-roles="admin">
        <p class="mt-2 text-sm text-gray-600">Sales Orders · Products · Sales Overview · Orders Report · Payments · User Management · Settings</p>
    </div>

    {{-- ── Attention Required (personnel + manager only) ── --}}
    <div class="mt-6 border-t border-gray-100 pt-5" data-roles="personnel manager">
        <h3 class="text-sm font-semibold text-gray-900">Attention Required</h3>
        <p class="mt-1 text-sm text-gray-500">
            Shows the orders that need your action as cards you can click, each with a one-line "what to do next." Only
            statuses that actually have orders show up. (Super admins and warehouse accounts don't see this panel.)
        </p>

        <div class="mt-3" data-roles="personnel">
            <x-guide.table :headers="['Card', 'Means']" :rows="[
                ['New Orders', 'Submitted orders not yet sent for approval.'],
                ['Approved', 'Approved — send to the warehouse, keep an eye on it, then mark Complete.'],
                ['Rejected', 'Read the reason, then fix and resubmit.'],
            ]" />
        </div>
        <div class="mt-3" data-roles="manager">
            <x-guide.table :headers="['Card', 'Means']" :rows="[['For Approval', 'Orders waiting on your decision — click to review and act.']]" />
        </div>
    </div>

    {{-- ── Store Performance (admin only) ── --}}
    <div class="mt-6 border-t border-gray-100 pt-5" data-roles="admin">
        <h3 class="text-sm font-semibold text-gray-900">Store Performance</h3>
        <p class="mt-1 text-sm text-gray-500">Ranks the top stores by all-time order volume so you can see where activity is concentrated. Super-admin only.</p>
    </div>

    {{-- ── Inventory Snapshot (store personnel only) ── --}}
    <div class="mt-6 border-t border-gray-100 pt-5" data-roles="personnel">
        <h3 class="text-sm font-semibold text-gray-900">Inventory Snapshot</h3>
        <p class="mt-1 text-sm text-gray-500">
            A quick stock summary for your store: how many products are <strong>In Stock</strong>,
            <strong>Low Stock</strong>, and <strong>Out of Stock</strong>, with the warehouse name and when stock was
            last updated.
        </p>
        {{-- <x-guide.screenshot src="dashboard-inventory-snapshot.png" height="h-48" placeholder
            caption="Inventory Snapshot — stock summary and when it was last updated" /> --}}
    </div>

    {{-- ── Recent Orders & Activity ── --}}
    <div class="mt-6 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-900">Recent Orders</h3>
        <p class="mt-1 text-sm text-gray-500">
            The latest orders you can see. Click any row to open its details.
            <span data-roles="manager admin">A <strong>Store</strong> column is included for managers and super admins.</span>
        </p>
        <div data-roles="admin">
            <h3 class="mt-5 text-sm font-semibold text-gray-900">Activity Feed</h3>
            <p class="mt-1 text-sm text-gray-500">Super-admin only — a running list of the latest changes across the system (approvals, edits, cancellations), each linking to its order.</p>
        </div>
    </div>

    {{-- ── Scoping notes ── --}}
    <div data-roles="manager">
        <h3 class="mt-6 text-sm font-semibold text-gray-900">Region Assignment</h3>
        <p class="mt-1 text-sm text-gray-500">Your manager account is tied to a region. You only see orders from stores within it:</p>
        <x-guide.table :headers="['Region', 'Stores']" :rows="[
            ['Visayas (VS)', 'F2 – Metro Wholesalemart Colon (4002), S10 – Metro Maasin (2010), S17 – Metro Tacloban (2017), S19 – Metro Bay-Bay (2019), F18 – Metro Alang-Alang (3018)'],
            ['Luzon (LZ)', 'H8 – Super Metro Antipolo (6012), F19 – Metro Hilongos (3019), S8 – Metro Toledo (2008), H9 – Super Metro Carcar (6009), H10 – Super Metro Bogo (6010)'],
        ]" />
    </div>
    <div data-roles="whmanager whpersonnel">
        <x-guide.callout type="info" title="Warehouse scope:">
            You see orders from every store served by your assigned warehouse — not just one store. Your dashboard,
            order list, and order details are all limited to that warehouse's stores.
        </x-guide.callout>
    </div>
    <div data-roles="admin">
        <x-guide.callout type="note" title="Super Admin:">
            Full visibility across all stores, plus a Cancelled / Rejected alert whenever any exist. Your sidebar also
            includes <strong>Products</strong> and the <strong>Admin</strong> (Control) menu.
        </x-guide.callout>
    </div>

    <x-guide.callout type="tip" class="mt-4">Start here each day to see what needs your attention before opening individual orders.</x-guide.callout>
</x-guide.section>
