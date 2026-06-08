<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center space-x-3">
        <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-3 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Product List</h1>
            <p class="text-xs text-gray-500 sm:text-sm">Manage and explore your product inventory</p>
        </div>
    </div>

    <span class="inline-flex items-center rounded-lg border border-pink-200 bg-pink-50 px-3 py-1.5 text-xs font-medium text-pink-700 shadow-sm">
        {{ number_format($totalProducts) }} products
    </span>
</div>
