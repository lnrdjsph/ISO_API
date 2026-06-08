{{-- Bulk Edit Modal --}}
<div id="bulk-edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="flex items-center text-2xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Bulk Edit Products
                    </h3>
                    <button id="close-bulk-edit" class="text-gray-400 transition-colors duration-200 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="mt-2 text-gray-600">Update multiple products at once. Leave fields empty to keep existing values.</p>
            </div>

            <form id="bulk-edit-form" class="space-y-6 p-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @foreach ([
                        ['case_pack',             'Case Pack',         'number', null],
                        ['srp',                   'SRP (₱)',           'number', '0.01'],
                        ['allocation_per_case',   'Allocation per Case','number', null],
                        ['cash_bank_card_scheme', 'C/BC Scheme',       'text',   null],
                        ['po15_scheme',           'PO15 Scheme',       'text',   null],
                        ['freebie_sku',           'Freebie SKU',       'text',   null],
                    ] as [$name, $label, $type, $step])
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">{{ $label }}</label>
                            <input type="{{ $type }}" name="{{ $name }}"
                                @if ($step) step="{{ $step }}" @endif
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                    <div class="text-sm text-gray-600">
                        <span id="bulk-edit-selected-count">0</span> products will be updated
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" id="cancel-bulk-edit"
                            class="rounded-xl border border-gray-300 px-6 py-3 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-indigo-700">
                            Update Products
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk Archive Confirmation Modal --}}
<div id="bulk-archive-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl">
            <div class="p-6">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <h3 class="mb-2 text-center text-xl font-bold text-gray-900">Archive Products</h3>
                <p class="mb-4 text-center text-gray-600">
                    Are you sure you want to archive
                    <span id="archive-selected-count" class="font-semibold text-red-600">0</span>
                    selected products? This action can be undone later.
                </p>

                <div class="mb-4">
                    <label for="archive-reason-input" class="mb-1 block text-sm font-medium text-gray-700">
                        Archive Reason <span class="text-[10px] text-gray-400">(optional)</span>
                    </label>
                    <textarea id="archive-reason-input"
                        class="w-full resize-none rounded-xl border px-4 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-400"
                        rows="3" maxlength="500"
                        placeholder="Enter reason for archiving (max 500 characters)..."></textarea>
                </div>

                <div class="flex space-x-3">
                    <button type="button" id="cancel-bulk-archive"
                        class="flex-1 rounded-xl border border-gray-300 px-4 py-3 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" id="confirm-bulk-archive"
                        class="flex-1 rounded-xl bg-red-600 px-4 py-3 font-medium text-white transition-colors duration-200 hover:bg-red-700">
                        Archive Products
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
