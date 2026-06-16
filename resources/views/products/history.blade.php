@extends('layouts.app')

@section('title', 'Product Import History')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-gradient-to-br from-slate-600 to-slate-800 p-3 shadow-lg">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">Import &amp; Update History</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Audit trail of every product import, preset, and bulk change.</p>
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

    {{-- Filters --}}
    <form method="GET" action="{{ route('products.history') }}"
        class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Action</label>
            <select name="action"
                class="h-9 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All product actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">User</label>
            <select name="user_id"
                class="h-9 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">Everyone</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                class="h-9 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                class="h-9 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="mb-1 block text-xs font-medium text-gray-600">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Description contains…"
                class="h-9 w-full rounded-md border border-gray-300 px-3 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="inline-flex h-9 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Filter</button>
            <a href="{{ route('products.history') }}"
                class="inline-flex h-9 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">Reset</a>
        </div>
    </form>

    {{-- Log table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($logs as $log)
                        @php
                            $props = $log->properties ?? [];
                            $badge = [
                                'product.imported'        => 'bg-blue-100 text-blue-700',
                                'product.preset_created'  => 'bg-violet-100 text-violet-700',
                                'product.preset_applied'  => 'bg-green-100 text-green-700',
                                'product.preset_discarded'=> 'bg-gray-100 text-gray-500',
                                'product.import_failed'   => 'bg-red-100 text-red-700',
                                'product.preset_apply_failed' => 'bg-red-100 text-red-700',
                            ][$log->action] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="align-top hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                <div class="font-medium text-gray-700">{{ $log->created_at->format('M d, Y') }}</div>
                                <div>{{ $log->created_at->format('h:i:s A') }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                {{ $log->user->name ?? 'System' }}
                                <div class="text-xs text-gray-400">{{ $log->user->role ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $badge }}">{{ $log->action }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                @php
                                    $detail = collect($props)->only(['table', 'inserted', 'updated', 'error_count', 'target_month', 'filename'])->filter(fn($v) => $v !== null && $v !== '');
                                @endphp
                                @if ($detail->isNotEmpty())
                                    <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                                        @foreach ($detail as $k => $v)
                                            <span><span class="text-gray-400">{{ $k }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No product activity recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
@endsection
