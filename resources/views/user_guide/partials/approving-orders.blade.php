{{-- ═══ MANAGER · APPROVE / REJECT ═══ --}}
<x-guide.section id="approving-orders" number="★" title="Approving &amp; Rejecting Orders" roles="manager" color="green">

    <p class="mb-4 text-sm text-gray-600">Your primary workflow. Store personnel submit orders for your review — you approve or reject based on your assessment. Approval now requires your
        <strong>e-signature</strong> as a formal sign-off.
    </p>

    {{-- Approving --}}
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-green-100 bg-green-50 px-4 py-3">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-green-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
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

            <div class="mt-4 overflow-hidden rounded-lg border border-blue-200 bg-blue-50">
                <div class="border-b border-blue-100 px-4 py-3">
                    <h4 class="mb-0.5 text-sm font-semibold text-blue-900">Step 5 · Choose an approval method in the modal</h4>
                    <p class="text-xs text-blue-700">The SOF PDF is the approval document. Pick one of two methods to sign it off.</p>
                </div>
                <div class="space-y-5 p-4">
                    <x-guide.screenshot src="manager-approve-modal.png" height="h-auto" caption="Approve Order modal — choose Upload signed SOF or Sign digitally" />

                    {{-- Mode A --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="mb-1 flex items-center gap-2 text-sm font-semibold text-gray-800">
                            <span class="text-lg">📄</span> Mode A · Upload signed SOF
                        </p>
                        <p class="mb-2 text-xs text-gray-600">Use this if you have already printed, signed, and scanned the SOF PDF.</p>
                        <x-guide.steps color="blue" :items="[
                            'Select <strong>Upload signed SOF</strong>.',
                            'Click the upload area (or drag and drop) to attach the signed PDF — max 10 MB, PDF only.',
                            'Click <strong>Approve</strong> to submit.',
                        ]" />
                    </div>

                    {{-- Mode B --}}
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50/40 p-4">
                        <p class="mb-1 flex items-center gap-2 text-sm font-semibold text-indigo-800">
                            <span class="text-lg">✍️</span> Mode B · Sign digitally
                        </p>
                        <p class="mb-2 text-xs text-indigo-700">Your signature is embedded directly above the <strong>Approved by</strong> line in the SOF PDF — no printing required.</p>
                        <x-guide.steps color="indigo" :items="[
                            'Select <strong>Sign digitally</strong>.',
                            'Provide your signature using one of the two sub-options below.',
                            'Click <strong>Approve</strong> to embed the signature and submit.',
                        ]" />

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg bg-white p-3 ring-1 ring-indigo-100">
                                <p class="mb-1 text-xs font-semibold text-indigo-700">✏️ Draw</p>
                                <p class="text-xs text-gray-600">Use your mouse or finger on the canvas to draw your signature. Click <strong>Clear</strong> to erase and redo.</p>
                            </div>
                            <div class="rounded-lg bg-white p-3 ring-1 ring-indigo-100">
                                <p class="mb-1 text-xs font-semibold text-indigo-700">🖼️ Upload image</p>
                                <p class="text-xs text-gray-600">Click or drop a PNG/JPG signature image. A transparent background is recommended for best results.</p>
                            </div>
                        </div>

                        <x-guide.screenshot src="manager-esignature-pad.png" :placeholder="false" height="h-48" caption="Digital signature pad — Draw tab active" />
                    </div>

                    <x-guide.callout type="info" title="Required:">You must select a mode and complete the required input before the <strong>Approve</strong> button
                        activates.</x-guide.callout>
                </div>
            </div>

            <x-guide.callout type="success">After approving, the requester is emailed that their order is approved and they can Generate SO# to begin fulfilment.</x-guide.callout>
        </div>
    </div>

    {{-- Rejecting --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-red-100 bg-red-50 px-4 py-3">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-red-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
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

            <x-guide.callout type="warning" title="Best practice:">Give a specific, actionable reason so personnel know exactly what to fix. They can see your notes and resubmit for
                approval.</x-guide.callout>
        </div>
    </div>
</x-guide.section>
