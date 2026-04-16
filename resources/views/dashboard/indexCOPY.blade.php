@extends('layouts.app')

@section('content')

    <style nonce="{{ $cspNonce ?? '' }}">
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Sora:wght@400;500;600;700;800&display=swap');

        .font-sora {
            font-family: 'Sora', system-ui, sans-serif;
        }

        .font-cormorant {
            font-family: 'Cormorant Garamond', Georgia, serif;
        }

        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp 0.4s ease both;
        }

        .fade-d1 {
            animation-delay: 0.02s;
        }

        .fade-d2 {
            animation-delay: 0.06s;
        }

        .fade-d3 {
            animation-delay: 0.10s;
        }

        .fade-d4 {
            animation-delay: 0.14s;
        }

        .fade-d5 {
            animation-delay: 0.18s;
        }

        .fade-d6 {
            animation-delay: 0.22s;
        }

        .fade-d7 {
            animation-delay: 0.26s;
        }

        .fade-d8 {
            animation-delay: 0.30s;
        }

        .row-link {
            cursor: pointer;
        }

        .row-link:active {
            background: rgba(16, 185, 129, 0.04);
        }

        .topmargin {
            margin-top: -1rem;
        }
    </style>

    @php
        $user = auth()->user();
        $userName = $user?->name ?? 'there';
        $userRole = $user?->role ?? '';
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');

        $isManager = $userRole === 'manager';
        $isSuperAdmin = $userRole === 'super admin';
        $isRegularUser = !$isManager && !$isSuperAdmin;

        $statusClassMap = [
            'new order' => 'new',
            'pending' => 'pending',
            'for approval' => 'approval',
            'approved' => 'approved',
            'completed' => 'completed',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'archived' => 'archived',
        ];
        $statusDotColors = [
            'new' => 'bg-blue-600',
            'pending' => 'bg-yellow-600',
            'approval' => 'bg-violet-600',
            'approved' => 'bg-emerald-700',
            'completed' => 'bg-teal-600',
            'rejected' => 'bg-red-600',
            'cancelled' => 'bg-zinc-500',
            'archived' => 'bg-zinc-400',
        ];
        $statusTextColors = [
            'new' => 'text-blue-700',
            'pending' => 'text-yellow-700',
            'approval' => 'text-violet-700',
            'approved' => 'text-emerald-700',
            'completed' => 'text-teal-700',
            'rejected' => 'text-red-700',
            'cancelled' => 'text-zinc-500',
            'archived' => 'text-zinc-400',
        ];
    @endphp

    <div class="font-sora topmargin text-zinc-900 antialiased">

        {{-- ═══ HEADER ═══ --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 px-6 pb-16 pt-6">
            <div class="pointer-events-none absolute inset-0 opacity-[0.03]"
                style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>
            <div class="pointer-events-none absolute -right-20 -top-32 h-[400px] w-[400px] rounded-full bg-emerald-500/[0.06] blur-3xl"></div>

            <div class="relative z-10 mx-auto max-w-[1200px]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="font-cormorant text-[1.7rem] font-normal leading-tight text-zinc-100">
                            Good <i class="font-medium text-emerald-300">{{ $greeting }}</i>, {{ $userName }}
                        </h1>
                        <div class="mt-1 flex items-center gap-2 text-[0.73rem] font-medium text-zinc-500">
                            <span>{{ now()->format('l, F j, Y') }}</span>
                            <span class="inline-block h-1 w-1 rounded-full bg-zinc-600"></span>
                            <span>
                                @if ($isSuperAdmin)
                                    System Overview
                                @elseif ($isManager)
                                    Region Overview
                                @else
                                    Operations Overview
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @foreach ([['val' => $ordersToday, 'label' => 'Today'], ['val' => $ordersThisWeek, 'label' => 'This week'], ['val' => $ordersThisMonth, 'label' => 'This month']] as $c)
                            <div class="flex min-w-[68px] flex-col items-center rounded-md border border-white/[0.06] bg-white/[0.03] px-3 py-2">
                                <span class="text-[1.2rem] font-extrabold tabular-nums leading-none text-white">{{ $c['val'] }}</span>
                                <span class="mt-1 text-[0.58rem] font-semibold uppercase tracking-widest text-zinc-500">{{ $c['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ KPI CARDS ═══ --}}
        <div class="fade-up fade-d1 relative z-10 mx-auto -mt-10 max-w-[1200px] px-6">
            <div class="grid grid-cols-4 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm max-[860px]:grid-cols-2 max-[480px]:grid-cols-1">
                @foreach ([['label' => 'New Orders', 'val' => $new_order_count, 'sub' => 'Awaiting processing', 'dot' => 'bg-blue-500', 'text' => 'text-blue-600'], ['label' => 'For Approval', 'val' => $for_approval_count, 'sub' => 'Needs manager review', 'dot' => 'bg-violet-500', 'text' => 'text-violet-600'], ['label' => 'Approved', 'val' => $approved_count, 'sub' => 'Ready to fulfill', 'dot' => 'bg-emerald-500', 'text' => 'text-emerald-600'], ['label' => 'Completed', 'val' => $completed_count, 'sub' => 'Fulfilled orders', 'dot' => 'bg-teal-500', 'text' => 'text-teal-600']] as $k)
                    <div
                        class="relative border-r border-zinc-100 px-5 py-4 last:border-r-0 max-[860px]:odd:border-r max-[480px]:border-b max-[480px]:border-r-0 max-[480px]:last:border-b-0 max-[860px]:[&:nth-child(-n+2)]:border-b">
                        <div class="flex items-center gap-1.5">
                            <span class="{{ $k['dot'] }} inline-block h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.07em] text-zinc-400">{{ $k['label'] }}</span>
                        </div>
                        <div class="{{ $k['text'] }} mt-1.5 text-[1.8rem] font-extrabold tabular-nums leading-none tracking-tight">{{ $k['val'] }}</div>
                        <div class="mt-1 text-[0.65rem] font-medium text-zinc-400">{{ $k['sub'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══ BODY ═══ --}}
        <div class="mx-auto max-w-[1200px] px-6 pb-10">

            {{-- ═══ QUICK ACTIONS (role-aware) ═══ --}}
            <div class="fade-up fade-d2 mt-5 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                <div class="flex items-center gap-4 border-b border-zinc-100 px-5 py-3">
                    <span class="flex-shrink-0 text-[0.65rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Quick Actions</span>
                    <div class="h-px flex-1 bg-zinc-100"></div>
                </div>
                <div class="flex flex-wrap gap-2 px-5 py-3">
                    @php
                        // ── SUPER ADMIN actions ──
                        if ($isSuperAdmin) {
                            $actions = [
                                [
                                    'route' => 'orders.index',
                                    'label' => 'All Orders',
                                    'icon' =>
                                        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                                    'accent' => true,
                                ],
                                ['route' => 'forms.sof', 'label' => 'New Sales Order', 'icon' => 'M12 4v16m8-8H4', 'accent' => false],
                                ['route' => 'forms.rof', 'label' => 'Request Order', 'icon' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6', 'accent' => false],
                                ['route' => 'products.index', 'label' => 'Products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'accent' => false],
                                [
                                    'route' => 'users.index',
                                    'label' => 'Manage Users',
                                    'icon' => 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 8 0 4 4 0 0 0-8 0 M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
                                    'accent' => false,
                                ],
                                ['route' => 'reports.sales', 'label' => 'Sales Report', 'icon' => 'M18 20V10M12 20V4M6 20v-6', 'accent' => false],
                                ['route' => 'reports.orders', 'label' => 'Orders Report', 'icon' => 'M4 20h16M4 4v16 M4 14l4-4 4 3 6-6 2 2', 'accent' => false],
                                ['route' => 'reports.payments', 'label' => 'Payments', 'icon' => 'M1 4h22v16H1z M1 10h22', 'accent' => false],
                            ];
                        }
                        // ── MANAGER actions ──
                        elseif ($isManager) {
                            $actions = [
                                ['route' => 'orders.index', 'label' => 'Review & Approve', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'accent' => true],
                                ['route' => 'reports.sales', 'label' => 'Sales Report', 'icon' => 'M18 20V10M12 20V4M6 20v-6', 'accent' => false],
                                ['route' => 'reports.orders', 'label' => 'Orders Report', 'icon' => 'M4 20h16M4 4v16 M4 14l4-4 4 3 6-6 2 2', 'accent' => false],
                                ['route' => 'reports.payments', 'label' => 'Payments', 'icon' => 'M1 4h22v16H1z M1 10h22', 'accent' => false],
                            ];
                        }
                        // ── REGULAR USER actions ──
                        else {
                            $actions = [
                                [
                                    'route' => 'orders.index',
                                    'label' => 'All Orders',
                                    'icon' =>
                                        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                                    'accent' => true,
                                ],
                                ['route' => 'forms.sof', 'label' => 'New Sales Order', 'icon' => 'M12 4v16m8-8H4', 'accent' => false],
                                ['route' => 'forms.rof', 'label' => 'Request Order', 'icon' => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6', 'accent' => false],
                                ['route' => 'products.index', 'label' => 'Products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'accent' => false],
                                ['route' => 'reports.sales', 'label' => 'Sales Report', 'icon' => 'M18 20V10M12 20V4M6 20v-6', 'accent' => false],
                                ['route' => 'reports.orders', 'label' => 'Orders Report', 'icon' => 'M4 20h16M4 4v16 M4 14l4-4 4 3 6-6 2 2', 'accent' => false],
                                ['route' => 'reports.payments', 'label' => 'Payments', 'icon' => 'M1 4h22v16H1z M1 10h22', 'accent' => false],
                            ];
                        }
                    @endphp
                    @foreach ($actions as $a)
                        @if ($a['accent'] ?? false)
                            <a href="{{ route($a['route']) }}"
                                class="group inline-flex items-center gap-2 rounded-md border border-emerald-600 bg-emerald-700 px-3.5 py-2 text-[0.74rem] font-semibold text-white shadow-sm transition-all hover:bg-emerald-800 active:scale-[0.98]">
                                <svg class="h-[15px] w-[15px] text-emerald-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="{{ $a['icon'] }}" />
                                </svg>
                                {{ $a['label'] }}
                            </a>
                        @else
                            <a href="{{ route($a['route']) }}"
                                class="group inline-flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3.5 py-2 text-[0.74rem] font-semibold text-zinc-500 shadow-sm transition-all hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 active:scale-[0.98]">
                                <svg class="h-[15px] w-[15px] text-zinc-400 transition-colors group-hover:text-emerald-600" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path d="{{ $a['icon'] }}" />
                                </svg>
                                {{ $a['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- ═══ MANAGER: Approval Queue Callout ═══ --}}
            @if ($isManager && $for_approval_count > 0)
                <div class="fade-up fade-d3 mt-4 flex items-center gap-4 rounded-lg border border-violet-200 bg-violet-50 px-5 py-3.5">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-violet-100">
                        <svg class="h-4.5 w-4.5 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-[0.78rem] font-semibold text-violet-900">
                            {{ $for_approval_count }} {{ Str::plural('order', $for_approval_count) }} waiting for your approval
                        </p>
                        <p class="mt-0.5 text-[0.65rem] font-medium text-violet-600">Review and approve pending orders from your region's stores</p>
                    </div>
                    <a href="{{ route('orders.index') }}"
                        class="flex-shrink-0 rounded-md bg-violet-600 px-3.5 py-2 text-[0.72rem] font-semibold text-white shadow-sm transition-all hover:bg-violet-700 active:scale-[0.98]">
                        Review Now
                    </a>
                </div>
            @endif

            {{-- ═══ REVENUE + STATUS BAR ═══ --}}
            <div class="fade-up fade-d3 mt-4 grid grid-cols-[1fr_2fr] overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm max-[700px]:grid-cols-1">
                <div class="flex flex-col justify-center border-r border-zinc-100 px-5 py-4 max-[700px]:border-b max-[700px]:border-r-0">
                    <span class="text-[0.65rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Revenue</span>
                    <span class="mt-1 text-[1.5rem] font-extrabold tabular-nums leading-none tracking-tight text-emerald-700">₱{{ number_format($totalRevenue, 2) }}</span>
                    <span class="mt-1.5 text-[0.67rem] font-medium text-zinc-400">
                        From <b class="font-bold text-zinc-600">{{ $approved_count }}</b> approved
                        + <b class="font-bold text-zinc-600">{{ $completed_count }}</b> completed orders
                    </span>
                </div>
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.65rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Order Distribution</span>
                        <span class="text-[0.72rem] font-bold tabular-nums text-zinc-700">{{ $totalOrders }} total</span>
                    </div>
                    @if ($totalOrders > 0)
                        <div class="mt-2.5 flex h-1.5 overflow-hidden rounded-full bg-zinc-100">
                            @foreach ($statusBreakdown as $label => $s)
                                <div class="min-w-[3px] transition-all duration-500" style="width:{{ ($s['count'] / $totalOrders) * 100 }}%; background:{{ $s['color'] }}"></div>
                            @endforeach
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                            @foreach ($statusBreakdown as $label => $s)
                                <div class="flex items-center gap-1.5 text-[0.67rem] font-medium text-zinc-500">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full" style="background:{{ $s['color'] }}"></span>
                                    {{ $label }}
                                    <span class="font-bold tabular-nums text-zinc-800">{{ $s['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-zinc-400">No orders yet</p>
                    @endif
                </div>
            </div>

            {{-- ═══ SECTION DIVIDER ═══ --}}
            <div class="fade-up fade-d4 mt-7 flex items-center gap-3">
                <span class="text-[0.58rem] font-bold uppercase tracking-[0.1em] text-zinc-400">Details</span>
                <div class="h-px flex-1 bg-zinc-200"></div>
            </div>

            {{-- ═══ MAIN GRID ═══ --}}
            <div class="mt-4 grid grid-cols-[1.2fr_0.8fr] gap-4 max-[860px]:grid-cols-1">

                {{-- ─── LEFT: Recent Orders ─── --}}
                <div class="fade-up fade-d5 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                        <span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-zinc-400">Recent Orders</span>
                        <a href="{{ route('orders.index') }}" class="text-[0.67rem] font-semibold text-emerald-700 transition-colors hover:text-emerald-900">View all &rarr;</a>
                    </div>
                    @if ($recentOrders->count())
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-zinc-50/70">
                                    <th class="px-5 py-2 text-left text-[0.58rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Customer</th>
                                    <th class="px-3 py-2 text-left text-[0.58rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Status</th>
                                    <th class="px-3 py-2 text-right text-[0.58rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Date</th>
                                    <th class="w-8 px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $o)
                                    @php $sc = $statusClassMap[$o->order_status] ?? 'pending'; @endphp
                                    <tr class="row-link group border-b border-zinc-50 transition-colors last:border-b-0 hover:bg-emerald-50/40"
                                        onclick="window.location='{{ route('orders.show', $o->id) }}'">
                                        <td class="px-5 py-2.5">
                                            <span class="text-[0.78rem] font-semibold text-zinc-800 transition-colors group-hover:text-emerald-700">
                                                {{ Str::limit($o->customer_name ?? '—', 24) }}
                                            </span>
                                            <span class="mt-0.5 block text-[0.64rem] font-medium text-zinc-400">{{ Str::limit($o->store_name, 24) }}</span>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <span class="{{ $statusTextColors[$sc] ?? 'text-zinc-500' }} inline-flex items-center gap-1.5 text-[0.67rem] font-semibold">
                                                <span class="{{ $statusDotColors[$sc] ?? 'bg-zinc-400' }} inline-block h-[6px] w-[6px] rounded-full"></span>
                                                {{ ucwords($o->order_status) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-[0.68rem] tabular-nums text-zinc-400">
                                            {{ \Carbon\Carbon::parse($o->created_at)->format('M j, g:ia') }}
                                        </td>
                                        <td class="px-2 py-2.5 text-right">
                                            <svg class="inline-block h-3.5 w-3.5 text-zinc-300 transition-colors group-hover:text-emerald-500" fill="none" stroke="currentColor"
                                                stroke-width="2.5" viewBox="0 0 24 24">
                                                <path d="M9 18l6-6-6-6" />
                                            </svg>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="py-10 text-center text-sm text-zinc-400">No recent orders</div>
                    @endif
                </div>

                {{-- ─── RIGHT COLUMN ─── --}}
                <div class="flex flex-col gap-4">

                    {{-- Top Stores --}}
                    <div class="fade-up fade-d6 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                        <div class="border-b border-zinc-100 px-5 py-3">
                            <span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-zinc-400">Top Stores</span>
                        </div>
                        <div class="px-5 py-2">
                            @if ($topStores->count())
                                @php $maxStore = $topStores->max('total'); @endphp
                                @foreach ($topStores as $i => $st)
                                    <div class="flex items-center gap-3 border-b border-zinc-50 py-2 last:border-b-0">
                                        <span
                                            class="{{ $i === 0 ? 'bg-emerald-700 text-white' : 'bg-zinc-100 text-zinc-400' }} flex h-5 w-5 flex-shrink-0 items-center justify-center rounded text-[0.6rem] font-extrabold">
                                            {{ $i + 1 }}
                                        </span>
                                        <span class="flex-1 truncate text-[0.76rem] font-medium text-zinc-600">{{ $st->name }}</span>
                                        <div class="h-[3px] w-14 overflow-hidden rounded-full bg-zinc-100">
                                            <div class="h-full rounded-full bg-emerald-600 transition-all duration-500"
                                                style="width: {{ $maxStore > 0 ? ($st->total / $maxStore) * 100 : 0 }}%"></div>
                                        </div>
                                        <span class="min-w-[24px] text-right text-[0.76rem] font-extrabold tabular-nums text-zinc-800">{{ $st->total }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="py-6 text-center text-sm text-zinc-400">No store data</div>
                            @endif
                        </div>
                    </div>

                    {{-- ═══ UNIFIED INVENTORY PANEL ═══ --}}
                    @if (!$isManager)
                        <div class="fade-up fade-d7 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-zinc-400">Inventory</span>
                                    @if ($warehouseName)
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[0.58rem] font-semibold text-zinc-500">{{ $warehouseName }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('products.index') }}" class="text-[0.67rem] font-semibold text-emerald-700 transition-colors hover:text-emerald-900">Products &rarr;</a>
                            </div>

                            {{-- Two-column stat tiles --}}
                            <div class="grid grid-cols-2 gap-px bg-zinc-100">
                                <div class="bg-white px-4 py-3">
                                    <div class="text-[0.58rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Store Allocation</div>
                                    <div class="mt-1 text-[1.25rem] font-extrabold tabular-nums leading-none text-emerald-700">{{ number_format($totalStoreAllocation) }}</div>
                                    <div class="mt-0.5 text-[0.58rem] font-medium text-zinc-400">Total cases in store</div>
                                </div>
                                <div class="bg-white px-4 py-3">
                                    <div class="text-[0.58rem] font-bold uppercase tracking-[0.07em] text-zinc-400">Warehouse Inventory</div>
                                    <div class="mt-1 text-[1.25rem] font-extrabold tabular-nums leading-none text-indigo-700">{{ number_format($wmsVirtualTotal) }}</div>
                                    <div class="mt-0.5 text-[0.58rem] font-medium text-zinc-400">Total pieces (WMS)</div>
                                </div>
                            </div>

                            {{-- Comparison bar --}}
                            @if ($totalStoreAllocation > 0 || $wmsVirtualTotal > 0)
                                @php $maxBar = max($totalStoreAllocation, $wmsVirtualTotal, 1); @endphp
                                <div class="space-y-1.5 border-t border-zinc-100 px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-12 text-right text-[0.6rem] font-semibold text-emerald-600">Store</span>
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100">
                                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width:{{ ($totalStoreAllocation / $maxBar) * 100 }}%"></div>
                                        </div>
                                        <span class="w-16 text-right text-[0.65rem] font-bold tabular-nums text-zinc-600">{{ number_format($totalStoreAllocation) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-12 text-right text-[0.6rem] font-semibold text-indigo-600">WMS</span>
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-100">
                                            <div class="h-full rounded-full bg-indigo-500 transition-all duration-500" style="width:{{ ($wmsVirtualTotal / $maxBar) * 100 }}%"></div>
                                        </div>
                                        <span class="w-16 text-right text-[0.65rem] font-bold tabular-nums text-zinc-600">{{ number_format($wmsVirtualTotal) }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Stock health breakdown --}}
                            <div class="border-t border-zinc-100 px-4 py-2.5">
                                @if ($totalProducts > 0)
                                    <div class="flex h-1 overflow-hidden rounded-full bg-zinc-100">
                                        <div class="bg-emerald-500 transition-all duration-500" style="width:{{ ($healthyStockCount / $totalProducts) * 100 }}%"></div>
                                        <div class="bg-amber-400 transition-all duration-500" style="width:{{ ($lowStockCount / $totalProducts) * 100 }}%"></div>
                                        <div class="bg-red-500 transition-all duration-500" style="width:{{ ($outOfStockCount / $totalProducts) * 100 }}%"></div>
                                    </div>
                                @endif
                                <div class="mt-2 space-y-0">
                                    @foreach ([['dot' => 'bg-emerald-500', 'label' => 'Healthy', 'val' => $healthyStockCount, 'color' => 'text-zinc-800'], ['dot' => 'bg-amber-400', 'label' => 'Low (≤ 5)', 'val' => $lowStockCount, 'color' => 'text-amber-600'], ['dot' => 'bg-red-500', 'label' => 'Out of Stock', 'val' => $outOfStockCount, 'color' => 'text-red-600']] as $inv)
                                        <div class="flex items-center gap-2 border-b border-zinc-50 py-1.5 last:border-b-0">
                                            <span class="{{ $inv['dot'] }} inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full"></span>
                                            <span class="flex-1 text-[0.72rem] font-medium text-zinc-500">{{ $inv['label'] }}</span>
                                            <span class="{{ $inv['color'] }} text-[0.78rem] font-extrabold tabular-nums">{{ number_format($inv['val']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t border-zinc-100 pt-2">
                                    <div class="flex items-center gap-3">
                                        <span class="text-[0.65rem] text-zinc-400">
                                            <b class="font-bold text-zinc-700">{{ number_format($totalProducts) }}</b> products
                                        </span>
                                        <span class="text-[0.65rem] text-zinc-400">
                                            <b class="font-bold text-zinc-700">{{ number_format($wmsSkuCount) }}</b> tracked in WMS
                                        </span>
                                        @if ($wmsZeroCount > 0)
                                            <span class="text-[0.65rem] text-red-500">
                                                <b class="font-bold">{{ number_format($wmsZeroCount) }}</b> at 0 pcs
                                            </span>
                                        @endif
                                    </div>
                                    @if ($wmsLastUpdated)
                                        <span class="text-[0.6rem] font-medium text-zinc-400">Synced {{ $wmsLastUpdated->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Channels --}}
                    @if ($channelBreakdown->count())
                        <div class="fade-up fade-d8 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                            <div class="border-b border-zinc-100 px-5 py-3">
                                <span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-zinc-400">Order Channels</span>
                            </div>
                            <div class="px-5 py-2">
                                @foreach ($channelBreakdown as $ch)
                                    <div class="flex items-center justify-between border-b border-zinc-50 py-2 last:border-b-0">
                                        <span class="text-[0.76rem] font-medium text-zinc-600">{{ $ch->channel_order }}</span>
                                        <span class="text-[0.76rem] font-extrabold tabular-nums text-zinc-800">{{ $ch->total }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══ SECONDARY STATS ROW ═══ --}}
            <div class="fade-up fade-d8 mt-5 grid grid-cols-4 gap-px overflow-hidden rounded-lg border border-zinc-200 bg-zinc-200 shadow-sm max-[700px]:grid-cols-2">
                @foreach ([['label' => 'Approved', 'val' => $approved_count, 'color' => 'text-emerald-700'], ['label' => 'Rejected', 'val' => $rejected_count, 'color' => 'text-red-600'], ['label' => 'Cancelled', 'val' => $cancelled_count, 'color' => 'text-zinc-500'], ['label' => 'Total', 'val' => $totalOrders, 'color' => 'text-zinc-800']] as $ss)
                    <div class="bg-white px-4 py-3">
                        <div class="text-[0.62rem] font-bold uppercase tracking-[0.07em] text-zinc-400">{{ $ss['label'] }}</div>
                        <div class="{{ $ss['color'] }} mt-0.5 text-[1.2rem] font-extrabold tabular-nums leading-none">{{ $ss['val'] }}</div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

@endsection
