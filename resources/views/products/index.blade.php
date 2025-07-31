@extends('layouts.app')

@section('title', 'Product List')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 py-8">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Loading Skeleton -->
        <div id="skeleton-loader" class="animate-pulse">
            <!-- Header Skeleton -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-gray-300 rounded-xl w-14 h-14"></div>
                        <div>
                            <div class="h-8 bg-gray-300 rounded w-48 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-64"></div>
                        </div>
                    </div>
                    <div class="bg-gray-200 rounded-2xl px-4 py-2 w-32 h-10"></div>
                </div>
            </div>

            <!-- Search Bar Skeleton -->
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-4">
                    <div class="relative flex-1 max-w-md">
                        <div class="h-12 bg-gray-200 rounded-2xl"></div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="h-10 bg-gray-200 rounded-xl w-20"></div>
                        <div class="h-10 bg-gray-200 rounded-xl w-20"></div>
                        <div class="h-12 bg-gray-300 rounded-2xl w-32"></div>
                    </div>
                </div>
            </div>

            <!-- Table Skeleton -->
            <div class="bg-white backdrop-blur-sm rounded-3xl shadow-lg border border-white/20 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-700">
                                <th class="pl-14 py-4 text-left rounded-tl-3xl">
                                    <div class="h-4 bg-gray-500 rounded w-12"></div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="h-4 bg-gray-500 rounded w-24"></div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="h-4 bg-gray-500 rounded w-20"></div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="h-4 bg-gray-500 rounded w-16"></div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="h-4 bg-gray-500 rounded w-16"></div>
                                </th>
                                {{-- <th class="px-6 py-4 text-left">
                                    <div class="h-4 bg-gray-500 rounded w-12"></div>
                                </th> --}}
                                <th class="px-6 py-4 text-left rounded-tr-3xl">
                                    <div class="h-4 bg-gray-500 rounded w-12"></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/60">
                            @for($i = 0; $i < 10; $i++)
                            <tr>
                                <td class="px-6 py-5">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-gray-300 rounded-full mr-3"></div>
                                        <div class="h-6 bg-gray-200 rounded-lg w-20"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="h-4 bg-gray-200 rounded w-32"></div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="h-6 bg-gray-200 rounded-full w-16"></div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="h-6 bg-gray-200 rounded-full w-20"></div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="h-6 bg-gray-200 rounded-full w-18"></div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="h-6 bg-gray-200 rounded-full w-12"></div>
                                </td>
                                {{-- <td class="px-6 py-5">
                                    <div class="h-6 bg-gray-200 rounded-full w-12"></div>
                                </td> --}}
                            </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <!-- Pagination Skeleton -->
                <div class="px-6 py-2 bg-white backdrop-blur-sm rounded-b-3xl">
                    <div class="flex justify-between items-center">
                        <div class="h-4 bg-gray-200 rounded w-32"></div>
                        <div class="flex space-x-2">
                            <div class="h-8 bg-gray-200 rounded w-8"></div>
                            <div class="h-8 bg-gray-200 rounded w-8"></div>
                            <div class="h-8 bg-gray-200 rounded w-8"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actual Content (Initially Hidden) -->
        <div id="actual-content" class="hidden">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Product List</h1>
                            <p class="text-gray-600 mt-1">Manage and explore your product inventory</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="bg-white/70 backdrop-blur-sm rounded-2xl px-4 py-2 border border-white/20 shadow-lg">
                            <span class="text-sm text-gray-600">Total Products: </span>
                            <span class="font-semibold text-gray-800">{{ $products->total() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Actions Bar -->
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-4">
                    <!-- Enhanced Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="product-search" 
                            class="w-full pl-12 pr-4 py-3 bg-white/60 border border-gray-200/60 rounded-2xl shadow-lg 
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
                                   placeholder-gray-400 text-gray-700 backdrop-blur-sm transition-all duration-200
                                   hover:bg-white/80 hover:shadow-lg" 
                            placeholder="Search by SKU or product name..."
                        >
                        <ul id="product-list" class="absolute z-50 w-full bg-white backdrop-blur-md border border-gray-200/60 rounded-2xl mt-2 shadow-lg max-h-80 overflow-y-auto hidden"></ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <button class="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100/60 rounded-xl transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Filter
                        </button>
                        <button class="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100/60 rounded-xl transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export
                        </button>
                        <a href="{{ route('products.create') }}" 
                           class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 
                                  text-white font-semibold rounded-2xl shadow-lg hover:shadow-xl 
                                  hover:from-blue-700 hover:to-indigo-700 transform hover:-trangray-y-0.5 
                                  transition-all duration-200 border border-blue-500/20">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Product
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modern Product Table -->
            <div class="bg-white backdrop-blur-sm rounded-3xl shadow-lg border border-white/20 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                    @php
                        $currentSort = request('sort', 'sku');
                        $currentDirection = request('direction', 'asc');
                        function sortUrl($column) {
                            $direction = (request('sort') === $column && request('direction') === 'asc') ? 'desc' : 'asc';
                            return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $direction]);
                        }
                    @endphp
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-800 to-gray-700 text-white">
                                <th class="pl-14 py-4 text-left text-sm font-bold uppercase text-blue rounded-tl-3xl">
                                    <a href="{{ sortUrl('sku') }}" class="flex items-center space-x-2 group">
                                        <span>SKU</span>
                                        @if($currentSort === 'sku')
                                            <svg class="w-4 h-4 {{ $currentDirection === 'asc' ? '' : 'rotate-180' }} text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 opacity-60 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">
                                    <a href="{{ sortUrl('name') }}" class="flex items-center space-x-2 group">
                                        <span>Product Name</span>
                                        @if($currentSort === 'name')
                                            <svg class="w-4 h-4 {{ $currentDirection === 'asc' ? '' : 'rotate-180' }} text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 opacity-60 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">Class</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">Brand</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">Department</th>
                                {{-- <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">Group</th> --}}
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue rounded-tr-3xl">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/60">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-100/60 transition-all duration-200 group opacity-0 animate-fade-in">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full mr-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
                                            <span class="text-sm font-mono font-semibold text-gray-800 bg-gray-100/60 px-3 py-1 rounded-lg">
                                                {{ $product->sku }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-semibold text-gray-800  max-w-xs">
                                                {{ $product->name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                                {{ $product->class_name ?? '-' }}
                                            </span>
                                    </td>                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-emerald-200/60">
                                                {{ $product->brand ?? '-' }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100/60 text-blue-800 border border-blue-200/60">
                                                {{ $product->department ?? '-' }}
                                            </span>
                                    </td>
                                    {{-- <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                                {{ $product->group_name ?? '-' }}
                                            </span>
                                    </td> --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                                {{ $product->stock_on_hand ?? '-' }}
                                            </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <div class="px-6 py-4 bg-white backdrop-blur-sm rounded-b-3xl">
                    {{ $products->withPath('/iso-api' . request()->getPathInfo())->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Simulate loading time and show actual content
        setTimeout(function() {
            $('#skeleton-loader').fadeOut(400, function() {
                $('#actual-content').removeClass('hidden').hide().fadeIn(600);
                
                // Animate table rows
                $('tbody tr').each(function(index) {
                    const row = $(this);
                    setTimeout(function() {
                        row.removeClass('opacity-0').addClass('opacity-100');
                    }, index * 100);
                });
            });
        }, 1500); // Adjust this delay as needed

        let debounceTimeout;

        function performSearch(query) {
            const list = $('#product-list');
            const searchInput = $('#product-search');

            // Add loading state to input
            searchInput.addClass('animate-pulse');
            
            list
                .removeClass('hidden')
                .html(`
                    <li class="px-6 py-4 text-gray-600 flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Searching products...
                    </li>
                `);

            debounceTimeout = setTimeout(() => {
                $.ajax({
                    url: '{{ route("products.search") }}',
                    data: { query: query },
                    success: function (data) {
                        searchInput.removeClass('animate-pulse');
                        list.empty();

                        let cleanedQuery = query.replace(/[^a-z0-9]/gi, '').toLowerCase();

                        let filtered = data.filter(item => {
                            let cleanSku = item.sku.replace(/[^a-z0-9]/gi, '').toLowerCase();
                            let cleanName = item.name.replace(/[^a-z0-9]/gi, '').toLowerCase();
                            return cleanSku.includes(cleanedQuery) || cleanName.includes(cleanedQuery);
                        });


                        if (filtered.length === 0) {
                            list.append(`
                                <li class="px-6 py-4 text-gray-500 flex items-center justify-center">
                                    <div class="text-center">
                                        <p class="text-sm">No products found</p>
                                        <p class="text-xs text-gray-400 mt-1">Try adjusting your search terms</p>
                                    </div>
                                </li>
                            `);
                        } else {
                            filtered.forEach((item, index) => {
                                const listItem = $('<li>')
                                    .addClass('px-6 py-4 hover:bg-gray-50/80 cursor-pointer product-item transition-all duration-200 border-b border-gray-100/60 last:border-b-0')
                                    .attr('data-sku', item.sku)
                                    .attr('data-name', item.name)
                                    .html(`
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <span class="text-xs font-mono font-medium text-gray-500 px-2 py-1 bg-gray-100/60 rounded mr-3">${item.sku}</span>
                                                    <span class="font-medium text-gray-800">${item.name}</span>
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    `);
                                
                                // Add staggered animation
                                setTimeout(() => {
                                    listItem.addClass('opacity-100 trangray-y-0');
                                }, index * 50);
                                
                                listItem.addClass('opacity-0 trangray-y-2 transition-all duration-200');
                                list.append(listItem);
                            });
                        }
                    },
                    error: function() {
                        searchInput.removeClass('animate-pulse');
                        list.html(`
                            <li class="px-6 py-4 text-red-600 flex items-center">
                                Search failed. Please try again.
                            </li>
                        `);
                    }
                });
            }, 300);
        }

        $('#product-search').on('keyup', function () {
            clearTimeout(debounceTimeout);

            const query = $(this).val().toLowerCase();
            if (query.length >= 2) {
                performSearch(query);
            } else {
                $('#product-list').empty().addClass('hidden');
            }
        });

        $('#product-search').on('focus', function () {
            const query = $(this).val().toLowerCase();
            if (query.length >= 2) {
                performSearch(query);
            }
        });

        $(document).on('click', '.product-item', function () {
            const sku = $(this).data('sku');
            const name = $(this).data('name');
            
            // Add visual feedback
            $(this).addClass('bg-blue-100/60 scale-95');
            
            setTimeout(() => {
                $('#product-search').val(`${sku} - ${name}`);
                $('#product-list').empty().addClass('hidden');
            }, 150);
        });

        $(document).on('click', function (e) {
            const target = $(e.target);
            if (
                !target.closest('#product-search').length &&
                !target.closest('#product-list').length
            ) {
                $('#product-list').empty().addClass('hidden');
            }
        });
    });
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: trangrayY(10px); }
        to { opacity: 1; transform: trangrayY(0); }
    }
    
    .animate-fade-in {
        animation: fade-in 0.6s ease-out forwards;
    }
    
    /* Skeleton pulse animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Custom scrollbar for search results */
    #product-list {
        scrollbar-width: thin;
        scrollbar-color: rgb(148 163 184) transparent;
    }
    
    #product-list::-webkit-scrollbar {
        width: 6px;
    }
    
    #product-list::-webkit-scrollbar-track {
        background: transparent;
    }
    
    #product-list::-webkit-scrollbar-thumb {
        background-color: rgb(148 163 184);
        border-radius: 3px;
    }
    
    #product-list::-webkit-scrollbar-thumb:hover {
        background-color: rgb(100 116 139);
    }
</style>
@endsection