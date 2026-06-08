{{-- ═══ 07 · PERSONNEL WORKFLOWS ═══ --}}
<x-guide.section id="workflows" number="07" title="Common Workflows" roles="personnel">

    @php
        $flows = [
            ['Creating a New Order', [
                'Transactions → Sales Order Form',
                'Complete all header fields (Request Details, Customer Info, Payment, Dispatch)',
                'Add products with Sale Type, quantities, and schemes',
                'Click Submit Order',
                'Open the order from the Orders List → Request For Approval',
            ]],
            ['After Manager Approval', [
                'Open the approved order from the Orders List',
                'Click Generate SO# so the warehouse can start preparing the items',
                'Track item fulfilment: Picking → Shipped → Received',
                'Once all items are received, Complete Order',
            ]],
            ['Editing an Order', [
                'Open the order from the Orders List',
                'Edit editable fields (a yellow highlight appears on changes)',
                'To cancel specific items: tick their boxes → Cancel Selected Items',
                'Click Update to save all changes',
            ]],
        ];
    @endphp

    <div class="space-y-6">
        @foreach ($flows as $i => $wf)
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ $wf[0] }}</h3>
                <x-guide.steps :items="$wf[1]" />
            </div>
            @if ($i < count($flows) - 1)
                <hr class="border-gray-100">
            @endif
        @endforeach
    </div>
</x-guide.section>
