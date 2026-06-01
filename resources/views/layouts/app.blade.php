<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ISO B2B Ordering System">
    <meta name="theme-color" content="#0f172a" />
    <title>ISO B2B2C Ordering System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/MarengEms_Logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style nonce="{{ $cspNonce ?? '' }}">
        /* RESET & GLOBAL */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            overflow-x: hidden;
            background: #f8fafc;
        }

        ul,
        li {
            list-style: none;
        }

        /* SIDEBAR WIDTH STATES */
        .sidebar-expanded {
            width: 14rem;
            min-width: 14rem;
        }

        .sidebar-collapsed {
            width: 4.5rem;
            min-width: 4.5rem;
        }

        /* SIDEBAR CONTAINER – allow tooltips to escape */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(203, 213, 225, 0.3);
            transition: width 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            display: flex;
            flex-direction: column;
            z-index: 9999;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.01);
        }

        /* INNER LAYOUT – allow tooltips to extend horizontally */
        .general-sidebar {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            padding: 2.75rem 0.75rem 0.75rem 0.75rem;
            overflow-y: auto;
            overflow-x: visible;
        }

        /* SCROLLABLE AREA – auto‑hide scrollbar */
        .nav-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: visible;
            margin-top: 0.5rem;
            padding-right: 0.25rem;
            scrollbar-width: thin;
        }

        .nav-content::-webkit-scrollbar {
            width: 0px;
            background: transparent;
            transition: width 0.2s;
        }

        .nav-content:hover::-webkit-scrollbar {
            width: 3px;
        }

        .nav-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 20px;
        }

        .nav-content:hover::-webkit-scrollbar-thumb {
            background: #94a3b8;
        }

        /* BOTTOM FIXED (My Account + Logout) */
        .nav-bottom {
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid #eef2ff;
        }

        /* MAIN CONTENT OFFSET */
        .main-container {
            transition: margin-left 0.2s ease;
            padding-top: 2rem;
        }

        @media (min-width: 768px) {
            .sidebar-expanded~.main-container {
                margin-left: 14rem;
            }

            .sidebar-collapsed~.main-container {
                margin-left: 4.5rem;
            }
        }

        /* TYPOGRAPHY & ICONS */
        .nav-text,
        .logo-text {
            transition: opacity 0.2s ease;
            white-space: nowrap;
        }

        .sidebar-collapsed .nav-text,
        .sidebar-collapsed .logo-text {
            opacity: 0;
            width: 0;
            display: none;
        }

        /* Icons fixed size */
        .nav-parent svg,
        .logout-btn svg {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
        }

        /* TRIANGLE ARROW – even smaller */
        .arrow-icon {
            width: 0.65rem;
            height: 0.65rem;
            fill: #94a3b8;
            transition: transform 0.2s, fill 0.2s;
            margin-left: auto;
            pointer-events: auto;
        }

        .nav-parent:hover .arrow-icon {
            fill: #475569;
        }

        .sidebar-collapsed .arrow-icon {
            display: none;
        }

        /* SECTION LABELS – ultra subtle */
        .section-label {
            margin: 0rem 0 0.4rem 0.5rem;
            padding-top: 1rem;
            font-size: 0.6rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #a1a9b5;
        }

        .sidebar-collapsed .section-label span {
            display: none;
        }

        .sidebar-collapsed .section-label {
            padding-top: 0rem;
            text-align: left;
            margin-left: 0;
        }

        /* Accordion item – base styles */
        .accordion-item {
            border-radius: 0.6rem;
            transition: background 0.2s;
        }

        /* When any child is active, highlight the entire accordion box */
        .accordion-item.has-active {
            background: rgba(59, 130, 246, 0.08);
        }

        /* MAIN NAV ITEM */
        .nav-parent {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.6rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #4b5563;
            transition: all 0.15s;
            cursor: pointer;
            width: 100%;
            background: transparent;
            border: none;
            text-decoration: none;
        }

        .nav-parent:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* SOFT ACTIVE HIGHLIGHT – for direct route match */
        .nav-parent.active {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            font-weight: 500;
        }

        /* When accordion has active child, parent button gets subtle touch */
        .accordion-item.has-active .nav-parent {
            color: #1e40af;
        }

        /* Active sub‑item – only lighter font color */
        .sub-item.active {
            color: #3b82f6;
            font-weight: 500;
            background: transparent;
        }

        /* Normal sub‑item – gray */
        .sub-item {
            color: #6b7280;
            background: transparent;
        }

        /* ACCORDION SUBMENU (hidden in collapsed mode) */
        .submenu-accordion {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s;
            margin-left: 1.75rem;
            padding-left: 0.75rem;
            border-left: 1px solid #e9eef3;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .submenu-accordion.open {
            max-height: 300px;
            opacity: 1;
            margin-bottom: 0.3rem;
        }

        .sidebar-collapsed .submenu-accordion {
            display: none;
        }

        .sub-item {
            padding: 0.35rem 0.35rem;
            font-size: 0.8rem;
            border-radius: 0.5rem;
            transition: all 0.1s;
            display: block;
            text-decoration: none;
        }

        .sub-item:hover {
            background: #f8fafc;
            color: #1e40af;
        }

        /* TOOLTIP – absolute, but now container allows overflow-x:visible */
        .tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 0.75rem;
            background: #1e293b;
            color: white;
            padding: 0.2rem 0.7rem;
            border-radius: 0.4rem;
            font-size: 0.7rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
            z-index: 100;
            font-weight: 400;
            letter-spacing: 0.01em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* Inline tooltips sit inside the scrollable nav (overflow-y:auto forces
   overflow-x to clip), so they get cut off at the sidebar edge. Hide them
   and render a viewport-fixed copy via JS instead (see .floating-tooltip). */
        #sidebar .nav-parent .tooltip {
            display: none;
        }

        /* Floating tooltip rendered on <body> — escapes all overflow clipping */
        .floating-tooltip {
            position: fixed;
            background: #1e293b;
            color: #fff;
            padding: 0.5rem 0.7rem;
            border-radius: 0.4rem;
            font-size: 0.7rem;
            line-height: 1.2;
            white-space: nowrap;
            font-weight: 400;
            letter-spacing: 0.01em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.12s ease;
            z-index: 10060;
        }

        .floating-tooltip.show {
            opacity: 1;
        }

        /* Hide tooltips when sidebar expanded (no need) */
        .sidebar-expanded .tooltip {
            display: none;
        }

        /* LOGOUT RED */
        .logout-btn {
            color: #e11d48 !important;
        }

        .logout-btn svg {
            stroke: #e11d48 !important;
        }

        .logout-btn:hover {
            background-color: #fff1f2 !important;
            color: #be123c !important;
        }

        .logout-btn:hover svg {
            stroke: #be123c !important;
        }

        /* COLLAPSED DROPDOWN (floating menu) */
        .collapsed-dropdown {
            position: fixed;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            border-radius: 0.8rem;
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.2);
            border: 1px solid #eef2ff;
            padding: 0.4rem;
            min-width: 170px;
            z-index: 10050;
            opacity: 0;
            transform: scale(0.96);
            pointer-events: none;
            transition: opacity 0.12s, transform 0.12s;
        }

        .collapsed-dropdown.show {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }

        .collapsed-dropdown a {
            display: block;
            padding: 0.45rem 1rem;
            font-size: 0.8rem;
            color: #334155;
            border-radius: 0.5rem;
            text-decoration: none;
        }

        .collapsed-dropdown a:hover {
            background: #f1f5f9;
            color: #1d4ed8;
        }

        .collapsed-dropdown a.active {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }

        /* TOPBAR */
        .topbar {
            z-index: 10005;
            backdrop-filter: blur(8px);
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }

        /* LOGO */
        .logo-wrapper {
            margin-bottom: 0.25rem;
        }

        /* MOBILE */
        @media (max-width: 767px) {
            .main-container {
                margin-left: 0 !important;
            }

            #sidebar {
                width: 16rem !important;
                transform: translateX(-100%);
                transition: transform 0.2s ease;
                z-index: 10001;
                /* sit ABOVE the overlay (was 9999, under overlay 10000) */
                background: #ffffff;
                /* solid panel — no see-through */
                backdrop-filter: none;
                /* no frosted glass on mobile */
                -webkit-backdrop-filter: none;
            }

            #sidebar.mobile-open {
                transform: translateX(0);
            }

            .general-sidebar {
                padding-top: 3rem;
                /* increased from 1rem to push logo down */
                overflow-y: auto;
            }

            .nav-text,
            .logo-text,
            .section-label span {
                display: inline-block !important;
                opacity: 1 !important;
            }

            .tooltip,
            .collapsed-dropdown {
                display: none !important;
            }

            .submenu-accordion {
                display: flex !important;
            }

            .sidebar-collapsed .arrow-icon {
                display: inline-block !important;
            }

            .accordion-item.has-active {
                background: rgba(59, 130, 246, 0.08);
            }
        }

        #mobile-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            /* clean dark scrim */
            z-index: 10000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        #mobile-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body class="bg-slate-50">

    <div id="mobile-overlay"></div>

    <!-- TOPBAR -->
    <div class="topbar fixed left-0 right-0 top-0 z-[10005] flex h-10 items-center justify-between gap-2 bg-gradient-to-r from-slate-900 to-slate-800 px-3 shadow-md sm:px-4">
        <div class="flex min-w-0 items-center gap-2">
            <!-- Desktop collapse toggle – hidden on mobile (the drawer uses the menu button) -->
            <button id="sidebarToggle" class="toggle-btn relative hidden rounded-lg p-1.5 text-white/80 transition hover:bg-white/10 md:inline-flex" aria-label="Toggle sidebar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <div class="tooltip !left-auto !right-full !ml-0 !mr-2" id="sidebarToggleTooltip">Collapse Menu</div>
            </button>
            <!-- Mobile drawer toggle -->
            <button id="mobileMenuBtn" class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 md:hidden" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        @php
            use App\Support\LocationConfig;
            $user = Auth::user();
            $code = trim((string) $user?->user_location);
            $storeLabel = LocationConfig::stores()[$code] ?? null;
            $regionLabel = LocationConfig::regionLabels()[$code] ?? null;
            $fullLocation = $storeLabel ?? ($regionLabel ?? $code);
        @endphp

        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
            @php
                $allowedIds = [1, 2, 3];
                $currentUserId = Auth::id();
                $switchableRoles = ['super admin', 'store personnel', 'store manager', 'warehouse personnel', 'warehouse manager'];
                $currentRole = session('switched_role') ?? Auth::user()->role;
                $currentLocation = session('switched_location') ?? Auth::user()->user_location;
                $storeLocations = LocationConfig::stores();
                $regionLabels = LocationConfig::regionLabels();
                $locationOptions = [];
                foreach ($regionLabels as $rc => $rl) {
                    $locationOptions[$rc] = "🌍 {$rl} (Region)";
                }
                foreach ($storeLocations as $sc => $sn) {
                    $locationOptions[$sc] = "🏪 {$sn}";
                }
            @endphp
            @if (in_array($currentUserId, $allowedIds))
                <select id="role-switcher" class="max-w-[30vw] cursor-pointer truncate rounded-md border-0 bg-white/10 px-2 py-1 text-xs text-white focus:ring-0 sm:max-w-none sm:text-sm">
                    @foreach ($switchableRoles as $role)
                        <option class="text-black" value="{{ $role }}" {{ $currentRole === $role ? 'selected' : '' }}>🎭 {{ ucfirst($role) }}</option>
                    @endforeach
                </select>
                <select id="location-switcher"
                    class="min-w-0 max-w-[40vw] cursor-pointer truncate rounded-md border-0 bg-white/10 px-2 py-1 text-xs text-white focus:ring-0 sm:min-w-[150px] sm:max-w-none sm:text-sm">
                    @foreach ($locationOptions as $lc => $label)
                        <option class="text-black" value="{{ $lc }}" {{ (string) $currentLocation === (string) $lc ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
            <span class="hidden max-w-[10rem] truncate text-sm text-white/90 sm:inline lg:max-w-none">Howdy, {{ Auth::user()->name }}
                ({{ $storeLocations[$currentLocation] ?? ($regionLabels[$currentLocation] ?? $currentLocation) }})</span>
        </div>
    </div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="sidebar-expanded">
        <nav class="general-sidebar">
            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="logo-wrapper m-1 block border-b border-gray-100/60 pt-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/MarengEms_Logo.png') }}" alt="Logo" class="h-9 w-9 rounded-xl shadow-sm">
                    <span class="logo-text text-base font-semibold tracking-tight text-gray-800">ISO B2B2C</span>
                </div>
            </a>

            <!-- Scrollable menu -->
            <div class="nav-content">
                <ul class="space-y-0.5">
                    <!-- CORE -->
                    <li class="section-label"><span>Core</span></li>
                    <li>
                        <a href="{{ route('dashboard') }}" class="nav-parent {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z" />
                            </svg>
                            <span class="nav-text">Dashboard</span>
                            <div class="tooltip">Dashboard</div>
                        </a>
                    </li>

                    <!-- Orders & Forms (merged) -->
                    <li class="accordion-item" data-accordion="orders">
                        <button class="nav-parent w-full text-left" aria-expanded="false">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                            <span class="nav-text">Transactions</span>
                            <svg class="arrow-icon" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                <polygon points="8,5 16,12 8,19" />
                            </svg>
                            <div class="tooltip">Transactions</div>
                        </button>
                        <ul class="submenu-accordion">
                            <li><a href="{{ route('orders.index') }}" class="sub-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">Sales Order List</a></li>
                            @auth
                                @if (!in_array(auth()->user()->role, ['store manager', 'warehouse personnel', 'warehouse manager']))
                                    <li><a href="{{ route('forms.sof') }}" class="sub-item {{ request()->routeIs('forms.sof') ? 'active' : '' }}">Sales Order Form</a></li>
                                @endif
                            @endauth
                            @if (preg_match('/orders\/\d+$/', request()->path()))
                                <li><a href="{{ url()->current() }}" class="sub-item active">Order Details</a></li>
                            @endif
                        </ul>
                    </li>


                    <!-- Analytics (Reports) -->
                    <li class="section-label"><span>Analytics</span></li>
                    <li class="accordion-item" data-accordion="reports">
                        <button class="nav-parent w-full text-left" aria-expanded="false">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20h16M4 4v16" />
                                <path d="M4 14l4-4 4 3 6-6 2 2" />
                            </svg>
                            <span class="nav-text">Reports</span>
                            <svg class="arrow-icon" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                <polygon points="8,5 16,12 8,19" />
                            </svg>
                            <div class="tooltip">Reports</div>
                        </button>
                        <ul class="submenu-accordion">
                            <li><a href="{{ route('reports.sales') }}" class="sub-item {{ request()->routeIs('reports.sales') ? 'active' : '' }}">Sales Overview</a></li>
                            <li><a href="{{ route('reports.orders') }}" class="sub-item {{ request()->routeIs('reports.orders') ? 'active' : '' }}">Orders Report</a></li>
                            <li><a href="{{ route('reports.payments') }}" class="sub-item {{ request()->routeIs('reports.payments') ? 'active' : '' }}">Mode of Payments</a></li>
                        </ul>
                    </li>

                    <!-- Inventory (Products) conditional -->
                    @auth
                        @if (in_array(auth()->user()->role, ['super admin', 'store personnel']))
                            <li class="section-label"><span>Inventory</span></li>
                            <li class="accordion-item" data-accordion="products">
                                <button class="nav-parent w-full text-left" aria-expanded="false">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span class="nav-text">Products</span>
                                    <svg class="arrow-icon" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                        <polygon points="8,5 16,12 8,19" />
                                    </svg>
                                    <div class="tooltip">Products</div>
                                </button>
                                <ul class="submenu-accordion">
                                    <li><a href="{{ route('products.index') }}" class="sub-item {{ request()->routeIs('products.index') ? 'active' : '' }}">Product List</a></li>
                                    @if (!in_array(auth()->user()->role, ['store personnel']))
                                        <li><a href="{{ route('products.create') }}" class="sub-item {{ request()->routeIs('products.create') ? 'active' : '' }}">Add Product</a></li>
                                        <li><a href="{{ route('products.import.show') }}" class="sub-item {{ request()->routeIs('products.import.show') ? 'active' : '' }}">Import CSV</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    @endauth

                    <!-- Admin & Utilities (super admin) -->
                    @auth
                        @if (auth()->user()->role === 'super admin')
                            <li class="section-label"><span>Control</span></li>
                            <li class="accordion-item" data-accordion="admin">
                                <button class="nav-parent w-full text-left" aria-expanded="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <span class="nav-text">Admin</span>
                                    <svg class="arrow-icon" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                        <polygon points="8,5 16,12 8,19" />
                                    </svg>
                                    <div class="tooltip">Admin</div>
                                </button>
                                <ul class="submenu-accordion">
                                    <li><a href="{{ route('users.index') }}" class="sub-item {{ request()->routeIs('users.index') ? 'active' : '' }}">User Management</a></li>
                                    <li><a href="{{ route('settings.index') }}" class="sub-item {{ request()->routeIs('settings*') ? 'active' : '' }}">System Settings</a></li>
                                    <li><a href="{{ route('logs') }}" class="sub-item {{ request()->routeIs('logs*') ? 'active' : '' }}">Inventory Logs</a></li>
                                    <li><a href="{{ route('activity_logs.index') }}" class="sub-item {{ request()->routeIs('activity_logs*') ? 'active' : '' }}">Activity Logs</a></li>
                                </ul>
                            </li>
                            <li class="accordion-item" data-accordion="utilities">
                                <button class="nav-parent w-full text-left" aria-expanded="false">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                    <span class="nav-text">Utilities</span>
                                    <svg class="arrow-icon" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                        <polygon points="8,5 16,12 8,19" />
                                    </svg>
                                    <div class="tooltip">Utilities</div>
                                </button>
                                <ul class="submenu-accordion">
                                    <li><a href="{{ route('others.inventory.form') }}" class="sub-item {{ request()->routeIs('others.inventory.form') ? 'active' : '' }}">Inventory Form</a>
                                    </li>
                                    <li><a href="{{ route('others.filemanager.index') }}" class="sub-item {{ request()->routeIs('others.filemanager.*') ? 'active' : '' }}">File Manager</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                    @endauth
                    <!-- Support -->
                    <li class="section-label"><span>Support</span></li>
                    <li>
                        <a href="{{ route('user-guide.document') }}" class="nav-parent {{ request()->routeIs('user-guide*') ? 'active' : '' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="nav-text">User Guide</span>
                            <div class="tooltip">User Guide</div>
                        </a>
                    </li>

                </ul>
            </div>

            <!-- Bottom fixed -->
            <div class="nav-bottom">
                <li><a href="{{ route('account.show') }}" class="nav-parent {{ request()->routeIs('account.*') ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A8.966 8.966 0 0112 15c2.21 0 4.236.797 5.879 2.11M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="nav-text">My Account</span>
                        <div class="tooltip">My Account</div>
                    </a></li>
                <div class="pt-2">
                    <a href="#" id="logout-btn" class="logout-btn nav-parent">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="nav-text">Logout</span>
                        <div class="tooltip">Logout</div>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                </div>
            </div>
        </nav>
    </aside>

    <div class="main-container min-h-screen">
        <main class="min-h-screen w-full bg-gradient-to-br from-slate-100 via-white to-indigo-100 py-6">@yield('content')</main>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('mobile-overlay');
            const mobileBtn = document.getElementById('mobileMenuBtn');

            const isMobile = () => window.innerWidth < 768;
            const openMobile = () => {
                sidebar.classList.add('mobile-open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            };
            const closeMobile = () => {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            };

            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                if (toggleBtn) toggleBtn.querySelector('.tooltip').innerText = 'Expand Menu';
            }
            toggleBtn?.addEventListener('click', () => {
                const collapsed = sidebar.classList.contains('sidebar-collapsed');
                if (collapsed) {
                    sidebar.classList.remove('sidebar-collapsed');
                    sidebar.classList.add('sidebar-expanded');
                    localStorage.setItem('sidebarCollapsed', 'false');
                    toggleBtn.querySelector('.tooltip').innerText = 'Collapse Menu';
                } else {
                    sidebar.classList.remove('sidebar-expanded');
                    sidebar.classList.add('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', 'true');
                    toggleBtn.querySelector('.tooltip').innerText = 'Expand Menu';
                }
                closeAllDropdowns();
                hideFloatingTooltip();
            });

            mobileBtn?.addEventListener('click', () => sidebar.classList.contains('mobile-open') ? closeMobile() : openMobile());
            overlay?.addEventListener('click', closeMobile);
            sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
                if (isMobile()) closeMobile();
            }));

            function updateParentHighlights() {
                document.querySelectorAll('.accordion-item').forEach(item => {
                    const hasActiveChild = item.querySelector('.submenu-accordion .sub-item.active') !== null;
                    if (hasActiveChild) {
                        item.classList.add('has-active');
                    } else {
                        item.classList.remove('has-active');
                    }
                });
            }

            document.querySelectorAll('.accordion-item').forEach(item => {
                const btn = item.querySelector('.nav-parent');
                const sub = item.querySelector('.submenu-accordion');
                if (!sub) return;
                const arrow = btn.querySelector('.arrow-icon');
                const hasActiveChild = sub.querySelector('.sub-item.active') !== null;
                if (hasActiveChild && !sidebar.classList.contains('sidebar-collapsed') && !isMobile()) {
                    sub.classList.add('open');
                    if (arrow) arrow.style.transform = 'rotate(90deg)';
                    btn.setAttribute('aria-expanded', 'true');
                    item.classList.add('has-active');
                } else {
                    sub.classList.remove('open');
                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                    btn.setAttribute('aria-expanded', 'false');
                }

                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    if (sidebar.classList.contains('sidebar-collapsed') && !isMobile()) {
                        showCollapsedDropdown(btn, sub);
                    } else {
                        const isOpen = sub.classList.contains('open');
                        if (isOpen) {
                            sub.classList.remove('open');
                            if (arrow) arrow.style.transform = 'rotate(0deg)';
                            btn.setAttribute('aria-expanded', 'false');
                        } else {
                            sub.classList.add('open');
                            if (arrow) arrow.style.transform = 'rotate(90deg)';
                            btn.setAttribute('aria-expanded', 'true');
                        }
                    }
                });
            });

            updateParentHighlights();

            /* ── Floating tooltips for collapsed sidebar ──
               The inline .tooltip elements get clipped by the nav's overflow,
               so when collapsed we render a position:fixed copy on <body>. */
            let activeTooltip = null;

            function hideFloatingTooltip() {
                if (activeTooltip) {
                    activeTooltip.remove();
                    activeTooltip = null;
                }
            }

            function showFloatingTooltip(navParent) {
                const src = navParent.querySelector('.tooltip');
                if (!src) return;
                hideFloatingTooltip();
                const tip = document.createElement('div');
                tip.className = 'floating-tooltip';
                tip.textContent = src.textContent.trim();
                document.body.appendChild(tip);
                const r = navParent.getBoundingClientRect();
                tip.style.left = (r.right + 12) + 'px';
                tip.style.top = (r.top + r.height / 2 - tip.offsetHeight / 2) + 'px';
                requestAnimationFrame(() => tip.classList.add('show'));
                activeTooltip = tip;
            }

            sidebar.querySelectorAll('.nav-parent').forEach(np => {
                np.addEventListener('mouseenter', () => {
                    if (sidebar.classList.contains('sidebar-collapsed') && !isMobile()) {
                        showFloatingTooltip(np);
                    }
                });
                np.addEventListener('mouseleave', hideFloatingTooltip);
                np.addEventListener('click', hideFloatingTooltip);
            });

            document.querySelector('.nav-content')?.addEventListener('scroll', hideFloatingTooltip);


            let activeDropdown = null;

            function closeAllDropdowns() {
                if (activeDropdown) {
                    activeDropdown.remove();
                    activeDropdown = null;
                }
            }

            function showCollapsedDropdown(trigger, submenu) {
                if (activeDropdown) closeAllDropdowns();
                const rect = trigger.getBoundingClientRect();
                const items = submenu.querySelectorAll('.sub-item');
                if (!items.length) return;
                const drop = document.createElement('div');
                drop.className = 'collapsed-dropdown';
                items.forEach(it => {
                    const a = document.createElement('a');
                    a.href = it.href;
                    a.innerText = it.innerText;
                    if (it.classList.contains('active')) a.classList.add('active');
                    a.addEventListener('click', () => closeAllDropdowns());
                    drop.appendChild(a);
                });
                document.body.appendChild(drop);
                drop.style.left = (rect.right + 8) + 'px';
                drop.style.top = rect.top + 'px';
                requestAnimationFrame(() => drop.classList.add('show'));
                activeDropdown = drop;
                const closeHandler = (e) => {
                    if (!drop.contains(e.target) && e.target !== trigger) {
                        closeAllDropdowns();
                        document.removeEventListener('click', closeHandler);
                    }
                };
                setTimeout(() => document.addEventListener('click', closeHandler), 10);
            }

            const roleSw = document.getElementById('role-switcher');
            if (roleSw) roleSw.addEventListener('change', function() {
                fetch('{{ route('switch.role') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        role: this.value
                    })
                }).then(r => r.json()).then(d => {
                    if (d.success) window.location.reload();
                    else alert('Switch failed');
                });
            });
            const locSw = document.getElementById('location-switcher');
            if (locSw) locSw.addEventListener('change', function() {
                fetch('{{ route('switch.location') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        location: this.value
                    })
                }).then(r => r.json()).then(d => {
                    if (d.success) window.location.reload();
                    else alert(d.message || 'Switch failed');
                });
            });
        });
        document.getElementById('logout-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            if (!document.getElementById('logout-btn').dataset.clicked) {
                document.getElementById('logout-btn').dataset.clicked = 'true';
                document.getElementById('logout-form').submit();
            }
        });
    </script>
</body>

</html>
