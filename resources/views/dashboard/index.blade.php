@extends('layouts.app')

@section('content')
    @php
        use App\Support\LocationConfig;

        $role = auth()->user()->role;
        $isAdmin = $role === 'super admin';
        $isMgr = $role === 'store manager';
        $isWarehouse = in_array($role, ['warehouse manager', 'warehouse personnel']);
        $isStore = in_array($role, ['store personnel', 'store admin']);
        $month = now()->format('F Y');

        $user = Auth::user();
        $code = trim((string) $user?->user_location);
        $storeLabel = LocationConfig::stores()[$code] ?? null;
        $regionLabel = LocationConfig::regionLabels()[$code] ?? null;
        $fullLocation = $storeLabel ?? ($regionLabel ?? $code);

        // Get selected time range from request
        $timeRange = request()->get('time_range', 'this_month');

        // Define date ranges
        $dateRanges = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_30_days' => [now()->subDays(30), now()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'all_time' => [null, null],
        ];

        $dateRange = $dateRanges[$timeRange] ?? $dateRanges['this_month'];
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        // ── Centralised order status badge classes ──────────────────────────
        $statusBadgeClasses = [
            'new order' => 'bg-blue-100 text-blue-700',
            // 'pending' => 'bg-yellow-100 text-yellow-700',
            'for approval' => 'bg-purple-100 text-purple-700',
            'approved' => 'bg-emerald-100 text-emerald-700',
            'completed' => 'bg-teal-100 text-teal-700',
            'rejected' => 'bg-orange-100 text-orange-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
            'archived' => 'bg-gray-100 text-gray-500',
        ];
    @endphp

    <div class="max-w-8xl mx-auto space-y-5 px-4 sm:px-6 lg:px-8">

        {{-- ══ PAGE HEADER ══════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between border-b border-gray-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ now()->format('l, F j, Y') }}
                    &middot;
                    {{ auth()->user()->name }}
                    @if ($fullLocation)
                        &middot; {{ $fullLocation }}
                    @endif
                    &middot;
                    <span class="font-medium text-blue-600">
                        @php
                            $rangeDisplay = match ($timeRange) {
                                'today' => 'Today (' . now()->format('M j, Y') . ')',
                                'this_month' => 'This Month (' . now()->format('F Y') . ')',
                                'last_30_days' => 'Last 30 Days (' . now()->subDays(30)->format('M j') . ' - ' . now()->format('M j, Y') . ')',
                                'this_year' => 'This Year (' . now()->format('Y') . ')',
                                default => 'All Time',
                            };
                        @endphp
                        {{ $rangeDisplay }}
                    </span>
                </p>
            </div>
        </div>

        {{-- ══ MANAGER CALLOUT (manager only) ══════════════════════════════════ --}}
        {{-- @if ($isMgr && $for_approval_count > 0)
            <a href="{{ route('orders.index', ['status' => 'for approval']) }}"
                class="flex items-center justify-between rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 transition hover:bg-purple-100">
                <div class="flex items-center gap-3">
                    <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-purple-500 text-xs font-bold text-white">
                        {{ $for_approval_count }}
                    </span>
                    <div>
                        <p class="text-xs font-semibold text-purple-800">Orders Awaiting Your Approval</p>
                        <p class="text-[10px] text-purple-600">Click to review and take action</p>
                    </div>
                </div>
                <svg class="h-4 w-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        @endif --}}

        {{-- Time Range Filter --}}
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-sm text-gray-600">Show:</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @php
                    $ranges = [
                        'today' => 'Today',
                        'this_month' => 'This Month',
                        'last_30_days' => 'Last 30 Days',
                        'this_year' => 'This Year',
                        'all_time' => 'All Time',
                    ];
                @endphp
                @foreach ($ranges as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['time_range' => $key]) }}"
                        class="{{ $timeRange === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} rounded-full px-4 py-1.5 text-xs font-medium transition">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ══ 3 · METRIC CARDS (role-based, icon + sub-line) ══════════════════ --}}
        @php
            $mIc = [
                'orders' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
                'bell' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
                'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'x' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'inbox' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
                'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                'peso' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            ];

            if ($isAdmin) {
                $kpiCards = [
                    ['Total Orders', $totalOrders, 'border-blue-400', $mIc['orders'], false, $amounts['total']],
                    ['For Approval', $for_approval_count, 'border-purple-400', $mIc['bell'], false, $amounts['for_approval']],
                    ['Approved', $approved_count, 'border-green-600', $mIc['check'], false, $amounts['approved']],
                    ['Completed', $completed_count, 'border-teal-400', $mIc['check'], false, $amounts['completed']],
                    ['Rejected', $rejected_count, 'border-orange-400', $mIc['x'], false, $amounts['rejected']],

                    // ['MTD Revenue', '₱' . number_format($mtdRevenue, 2), 'border-emerald-400', $mIc['peso'], true, null],
                ];
            } elseif ($isMgr) {
                $kpiCards = [
                    ['Total Orders', $totalOrders, 'border-blue-500', $mIc['orders'], false, $amounts['total']],
                    ['For Approval', $for_approval_count, 'border-purple-400', $mIc['bell'], false, $amounts['for_approval']],
                    ['Approved', $approved_count, 'border-green-600', $mIc['check'], false, $amounts['approved']],
                    ['Completed', $completed_count, 'border-teal-400', $mIc['check'], false, $amounts['completed']],
                    ['Rejected', $rejected_count, 'border-orange-400', $mIc['x'], false, $amounts['rejected']],
                ];
            } elseif ($isWarehouse) {
                $kpiCards = [
                    ['Total Orders', $totalOrders, 'border-blue-500', $mIc['orders'], false, $amounts['total']],
                    ['Approved', $approved_count, 'border-green-600', $mIc['check'], false, $amounts['approved']],
                    ['Completed', $completed_count, 'border-teal-400', $mIc['check'], false, $amounts['completed']],
                ];
            } else {
                $kpiCards = [
                    ['Total Orders', $totalOrders, 'border-blue-500', $mIc['orders'], false, $amounts['total']],
                    ['New Orders', $new_order_count, 'border-blue-400', $mIc['inbox'], false, $amounts['new_order']],
                    ['For Approval', $for_approval_count, 'border-purple-400', $mIc['bell'], false, $amounts['for_approval']],
                    ['Approved', $approved_count, 'border-green-600', $mIc['check'], false, $amounts['approved']],
                    ['Completed', $completed_count, 'border-teal-400', $mIc['check'], false, $amounts['completed']],
                ];
            }
        @endphp

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($kpiCards as [$label, $value, $border, $icon, $isRevenue, $amount])
                <div class="{{ $border }} rounded-xl border border-l-4 border-gray-200 bg-white px-4 py-4">
                    <div class="flex items-start justify-between">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">{!! $icon !!}</svg>
                        </span>
                    </div>
                    <p class="{{ $isRevenue ? 'text-xl' : 'text-2xl' }} mt-1 font-bold text-gray-900">
                        {{ $isRevenue ? $value : number_format($value) }}
                    </p>
                    @if ($isRevenue)
                        <p class="mt-0.5 text-[10px] text-gray-400">{{ $month }}</p>
                    @else
                        <p class="mt-0.5 text-sm font-semibold text-emerald-600">₱{{ number_format($amount ?? 0, 2) }}</p>
                        <p class="text-[10px] text-gray-400">
                            {{ $totalOrders > 0 ? round(($value / $totalOrders) * 100) : 0 }}% of total
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ══ ADMIN: secondary metric strip (admin only) ══════════════════════ --}}
        @if ($isAdmin)
            @php
                $adminSecondaryCards = [
                    ['Today', number_format($ordersToday), 'orders'],
                    ['This Week', number_format($ordersThisWeek), 'orders'],
                    ['All-time Revenue', '₱' . number_format($totalRevenue, 2), 'approved + completed'],
                    ['MTD Revenue', '₱' . number_format($mtdRevenue, 2), 'border-emerald-400', $mIc['peso'], true, null],
                    ['MTD Freebies Value', '₱' . number_format($mtdFreebiesValue, 2), $month],
                ];
            @endphp
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($adminSecondaryCards as $card)
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $card[0] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $card[1] }}</p>
                        <p class="text-[10px] text-gray-400">{{ $card[2] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ══ 4 · QUICK ACTIONS (horizontal, role-based) ══════════════════════ --}}
        @php
            $ic = [
                'list' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
                'plus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />',
                'cube' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />',
                'bars' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
                'doc' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                'card' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
                'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-3.72M9 20H4v-2a4 4 0 015-3.72M12 12a4 4 0 100-8 4 4 0 000 8z" />',
                'gear' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><circle cx="12" cy="12" r="3" />',
            ];

            if ($isAdmin) {
                $quickActions = [
                    ['Sales Orders', route('orders.index'), $ic['list'], 'bg-blue-50 text-blue-600', null],
                    ['Products', route('products.index'), $ic['cube'], 'bg-green-50 text-green-600', null],
                    ['Sales Overview', route('reports.sales'), $ic['bars'], 'bg-orange-50 text-orange-600', null],
                    ['Orders Report', route('reports.orders'), $ic['doc'], 'bg-orange-50 text-orange-600', null],
                    ['Payments', route('reports.payments'), $ic['card'], 'bg-orange-50 text-orange-600', null],
                    ['User Management', route('users.index'), $ic['users'], 'bg-purple-50 text-purple-600', null],
                    ['Settings', route('settings.index'), $ic['gear'], 'bg-gray-100 text-gray-500', null],
                ];
            } elseif ($isMgr) {
                $quickActions = [
                    ['Sales Orders', route('orders.index'), $ic['list'], 'bg-blue-50 text-blue-600', null],
                    ['Sales Overview', route('reports.sales'), $ic['bars'], 'bg-orange-50 text-orange-600', null],
                    ['Orders Report', route('reports.orders'), $ic['doc'], 'bg-orange-50 text-orange-600', null],
                    ['Payments', route('reports.payments'), $ic['card'], 'bg-orange-50 text-orange-600', null],
                ];
            } elseif ($isWarehouse) {
                $quickActions = [
                    ['Sales Orders', route('orders.index'), $ic['list'], 'bg-blue-50 text-blue-600', null],
                    ['Sales Overview', route('reports.sales'), $ic['bars'], 'bg-orange-50 text-orange-600', null],
                    ['Orders Report', route('reports.orders'), $ic['doc'], 'bg-orange-50 text-orange-600', null],
                    ['Payments', route('reports.payments'), $ic['card'], 'bg-orange-50 text-orange-600', null],
                ];
            } else {
                $quickActions = [
                    ['New Sales Order', route('forms.sof'), $ic['plus'], 'bg-indigo-50 text-indigo-600', null],
                    ['Sales Orders', route('orders.index'), $ic['list'], 'bg-blue-50 text-blue-600', null],
                    ['Products', route('products.index'), $ic['cube'], 'bg-green-50 text-green-600', null],
                    ['Sales Overview', route('reports.sales'), $ic['bars'], 'bg-orange-50 text-orange-600', null],
                    ['Orders Report', route('reports.orders'), $ic['doc'], 'bg-orange-50 text-orange-600', null],
                    ['Payments', route('reports.payments'), $ic['card'], 'bg-orange-50 text-orange-600', null],
                ];
            }
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">Quick Actions</h2>
            </div>
            <div class="flex flex-wrap gap-3 p-4">
                @foreach ($quickActions as [$label, $href, $icon, $chip, $badge])
                    <a href="{{ $href }}"
                        aria-label="{{ $label }}"
                        class="group relative flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left shadow-sm transition duration-200 ease-out hover:-translate-y-0.5 hover:border-gray-300 hover:bg-slate-50 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400">
                        <span class="{{ $chip }} flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg transition duration-200 ease-out group-hover:bg-opacity-90">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">{!! $icon !!}</svg>
                        </span>
                        <span class="text-xs font-medium text-gray-700 transition duration-200 group-hover:text-gray-900">{{ $label }}</span>
                        @if (!is_null($badge) && $badge > 0)
                            <span class="flex h-5 min-w-[20px] flex-shrink-0 items-center justify-center rounded-full bg-purple-500 px-1.5 text-[9px] font-semibold text-white">
                                {{ number_format($badge) }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ══ 1 · NEEDS ATTENTION (role‑specific, hidden for super admin) ═════ --}}
        @if (!$isAdmin && !$isWarehouse)
            @php
                $alertIc = [
                    'inbox' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
                    'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'bell' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
                    'x' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                ];

                if ($isMgr) {
                    $attention = [['For Approval', 'for approval', $for_approval_count, 'border-purple-200', 'bg-purple-50', 'text-purple-700', 'bg-purple-500', $alertIc['bell']]];
                } else {
                    $attention = [
                        ['New Orders', 'new order', $new_order_count, 'border-blue-200', 'bg-blue-50', 'text-blue-700', 'bg-blue-500', $alertIc['inbox']],
                        ['Approved', 'approved', $approved_count, 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'bg-emerald-500', $alertIc['check']],
                        ['Rejected', 'rejected', $rejected_count, 'border-orange-200', 'bg-orange-50', 'text-orange-700', 'bg-orange-500', $alertIc['x']],
                    ];
                }

                $attention = array_values(array_filter($attention, fn($a) => $a[2] > 0));
                $attentionTotal = array_sum(array_map(fn($a) => $a[2], $attention));
            @endphp

            @if ($attentionTotal > 0)
                <div class="overflow-hidden rounded-xl border border-orange-200 bg-white shadow-sm">
                    {{-- Header with flex alignment --}}
                    <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Attention Required</h2>
                                <p class="text-xs text-gray-500">Review orders and approvals that need your action.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center self-start rounded-full bg-gray-100 px-3 py-1 text-[10px] font-semibold text-gray-700 sm:self-center">
                            {{ number_format($attentionTotal) }} total items
                        </span>
                    </div>

                    {{-- Flexible cards layout: column on mobile, row on desktop --}}
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap">
                        @foreach ($attention as [$label, $statusKey, $count, $border, $bg, $text, $dot, $icon])
                            <a href="{{ route('orders.index', ['status' => $statusKey]) }}"
                                aria-label="{{ $label }}: {{ number_format($count) }} items"
                                class="{{ $border }} {{ $bg }} flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-sm transition duration-150 ease-in-out hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 sm:min-w-[200px] sm:flex-1">

                                <div class="flex flex-1 items-center gap-2.5">
                                    <span class="{{ $dot }} flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-white">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">{!! $icon !!}</svg>
                                    </span>
                                    <div class="flex-1">
                                        <span class="{{ $text }} text-xs font-semibold">{{ $label }}</span>
                                        @php
                                            // short next-step guidance per status and role
                                            if ($statusKey === 'pending') {
                                                if ($isAdmin) {
                                                    $next = 'Review and approve pending orders.';
                                                } elseif ($isMgr) {
                                                    $next = 'Review pending approvals and escalate if needed.';
                                                } elseif ($isStore) {
                                                    $next = 'Provide any missing order details or follow up on payment.';
                                                } else {
                                                    $next = 'Open the order to view details or contact support.';
                                                }
                                            } elseif ($statusKey === 'processing') {
                                                if ($isAdmin) {
                                                    $next = 'Assign resources and monitor fulfillment.';
                                                } elseif ($isMgr) {
                                                    $next = 'Coordinate teams and track processing progress.';
                                                } elseif ($isStore) {
                                                    $next = 'Prepare items for shipment and update order progress.';
                                                } else {
                                                    $next = 'Check order status or contact your manager for updates.';
                                                }
                                            } elseif ($statusKey === 'awaiting_payment' || $statusKey === 'payment-pending') {
                                                if ($isAdmin) {
                                                    $next = 'Verify payment and confirm to proceed.';
                                                } elseif ($isMgr) {
                                                    $next = 'Confirm payment status and follow up with finance if needed.';
                                                } elseif ($isStore) {
                                                    $next = 'Complete payment or contact support to resolve payment issues.';
                                                } else {
                                                    $next = 'Complete payment or contact support for assistance.';
                                                }
                                            } elseif ($statusKey === 'approved') {
                                                if ($isAdmin) {
                                                    $next = 'Schedule fulfillment and notify warehouse.';
                                                } elseif ($isMgr) {
                                                    // managers have no direct action for approved orders
                                                    $next = 'No action required.';
                                                } elseif ($isStore) {
                                                    // personnel/store: generate transfer and monitor if already generated
                                                    $next = 'Generate transfer, monitor item transfers and Complete the Order when released to customer.';
                                                } else {
                                                    $next = 'Await shipment confirmation or contact support.';
                                                }
                                            } elseif ($statusKey === 'shipped') {
                                                if ($isAdmin) {
                                                    $next = 'Track shipment and ensure delivery.';
                                                } elseif ($isMgr) {
                                                    $next = 'Monitor delivery and follow up on exceptions.';
                                                } elseif ($isStore) {
                                                    $next = 'Track your shipment and confirm receipt when delivered.';
                                                } else {
                                                    $next = 'Track shipment or contact support for delivery questions.';
                                                }
                                            } elseif ($statusKey === 'completed') {
                                                $next = 'Archive the order or request feedback from the customer.';
                                            } elseif ($statusKey === 'rejected') {
                                                if ($isAdmin) {
                                                    $next = 'Coordinate with store personnel to review the rejection.';
                                                } elseif ($isMgr) {
                                                    $next = 'Review rejection reasons and advise next steps.';
                                                } elseif ($isStore) {
                                                    $next = 'Review rejection details and update or resubmit if applicable.';
                                                } else {
                                                    $next = 'Review rejection details or contact support.';
                                                }
                                            } elseif ($statusKey === 'cancelled') {
                                                if ($isAdmin) {
                                                    $next = 'Review cancellation reason and process refunds if needed.';
                                                } elseif ($isMgr) {
                                                    $next = 'Confirm cancellation details and coordinate refunds.';
                                                } elseif ($isStore) {
                                                    $next = 'Contact support for help or place a new order if needed.';
                                                } else {
                                                    $next = 'Contact support for assistance or place a new order.';
                                                }
                                            } else {
                                                if ($isAdmin) {
                                                    $next = 'Open the order to view details and take appropriate action.';
                                                } elseif ($isMgr) {
                                                    $next = 'Open the order to review and coordinate next steps.';
                                                } elseif ($isStore) {
                                                    $next = 'Open the order to view next steps or contact support.';
                                                } else {
                                                    $next = 'Open the order to view details or contact support.';
                                                }
                                            }
                                        @endphp
                                        <p class="line-clamp-2 text-xs text-gray-500">{{ $next }}</p>
                                    </div>
                                </div>
                                <span class="{{ $text }} flex-shrink-0 text-2xl font-bold">{{ number_format($count) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif







        {{-- ══ 6 · STORE PERFORMANCE (admin only) ══════════════════════════════ --}}
        @if ($isAdmin && $topStores->isNotEmpty())
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-gray-800">Store Performance</h2>
                    <span class="text-[10px] text-gray-400">top stores · all-time</span>
                </div>

                {{-- mobile cards --}}
                <div class="space-y-2 px-5 py-4 sm:hidden">
                    @foreach ($topStores as $i => $store)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $i + 1 }}. {{ $store->name }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500">Store</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ number_format($store->total) }}</p>
                                    <p class="text-[11px] text-gray-500">Orders</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- desktop table --}}
                <div class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-left text-[10px] uppercase tracking-wide text-gray-500">
                                <th class="w-6 px-5 py-2 font-medium">#</th>
                                <th class="px-4 py-2 font-medium">Store</th>
                                <th class="px-5 py-2 text-right font-medium">Orders</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($topStores as $i => $store)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-2 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $store->name }}</td>
                                    <td class="px-5 py-2 text-right font-semibold text-gray-900">{{ number_format($store->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif



        {{-- ══ INVENTORY SNAPSHOT (store personnel only) ═══════════════════════ --}}
        @if ($isStore && $totalProducts > 0)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-gray-800">Inventory Snapshot</h2>
                    @if ($warehouseName)
                        <span class="text-[10px] text-gray-400">{{ $warehouseName }}</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 divide-x divide-gray-100 text-center">
                    <div class="px-3 py-4">
                        <p class="text-xl font-bold text-emerald-600">{{ number_format($healthyStockCount) }}</p>
                        <p class="text-[10px] text-gray-500">In Stock</p>
                    </div>
                    <div class="px-3 py-4">
                        <p class="text-xl font-bold text-yellow-500">{{ number_format($lowStockCount) }}</p>
                        <p class="text-[10px] text-gray-500">Low Stock</p>
                    </div>
                    <div class="px-3 py-4">
                        <p class="text-xl font-bold text-red-500">{{ number_format($outOfStockCount) }}</p>
                        <p class="text-[10px] text-gray-500">Out of Stock</p>
                    </div>
                </div>
                @if ($wmsSkuCount > 0)
                    <div class="border-t border-gray-100 px-5 py-2 text-xs text-gray-500">
                        WMS Virtual: <span class="font-medium text-gray-700">{{ number_format($wmsVirtualTotal) }} units</span>
                        across {{ number_format($wmsSkuCount) }} SKUs
                        @if ($wmsLastUpdated)
                            · {{ $wmsLastUpdated->diffForHumans() }}
                        @endif
                    </div>
                @endif
            </div>
        @endif


        {{-- ══ 8 · RECENT ORDERS (all roles, last) ═════════════════════════════ --}}
        <div class="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-blue-100 px-5 py-3">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h2 class="text-sm font-semibold text-gray-900">Recent Orders</h2>
                </div>
                <a href="{{ route('orders.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">View all →</a>
            </div>

            @if ($recentOrders->isEmpty())
                <p class="py-12 text-center text-xs text-gray-400">No orders yet.</p>
            @else
                {{-- Mobile: stacked cards --}}
                <div class="divide-y divide-blue-50 sm:hidden">
                    @foreach ($recentOrders as $order)
                        @php $cls = $statusBadgeClasses[strtolower($order->order_status)] ?? 'bg-gray-100 text-gray-600'; @endphp
                        <a href="{{ route('orders.show', $order->id) }}" class="block px-5 py-3 transition hover:bg-blue-50/30">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-sm font-medium text-gray-800">{{ $order->sof_id }}</span>
                                <span class="{{ $cls }} inline-block flex-shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium">
                                    {{ ucwords($order->order_status) }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-gray-500">
                                <span class="truncate">
                                    {{ $order->channel_order ?? '—' }}
                                    @if (($isAdmin || $isMgr) && $order->store_name)
                                        · {{ $order->store_name }}
                                    @endif
                                </span>
                                <span class="flex-shrink-0 text-gray-400">{{ $order->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="mt-2 text-left text-[10px] font-medium text-blue-600">
                                View →
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <div class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-blue-50/30 text-left text-[10px] uppercase tracking-wide text-gray-500">
                                <th class="px-5 py-2.5 font-medium">SOF ID</th>
                                @if ($isAdmin || $isMgr)
                                    <th class="px-4 py-2.5 font-medium">Store</th>
                                @endif
                                {{-- customer --}}
                                <th class="px-4 py-2.5 font-medium">Customer</th>
                                <th class="px-4 py-2.5 font-medium">Channel</th>
                                <th class="px-4 py-2.5 font-medium">Status</th>
                                <th class="px-4 py-2.5 font-medium">Date</th>
                                <th class="px-4 py-2.5 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50">
                            @foreach ($recentOrders as $order)
                                @php $cls = $statusBadgeClasses[strtolower($order->order_status)] ?? 'bg-gray-100 text-gray-600'; @endphp
                                <tr data-href="{{ route('orders.show', $order->id) }}"
                                    class="group cursor-pointer transition-colors hover:bg-blue-50/30">
                                    <td class="px-5 py-2.5 font-mono font-medium text-gray-800">{{ $order->sof_id }}</td>
                                    @if ($isAdmin || $isMgr)
                                        <td class="max-w-[130px] truncate px-4 py-2.5 text-gray-500">{{ $order->store_name }}</td>
                                    @endif
                                    <td class="px-4 py-2.5 text-gray-500">{{ $order->customer_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $order->channel_order ?? '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="{{ $cls }} inline-block rounded-full px-2 py-0.5 text-[10px] font-medium">
                                            {{ ucwords($order->order_status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-gray-400">{{ $order->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-2.5 font-medium text-blue-600 group-hover:text-blue-800">View →</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ══ 7 · ACTIVITY FEED (super admin only) — from activity_logs ═══════ --}}
        @if ($isAdmin)
            @php
                // 2 columns, filled top-to-bottom. 10 items → 5 rows × 2 cols.
                $logCount = $recentActivityLogs->count();
                $logRows = max(1, (int) ceil($logCount / 2));
                $logCols = max(1, (int) ceil($logCount / $logRows));

                // min-content rows = size each row to its content (no stretching).
                $logRowsClass =
                    [
                        1 => 'sm:grid-rows-[repeat(1,min-content)]',
                        2 => 'sm:grid-rows-[repeat(2,min-content)]',
                        3 => 'sm:grid-rows-[repeat(3,min-content)]',
                        4 => 'sm:grid-rows-[repeat(4,min-content)]',
                        5 => 'sm:grid-rows-[repeat(5,min-content)]',
                        6 => 'sm:grid-rows-[repeat(6,min-content)]',
                        7 => 'sm:grid-rows-[repeat(7,min-content)]',
                        8 => 'sm:grid-rows-[repeat(8,min-content)]',
                        9 => 'sm:grid-rows-[repeat(9,min-content)]',
                        10 => 'sm:grid-rows-[repeat(10,min-content)]',
                    ][$logRows] ?? 'sm:grid-rows-[repeat(5,min-content)]';
            @endphp
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-gray-800">Activity Feed</h2>
                    <span class="text-[10px] text-gray-400">recent system activity</span>
                </div>
                @if ($recentActivityLogs->isEmpty())
                    <p class="py-12 text-center text-xs text-gray-400">No recent activity.</p>
                @else
                    <ul class="{{ $logRowsClass }} grid grid-cols-1 divide-y divide-gray-100 sm:grid-flow-col sm:grid-cols-2 sm:divide-y-0">
                        @foreach ($recentActivityLogs as $i => $log)
                            @php
                                $props = is_array($log->properties) ? $log->properties : (json_decode($log->properties ?? '[]', true) ?: []);

                                $orderId = $props['order_id'] ?? null;
                                $sofId = $props['sof_id'] ?? null;
                                $storeRaw = $props['store'] ?? null;
                                $store = $storeRaw ? LocationConfig::storeName($storeRaw, $storeRaw) : null;

                                $statusKey = str_replace(['order.', '_'], ['', ' '], strtolower($log->action));

                                $fc = [
                                    'approved' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
                                    'rejected' => ['dot' => 'bg-orange-500', 'text' => 'text-orange-700'],
                                    'for approval' => ['dot' => 'bg-purple-500', 'text' => 'text-purple-700'],
                                    'cancelled' => ['dot' => 'bg-gray-400', 'text' => 'text-gray-500'],
                                    'completed' => ['dot' => 'bg-teal-500', 'text' => 'text-teal-700'],
                                    'updated' => ['dot' => 'bg-blue-400', 'text' => 'text-blue-600'],
                                    'restored' => ['dot' => 'bg-indigo-400', 'text' => 'text-indigo-600'],
                                    'archived' => ['dot' => 'bg-gray-300', 'text' => 'text-gray-500'],
                                ][$statusKey] ?? ['dot' => 'bg-gray-300', 'text' => 'text-gray-500'];

                                // Column-major borders: right divider except last column, top divider except first row of each column.
                                $br = intdiv($i, $logRows) < $logCols - 1 ? 'sm:border-r sm:border-gray-100' : '';
                                $bt = $i % $logRows > 0 ? 'sm:border-t sm:border-gray-100' : '';
                            @endphp
                            <li class="{{ $br }} {{ $bt }} flex items-start gap-2.5 px-5 py-2.5">
                                <span class="{{ $fc['dot'] }} mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        @if ($orderId)
                                            <a href="{{ route('orders.show', $orderId) }}"
                                                class="truncate text-xs font-semibold text-gray-800 hover:text-blue-600">
                                                {{ $sofId ?? '#' . $orderId }}
                                            </a>
                                        @else
                                            <span class="truncate text-xs font-semibold text-gray-800">{{ $sofId ?? ucfirst($statusKey) }}</span>
                                        @endif
                                        <span class="flex-shrink-0 text-[10px] text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="{{ $fc['text'] }} text-[10px] font-medium uppercase tracking-wide">{{ $statusKey }}</p>
                                    <p class="truncate text-[10px] text-gray-500">
                                        {{ $log->user?->name ?? 'System' }}
                                        @if ($store)
                                            · {{ $store }}
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- ══ ADMIN: Cancelled / Rejected alert (admin only) ══════════════════ --}}
        @if ($isAdmin && $cancelled_count + $rejected_count > 0)
            <div class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <span>
                    <strong>{{ number_format($cancelled_count + $rejected_count) }}</strong> orders are cancelled or rejected
                    ({{ number_format($cancelled_count) }} cancelled, {{ number_format($rejected_count) }} rejected).
                    <a href="{{ route('orders.index', ['status' => 'cancelled']) }}" class="ml-1 underline hover:text-red-900">Review</a>
                </span>
            </div>
        @endif

    </div>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('click', function (e) {
            var row = e.target.closest('tr[data-href]');
            if (row) window.location = row.getAttribute('data-href');
        });
    </script>
@endsection
