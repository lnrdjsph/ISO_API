@extends('layouts.app')

@section('title', 'Import')

@section('content')

{{-- CSV Import Section --}}
<div class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 0 1-.88-7.9A5 5 0 1 1 15.9 6L16 6a5 5 0 0 1 1 9.9M15 11l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Import CSV</h1>
                        <p class="text-gray-600 mt-1">Import Products from CSV</p>
                    </div>
                </div>
                <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-500 pr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Drag & drop or choose files on the drop zone.</span>
                </div>
            </div>
        </div>

        <div class="bg-white backdrop-blur-sm rounded-3xl p-6 mb-8 shadow-lg border border-white/20">

            @if(session('import_success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('import_success') }}</span>
                </div>
            @endif

            @if(session('import_errors'))
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                    <p class="font-semibold mb-2">Import completed with errors:</p>
                    <div class="max-h-48 overflow-y-auto pr-2">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('products.import.upload') }}" method="POST" enctype="multipart/form-data" id="csvImportForm">
                @csrf
                
                {{-- Drop Zone --}}
                <div id="dropZone" class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-all duration-300 ease-in-out hover:border-blue-400 hover:bg-blue-50 cursor-pointer group">
                    <div id="dropContent" class="space-y-4">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center group-hover:bg-blue-100 transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Drag & Drop Your CSV File</h3>
                            <p class="text-gray-500 mb-4">or click to browse your files</p>
                            <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-medium shadow transition-colors duration-200">
                                Choose File
                            </button>
                        </div>
                    </div>

                    {{-- Upload Progress --}}
                    <div id="uploadProgress" class="hidden space-y-4">
                        <div class="w-12 h-12 mx-auto">
                            <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Processing your file...</h3>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                            <p id="progressText" class="text-sm text-gray-600">0% complete</p>
                        </div>
                    </div>

                    <input type="file" id="csvFile" name="csv_file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".csv" />
                </div>

                {{-- File Info Display --}}
                <div id="fileInfo" class="hidden mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center space-x-4">
                        <div class="bg-green-500 p-2 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800" id="fileName">File loaded successfully!</h4>
                            <p class="text-sm text-gray-600" id="fileDetails">Ready to process</p>
                        </div>
                        <button type="button" id="removeFile" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- CSV Format Guide --}}
                <div class="mt-6 p-4 rounded-lg border border-gray-200">
                    <div class="flex items-start space-x-3">
                        <div class="p-1 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <h2 class="text-md font-semibold text-gray-600 mb-2">CSV Format Requirements</h2>
                                    <p class="text-md text-gray-600 mb-2">Your CSV file should have exactly 7 columns in this order:</p>
                                    <ul class="text-sm text-gray-700 space-y-1">
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                            <span><strong>Column 1:</strong> SKU</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                            <span><strong>Column 2:</strong> Product Description</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                                            <span><strong>Column 3:</strong> Case Pack</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                                            <span><strong>Column 4:</strong> SRP</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                            <span><strong>Column 5:</strong> Allocation / Case</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                            <span><strong>Column 6:</strong> Cash / Bank Card Scheme</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-pink-500 rounded-full"></span>
                                            <span><strong>Column 7:</strong> PO15 Scheme</span>
                                        </li>
                                        <li class="flex items-center space-x-2">
                                            <span class="w-2 h-2 bg-pink-500 rounded-full"></span>
                                            <span><strong>Column 8:</strong> Freebie SKU</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="bg-white p-3">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">EXAMPLE FORMAT:</p>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-xs text-gray-700 font-mono border border-gray-300">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">SKU</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">Description</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">Case Pack</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">SRP</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">Allocation / Case</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">C/BC Scheme</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">PO15 Scheme</th>
                                                    <th class="px-2 py-1 border border-gray-300 text-left">Freebie SKU</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-2 py-1 border border-gray-300">102806178</td>
                                                    <td class="px-2 py-1 border border-gray-300">Bearbrand Pwdr Mlk</td>
                                                    <td class="px-2 py-1 border border-gray-300">24</td>
                                                    <td class="px-2 py-1 border border-gray-300">15.50</td>
                                                    <td class="px-2 py-1 border border-gray-300">3975</td>
                                                    <td class="px-2 py-1 border border-gray-300">15+1</td>
                                                    <td class="px-2 py-1 border border-gray-300">15+2</td>
                                                    <td class="px-2 py-1 border border-gray-300">9413022</td>
                                                </tr>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-2 py-1 border border-gray-300">8404794</td>
                                                    <td class="px-2 py-1 border border-gray-300">Lucky Me Xtra Hot</td>
                                                    <td class="px-2 py-1 border border-gray-300">72</td>
                                                    <td class="px-2 py-1 border border-gray-300">11.50</td>
                                                    <td class="px-2 py-1 border border-gray-300">3000</td>
                                                    <td class="px-2 py-1 border border-gray-300">10+1</td>
                                                    <td class="px-2 py-1 border border-gray-300">8+1</td>
                                                    <td class="px-2 py-1 border border-gray-300">8404794</td>
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
                <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
                    <a href="#" id="downloadTemplate" class="flex items-center space-x-2 text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Download CSV Template</span>
                    </a>
                    
                    <div class="flex space-x-3">
                        <button type="button" id="previewBtn" class="hidden bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium transition-colors flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Preview Data</span>
                        </button>
                        <button type="submit" id="importBtn" class="hidden bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-medium transition-colors flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Import Products</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Data Preview Section --}}
        <div id="previewSection" class="hidden bg-white backdrop-blur-sm rounded-3xl p-6 mb-8 shadow-lg border border-white/20">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Data Preview</h3>
                <div class="flex items-center space-x-2 bg-green-100 px-3 py-1 rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="recordCount" class="text-sm text-green-700 font-medium">0 records found</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">Case Pack</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">SRP</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">Allocation</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">CBC Scheme</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">PO15 Scheme</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">Freebie SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider border-b">Action</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody" class="divide-y divide-gray-200">
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
    document.addEventListener('DOMContentLoaded', function () {
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

                // Parse CSV data
                csvData = lines.slice(1).map(line => {
                    const cols = line.split(',').map(col => col.trim().replace(/"/g, ''));
                    return {
                        sku: cols[0] || '',
                        description: cols[1] || '',
                        case_pack: cols[2] || '',
                        srp: cols[3] || '',
                        allocation_per_case: cols[4] || '',
                        cash_bank_card_scheme: cols[5] || '',
                        po15_scheme: cols[6] || '',
                        freebie_sku: (cols[7] || '').split('/').map(s => s.replace(/\s+/g, '')).join('/')

                    };
                }).filter(row => row.sku && row.description);

                console.log(csvData.map(row => row.freebie_sku));

                if (csvData.length === 0) {
                    alert('No valid data found in CSV file. Please check the format.');
                    resetUpload();
                    return;
                }

                // Show success
                setTimeout(() => {
                    uploadProgress.classList.add('hidden');
                    fileInfo.classList.remove('hidden');
                    fileName.textContent = file.name;
                    fileDetails.textContent = `${csvData.length} products ready to import • ${(file.size / 1024).toFixed(1)} KB`;
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

            if (hasUpdate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Heads up!',
                    text: 'Some SKUs already exist and will be updated. Please review the data carefully.',
                    confirmButtonColor: '#f59e0b',
                    confirmButtonText: 'Got it',
                });
            }
            renderPreviewChunk();
            previewSection.classList.remove('hidden');
            previewSection.scrollIntoView({ behavior: 'smooth' });
        });

        function renderPreviewChunk() {
            let hasUpdate = false;
            const nextChunk = csvData.slice(previewOffset, previewOffset + PREVIEW_LIMIT);
            nextChunk.forEach(row => {
                const action = existingSkus.includes(row.sku.toUpperCase()) ? 'update' : 'insert';
                const tr = document.createElement('tr');
                tr.classList.add('hover:bg-gray-50');
                tr.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">${escapeHtml(row.sku)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.description)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.case_pack)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.srp)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.allocation_per_case)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.cash_bank_card_scheme)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.po15_scheme)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.freebie_sku)}</td>


                    <td class="px-4 py-3 text-xs">
                        <span class="inline-block px-2 py-1 font-semibold rounded-full 
                            ${action === 'update' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'}">
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
            const csvContent = 'SKU,Product Description,Case Pack,SRP,Allocation Per Case,Cash Bank Card Scheme,PO15 Scheme,Freebie SKU\n102806178,Bearbrand Pwdr Mlk 128-192/33G,192,11.20,3795,15+1,15+2,9413022\n8404794,Lucky Me Pc Xtra Hot Chi72/60G,72,11.50,3000,10+1,8+1,8404794';
            const blob = new Blob([csvContent], { type: 'text/csv' });
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
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });
</script>

@endsection