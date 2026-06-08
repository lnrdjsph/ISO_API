{{-- ═══ 06 · STATUS REFERENCE ═══ --}}
<x-guide.section id="statuses" number="06" title="Status Reference"
    roles="personnel manager admin whmanager whpersonnel">

    {{-- Order statuses — personnel & admin (full set) --}}
    <div data-roles="personnel admin">
        <h3 class="text-sm font-semibold text-gray-900">Order Statuses</h3>
        <x-guide.status-table :rows="[
            ['dot' => 'bg-blue-500',   'status' => 'New Order',    'cells' => ['Order submitted, not yet sent for approval', 'Review details, then Request For Approval']],
            ['dot' => 'bg-purple-500', 'status' => 'For Approval', 'cells' => ['Sent to manager for review', 'Wait for the manager to approve or reject']],
            ['dot' => 'bg-green-500',  'status' => 'Approved',     'cells' => ['Manager approved the order', 'Generate SO# to begin fulfilment']],
            ['dot' => 'bg-teal-500',   'status' => 'Completed',    'cells' => ['Order fully fulfilled and received', 'No further action needed']],
            ['dot' => 'bg-red-500',    'status' => 'Rejected',     'cells' => ['Manager rejected — check Order Notes', 'Revise and resubmit for approval']],
            ['dot' => 'bg-gray-400',   'status' => 'Cancelled',    'cells' => ['Order was cancelled', 'Can be restored if needed']],
        ]" />
    </div>

    {{-- Order statuses — manager (3) --}}
    <div data-roles="manager">
        <h3 class="text-sm font-semibold text-gray-900">Order Statuses</h3>
        <p class="mt-1 text-sm text-gray-500">As a manager you see orders in three statuses:</p>
        <x-guide.status-table :headers="['Status', 'Meaning', 'Your Action']" :rows="[
            ['dot' => 'bg-purple-500', 'status' => 'For Approval', 'cells' => ['Submitted for your review and waiting on a decision.', 'Review, then approve if correct or reject with a reason.']],
            ['dot' => 'bg-green-500',  'status' => 'Approved',     'cells' => ['You approved it. Personnel can now fulfil.', 'No action needed — personnel Generate SO# and track shipments.']],
            ['dot' => 'bg-red-500',    'status' => 'Rejected',     'cells' => ['You sent it back; the set-aside stock is returned.', 'Staff see your notes, fix the order, and may resubmit.']],
        ]" />
    </div>

    {{-- Order statuses — warehouse (2) --}}
    <div data-roles="whmanager whpersonnel">
        <h3 class="text-sm font-semibold text-gray-900">Order Statuses</h3>
        <p class="mt-1 text-sm text-gray-500">Warehouse accounts see orders only after approval:</p>
        <x-guide.status-table :headers="['Status', 'Meaning', 'Your Focus']" :rows="[
            ['dot' => 'bg-green-500', 'status' => 'Approved',  'cells' => ['Approved and now in fulfilment.', 'Track item transfer statuses as picking and shipping progress.']],
            ['dot' => 'bg-teal-500',  'status' => 'Completed', 'cells' => ['Fully fulfilled and received by the store.', 'No further action — archive of completed work.']],
        ]" />
    </div>

    {{-- Item transfer statuses — everyone --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">Item Delivery Stages</h3>
    <p class="mt-1 text-sm text-gray-500">Once an approved order is sent to the warehouse, each item moves through these stages:</p>
    <x-guide.status-table :headers="['Stage', 'What it means']" :rows="[
        ['status' => 'N/A',              'cells' => ['Not sent to the warehouse yet']],
        ['status' => 'Processing',       'cells' => ['Being prepared for picking at the warehouse']],
        ['status' => 'Picking',          'cells' => ['Staff are gathering the items at the warehouse']],
        ['status' => 'Shipped',          'cells' => ['On the way to the store']],
        ['status' => 'Received',         'cells' => ['The store has confirmed the items arrived']],
        ['status' => 'Error / Not Found','cells' => ['Something went wrong with tracking — contact IT support']],
    ]" />
</x-guide.section>
