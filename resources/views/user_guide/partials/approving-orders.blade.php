{{-- ═══ MANAGER · APPROVE / REJECT ═══ --}}
<x-guide.section id="approving-orders" number="★" title="Approving &amp; Rejecting Orders" roles="manager" color="green">

    <p class="mb-4 text-sm text-gray-600">Your primary workflow. Store personnel submit orders for your review — you approve or reject based on your assessment.</p>

    {{-- Approving --}}
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-green-100 bg-green-50 px-4 py-3">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-green-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                Approving an Order
            </h3>
        </div>
        <div class="p-4">
            <x-guide.steps color="green" :items="[
                'Open an order with <strong>For Approval</strong> status (from the Orders List or an email link).',
                'Check every panel: Customer Info, Payment, Delivery, and the Ordered Items table.',
                'Make sure the prices, quantities, deals, and totals on the right all look correct.',
                'Click <strong>Order Actions → Approve Order</strong>.',
            ]" />

            <div class="mt-4 rounded-lg bg-blue-50 p-4">
                <h4 class="mb-2 text-sm font-semibold text-blue-900">Step 5 · Attach a supporting document</h4>
                <p class="mb-3 text-sm text-blue-800">When you click "Approve Order" a pop-up appears. <strong>You must attach a document</strong> (PDF, Word, or image — up to 5MB).</p>
                <x-guide.screenshot src="manager-approve-modal.png" height="h-auto" caption="Approve Order modal — the document upload is marked required (*)" />
            </div>

            <x-guide.callout type="success">After you approve, the requester is emailed that their order is approved, and they can then Generate SO# to begin fulfilment.</x-guide.callout>
        </div>
    </div>

    {{-- Rejecting --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-red-100 bg-red-50 px-4 py-3">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-red-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Rejecting an Order
            </h3>
        </div>
        <div class="p-4">
            <x-guide.steps color="red" :items="[
                'Open an order with <strong>For Approval</strong> status.',
                'Identify the issue (e.g. incorrect pricing, unreasonable quantity, wrong customer details).',
                'Click <strong>Order Actions → Reject Order</strong>.',
            ]" />

            <div class="mt-4 rounded-lg bg-amber-50 p-4">
                <h4 class="mb-2 text-sm font-semibold text-amber-900">Step 4 · Give a reason</h4>
                <p class="mb-3 text-sm text-amber-800">The reject pop-up needs a clear reason — you can't skip it.</p>
                <x-guide.screenshot src="manager-reject-modal.png" height="h-auto" caption="Reject Order modal — the rejection reason is required" />
            </div>

            <x-guide.steps color="red" :start="3" :items="[
                'Provide a <strong>clear rejection reason</strong> — this field is required.',
                'The order becomes <strong>Rejected</strong>, the set-aside stock is returned, and your reason is saved in Order Notes.',
            ]" />

            <x-guide.callout type="warning" title="Best practice:">Give a specific, actionable reason so personnel know exactly what to fix. They can see your notes and resubmit for approval.</x-guide.callout>
        </div>
    </div>
</x-guide.section>
