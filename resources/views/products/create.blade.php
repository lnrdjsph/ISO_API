@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <!-- 3D box -->
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            
                             <circle cx="12" cy="4" r="3" fill="#4361FE" stroke="#4361FE" stroke-width="2"/>
                            <!-- Plus sign drawn on the left face of the cube -->
                            <line x1="9" y1="4" x2="15" y2="4" stroke="currentColor" />
                            <line x1="12" y1="7" x2="12" y2="1" stroke="currentColor" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Add New Products</h1>
                        <p class="text-gray-600 mt-1">Enroll multiple products efficiently with our batch creation tool</p>
                    </div>
                </div>
                <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-500 pr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Fill in product details below</span>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-sm animate-fade-in">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="p-1 bg-green-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-green-800">Success!</h3>
                        <p class="text-sm text-green-700 mt-1">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl shadow-sm animate-fade-in">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="p-1 bg-red-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                        <ul class="mt-2 text-sm text-red-700 space-y-1">
                            @foreach($errors->all() as $error)
                                <li class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3"/>
                                    </svg>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Form Card -->
        <div class="bg-white backdrop-blur-sm shadow-xl rounded-2xl border border-white/20 overflow-hidden">
            <!-- Form Header -->
            <div class="bg-white px-6 py-4  ">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Product Information</h2>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Products:</span>
                        <span id="product-counter" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">1</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('products.store') }}" method="POST" class="p-6">
                @csrf

                <!-- Product Rows Container -->
                <div id="input-rows" class="space-y-6">
                    <div class="product-row group">
                        <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-100 transition-all duration-300 hover:shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                        1
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Product #1</h3>
                                </div>
                                <button type="button" class="btn-remove-row opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <!-- First Row: SKU & Product Name -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="space-y-2">
                                    <label for="sku_0" class="block text-sm font-medium text-gray-700 flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                        <span>SKU</span><span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="sku[]" id="sku_0" placeholder="Enter product SKU"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white shadow-sm hover:shadow-md"
                                        required>
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label for="name_0" class="block text-sm font-medium text-gray-700 flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span>Product Name</span><span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name[]" id="name_0" placeholder="Enter product name"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white shadow-sm hover:shadow-md"
                                        required>
                                </div>
                            </div>

                            <!-- Second Row: Additional Fields -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                <div>
                                    <label for="case_pack_0" class="block text-sm font-medium text-gray-700">Case Pack</label>
                                    <input type="number" name="case_pack[]" id="case_pack_0" placeholder="e.g. 24"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="srp_0" class="block text-sm font-medium text-gray-700">SRP</label>
                                    <input type="number" step="0.01" name="srp[]" id="srp_0" placeholder="e.g. 99.99"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="allocation_per_case_0" class="block text-sm font-medium text-gray-700">Allocation / Case</label>
                                    <input type="number" name="allocation_per_case[]" id="allocation_per_case_0" placeholder="e.g. 100"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="cbc_scheme_0" class="block text-sm font-medium text-gray-700">C/BC Scheme</label>
                                    <input type="text" name="cbc_scheme[]" id="cbc_scheme_0" placeholder="e.g. 12+1"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="po15_scheme_0" class="block text-sm font-medium text-gray-700">PO15 Scheme</label>
                                    <input type="text" name="po15_scheme[]" id="po15_scheme_0" placeholder="e.g. 5+1"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="freebie_sku_0" class="block text-sm font-medium text-gray-700">Freebie SKU</label>
                                    <input type="text" name="freebie_sku[]" id="freebie_sku_0" placeholder="e.g. FB123"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


                <!-- Add Product Button -->
                <div class="mt-8">
                    <button type="button" id="add-row-btn" 
                            class="w-full inline-flex justify-center items-center px-6 py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-all duration-200 group hover:bg-blue-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="font-medium">Add Another Product</span>
                    </button>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4 justify-between">
                        <a href="{{ route('products.index') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:-translate-x-1 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Back to Products</span>
                        </a>

                        <div class="flex space-x-4">
                            <button type="button" id="clear-all-btn"
                                    class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Clear All
                            </button>
                            
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="font-medium">Enroll Products</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Quick Tips</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            <span>SKU must be unique across all products</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            <span>Use descriptive product names for better organization</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3"/>
                            </svg>
                            <span>Click "Add Another Product" to enroll multiple products at once</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

