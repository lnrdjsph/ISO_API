{{-- Action buttons — export, add, update WMS --}}
<div class="flex flex-wrap items-center gap-1.5">

    <a href="{{ route('products.export', request()->query()) }}"
        class="inline-flex h-8 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 sm:h-9">
        <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
        </svg>
        <span class="hidden sm:inline">Export</span>
    </a>

    {{-- Add Products (overlay select) --}}
    <div class="relative">
        <button type="button"
            class="inline-flex h-8 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 sm:h-9">
            <svg class="h-3.5 w-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Add Products</span>
        </button>
        <select id="no-products-action" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
            <option value="" selected disabled>Select action</option>
            <option value="{{ route('products.create') }}">Add New Product</option>
            <option value="{{ route('products.import.show') }}">Import Products</option>
        </select>
    </div>

    {{-- Update WMS Inventory --}}
    <form id="updateAllocationsForm" action="{{ route('update.allocations') }}" method="POST">
        @csrf
        <button type="submit" id="updateButton"
            class="inline-flex h-8 items-center gap-1.5 rounded-md bg-indigo-600 px-3 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 sm:h-9">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0112-3.464M20 15a8 8 0 01-12 3.464" />
            </svg>
            <span class="hidden sm:inline">Update WMS</span>
        </button>
    </form>

</div>
