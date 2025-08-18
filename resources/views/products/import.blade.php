@extends('layouts.app')

@section('title', 'Import')

@section('content')

		{{-- CSV Import Section --}}
		<div class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
				<div class="mx-auto px-4 sm:px-6 lg:px-8">

						<div class="mb-8">
								<div class="flex items-center justify-between">
										<div class="flex items-center space-x-4">
												<div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
														<svg
																xmlns="http://www.w3.org/2000/svg"
																class="h-8 w-8 text-white"
																fill="none"
																viewBox="0 0 24 24"
																stroke="currentColor"
																stroke-width="2"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		d="M7 16a4 4 0 0 1-.88-7.9A5 5 0 1 1 15.9 6L16 6a5 5 0 0 1 1 9.9M15 11l-3-3m0 0l-3 3m3-3v12"
																></path>
														</svg>
												</div>
												<div>
														<h1 class="text-3xl font-bold text-gray-900">Import CSV</h1>
														<p class="mt-1 text-gray-600">Import Products from CSV</p>
												</div>
										</div>
										<div class="hidden items-center space-x-2 pr-4 text-sm text-gray-500 sm:flex">
												<svg
														xmlns="http://www.w3.org/2000/svg"
														class="h-4 w-4"
														fill="none"
														viewBox="0 0 24 24"
														stroke="currentColor"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
														/>
												</svg>
												<span>Drag & drop or choose files on the drop zone.</span>
										</div>
								</div>
						</div>
						<style>
								@keyframes shimmer {
										0% {
												background-position: -1000px 0;
										}

										100% {
												background-position: 1000px 0;
										}
								}

								.skeleton {
										background: linear-gradient(90deg,
														#f0f0f0 0px,
														#e0e0e0 40px,
														#f0f0f0 80px);
										background-size: 1000px 100%;
										animation: shimmer 1.5s infinite linear;
								}

								.floating-btn {
										position: fixed;
										bottom: 20px;
										right: 20px;
										z-index: 50;
										box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
								}
						</style>
						<div
								id="skeletonLoader"
								class="mb-8 rounded-3xl border border-white/20 bg-white p-6 shadow-lg backdrop-blur-sm"
						>

								<!-- Dropzone placeholder -->
								<div class="mb-6 rounded-lg border-2 border-dashed border-gray-300 p-16">
										<div class="skeleton mx-auto h-16 w-16 rounded-full"></div>
										<div class="skeleton mx-auto mt-4 h-4 w-48 rounded"></div>
										<div class="skeleton mx-auto mt-2 h-3 w-32 rounded"></div>
								</div>

								<!-- CSV format guide placeholder -->
								<div class="mt-4 rounded-lg border border-gray-200 p-28">
										<div class="skeleton mb-3 h-4 w-56 rounded"></div>
										<div class="space-y-2">
												<div class="skeleton h-3 w-full rounded"></div>
												<div class="skeleton h-3 w-3/4 rounded"></div>
												<div class="skeleton h-3 w-4/5 rounded"></div>
										</div>
								</div>

								<!-- Buttons placeholder -->
								<div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
										<div class="skeleton h-4 w-32 rounded"></div>
										<div class="flex space-x-3">
												<div class="skeleton h-8 w-28 rounded"></div>
												<div class="skeleton h-8 w-32 rounded"></div>
										</div>
								</div>
						</div>

						<div
								class="realImportContainer mb-8 rounded-3xl border border-white/20 bg-white p-6 shadow-lg backdrop-blur-sm"
								style="display: none;"
								id="realImportContainer"
						>

								{{-- Success/Error Messages --}}

								@if (session('import_success'))
										<div class="mb-4 flex items-center space-x-2 rounded bg-green-100 p-3 text-green-700">
												<svg
														xmlns="http://www.w3.org/2000/svg"
														class="h-5 w-5"
														fill="none"
														viewBox="0 0 24 24"
														stroke="currentColor"
														stroke-width="2"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																d="M5 13l4 4L19 7"
														/>
												</svg>
												<span>{{ session('import_success') }}</span>
										</div>
								@endif

								@if (session('import_errors'))
										<div class="mb-4 rounded bg-red-100 p-3 text-red-700">
												<p class="mb-2 font-semibold">Import done, with some rows skipped due to errors:</p>
												<div class="max-h-48 overflow-y-auto pr-2">
														<ul class="list-inside list-disc space-y-1">
																@foreach (session('import_errors') as $error)
																		<li>{{ $error }}</li>
																@endforeach
														</ul>
												</div>
										</div>
								@endif

								<form
										action="{{ route('products.import.upload') }}"
										method="POST"
										enctype="multipart/form-data"
										id="csvImportForm"
								>
										@csrf

										{{-- Drop Zone --}}
										<div
												id="dropZone"
												class="group relative cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-8 text-center transition-all duration-300 ease-in-out hover:border-blue-400 hover:bg-blue-50"
										>
												<div
														id="dropContent"
														class="space-y-4"
												>
														<div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 transition-colors duration-300 group-hover:bg-blue-100">
																<svg
																		xmlns="http://www.w3.org/2000/svg"
																		class="h-8 w-8 text-gray-400 group-hover:text-blue-500"
																		fill="none"
																		viewBox="0 0 24 24"
																		stroke="currentColor"
																		stroke-width="2"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
																		/>
																</svg>
														</div>
														<div>
																<h3 class="mb-2 text-lg font-semibold text-gray-700">Drag & Drop Your CSV File</h3>
																<p class="mb-4 text-gray-500">or click to browse your files</p>
																<button
																		type="button"
																		class="rounded bg-blue-600 px-6 py-2 font-medium text-white shadow transition-colors duration-200 hover:bg-blue-700"
																>
																		Choose File
																</button>
														</div>
												</div>

												{{-- Upload Progress --}}
												<div
														id="uploadProgress"
														class="hidden space-y-4"
												>
														<div class="mx-auto h-12 w-12">
																<svg
																		class="h-12 w-12 animate-spin text-blue-600"
																		xmlns="http://www.w3.org/2000/svg"
																		fill="none"
																		viewBox="0 0 24 24"
																>
																		<circle
																				class="opacity-25"
																				cx="12"
																				cy="12"
																				r="10"
																				stroke="currentColor"
																				stroke-width="4"
																		></circle>
																		<path
																				class="opacity-75"
																				fill="currentColor"
																				d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
																		></path>
																</svg>
														</div>
														<div>
																<h3 class="mb-2 text-lg font-semibold text-gray-700">Processing your file...</h3>
																<div class="mb-2 h-2 w-full rounded-full bg-gray-200">
																		<div
																				id="progressBar"
																				class="h-2 rounded-full bg-blue-600 transition-all duration-500"
																				style="width: 0%"
																		></div>
																</div>
																<p
																		id="progressText"
																		class="text-sm text-gray-600"
																>0% complete</p>
														</div>
												</div>

												<input
														type="file"
														id="csvFile"
														name="csv_file"
														class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
														accept=".csv"
												/>
										</div>

										{{-- File Info Display --}}
										<div
												id="fileInfo"
												class="mt-4 hidden rounded-lg border border-green-200 bg-green-50 p-4"
										>
												<div class="flex items-center space-x-4">
														<div class="rounded bg-green-500 p-2">
																<svg
																		xmlns="http://www.w3.org/2000/svg"
																		class="h-5 w-5 text-white"
																		fill="none"
																		viewBox="0 0 24 24"
																		stroke="currentColor"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
																		/>
																</svg>
														</div>
														<div class="flex-1">
																<h4
																		class="font-semibold text-gray-800"
																		id="fileName"
																>File loaded successfully!</h4>
																<p
																		class="text-sm text-gray-600"
																		id="fileDetails"
																>Ready to process</p>
														</div>
														<button
																type="button"
																id="removeFile"
																class="rounded p-1 text-red-500 transition-colors hover:bg-red-50 hover:text-red-700"
														>
																<svg
																		xmlns="http://www.w3.org/2000/svg"
																		class="h-5 w-5"
																		fill="none"
																		viewBox="0 0 24 24"
																		stroke="currentColor"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M6 18L18 6M6 6l12 12"
																		/>
																</svg>
														</button>
												</div>
										</div>

										{{-- CSV Format Guide --}}
										<div class="mt-6 rounded-lg border border-gray-200 p-4">
												<div class="flex items-start space-x-3">
														<div class="rounded p-1">
																<svg
																		xmlns="http://www.w3.org/2000/svg"
																		class="h-5 w-5 text-yellow-600"
																		fill="none"
																		viewBox="0 0 24 24"
																		stroke="currentColor"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
																		/>
																</svg>
														</div>
														<div class="flex-1">
																<div class="grid gap-4 md:grid-cols-2">
																		<div>
																				<h2 class="text-md mb-2 font-semibold text-gray-600">CSV Format Requirements</h2>
																				<p class="text-md mb-2 text-gray-600">Your CSV file should have exactly 7 columns in this order:</p>
																				<ul class="space-y-1 text-sm text-gray-700">
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-blue-500"></span>
																								<span><strong>Column 1:</strong> SKU</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-green-500"></span>
																								<span><strong>Column 2:</strong> Product Description</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-green-500"></span>
																								<span><strong>Column 3:</strong> Store Allocation</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-purple-500"></span>
																								<span><strong>Column 4:</strong> Case Pack</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-orange-500"></span>
																								<span><strong>Column 4:</strong> SRP</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-indigo-500"></span>
																								<span><strong>Column 5:</strong> Cash / Bank Card Scheme</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-pink-500"></span>
																								<span><strong>Column 6:</strong> PO15 Scheme</span>
																						</li>
																						<li class="flex items-center space-x-2">
																								<span class="h-2 w-2 rounded-full bg-pink-500"></span>
																								<span><strong>Column 7:</strong> Freebie SKU</span>
																						</li>
																				</ul>
																		</div>
																		<div class="bg-white p-3">
																				<p class="mb-2 text-xs font-semibold text-gray-500">EXAMPLE FORMAT:</p>
																				<div class="overflow-x-auto">
																						<table class="min-w-full border border-gray-300 font-mono text-xs text-gray-700">
																								<thead class="bg-gray-100">
																										<tr>
																												<th class="border border-gray-300 px-2 py-1 text-left">SKU</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">Description</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">Store Allocation</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">Case Pack</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">SRP</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">C/BC Scheme</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">PO15 Scheme</th>
																												<th class="border border-gray-300 px-2 py-1 text-left">Freebie SKU</th>
																										</tr>
																								</thead>
																								<tbody>
																										<tr class="hover:bg-gray-50">
																												<td class="border border-gray-300 px-2 py-1">102806178</td>
																												<td class="border border-gray-300 px-2 py-1">Bearbrand Pwdr Mlk</td>
																												<td class="border border-gray-300 px-2 py-1">500</td>
																												<td class="border border-gray-300 px-2 py-1">24</td>
																												<td class="border border-gray-300 px-2 py-1">15.50</td>
																												<td class="border border-gray-300 px-2 py-1">15+1</td>
																												<td class="border border-gray-300 px-2 py-1">15+2</td>
																												<td class="border border-gray-300 px-2 py-1">9413022</td>
																										</tr>
																										<tr class="hover:bg-gray-50">
																												<td class="border border-gray-300 px-2 py-1">8404794</td>
																												<td class="border border-gray-300 px-2 py-1">Lucky Me Xtra Hot</td>
																												<td class="border border-gray-300 px-2 py-1">600</td>
																												<td class="border border-gray-300 px-2 py-1">72</td>
																												<td class="border border-gray-300 px-2 py-1">11.50</td>
																												<td class="border border-gray-300 px-2 py-1">10+1</td>
																												<td class="border border-gray-300 px-2 py-1">8+1</td>
																												<td class="border border-gray-300 px-2 py-1">8404794</td>
																										</tr>
																								</tbody>
																						</table>
																				</div>
																		</div>
																</div>
														</div>
												</div>
										</div>

										{{-- Action Buttons --}}
										<div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
												<a
														href="#"
														id="downloadTemplate"
														class="flex items-center space-x-2 font-medium text-blue-600 transition-colors hover:text-blue-800"
												>
														<svg
																xmlns="http://www.w3.org/2000/svg"
																class="h-5 w-5"
																fill="none"
																viewBox="0 0 24 24"
																stroke="currentColor"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		stroke-width="2"
																		d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
																/>
														</svg>
														<span>Download CSV Template</span>
												</a>

												<div class="flex space-x-3">
														<button
																type="button"
																id="previewBtn"
																class="flex hidden items-center space-x-2 rounded bg-gray-600 px-4 py-2 font-medium text-white transition-colors hover:bg-gray-700"
														>
																<svg
																		xmlns="http://www.w3.org/2000/svg"
																		class="h-5 w-5"
																		fill="none"
																		viewBox="0 0 24 24"
																		stroke="currentColor"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
																		/>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
																		/>
																</svg>
																<span>Preview Data</span>
														</button>
														<button
																type="submit"
																id="importBtn"
																class="flex hidden items-center space-x-2 rounded bg-green-600 px-6 py-2 font-medium text-white transition-colors hover:bg-green-700"
														>
																<svg
																		xmlns="http://www.w3.org/2000/svg"
																		class="h-5 w-5"
																		fill="none"
																		viewBox="0 0 24 24"
																		stroke="currentColor"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
																		/>
																</svg>
																<span>Import Products</span>
														</button>
												</div>
										</div>
								</form>
						</div>

						{{-- Data Preview Section --}}
						<div
								id="previewSection"
								class="mb-8 hidden rounded-3xl border border-white/20 bg-white p-6 shadow-lg backdrop-blur-sm"
						>
								<div class="mb-4 flex items-center justify-between">
										<h3 class="text-xl font-semibold text-gray-800">Data Preview</h3>
										<div class="flex items-center space-x-2 rounded bg-green-100 px-3 py-1">
												<svg
														xmlns="http://www.w3.org/2000/svg"
														class="h-4 w-4 text-green-600"
														fill="none"
														viewBox="0 0 24 24"
														stroke="currentColor"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
														/>
												</svg>
												<span
														id="recordCount"
														class="text-sm font-medium text-green-700"
												>0 records found</span>
										</div>
								</div>

								<div class="overflow-x-auto">
										<table class="min-w-full overflow-hidden rounded-lg border border-gray-200 bg-white">
												<thead class="bg-gray-50">
														<tr>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">SKU
																</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
																		Description</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
																		Store Allocation</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
																		Case Pack</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">SRP
																</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">CBC
																		Scheme</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
																		PO15 Scheme</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
																		Freebie SKU</th>
																<th class="border-b px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
																		Action</th>
														</tr>
												</thead>
												<tbody
														id="previewTableBody"
														class="divide-y divide-gray-200"
												>
														{{-- Preview data will be inserted here --}}
												</tbody>
										</table>
								</div>
						</div>

				</div>
		</div>

		<style>
				.drag-over {
						@apply border-blue-400 bg-blue-50 scale-105;
						box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
				}
		</style>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script>
				document.addEventListener('DOMContentLoaded', function() {
						const skeleton = document.getElementById('skeletonLoader');
						const realContent = document.querySelector('#realImportContainer'); // wrap your real form in a div with this ID

						setTimeout(() => { // Simulate loading delay
								skeleton.style.display = 'none';
								realContent.style.display = 'block';
						}, 800); // Adjust time as needed
				});

				const importBtn = document.getElementById('importBtn');

				window.addEventListener('scroll', () => {
						const rect = importBtn.getBoundingClientRect();
						const inView = rect.bottom <= window.innerHeight;

						if (!inView) {
								importBtn.classList.add('floating-btn');
						} else {
								importBtn.classList.remove('floating-btn');
						}
				});


				document.addEventListener('DOMContentLoaded', function() {
						let csvData = null;
						let previewOffset = 0;
						const PREVIEW_LIMIT = 20;

						const dropZone = document.getElementById('dropZone');
						const csvFile = document.getElementById('csvFile');
						const dropContent = document.getElementById('dropContent');
						const uploadProgress = document.getElementById('uploadProgress');
						const progressBar = document.getElementById('progressBar');
						const progressText = document.getElementById('progressText');
						const fileInfo = document.getElementById('fileInfo');
						const fileName = document.getElementById('fileName');
						const fileDetails = document.getElementById('fileDetails');
						const removeFile = document.getElementById('removeFile');
						const previewBtn = document.getElementById('previewBtn');
						const importBtn = document.getElementById('importBtn');
						const previewSection = document.getElementById('previewSection');
						const previewTableBody = document.getElementById('previewTableBody');
						const recordCount = document.getElementById('recordCount');
						const csvImportForm = document.getElementById('csvImportForm');

						// Drag and drop events
						dropZone.addEventListener('dragover', (e) => {
								e.preventDefault();
								dropZone.classList.add('drag-over');
						});

						dropZone.addEventListener('dragleave', (e) => {
								e.preventDefault();
								if (!dropZone.contains(e.relatedTarget)) {
										dropZone.classList.remove('drag-over');
								}
						});

						dropZone.addEventListener('drop', (e) => {
								e.preventDefault();
								dropZone.classList.remove('drag-over');
								const files = e.dataTransfer.files;
								if (files.length > 0) {
										csvFile.files = files;
										handleFile(files[0]);
								}
						});

						// File input change
						csvFile.addEventListener('change', (e) => {
								if (e.target.files.length > 0) {
										handleFile(e.target.files[0]);
								}
						});

						// Handle file upload
						function handleFile(file) {
								if (!file.name.toLowerCase().endsWith('.csv')) {
										alert('Please select a CSV file.');
										return;
								}

								// Show upload progress
								dropContent.classList.add('hidden');
								uploadProgress.classList.remove('hidden');

								// Simulate upload progress
								let progress = 0;
								const interval = setInterval(() => {
										progress += Math.random() * 30;
										if (progress >= 100) {
												progress = 100;
												clearInterval(interval);
												processFile(file);
										}
										progressBar.style.width = progress + '%';
										progressText.textContent = Math.round(progress) + '% complete';
								}, 200);
						}

						let existingSkus = [];

						fetch("{{ route('products.get-skus') }}")
								.then(res => res.json())
								.then(data => {
										existingSkus = data.map(s => s.toUpperCase());
								});

						function getAllUploadedSkus() {
								return csvData.map(row => row.sku.toUpperCase());
						}

						// Process CSV file
						function processFile(file) {
								const reader = new FileReader();
								reader.onload = function(e) {
										const csv = e.target.result;
										const lines = csv.split('\n').filter(line => line.trim());

										if (lines.length < 2) {
												alert('CSV file must contain at least a header and one data row.');
												resetUpload();
												return;
										}

										const expectedColumns = 8;
										const validRows = [];
										const seenSkus = new Set(); // Track SKUs within this file

										lines.slice(1).forEach((line, index) => {
												const rowNum = index + 2;
												const cols = line.split(',').map(col => col.trim().replace(/"/g, ''));

												if (cols.length !== expectedColumns) {
														validRows.push({
																sku: cols[0] || '',
																description: cols[1] || '',
																allocation_per_case: cols[2] || '',
																case_pack: cols[3] || '',
																srp: cols[4] || '',
																cash_bank_card_scheme: cols[5] || '',
																po15_scheme: cols[6] || '',
																freebie_sku: cols[7] || '',
																action: 'invalid'
														});
														return;
												}

												const [
														sku,
														description,
														allocation_per_case,
														case_pack,
														srp,
														cbc_scheme,
														po15_scheme,
														freebie_sku_raw
												] = cols;

												const freebie_sku = freebie_sku_raw.split('/')
														.map(s => s.replace(/\s+/g, ''))
														.join('/');

												let isValid = true;

												if (!sku || !/^\d+$/.test(sku)) isValid = false;
												if (!description) isValid = false;
												if (!allocation_per_case || isNaN(allocation_per_case)) isValid = false;
												if (!case_pack || isNaN(case_pack)) isValid = false;
												if (!srp || isNaN(srp.replace(/[₱,]/g, ''))) isValid = false;
												if (!cbc_scheme || !/^\d+\+\d+$/.test(cbc_scheme)) isValid = false;
												if (!po15_scheme || !/^\d+\+\d+$/.test(po15_scheme)) isValid = false;
												if (!freebie_sku || !/^\d+(\/\d+)*$/.test(freebie_sku)) isValid = false;

												let action;
												const upperSku = sku.toUpperCase();

												if (seenSkus.has(upperSku)) {
														action = 'duplicate'; // CSV internal duplicate
												} else {
														seenSkus.add(upperSku);
														action = isValid ?
																(existingSkus.includes(upperSku) ? 'update' : 'insert') :
																'invalid';
												}

												validRows.push({
														sku,
														description,
														allocation_per_case,
														case_pack,
														srp,
														cash_bank_card_scheme: cbc_scheme,
														po15_scheme,
														freebie_sku,
														action
												});
										});

										if (validRows.length === 0) {
												Swal.fire({
														icon: 'error',
														title: 'No valid data found',
														text: 'Please check the CSV file format.',
														confirmButtonColor: '#d33'
												}).then(() => {
														resetUpload();
												});
												return;
										}

										csvData = validRows;

										setTimeout(() => {
												uploadProgress.classList.add('hidden');
												fileInfo.classList.remove('hidden');
												fileName.textContent = file.name;
												fileDetails.textContent =
														`${csvData.length} products ready to import • ${(file.size / 1024).toFixed(1)} KB`;
												previewBtn.classList.remove('hidden');
												importBtn.classList.remove('hidden');
										}, 500);
								};


								reader.readAsText(file);
						}



						// Reset upload state
						function resetUpload() {
								csvData = null;
								dropContent.classList.remove('hidden');
								uploadProgress.classList.add('hidden');
								fileInfo.classList.add('hidden');
								previewBtn.classList.add('hidden');
								importBtn.classList.add('hidden');
								previewSection.classList.add('hidden');
								csvFile.value = '';
								progressBar.style.width = '0%';
								progressText.textContent = '0% complete';
						}

						// Remove file
						removeFile.addEventListener('click', resetUpload);

						// Preview data

						previewBtn.addEventListener('click', () => {
								if (!csvData) return;

								previewOffset = 0;
								previewTableBody.innerHTML = '';
								recordCount.textContent = `${csvData.length} records found`;

								const hasUpdate = csvData.some(row =>
										existingSkus.includes(row.sku.toUpperCase())
								);

								// Always render and show the preview section first
								renderPreviewChunk();
								previewSection.classList.remove('hidden');

								if (hasUpdate) {
										Swal.fire({
												icon: 'warning',
												title: 'Heads up!',
												text: 'Some SKUs already exist and will be updated. Please review the data carefully.',
												confirmButtonColor: '#f59e0b',
												confirmButtonText: 'Got it',
										}).then((result) => {
												// Only scroll AFTER the user clicks "Got it"
												if (result.isConfirmed) {
														setTimeout(() => {
																previewSection.scrollIntoView({
																		behavior: 'smooth',
																		block: 'start'
																});
														}, 500); // Small delay to let Swal cleanup finish
												}
										});
								} else {
										// No warning needed, scroll immediately
										previewSection.scrollIntoView({
												behavior: 'smooth'
										});
								}
						});

						function renderPreviewChunk() {
								const nextChunk = csvData.slice(previewOffset, previewOffset + PREVIEW_LIMIT);
								nextChunk.forEach(row => {
										const action = row.action; // <-- use validated action

										const tr = document.createElement('tr');
										tr.classList.add('hover:bg-gray-50');

										tr.innerHTML = `
											<td class="px-4 py-3 text-sm text-gray-900 font-medium">${escapeHtml(row.sku)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.description)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.allocation_per_case)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.case_pack)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.srp)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.cash_bank_card_scheme)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.po15_scheme)}</td>
											<td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.freebie_sku)}</td>
											<td class="px-4 py-3 text-xs">
											<span 
											title="${action === 'update' 
											? 'This SKU already exists in the database and will be updated' 
											: action === 'insert' 
											? 'This SKU is new and will be inserted' 
											: action === 'duplicate'
											? 'This SKU is duplicated in the uploaded CSV and will be skipped'
											: 'This row has invalid data and will be skipped'}"
											class="inline-block px-2 py-1 font-semibold rounded-full 
											${action === 'update' ? 'bg-indigo-100 text-indigo-800' : 
											action === 'insert' ? 'bg-green-100 text-green-800' :
											action === 'duplicate' ? 'bg-orange-100 text-orange-800' :
											'bg-red-100 text-red-800'}">
											${action.toUpperCase()}
											</span>
											</td>


											`;
										previewTableBody.appendChild(tr);
								});
								previewOffset += PREVIEW_LIMIT;

								const loadMoreRow = document.getElementById('loadMoreRow');
								if (loadMoreRow) loadMoreRow.remove();

								if (previewOffset < csvData.length) {
										const tr = document.createElement('tr');
										tr.id = 'loadMoreRow';
										tr.innerHTML = `
                    <td colspan="8" class="px-6 py-4 text-sm text-center">
                        <button id="loadMoreBtn" class="text-blue-600 hover:underline font-semibold">
                            Load more (${csvData.length - previewOffset} more records)
                        </button>
                    </td>
                `;
										previewTableBody.appendChild(tr);
										document.getElementById('loadMoreBtn').addEventListener('click', renderPreviewChunk);
								}
						}




						// Download template
						document.getElementById('downloadTemplate').addEventListener('click', (e) => {
								e.preventDefault();
								const csvContent =
										'SKU,Product Description,Store Allocation,Case Pack,SRP,Cash Bank Card Scheme,PO15 Scheme,Freebie SKU\n102806178,Bearbrand Pwdr Mlk 128-192/33G,500,192,11.20,15+1,15+2,9413022\n8404794,Lucky Me Pc Xtra Hot Chi72/60G,600,72,11.50,10+1,8+1,8404794';
								const blob = new Blob([csvContent], {
										type: 'text/csv'
								});
								const url = window.URL.createObjectURL(blob);
								const a = document.createElement('a');
								a.href = url;
								a.download = 'product_import_template.csv';
								a.click();
								window.URL.revokeObjectURL(url);
						});

						csvImportForm.addEventListener('submit', (e) => {
								if (!csvFile.files.length) {
										e.preventDefault();
										alert('Please select a CSV file first.');
										return;
								}

								// Show loading state
								importBtn.disabled = true;
								importBtn.innerHTML = `
                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Importing...
            `;
						});

						// Helper function to escape HTML
						function escapeHtml(text) {
								const map = {
										'&': '&amp;',
										'<': '&lt;',
										'>': '&gt;',
										'"': '&quot;',
										"'": '&#039;'
								};
								return text.replace(/[&<>"']/g, function(m) {
										return map[m];
								});
						}
				});
		</script>

@endsection
