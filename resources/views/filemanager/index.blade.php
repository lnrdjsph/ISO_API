@extends('layouts.app')

@section('title', 'File Manager')

@section('content')
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Header ── --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4">
                <div class="rounded-xl bg-gradient-to-r from-violet-500 to-indigo-600 p-3 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">File Manager</h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        <span class="font-mono text-violet-600">{{ $baseName }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- data-modal replaces onclick="openModal(...)" --}}
                <button data-modal="modalMkdir"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h3.586a1 1 0 01.707.293L10 7h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    New Folder
                </button>
                <button data-modal="modalUpload"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-violet-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:opacity-90">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0l-3 3m3-3l3 3" />
                    </svg>
                    Upload Files
                </button>
            </div>
        </div>

        {{-- ── Flash messages ── --}}
        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                @foreach ($errors->all() as $e)
                    <p>{{ $e }}</p>
                @endforeach
            </div>
        @endif

        {{-- ── Main Card ── --}}
        <div class="rounded-2xl border border-gray-100 bg-white shadow-lg">

            {{-- Breadcrumb + stats bar --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                <nav class="flex min-w-0 items-center gap-1 overflow-x-auto text-sm">
                    @foreach ($breadcrumbs as $i => $crumb)
                        @if ($i > 0)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-gray-300"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                        @if ($loop->last)
                            <span class="whitespace-nowrap font-semibold text-gray-800">{{ $crumb['label'] }}</span>
                        @else
                            <a href="{{ route('others.filemanager.index', ['path' => $crumb['path']]) }}"
                                class="whitespace-nowrap text-violet-600 transition hover:text-violet-800 hover:underline">
                                {{ $crumb['label'] }}
                            </a>
                        @endif
                    @endforeach
                </nav>

                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">
                        {{ count($entries) }} item{{ count($entries) !== 1 ? 's' : '' }}
                    </span>
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input id="fileSearch" type="text" placeholder="Filter…"
                            class="w-36 rounded-lg border border-gray-200 py-1.5 pl-8 pr-3 text-xs focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-100" />
                    </div>
                </div>
            </div>

            {{-- File table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="w-8 px-4 py-3"></th>
                            <th class="px-3 py-3">Name</th>
                            <th class="px-3 py-3">Size</th>
                            <th class="px-3 py-3">Modified</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50" id="fileTableBody">

                        {{-- Up a level --}}
                        @if ($currentPath !== '')
                            @php
                                $upPath = str_contains($currentPath, '/') ? substr($currentPath, 0, strrpos($currentPath, '/')) : '';
                            @endphp
                            <tr class="transition hover:bg-violet-50/40">
                                <td class="px-4 py-3">
                                    @include('filemanager._icon', ['type' => 'up'])
                                </td>
                                <td class="px-3 py-3" colspan="4">
                                    <a href="{{ route('others.filemanager.index', ['path' => $upPath]) }}"
                                        class="flex items-center gap-2 font-medium text-gray-600 hover:text-violet-700">
                                        <span>.. (up a level)</span>
                                    </a>
                                </td>
                            </tr>
                        @endif

                        @forelse ($entries as $entry)
                            <tr class="file-row transition hover:bg-violet-50/40"
                                data-name="{{ strtolower($entry['name']) }}">

                                <td class="px-4 py-3">
                                    @if ($entry['is_dir'])
                                        @include('filemanager._icon', ['type' => 'folder'])
                                    @else
                                        @include('filemanager._icon', ['type' => $entry['ext']])
                                    @endif
                                </td>

                                <td class="max-w-xs px-3 py-3">
                                    @if ($entry['is_dir'])
                                        <a href="{{ route('others.filemanager.index', ['path' => $entry['path']]) }}"
                                            class="flex items-center gap-2 truncate font-medium text-gray-800 hover:text-violet-700"
                                            title="{{ $entry['name'] }}">
                                            {{ $entry['name'] }}
                                        </a>
                                    @else
                                        <span class="block truncate font-medium text-gray-800"
                                            title="{{ $entry['name'] }}">{{ $entry['name'] }}</span>
                                        @if ($entry['ext'])
                                            <span class="mt-0.5 text-xs uppercase tracking-wider text-gray-400">
                                                {{ $entry['ext'] }}
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-400">
                                    {{ $entry['size_formatted'] }}
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-400">
                                    {{ date('Y-m-d H:i', $entry['modified']) }}
                                </td>

                                <td class="px-3 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">

                                        @if (!$entry['is_dir'])
                                            <a href="{{ route('others.filemanager.download', ['path' => $entry['path']]) }}"
                                                title="Download / Open in external editor"
                                                class="action-btn text-indigo-500 hover:bg-indigo-50 hover:text-indigo-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12V4m0 0l-3 3m3-3l3 3" />
                                                </svg>
                                                <span class="hidden text-xs sm:inline">Download</span>
                                            </a>
                                        @endif

                                        {{-- data-action="rename" + data-* carry the params instead of onclick --}}
                                        <button type="button"
                                            data-action="rename"
                                            data-name="{{ $entry['name'] }}"
                                            data-path="{{ $entry['path'] }}"
                                            class="action-btn text-amber-500 hover:bg-amber-50 hover:text-amber-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                                               m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span class="hidden text-xs sm:inline">Rename</span>
                                        </button>

                                        <button type="button"
                                            data-action="delete"
                                            data-name="{{ $entry['name'] }}"
                                            data-path="{{ $entry['path'] }}"
                                            data-isdir="{{ $entry['is_dir'] ? '1' : '0' }}"
                                            class="action-btn text-red-400 hover:bg-red-50 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                                                               L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span class="hidden text-xs sm:inline">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-20 text-center text-sm text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                    </svg>
                                    This folder is empty.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-5 py-2.5 text-xs text-gray-400">
                "Download" opens the file in your external editor (Excel, VS Code, etc.)
            </div>
        </div>
    </div>

    {{-- ══ Modals ══════════════════════════════════════════════════════ --}}

    {{-- Upload --}}
    <div id="modalUpload" class="modal-backdrop hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="text-base font-semibold text-gray-800">Upload Files</h3>
                {{-- data-close replaces onclick="closeModal(...)" --}}
                <button data-close="modalUpload" class="modal-close">✕</button>
            </div>
            <form action="{{ route('others.filemanager.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="path" value="{{ $currentPath }}">
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Files</label>
                    <div
                        class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 py-10 text-center transition hover:border-violet-400 hover:bg-violet-50">
                        <input type="file" name="files[]" id="uploadFileInput" multiple
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-8 w-8 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-sm font-medium text-gray-600">Drop files here or click to browse</p>
                        <p id="uploadFileNames" class="mt-1 text-xs text-gray-400">No files selected</p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-close="modalUpload" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    {{-- New Folder --}}
    <div id="modalMkdir" class="modal-backdrop hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="text-base font-semibold text-gray-800">New Folder</h3>
                <button data-close="modalMkdir" class="modal-close">✕</button>
            </div>
            <form action="{{ route('others.filemanager.mkdir') }}" method="POST">
                @csrf
                <input type="hidden" name="path" value="{{ $currentPath }}">
                <div class="mt-4">
                    <label for="dirnameinput" class="mb-1 block text-sm font-medium text-gray-700">Folder name</label>
                    <input type="text" name="dirname" id="dirnameinput" required placeholder="my-folder"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-100">
                    @error('dirname')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-close="modalMkdir" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Rename --}}
    <div id="modalRename" class="modal-backdrop hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="text-base font-semibold text-gray-800">Rename</h3>
                <button data-close="modalRename" class="modal-close">✕</button>
            </div>
            <form action="{{ route('others.filemanager.rename') }}" method="POST">
                @csrf
                <input type="hidden" name="path" id="renamePathInput">
                <div class="mt-4">
                    <label for="newnameinput" class="mb-1 block text-sm font-medium text-gray-700">New name</label>
                    <input type="text" name="newname" id="newnameinput" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-100">
                    @error('newname')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-close="modalRename" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Rename</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete --}}
    <div id="modalDelete" class="modal-backdrop hidden">
        <div class="modal-box max-w-sm">
            <div class="modal-header">
                <h3 class="text-base font-semibold text-gray-800">Confirm Delete</h3>
                <button data-close="modalDelete" class="modal-close">✕</button>
            </div>
            <div class="mt-3 flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-700">
                        Delete <span class="font-semibold" id="deleteTargetName"></span>?
                    </p>
                    <p id="deleteFolderWarning" class="mt-1 hidden text-xs text-red-500">
                        This folder and all its contents will be permanently deleted.
                    </p>
                    <p class="mt-1 text-xs text-gray-400">This action cannot be undone.</p>
                </div>
            </div>
            <form action="{{ route('others.filemanager.delete') }}" method="POST">
                @csrf
                <input type="hidden" name="path" id="deletePathInput">
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" data-close="modalDelete" class="btn-secondary">Cancel</button>
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-red-600">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ Styles — nonce satisfies CSP style-src ═════════════════════ --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.3rem 0.55rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: background 0.12s, color 0.12s;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(3px);
            padding: 1rem;
            animation: fadeIn 0.15s ease;
        }

        .modal-backdrop.hidden {
            display: none !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-box {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .18);
            width: 100%;
            max-width: 28rem;
            padding: 1.5rem;
            animation: slideUp 0.18s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(12px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-close {
            color: #9ca3af;
            font-size: 0.875rem;
            line-height: 1;
            padding: 0.25rem 0.4rem;
            border-radius: 0.375rem;
            transition: background 0.1s, color 0.1s;
        }

        .modal-close:hover {
            background: #f1f5f9;
            color: #374151;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            background: linear-gradient(to right, #8b5cf6, #6366f1);
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
            transition: opacity 0.15s;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.12s;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }
    </style>

    {{-- ══ Script — nonce satisfies CSP script-src ════════════════════
         NO onclick/onchange/on* attributes anywhere in the HTML above.
         All handlers are wired here via addEventListener + data-* attrs.
    ══════════════════════════════════════════════════════════════════ --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            // ── Modal helpers ─────────────────────────────────────────────
            function openModal(id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.classList.remove('hidden');
                var first = el.querySelector('input[type=text]');
                if (first) setTimeout(function() {
                    first.focus();
                }, 50);
            }

            function closeModal(id) {
                var el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            }

            // ── [data-modal] buttons → open ───────────────────────────────
            document.querySelectorAll('[data-modal]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openModal(btn.getAttribute('data-modal'));
                });
            });

            // ── [data-close] buttons → close ──────────────────────────────
            document.querySelectorAll('[data-close]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    closeModal(btn.getAttribute('data-close'));
                });
            });

            // ── Backdrop click → close ────────────────────────────────────
            document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                backdrop.addEventListener('click', function(e) {
                    if (e.target === backdrop) closeModal(backdrop.id);
                });
            });

            // ── Escape key → close all open modals ───────────────────────
            document.addEventListener('keydown', function(e) {
                if (e.key !== 'Escape') return;
                document.querySelectorAll('.modal-backdrop:not(.hidden)').forEach(function(el) {
                    closeModal(el.id);
                });
            });

            // ── [data-action="rename"] buttons ────────────────────────────
            document.querySelectorAll('[data-action="rename"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var name = btn.getAttribute('data-name');
                    var path = btn.getAttribute('data-path');

                    document.getElementById('newnameinput').value = name;
                    document.getElementById('renamePathInput').value = path;
                    openModal('modalRename');

                    setTimeout(function() {
                        var inp = document.getElementById('newnameinput');
                        inp.focus();
                        var dot = name.lastIndexOf('.');
                        inp.setSelectionRange(0, dot > 0 ? dot : name.length);
                    }, 60);
                });
            });

            // ── [data-action="delete"] buttons ────────────────────────────
            document.querySelectorAll('[data-action="delete"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var name = btn.getAttribute('data-name');
                    var path = btn.getAttribute('data-path');
                    var isDir = btn.getAttribute('data-isdir') === '1';

                    document.getElementById('deleteTargetName').textContent = name;
                    document.getElementById('deletePathInput').value = path;

                    var warn = document.getElementById('deleteFolderWarning');
                    if (isDir) warn.classList.remove('hidden');
                    else warn.classList.add('hidden');

                    openModal('modalDelete');
                });
            });

            // ── Upload file input → show selected names ───────────────────
            var uploadInput = document.getElementById('uploadFileInput');
            if (uploadInput) {
                uploadInput.addEventListener('change', function() {
                    var names = Array.from(this.files).map(function(f) {
                        return f.name;
                    });
                    var label = names.length === 0 ? 'No files selected' :
                        names.length === 1 ? names[0] :
                        names.length + ' files selected';
                    document.getElementById('uploadFileNames').textContent = label;
                });
            }

            // ── Search / filter ───────────────────────────────────────────
            var searchInput = document.getElementById('fileSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var q = this.value.trim().toLowerCase();
                    document.querySelectorAll('#fileTableBody .file-row').forEach(function(tr) {
                        var name = (tr.getAttribute('data-name') || '');
                        tr.style.display = (!q || name.includes(q)) ? '' : 'none';
                    });
                });
            }

            // ── Auto-open modals on validation errors ─────────────────────
            @if ($errors->has('dirname'))
                openModal('modalMkdir');
            @endif
            @if ($errors->has('newname'))
                openModal('modalRename');
            @endif

        }());
    </script>

    @php
        function fmtBytes(int $bytes): string
        {
            if ($bytes < 1024) {
                return $bytes . ' B';
            }
            if ($bytes < 1048576) {
                return round($bytes / 1024, 1) . ' KB';
            }
            if ($bytes < 1073741824) {
                return round($bytes / 1048576, 1) . ' MB';
            }
            return round($bytes / 1073741824, 2) . ' GB';
        }
    @endphp
@endsection
