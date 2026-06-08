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
<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:10px 16px;border-top:1px solid #f1f5f9;background:#fff">
    <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;color:#6b7280">Rows per page:</span>
        <select id="per-page-select"
            style="-webkit-appearance:none;-moz-appearance:none;appearance:none;height:32px;border:1px solid #d1d5db;border-radius:6px;padding:0 28px 0 8px;font-size:12px;color:#374151;background:#fff url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3E%3Cpath stroke=%27%236b7280%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27M6 8l4 4 4-4%27/%3E%3C/svg%3E') right 6px center / 1.1em no-repeat;cursor:pointer">
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
