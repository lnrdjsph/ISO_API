@extends('layouts.app')

@section('title', 'Product List')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    @include('products.partials.header')
    @include('products.partials.loader')
    @include('products.partials.filters')

    {{-- Product Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        {{-- Loading overlay (sits over the table container) --}}
        <div id="products-table-wrapper" class="relative">
            <div id="products-loading"
                class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-[1px]">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
            </div>

            {{-- This div is replaced entirely on every AJAX call --}}
            <div id="products-table-container">
                @include('products.partials.table-content')
            </div>
        </div>
    </div>

    @if (!$isPersonnel)
        @include('products.partials.modals')
    @endif

</div>

{{-- Pass config to JS --}}
<script nonce="{{ $cspNonce ?? '' }}">
    window.productsConfig = {
        currentStore:     @json($currentStore),
        currentWarehouse: @json($currentWarehouse),
        csrfToken:        @json(csrf_token()),
        indexUrl:         @json(route('products.index')),
        routes: {
            search:      @json(route('products.search')),
            wmsStatus:   @json(route('update.allocations.status')),
            bulkUpdate:  @json(route('products.bulk-update')),
            bulkArchive: @json(route('products.bulk-archive')),
        },
    };
</script>

@vite(['resources/js/pages/products/index.js'])
@endsection
