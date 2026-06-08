{{-- ═══ WAREHOUSE · FULFILMENT TRACKING ═══ --}}
<x-guide.section id="warehouse-fulfillment" number="★" title="Fulfilment Tracking" roles="whmanager whpersonnel" color="teal">

    <p class="mb-4 text-sm text-gray-600">
        Warehouse accounts are <strong>view-only</strong>. You don't create, approve, or edit orders — you keep an eye
        on approved orders as they move through the warehouse and make sure everything reaches the store.
    </p>

    {{-- Flow --}}
    <h3 class="text-sm font-semibold text-gray-900">How an Order Moves</h3>
    <p class="mt-1 text-sm text-gray-500">Once an order is approved and sent to the warehouse, every item moves through these stages:</p>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium">
        @foreach ([['Processing', 'bg-gray-100 text-gray-700'], ['Picking', 'bg-amber-100 text-amber-800'], ['Shipped', 'bg-blue-100 text-blue-800'], ['Received', 'bg-green-100 text-green-800']] as $i => $stage)
            @if ($i > 0)
                <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @endif
            <span class="{{ $stage[1] }} rounded-full px-3 py-1">{{ $stage[0] }}</span>
        @endforeach
    </div>

    {{-- Capabilities --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">What You Can &amp; Can't Do</h3>
    @php
        $yes = '<span class="inline-flex items-center gap-1 text-green-700"><span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>Yes</span>';
        $no = '<span class="inline-flex items-center gap-1 text-gray-400"><span class="inline-block h-2 w-2 rounded-full bg-gray-300"></span>No</span>';
    @endphp
    <x-guide.table :headers="['Can you…', 'Warehouse']" :rows="[
        ['See approved &amp; completed orders for your warehouse', $yes],
        ['Keep track of each item\'s delivery stage', $yes],
        ['Switch between the warehouses you cover in Products', $yes],
        ['Create a new sales order', $no],
        ['Approve, reject, or edit an order', $no],
        ['Change an order\'s status', $no],
    ]" />

    {{-- Manager vs personnel --}}
    <h3 class="mt-6 text-sm font-semibold text-gray-900">Warehouse Manager vs Warehouse Personnel</h3>
    <p class="mt-1 text-sm text-gray-500">
        Both roles share the same read-only view of orders and fulfilment. The intended distinction is product
        oversight: the <strong>Warehouse Manager</strong> is the senior fulfilment contact for the warehouse, while
        <strong>Warehouse Personnel</strong> focus on day-to-day monitoring.
    </p>
</x-guide.section>
