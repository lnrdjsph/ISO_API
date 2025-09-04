@extends('layouts.app')

@section('content')
		<div class="to-white-50 via-white-50 bg-gradient-to-br from-slate-50 py-8">
				<div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

						<!-- Header -->
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
																d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0
																																																						11-2 0 1 1 0 012 0zm8 0a1 1 0
																																																						11-2 0 1 1 0 012 0z"
														/>
												</svg>
										</div>
										<div>
												<h1 class="text-3xl font-bold text-gray-900">Inventory Export</h1>
												<p class="mt-1 text-gray-600">Upload your SKU CSV, choose stores, and export in your preferred format.</p>
										</div>
								</div>
						</div>

						<!-- Form Card -->
						<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
								<div class="bg-gradient-to-r from-gray-600 to-gray-500 px-6 py-4">
										<h2 class="text-lg font-semibold text-white">Upload & Export</h2>
								</div>

								<div class="p-6">
										<form
												method="POST"
												enctype="multipart/form-data"
												action="{{ route('others.inventory.export') }}"
												class="space-y-6"
										>
												@csrf

												<div
														id="drop-zone"
														class="group relative flex w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-gray-700 transition-all duration-300 ease-in-out hover:border-gray-500 hover:bg-gray-100 hover:shadow-lg"
												>
														<!-- Animated Icon -->
														<svg
																class="h-12 w-12 text-gray-400 transition-transform duration-300 ease-in-out group-hover:scale-110 group-hover:text-gray-600"
																fill="none"
																stroke="currentColor"
																stroke-width="2"
																viewBox="0 0 24 24"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		d="M7 16V4m0 0l-4 4m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"
																/>
														</svg>

														<p class="mt-3 text-sm font-medium">Drag & drop your <span class="font-semibold text-gray-800">.csv</span> file here</p>
														<p class="text-xs text-gray-500">or click to browse</p>

														<!-- Hidden File Input -->
														<input
																type="file"
																name="sku_csv"
																accept=".csv"
																required
																class="absolute inset-0 cursor-pointer opacity-0"
														>
												</div>
												<!-- File Preview -->
												<div
														id="file-info"
														class="mt-3 hidden text-sm text-gray-700"
												></div>

												<!-- Store Codes -->
												<div>
														<label class="mb-1 block text-sm font-medium text-gray-700">Store Codes (comma-separated):</label>
														<input
																type="text"
																name="store_codes"
																placeholder="e.g. 1001,1002,1003"
																required
																class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-gray-400 focus:ring-2 focus:ring-gray-400"
														>
												</div>

												<!-- Export Format -->
												<div>
														<span class="mb-2 block text-sm font-medium text-gray-700">Export Format:</span>
														<div class="flex items-center space-x-6">
																<label class="flex items-center space-x-2">
																		<input
																				type="radio"
																				name="export_format"
																				value="csv"
																				checked
																				class="text-gray-600 focus:ring-gray-500"
																		>
																		<span class="text-sm text-gray-700">CSV (Pivoted)</span>
																</label>
																<label class="flex items-center space-x-2">
																		<input
																				type="radio"
																				name="export_format"
																				value="excel"
																				class="text-gray-600 focus:ring-gray-500"
																		>
																		<span class="text-sm text-gray-700">Excel (Separate Sheets)</span>
																</label>
														</div>
												</div>

												<!-- Submit Button -->
												<div class="pt-4">
														<button
																type="submit"
																class="w-full rounded-lg bg-gray-600 py-2.5 font-semibold text-white shadow-md transition hover:bg-gray-700"
														>
																Upload & Export
														</button>
												</div>
										</form>
								</div>
						</div>

				</div>
		</div>

		<script>
				const dropZone = document.getElementById("drop-zone");
				const fileInput = dropZone.querySelector("input[type='file']");
				const fileInfo = document.getElementById("file-info");

				// Highlight on drag
				dropZone.addEventListener("dragover", (e) => {
						e.preventDefault();
						dropZone.classList.add("border-gray-500", "bg-gray-100");
				});

				dropZone.addEventListener("dragleave", () => {
						dropZone.classList.remove("border-gray-500", "bg-gray-100");
				});

				dropZone.addEventListener("drop", (e) => {
						e.preventDefault();
						dropZone.classList.remove("border-gray-500", "bg-gray-100");
						fileInput.files = e.dataTransfer.files;

						if (fileInput.files.length > 0) {
								showFileInfo(fileInput.files[0]);
						}
				});

				fileInput.addEventListener("change", () => {
						if (fileInput.files.length > 0) {
								showFileInfo(fileInput.files[0]);
						}
				});

				function showFileInfo(file) {
						fileInfo.classList.remove("hidden");
						fileInfo.innerHTML = `
            <div class="flex items-center gap-2 animate-fade-in">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>${file.name} <span class="text-gray-500">(${(file.size / 1024).toFixed(1)} KB)</span></span>
            </div>
        `;
				}
		</script>
@endsection
