<div class="mb-8 text-center sm:text-left">
    <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <div class="flex items-center gap-4">
            <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 p-3 shadow-lg">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Sales Order Form</h1>
                <p class="mt-1 text-gray-500">Create a new sales order with ease</p>
            </div>
        </div>
        <div class="flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm">
            <span class="text-sm font-medium text-gray-600">Order ID:</span>
            <span class="font-mono text-sm font-bold text-indigo-600">{{ $nextSofId }}</span>
        </div>
    </div>
</div>
