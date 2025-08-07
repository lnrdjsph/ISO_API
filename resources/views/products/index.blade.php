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
                <div class="flex justify-between items-start gap-4 flex-nowrap ">

                    <form method="GET" action="{{ route('products.index') }}" class="w-full max-w-lg">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" name="query" id="product-search" value="{{ request('query') }}" autocomplete="off"
                                class="w-full pl-12 pr-4 py-3 bg-white/60 border border-gray-200/60 rounded-2xl 
                                    focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
                                    placeholder-gray-400 text-gray-700 backdrop-blur-sm transition-all duration-200
                                    hover:bg-white/80 hover:shadow-lg"
                                placeholder="Search by SKU or product description..."/>
                                <ul id="product-list" class="absolute z-[999] mt-1 w-full bg-white rounded-xl shadow-xl hidden"></ul>


                        </div>
                    </form>

                    <!-- Bulk Actions Bar -->
                    <div id="bulk-actions-bar" class="hidden ">
                        <div class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-1 border border-blue-500/20">
                            <div class="flex items-center justify-between text-white whitespace-nowrap overflow-x-auto px-4 py-2">

                                <!-- Count -->
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span id="selected-count" class="font-semibold">0</span>
                                    <span>items selected</span>
                                </div>

                                <!-- Buttons -->
                                <div class="flex items-center gap-3">
                                    <button id="bulk-edit-btn" class="flex items-center px-3 hover:underline font-medium rounded-xl">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Bulk Edit
                                    </button>

                                    <button id="bulk-archive-btn" class="flex items-center px-3 hover:underline font-medium rounded-xl">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 8l4 4 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Archive
                                    </button>

                                    <button id="clear-selection-btn" class="flex items-center px-2 hover:underline rounded-xl">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <!-- Filter -->
                        <button class="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100/60 rounded-xl transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Filter
                        </button>

                        <!-- Export -->
                        <a href="{{ route('products.export', request()->query()) }}" class="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100/60 rounded-xl transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                        </a>

                        <!-- Add Product -->
                        <a href="{{ route('products.create') }}"
                        class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 
                                text-white font-semibold rounded-2xl hover:shadow-xl 
                                hover:from-blue-700 hover:to-indigo-700 transform hover:-translate-y-0.5 
                                transition-all border border-blue-500/20">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
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

                        if (!function_exists('sortRoute')) {
                            function sortRoute($column) {
                                $direction = request('direction', 'asc') === 'asc' ? 'desc' : 'asc';

                                return route('products.index', array_merge(request()->except(['page', 'direction', 'sort']), [
                                    'sort' => $column,
                                    'direction' => request('sort') === $column ? $direction : 'asc',
                                ]));
                            }
                        }
                    @endphp
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-800 to-gray-700 text-white">
                                <th class="pl-6 py-4 text-left text-sm font-bold uppercase text-blue rounded-tl-3xl w-16">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="select-all" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">
                                    <a href="{{ sortRoute('sku') }}" class="flex items-center space-x-2 group">
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
                                    <a href="{{ sortRoute('description') }}" class="flex items-center space-x-2 group">
                                        <span>Product description</span>
                                        @if($currentSort === 'description')
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
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">Case Pack</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">SRP</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">Allocation / Case</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">C/BC Scheme</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue">PO15 Scheme</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase text-blue rounded-tr-3xl">Freebie SKU</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/60">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-100/60 transition-all duration-200 group opacity-0 animate-fade-in product-row" data-product-id="{{ $product->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="product-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" value="{{ $product->id }}">
                                    </td>
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
                                            <div class="text-sm font-semibold text-gray-800 max-w-xs">
                                                {{ $product->description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                            {{ $product->case_pack ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                            ₱{{ number_format($product->srp ?? 0, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                            {{ $product->allocation_per_case ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                            {{ $product->cash_bank_card_scheme ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                            {{ $product->po15_scheme ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="relative inline-block w-full">
                                            <div class="peer inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100/60 text-purple-800 border border-purple-200/60">
                                                {{ $product->freebie_sku ?? '-' }}
                                            </div>
                                            <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-full ml-2 px-3 py-2 max-w-xs w-max bg-gray-800 text-white text-xs rounded shadow-lg opacity-0 peer-hover:opacity-100 transition-opacity z-50 whitespace-normal break-words pointer-events-none">
                                                {{ $product->freebie_description ?? 'No description found' }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <div class="px-6 py-4 bg-white backdrop-blur-sm rounded-b-3xl">
                    {{ $products->withPath(route('products.index'))->onEachSide(1)->links() }}
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div id="bulk-edit-modal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Bulk Edit Products
                    </h3>
                    <button id="close-bulk-edit" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-gray-600 mt-2">Update multiple products at once. Leave fields empty to keep existing values.</p>
            </div>
            
            <form id="bulk-edit-form" class="p-6 space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Case Pack</label>
                        <input type="number" name="case_pack" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SRP (₱)</label>
                        <input type="number" step="0.01" name="srp" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allocation per Case</label>
                        <input type="number" name="allocation_per_case" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">C/BC Scheme</label>
                        <input type="text" name="cash_bank_card_scheme" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PO15 Scheme</label>
                        <input type="text" name="po15_scheme" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Freebie SKU</label>
                        <input type="text" name="freebie_sku" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-600">
                        <span id="bulk-edit-selected-count">0</span> products will be updated
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" id="cancel-bulk-edit" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg">
                            Update Products
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Archive Confirmation Modal -->
<div id="bulk-archive-modal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Archive Products</h3>

                <p class="text-gray-600 text-center mb-4">
                    Are you sure you want to archive
                    <span id="archive-selected-count" class="font-semibold text-red-600">0</span>
                    selected products? This action can be undone later.
                </p>

                <!-- Optional archive reason -->
                <div class="mb-4">
                    <label for="archive-reason-input" class="block text-sm font-medium text-gray-700 mb-1">
                        Archive Reason <span class="text-gray-400 text-xs">(optional)</span>
                    </label>
                    <textarea id="archive-reason-input"
                              class="w-full px-4 py-2 border rounded-xl text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                              rows="3"
                              maxlength="500"
                              placeholder="Enter reason for archiving (max 500 characters)..."></textarea>
                </div>

                <div class="flex space-x-3">
                    <button type="button" id="cancel-bulk-archive"
                            class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="button" id="confirm-bulk-archive"
                            class="flex-1 px-4 py-3 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition-colors duration-200">
                        Archive Products
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        let selectedProducts = new Set();

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
        }, 100);

        // Checkbox functionality
        function updateBulkActionsBar() {
            const count = selectedProducts.size;
            
            if (count > 0) {
                $('#bulk-actions-bar').removeClass('hidden').hide().slideDown(300);
                $('#selected-count').text(count);
                $('#bulk-edit-selected-count').text(count);
                $('#archive-selected-count').text(count);
            } else {
                $('#bulk-actions-bar').slideUp(300, function() {
                    $(this).addClass('hidden');
                });
            }
        }

        // Select all checkbox
        $('#select-all').change(function() {
            const isChecked = $(this).is(':checked');
            $('.product-checkbox').prop('checked', isChecked);
            
            selectedProducts.clear();
            if (isChecked) {
                $('.product-checkbox').each(function() {
                    selectedProducts.add($(this).val());
                });
            }
            updateBulkActionsBar();
        });

        // Individual checkbox
        $(document).on('change', '.product-checkbox', function() {
            const productId = $(this).val();
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                selectedProducts.add(productId);
            } else {
                selectedProducts.delete(productId);
                $('#select-all').prop('checked', false);
            }
            
            // Update select-all checkbox
            const totalCheckboxes = $('.product-checkbox').length;
            const checkedCheckboxes = $('.product-checkbox:checked').length;
            
            if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
            
            updateBulkActionsBar();
        });

        // Clear selection
        $('#clear-selection-btn').click(function() {
            selectedProducts.clear();
            $('.product-checkbox, #select-all').prop('checked', false);
            updateBulkActionsBar();
        });

        // Bulk Edit Modal
        $('#bulk-edit-btn').click(function() {
            if (selectedProducts.size === 0) {
                alert('Please select products first');
                return;
            }
            $('#bulk-edit-modal').removeClass('hidden').addClass('flex');
        });

        $('#close-bulk-edit, #cancel-bulk-edit').click(function() {
            $('#bulk-edit-modal').addClass('hidden').removeClass('flex');
        });

        // Bulk Edit Form Submission
        $('#bulk-edit-form').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const productIds = Array.from(selectedProducts);
            
            // Add product IDs to form data
            productIds.forEach(id => {
                formData.append('product_ids[]', id);
            });

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).html(`
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating...
            `);

            $.ajax({
                url: '{{ route("products.bulk-update") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                        searchInput.removeClass('animate-pulse');
                        list.empty();

                        // Always show list when results are present
                        list.removeClass('hidden');
                    // Show success message
                    showNotification('Products updated successfully!', 'success');
                    
                    // Close modal and refresh page
                    $('#bulk-edit-modal').addClass('hidden').removeClass('flex');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while updating products.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    showNotification(errorMessage, 'error');
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        // Ensure CSRF token is set for all AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Show modal
$('#bulk-archive-btn').click(function () {
    if (selectedProducts.size === 0) {
        alert('Please select products first');
        return;
    }
    $('#bulk-archive-modal').removeClass('hidden').addClass('flex');
});

// Cancel modal
$('#cancel-bulk-archive').click(function () {
    $('#bulk-archive-modal').addClass('hidden').removeClass('flex');
});

// Confirm archive
$('#confirm-bulk-archive').click(function () {
    const productIds = Array.from(selectedProducts);
    const archiveReason = $('#archive-reason-input').val();
    const button = $(this);
    const originalText = button.text();

    button.prop('disabled', true).html(`
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Archiving...
    `);

    $.ajax({
        url: '{{ route("products.bulk-archive") }}',
        method: 'POST',
        data: {
            product_ids: productIds,
            archive_reason: archiveReason
        },
        success: function (response) {
            if (response.success) {
                showNotification(response.message, 'success');
                $('#bulk-archive-modal').addClass('hidden').removeClass('flex');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function (xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred.';
            showNotification(message, 'error');
        },
        complete: function () {
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Notification utility
function showNotification(message, type = 'info') {
    const bgColor = type === 'success' ? 'bg-green-500' :
                    type === 'error' ? 'bg-red-500' : 'bg-blue-500';

    const notification = $(`
        <div class="fixed top-4 right-4 z-50 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-lg transform translate-x-full transition-transform duration-300">
            <div class="flex items-center">
                <span class="mr-3">${message}</span>
                <button class="ml-auto text-white hover:text-gray-200" onclick="$(this).closest('div').remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    `);

    $('body').append(notification);
    setTimeout(() => notification.removeClass('translate-x-full'), 100);
    setTimeout(() => {
        notification.addClass('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Close modal if clicked outside
$('#bulk-edit-modal, #bulk-archive-modal').click(function (e) {
    if (e.target === this) {
        $(this).addClass('hidden').removeClass('flex');
    }
});


        // Search functionality (existing code)
        let debounceTimeout;

        function highlightMatch(text, query) {
            if (!query) return text;

            // Escape special characters in the query
            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

            const regex = new RegExp(`(${escapedQuery})`, 'gi');
            return text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
        }

        function performSearch(query) {

            const list = $('#product-list');
            const searchInput = $('#product-search');

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
                        list.removeClass('hidden');
                        list.empty();

                        let cleanedQuery = query.replace(/[^a-z0-9]/gi, '').toLowerCase();

                        let filtered = data.filter(item => {
                            let cleanSku = item.sku.replace(/[^a-z0-9]/gi, '').toLowerCase();
                            let cleanDescription = item.description.replace(/[^a-z0-9]/gi, '').toLowerCase();
                            return cleanSku.includes(cleanedQuery) || cleanDescription.includes(cleanedQuery);
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
                                    .addClass('px-6 py-4 hover:bg-gray-50/80 cursor-pointer product-item transition-all duration-200 border-b border-gray-100/60 last:border-b-0 opacity-0 translate-y-2')
                                    .attr('data-sku', item.sku)
                                    .attr('data-description', item.description)
                                    .html(`
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <span class="text-xs font-mono font-medium text-gray-500 px-2 py-1 bg-gray-100/60 rounded mr-3">${item.sku}</span>
                                                    <span class="font-medium text-gray-800">
                                                        ${highlightMatch(item.description, query)}
                                                    </span>
                                                </div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    `);

                                // Animate item in
                                setTimeout(() => {
                                    listItem
                                        .removeClass('opacity-0 translate-y-2')
                                        .addClass('opacity-100 translate-y-0');
                                }, index * 50);

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
            const description = $(this).data('description');
            
            $(this).addClass('bg-blue-100/60 scale-95');
            
            setTimeout(() => {
                if (sku) {
                    $('#product-search').val(`${sku}`);
                    $('#product-list').empty().addClass('hidden');
                    $('#product-search').closest('form').submit();
                }
            }, 150);

        });

        $(document).on('click', function (e) {
            const target = $(e.target);
            setTimeout(() => {
                if (
                    !target.closest('#product-search').length &&
                    !target.closest('#product-list').length
                ) {
                    $('#product-list').empty().addClass('hidden');
                }
            }, 10); // let inner clicks finish first
        });

    });
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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

    /* Modal animations */
    .modal-enter {
        opacity: 0;
        transform: scale(0.95);
    }
    
    .modal-enter-active {
        opacity: 1;
        transform: scale(1);
        transition: all 0.2s ease-out;
    }
</style>
@endsection