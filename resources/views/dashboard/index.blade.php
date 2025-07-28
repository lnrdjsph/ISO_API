@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="mx-auto max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-gray-600 mt-1">Welcome! Choose a section below.

</p>
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Orders Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-800">Orders</h2>
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-full">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 17v-6h13V5H6l-2 2v12a2 2 0 002 2h13v-6H9z" />
                        </svg>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li><a href="{{ route('orders.index') }}" class="hover:text-blue-600">View Orders</a></li>
                    <li><a href="#" class="hover:text-blue-600">Create Order</a></li>
                    <li><a href="#" class="hover:text-blue-600">Archived Orders</a></li>
                </ul>
            </div>

            <!-- Forms Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-800">Forms</h2>
                    <div class="bg-indigo-100 text-indigo-600 p-2 rounded-full">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5.121 17.804A7.975 7.975 0 0112 15a7.975 7.975 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li><a href="{{ route('forms.sof') }}" class="hover:text-indigo-600">Sales Order Form</a></li>
                    <li><a href="{{ route('forms.rof') }}" class="hover:text-indigo-600">Request Order Form</a></li>
                    {{-- <li><a href="{{ route('forms.feedback') }}" class="hover:text-indigo-600">Feedback Form</a></li> --}}
                </ul>
            </div>

            <!-- Products Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-800">Products</h2>
                    <div class="bg-green-100 text-green-600 p-2 rounded-full">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 13V7a2 2 0 00-2-2h-4M4 13V7a2 2 0 012-2h4m4 0v6M4 17v2a2 2 0 002 2h4m8-4v2a2 2 0 01-2 2h-4m-4 0v-4" />
                        </svg>
                    </div>
                </div>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li><a href="{{ route('products.index') }}" class="hover:text-green-600">All Products</a></li>
                    <li><a href="{{ route('products.create') }}" class="hover:text-green-600">Add New Product</a></li>
                    <li><a href="#" class="hover:text-green-600">Categories</a></li>
                </ul>
            </div>

        </div>
    </div>
</div>
@endsection
