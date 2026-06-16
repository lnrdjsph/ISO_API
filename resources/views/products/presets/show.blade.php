@extends('layouts.app')

@section('title', 'Review Preset')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ $preset->name }}</h1>
                @php
                    $badge = [
                        'draft'     => 'bg-amber-100 text-amber-700',
                        'applied'   => 'bg-green-100 text-green-700',
                        'discarded' => 'bg-gray-100 text-gray-500',
                    ][$preset->status] ?? 'bg-gray-100 text-gray-500';
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">{{ ucfirst($preset->status) }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">
                Store <strong>{{ strtoupper($preset->store_code) }}</strong> ·
                Target <strong>{{ $preset->target_month?->format('M Y') }}</strong> ·
                Staged by {{ $preset->creator->name ?? 'System' }} on {{ $preset->created_at?->format('M d, Y H:i') }}
                @if ($preset->status === 'applied')
                    · Applied by {{ $preset->applier->name ?? '—' }} on {{ $preset->applied_at?->format('M d, Y H:i') }}
                @endif
            </p>
        </div>
        <a href="{{ route('products.presets.index') }}"
            class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            All Presets
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

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
            <div class="text-2xl font-bold text-green-700">{{ $preset->insert_count }}</div>
            <div class="text-xs font-medium text-green-600">New products</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <div class="text-2xl font-bold text-blue-700">{{ $preset->update_count }}</div>
            <div class="text-xs font-medium text-blue-600">Updates</div>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="text-2xl font-bold text-red-700">{{ $preset->error_count }}</div>
            <div class="text-xs font-medium text-red-600">Skipped rows</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-2xl font-bold text-gray-800">{{ count($preset->rows ?? []) }}</div>
            <div class="text-xs font-medium text-gray-500">Total staged</div>
        </div>
    </div>

    {{-- Actions (draft only) --}}
    @if ($preset->isDraft())
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex-1 text-sm text-amber-800">
                <strong>Nothing has been written to live products yet.</strong>
                Applying will upsert these {{ count($preset->rows ?? []) }} rows into <code>products_{{ $preset->store_code }}</code>.
            </div>
            <form action="{{ route('products.presets.apply', $preset->id) }}" method="POST"
                onsubmit="return confirm('Apply this preset to live products? This writes {{ $preset->insert_count }} new and {{ $preset->update_count }} updated products and cannot be undone.');">
                @csrf
                <button type="submit"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-green-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Apply to Live Products
                </button>
            </form>
            <form action="{{ route('products.presets.discard', $preset->id) }}" method="POST"
                onsubmit="return confirm('Discard this preset? It will no longer be applicable.');">
                @csrf
                <button type="submit"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">
                    Discard
                </button>
            </form>
        </div>
    @endif

    {{-- Validation errors from staged CSV --}}
    @if (!empty($preset->errors))
        <div class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
            <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700">
                {{ count($preset->errors) }} row(s) were skipped during validation
            </div>
            <ul class="max-h-48 space-y-1 overflow-y-auto px-5 py-3 text-xs text-red-600">
                @foreach ($preset->errors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Staged rows preview --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">Staged Rows</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Dept</th>
                        <th class="px-4 py-3 text-right">SRP</th>
                        <th class="px-4 py-3 text-right">Alloc/Case</th>
                        <th class="px-4 py-3">Schemes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($preset->rows ?? [] as $row)
                        @php $isUpdate = isset($existingSkus[strtoupper($row['sku'])]); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5">
                                @if ($isUpdate)
                                    <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">Update</span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">New</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-700">{{ $row['sku'] }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ $row['description'] }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-500">{{ $row['department'] ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-700">{{ number_format((float) ($row['srp'] ?? 0), 2) }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-700">{{ $row['allocation_per_case'] ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-500">
                                @php
                                    $schemes = array_filter([
                                        $row['cash_bank_card_scheme'] ?? null,
                                        $row['po15_scheme'] ?? null,
                                        $row['discount_scheme'] ?? null,
                                    ]);
                                @endphp
                                {{ $schemes ? implode(' · ', $schemes) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">No valid rows were staged.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
