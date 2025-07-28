@extends('layouts.app')

@section('content')
@php date_default_timezone_set('Asia/Manila'); $currentDateTime = now()->format('Y-m-d\TH:i'); @endphp
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
                    <h1 class="text-3xl font-bold text-gray-900">Request Order Form</h1>
                    <p class="text-gray-600 mt-1">Fill out the form to request items or check inventory availability.</p>
                </div>
            </div>
        </div>

            <!-- Alerts -->
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl shadow-sm">
            <p class="text-green-800 font-medium">✅ {{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl shadow-sm">
            <p class="text-red-800 font-medium">❌ Please fix the following errors:</p>
            <div class="max-h-48 overflow-y-auto pr-2 mt-2">
                <ul class="list-disc list-inside text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('forms.sof_submit') }}" class="bg-white p-6 rounded-xl shadow-lg space-y-6">
        @csrf
                <!-- Request Details -->
            <section class="bg-white p-4 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold mb-4">Request Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 text-sm">Requesting Store</label>
                        <input value="{{ old('requesting_store', 'Test Store') }}" type="text" name="requesting_store" readonly 
                            class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm">Requested By</label>
                        <input value="{{ old('requested_by', 'Personnel Sample') }}" type="text" name="requested_by"  readonly
                            class="w-full p-2 rounded border border-gray-300 bg-gray-100 cursor-not-allowed text-sm">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm">Date & Time of Order</label>
                        <input value="{{ old('time_order') }}" type="datetime-local" name="time_order" value="{{ $currentDateTime }}" 
                            class="w-full p-2 rounded border border-gray-300 text-sm">
                    </div>
                    {{-- <div>
                        <label class="block mb-1 text-sm">Channel of Order</label>
                        <select name="channel_order" class="w-full p-2 rounded border border-gray-300 text-sm">
                            <option disabled {{ old('channel_order') ? '' : 'selected' }}>Select channel</option>
                            @foreach(['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
                                <option value="{{ $option }}" {{ old('channel_order') == $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>

                    </div> --}}
                    <div>
                        <label class="block mb-1 text-sm">ROF No.</label>
                        <input value="{{ old('rof_number') }}" type="text" name="rof_number" 
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Enter ROF Number">
                    </div>
                </div>
            </section>

            <!-- Customer Info -->
            <section class="bg-white p-4 rounded-lg shadow-sm mb-6">
                <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 text-sm">MBC Card Number</label>
                        <input value="{{ old('mbc_card_no') }}" type="text" name="mbc_card_no"  maxlength="16" inputmode="numeric" pattern="\d*"
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Enter customer MBC Number">
                    </div>
                    <div>
                        <label class="block mb-1 text-sm">Customer Name</label>
                        <input value="{{ old('customer_name') }}" type="text" name="customer_name" 
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="Enter customer Name">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm">Contact Number</label>
                        <input value="{{ old('contact_number') }}" type="tel" name="contact_number" pattern="[0-9]{11}"  maxlength="12"
                            class="w-full p-2 rounded border border-gray-300 text-sm" placeholder="11-digit contact number">
                    </div>
                </div>
            </section>
        </form>
    </div>
</div>
@endsection