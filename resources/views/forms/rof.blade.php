@extends('layouts.app')

@section('content')
		@php
				date_default_timezone_set('Asia/Manila');
				$currentDateTime = now()->format('Y-m-d\TH:i');
		@endphp
		<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
				<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
						<div class="mb-8">
								<div class="flex items-center space-x-4">
										<div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
												<svg
														xmlns="http://www.w3.org/2000/svg"
														class="h-8 w-8 text-white"
														fill="none"
														viewBox="0 0 24 24"
														stroke="currentColor"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
														/>
												</svg>
										</div>
										<div>
												<h1 class="text-3xl font-bold text-gray-900">Request Order Form</h1>
												<p class="mt-1 text-gray-600">Fill out the form to request items or check inventory availability.</p>
										</div>
								</div>
						</div>

						<!-- Alerts -->
						@if (session('success'))
								<div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 shadow-sm">
										<p class="font-medium text-green-800">✅ {{ session('success') }}</p>
								</div>
						@endif

						@if ($errors->any())
								<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
										<p class="font-medium text-red-800">❌ Please fix the following errors:</p>
										<div class="mt-2 max-h-48 overflow-y-auto pr-2">
												<ul class="list-inside list-disc space-y-1 text-red-700">
														@foreach ($errors->all() as $error)
																<li>{{ $error }}</li>
														@endforeach
												</ul>
										</div>
								</div>
						@endif

						<form
								method="POST"
								action="{{ route('forms.sof_submit') }}"
								class="space-y-6 rounded-xl bg-white p-6 shadow-lg"
						>
								@csrf
								<!-- Request Details -->
								<section class="mb-6 rounded-lg bg-white p-4 shadow-sm">
										<h2 class="mb-4 text-lg font-semibold">Request Details</h2>
										<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
												<div>
														<label class="mb-1 block text-sm">Requesting Store</label>
														<input
																value="{{ old('requesting_store', 'Test Store') }}"
																type="text"
																name="requesting_store"
																readonly
																class="w-full cursor-not-allowed rounded border border-gray-300 bg-gray-100 p-2 text-sm"
														>
												</div>

												<div>
														<label class="mb-1 block text-sm">Requested By</label>
														<input
																value="{{ old('requested_by', 'Personnel Sample') }}"
																type="text"
																name="requested_by"
																readonly
																class="w-full cursor-not-allowed rounded border border-gray-300 bg-gray-100 p-2 text-sm"
														>
												</div>

												<div>
														<label class="mb-1 block text-sm">Date & Time of Order</label>
														<input
																value="{{ old('time_order') }}"
																type="datetime-local"
																name="time_order"
																value="{{ $currentDateTime }}"
																class="w-full rounded border border-gray-300 p-2 text-sm"
														>
												</div>
												{{-- <div>
                        <label class="block mb-1 text-sm">Channel of Order</label>
                        <select name="channel_order" class="w-full p-2 rounded border border-gray-300 text-sm">
                            <option disabled {{ old('channel_order') ? '' : 'selected' }}>Select channel</option>
                            @foreach (['E-Commerce', 'Store', 'ISO Retail', 'Wholesale'] as $option)
                                <option value="{{ $option }}" {{ old('channel_order') == $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>

                    </div> --}}
												<div>
														<label class="mb-1 block text-sm">ROF No.</label>
														<input
																value="{{ old('rof_number') }}"
																type="text"
																name="rof_number"
																class="w-full rounded border border-gray-300 p-2 text-sm"
																placeholder="Enter ROF Number"
														>
												</div>
										</div>
								</section>

								<!-- Customer Info -->
								<section class="mb-6 rounded-lg bg-white p-4 shadow-sm">
										<h2 class="mb-4 text-lg font-semibold">Customer Information</h2>
										<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
												<div>
														<label class="mb-1 block text-sm">MBC Card Number</label>
														<input
																value="{{ old('mbc_card_no') }}"
																type="text"
																name="mbc_card_no"
																maxlength="16"
																inputmode="numeric"
																pattern="\d*"
																class="w-full rounded border border-gray-300 p-2 text-sm"
																placeholder="Enter customer MBC Number"
														>
												</div>
												<div>
														<label class="mb-1 block text-sm">Customer Name</label>
														<input
																value="{{ old('customer_name') }}"
																type="text"
																name="customer_name"
																class="w-full rounded border border-gray-300 p-2 text-sm"
																placeholder="Enter customer Name"
														>
												</div>

												<div>
														<label class="mb-1 block text-sm">Contact Number</label>
														<input
																value="{{ old('contact_number') }}"
																type="tel"
																name="contact_number"
																pattern="[0-9]{11}"
																maxlength="12"
																class="w-full rounded border border-gray-300 p-2 text-sm"
																placeholder="11-digit contact number"
														>
												</div>
										</div>
								</section>
						</form>
				</div>
		</div>
@endsection
