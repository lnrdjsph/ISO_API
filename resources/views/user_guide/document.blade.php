@extends('layouts.app')

@section('content')
    @php
        $userRole = auth()->user()->role ?? 'store personnel';
        $availableRoles = match ($userRole) {
            'super admin' => ['personnel', 'manager', 'admin'],
            'manager' => ['manager'],
            default => ['personnel'],
        };
        $defaultTab = match ($userRole) {
            'super admin' => 'admin',
            'manager' => 'manager',
            default => 'personnel',
        };
    @endphp

    <style nonce="{{ $cspNonce ?? '' }}">
        [data-roles] {
            display: none;
        }

        [data-roles].role-visible {
            display: block;
        }

        .guide-nav-link.active {
            color: #1d4ed8;
            background: #eff6ff;
            font-weight: 600;
        }

        .guide-nav-sub.active {
            color: #1d4ed8;
            font-weight: 600;
        }

        .guide-sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .guide-sidebar-scroll::-webkit-scrollbar {
            width: 3px;
        }

        .guide-sidebar-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .screenshot-frame {
            cursor: pointer;
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .screenshot-frame:hover {
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .lightbox-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
        }

        .lightbox-overlay.active {
            pointer-events: auto;
            opacity: 1;
            visibility: visible;
        }

        .lightbox-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0);
            transition: background 0.35s cubic-bezier(0.32, 0.72, 0, 1);
            backdrop-filter: blur(0px);
        }

        .lightbox-overlay.active .lightbox-backdrop {
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(12px);
        }

        .lightbox-content {
            position: relative;
            max-width: 90vw;
            max-height: 85vh;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
            transform: scale(0.85);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.32, 0.72, 0, 1), opacity 0.3s;
        }

        .lightbox-overlay.active .lightbox-content {
            transform: scale(1);
            opacity: 1;
        }

        .lightbox-overlay.closing .lightbox-backdrop {
            background: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px);
        }

        .lightbox-overlay.closing .lightbox-content {
            transform: scale(0.85);
            opacity: 0;
        }

        .lightbox-content img {
            display: block;
            max-width: 90vw;
            max-height: 80vh;
            width: auto;
            height: auto;
        }

        .step-row {
            transition: all 0.15s;
        }

        .step-row:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transform: translateX(4px);
        }

        .section-card {
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .section-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        @media (max-width:1024px) {
            .guide-sidebar-col {
                display: none !important;
            }

            .guide-sidebar-col.open {
                display: flex !important;
                position: fixed;
                inset: 0;
                z-index: 100;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(4px);
                padding: 2rem;
                align-items: flex-start;
                justify-content: center;
            }

            .guide-sidebar-col.open>div {
                max-height: calc(100vh - 4rem);
            }

            .guide-main {
                margin-left: 0 !important;
            }
        }

        @media (min-width:1025px) {
            .mobile-toggle {
                display: none !important;
            }
        }

        html,
        body {
            scroll-behavior: smooth;
            scroll-padding-top: 2rem;
            overflow-x: clip !important;
        }

        .field-row {
            display: flex;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            background: #f9fafb;
        }

        .field-row:nth-child(even) {
            background: #f0f4ff;
        }

        .field-label {
            font-weight: 600;
            color: #1e293b;
            min-width: 140px;
            flex-shrink: 0;
            font-size: 0.8125rem;
        }

        .field-desc {
            color: #4b5563;
            font-size: 0.8125rem;
            line-height: 1.5;
        }
    </style>

    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═══ PAGE HEADER ═══ --}}
        <div class="mb-8 overflow-hidden rounded-2xl bg-gray-950 p-8 shadow-xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 id="role-title" class="text-2xl font-bold tracking-tight text-white sm:text-3xl">User Guide</h1>
                        <p class="mt-1 text-sm text-gray-300">ISO B2B2C Ordering Platform · v1.2 · March 2026</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-gray-200 ring-1 ring-white/20">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Logged in as <span class="font-semibold text-white">{{ ucwords(str_replace('_', ' ', $userRole)) }}</span>
                </div>
            </div>
        </div>

        {{-- ═══ MOBILE TOGGLE ═══ --}}
        <button class="mobile-toggle mb-4 flex w-full items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200"
            onclick="document.getElementById('guideSidebarCol').classList.toggle('open')">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            {{ count($availableRoles) > 1 ? 'Menu & Switch Role' : 'Navigation' }}
        </button>

        <div class="relative flex gap-6">

            {{-- ═══ SIDEBAR ═══ --}}
            <div class="guide-sidebar-col w-60 flex-shrink-0 self-stretch" id="guideSidebarCol" onclick="if(event.target===this)this.classList.remove('open')">
                <div class="sticky top-24 w-60 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" style="max-height: calc(100vh - 7rem);">
                    @if (count($availableRoles) > 1)
                        <div class="border-b border-gray-100 px-3 py-3">
                            <p class="mb-2 px-2 text-[0.65rem] font-semibold uppercase tracking-wider text-gray-400">Switch view</p>
                            <div class="flex flex-col gap-0.5">
                                @if (in_array('personnel', $availableRoles))
                                    <button
                                        class="sidebar-role-btn {{ $defaultTab === 'personnel' ? 'active bg-blue-50 !text-blue-700 font-semibold' : '' }} flex items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-gray-600 transition-all hover:bg-gray-50"
                                        data-role="personnel">
                                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Store Personnel
                                    </button>
                                @endif
                                @if (in_array('manager', $availableRoles))
                                    <button
                                        class="sidebar-role-btn {{ $defaultTab === 'manager' ? 'active bg-blue-50 !text-blue-700 font-semibold' : '' }} flex items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-gray-600 transition-all hover:bg-gray-50"
                                        data-role="manager">
                                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Manager
                                    </button>
                                @endif
                                @if (in_array('admin', $availableRoles))
                                    <button
                                        class="sidebar-role-btn {{ $defaultTab === 'admin' ? 'active bg-blue-50 !text-blue-700 font-semibold' : '' }} flex items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-gray-600 transition-all hover:bg-gray-50"
                                        data-role="admin">
                                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        Super Admin
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                    <nav class="guide-sidebar-scroll overflow-y-auto p-2" id="guideSidebar" style="max-height: {{ count($availableRoles) > 1 ? 'calc(100vh - 16rem)' : 'calc(100vh - 8rem)' }};">
                    </nav>
                </div>
            </div>

            {{-- ═══ MAIN CONTENT ═══ --}}
            <div class="guide-main min-w-0 flex-1">

                {{-- ══════════════════════════════════════════
                     1. DASHBOARD
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="dashboard" data-roles="personnel manager admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">01</span>
                            <h2 class="text-lg font-semibold text-gray-900">Dashboard</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="text-sm leading-relaxed text-gray-600">Your command center. Real-time snapshot of order activity the moment you log in.</p>

                        {{-- Personnel Dashboard Screenshot --}}
                        <div data-roles="personnel">
                            <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                <div class="flex h-64 items-center justify-center overflow-hidden"><img class="h-full w-full object-cover"
                                        src="{{ asset('images/guide/dashboard-full.png') }}" alt="Personnel Dashboard"></div>
                                <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">Store Personnel Dashboard — Order counts, quick access cards, and recent
                                    activity</p>
                            </div>
                        </div>

                        {{-- Manager Dashboard Screenshot --}}
                        <div data-roles="manager">
                            <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                <div class="flex h-64 items-center justify-center overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/guide/dashboard-manager.png') }}"
                                        alt="Manager Dashboard"></div>
                                <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">Manager Dashboard — Shows orders from your assigned region only</p>
                            </div>
                        </div>

                        {{-- Admin Dashboard Screenshot --}}
                        <div data-roles="admin">
                            <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                <div class="flex h-64 items-center justify-center overflow-hidden">
                                    @php
                                        $user = auth()->user();
                                        $dashboardImage = 'dashboard-full.png'; // default admin image

                                        // Check if user is manager or other role to show different dashboard
                                        if ($user->role === 'manager') {
                                            $dashboardImage = 'dashboard-manager.png';
                                        } elseif ($user->role === 'supervisor') {
                                            $dashboardImage = 'dashboard-supervisor.png';
                                        } elseif ($user->role === 'staff') {
                                            $dashboardImage = 'dashboard-staff.png';
                                        }

                                        // You can also check location
                                        if (isset($user->user_location)) {
                                            if ($user->user_location === 'lz') {
                                                $dashboardImage = 'dashboard-lz.png';
                                            } elseif ($user->user_location === 'vs') {
                                                $dashboardImage = 'dashboard-vs.png';
                                            }
                                        }
                                    @endphp
                                    <img class="h-full w-full object-cover"
                                        src="{{ asset('images/guide/' . $dashboardImage) }}"
                                        alt="{{ ucfirst($user->role ?? 'Admin') }} Dashboard">
                                </div>
                                <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">
                                    @if (auth()->user()->role === 'manager')
                                        Manager Dashboard — Store-level overview and reports
                                    @elseif(auth()->user()->role === 'supervisor')
                                        Supervisor Dashboard — Team performance and monitoring
                                    @elseif(auth()->user()->role === 'staff')
                                        Staff Dashboard — Daily tasks and orders
                                    @else
                                        Super Admin Dashboard — Full visibility across all stores
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- ── Status Indicators: PERSONNEL ── --}}
                        <div data-roles="personnel">
                            <h3 class="mt-6 text-sm font-semibold text-gray-900">Status Indicators</h3>
                            <p class="mt-1 text-sm text-gray-500">Color-coded badges on the Orders card show live counts of orders that need attention:</p>
                            <div class="mt-3 space-y-2">
                                @foreach ([['New Orders', 'bg-blue-500', 'Orders just submitted and awaiting initial processing or approval request.'], ['For Approval', 'bg-purple-500', 'Orders you have sent to the manager for review. Waiting for the manager to approve or reject.']] as $b)
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"><span
                                                class="{{ $b[1] }} inline-block h-2 w-2 rounded-full"></span>{{ $b[0] }}</span>
                                        <span class="text-sm text-gray-500">{{ $b[2] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <h3 class="mt-5 text-sm font-semibold text-gray-900">Quick Access Cards</h3>
                            <p class="mt-1 text-sm text-gray-500">The Dashboard provides shortcut cards to the main areas of the system:</p>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach ([['Orders', 'View all submitted orders with status badges. Click to open the full Orders List.'], ['Forms', 'Access the Sales Order Form (SOF) and Request Order Form (ROF) to create new orders.'], ['Products', 'Browse the product catalog to check SKU, pricing, inventory, and allocation.'], ['Reports', 'View Sales Overview, Orders Report, and Payments Report summaries.']] as $c)
                                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                                        <p class="text-sm font-medium text-gray-800">{{ $c[0] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $c[1] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ── Status Indicators: MANAGER ── --}}
                        <div data-roles="manager">
                            <h3 class="mt-6 text-sm font-semibold text-gray-900">Status Indicators</h3>
                            <p class="mt-1 text-sm text-gray-500">As a manager, the Orders card displays badges for the three statuses visible to you:</p>
                            <div class="mt-3 space-y-2">
                                @foreach ([['For Approval', 'bg-purple-500', 'Orders submitted by store personnel awaiting your review. This is your primary action item — review and either approve or reject.'], ['Approved', 'bg-green-500', 'Orders you have already approved. Store personnel can now proceed with fulfillment (Generate SO#, track shipments).'], ['Rejected', 'bg-red-500', 'Orders you have rejected with a reason. Store personnel will see your rejection notes and may revise and resubmit for approval.']] as $b)
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"><span
                                                class="{{ $b[1] }} inline-block h-2 w-2 rounded-full"></span>{{ $b[0] }}</span>
                                        <span class="text-sm text-gray-500">{{ $b[2] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
                                <strong>Manager view:</strong> You only see orders from stores in your assigned region. The Dashboard does not include Forms or Products cards — your role is to
                                review and approve orders submitted by store personnel.
                            </div>

                            <h3 class="mt-5 text-sm font-semibold text-gray-900">Region Assignment</h3>
                            <p class="mt-1 text-sm text-gray-500">Your manager account is assigned to a specific region. You will only see orders from the stores within your region:</p>
                            <div class="mt-3 overflow-hidden rounded-lg ring-1 ring-gray-200">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            <th class="px-4 py-2.5">Region</th>
                                            <th class="px-4 py-2.5">Stores</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 font-medium text-gray-800">Visayas (VS)</td>
                                            <td class="px-4 py-2.5 text-gray-600">F2 – Metro Wholesalemart Colon (4002), S10 – Metro Maasin (2010), S17 – Metro Tacloban (2017), S19 – Metro
                                                Bay-Bay (2019), F18 – Metro Alang-Alang (3018)</td>
                                        </tr>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 font-medium text-gray-800">Luzon (LZ)</td>
                                            <td class="px-4 py-2.5 text-gray-600">H8 – Super Metro Antipolo (6012), F19 – Metro Hilongos (3019), S8 – Metro Toledo (2008), H9 – Super Metro
                                                Carcar (6009), H10 – Super Metro Bogo (6010)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── Admin ── --}}
                        <div data-roles="admin">
                            <h3 class="mt-6 text-sm font-semibold text-gray-900">Status Indicators</h3>
                            <p class="mt-1 text-sm text-gray-500">Color-coded badges show live order counts:</p>
                            <div class="mt-3 space-y-2">
                                @foreach ([['New Orders', 'bg-blue-500', 'Awaiting processing'], ['For Approval', 'bg-purple-500', 'Needs manager review'], ['Approved', 'bg-green-500', 'Manager approved']] as $b)
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"><span
                                                class="{{ $b[1] }} inline-block h-2 w-2 rounded-full"></span>{{ $b[0] }}</span>
                                        <span class="text-sm text-gray-500">{{ $b[2] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                                <strong>Super Admin:</strong> Full visibility across all stores. Your sidebar also includes <strong>Users</strong> and <strong>Others</strong>.
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            Start here each day to check pending work.
                        </div>
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     2. SALES ORDER FORM (Personnel & Admin only)
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="sales-order-form" data-roles="personnel admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">02</span>
                            <h2 class="text-lg font-semibold text-gray-900">Sales Order Form (SOF)</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="text-sm text-gray-600">Create new B2B sales orders. The form has two main parts: header information and order items.</p>

                        <h3 class="mt-6 text-sm font-semibold text-gray-900" id="sof-header">Step 1 — Fill Order Information</h3>
                        <div class="screenshot-frame mt-3 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                            <div class="flex h-64 items-center justify-center overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/guide/sof-header.png') }}"
                                    alt="SOF header"></div>
                            <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">Header — Request Details, Customer Info, Payment, Dispatch</p>
                        </div>

                        {{-- ── FIELD-BY-FIELD EXPLANATIONS ── --}}
                        <div class="mt-4 space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Request Details</h4>
                                <p class="mb-2 mt-1 text-xs text-gray-400">Fields with a shaded background are auto-filled and cannot be edited.</p>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">SOF Order ID</span><span class="field-desc">Auto-generated unique order number in the format <strong>SOF +
                                                Year/Month + Sequence</strong> (e.g., SOF202603-001). Read-only — assigned by the system.</span></div>
                                    <div class="field-row"><span class="field-label">Requesting Store</span><span class="field-desc">Your assigned store location (e.g., "F2 – Metro
                                            Wholesalemart Colon"). Read-only — set from your user account.</span></div>
                                    <div class="field-row"><span class="field-label">Requested By</span><span class="field-desc">Your name as the logged-in user who is creating this order.
                                            Read-only.</span></div>
                                    <div class="field-row"><span class="field-label">Channel of Order</span><span class="field-desc">Select the sales channel: <strong>E-Commerce</strong>,
                                            <strong>Store</strong>, <strong>ISO Retail</strong>, or <strong>Wholesale</strong>. This determines how the order is categorized.</span></div>
                                    <div class="field-row"><span class="field-label">Serving Warehouse</span><span class="field-desc">The warehouse that will fulfill this order, auto-assigned
                                            based on your store location (e.g., Bacolod Depot or Silangan Warehouse). Read-only.</span></div>
                                    <div class="field-row"><span class="field-label">Date &amp; Time of Order</span><span class="field-desc">Pre-filled with the current date and time. You can
                                            adjust it if needed (e.g., for backdated orders).</span></div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Customer Information</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">MBC Card Number</span><span class="field-desc">Enter the customer's 16-digit MBC loyalty card number. When
                                            a valid card is entered, the system auto-fills the customer's name, contact number, and email. If the card is not found, you can enter details
                                            manually.</span></div>
                                    <div class="field-row"><span class="field-label">Customer Name</span><span class="field-desc">The customer's full name. Auto-filled from MBC Card lookup,
                                            or enter manually.</span></div>
                                    <div class="field-row"><span class="field-label">Contact Number</span><span class="field-desc">The customer's phone number. Auto-filled from MBC Card
                                            lookup, or enter manually.</span></div>
                                    <div class="field-row"><span class="field-label">Customer Email</span><span class="field-desc">The customer's email address. Auto-filled from MBC Card
                                            lookup, or enter manually.</span></div>
                                </div>
                                <div class="mt-2 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">MBC Card must be exactly <strong>16 digits</strong>. Entering fewer digits triggers a
                                    warning. Only numbers (0–9) are accepted — letters and symbols are ignored.</div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Payment Information</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">Payment Center</span><span class="field-desc">Auto-assigned to match your store (e.g., "F2 – Metro
                                            Wholesalemart Colon" for store 4002). Read-only for store personnel.</span></div>
                                    <div class="field-row"><span class="field-label">Mode of Payment</span><span class="field-desc">Select either <strong>PO15%</strong> (Purchase Order with
                                            15% terms) or <strong>Cash / Bank Card</strong> (immediate payment). This affects the product scheme applied to order items.</span></div>
                                    <div class="field-row"><span class="field-label">Payment Date</span><span class="field-desc">Pre-filled with today's date. Editable — set to the customer's
                                            preferred payment date.</span></div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Dispatch Details</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">Delivery/Pick-up Date</span><span class="field-desc">Set the expected date for customer delivery or store
                                            pick-up.</span></div>
                                    <div class="field-row"><span class="field-label">Mode of Dispatching</span><span class="field-desc">Select <strong>Customer Pick-up</strong> (customer
                                            collects at store — no address needed) or <strong>Delivery Direct to Customer</strong> (Address and Landmark fields appear and must be filled).</span>
                                    </div>
                                    <div class="field-row"><span class="field-label">Address</span><span class="field-desc">Delivery address. Only appears when "Delivery Direct to Customer"
                                            is selected.</span></div>
                                    <div class="field-row"><span class="field-label">Landmark</span><span class="field-desc">A nearby landmark to help with delivery. Only appears when
                                            "Delivery Direct to Customer" is selected.</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">The Order Items section only appears after <strong>all required header fields</strong> are
                            completed. If any required field is empty, the items section stays hidden.</div>

                        <div class="mt-6 border-t border-gray-100 pt-5" id="sof-items">
                            <h3 class="text-sm font-semibold text-gray-900">Step 2 — Add Order Items</h3>
                            <div class="mt-3 space-y-1.5">
                                @foreach (['Select <strong>Sale Type</strong>: <strong>Freebie</strong> (buy X get Y free — shows Scheme and Freebie Product fields) or <strong>Discount</strong> (fixed amount or percentage off — shows Discount field).', 'Search for a product by typing at least 2 characters of the <strong>SKU number</strong>, <strong>product name</strong>, or <strong>sub-department</strong>. Select from the dropdown.', 'Set <strong>Case/s Ordered</strong>. The Item Breakdown on the right updates live showing Price per Case, Freebies, Total Cases, Freebie Amount, and Total Payable.'] as $s)
                                    <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2.5"><span
                                            class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[0.65rem] font-bold text-white">{{ $loop->iteration }}</span><span
                                            class="text-sm text-gray-600">{!! $s !!}</span></div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5" id="sof-submit">
                            <h3 class="text-sm font-semibold text-gray-900">Step 3 — Submit</h3>
                            <p class="mt-1 text-sm text-gray-500">Use <strong>+ Add Another Item</strong> to add more products. Click <strong>Submit Order</strong> to create the order. The
                                button changes to "Processing…" and disables to prevent double submission.</p>
                        </div>
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     3. ORDERS LIST
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="orders-list" data-roles="personnel manager admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">03</span>
                            <h2 class="text-lg font-semibold text-gray-900">Orders List</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="text-sm text-gray-600">Track all submitted sales orders with filters and search.</p>

                        <div data-roles="manager">
                            <div class="mt-3 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
                                <strong>Manager view:</strong> You only see orders from stores in your assigned region (Visayas or Luzon). The Status filter only shows three options: <strong>For
                                    Approval</strong>, <strong>Approved</strong>, and <strong>Rejected</strong>. The Store dropdown only lists stores within your region.
                            </div>
                        </div>
                        <div data-roles="admin">
                            <div class="mt-3 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-800">Super Admins see orders from <strong>all stores</strong> with all statuses and a full
                                store dropdown.</div>
                        </div>

                        <h3 class="mt-5 text-sm font-semibold text-gray-900" id="orders-filters">Filtering &amp; Searching</h3>
                        <div class="screenshot-frame mt-3 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                            <div class="flex h-36 items-center justify-center overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/guide/orders-filter-bar.png') }}"
                                    alt="Filter bar"></div>
                            <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">Filter by keyword, store, channel, status, date</p>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">Use the filter bar to narrow results by <strong>keyword</strong> (customer name, SOF ID, store code), <strong>store</strong>,
                            <strong>channel</strong>, <strong>status</strong>, or <strong>date range</strong>. Click <strong>Apply</strong> to filter or <strong>Reset</strong> to clear all
                            filters.
                        </p>

                        <div class="mt-5 border-t border-gray-100 pt-5" id="orders-table">
                            <h3 class="text-sm font-semibold text-gray-900">Order Table</h3>
                            <div class="screenshot-frame mt-3 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                <div class="flex h-64 items-center justify-center overflow-hidden">
                                    @if (auth()->user()->role === 'manager' || auth()->user()->user_type === 'manager')
                                        <img class="h-full w-full object-cover"
                                            src="{{ asset('images/guide/orders-table-manager.png') }}"
                                            alt="Orders table for managers">
                                    @else
                                        <img class="h-full w-full object-cover"
                                            src="{{ asset('images/guide/orders-table-personnel.png') }}"
                                            alt="Orders table">
                                    @endif
                                </div>
                                <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">
                                    Orders with status badges and channel indicators
                                </p>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Each row shows the SOF ID, Customer Name, Store, Channel, Status (color-coded badge), and key dates. Click any row to open the
                                Order Details page.</p>
                        </div>

                        {{-- <div class="mt-4 flex gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            AJAX updates — filters and pagination don't reload the page.
                        </div> --}}
                    </div>
                </section>


                {{-- ══════════════════════════════════════════
     4. ORDER DETAILS
══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="order-details" data-roles="personnel manager admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">04</span>
                            <h2 class="text-lg font-semibold text-gray-900">Order Details</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="text-sm text-gray-600">View, edit, and take action on individual orders.</p>

                        <h3 class="mt-5 text-sm font-semibold text-gray-900" id="od-panels">Information Panels</h3>
                        <div class="screenshot-frame mt-3 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                            <div class="flex h-48 items-center justify-center overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/guide/od-info-panels.png') }}"
                                    alt="Info panels"></div>
                            <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">Customer, Payment, Delivery, Order panels</p>
                        </div>

                        {{-- ── FIELD EXPLANATIONS FOR ORDER DETAILS ── --}}
                        <div class="mt-4 space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Customer Information Panel</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">MBC Card No.</span><span class="field-desc">The customer's 16-digit MBC loyalty card number used to
                                            identify them in the system.</span></div>
                                    <div class="field-row"><span class="field-label">Customer Name</span><span class="field-desc">Full name of the customer who placed the order.</span></div>
                                    <div class="field-row"><span class="field-label">Contact Number</span><span class="field-desc">Customer's phone number for delivery coordination or
                                            follow-up.</span></div>
                                    <div class="field-row"><span class="field-label">Email</span><span class="field-desc">Customer's email address for order notifications.</span></div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Payment Information Panel</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">Payment Center</span><span class="field-desc">The store where payment is processed, auto-assigned based on
                                            the requesting store.</span></div>
                                    <div class="field-row"><span class="field-label">Mode of Payment</span><span class="field-desc">How the customer is paying: PO15% (purchase order terms) or
                                            Cash / Bank Card.</span></div>
                                    <div class="field-row"><span class="field-label">Payment Date</span><span class="field-desc">The expected date of payment from the customer.</span></div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Delivery/Dispatch Panel</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">Delivery/Pick-up Date</span><span class="field-desc">When the customer will receive or collect their
                                            order.</span></div>
                                    <div class="field-row"><span class="field-label">Mode of Dispatching</span><span class="field-desc">Customer Pick-up (collect at store) or Delivery Direct
                                            to Customer (shipped to address).</span></div>
                                    <div class="field-row"><span class="field-label">Address / Landmark</span><span class="field-desc">Delivery location details. Only shown for direct
                                            delivery orders.</span></div>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Order Information Panel</h4>
                                <div class="space-y-1">
                                    <div class="field-row"><span class="field-label">SOF Order ID</span><span class="field-desc">The unique system-generated order number (e.g.,
                                            SOF202603-001). Read-only.</span></div>
                                    <div class="field-row"><span class="field-label">Requesting Store</span><span class="field-desc">The store that created this order. Read-only.</span></div>
                                    <div class="field-row"><span class="field-label">Requested By</span><span class="field-desc">The store personnel who created the order. Read-only.</span>
                                    </div>
                                    <div class="field-row"><span class="field-label">Channel of Order</span><span class="field-desc">The sales channel (E-Commerce, Store, ISO Retail, or
                                            Wholesale).</span></div>
                                    <div class="field-row"><span class="field-label">Serving Warehouse</span><span class="field-desc">The warehouse fulfilling this order (Bacolod Depot or
                                            Silangan Warehouse). Read-only.</span></div>
                                    <div class="field-row"><span class="field-label">Order Status</span><span class="field-desc">Current lifecycle status of the order (e.g., New Order, For
                                            Approval, Approved). Read-only — changed via Order Actions.</span></div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-gray-500">Fields with a shaded background are read-only. Editable fields turn <strong>yellow</strong> when changed — click the
                            <strong>Update</strong> button to save.
                        </p>

                        <div class="mt-6 border-t border-gray-100 pt-5" id="od-actions">
                            <h3 class="text-sm font-semibold text-gray-900">Order Actions Dropdown</h3>
                            <p class="mt-1 text-sm text-gray-500">The Order Actions dropdown lets you change the order's status. Available actions depend on your role and the current order
                                status:</p>

                            {{-- Order Actions Dropdown Screenshot with Context --}}
                            <div class="mt-4 rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4">
                                <h4 class="mb-2 text-sm font-semibold text-blue-900">📍 Where to Find Order Actions</h4>
                                <p class="mb-3 text-sm text-blue-800">On any Order Details page, look for the <strong>"Order Actions" dropdown button</strong> in the top-right corner, just
                                    above the information panels. Click it to see all available actions for the current order.</p>

                                <div class="screenshot-frame mt-2 overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden bg-gray-50 p-2">
                                        <img class="rounded border border-gray-200 object-cover"
                                            src="{{ asset('images/guide/manager-order-actions.png') }}"
                                            alt="Manager order actions dropdown showing Approve & Reject options">
                                    </div>
                                    <div class="border-t border-gray-100 bg-white px-4 py-3">
                                        <p class="flex items-center gap-2 text-xs text-gray-600">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3 w-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                                <span class="font-medium">Manager View:</span>
                                            </span>
                                            The dropdown shows all actions available to managers: <strong>Approve Order & Reject Order</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- ── Personnel Actions ── --}}
                            <div data-roles="personnel" class="mt-4">
                                <h4 class="mb-2 text-sm font-medium text-gray-800">Personnel Actions</h4>
                                <div class="space-y-2">
                                    @foreach ([['Request For Approval', 'Sends the order to your regional manager for review. The manager receives an email notification. The order status changes to "For Approval." If the order was previously rejected, inventory allocation is re-deducted.'], ['Cancel Order', 'Cancels the entire order. You must provide a reason. Inventory allocation is reverted (added back to stock). The reason is logged in Order Notes.'], ['Complete Order', 'Marks the order as fully fulfilled after all items have been received. Status changes to "Completed."'], ['Restore Order', 'Re-opens a cancelled order. Status changes back to "New Order" so it can go through the approval process again.']] as $a)
                                        <div class="rounded-lg bg-gray-50 px-4 py-2.5">
                                            <p class="text-sm font-medium text-gray-800">{{ $a[0] }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $a[1] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- ── Manager Actions with Visual Context ── --}}
                            <div data-roles="manager" class="mt-4">
                                <h4 class="mb-2 text-sm font-medium text-gray-800">Manager Actions</h4>
                                <div class="space-y-3">
                                    @foreach ([
            ['Approve Order', 'Approves the order after your review. You are required to attach a supporting document (PDF, DOC, DOCX, JPG, JPEG, PNG — max 5MB). The store personnel who submitted the order receives an email notification that it has been approved. Status changes to "Approved" and personnel can then Generate SO# to begin fulfillment.'],
            ['Reject Order', 'Rejects the order when it has issues (e.g., incorrect pricing, unreasonable quantities). You must provide a rejection reason — this is required and cannot be skipped. The reason is logged in Order Notes and visible to the store personnel. Inventory allocation is reverted. Personnel can revise the order and resubmit for approval.'],
            ['Cancel Order', 'Cancels the entire order. You must provide a reason. If the order was not already rejected, inventory allocation is reverted. The reason is logged in Order Notes.'],
            ['Complete Order', 'Marks the order as fully fulfilled. Status changes to "Completed."'],
            ['Restore Order', 'Re-opens a cancelled order. Status changes back to "New Order."'],
        ] as $a)
                                        <div class="rounded-lg bg-gray-50 px-4 py-2.5">
                                            <p class="text-sm font-medium text-gray-800">{{ $a[0] }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $a[1] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5" id="od-invoice">
                            <h3 class="text-sm font-semibold text-gray-900">Invoice, Printing &amp; Notes</h3>
                            <p class="mt-1 text-sm text-gray-500">The Invoice sidebar recalculates live as items are modified. Print buttons generate downloadable PDFs:</p>
                            <div class="mt-2 space-y-1">
                                <div class="field-row"><span class="field-label">Print SOF</span><span class="field-desc">Generates the full Sales Order Form as a landscape A4 PDF.</span>
                                </div>
                                <div class="field-row"><span class="field-label">Print Invoice</span><span class="field-desc">Generates the invoice with totals as a portrait A4 PDF.</span>
                                </div>
                                <div class="field-row"><span class="field-label">Print Freebies Form</span><span class="field-desc">Generates a form showing main products paired with their
                                        freebie products (only for orders with Freebie items).</span></div>
                                <div class="field-row"><span class="field-label">Print Order Slip</span><span class="field-desc">Generates an order slip for warehouse/dispatch use (excludes
                                        freebie-only rows).</span></div>
                            </div>
                            <p class="mt-3 text-sm text-gray-500"><strong>Order Notes</strong> is a scrollable timeline log of all actions taken on the order — who made changes, what changed,
                                and when. Every status change, field update, and approval/rejection reason is recorded here.</p>
                            <div class="mt-3 flex gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <strong>Update</strong> button activates when fields are changed (yellow highlight). Click to save edits.
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     MANAGER: Approve/Reject (dedicated section)
                ══════════════════════════════════════════ --}}
                {{-- ══════════════════════════════════════════
     MANAGER: Approve/Reject with Visual Guides
══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="approving-orders" data-roles="manager">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-600 text-xs font-bold text-white shadow-sm">★</span>
                            <h2 class="text-lg font-semibold text-gray-900">Approving &amp; Rejecting Orders</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="mb-4 text-sm text-gray-600">This is your primary workflow. Store personnel submit orders for your review — you approve or reject them based on your assessment.
                        </p>

                        {{-- Approving an Order with Visual Guide --}}
                        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-green-100 bg-green-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-green-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Approving an Order
                                </h3>
                            </div>
                            <div class="p-4">
                                <div class="space-y-3">
                                    @foreach (['Open an order with <strong>"For Approval"</strong> status (from Orders List or email link).', 'Review all panels: Customer Info, Payment, Delivery, and the Ordered Items table.', 'Verify pricing, quantities, scheme calculations, and totals in the Invoice sidebar.', 'Click <strong>Order Actions → Approve Order</strong>.'] as $s)
                                        <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-green-600 text-[0.65rem] font-bold text-white">{{ $loop->iteration }}</span>
                                            <span class="text-sm text-gray-600">{!! $s !!}</span>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Approve Modal Screenshot with Context --}}
                                <div class="mt-4 rounded-lg bg-blue-50 p-4">
                                    <h4 class="mb-2 text-sm font-semibold text-blue-900">📎 Step 5: Attach Supporting Document</h4>
                                    <p class="mb-3 text-sm text-blue-800">When you click "Approve Order", this modal appears. <strong>Attaching a document is required</strong> (PDF, DOC, DOCX,
                                        JPG, JPEG, PNG — max 5MB).</p>

                                    <div class="screenshot-frame overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                                        <div class="flex items-center justify-center overflow-hidden bg-gray-50 p-2">
                                            <img class="w-full rounded border border-gray-200 object-cover"
                                                src="{{ asset('images/guide/manager-approve-modal.png') }}"
                                                alt="Approve Order modal showing required document upload field">
                                        </div>
                                        <div class="border-t border-gray-100 bg-white px-4 py-3">
                                            <p class="flex items-center gap-2 text-xs text-gray-600">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="font-medium">Required Field:</span>
                                                </span>
                                                The document upload is marked with a red asterisk (*) and must be completed before approval
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-start gap-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>After clicking <strong>Approve</strong>, the requester (store personnel) receives an email notification that their order has been approved.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Rejecting an Order with Visual Guide --}}
                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-red-100 bg-red-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-red-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Rejecting an Order
                                </h3>
                            </div>
                            <div class="p-4">
                                <div class="space-y-3">
                                    @foreach (['Open an order with <strong>"For Approval"</strong> status.', 'Identify the issue (e.g., incorrect pricing, unreasonable quantity, wrong customer details).', 'Click <strong>Order Actions → Reject Order</strong>.'] as $s)
                                        <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[0.65rem] font-bold text-white">{{ $loop->iteration }}</span>
                                            <span class="text-sm text-gray-600">{!! $s !!}</span>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Reject Modal Screenshot with Context --}}
                                <div class="mt-4 rounded-lg bg-amber-50 p-4">
                                    <h4 class="mb-2 text-sm font-semibold text-amber-900">📝 Step 4: Provide Rejection Reason</h4>
                                    <p class="mb-3 text-sm text-amber-800">When you click "Reject Order", this modal appears. <strong>A clear rejection reason is REQUIRED</strong> and cannot be
                                        skipped.</p>

                                    <div class="screenshot-frame overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                                        <div class="flex items-center justify-center overflow-hidden bg-gray-50 p-2">
                                            <img class="w-full rounded border border-gray-200 object-cover"
                                                src="{{ asset('images/guide/manager-reject-modal.png') }}"
                                                alt="Reject Order modal showing required rejection reason textarea">
                                        </div>
                                        <div class="border-t border-gray-100 bg-white px-4 py-3">
                                            <p class="flex items-center gap-2 text-xs text-gray-600">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="font-medium">Important:</span>
                                                </span>
                                                Be specific and actionable so personnel know exactly what to fix
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2">
                                    @foreach (['Provide a <strong>clear rejection reason</strong> — this field is <strong>required</strong> and cannot be skipped.', 'Status changes to <strong>"Rejected"</strong>. Inventory allocation is reverted (stock added back). The reason is logged in Order Notes.'] as $s)
                                        <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[0.65rem] font-bold text-white">{{ $loop->iteration + 3 }}</span>
                                            <span class="text-sm text-gray-600">{!! $s !!}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-3 flex gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-2a1 1 0 01-1-1V8a1 1 0 112 0v2a1 1 0 01-1 1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <strong>Best Practice:</strong> Always provide a specific, actionable rejection reason so store personnel know exactly what to fix before resubmitting.
                                    Personnel can see your notes and resubmit the order for approval.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                {{-- ══════════════════════════════════════════
     EMAIL NOTIFICATIONS REFERENCE
══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="email-notifications" data-roles="manager">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-600 text-xs font-bold text-white shadow-sm">📧</span>
                            <h2 class="text-lg font-semibold text-gray-900">Email Notifications</h2>
                            <span class="rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">Reference Guide</span>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="mb-4 text-sm text-gray-600">All system notifications are sent automatically. Here's what each email looks like and what it means.</p>

                        {{-- Email 1: Order For Approval --}}
                        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-purple-100 bg-purple-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-purple-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    1. Order Submitted for Approval
                                </h3>
                                <p class="mt-1 text-xs text-purple-600">Sent to Manager when personnel requests approval</p>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="md:col-span-2">
                                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">What happens</h4>
                                        <ul class="space-y-2 text-sm text-gray-600">
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Store personnel submits an order and clicks "Request For Approval"</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span>Email is sent to the regional manager immediately</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>Contains direct links to Review, Approve, or Reject the order</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="md:col-span-1">
                                        <div class="rounded-lg bg-purple-50 p-3">
                                            <p class="mb-1 text-xs font-medium text-purple-800">⏱️ Action Required</p>
                                            <p class="text-xs text-purple-600">Review within 24 hours</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-order-for-approval.png') }}"
                                            alt="Order for approval email showing order summary and action buttons">
                                    </div>
                                    <div class="border-t border-gray-100 bg-white px-4 py-3">
                                        <p class="flex items-center gap-2 text-xs text-gray-600">
                                            <span class="font-medium text-purple-600">📧 Sample Email:</span>
                                            Subject: "[ACTION REQUIRED] New Order #SOF202602-002 Awaiting Your Approval"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Email 2: Order Approved --}}
                        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-green-100 bg-green-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-green-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    2. Order Approved
                                </h3>
                                <p class="mt-1 text-xs text-green-600">Sent to Store Personnel when manager approves</p>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="md:col-span-2">
                                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">What happens</h4>
                                        <ul class="space-y-2 text-sm text-gray-600">
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Manager approves the order and uploads supporting document</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span>Email sent to personnel with approval confirmation</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                <span>Includes link to download the supporting document</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="md:col-span-1">
                                        <div class="rounded-lg bg-green-50 p-3">
                                            <p class="mb-1 text-xs font-medium text-green-800">📎 Document Attached</p>
                                            <p class="text-xs text-green-600">Personnel can now Generate SO#</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-order-approved.png') }}"
                                            alt="Order approved email showing approval details and document attachment">
                                    </div>
                                    <div class="border-t border-gray-100 bg-white px-4 py-3">
                                        <p class="flex items-center gap-2 text-xs text-gray-600">
                                            <span class="font-medium text-green-600">📧 Sample Email:</span>
                                            Subject: "✅ Order #SOF202602-002 Has Been APPROVED"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Email 3: Order Rejected --}}
                        {{-- <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-red-100 bg-red-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-red-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    3. Order Rejected
                                </h3>
                                <p class="mt-1 text-xs text-red-600">Sent to Store Personnel when manager rejects</p>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="md:col-span-2">
                                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">What happens</h4>
                                        <ul class="space-y-2 text-sm text-gray-600">
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span>Manager rejects with a specific reason</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span>Email includes the rejection reason prominently</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Personnel can click "Revise & Resubmit" to fix the order</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="md:col-span-1">
                                        <div class="rounded-lg bg-red-50 p-3">
                                            <p class="mb-1 text-xs font-medium text-red-800">📝 Reason Required</p>
                                            <p class="text-xs text-red-600">Be specific and actionable</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-order-rejected.png') }}"
                                            alt="Order rejected email showing rejection reason and revise button">
                                    </div>
                                    <div class="border-t border-gray-100 bg-white px-4 py-3">
                                        <p class="flex items-center gap-2 text-xs text-gray-600">
                                            <span class="font-medium text-red-600">📧 Sample Email:</span>
                                            Subject: "❌ Order #SOF202603-001 Has Been REJECTED"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Email 4: Order Cancelled --}}
                        {{-- <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    4. Order Cancelled
                                </h3>
                                <p class="mt-1 text-xs text-gray-600">Sent to both Manager and Personnel</p>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="md:col-span-2">
                                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">What happens</h4>
                                        <ul class="space-y-2 text-sm text-gray-600">
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>Order is cancelled by either manager or personnel</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                <span>Inventory allocation is automatically reverted</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="md:col-span-1">
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <p class="mb-1 text-xs font-medium text-gray-800">↩️ Restore Available</p>
                                            <p class="text-xs text-gray-600">Cancelled orders can be restored</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-order-cancelled.png') }}"
                                            alt="Order cancelled email with cancellation reason">
                                    </div>
                                    <div class="border-t border-gray-100 bg-white px-4 py-3">
                                        <p class="text-xs text-gray-600">Subject: "⚠️ Order #SOF202603-001 Has Been CANCELLED"</p>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Email 5: Order Completed --}}
                        {{-- <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-teal-100 bg-teal-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-teal-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    5. Order Completed
                                </h3>
                                <p class="mt-1 text-xs text-teal-600">Sent when fulfillment is complete</p>
                            </div>
                            <div class="p-4">
                                <div class="screenshot-frame mt-2 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-order-completed.png') }}"
                                            alt="Order completed email with fulfillment summary">
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Email 6: Daily Summary --}}
                        {{-- <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-blue-100 bg-blue-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-blue-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    6. Daily Summary (End of Day)
                                </h3>
                                <p class="mt-1 text-xs text-blue-600">Sent to Managers only</p>
                            </div>
                            <div class="p-4">
                                <div class="screenshot-frame mt-2 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-daily-summary.png') }}"
                                            alt="Daily summary email showing pending orders by status">
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Email 7: Document Upload Confirmation --}}
                        {{-- <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                            <div class="border-b border-indigo-100 bg-indigo-50 px-4 py-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-indigo-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    7. Document Upload Confirmation
                                </h3>
                                <p class="mt-1 text-xs text-indigo-600">Confirmation when supporting document is uploaded</p>
                            </div>
                            <div class="p-4">
                                <div class="screenshot-frame mt-2 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img class="w-full object-cover" src="{{ asset('images/guide/email-document-uploaded.png') }}"
                                            alt="Document upload confirmation email">
                                    </div>
                                </div>
                            </div>
                        </div> --}}

                        {{-- Email Quick Reference Table --}}
                        <div class="mt-6 overflow-hidden rounded-lg ring-1 ring-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-2.5">Email Type</th>
                                        <th class="px-4 py-2.5">Recipient</th>
                                        <th class="px-4 py-2.5">When Sent</th>
                                        <th class="px-4 py-2.5">Action Required</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-medium text-gray-800">Order For Approval</td>
                                        <td class="px-4 py-2.5 text-gray-600">Manager</td>
                                        <td class="px-4 py-2.5 text-gray-600">Personnel requests approval</td>
                                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">Review &
                                                Decide</span></td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-medium text-gray-800">Order Approved</td>
                                        <td class="px-4 py-2.5 text-gray-600">Personnel</td>
                                        <td class="px-4 py-2.5 text-gray-600">Manager approves</td>
                                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Generate
                                                SO#</span></td>
                                    </tr>
                                    {{-- <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-medium text-gray-800">Order Rejected</td>
                                        <td class="px-4 py-2.5 text-gray-600">Personnel</td>
                                        <td class="px-4 py-2.5 text-gray-600">Manager rejects</td>
                                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Revise &
                                                Resubmit</span></td>
                                    </tr>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-medium text-gray-800">Order Cancelled</td>
                                        <td class="px-4 py-2.5 text-gray-600">Both</td>
                                        <td class="px-4 py-2.5 text-gray-600">Order is cancelled</td>
                                        <td class="px-4 py-2.5"><span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">Optional:
                                                Restore</span></td>
                                    </tr> --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                {{-- ══════════════════════════════════════════
                     5. PRODUCTS PAGE (Personnel & Admin only)
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="products" data-roles="personnel admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">05</span>
                            <h2 class="text-lg font-semibold text-gray-900">Products Page</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="text-sm text-gray-600">The product catalog for your store. Search by SKU, description, or sub-department.</p>
                        <div data-roles="personnel">
                            <div class="mt-3 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">The depot selector is <strong>not available</strong> for store personnel. This page is
                                <strong>view-only</strong> — contact your admin for product changes.
                            </div>
                        </div>
                        <div data-roles="admin">
                            <div class="mt-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Full access: depot filter, <strong>Add Product</strong>, <strong>Import CSV</strong>.
                            </div>
                        </div>
                        <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
                            <div class="flex h-64 items-center justify-center overflow-hidden"><img class="h-full w-full object-cover" src="{{ asset('images/guide/products-table.png') }}"
                                    alt="Products"></div>
                            <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">Product catalog with inventory levels and pricing</p>
                        </div>

                        {{-- ── COLUMN EXPLANATIONS ── --}}
                        <h3 class="mt-5 text-sm font-semibold text-gray-900">Column Descriptions</h3>
                        <p class="mb-2 mt-1 text-xs text-gray-400">Each column in the products table provides specific inventory and pricing information:</p>
                        <div class="space-y-1">
                            <div class="field-row"><span class="field-label">SKU</span><span class="field-desc">The unique Stock Keeping Unit code that identifies each product in the system
                                    (e.g., 12345).</span></div>
                            <div class="field-row"><span class="field-label">Product Description</span><span class="field-desc">The full name and variant of the product (e.g., "BEARBRAND FORT
                                    POW MLK 128/33G").</span></div>
                            <div class="field-row"><span class="field-label">Sub-Department</span><span class="field-desc">The product category or department classification (e.g., Beverage,
                                    Snacks & Biscuits, Confectionery).</span></div>
                            <div class="field-row"><span class="field-label">Price (SRP)</span><span class="field-desc">The Suggested Retail Price per piece of the product.</span></div>
                            @if ($userRole === 'super admin')
                                <div class="field-row">
                                    <span class="field-label">WMS Actual Inventory</span>
                                    <span class="field-desc">
                                        The true physical inventory count from the Warehouse Management System (WMS). This reflects the real stock in the warehouse.
                                    </span>
                                </div>
                                <div class="field-row">
                                    <span class="field-label">WMS Virtual Inventory</span>
                                    <span class="field-desc">
                                        The inventory of the warehouse, but updated at the application level first due to API-delayed fulfillment. This may show allocations and reservations
                                        before the actual warehouse stock is updated.
                                    </span>
                                </div>
                            @else
                                <div class="field-row">
                                    <span class="field-label">WMS Inventory</span>
                                    <span class="field-desc">
                                        The warehouse stock for your store. This number may change as orders are placed or cancelled.
                                    </span>
                                </div>
                            @endif
                            <div class="field-row"><span class="field-label">Stocks</span><span class="field-desc">The computed available stock after accounting for allocations and pending
                                    orders.</span></div>
                            <div class="field-row"><span class="field-label">Allocation</span><span class="field-desc">The number of cases currently allocated (reserved) for pending orders.
                                    Decreases as orders are placed, increases when orders are cancelled or rejected.</span></div>
                            <div class="field-row"><span class="field-label">Case Pack</span><span class="field-desc">The number of individual pieces in one case (e.g., 12 means 12 pieces per
                                    case). Some products have multiple pack sizes shown with "|" separator (e.g., "12|24").</span></div>
                            <div class="field-row"><span class="field-label">C/BC Scheme </span><span class="field-desc">Use “Buy X, Get Y” format (e.g. 15+1).</span></div>
                            <div class="field-row"><span class="field-label">PO15 Scheme</span><span class="field-desc">Same “X+Y” format (e.g. 10+2)</span></div>
                            <div class="field-row"><span class="field-label">Discount Scheme</span><span class="field-desc">Any standing discount scheme configured for this product (fixed
                                    amount or
                                    percentage).</span></div>
                            <div class="field-row"><span class="field-label">Freebie SKU</span><span class="field-desc">The SKU of the linked freebie product. When this product is ordered
                                    with a "Freebie" sale type, the freebie product auto-populates in the order form.</span></div>
                        </div>
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     6. STATUS REFERENCE
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="statuses" data-roles="personnel manager admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">06</span>
                            <h2 class="text-lg font-semibold text-gray-900">Status Reference</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">

                        {{-- ── PERSONNEL & ADMIN: Full status table ── --}}
                        <div data-roles="personnel admin">
                            <h3 class="text-sm font-semibold text-gray-900">Order Statuses</h3>
                            <div class="mt-3 overflow-hidden rounded-lg ring-1 ring-gray-200">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            <th class="px-4 py-2.5">Status</th>
                                            <th class="px-4 py-2.5">Meaning</th>
                                            <th class="px-4 py-2.5">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ([['New Order', 'bg-blue-500', 'Order just submitted, not yet sent for approval', 'Review details, then Request For Approval'], ['For Approval', 'bg-purple-500', 'Sent to manager for review', 'Wait for manager to approve or reject'], ['Approved', 'bg-green-500', 'Manager approved the order', 'Generate SO# to begin fulfillment'], ['Completed', 'bg-teal-500', 'Order fully fulfilled and received', 'No further action needed'], ['Rejected', 'bg-red-500', 'Manager rejected — check Order Notes for reason', 'Revise the order and resubmit for approval'], ['Cancelled', 'bg-gray-400', 'Order was cancelled', 'Can be restored if needed']] as $st)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2.5"><span class="inline-flex items-center gap-1.5 text-xs font-medium"><span
                                                            class="{{ $st[1] }} inline-block h-2 w-2 rounded-full"></span>{{ $st[0] }}</span></td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $st[2] }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $st[3] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── MANAGER: Only 3 statuses ── --}}
                        <div data-roles="manager">
                            <h3 class="text-sm font-semibold text-gray-900">Order Statuses</h3>
                            <p class="mt-1 text-sm text-gray-500">As a manager, you only see orders with the following three statuses:</p>
                            <div class="mt-3 overflow-hidden rounded-lg ring-1 ring-gray-200">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            <th class="px-4 py-2.5">Status</th>
                                            <th class="px-4 py-2.5">Meaning</th>
                                            <th class="px-4 py-2.5">Your Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ([['For Approval', 'bg-purple-500', 'Store personnel submitted this order for your review. It is waiting for your decision.', 'Review all details. Approve if correct, or reject with a reason if there are issues.'], ['Approved', 'bg-green-500', 'You approved this order. Store personnel can now proceed with fulfillment.', 'No further action needed. Personnel will Generate SO# and track shipments.'], ['Rejected', 'bg-red-500', 'You rejected this order with a reason. Inventory allocation has been reverted.', 'Personnel will see your notes, revise the order, and may resubmit for approval.']] as $st)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2.5"><span class="inline-flex items-center gap-1.5 text-xs font-medium"><span
                                                            class="{{ $st[1] }} inline-block h-2 w-2 rounded-full"></span>{{ $st[0] }}</span></td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $st[2] }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $st[3] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <h3 class="mt-6 text-sm font-semibold text-gray-900">Item Transfer Statuses</h3>
                        <p class="mt-1 text-sm text-gray-500">After SO# is generated, each item is tracked through the warehouse fulfillment process:</p>
                        <div class="mt-3 overflow-hidden rounded-lg ring-1 ring-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-2.5">Status</th>
                                        <th class="px-4 py-2.5">Meaning</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ([['N/A', 'No transfer number generated yet'], ['Processing', 'Transfer is being processed for warehouse picking'], ['Picking', 'Items are being picked at the warehouse'], ['Shipped', 'Items are in transit to the store'], ['Received', 'Store has confirmed receipt of the items'], ['Error / Not Found', 'Issue with tracking — contact IT support']] as $ts)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ $ts[0] }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $ts[1] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     PERSONNEL: Workflows
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="workflows" data-roles="personnel">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">07</span>
                            <h2 class="text-lg font-semibold text-gray-900">Common Workflows</h2>
                        </div>
                    </div>
                    <div class="space-y-6 px-6 pb-6 pt-3">
                        @foreach ([['Creating a New Order', ['Forms → Sales Order Form', 'Complete all header fields (Request Details, Customer Info, Payment, Dispatch)', 'Add products with Sale Type, quantities, and schemes', 'Click Submit Order', 'Open the order from Orders List → Request For Approval']], ['After Manager Approval', ['Open the approved order from Orders List', 'Click Generate SO# to create transfer numbers', 'Track item fulfillment: Picking → Shipped → Received', 'Once all items received, Complete Order']], ['Editing an Order', ['Open the order from Orders List', 'Edit editable fields (yellow highlight appears on changes)', 'To cancel specific items: check boxes → Cancel Selected Items', 'Click Update to save all changes']]] as $wf)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $wf[0] }}</h3>
                                <div class="mt-2 space-y-1.5">
                                    @foreach ($wf[1] as $s)
                                        <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2"><span
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[0.65rem] font-bold text-white">{{ $loop->iteration }}</span><span
                                                class="text-sm text-gray-600">{!! $s !!}</span></div>
                                    @endforeach
                                </div>
                            </div>
                            @if (!$loop->last)
                                <hr class="border-gray-100">
                            @endif
                        @endforeach
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     ADMIN: User Management
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="user-management" data-roles="admin">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white shadow-sm">★</span>
                            <h2 class="text-lg font-semibold text-gray-900">User Management</h2>
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Admin Only</span>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <p class="text-sm text-gray-600">Navigate to <strong>Users → User List</strong>. Create, edit, and manage system users.</p>
                        <h3 class="mt-5 text-sm font-semibold text-gray-900">Adding a User</h3>
                        <div class="mt-2 space-y-1.5">
                            @foreach (['Click <strong>Add User</strong>', 'Fill: Name, Email, Password, Role, Location', 'Click <strong>Add User</strong> to create'] as $s)
                                <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2.5"><span
                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[0.65rem] font-bold text-white">{{ $loop->iteration }}</span><span
                                        class="text-sm text-gray-600">{!! $s !!}</span></div>
                            @endforeach
                        </div>
                        <h3 class="mt-6 text-sm font-semibold text-gray-900">Roles</h3>
                        <div class="mt-3 overflow-hidden rounded-lg ring-1 ring-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-2.5">Role</th>
                                        <th class="px-4 py-2.5">Access</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ([['Super Admin', 'Full system access'], ['Manager', 'Approve/reject, regional reports'], ['Store Admin', 'Store-level management'], ['Store Personnel', 'Create orders, view products'], ['Warehouse Admin', 'Product management + depot filter'], ['Warehouse Personnel', 'View products + depot filter']] as $r)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 font-medium text-gray-800">{{ $r[0] }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $r[1] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <h3 class="mt-6 text-sm font-semibold text-gray-900">Editing</h3>
                        <p class="mt-1 text-sm text-gray-500">Click <strong>Edit</strong> → update fields → leave Password blank to keep current → <strong>Update User</strong>.</p>
                        <div class="mt-3 flex gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            Role changes update permissions immediately.
                        </div>
                    </div>
                </section>

                {{-- ══════════════════════════════════════════
                     PERSONNEL: Troubleshooting
                ══════════════════════════════════════════ --}}
                <section class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" id="troubleshooting" data-roles="personnel">
                    <div class="px-6 pt-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">08</span>
                            <h2 class="text-lg font-semibold text-gray-900">Troubleshooting</h2>
                        </div>
                    </div>
                    <div class="px-6 pb-6 pt-3">
                        <div class="overflow-hidden rounded-lg ring-1 ring-gray-200">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-2.5">Error</th>
                                        <th class="px-4 py-2.5">Cause</th>
                                        <th class="px-4 py-2.5">Solution</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ([['MBC Card not found', 'Invalid/incomplete card', 'Check & re-enter 16 digits'], ['Order Items missing', 'Incomplete header', 'Complete all required fields'], ['Cannot submit', 'No products added', 'Add at least one product with qty > 0'], ['Update inactive', 'No changes detected', 'Edit a field first — yellow highlight appears']] as $e)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5"><span class="font-medium text-red-600">"{{ $e[0] }}"</span></td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $e[1] }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $e[2] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <h3 class="mt-6 text-sm font-semibold text-gray-900">Quick Tips</h3>
                        <div class="mt-3 space-y-2">
                            @foreach (['<strong>Dashboard</strong> refreshes on navigation — use as your first stop each day.', '<strong>Orders List</strong> updates via AJAX — filters and pagination don\'t reload the page.', '<strong>MBC Card</strong> must be exactly 16 digits. Only numbers accepted.', '<strong>Yellow highlights</strong> on Order Details = unsaved changes. Click Update to save.', '<strong>Products page</strong> is view-only — contact your admin for product changes.', '<strong>Order Notes</strong> log all actions with timestamps — use as your audit trail.'] as $t)
                                <div class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5"
                                        viewBox="0 0 24 24">
                                        <path d="M5 13l4 4L19 7" />
                                    </svg>
                                    <p class="text-sm text-gray-600">{!! $t !!}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <div class="pb-8 pt-4 text-center">
                    <p class="text-xs text-gray-400">ISO B2B2C Ordering System — User Guide — v1.2 — March 2026</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ LIGHTBOX ═══ --}}
    <div class="lightbox-overlay" id="lightboxOverlay">
        <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
        <div class="lightbox-content" id="lightboxContent">
            <button
                class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow-md backdrop-blur transition hover:rotate-90 hover:bg-white"
                id="lightboxClose">
                <svg width="12" height="12" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M1 1l12 12M13 1L1 13" />
                </svg>
            </button>
            <img id="lightboxImg" src="" alt="">
            <div class="border-t border-gray-100 bg-white px-4 py-2.5 text-center text-sm text-gray-500" id="lightboxCaption"></div>
        </div>
    </div>

    {{-- ═══ JS ═══ --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            const availableRoles = @json($availableRoles);

            const sidebarConfig = {
                personnel: [{
                        label: 'Getting Started'
                    },
                    {
                        href: '#dashboard',
                        section: 'dashboard',
                        icon: 'grid',
                        text: 'Dashboard'
                    },
                    {
                        label: 'Order Management'
                    },
                    {
                        href: '#sales-order-form',
                        section: 'sales-order-form',
                        icon: 'file',
                        text: 'Sales Order Form'
                    },
                    {
                        href: '#sof-header',
                        text: 'Header Fields',
                        sub: true
                    },
                    {
                        href: '#sof-items',
                        text: 'Order Items',
                        sub: true
                    },
                    {
                        href: '#sof-submit',
                        text: 'Submitting',
                        sub: true
                    },
                    {
                        href: '#orders-list',
                        section: 'orders-list',
                        icon: 'list',
                        text: 'Orders List'
                    },
                    {
                        href: '#order-details',
                        section: 'order-details',
                        icon: 'info',
                        text: 'Order Details'
                    },
                    {
                        href: '#od-actions',
                        text: 'Actions',
                        sub: true
                    },
                    {
                        href: '#od-invoice',
                        text: 'Invoice & Print',
                        sub: true
                    },
                    {
                        label: 'Catalog'
                    },
                    {
                        href: '#products',
                        section: 'products',
                        icon: 'box',
                        text: 'Products'
                    },
                    {
                        label: 'Reference'
                    },
                    {
                        href: '#statuses',
                        section: 'statuses',
                        icon: 'check',
                        text: 'Statuses'
                    },
                    {
                        href: '#workflows',
                        section: 'workflows',
                        icon: 'bolt',
                        text: 'Workflows'
                    },
                    {
                        href: '#troubleshooting',
                        section: 'troubleshooting',
                        icon: 'help',
                        text: 'Troubleshooting'
                    },
                ],
                manager: [{
                        label: 'Getting Started'
                    },
                    {
                        href: '#dashboard',
                        section: 'dashboard',
                        icon: 'grid',
                        text: 'Dashboard'
                    },
                    {
                        label: 'Order Review'
                    },
                    {
                        href: '#orders-list',
                        section: 'orders-list',
                        icon: 'list',
                        text: 'Orders List'
                    },
                    {
                        href: '#order-details',
                        section: 'order-details',
                        icon: 'info',
                        text: 'Order Details'
                    },
                    {
                        href: '#od-panels',
                        text: 'Info Panels',
                        sub: true
                    },
                    {
                        href: '#od-actions',
                        text: 'Actions',
                        sub: true
                    },
                    {
                        href: '#od-invoice',
                        text: 'Invoice & Print',
                        sub: true
                    },
                    {
                        label: 'Approval'
                    },
                    {
                        href: '#approving-orders',
                        section: 'approving-orders',
                        icon: 'shield',
                        text: 'Approve / Reject'
                    },
                    {
                        label: 'Notifications'
                    },
                    {
                        href: '#email-notifications',
                        section: 'email-notifications',
                        icon: 'mail',
                        text: 'Email Notifications'
                    },
                    // {
                    //     href: '#email-notifications',
                    //     text: 'Email Samples',
                    //     sub: true
                    // },
                    {
                        label: 'Reference'
                    },
                    {
                        href: '#statuses',
                        section: 'statuses',
                        icon: 'check',
                        text: 'Statuses'
                    },
                ],
                admin: [{
                        label: 'Getting Started'
                    },
                    {
                        href: '#dashboard',
                        section: 'dashboard',
                        icon: 'grid',
                        text: 'Dashboard'
                    },
                    {
                        label: 'Orders'
                    },
                    {
                        href: '#sales-order-form',
                        section: 'sales-order-form',
                        icon: 'file',
                        text: 'Sales Order Form'
                    },
                    {
                        href: '#orders-list',
                        section: 'orders-list',
                        icon: 'list',
                        text: 'Orders List'
                    },
                    {
                        href: '#order-details',
                        section: 'order-details',
                        icon: 'info',
                        text: 'Order Details'
                    },
                    {
                        label: 'Administration'
                    },
                    {
                        href: '#user-management',
                        section: 'user-management',
                        icon: 'users',
                        text: 'User Management'
                    },
                    {
                        href: '#products',
                        section: 'products',
                        icon: 'box',
                        text: 'Products'
                    },
                    {
                        label: 'Reference'
                    },
                    {
                        href: '#statuses',
                        section: 'statuses',
                        icon: 'check',
                        text: 'Statuses'
                    },
                ]
            };

            const icons = {
                grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
                file: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>',
                list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
                info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
                box: '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                check: '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                shield: '<path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                users: '<path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
                bolt: '<path d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                help: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/>',
            };

            let activeRole = '{{ $defaultTab }}';
            let observer = null;
            const titles = {
                personnel: 'Store Personnel User Guide',
                manager: 'Manager User Guide',
                admin: 'Super Admin User Guide'
            };

            function buildSidebar(role) {
                const nav = document.getElementById('guideSidebar');
                if (!nav) return;
                nav.innerHTML = '';
                (sidebarConfig[role] || []).forEach(item => {
                    if (item.label) {
                        const d = document.createElement('div');
                        d.className = 'px-3 pb-1 pt-4 text-[0.6rem] font-semibold uppercase tracking-wider text-gray-400';
                        d.textContent = item.label;
                        nav.appendChild(d);
                    } else if (item.sub) {
                        const a = document.createElement('a');
                        a.href = item.href;
                        a.className = 'guide-nav-sub block rounded-md px-3 py-1.5 pl-9 text-xs font-medium text-gray-400 transition-all hover:bg-gray-50 hover:text-gray-700';
                        a.textContent = item.text;
                        nav.appendChild(a);
                    } else {
                        const a = document.createElement('a');
                        a.href = item.href;
                        a.className =
                            'guide-nav-link flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-all hover:bg-gray-50 hover:text-gray-900';
                        if (item.section) a.dataset.section = item.section;
                        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        svg.setAttribute('class', 'h-4 w-4 opacity-50');
                        svg.setAttribute('fill', 'none');
                        svg.setAttribute('stroke', 'currentColor');
                        svg.setAttribute('stroke-width', '1.5');
                        svg.setAttribute('viewBox', '0 0 24 24');
                        svg.innerHTML = icons[item.icon] || '';
                        a.appendChild(svg);
                        const sp = document.createElement('span');
                        sp.textContent = item.text;
                        a.appendChild(sp);
                        nav.appendChild(a);
                    }
                });
            }

            function setActiveRole(role) {
                activeRole = role;
                document.querySelectorAll('.sidebar-role-btn').forEach(b => {
                    b.classList.toggle('active', b.dataset.role === role);
                    b.classList.toggle('bg-blue-50', b.dataset.role === role);
                    b.classList.toggle('!text-blue-700', b.dataset.role === role);
                    b.classList.toggle('font-semibold', b.dataset.role === role);
                });
                const t = document.getElementById('role-title');
                if (t) t.textContent = titles[role] || 'User Guide';
                document.querySelectorAll('[data-roles]').forEach(el => {
                    el.classList.toggle('role-visible', el.dataset.roles.split(' ').includes(role));
                });
                buildSidebar(role);
                setTimeout(initScrollSpy, 50);
                document.getElementById('guideSidebarCol')?.classList.remove('open');
            }

            function initScrollSpy() {
                if (observer) observer.disconnect();
                const links = document.querySelectorAll('.guide-nav-link[data-section]');
                const subs = document.querySelectorAll('.guide-nav-sub');
                const sections = document.querySelectorAll('.section-card.role-visible');
                observer = new IntersectionObserver(entries => {
                    entries.forEach(e => {
                        if (e.isIntersecting) {
                            const id = e.target.id;
                            links.forEach(l => l.classList.toggle('active', l.dataset.section === id));
                            subs.forEach(s => {
                                const target = document.querySelector(s.getAttribute('href'));
                                if (target) {
                                    const r = target.getBoundingClientRect();
                                    s.classList.toggle('active', r.top < innerHeight / 2 && r.bottom > 0);
                                }
                            });
                        }
                    });
                }, {
                    rootMargin: '-80px 0px -60% 0px',
                    threshold: 0.1
                });
                sections.forEach(s => observer.observe(s));
            }

            if (availableRoles.length > 1) {
                document.querySelectorAll('.sidebar-role-btn').forEach(b => b.addEventListener('click', () => setActiveRole(b.dataset.role)));
            }
            document.addEventListener('click', e => {
                if (e.target.closest('.guide-nav-link,.guide-nav-sub')) document.getElementById('guideSidebarCol')?.classList.remove('open');
            });

            // Lightbox
            const ov = document.getElementById('lightboxOverlay'),
                ct = document.getElementById('lightboxContent'),
                li = document.getElementById('lightboxImg'),
                lc = document.getElementById('lightboxCaption');
            document.addEventListener('click', e => {
                const f = e.target.closest('.screenshot-frame');
                if (!f) return;
                const img = f.querySelector('img'),
                    cap = f.querySelector('p');
                if (!img) return;
                const r = img.getBoundingClientRect(),
                    cx = r.left + r.width / 2 - innerWidth / 2,
                    cy = r.top + r.height / 2 - innerHeight / 2;
                ct.style.transition = 'none';
                ct.style.transform = `translate(${cx}px,${cy}px) scale(0.4)`;
                ct.style.opacity = '0';
                li.src = img.src;
                li.alt = img.alt;
                lc.textContent = cap?.textContent || '';
                ov.classList.remove('closing');
                ov.classList.add('active');
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    ct.style.transition = 'transform 0.4s cubic-bezier(0.32,0.72,0,1),opacity 0.3s';
                    ct.style.transform = 'scale(1)';
                    ct.style.opacity = '1';
                }));
                document.body.style.overflow = 'hidden';
            });

            function closeLB() {
                ov.classList.add('closing');
                ct.style.transform = 'scale(0.85)';
                ct.style.opacity = '0';
                setTimeout(() => {
                    ov.classList.remove('active', 'closing');
                    ct.style.transition = 'none';
                    ct.style.transform = '';
                    ct.style.opacity = '';
                    li.src = '';
                    document.body.style.overflow = '';
                }, 350);
            }
            document.getElementById('lightboxClose').addEventListener('click', e => {
                e.stopPropagation();
                closeLB();
            });
            document.getElementById('lightboxBackdrop').addEventListener('click', closeLB);
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && ov.classList.contains('active')) closeLB();
            });

            setActiveRole(activeRole);
        });
    </script>
@endsection
