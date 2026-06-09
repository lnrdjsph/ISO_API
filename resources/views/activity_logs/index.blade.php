@extends('layouts.app')

@section('content')
    @php
        if (!auth()->user() || !auth()->user()->role) {
            return redirect()->route('login')->send();
        }
        if (auth()->user()->role !== 'super admin') {
            return redirect('/403')->send();
        }
    @endphp

    @php
        // Color mapping for action categories
        $categoryColors = [
            'auth' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
            'user' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
            'order' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'dot' => 'bg-green-500'],
            'product' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
            'mbc' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'dot' => 'bg-indigo-500'],
        ];
        $defaultColor = ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'dot' => 'bg-gray-500'];
    @endphp

    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═══ Header ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="rounded-xl bg-gradient-to-br from-gray-900 to-indigo-900 p-3 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Activity Logs</h1>
                    <p class="mt-1 text-gray-600">Audit trail of all system activity</p>
                </div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm">
                <span class="text-xs font-medium text-gray-500">Total Records</span>
                <p class="text-xl font-bold text-gray-900">{{ number_format($logs->total()) }}</p>
            </div>
        </div>

        {{-- ═══ Filters ═══ --}}
        @php
            // Group actions under their category for a single smart dropdown (no separate category filter needed)
            $groupedActions = $actions->groupBy(fn($a) => explode('.', $a)[0]);

            // Date presets — the way people actually query logs
            $today = now()->toDateString();
            $datePresets = [
                'Today' => ['from' => $today, 'to' => $today],
                'Last 7 days' => ['from' => now()->subDays(6)->toDateString(), 'to' => $today],
                'Last 30 days' => ['from' => now()->subDays(29)->toDateString(), 'to' => $today],
            ];
            $isAllTime = !request('from') && !request('to');

            // Active filter chips
            $activeChips = [];
            if (request('search')) {
                $activeChips['search'] = 'Search: "' . request('search') . '"';
            }
            if (request('action')) {
                $activeChips['action'] = request('action');
            }
            if (request('user_id')) {
                $u = $users->firstWhere('id', request('user_id'));
                $activeChips['user_id'] = $u->name ?? request('user_id');
            }
            if (request('from') || request('to')) {
                $activeChips['from'] = (request('from') ?: '…') . ' → ' . (request('to') ?: '…');
            }
        @endphp

        <div class="mb-6 space-y-3">

            {{-- Primary row: search + the two most-used dropdowns --}}
            <form method="GET" action="{{ route('activity_logs.index') }}" id="filterForm"
                class="flex flex-col gap-3 sm:flex-row sm:items-center">

                {{-- preserve date filters when searching --}}
                @if (request('from'))
                    <input type="hidden" name="from" value="{{ request('from') }}">
                @endif
                @if (request('to'))
                    <input type="hidden" name="to" value="{{ request('to') }}">
                @endif

                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                        </svg>
                    </span>
                    <input type="text" name="search" placeholder="Search activity…"
                        value="{{ request('search') }}"
                        class="w-full rounded-xl border-0 bg-white py-3 pl-11 pr-4 text-sm text-gray-800 shadow-sm ring-1 ring-inset ring-gray-200 transition placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500">
                </div>

                {{-- Single grouped Action dropdown — replaces Category + Action --}}
                <select name="action" data-autosubmit="filterForm"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All activity</option>
                    @foreach ($groupedActions as $cat => $catActions)
                        <optgroup label="{{ ucfirst($cat) }}">
                            @foreach ($catActions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ $action }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <select name="user_id" data-autosubmit="filterForm"
                    class="rounded-xl border-0 bg-white py-3 pl-3.5 pr-9 text-sm text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 transition focus:ring-2 focus:ring-indigo-500">
                    <option value="">All users</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </form>

            {{-- Secondary row: date presets (one-click) + active chips --}}
            <div class="flex flex-wrap items-center gap-2">

                {{-- Quick date presets --}}
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5">
                    <a href="{{ route('activity_logs.index', request()->except(['from', 'to', 'page'])) }}"
                        class="{{ $isAllTime ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                        All time
                    </a>
                    @foreach ($datePresets as $label => $range)
                        @php $isActive = request('from') == $range['from'] && request('to') == $range['to']; @endphp
                        <a href="{{ route('activity_logs.index', array_merge(request()->except(['from', 'to', 'page']), $range)) }}"
                            class="{{ $isActive ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' }} rounded-md px-3 py-1.5 text-xs font-medium transition">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                {{-- Custom range — tucked to the side, only for when presets aren't enough --}}
                <form method="GET" action="{{ route('activity_logs.index') }}" class="inline-flex items-center gap-1.5">
                    @foreach (request()->except(['from', 'to', 'page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <input type="date" name="from" value="{{ request('from') }}"
                        class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <span class="text-xs text-gray-400">→</span>
                    <input type="date" name="to" value="{{ request('to') }}"
                        class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                    <button type="submit"
                        class="rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-900">Go</button>
                </form>

                {{-- Result count, pushed to the right --}}
                <span class="ml-auto text-xs text-gray-400">{{ number_format($logs->total()) }} records</span>
            </div>

            {{-- Active filter chips --}}
            @if (!empty($activeChips))
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($activeChips as $key => $label)
                        @php
                            $remove = $key === 'from' ? request()->except(['from', 'to', 'page']) : request()->except([$key, 'page']);
                        @endphp
                        <a href="{{ route('activity_logs.index', $remove) }}"
                            class="group inline-flex items-center gap-1.5 rounded-full bg-indigo-50 py-1 pl-3 pr-2 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-100 transition hover:bg-indigo-100">
                            {{ $label }}
                            <span class="flex h-4 w-4 items-center justify-center rounded-full text-indigo-400 group-hover:bg-indigo-200 group-hover:text-indigo-700">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                    <a href="{{ route('activity_logs.index') }}"
                        class="text-xs font-semibold text-gray-500 hover:text-gray-700 hover:underline">Clear all</a>
                </div>
            @endif
        </div>

        {{-- ═══ Table ═══ --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="w-10 px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">When</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            @php
                                $category = explode('.', $log->action)[0] ?? '';
                                $color = $categoryColors[$category] ?? $defaultColor;
                                $props = $log->properties ?? [];
                                $ip = $props['ip_address'] ?? '—';
                            @endphp

                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <button type="button"
                                        data-toggle-details="log-{{ $log->id }}"
                                        class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                        title="View details">
                                        <svg id="icon-log-{{ $log->id }}" class="h-4 w-4 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    <div class="font-medium text-gray-900">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->created_at->format('h:i:s A') }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($log->user)
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-xs font-bold text-white">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $log->user->name }}</div>
                                                <div class="text-xs text-gray-500">{{ ucwords($log->user->role) }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs italic text-gray-400">System / Guest</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="{{ $color['bg'] }} {{ $color['text'] }} inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                                        <span class="{{ $color['dot'] }} inline-block h-1.5 w-1.5 rounded-full"></span>
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $log->description }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">{{ $ip }}</td>
                            </tr>

                            {{-- Expandable details row --}}
                            <tr id="log-{{ $log->id }}" class="hidden bg-gray-50/70">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-inner">
                                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Properties</h4>
                                        @if (empty($props))
                                            <p class="text-sm italic text-gray-400">No additional properties</p>
                                        @else
                                            <pre class="overflow-x-auto rounded-md bg-gray-900 p-4 text-xs leading-relaxed text-green-300"><code>{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-16 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="mt-3 text-sm text-gray-500">No activity logs found matching your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>

    <script @if (isset($cspNonce)) nonce="{{ $cspNonce }}" @endif>
        // Delegate filter-select auto-submit
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () {
                var formId = el.getAttribute('data-autosubmit');
                var form = document.getElementById(formId);
                if (form) form.submit();
            });
        });

        // Delegate expandable row toggle
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-toggle-details]');
            if (!btn) return;
            var rowId = btn.getAttribute('data-toggle-details');
            var row  = document.getElementById(rowId);
            var icon = document.getElementById('icon-' + rowId);
            if (!row || !icon) return;
            if (row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                icon.style.transform = 'rotate(90deg)';
            } else {
                row.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        });
    </script>
@endsection
