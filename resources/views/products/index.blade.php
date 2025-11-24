@extends('layouts.app')

@section('title', 'Product List')

@section('content')
    <div class="">
        <div class="mx-auto max-w-full overflow-hidden px-4 sm:px-6 lg:px-8">

            <!-- Actual Content (Initially Hidden) -->
            <div
                id="actual-content"
                class="">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-md bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg font-bold text-gray-900">Product List</h1>
                                <p class="text-sm text-gray-600">Manage and explore your product inventory</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="mx-auto w-full max-w-md rounded-lg">
                                <form
                                    id="updateAllocationsForm"
                                    action="{{ route('update.allocations') }}"
                                    method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        id="updateButton"
                                        class="mt-2 flex transform items-center rounded-xl border border-blue-500/20 bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-2 font-semibold text-white transition-all hover:-translate-y-0.5 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50">
                                        <span id="buttonText" class="text-sm">Update Allocations</span>
                                    </button>
                                </form>
                            </div>

                            <!-- Full Page Loader -->
                            <div
                                id="pageLoader"
                                class="fixed inset-0 left-[-16px] z-50 flex hidden items-center justify-center bg-black/50 backdrop-blur-sm">
                                <div class="mx-4 flex w-full max-w-md flex-col items-center rounded-2xl bg-white px-8 py-6 shadow-2xl">
                                    <!-- Spinner -->
                                    <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>

                                    <!-- Title -->
                                    <p id="loaderTitle" class="mt-4 text-lg font-semibold text-gray-700">
                                        Updating the following fields:
                                    </p>

                                    <!-- List -->
                                    <ul class="mt-3 space-y-1 text-gray-600">
                                        <li class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                            WMS Actual Allocation
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                            WMS Virtual Allocation
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                            Case Pack
                                        </li>
                                    </ul>

                                    <!-- Progress Message -->
                                    <div class="mt-4 w-full">
                                        <p id="loaderMessage" class="text-center text-sm text-gray-600">
                                            Please wait, this may take a few minutes...
                                        </p>

                                        <!-- Progress Details -->
                                        <div id="progressDetails" class="mt-3 hidden rounded-lg bg-blue-50 p-3">
                                            <p class="font-mono text-xs text-blue-800" id="progressText"></p>
                                        </div>
                                    </div>

                                    <!-- Progress Bar (Optional) -->
                                    <div class="mt-4 w-full">
                                        <div class="h-1 w-full overflow-hidden rounded-full bg-gray-200">
                                            <div id="progressBar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.getElementById('updateAllocationsForm');
                                const button = document.getElementById('updateButton');
                                const loader = document.getElementById('pageLoader');
                                const loaderTitle = document.getElementById('loaderTitle');
                                const loaderMessage = document.getElementById('loaderMessage');
                                const progressDetails = document.getElementById('progressDetails');
                                const progressText = document.getElementById('progressText');
                                const progressBar = document.getElementById('progressBar');

                                let checkStatusInterval = null;
                                let progressPercentage = 0;

                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();

                                    Swal.fire({
                                        title: 'Warning',
                                        html: '<p>This process may take several minutes and will update:</p>' +
                                            '<ul class="text-left mt-2 space-y-1">' +
                                            '<li>• WMS Actual Allocation</li>' +
                                            '<li>• WMS Virtual Allocation</li>' +
                                            '<li>• Case Pack Data</li>' +
                                            '</ul>' +
                                            '<p class="mt-2">Are you sure you want to proceed?</p>',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Yes, proceed',
                                        cancelButtonText: 'Cancel',
                                        confirmButtonColor: '#2563eb',
                                        cancelButtonColor: '#6b7280'
                                    }).then((result) => {
                                        if (!result.isConfirmed) return;

                                        startUpdate();
                                    });
                                });

                                function startUpdate() {
                                    // Disable button
                                    button.disabled = true;

                                    // Show loader
                                    loader.classList.remove('hidden');
                                    loaderTitle.textContent = 'Initializing Update...';
                                    loaderMessage.textContent = 'Starting allocation update process...';
                                    progressBar.style.width = '10%';
                                    progressPercentage = 10;

                                    // Trigger update
                                    fetch(form.action, {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json'
                                            }
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.status === 'started' || data.status === 'running') {
                                                loaderTitle.textContent = 'Update in Progress';
                                                loaderMessage.textContent = data.message || 'Processing...';
                                                progressBar.style.width = '25%';
                                                progressPercentage = 25;

                                                // Start polling for status
                                                startPolling();
                                            } else if (data.status === 'error') {
                                                showError(data.message);
                                            }
                                        })
                                        .catch(err => {
                                            console.error('Start error:', err);
                                            showError('Failed to start allocation update. Please try again.');
                                        });
                                }

                                function startPolling() {
                                    // Poll every 3 seconds
                                    checkStatusInterval = setInterval(checkStatus, 3000);
                                }

                                function stopPolling() {
                                    if (checkStatusInterval) {
                                        clearInterval(checkStatusInterval);
                                        checkStatusInterval = null;
                                    }
                                }

                                function checkStatus() {
                                    fetch('{{ route('update.allocations.status') }}')
                                        .then(res => res.json())
                                        .then(data => {
                                            console.log('Status:', data);

                                            if (data.status === 'done') {
                                                stopPolling();
                                                handleCompletion(data);
                                            } else if (data.status === 'running') {
                                                updateProgress(data);
                                            } else if (data.status === 'pending') {
                                                loaderMessage.textContent = data.message;
                                                // Slowly increase progress while pending
                                                if (progressPercentage < 30) {
                                                    progressPercentage += 2;
                                                    progressBar.style.width = progressPercentage + '%';
                                                }
                                            } else if (data.status === 'error') {
                                                stopPolling();
                                                showError(data.message);
                                            }
                                        })
                                        .catch(err => {
                                            console.error('Status check error:', err);
                                            // Don't stop polling on network errors, might be temporary
                                        });
                                }

                                function updateProgress(data) {
                                    loaderMessage.textContent = data.message || 'Processing...';

                                    // Show progress details if available
                                    if (data.progress) {
                                        if (data.progress.current_step) {
                                            progressDetails.classList.remove('hidden');
                                            progressText.textContent = data.progress.current_step;
                                        }

                                        // Use actual percentage if available
                                        if (data.progress.percentage !== undefined) {
                                            progressPercentage = data.progress.percentage;
                                            progressBar.style.width = progrewms_allocation_per_casessPercentage + '%';

                                            // Update title with percentage
                                            loaderTitle.textContent = `Update in Progress (${Math.round(progressPercentage)}%)`;
                                        } else {
                                            // Fallback: Gradually increase progress bar
                                            if (progressPercentage < 90) {
                                                progressPercentage += Math.random() * 5;
                                                progressBar.style.width = Math.min(progressPercentage, 90) + '%';
                                            }
                                        }
                                    }
                                }

                                function handleCompletion(data) {
                                    // Complete the progress bar
                                    progressBar.style.width = '100%';
                                    loaderTitle.textContent = 'Update in Progress (100%)';

                                    // Hide loader after animation
                                    setTimeout(() => {
                                        loader.classList.add('hidden');
                                        button.disabled = false;

                                        // Build summary message
                                        let summaryHtml = '<p class="mb-2">Update completed successfully!</p>';

                                        if (data.summary) {
                                            summaryHtml += '<div class="text-left bg-green-50 p-3 rounded-lg mt-3">';
                                            summaryHtml += '<p class="text-sm font-semibold mb-2">Summary:</p>';
                                            summaryHtml += '<ul class="text-sm space-y-1">';

                                            if (data.summary.processed_skus || data.summary.total_skus) {
                                                const processed = data.summary.processed_skus || data.summary.total_skus;
                                                summaryHtml += `<li>✓ SKUs Processed: <strong>${processed}</strong></li>`;
                                            }
                                            if (data.summary.warehouse_code) {
                                                summaryHtml += `<li>📦 Warehouse: <strong>${data.summary.warehouse_code}</strong></li>`;
                                            }
                                            if (data.summary.started_at && data.summary.completed_at) {
                                                const start = new Date(data.summary.started_at);
                                                const end = new Date(data.summary.completed_at);
                                                const durationMs = end - start;
                                                const minutes = Math.floor(durationMs / 60000);
                                                const seconds = Math.floor((durationMs % 60000) / 1000);
                                                summaryHtml += `<li>⏱ Duration: <strong>${minutes}m ${seconds}s</strong></li>`;
                                            }

                                            summaryHtml += '</ul></div>';
                                        }

                                        Swal.fire({
                                            title: 'Completed!',
                                            html: summaryHtml,
                                            icon: 'success',
                                            confirmButtonText: 'OK',
                                            confirmButtonColor: '#10b981'
                                        }).then(() => {
                                            window.location.reload();
                                        });

                                        // Reset progress
                                        progressPercentage = 0;
                                        progressBar.style.width = '0%';
                                        progressDetails.classList.add('hidden');
                                        loaderTitle.textContent = 'Updating the following fields:';
                                    }, 500);
                                }

                                function showError(message) {
                                    loader.classList.add('hidden');
                                    button.disabled = false;

                                    Swal.fire({
                                        title: 'Error',
                                        text: message || 'Failed to update allocations.',
                                        icon: 'error',
                                        confirmButtonText: 'OK',
                                        confirmButtonColor: '#ef4444'
                                    });

                                    // Reset progress
                                    progressPercentage = 0;
                                    progressBar.style.width = '0%';
                                    progressDetails.classList.add('hidden');
                                }

                                // Check if update is already running on page load
                                checkStatus();
                            });
                        </script>

                        <style>
                            @keyframes shimmer {
                                0% {
                                    background-position: -1000px 0;
                                }

                                100% {
                                    background-position: 1000px 0;
                                }
                            }

                            #progressBar {
                                background: linear-gradient(90deg, #3b82f6 0%, #2563eb 50%, #3b82f6 100%);
                                background-size: 1000px 100%;
                                animation: shimmer 2s infinite;
                            }
                        </style>
                    </div>
                </div>

                <!-- Search and Actions Bar -->
                <div class="mb-4">
                    <div class="flex flex-nowrap items-start justify-between gap-4">

                        <form
                            method="GET"
                            action="{{ route('products.index') }}"
                            class="w-full max-w-lg">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    name="query"
                                    id="product-search"
                                    value="{{ request('query') }}"
                                    autocomplete="off"
                                    class="w-full rounded-2xl border border-gray-200/60 bg-white/60 py-2 pl-12 pr-4 text-gray-700 placeholder-gray-400 backdrop-blur-sm transition-all duration-200 hover:bg-white/80 hover:shadow-lg focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    placeholder="Search by SKU or product description..." />
                                <ul
                                    id="product-list"
                                    class="absolute z-[999] mt-1 hidden w-full rounded-xl bg-white shadow-xl"></ul>

                            </div>
                        </form>

                        <!-- Bulk Actions Bar -->
                        <div
                            id="bulk-actions-bar"
                            class="hidden">
                            <div class="w-full rounded-2xl border border-blue-500/20 bg-gradient-to-r from-blue-600 to-indigo-600 p-1">
                                <div class="flex items-center justify-between overflow-x-auto whitespace-nowrap px-4 py-2 text-white">

                                    <!-- Count -->
                                    <div class="flex items-center gap-2">
                                        <svg
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span
                                            id="selected-count"
                                            class="font-semibold">0</span>
                                        <span>items selected</span>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="flex items-center gap-3">
                                        {{-- <button
																						id="bulk-edit-btn"
																						class="flex items-center rounded-xl px-3 font-medium hover:underline"
																				>
																						<svg
																								class="mr-1 h-4 w-4"
																								fill="none"
																								stroke="currentColor"
																								viewBox="0 0 24 24"
																						>
																								<path
																										stroke-linecap="round"
																										stroke-linejoin="round"
																										stroke-width="2"
																										d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
																								/>
																						</svg>
																						Bulk Edit
																				</button> --}}

                                        <button
                                            id="bulk-archive-btn"
                                            class="flex items-center rounded-xl px-3 font-medium hover:underline">
                                            <svg
                                                class="mr-1 h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 8l4 4 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Archive
                                        </button>

                                        <button
                                            id="clear-selection-btn"
                                            class="flex items-center rounded-xl px-2 hover:underline">
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="flex flex-shrink-0 items-center gap-3">
                            <!-- Filter -->
                            <button class="flex items-center rounded-xl px-4 py-2 text-gray-600 transition hover:bg-gray-100/60 hover:text-gray-800">
                                <svg
                                    class="mr-2 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filter
                            </button>

                            <!-- Export -->
                            <a
                                href="{{ route('products.export', request()->query()) }}"
                                class="flex items-center rounded-xl px-4 py-2 text-gray-600 transition hover:bg-gray-100/60 hover:text-gray-800">
                                <svg
                                    class="mr-2 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export
                            </a>

                            <!-- Add Product -->
                            <a
                                href="{{ route('products.create') }}"
                                class="flex transform items-center rounded-2xl border border-blue-500/20 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 font-semibold text-white transition-all hover:-translate-y-0.5 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl">
                                <svg
                                    class="mr-2 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Product
                            </a>
                        </div>

                    </div>
                </div>

                <!-- Modern Product Table -->
                <div class="overflow-hidden rounded-xl border border-white/20 bg-white shadow-lg backdrop-blur-sm">
                    <div class="overflow-x-auto">
                        <table class="table-sm w-full table-auto">
                            @php
                                $currentSort = request('sort', 'sku');
                                $currentDirection = request('direction', 'asc');

                                if (!function_exists('sortRoute')) {
                                    function sortRoute($column)
                                    {
                                        $direction = request('direction', 'asc') === 'asc' ? 'desc' : 'asc';

                                        return route(
                                            'products.index',
                                            array_merge(request()->except(['page', 'direction', 'sort']), [
                                                'sort' => $column,
                                                'direction' => request('sort') === $column ? $direction : 'asc',
                                            ]),
                                        );
                                    }
                                }
                            @endphp
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-800 to-gray-700 text-white">
                                    <th class="rounded-tl-xl px-3 py-2 text-left text-sm uppercase">
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                id="select-all"
                                                class="h-4 w-4 rounded border-gray-300 bg-gray-100 focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">
                                        <a
                                            href="{{ sortRoute('sku') }}"
                                            class="group flex items-center space-x-2">
                                            <span>SKU</span>
                                            @if ($currentSort === 'sku')
                                                <svg
                                                    class="{{ $currentDirection === 'asc' ? '' : 'rotate-180' }} h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @else
                                                <svg
                                                    class="group-hover:400 h-4 w-4 opacity-60"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">
                                        <a
                                            href="{{ sortRoute('description') }}"
                                            class="group flex items-center space-x-2">
                                            <span>Product description</span>
                                            @if ($currentSort === 'description')
                                                <svg
                                                    class="{{ $currentDirection === 'asc' ? '' : 'rotate-180' }} h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @else
                                                <svg
                                                    class="group-hover:400 h-4 w-4 opacity-60"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">Sub-Department</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">WMS Virtual Inventory</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">Store Allocation</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">Case Pack</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">SRP</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">C/BC Scheme</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">PO15 Scheme</th>
                                    <th class="px-3 py-3 text-left text-xs uppercase">Discount Scheme</th>
                                    <th class="rounded-tr-xl px-3 py-3 text-left text-xs uppercase">Freebie SKU</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/60">
                                @forelse ($products as $product)
                                    <tr
                                        class="animate-fade-in product-row group opacity-0 transition-all duration-200 hover:bg-indigo-100/60"
                                        data-product-id="{{ $product->id }}">
                                        <td class="whitespace-nowrap px-3 py-3">
                                            <input
                                                type="checkbox"
                                                class="product-checkbox h-4 w-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                                value="{{ $product->id }}"
                                                id="product-{{ $product->id }}">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            <div class="flex items-center">
                                                <span class="font-mono text-xs font-semibold text-gray-800">
                                                    {{ $product->sku }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="relative inline-block w-full">
                                                <div class="peer block max-w-[150px] overflow-hidden text-ellipsis whitespace-nowrap text-xs font-semibold text-gray-800">
                                                    {{ $product->description }}
                                                </div>
                                                <div
                                                    class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded bg-gray-800 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
                                                    {{ $product->description }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="relative inline-block w-full">
                                                <div class="peer block max-w-[100px] overflow-hidden text-ellipsis whitespace-nowrap text-xs font-semibold text-gray-800">
                                                    {{ $product->department_code }} - {{ $product->department }}
                                                </div>
                                                <div
                                                    class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded bg-gray-800 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
                                                    {{ $product->department_code }} - {{ $product->department }}
                                                </div>
                                            </div>
                                        </td>


                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                {{ $product->warehouse_allocation ?? '-' }}
                                            </span>
                                        </td>


                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                {{ $product->allocation_per_case ?? '-' }}
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                {{ $product->case_pack ?? '-' }}
                                            </span>
                                        </td>


                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                ₱{{ number_format($product->srp ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                {{ !empty($product->cash_bank_card_scheme) ? $product->cash_bank_card_scheme : '-' }}
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                {{ !empty($product->po15_scheme) ? $product->po15_scheme : '-' }}
                                            </span>
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-3">
                                            <span class="inline-flex items-center rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                {{ !empty($product->discount_scheme) ? $product->discount_scheme : '-' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="relative inline-block w-full">
                                                <div
                                                    class="peer inline-flex max-w-full items-center truncate rounded-full border border-purple-200/60 bg-purple-100/60 px-3 py-1 text-xs font-medium text-purple-800">
                                                    {{ !empty($product->freebie_sku) ? $product->freebie_sku : '-' }}
                                                </div>
                                                <div
                                                    class="pointer-events-none absolute left-0 top-1/2 z-50 ml-2 w-max -translate-x-full -translate-y-1/2 whitespace-normal break-words rounded bg-gray-800 px-3 py-2 text-xs text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
                                                    {{ !empty($product->freebie_description) ? $product->freebie_description : 'No description found' }}
                                                </div>
                                            </div>
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="12"
                                            class="px-6 py-8 text-center text-gray-500">
                                            No products found.
                                            <div class="mt-4 inline-flex items-center justify-center">
                                                <select
                                                    id="no-products-action"
                                                    onchange="if(this.value) window.location.href=this.value"
                                                    class="m-0 cursor-pointer appearance-none bg-transparent p-1 text-sm font-medium text-blue-600 hover:underline focus:outline-none focus:ring-0"
                                                    style="border:none; outline:none; width:auto;">
                                                    <option
                                                        value=""
                                                        selected
                                                        disabled>Click here to . . .</option>
                                                    <option value="{{ route('products.create') }}">Add New Product</option>
                                                    <option value="{{ route('products.import.show') }}">Import Products</option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>

                                    <style>
                                        #no-products-action {
                                            -webkit-appearance: none;
                                            -moz-appearance: none;
                                            appearance: none;
                                            background: transparent;
                                            border: none;
                                            cursor: pointer;
                                            padding-right: 1em;
                                            /* optional, space for text */
                                        }
                                    </style>
                                    <script>
                                        document.getElementById('no-products-action').addEventListener('change', function() {
                                            const url = this.value;
                                            if (url) {
                                                window.location.href = url;
                                            }
                                        });
                                    </script>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Enhanced Pagination -->
                    <div class="flex items-center justify-between rounded-b-xl bg-white px-3 py-3 backdrop-blur-sm">

                        <!-- Rows per page -->
                        <form
                            method="GET"
                            action="{{ route('products.index') }}"
                            class="flex items-center space-x-2">
                            <div class="flex items-center overflow-hidden rounded-lg">
                                <span class="px-3 text-sm text-gray-600">
                                    Rows
                                </span>
                                <select
                                    name="per_page"
                                    id="perPage"
                                    class="border-0 px-10 py-1 text-sm ring-0"
                                    onchange="this.form.submit()">
                                    @foreach ([10, 25, 50, 100] as $size)
                                        <option
                                            value="{{ $size }}"
                                            {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>


                        <!-- Laravel pagination -->
                        <div>
                            {{ $products->withPath(route('products.index'))->appends(['per_page' => request('per_page')])->onEachSide(1)->links() }}
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Bulk Edit Modal -->
    <div
        id="bulk-edit-modal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="flex items-center text-2xl font-bold text-gray-900">
                            <svg
                                class="mr-3 h-6 w-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Bulk Edit Products
                        </h3>
                        <button
                            id="close-bulk-edit"
                            class="text-gray-400 transition-colors duration-200 hover:text-gray-600">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-gray-600">Update multiple products at once. Leave fields empty to keep existing values.</p>
                </div>

                <form
                    id="bulk-edit-form"
                    class="space-y-6 p-6">
                    @csrf
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Case Pack</label>
                            <input
                                type="number"
                                name="case_pack"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">SRP (₱)</label>
                            <input
                                type="number"
                                step="0.01"
                                name="srp"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Allocation per Case</label>
                            <input
                                type="number"
                                name="allocation_per_case"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">C/BC Scheme</label>
                            <input
                                type="text"
                                name="cash_bank_card_scheme"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">PO15 Scheme</label>
                            <input
                                type="text"
                                name="po15_scheme"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Freebie SKU</label>
                            <input
                                type="text"
                                name="freebie_sku"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                        <div class="text-sm text-gray-600">
                            <span id="bulk-edit-selected-count">0</span> products will be updated
                        </div>
                        <div class="flex space-x-3">
                            <button
                                type="button"
                                id="cancel-bulk-edit"
                                class="rounded-xl border border-gray-300 px-6 py-3 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-indigo-700">
                                Update Products
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Archive Confirmation Modal -->
    <div
        id="bulk-archive-modal"
        class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl">
                <div class="p-6">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                        <svg
                            class="h-8 w-8 text-red-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>

                    <h3 class="mb-2 text-center text-xl font-bold text-gray-900">Archive Products</h3>

                    <p class="mb-4 text-center text-gray-600">
                        Are you sure you want to archive
                        <span
                            id="archive-selected-count"
                            class="font-semibold text-red-600">0</span>
                        selected products? This action can be undone later.
                    </p>

                    <!-- Optional archive reason -->
                    <div class="mb-4">
                        <label
                            for="archive-reason-input"
                            class="mb-1 block text-sm font-medium text-gray-700">
                            Archive Reason <span class="text-xs text-gray-400">(optional)</span>
                        </label>
                        <textarea
                            id="archive-reason-input"
                            class="w-full resize-none rounded-xl border px-4 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-400"
                            rows="3"
                            maxlength="500"
                            placeholder="Enter reason for archiving (max 500 characters)..."></textarea>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            type="button"
                            id="cancel-bulk-archive"
                            class="flex-1 rounded-xl border border-gray-300 px-4 py-3 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button
                            type="button"
                            id="confirm-bulk-archive"
                            class="flex-1 rounded-xl bg-red-600 px-4 py-3 font-medium text-white transition-colors duration-200 hover:bg-red-700">
                            Archive Products
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedProducts = new Set();


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
                    url: '{{ route('products.bulk-update') }}',
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
            $('#bulk-archive-btn').click(function() {
                if (selectedProducts.size === 0) {
                    alert('Please select products first');
                    return;
                }
                $('#bulk-archive-modal').removeClass('hidden').addClass('flex');
            });

            // Cancel modal
            $('#cancel-bulk-archive').click(function() {
                $('#bulk-archive-modal').addClass('hidden').removeClass('flex');
            });

            // Confirm archive
            $('#confirm-bulk-archive').click(function() {
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
                    url: '{{ route('products.bulk-archive') }}',
                    method: 'POST',
                    data: {
                        product_ids: productIds,
                        archive_reason: archiveReason
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message, 'success');
                            $('#bulk-archive-modal').addClass('hidden').removeClass('flex');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification(response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'An error occurred.';
                        showNotification(message, 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });

            // Notification utility
            function showNotification(message, type = 'info') {
                const bgColor = type === 'success' ? 'bg-green-500' :
                    type === 'error' ? 'bg-red-500' : 'bg-blue-500';

                const notification = $(`
                                    <div class="fixed top-4 right-4 z-50 ${bgColor} text-white px-3 py-3 rounded-2xl shadow-lg transform translate-x-full transition-transform duration-300">
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
            $('#bulk-edit-modal, #bulk-archive-modal').click(function(e) {
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
                                            <li class="px-3 py-3 text-gray-600 flex items-center">
                                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Searching products...
                                            </li>
                                        `);

                debounceTimeout = setTimeout(() => {
                    $.ajax({
                        url: '{{ route('products.search') }}',
                        data: {
                            query: query
                        },
                        success: function(data) {
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
                                                                    <li class="px-3 py-3 text-gray-500 flex items-center justify-center">
                                                                        <div class="text-center">
                                                                            <p class="text-sm">No products found</p>
                                                                            <p class="text-xs text-gray-400 mt-1">Try adjusting your search terms</p>
                                                                        </div>
                                                                    </li>
                                                                `);
                            } else {
                                filtered.forEach((item, index) => {
                                    const listItem = $('<li>')
                                        .addClass(
                                            'px-3 py-3 hover:bg-gray-50/80 cursor-pointer product-item transition-all duration-200 border-b border-gray-100/60 last:border-b-0 opacity-0 translate-y-2'
                                        )
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
                            <li class="px-3 py-3 text-red-600 flex items-center">
                                Search failed. Please try again.
                            </li>
                        `);
                        }
                    });
                }, 300);
            }

            $('#product-search').on('keyup', function() {
                clearTimeout(debounceTimeout);

                const query = $(this).val().toLowerCase();
                if (query.length >= 2) {
                    performSearch(query);
                } else {
                    $('#product-list').empty().addClass('hidden');
                }
            });

            $('#product-search').on('focus', function() {
                const query = $(this).val().toLowerCase();
                if (query.length >= 2) {
                    performSearch(query);
                }
            });

            $(document).on('click', '.product-item', function() {
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

            $(document).on('click', function(e) {
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

        document.addEventListener('DOMContentLoaded', () => {
            // Restore scroll position
            const scrollY = localStorage.getItem('scrollPosition');
            if (scrollY) {
                window.scrollTo(0, parseInt(scrollY));
                localStorage.removeItem('scrollPosition'); // optional, clear after restoring
            }

            // Save scroll position before navigating away
            document.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', () => {
                    localStorage.setItem('scrollPosition', window.scrollY);
                });
            });
        });
    </script>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.6s ease-out forwards;
        }

        /* Skeleton pulse animation */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
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
