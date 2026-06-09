{{--
    Swappable table content — returned as-is for AJAX requests.
    Includes thead (sort indicators update per request) + tbody + pagination footer.
--}}
<div class="overflow-x-auto">
    <table class="products-table w-full min-w-full table-auto text-xs">
        @include('products.partials.table-head')
        <tbody class="divide-y divide-gray-100">
            @forelse ($products as $product)
                @include('products.partials.table-row')
            @empty
                <tr>
                    <td colspan="{{ $isSuperAdmin ? 14 : 13 }}" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">No products found</p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    @if(request('query'))
                                        No results for "<span class="font-medium">{{ request('query') }}</span>" — try a different search.
                                    @else
                                        Get started by adding your first product.
                                    @endif
                                </p>
                            </div>
                            @if (!$isPersonnel)
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('products.create') }}"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-md bg-indigo-600 px-3 text-xs font-medium text-white hover:bg-indigo-700">
                                        Add Product
                                    </a>
                                    <a href="{{ route('products.import.show') }}"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        Import CSV
                                    </a>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination footer --}}
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white px-4 py-2.5">
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500">Rows per page:</span>
        <select id="per-page-select"
            class="h-8 appearance-none cursor-pointer rounded-md border border-gray-300 bg-white py-0 pl-2 pr-7 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-400">
            @foreach ([10, 15, 25, 50, 100] as $size)
                <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                    {{ $size }}
                </option>
            @endforeach
        </select>
    </div>
    <div id="pagination-links">
        {{ $products->withPath(route('products.index'))->appends(request()->query())->onEachSide(1)->links() }}
    </div>
</div>
