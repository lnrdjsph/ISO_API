@extends('layouts.app')

@section('title', 'XLSX Viewer & Editor')

@section('content')

    <div class="mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Page Header ── --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-4">
                <div class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 p-3 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">XLSX Viewer &amp; Editor</h1>
                    <p class="mt-1 text-gray-500">Upload, view, and edit Excel spreadsheets in-browser</p>
                </div>
            </div>
            <div id="headerActions" class="hidden items-center gap-2">
                <button id="addRowBtn"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Row
                </button>
                <button id="downloadBtn"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:opacity-90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download XLSX
                </button>
            </div>
        </div>

        {{-- ── Upload Panel ── --}}
        <div id="uploadPanel" class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-lg">

            <div id="dropZone"
                class="group relative flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 text-center transition-all duration-200 hover:border-teal-400 hover:bg-teal-50">

                {{-- Invisible file input covers the whole zone --}}
                <input type="file" id="xlsxFileInput" accept=".xlsx,.xls,.csv"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />

                <div id="dropIdle" class="pointer-events-none space-y-3">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 transition-colors group-hover:bg-teal-100">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8 text-gray-400 group-hover:text-teal-500"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-gray-700">Drag &amp; Drop your Excel file here</p>
                    <p class="text-sm text-gray-400">
                        or click to browse &nbsp;&middot;&nbsp;
                        <span class="font-medium text-teal-600">.xlsx &nbsp; .xls &nbsp; .csv</span>
                    </p>
                </div>

                <div id="dropLoading" class="pointer-events-none hidden flex-col items-center gap-3">
                    <svg class="h-10 w-10 animate-spin text-teal-500" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962
                               7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <p class="text-sm text-gray-500">Parsing file…</p>
                </div>
            </div>

            {{-- File info bar --}}
            <div id="fileInfoBar" class="mt-4 hidden items-center justify-between rounded-lg bg-teal-50 px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-teal-600"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                               a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                    </svg>
                    <span id="fileNameLabel" class="truncate text-sm font-semibold text-teal-800"></span>
                    <span id="fileSizeLabel" class="shrink-0 text-xs text-teal-600"></span>
                </div>
                <button id="removeFileBtn"
                    class="ml-4 shrink-0 text-xs font-medium text-red-500 transition hover:text-red-700">
                    Remove file
                </button>
            </div>
        </div>

        {{-- ── Sheet Panel ── --}}
        <div id="sheetPanel" class="hidden rounded-2xl border border-gray-100 bg-white shadow-lg">

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-3">
                <div class="relative min-w-40 flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="searchInput" type="text" placeholder="Search cells…"
                        class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-100" />
                </div>
                <div class="flex items-center gap-2">
                    <span id="rowCount" class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"></span>
                    <span id="colCount" class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"></span>
                    <span id="editBadge" class="hidden rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700"></span>
                </div>
                <button id="undoBtn"
                    class="hidden items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    Undo
                </button>
            </div>

            {{-- Sheet tabs --}}
            <div id="sheetTabs"
                class="flex gap-1 overflow-x-auto border-b border-gray-100 bg-gray-50 px-4 py-2"></div>

            {{-- Table wrapper --}}
            <div class="overflow-auto" style="max-height: 65vh;">
                <table id="xlsxTable" class="min-w-full border-collapse text-sm">
                    <thead id="xlsxThead" class="sticky top-0 z-10"></thead>
                    <tbody id="xlsxTbody"></tbody>
                </table>
                <p id="emptyState" class="hidden py-16 text-center text-sm text-gray-400">
                    No matching rows found.
                </p>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-2.5 text-xs text-gray-400">
                <span>
                    Click any cell to edit &nbsp;&middot;&nbsp;
                    <kbd class="rounded bg-gray-100 px-1 py-0.5 font-mono text-gray-500">Enter</kbd> = next row &nbsp;
                    <kbd class="rounded bg-gray-100 px-1 py-0.5 font-mono text-gray-500">Tab</kbd> = next cell &nbsp;
                    <kbd class="rounded bg-gray-100 px-1 py-0.5 font-mono text-gray-500">Esc</kbd> = cancel
                </span>
                <span id="syncStatus"></span>
            </div>
        </div>
    </div>

    {{-- ─── Styles ─────────────────────────────────────────── --}}
    <style>
        /* Header row */
        #xlsxThead th {
            background: #f1f5f9;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            padding: 0.55rem 0.75rem;
            white-space: nowrap;
            border-bottom: 2px solid #cbd5e1;
            border-right: 1px solid #e2e8f0;
            user-select: none;
        }

        #xlsxThead th.th-rownum {
            width: 2.5rem;
            min-width: 2.5rem;
            text-align: center;
            background: #e2e8f0;
            color: #94a3b8;
        }

        #xlsxThead th.th-del {
            width: 1.75rem;
            min-width: 1.75rem;
            border-right: none;
        }

        /* Body */
        #xlsxTbody tr {
            transition: background 0.1s;
        }

        #xlsxTbody tr:nth-child(even) {
            background: #fafafa;
        }

        #xlsxTbody tr:hover {
            background: #f0fdf8 !important;
        }

        #xlsxTbody tr.row-search-match {
            background: #fefce8 !important;
        }

        #xlsxTbody td {
            border-right: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            padding: 0;
            vertical-align: middle;
        }

        #xlsxTbody td.td-rownum {
            text-align: center;
            font-size: 0.68rem;
            color: #94a3b8;
            background: #f8fafc;
            padding: 0.35rem 0.4rem;
            user-select: none;
            border-right: 1px solid #e2e8f0;
        }

        #xlsxTbody td.td-del {
            width: 1.75rem;
            border-right: none;
            text-align: center;
        }

        /* Cell inputs */
        .cell-inp {
            display: block;
            width: 100%;
            min-width: 4.5rem;
            padding: 0.4rem 0.55rem;
            background: transparent;
            border: 2px solid transparent;
            font-size: 0.8125rem;
            color: #1e293b;
            outline: none;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            transition: background 0.1s, border-color 0.1s;
            box-sizing: border-box;
        }

        .cell-inp:focus {
            background: #f0fdfa;
            border-color: #2dd4bf;
            border-radius: 3px;
            position: relative;
            z-index: 2;
            white-space: normal;
            overflow: visible;
            min-width: 10rem;
            box-shadow: 0 0 0 3px rgba(45, 212, 191, .15);
        }

        /* Modified cell: amber tint */
        .cell-inp.is-modified {
            background: #fffbeb;
        }

        .cell-inp.is-modified:not(:focus) {
            border-color: #fcd34d;
            border-radius: 3px;
        }

        .cell-inp.is-modified:focus {
            background: #fef9c3;
            border-color: #f59e0b;
        }

        /* Delete row button */
        .btn-del-row {
            display: none;
            cursor: pointer;
            padding: 2px 6px;
            font-size: 10px;
            line-height: 1.4;
            color: #dc2626;
            background: #fee2e2;
            border-radius: 4px;
            transition: background 0.1s;
        }

        .btn-del-row:hover {
            background: #fca5a5;
        }

        #xlsxTbody tr:hover .btn-del-row {
            display: inline-block;
        }

        /* Sheet tabs */
        .sheet-tab {
            cursor: pointer;
            padding: 0.3rem 0.9rem;
            border-radius: 0.45rem;
            font-size: 0.78rem;
            font-weight: 500;
            color: #64748b;
            white-space: nowrap;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.12s;
        }

        .sheet-tab:hover {
            background: #fff;
            color: #0f766e;
        }

        .sheet-tab.active {
            background: #fff;
            color: #0f766e;
            border-color: #99f6e4;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }
    </style>

    {{-- ─── SheetJS ────────────────────────────────────────── --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        (function() {
            'use strict';

            // ── State ────────────────────────────────────────────────
            var workbook = null; // XLSX.WorkBook
            var sheetName = null; // active sheet
            var headers = []; // string[]
            var rows = []; // string[][]  ← single source of truth
            var modified = {}; // { "ri,ci": true }
            var undoStack = []; // [{ri,ci,before,after}]
            var origFile = null;
            var MAX_UNDO = 100;

            // ── DOM refs ─────────────────────────────────────────────
            var elDropZone = document.getElementById('dropZone');
            var elFileInput = document.getElementById('xlsxFileInput');
            var elDropIdle = document.getElementById('dropIdle');
            var elDropLoading = document.getElementById('dropLoading');
            var elFileInfoBar = document.getElementById('fileInfoBar');
            var elFileNameLabel = document.getElementById('fileNameLabel');
            var elFileSizeLabel = document.getElementById('fileSizeLabel');
            var elRemoveFileBtn = document.getElementById('removeFileBtn');
            var elSheetPanel = document.getElementById('sheetPanel');
            var elHeaderActions = document.getElementById('headerActions');
            var elSheetTabs = document.getElementById('sheetTabs');
            var elThead = document.getElementById('xlsxThead');
            var elTbody = document.getElementById('xlsxTbody');
            var elSearchInput = document.getElementById('searchInput');
            var elRowCount = document.getElementById('rowCount');
            var elColCount = document.getElementById('colCount');
            var elEditBadge = document.getElementById('editBadge');
            var elUndoBtn = document.getElementById('undoBtn');
            var elAddRowBtn = document.getElementById('addRowBtn');
            var elDownloadBtn = document.getElementById('downloadBtn');
            var elEmptyState = document.getElementById('emptyState');
            var elSyncStatus = document.getElementById('syncStatus');

            // ── File events ──────────────────────────────────────────
            elFileInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) loadFile(e.target.files[0]);
            });

            elDropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                elDropZone.classList.add('border-teal-400', 'bg-teal-50');
            });
            elDropZone.addEventListener('dragleave', function() {
                elDropZone.classList.remove('border-teal-400', 'bg-teal-50');
            });
            elDropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                elDropZone.classList.remove('border-teal-400', 'bg-teal-50');
                if (e.dataTransfer.files && e.dataTransfer.files[0]) loadFile(e.dataTransfer.files[0]);
            });

            elRemoveFileBtn.addEventListener('click', resetAll);

            // ── Load & parse ─────────────────────────────────────────
            function loadFile(file) {
                origFile = file;
                setLoading(true);

                var reader = new FileReader();
                reader.onerror = function() {
                    setLoading(false);
                    alert('Could not read file.');
                };
                reader.onload = function(e) {
                    try {
                        var data = new Uint8Array(e.target.result);
                        workbook = XLSX.read(data, {
                            type: 'array',
                            cellDates: true
                        });

                        elFileNameLabel.textContent = file.name;
                        elFileSizeLabel.textContent = fmtBytes(file.size);
                        show(elFileInfoBar, 'flex');

                        buildTabs();
                        switchSheet(workbook.SheetNames[0]);

                        show(elSheetPanel);
                        show(elHeaderActions, 'flex');
                    } catch (err) {
                        console.error(err);
                        alert('Could not parse file: ' + err.message);
                    } finally {
                        setLoading(false);
                    }
                };
                reader.readAsArrayBuffer(file);
            }

            function setLoading(on) {
                elDropIdle.classList.toggle('hidden', on);
                elDropLoading.classList.toggle('hidden', !on);
                if (on) elDropLoading.classList.add('flex');
                else elDropLoading.classList.remove('flex');
            }

            // ── Sheet tabs ───────────────────────────────────────────
            function buildTabs() {
                elSheetTabs.innerHTML = '';
                workbook.SheetNames.forEach(function(name) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'sheet-tab';
                    btn.textContent = name;
                    btn.addEventListener('click', function() {
                        switchSheet(name);
                    });
                    elSheetTabs.appendChild(btn);
                });
            }

            function switchSheet(name) {
                sheetName = name;
                modified = {};
                undoStack = [];
                refreshUndoBtn();
                refreshEditBadge();

                // Highlight active tab
                Array.from(elSheetTabs.querySelectorAll('.sheet-tab')).forEach(function(b) {
                    b.classList.toggle('active', b.textContent === name);
                });

                parseSheet(workbook.Sheets[name]);
                renderTable();
                updateStats();
            }

            // ── Parse sheet → headers + rows (all strings) ───────────
            function parseSheet(ws) {
                // Use sheet_to_json with header:1 to get a 2-D array
                var raw = XLSX.utils.sheet_to_json(ws, {
                    header: 1,
                    defval: null,
                    blankrows: true,
                    raw: false // ← return formatted strings, not raw numbers/dates
                });

                if (!raw || raw.length === 0) {
                    headers = [];
                    rows = [];
                    return;
                }

                // Find widest row
                var colLen = 0;
                for (var i = 0; i < raw.length; i++) {
                    if (Array.isArray(raw[i]) && raw[i].length > colLen) colLen = raw[i].length;
                }
                if (colLen === 0) {
                    headers = [];
                    rows = [];
                    return;
                }

                // First row → headers
                var hdrRow = Array.isArray(raw[0]) ? raw[0] : [];
                headers = [];
                for (var c = 0; c < colLen; c++) {
                    var h = hdrRow[c];
                    headers.push((h !== null && h !== undefined && String(h).trim() !== '') ?
                        String(h).trim() :
                        'Col ' + (c + 1));
                }

                // Remaining rows → data (padded, all strings)
                rows = [];
                for (var r = 1; r < raw.length; r++) {
                    var src = Array.isArray(raw[r]) ? raw[r] : [];
                    var out = [];
                    for (var ci = 0; ci < colLen; ci++) {
                        var v = src[ci];
                        out.push(v === null || v === undefined ? '' : String(v));
                    }
                    rows.push(out);
                }
            }

            // ── Render table ─────────────────────────────────────────
            function renderTable() {
                var query = elSearchInput.value.trim().toLowerCase();

                // ── Build header row ──
                elThead.innerHTML = '';
                var trH = document.createElement('tr');

                var thNum = document.createElement('th');
                thNum.className = 'th-rownum';
                thNum.textContent = '#';
                trH.appendChild(thNum);

                for (var hi = 0; hi < headers.length; hi++) {
                    var th = document.createElement('th');
                    th.textContent = headers[hi];
                    th.title = headers[hi];
                    trH.appendChild(th);
                }
                var thDel = document.createElement('th');
                thDel.className = 'th-del';
                trH.appendChild(thDel);
                elThead.appendChild(trH);

                // ── Build body rows ──
                elTbody.innerHTML = '';
                var visible = 0;

                for (var ri = 0; ri < rows.length; ri++) {
                    var rowArr = rows[ri];

                    // Filter
                    if (query) {
                        var match = false;
                        for (var fi = 0; fi < rowArr.length; fi++) {
                            if (rowArr[fi].toLowerCase().indexOf(query) !== -1) {
                                match = true;
                                break;
                            }
                        }
                        if (!match) continue;
                    }

                    visible++;
                    var tr = document.createElement('tr');
                    tr.dataset.ri = ri; // ← store data index on the row
                    if (query) tr.classList.add('row-search-match');

                    // Row number cell
                    var tdNum = document.createElement('td');
                    tdNum.className = 'td-rownum';
                    tdNum.textContent = ri + 1;
                    tr.appendChild(tdNum);

                    // Data cells
                    for (var ci = 0; ci < rowArr.length; ci++) {
                        var td = document.createElement('td');
                        var inp = buildCellInput(ri, ci, rowArr[ci]);
                        td.appendChild(inp);
                        tr.appendChild(td);
                    }

                    // Delete button
                    var tdDel = document.createElement('td');
                    tdDel.className = 'td-del';
                    var delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.className = 'btn-del-row';
                    delBtn.textContent = '✕';
                    delBtn.title = 'Delete row';;
                    (function(capturedRi) {
                        delBtn.addEventListener('click', function() {
                            deleteRow(capturedRi);
                        });
                    })(ri);
                    tdDel.appendChild(delBtn);
                    tr.appendChild(tdDel);

                    elTbody.appendChild(tr);
                }

                // Empty state
                elEmptyState.classList.toggle('hidden', visible > 0 || rows.length === 0);
            }

            // Build a single editable cell input
            function buildCellInput(ri, ci, value) {
                var inp = document.createElement('input');
                inp.type = 'text';
                inp.className = 'cell-inp' + (modified[ri + ',' + ci] ? ' is-modified' : '');
                inp.value = value;
                inp.title = value;

                var prevValue; // value when focus was gained
                inp.addEventListener('focus', function() {
                    prevValue = inp.value;
                });

                inp.addEventListener('blur', function() {
                    commitEdit(inp, ri, ci, prevValue);
                });

                inp.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        commitEdit(inp, ri, ci, prevValue);
                        inp.blur();
                        focusCell(ri + 1, ci);
                    } else if (e.key === 'Tab') {
                        e.preventDefault();
                        commitEdit(inp, ri, ci, prevValue);
                        inp.blur();
                        if (e.shiftKey) {
                            if (ci > 0) focusCell(ri, ci - 1);
                            else if (ri > 0) focusCell(ri - 1, headers.length - 1);
                        } else {
                            if (ci < headers.length - 1) focusCell(ri, ci + 1);
                            else focusCell(ri + 1, 0);
                        }
                    } else if (e.key === 'Escape') {
                        inp.value = prevValue; // cancel edit
                        inp.blur();
                    }
                });

                return inp;
            }

            // ── Commit an edit ───────────────────────────────────────
            function commitEdit(inp, ri, ci, prevValue) {
                var newVal = inp.value;
                // Use prevValue (captured on focus) as old value
                var oldVal = (prevValue !== undefined) ? prevValue : rows[ri][ci];
                if (newVal === oldVal) return; // nothing changed

                // Save undo entry
                undoStack.push({
                    ri: ri,
                    ci: ci,
                    before: oldVal,
                    after: newVal
                });
                if (undoStack.length > MAX_UNDO) undoStack.shift();

                rows[ri][ci] = newVal;
                inp.title = newVal;

                modified[ri + ',' + ci] = true;
                inp.classList.add('is-modified');

                refreshUndoBtn();
                refreshEditBadge();
                syncWorkbook();
            }

            // ── Focus a specific cell by data indices ────────────────
            function focusCell(ri, ci) {
                var tr = elTbody.querySelector('tr[data-ri="' + ri + '"]');
                if (!tr) return;
                // data cells are td children 1..N (index 0 is rownum)
                var tds = tr.querySelectorAll('td');
                var targetTd = tds[ci + 1]; // +1 to skip rownum td
                if (!targetTd) return;
                var inp = targetTd.querySelector('.cell-inp');
                if (inp) {
                    inp.focus();
                    inp.select();
                }
            }

            // ── Delete row ───────────────────────────────────────────
            function deleteRow(ri) {
                if (!confirm('Delete row ' + (ri + 1) + '? This cannot be undone.')) return;

                rows.splice(ri, 1);

                // Shift modified keys
                var newModified = {};
                Object.keys(modified).forEach(function(key) {
                    var parts = key.split(',');
                    var r = parseInt(parts[0], 10);
                    var c = parseInt(parts[1], 10);
                    if (r < ri) newModified[key] = true;
                    else if (r > ri) newModified[(r - 1) + ',' + c] = true;
                });
                modified = newModified;

                renderTable();
                updateStats();
                syncWorkbook();
            }

            // ── Add row ──────────────────────────────────────────────
            elAddRowBtn.addEventListener('click', function() {
                var emptyRow = [];
                for (var i = 0; i < headers.length; i++) emptyRow.push('');
                rows.push(emptyRow);
                renderTable();
                updateStats();
                syncWorkbook();
                // Scroll and focus first cell of new row
                setTimeout(function() {
                    var lastTr = elTbody.querySelector('tr:last-child');
                    if (!lastTr) return;
                    lastTr.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    var inp = lastTr.querySelector('.cell-inp');
                    if (inp) inp.focus();
                }, 40);
            });

            // ── Undo ─────────────────────────────────────────────────
            elUndoBtn.addEventListener('click', function() {
                var op = undoStack.pop();
                if (!op) return;

                rows[op.ri][op.ci] = op.before;

                // Remove from modified if reverted to original
                if (op.before === op.after) delete modified[op.ri + ',' + op.ci];
                // (We keep it modified if "before" is still different from the pre-edit value;
                //  a full equality check against original would need a snapshot. Acceptable UX.)

                refreshUndoBtn();
                refreshEditBadge();
                renderTable();
                syncWorkbook();
            });

            // ── Search ───────────────────────────────────────────────
            var searchTimer;
            elSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(renderTable, 180);
            });

            // ── Sync workbook object ─────────────────────────────────
            function syncWorkbook() {
                var aoa = [headers].concat(rows);
                var newWs = XLSX.utils.aoa_to_sheet(aoa);
                var oldWs = workbook.Sheets[sheetName];
                if (oldWs && oldWs['!cols']) newWs['!cols'] = oldWs['!cols'];
                workbook.Sheets[sheetName] = newWs;
                elSyncStatus.textContent = 'Synced ' + new Date().toLocaleTimeString();
            }

            // ── Download ─────────────────────────────────────────────
            elDownloadBtn.addEventListener('click', function() {
                syncWorkbook();
                var base = origFile ?
                    origFile.name.replace(/\.(xlsx?|csv)$/i, '') :
                    'spreadsheet';
                XLSX.writeFile(workbook, base + '_edited.xlsx');
            });

            // ── Helpers ──────────────────────────────────────────────
            function updateStats() {
                elRowCount.textContent = rows.length + ' rows';
                elColCount.textContent = headers.length + ' cols';
            }

            function refreshEditBadge() {
                var n = Object.keys(modified).length;
                if (n > 0) {
                    elEditBadge.textContent = n + ' edit' + (n > 1 ? 's' : '');
                    elEditBadge.classList.remove('hidden');
                } else {
                    elEditBadge.classList.add('hidden');
                }
            }

            function refreshUndoBtn() {
                if (undoStack.length > 0) {
                    elUndoBtn.classList.remove('hidden');
                    elUndoBtn.classList.add('inline-flex');
                } else {
                    elUndoBtn.classList.add('hidden');
                    elUndoBtn.classList.remove('inline-flex');
                }
            }

            function resetAll() {
                workbook = null;
                sheetName = null;
                headers = [];
                rows = [];
                modified = {};
                undoStack = [];
                origFile = null;

                hide(elSheetPanel);
                hide(elHeaderActions);
                hide(elFileInfoBar);
                elFileInput.value = '';
                elSheetTabs.innerHTML = '';
                elThead.innerHTML = '';
                elTbody.innerHTML = '';
                elSearchInput.value = '';
                elEditBadge.classList.add('hidden');
                elUndoBtn.classList.add('hidden');
                elUndoBtn.classList.remove('inline-flex');
                elSyncStatus.textContent = '';
            }

            function show(el, display) {
                el.classList.remove('hidden');
                if (display) el.classList.add(display);
            }

            function hide(el) {
                el.classList.add('hidden');
                el.classList.remove('block', 'flex', 'inline-flex');
            }

            function fmtBytes(b) {
                if (b < 1024) return b + ' B';
                if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
                return (b / 1048576).toFixed(2) + ' MB';
            }

        }());
    </script>

@endsection