@keyframes slide-in {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.animate-slide-in {
    animation: slide-in 0.4s ease-out;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputRows = document.getElementById('input-rows');
    const addRowBtn = document.getElementById('add-row-btn');
    const clearAllBtn = document.getElementById('clear-all-btn');
    const productCounter = document.getElementById('product-counter');

    let rowCount = 1;

    // Add new product row
    addRowBtn.addEventListener('click', () => {
        rowCount++;
        const newRow = document.createElement('div');
        newRow.classList.add('product-row', 'group', 'animate-slide-in');
        newRow.innerHTML = `
            <div class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-xl p-6 border border-blue-100 transition-all duration-300 hover:shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            ${rowCount}
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Product #${rowCount}</h3>
                    </div>
                    <button type="button" class="btn-remove-row opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label for="sku_${rowCount - 1}" class="block text-sm font-medium text-gray-700 flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            <span>SKU</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="sku[]" id="sku_${rowCount - 1}" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white shadow-sm hover:shadow-md" 
                               placeholder="Enter product SKU" required>
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label for="name_${rowCount - 1}" class="block text-sm font-medium text-gray-700 flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Product Name</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name[]" id="name_${rowCount - 1}" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white shadow-sm hover:shadow-md" 
                               placeholder="Enter product name" required>
                    </div>
                </div>
            </div>
        `;

        inputRows.appendChild(newRow);
        updateProductNumbers();
        updateRemoveButtons();
        
        // Focus on the first input of the new row
        setTimeout(() => {
            newRow.querySelector('input[name="sku[]"]').focus();
        }, 100);
    });

    // Remove product row
    inputRows.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-row')) {
            const row = e.target.closest('.product-row');
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                row.remove();
                updateProductNumbers();
                updateRemoveButtons();
            }, 200);
        }
    });

    // Clear all products (keep at least one)
    clearAllBtn.addEventListener('click', function () {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will clear all product fields.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear all',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const rows = inputRows.querySelectorAll('.product-row');
                for (let i = 1; i < rows.length; i++) {
                    rows[i].remove();
                }

                const firstRow = inputRows.querySelector('.product-row');
                firstRow.querySelectorAll('input').forEach(input => input.value = '');

                rowCount = 1;
                updateProductNumbers();
                updateRemoveButtons();
            }
        });
    });



    function updateProductNumbers() {
        const rows = inputRows.querySelectorAll('.product-row');
        rows.forEach((row, index) => {
            const number = index + 1;
            const numberBadge = row.querySelector('.w-8.h-8');
            const title = row.querySelector('h3');
            
            if (numberBadge) numberBadge.textContent = number;
            if (title) title.textContent = `Product #${number}`;
        });
        
        productCounter.textContent = rows.length;
    }

    function updateRemoveButtons() {
        const removeButtons = inputRows.querySelectorAll('.btn-remove-row');
        const totalRows = removeButtons.length;
        
        removeButtons.forEach(btn => {
            if (totalRows === 1) {
                btn.style.display = 'none';
            } else {
                btn.style.display = 'block';
            }
        });
    }

    // Form validation enhancement
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const skus = Array.from(document.querySelectorAll('input[name="sku[]"]')).map(input => input.value.trim());
        const duplicates = skus.filter((sku, index) => skus.indexOf(sku) !== index);
        
        if (duplicates.length > 0) {
            e.preventDefault();
            alert('Duplicate SKUs found. Please ensure all SKUs are unique.');
            return false;
        }
    });

    // Initialize
    updateRemoveButtons();
});
</script>
@endsection