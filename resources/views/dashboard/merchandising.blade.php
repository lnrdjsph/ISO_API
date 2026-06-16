@extends('layouts.app')

@section('title', 'Merchandising Dashboard')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Merchandising</h1>
            <p class="text-xs text-gray-500 sm:text-sm">Catalog health across all stores · {{ now()->format('F Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.presets.index') }}"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Preset
            </a>
            <a href="{{ route('products.history') }}"
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                History
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Active SKUs</div>
            <div class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($totalActive) }}</div>
            <div class="mt-1 text-xs text-gray-400">{{ number_format($totalArchived) }} archived</div>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wide text-green-500">Added this month</div>
            <div class="mt-1 text-3xl font-bold text-green-700">{{ number_format($addedThisMonth) }}</div>
            <div class="mt-1 text-xs text-green-600">new products</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wide text-blue-500">Updated this month</div>
            <div class="mt-1 text-3xl font-bold text-blue-700">{{ number_format($updatedThisMonth) }}</div>
            <div class="mt-1 text-xs text-blue-600">price / scheme / alloc edits</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wide text-amber-500">Pending presets</div>
            <div class="mt-1 text-3xl font-bold text-amber-700">{{ number_format($pendingPresets) }}</div>
            <div class="mt-1 text-xs text-amber-600">{{ $appliedThisMonth }} applied this month</div>
        </div>
    </div>

    {{-- Scheme coverage --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Scheme Coverage</h2>
            <span class="text-sm font-bold text-gray-900">{{ $schemeCoverage }}%</span>
        </div>
        <p class="mt-0.5 text-xs text-gray-400">Share of active products carrying at least one promo scheme (CBC / PO15 / discount).</p>
        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500" style="width: {{ $schemeCoverage }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Per-store product counts --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-gray-800">Active Products by Store</h2>
            @php $maxActive = collect($perStore)->max('active') ?: 1; @endphp
            <div class="space-y-3">
                @forelse ($perStore as $s)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-600">{{ $s['code'] }} <span class="text-gray-400">{{ \Illuminate\Support\Str::limit($s['label'], 24) }}</span></span>
                            <span class="font-semibold text-gray-700">{{ number_format($s['active']) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ max(2, round(($s['active'] / $maxActive) * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No product tables found.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent presets --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Recent Presets</h2>
                <a href="{{ route('products.presets.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="space-y-2">
                @forelse ($recentPresets as $preset)
                    @php
                        $badge = [
                            'draft'     => 'bg-amber-100 text-amber-700',
                            'applied'   => 'bg-green-100 text-green-700',
                            'discarded' => 'bg-gray-100 text-gray-500',
                        ][$preset->status] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <a href="{{ route('products.presets.show', $preset->id) }}"
                        class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50">
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ $preset->name }}</div>
                            <div class="text-xs text-gray-400">{{ strtoupper($preset->store_code) }} · {{ $preset->target_month?->format('M Y') }} · +{{ $preset->insert_count }}/~{{ $preset->update_count }}</div>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $badge }}">{{ ucfirst($preset->status) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No presets staged yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">Recent Product Activity</h2>
            <a href="{{ route('products.history') }}" class="text-xs font-medium text-indigo-600 hover:underline">Full history</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse ($recentActivity as $log)
                <div class="flex items-start gap-3 px-5 py-3">
                    <div class="mt-0.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-400"></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm text-gray-700">{{ $log->description }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $log->user->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <span class="whitespace-nowrap text-[11px] font-medium text-gray-400">{{ $log->action }}</span>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-400">No recent product activity.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
