@extends('layouts.app')

@section('title', 'Import Products')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-3 shadow-lg">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Import Products</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Upload a CSV file to add or update products in bulk</p>
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

    {{-- Store selector bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-wrap items-center gap-3">
            <svg class="h-4 w-4 flex-shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="text-xs font-medium text-gray-600">Importing into store:</span>
            @include('products.partials.store-selector')
        </div>
    </div>

    {{-- Drop zone card --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">Upload CSV File</h2>
        </div>
        <div class="p-5 space-y-4">

            {{-- Drop zone --}}
            <div id="drop-zone"
                class="relative flex min-h-[160px] cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-gray-300 transition-colors duration-200 hover:border-indigo-400 hover:bg-indigo-50/40"

                <input type="file" id="csv-file" accept=".csv" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">

                {{-- Idle state --}}
                <div id="drop-idle" class="flex flex-col items-center gap-3 px-6 py-10 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                        <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Drag &amp; drop your CSV file here</p>
                        <p class="mt-1 text-xs text-gray-400">or click to browse — .csv files only</p>
                    </div>
                    <span class="inline-flex h-8 items-center rounded-lg bg-indigo-600 px-4 text-xs font-medium text-white">
                        Choose File
                    </span>
                </div>

                {{-- Selected state --}}
                <div id="drop-selected" class="hidden w-full px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-green-100">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p id="selected-name" class="truncate text-sm font-semibold text-gray-800"></p>
                            <p id="selected-meta" class="mt-0.5 text-xs text-gray-500"></p>
                        </div>
                        <button type="button" id="remove-file-btn"
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:border-red-200 hover:bg-red-50 hover:text-red-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Upload progress --}}
            <div id="upload-progress-wrap" class="hidden">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-500">Uploading &amp; processing…</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div id="upload-progress-bar"
                        class="h-full w-0 rounded-full bg-indigo-600 transition-all duration-300"></div>
                </div>
            </div>

            {{-- Import button --}}
            <div class="flex items-center justify-between">
                <button type="button" id="download-template-btn"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download CSV Template
                </button>
                <button type="button" id="import-btn"
                    class="hidden inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Products
                </button>
            </div>

            {{-- Result section --}}
            <div id="result-section" class="hidden"></div>

        </div>
    </div>

    {{-- Preview table --}}
    <div id="preview-section" class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">Data Preview</h2>
            <div id="preview-count" class="flex flex-wrap items-center gap-2"></div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-full text-xs">
                <thead class="bg-slate-800">
                    <tr>
                        @foreach (['#','SKU','Description','Allocation','Case Pack','SRP','C/BC','PO15%','Discount','Freebie','Status'] as $col)
                            <th class="whitespace-nowrap px-2.5 py-2 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="preview-body"></tbody>
            </table>
        </div>
    </div>

    {{-- CSV format guide (collapsible) --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <button type="button" id="guide-toggle"
            class="flex w-full items-center justify-between px-5 py-4 text-left">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold text-gray-800">CSV Format Requirements</span>
            </div>
            <svg id="guide-icon" class="h-4 w-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div id="guide-content" class="hidden border-t border-gray-100 px-5 py-5">
            <div class="grid gap-6 md:grid-cols-2">

                {{-- Column rules --}}
                <div>
                    <p class="mb-3 text-xs text-gray-500">Your CSV must have exactly <strong class="text-gray-700">9 columns</strong> in this order:</p>
                    <ol class="list-[upper-alpha] space-y-1.5 pl-4 text-xs text-gray-600">
                        @foreach ([
                            ['SKU', 'Required. Numbers only (e.g. 102806178).'],
                            ['Description', 'Required. Product name/details.'],
                            ['Store Allocation', 'Required. Must be numeric and > 0.'],
                            ['Case Pack', 'Optional. Whole numbers, pipe-separated (e.g. 24 | 48).'],
                            ['SRP', 'Required. Valid price (₱ symbol allowed).'],
                            ['C/BC Scheme', 'Optional. Buy X Get Y format (e.g. 15+1).'],
                            ['PO15 Scheme', 'Optional. Same X+Y format (e.g. 10+2).'],
                            ['Discount Scheme', 'Optional. Number or % (e.g. 10 or 10%).'],
                            ['Freebie SKU', 'Optional. Digits, separated by | or / (e.g. 9413022 | 8404794).'],
                        ] as [$col, $desc])
                            <li class="leading-relaxed"><strong class="text-gray-700">{{ $col }}</strong> — {{ $desc }}</li>
                        @endforeach
                    </ol>
                </div>

                {{-- Example table --}}
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Example</p>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach (['SKU','Description','Alloc.','Case Pack','SRP','C/BC','PO15','Discount','Freebie'] as $h)
                                        <th class="px-2 py-1.5 text-left text-[9px] font-semibold uppercase tracking-wider text-gray-500">{{ $h }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-mono">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1.5 text-gray-700">102806178</td>
                                    <td class="px-2 py-1.5 text-gray-700">Bearbrand Pwdr Mlk</td>
                                    <td class="px-2 py-1.5 text-gray-700">500</td>
                                    <td class="px-2 py-1.5 text-gray-700">24</td>
                                    <td class="px-2 py-1.5 text-gray-700">15.50</td>
                                    <td class="px-2 py-1.5 text-gray-700">15+1</td>
                                    <td class="px-2 py-1.5 text-gray-700">15+2</td>
                                    <td class="px-2 py-1.5 text-gray-700">10%</td>
                                    <td class="px-2 py-1.5 text-gray-700">9413022</td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1.5 text-gray-700">8404794</td>
                                    <td class="px-2 py-1.5 text-gray-700">Lucky Me Xtra Hot</td>
                                    <td class="px-2 py-1.5 text-gray-700">600</td>
                                    <td class="px-2 py-1.5 text-gray-700">72</td>
                                    <td class="px-2 py-1.5 text-gray-700">11.50</td>
                                    <td class="px-2 py-1.5 text-gray-700">10+1</td>
                                    <td class="px-2 py-1.5 text-gray-700">8+1</td>
                                    <td class="px-2 py-1.5 text-gray-700">66</td>
                                    <td class="px-2 py-1.5 text-gray-700">–</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Status legend --}}
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ([
                            ['New', '#f0fdf4', '#86efac', '#166534'],
                            ['Update', '#eff6ff', '#bfdbfe', '#1e40af'],
                            ['Invalid', '#fef2f2', '#fca5a5', '#991b1b'],
                            ['Duplicate', '#fff7ed', '#fed7aa', '#9a3412'],
                        ] as [$label, $bg, $border, $color])
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                style="background:{{ $bg }};border:1px solid {{ $border }};color:{{ $color }}">
                                {{ $label }}
                            </span>
                        @endforeach
                        <span class="text-xs text-gray-400 self-center">— row statuses shown in preview</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script nonce="{{ $cspNonce ?? '' }}">
    window.importConfig = {
        csrfToken:   @json(csrf_token()),
        uploadUrl:   @json(route('products.import.upload')),
        skusUrl:     @json(route('products.get-skus')),
        productsUrl: @json(route('products.index')),
        currentStore: @json($currentStore),
    };
</script>

<style nonce="{{ $cspNonce ?? '' }}">
    #drop-zone.drag-over {
        border-color: #6366f1;
        background: #eef2ff;
    }
</style>

@vite(['resources/js/pages/products/import.js'])
@endsection
