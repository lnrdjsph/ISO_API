@extends('layouts.app')

@section('title', 'Monthly Product Presets')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 p-3 shadow-lg">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Monthly Product Presets</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Stage product adds &amp; updates for an upcoming month, review, then apply when ready.</p>
            </div>
        </div>
        <a href="{{ route('products.index') }}"
            class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Products
        </a>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if (session('import_errors'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc space-y-0.5 pl-5">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- New preset form --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">Stage a New Preset</h2>
            <p class="mt-0.5 text-xs text-gray-500">Uploading here validates the CSV but does <strong>not</strong> change live products until you Apply.</p>
        </div>
        <form action="{{ route('products.presets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 p-5">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Preset name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. July 2026 Catalog"
                        class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Target month <span class="text-red-500">*</span></label>
                    <input type="month" name="target_month" required value="{{ old('target_month') }}"
                        class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">CSV file <span class="text-red-500">*</span></label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                        class="block w-full text-xs text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Target store</label>
                    <div class="flex h-9 items-center">
                        @include('products.partials.store-selector')
                    </div>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Notes (optional)</label>
                <input type="text" name="notes" value="{{ old('notes') }}" maxlength="1000" placeholder="Context for reviewers…"
                    class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Stage Preset
                </button>
            </div>
        </form>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
            <select name="status" onchange="this.form.submit()"
                class="h-9 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All</option>
                @foreach (['draft', 'applied', 'discarded'] as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- Presets table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Preset</th>
                        <th class="px-4 py-3">Store</th>
                        <th class="px-4 py-3">Target Month</th>
                        <th class="px-4 py-3">Changes</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($presets as $preset)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $preset->name }}</div>
                                @if ($preset->notes)
                                    <div class="text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($preset->notes, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ strtoupper($preset->store_code) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $preset->target_month?->format('M Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <span class="text-green-600">+{{ $preset->insert_count }}</span>
                                <span class="text-gray-300">/</span>
                                <span class="text-blue-600">~{{ $preset->update_count }}</span>
                                @if ($preset->error_count)
                                    <span class="text-gray-300">/</span><span class="text-red-500">!{{ $preset->error_count }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = [
                                        'draft'     => 'bg-amber-100 text-amber-700',
                                        'applied'   => 'bg-green-100 text-green-700',
                                        'discarded' => 'bg-gray-100 text-gray-500',
                                    ][$preset->status] ?? 'bg-gray-100 text-gray-500';
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">{{ ucfirst($preset->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $preset->creator->name ?? 'System' }}<br>
                                {{ $preset->created_at?->format('M d, Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('products.presets.show', $preset->id) }}"
                                    class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                    {{ $preset->isDraft() ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">No presets yet. Stage one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $presets->links() }}</div>
</div>
@endsection
