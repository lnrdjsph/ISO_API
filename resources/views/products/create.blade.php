@extends('layouts.app')

@section('title', 'Add Products')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-3 shadow-lg">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Add Products</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Enroll multiple products in one go</p>
            </div>
        </div>
        <a href="{{ route('products.index') }}"
            class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Products
        </a>
    </div>

    {{-- Store selector + counter bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <svg class="h-4 w-4 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="text-xs font-medium text-gray-600">Adding products to:</span>
                @include('products.partials.store-selector')
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span>Products in batch:</span>
                <span id="product-counter"
                    class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">1</span>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form id="product-form" action="{{ route('products.store') }}" method="POST" class="space-y-4">
        @csrf

        {{-- Rows container --}}
        <div id="product-rows" class="space-y-3">
            @include('products.partials.product-row', ['rowIndex' => 0, 'isFirst' => true])
        </div>

        {{-- Add another button --}}
        <button type="button" id="add-row-btn"
            class="group flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 py-4 text-sm font-medium text-gray-400 transition-all hover:border-indigo-300 hover:bg-indigo-50/60 hover:text-indigo-600">
            <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-dashed border-current transition-all group-hover:bg-indigo-100">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            Add Another Product
        </button>

        {{-- Result box --}}
        <div id="form-result" class="hidden"></div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <button type="button" id="clear-all-btn"
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear All
            </button>
            <button type="submit" id="submit-btn"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-6 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enroll Products
            </button>
        </div>
    </form>

    {{-- Tips --}}
    <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <ul class="space-y-1 text-xs text-blue-700">
                <li>SKU must be unique — duplicates within this batch or against the database are rejected.</li>
                <li>Freebie SKUs can be separated by <code class="rounded bg-blue-100 px-1">/</code> e.g. <code class="rounded bg-blue-100 px-1">15003036/6447779</code></li>
                <li>Case Pack supports multiple values separated by <code class="rounded bg-blue-100 px-1">|</code> e.g. <code class="rounded bg-blue-100 px-1">24 | 48</code></li>
            </ul>
        </div>
    </div>

</div>

{{-- Row template (cloned by JS — no inline string building) --}}
<template id="product-row-template">
    @include('products.partials.product-row', ['rowIndex' => '__IDX__', 'isFirst' => false])
</template>

<script nonce="{{ $cspNonce ?? '' }}">
    window.createConfig = {
        csrfToken: @json(csrf_token()),
        indexUrl:  @json(route('products.index')),
        storeUrl:  @json(route('products.store')),
    };
</script>

@vite(['resources/js/pages/products/create.js'])
@endsection
