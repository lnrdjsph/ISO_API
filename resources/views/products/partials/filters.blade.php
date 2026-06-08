<div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">

    {{-- Row 1: search + store + warehouse --}}
    <div class="flex flex-wrap items-center gap-2">

        {{-- Search --}}
        <form method="GET" action="{{ route('products.index') }}" class="relative min-w-0 flex-1 sm:max-w-xs">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                name="query"
                id="product-search"
                value="{{ request('query') }}"
                autocomplete="off"
                placeholder="Search SKU, description, or sub-dept…"
                class="h-8 w-full rounded-md border border-gray-300 pl-9 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:h-9" />
            <button type="button" id="clear-search-btn" title="Clear search"
                class="{{ request('query') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <ul id="product-list" class="absolute z-[999] mt-1 hidden w-full rounded-xl bg-white shadow-xl ring-1 ring-black/5"></ul>
        </form>

        {{-- Store selector --}}
        @if (empty($accessibleStores))
            <span class="inline-flex h-8 items-center rounded-md border border-yellow-200 bg-yellow-50 px-3 text-xs text-yellow-700 sm:h-9">
                No stores assigned
            </span>
        @elseif (!$hasMultipleStores)
            <span class="inline-flex h-8 items-center rounded-md border border-gray-200 bg-gray-50 px-3 text-xs font-medium text-gray-700 sm:h-9">
                {{ $singleStoreCode }} – {{ $singleStoreName }}
            </span>
        @else
            <select name="store" id="store-select"
                class="h-8 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                @foreach ($accessibleStores as $code => $name)
                    <option value="{{ $code }}" {{ $currentStore == $code ? 'selected' : '' }}>
                        {{ $code }} – {{ $name }}
                    </option>
                @endforeach
            </select>
        @endif

        {{-- Warehouse selector --}}
        @if (empty($accessibleWarehouses))
            <span class="inline-flex h-8 items-center rounded-md border border-yellow-200 bg-yellow-50 px-3 text-xs text-yellow-700 sm:h-9">
                No warehouse assigned
            </span>
        @elseif (!$hasMultipleWarehouses)
            <span class="inline-flex h-8 items-center rounded-md border border-gray-200 bg-gray-50 px-3 text-xs font-medium text-gray-700 sm:h-9">
                {{ $singleWhCode }} – {{ $singleWhName }}
            </span>
        @else
            <select name="warehouse" id="warehouse-select"
                class="h-8 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                @foreach ($accessibleWarehouses as $code => $name)
                    <option value="{{ $code }}" {{ $currentWarehouse == $code ? 'selected' : '' }}>
                        {{ $code }} – {{ $name }}
                    </option>
                @endforeach
            </select>
        @endif

        {{-- Spacer --}}
        <div class="flex-1"></div>

        {{-- Action buttons (non-personnel only) --}}
        @if (!$isPersonnel)
            @include('products.partials.bulk-actions')
        @endif

    </div>

    {{-- Row 2: active bulk-select bar (hidden until items selected) --}}
    @if (!$isPersonnel)
        <div id="bulk-actions-bar" class="hidden mt-2">
            <div class="flex flex-wrap items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2">
                <svg class="h-4 w-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs font-medium text-blue-700">
                    <span id="selected-count">0</span> selected
                </span>
                <div class="mx-1 h-4 w-px bg-blue-200"></div>
                <button id="bulk-archive-btn"
                    class="h-7 rounded-md border border-blue-300 bg-white px-3 text-xs font-medium text-blue-700 transition hover:bg-blue-100">
                    Archive
                </button>
                <button id="clear-selection-btn"
                    class="h-7 rounded-md border border-gray-300 bg-white px-3 text-xs font-medium text-gray-600 transition hover:bg-gray-100">
                    Clear
                </button>
                <span class="hidden" id="bulk-edit-selected-count">0</span>
                <span class="hidden" id="archive-selected-count">0</span>
            </div>
        </div>
    @endif

</div>
