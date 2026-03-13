<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1" />
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}">
    <meta
        name="description"
        content="ISO B2B Ordering System">
    <meta
        name="theme-color"
        content="darkblue" />
    <title>ISO B2B2C Ordering System</title>

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('images/MarengEms_Logo.png') }}">
    @vite('resources/css/app.css')

    <style>
        /* Prevent horizontal scroll completely */
        html,
        body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* Remove all list styling */
        ul,
        li {
            list-style: none;
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        /* Sidebar width states with strict containment */
        .sidebar-expanded {
            width: 13rem;
            max-width: 13rem;
            min-width: 13rem;
        }

        .sidebar-collapsed {
            width: 4.75rem;
            max-width: 4.75rem;
            min-width: 4.75rem;
        }

        /* Sidebar container — truly fixed, out of document flow */
        #sidebar {
            transition: width 0.10s ease-in-out, min-width 0.10s ease-in-out, max-width 0.10s ease-in-out, transform 0.10s ease-in-out;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* zoom: 0.75; */
        }

        /* Topbar also scaled down */
        .topbar {
            /* zoom: 0.75; */
        }

        /* Main content offsets for zoomed sidebar width (13rem * 0.75 = 9.75rem, 4.5rem * 0.75 = 3.375rem) */
        .main-container {
            transition: margin-left 0.10s ease-in-out;
        }

        @media (min-width: 768px) {
            .sidebar-expanded~.main-container {
                margin-left: 13rem;
            }

            .sidebar-collapsed~.main-container {
                margin-left: 4.5rem;
            }
        }

        /* Animate text fade + slide */
        .nav-text,
        .logo-text {
            transition: opacity 0.05 ease, width 0.05 ease, margin 0.05 ease;
            opacity: 1;
            display: inline;
            overflow: hidden;
            white-space: nowrap;
        }

        .nav-item {
            transition: opacity 0.05 ease, transform 0.05 ease;
            opacity: 1;
            transform: translateX(0);
        }

        /* Navigation container */
        nav {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Hide text when collapsed — animatable, not display:none */
        .sidebar-collapsed .nav-text,
        .sidebar-collapsed .logo-text {
            opacity: 0;
            width: 0;
            margin-left: 0;
            padding: 0;
            overflow: hidden;
            display: none;
        }

        /* Ensure nav items don't overflow */
        .nav-item,
        .sub-item {
            overflow: hidden;
            white-space: nowrap;
        }

        /* Show tooltips */
        .tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 0.5rem;
            background-color: #1f2937;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 50;
        }

        /* Show tooltip on hover for all buttons */
        .nav-item:hover .tooltip,
        .logout-btn:hover .tooltip,
        .toggle-btn:hover .tooltip {
            opacity: 1;
        }

        /* Center content when collapsed with proper containment */
        .sidebar-collapsed .nav-item {
            justify-content: center;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .sidebar-collapsed .nav-item>div {
            justify-content: center;
        }

        /* Hide submenus when collapsed — animated */
        .submenu {
            transition: opacity 0..05s ease, max-height 0.10s ease;
            max-height: 20rem;
            opacity: 1;
            overflow: hidden;
        }

        .sidebar-collapsed .submenu {
            opacity: 0;
            max-height: 0;
            pointer-events: none;
        }

        /* Logo container in collapsed state */
        .sidebar-collapsed .flex.items-center.space-x-3 {
            justify-content: center;
            space-x: 0;
        }

        /* Active states */
        .nav-item.active {
            background: transparent;
            color: inherit;
            font-weight: 500;
        }

        .group.active {
            background-color: #EFF6FF;
            color: #1D4ED8;
            border-radius: 0.8rem;
            width: 11.5rem;
            transition: width 0.10s ease-in-out;
        }

        .sidebar-collapsed .group.active {
            width: auto;
        }

        /* Make SVG inherit the color */
        .nav-item .icon-wrapper svg {
            stroke: currentColor;
        }

        /* Child active = strong */
        .sub-item.active {
            color: rgb(29 78 216);
            font-weight: 600;
        }

        /* Toggle button rotation */
        .sidebar-collapsed .toggle-btn svg {
            transform: rotate(180deg);
        }

        /* ══════════════════════════════════════════════════
           FLYOUT MENUS — position:fixed to escape any container
           ══════════════════════════════════════════════════ */
        .flyout-menu {
            position: fixed;
            z-index: 99999;
            width: 14rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.10s ease;
            border-radius: 0.5rem;
            border: 1px solid rgb(229, 231, 235);
            background: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -4px rgba(0, 0, 0, .1);
            padding: 0.25rem 0;
        }

        .flyout-menu.flyout-visible {
            opacity: 1;
            pointer-events: auto;
        }

        .flyout-menu a {
            display: block;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            color: rgb(75 85 99);
            border-radius: 0.43rem;
            margin: 0 0.25rem;
        }

        .flyout-menu a:hover {
            background-color: rgb(239 246 255);
            color: rgb(37 99 235);
        }

        /* Active sub-item inside flyout */
        .flyout-menu a.active {
            color: rgb(29 78 216);
            font-weight: 500;
        }

        /* Flyout arrow pointing back to parent */
        .flyout-menu::before {
            content: "";
            position: absolute;
            top: 0.75rem;
            left: -6px;
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid white;
            z-index: 100000;
        }

        /* Optional: border around arrow (to match border-gray-200) */
        .flyout-menu::after {
            content: "";
            position: absolute;
            top: 0.75rem;
            left: -7px;
            width: 0;
            height: 0;
            border-top: 7px solid transparent;
            border-bottom: 7px solid transparent;
            border-right: 7px solid rgb(229, 231, 235);
            z-index: 99999;
        }

        /* Loading animation */
        @keyframes loading-shimmer {
            0% {
                background-position: -200px 0;
            }

            100% {
                background-position: 200px 0;
            }
        }

        .loading-bg {
            background: linear-gradient(90deg,
                    rgba(229, 231, 235, 0) 25%,
                    rgba(229, 231, 235, 0.6) 50%,
                    rgba(229, 231, 235, 0) 75%);
            background-size: 400px 100%;
            animation: loading-shimmer 1.2s linear 4;
        }

        /* Flex layout for nav content */
        .nav-content {
            flex: 1;
        }

        .nav-bottom {
            margin-top: auto;
        }

        /* ══════════════════════════════════════════════════
           MOBILE — off-canvas drawer replacing broken dual-menu
           ══════════════════════════════════════════════════ */
        @media (max-width: 767.98px) {

            /* Main content full width on mobile */
            .main-container {
                margin-left: 0 !important;
            }

            /* Sidebar becomes a slide-in drawer */
            #sidebar {
                position: fixed !important;
                top: 0;
                left: 0;
                bottom: 0;
                width: 16rem !important;
                min-width: 16rem !important;
                max-width: 16rem !important;
                z-index: 10001;
                /* above topbar so drawer is fully on top */
                background: white;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
                transform: translateX(-100%);
                /* transition inherited from base #sidebar rule */
            }

            #sidebar.mobile-open {
                transform: translateX(0);
            }

            /* Inner nav: override fixed to relative so it flows inside the drawer */
            #sidebar>nav {
                position: relative !important;
                height: 100%;
                overflow-y: auto;
            }

            /* Override the fixed bottom nav for mobile */
            #sidebar .nav-bottom {
                position: relative !important;
                bottom: auto !important;
            }

            /* Force-show all text in mobile drawer regardless of collapse state */
            #sidebar .nav-text,
            #sidebar .logo-text {
                opacity: 1 !important;
                width: auto !important;
                margin-left: 0.75rem !important;
                display: inline !important;
            }

            #sidebar .submenu {
                opacity: 1 !important;
                max-height: 20rem !important;
                pointer-events: auto !important;
            }

            #sidebar .nav-item {
                justify-content: flex-start !important;
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            /* Hide flyouts on mobile — submenus are visible inline */
            .flyout-menu {
                display: none !important;
            }

            /* Hide tooltips on mobile — text is always visible */
            .tooltip {
                display: none !important;
            }

            /* Hide desktop collapse toggle on mobile */
            #sidebarToggle {
                display: none !important;
            }
        }

        /* Mobile overlay */
        #mobile-overlay {
            position: fixed;
            inset: 0;
            z-index: 10000;
            /* between topbar and sidebar drawer */
            background: rgba(0, 0, 0, .5);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.10s ease;
        }

        #mobile-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body class="relative overflow-x-hidden bg-gray-100">

    <!-- Mobile overlay -->
    <div id="mobile-overlay"></div>

    <div class="topbar fixed left-0 right-0 top-0 z-[10000] flex h-8 items-center justify-between bg-gradient-to-r from-green-900 via-black to-blue-900 px-4 shadow-sm">
        <!-- Left: mobile hamburger -->
        <div class="flex items-center space-x-3">
            <button id="mobileMenuBtn" class="rounded p-1 text-white hover:bg-white/10 md:hidden" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Right -->
        @php
            $locationMap = [
                '4002' => 'F2 - Metro Wholesalemart Colon',
                '2010' => 'S10 - Metro Maasin',
                '2017' => 'S17 - Metro Tacloban',
                '2019' => 'S19 - Metro Bay-Bay',
                '3018' => 'F18 - Metro Alang-Alang',
                '3019' => 'F19 - Metro Hilongos',
                '2008' => 'S8 - Metro Toledo',
                '6012' => 'H8 - Super Metro Antipolo',
                '6009' => 'H9 - Super Metro Carcar',
                '6010' => 'H10 - Super Metro Bogo',
            ];

            $userLocation = Auth::user()->user_location ?? null;
            $fullLocation = $locationMap[$userLocation] ?? $userLocation;
        @endphp

        <div class="flex items-center space-x-4">
            <span class="text-sm text-white">
                Howdy, {{ Auth::user()->name }} ({{ $fullLocation }})
            </span>
        </div>
    </div>


    <aside
        id="sidebar"
        class="sidebar-expanded fixed z-[9999] flex flex-col bg-white shadow-xl">

        <nav class="general-sidebar fixed flex flex-col px-3 pb-4">
            <a
                href="{{ route('dashboard') }}"
                class="block">
                <div class="mt-8 w-auto flex-col items-center justify-center border-b border-gray-200 py-4">
                    <div class="flex items-center space-x-6">
                        <img
                            src="{{ asset('images/MarengEms_Logo.png') }}"
                            alt="Logo"
                            class="h-10 w-10 flex-shrink-0 rounded-lg">
                        <span class="logo-text text-lg font-bold text-gray-800">ISO B2B2C</span>
                    </div>
                </div>
            </a>

            <div class="nav-content">
                <ul class="space-y-1 pt-4">
                    <!-- Dashboard Group -->
                    <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }} group relative">
                        <a
                            href="{{ route('dashboard') }}"
                            class="nav-item {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-5 w-5 flex-shrink-0">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z" />
                            </svg>
                            <span class="nav-text ml-3">Dashboard</span>
                            <div class="tooltip">Dashboard</div>
                        </a>

                        <ul class="flyout-menu" data-flyout>
                            <li>
                                <a href="{{ route('dashboard') }}"
                                    class="sub-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    Dashboard
                                </a>
                            </li>
                        </ul>
                    </li>



                    <!-- Orders Group -->
                    <li class="{{ request()->routeIs('orders*') ? 'active' : '' }} group relative">
                        @if (request()->routeIs('orders*'))
                            <div class="nav-item {{ request()->routeIs('orders*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                                <span class="icon-wrapper">
                                    <svg
                                        class="h-5 w-5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                    </svg>
                                </span>
                                <span class="nav-text ml-3 font-medium">Orders</span>
                                <div class="tooltip">Orders</div>
                            </div>

                            <ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
                                <li>
                                    <a
                                        href="{{ route('orders.index') }}"
                                        class="sub-item {{ request()->routeIs('orders.index') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                        <span class="nav-text">Sales Order List</span>
                                    </a>
                                </li>
                                @if (preg_match('/orders\/\d+$/', request()->path()))
                                    <li>
                                        <a
                                            href="{{ url()->current() }}"
                                            class="sub-item active relative flex items-center rounded-lg px-3 py-2 text-sm">
                                            <span class="nav-text">Sales Order Details</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        @else
                            <a
                                href="{{ route('orders.index') }}"
                                class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                <svg
                                    class="h-5 w-5 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                </svg>
                                <span class="nav-text ml-3">Orders</span>
                                <div class="tooltip">Orders</div>
                            </a>
                        @endif

                        <ul class="flyout-menu" data-flyout>
                            <li>
                                <a href="{{ route('orders.index') }}"
                                    class="sub-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                                    Sales Order List
                                </a>
                            </li>
                            @if (preg_match('/orders\/\d+$/', request()->path()))
                                <li>
                                    <a href="{{ url()->current() }}"
                                        class="sub-item active">
                                        Sales Order Details
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>


                    <!-- Forms Group -->
                    @auth
                        @if (!in_array(auth()->user()->role, ['manager']))
                            <li class="{{ request()->routeIs('forms*') ? 'active' : '' }} group relative">
                                @if (request()->routeIs('forms*'))
                                    <div class="nav-item {{ request()->routeIs('forms*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                                        <span class="icon-wrapper">
                                            <svg
                                                class="h-5 w-5 flex-shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </span>

                                        <span class="nav-text ml-3 font-medium">Forms</span>
                                        <div class="tooltip">Forms</div>
                                    </div>

                                    <ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
                                        <li>
                                            <a
                                                href="{{ route('forms.sof') }}"
                                                class="sub-item {{ request()->routeIs('forms.sof') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                <span class="nav-text">Sales Order Form</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="{{ route('forms.rof') }}"
                                                class="sub-item {{ request()->routeIs('forms.rof') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                <span class="nav-text">Request Order Form</span>
                                            </a>
                                        </li>
                                    </ul>
                                @else
                                    <a
                                        href="{{ route('forms.sof') }}"
                                        class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                        <svg
                                            class="h-5 w-5 flex-shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="nav-text ml-3">Forms</span>
                                        <div class="tooltip">Forms</div>
                                    </a>
                                @endif

                                <ul class="flyout-menu" data-flyout>
                                    <li>
                                        <a href="{{ route('forms.sof') }}"
                                            class="sub-item {{ request()->routeIs('forms.sof') ? 'active' : '' }}">
                                            Sales Order Form
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('forms.rof') }}"
                                            class="sub-item {{ request()->routeIs('forms.rof') ? 'active' : '' }}">
                                            Request Order Form
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endauth


                    <!-- Reports Group -->
                    <li class="{{ request()->routeIs('reports*') ? 'active' : '' }} group relative">
                        @if (request()->routeIs('reports*'))
                            <div class="nav-item {{ request()->routeIs('reports*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                                <span class="icon-wrapper">
                                    <svg
                                        class="h-5 w-5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 20h16M4 4v16" />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 14l4-4 4 3 6-6 2 2" />
                                    </svg>
                                </span>

                                <span class="nav-text ml-3 font-medium">Reports</span>
                                <div class="tooltip">Reports</div>
                            </div>

                            <ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
                                <li>
                                    <a
                                        href="{{ route('reports.sales') }}"
                                        class="sub-item {{ request()->routeIs('reports.sales') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                        <span class="nav-text">Sales Overview</span>
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="{{ route('reports.orders') }}"
                                        class="sub-item {{ request()->routeIs('reports.orders') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                        <span class="nav-text">Orders Report</span>
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="{{ route('reports.payments') }}"
                                        class="sub-item {{ request()->routeIs('reports.payments') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                        <span class="nav-text">Mode of Payments</span>
                                    </a>
                                </li>
                            </ul>
                        @else
                            <a
                                href="{{ route('reports.sales') }}"
                                class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                <svg
                                    class="h-5 w-5 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 20h16M4 4v16" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 14l4-4 4 3 6-6 2 2" />
                                </svg>
                                <span class="nav-text ml-3">Reports</span>
                                <div class="tooltip">Reports</div>
                            </a>
                        @endif

                        <ul class="flyout-menu" data-flyout>
                            <li>
                                <a href="{{ route('reports.sales') }}"
                                    class="sub-item {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                                    Sales Overview
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('reports.orders') }}"
                                    class="sub-item {{ request()->routeIs('reports.orders') ? 'active' : '' }}">
                                    Orders Report
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('reports.payments') }}"
                                    class="sub-item {{ request()->routeIs('reports.payments') ? 'active' : '' }}">
                                    Mode of Payments
                                </a>
                            </li>
                        </ul>
                    </li>

                    @auth
                        @if (in_array(auth()->user()->role, ['super admin', 'warehouse personnel', 'warehouse admin', 'store personnel']))
                            <!-- Products Group -->
                            <li class="{{ request()->routeIs('products*') ? 'active' : '' }} group relative">
                                @if (request()->routeIs('products*'))
                                    <div class="nav-item {{ request()->routeIs('products*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                                        <span class="icon-wrapper">
                                            <svg
                                                class="h-5 w-5 flex-shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </span>
                                        <span class="nav-text ml-3 font-medium">Products</span>
                                        <div class="tooltip">Products</div>
                                    </div>

                                    <ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
                                        <li>
                                            <a
                                                href="{{ route('products.index') }}"
                                                class="sub-item {{ request()->routeIs('products.index') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                <span class="nav-text">Product List</span>
                                            </a>
                                        </li>
                                        @if (!in_array(auth()->user()->role, ['store personnel']))
                                            <li>
                                                <a
                                                    href="{{ route('products.create') }}"
                                                    class="sub-item {{ request()->routeIs('products.create') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                    <span class="nav-text">Add New Product</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a
                                                    href="{{ route('products.import.show') }}"
                                                    class="sub-item {{ request()->routeIs('products.import.show') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                    <span class="nav-text">Import CSV</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <a
                                        href="{{ route('products.index') }}"
                                        class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                        <svg
                                            class="h-5 w-5 flex-shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="nav-text ml-3">Products</span>
                                        <div class="tooltip">Products</div>
                                    </a>
                                @endif

                                <ul class="flyout-menu" data-flyout>
                                    <li>
                                        <a href="{{ route('products.index') }}"
                                            class="sub-item {{ request()->routeIs('products.index') ? 'active' : '' }}">
                                            Product List
                                        </a>
                                    </li>
                                    @if (!in_array(auth()->user()->role, ['store personnel']))
                                        <li>
                                            <a href="{{ route('products.create') }}"
                                                class="sub-item {{ request()->routeIs('products.create') ? 'active' : '' }}">
                                                Add New Product
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('products.import.show') }}"
                                                class="sub-item {{ request()->routeIs('products.import.show') ? 'active' : '' }}">
                                                Import CSV
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>


                        @endif
                    @endauth
                    <!-- User Guide - Add this as a new menu item -->
                    <li class="{{ request()->routeIs('user-guide*') ? 'active' : '' }} group relative">
                        <a href="{{ route('user-guide.document') }}"
                            class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="nav-text ml-3">User Guide</span>
                            <div class="tooltip">User Guide</div>
                        </a>

                        <!-- Flyout menu for sub-sections (optional) -->
                        <ul class="flyout-menu" data-flyout>
                            <li>
                                <a href="{{ route('user-guide.document') }}"
                                    class="sub-item {{ request()->routeIs('user-guide.document') ? 'active' : '' }}">
                                    User Guide
                                </a>
                            </li>
                        </ul>
                    </li>
                    @auth
                        @if (auth()->user()->role === 'super admin')
                            <!-- Users Group -->
                            <li class="{{ request()->routeIs('users*') ? 'active' : '' }} group relative">
                                @if (request()->routeIs('users*'))
                                    <div class="nav-item {{ request()->routeIs('users*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                                        <span class="icon-wrapper">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="h-5 w-5 flex-shrink-0">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                                <circle
                                                    cx="9"
                                                    cy="7"
                                                    r="4" />
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                            </svg>
                                        </span>
                                        <span class="nav-text ml-3">Users</span>
                                        <div class="tooltip">Users</div>
                                    </div>

                                    <ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
                                        <li>
                                            <a
                                                href="{{ route('users.index') }}"
                                                class="sub-item {{ request()->routeIs('users.index') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                <span class="nav-text">User List</span>
                                            </a>
                                        </li>
                                        @if (preg_match('/users\/\d+$/', request()->path()))
                                            <li>
                                                <a
                                                    href="{{ url()->current() }}"
                                                    class="sub-item active relative flex items-center rounded-lg px-3 py-2 text-sm">
                                                    <span class="nav-text">User Details</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <a
                                        href="{{ route('users.index') }}"
                                        class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="h-5 w-5 flex-shrink-0">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                                            <circle
                                                cx="9"
                                                cy="7"
                                                r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                        </svg>
                                        <span class="nav-text ml-3">Users</span>
                                        <div class="tooltip">Users</div>
                                    </a>

                                    <ul class="flyout-menu" data-flyout>
                                        <li>
                                            <a href="{{ route('users.index') }}"
                                                class="sub-item">
                                                User List
                                            </a>
                                        </li>
                                    </ul>
                                @endif
                            </li>



                            <!-- Others Group -->
                            <li class="{{ request()->routeIs('others.*') ? 'active' : '' }} group relative">
                                @if (request()->routeIs('others.*'))
                                    <div class="nav-item {{ request()->routeIs('others.*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
                                        <span class="icon-wrapper">
                                            <svg
                                                class="h-5 w-5 flex-shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                            </svg>
                                        </span>
                                        <span class="nav-text ml-3 font-medium">Others</span>
                                        <div class="tooltip">Others</div>
                                    </div>

                                    <ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
                                        <li>
                                            <a
                                                href="{{ route('others.inventory.form') }}"
                                                class="sub-item {{ request()->routeIs('others.inventory.form') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600">
                                                <span class="nav-text">Inventory Form</span>
                                            </a>
                                        </li>
                                    </ul>
                                @else
                                    <a
                                        href="{{ route('others.inventory.form') }}"
                                        class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                        <svg
                                            class="h-5 w-5 flex-shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                        </svg>
                                        <span class="nav-text ml-3">Others</span>
                                        <div class="tooltip">Others</div>
                                    </a>
                                @endif

                                <ul class="flyout-menu" data-flyout>
                                    <li>
                                        <a href="{{ route('others.inventory.form') }}"
                                            class="sub-item {{ request()->routeIs('others.inventory.form') ? 'active' : '' }}">
                                            Inventory Form
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endauth
                </ul>
            </div>

            <div class="nav-bottom fixed bottom-2 w-auto">
                <!-- Toggle Button -->
                <div class="pb-4">
                    <button
                        id="sidebarToggle"
                        class="toggle-btn relative flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                        <svg
                            class="h-5 w-5 flex-shrink-0 transition-transform duration-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="nav-text ml-3">Collapse Menu</span>
                        <div class="tooltip">Toggle Menu</div>
                    </button>
                </div>

                <!-- Logout -->
                <div class="border-t border-gray-200 pt-4">
                    <a
                        href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="logout-btn nav-item relative flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700">
                        <svg
                            class="h-5 w-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="nav-text ml-3">Logout</span>
                        <div class="tooltip">Logout</div>
                    </a>

                    <form
                        id="logout-form"
                        action="{{ route('logout') }}"
                        method="POST"
                        class="hidden">
                        @csrf
                    </form>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-container min-h-screen" style="padding-top: 1.5rem;">
        <main class="min-h-screen w-full bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-6">
            @yield('content')
        </main>
    </div>

    @vite('resources/js/app.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('mobile-overlay');
            const mobileBtn = document.getElementById('mobileMenuBtn');

            // ── Helpers ──
            function isMobile() {
                return window.innerWidth < 768;
            }

            function openMobileDrawer() {
                sidebar.classList.add('mobile-open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileDrawer() {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Initialize sidebar state from localStorage or default to expanded
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && sidebar) {
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
            }

            // Update main container width when sidebar toggles
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    const isCurrentlyCollapsed = sidebar.classList.contains('sidebar-collapsed');

                    if (isCurrentlyCollapsed) {
                        sidebar.classList.remove('sidebar-collapsed');
                        sidebar.classList.add('sidebar-expanded');
                        localStorage.setItem('sidebarCollapsed', 'false');
                    } else {
                        sidebar.classList.remove('sidebar-expanded');
                        sidebar.classList.add('sidebar-collapsed');
                        localStorage.setItem('sidebarCollapsed', 'true');
                    }
                });
            }

            // Mobile: hamburger opens sidebar as drawer
            if (mobileBtn) {
                mobileBtn.addEventListener('click', function() {
                    if (sidebar.classList.contains('mobile-open')) {
                        closeMobileDrawer();
                    } else {
                        openMobileDrawer();
                    }
                });
            }

            // Mobile: tap overlay to close
            if (overlay) {
                overlay.addEventListener('click', closeMobileDrawer);
            }

            // Close drawer on nav link click (mobile only)
            sidebar.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (isMobile()) closeMobileDrawer();
                });
            });

            // ══════════════════════════════════════════════
            // FLYOUT POSITIONING — position:fixed, tooltip-style
            // Escapes sidebar container, works with zoom:0.75
            // ══════════════════════════════════════════════
            document.querySelectorAll('.group').forEach(function(group) {
                var flyout = group.querySelector('[data-flyout]');
                if (!flyout) return;

                var hideTimer = null;

                function showFlyout() {
                    if (isMobile()) return;
                    // Don't show flyout on active parent when expanded — it already has inline submenu
                    if (group.classList.contains('active') && sidebar.classList.contains('sidebar-expanded')) return;
                    clearTimeout(hideTimer);

                    // Hide all other flyouts first
                    document.querySelectorAll('.flyout-menu.flyout-visible').forEach(function(f) {
                        if (f !== flyout) f.classList.remove('flyout-visible');
                    });

                    // Calculate position accounting for sidebar zoom
                    var sidebarZoom = parseFloat(getComputedStyle(sidebar).zoom) || 1;
                    var sidebarRect = sidebar.getBoundingClientRect();
                    var groupRect = group.getBoundingClientRect();

                    // sidebarRect is in viewport coords (zoomed). Flyout is inside sidebar (inherits zoom),
                    // so CSS values need to be divided by zoom to land at the right viewport position.
                    flyout.style.left = ((sidebarRect.right - 16) / sidebarZoom) + 'px';
                    flyout.style.top = (groupRect.top / sidebarZoom) + 'px';
                    flyout.classList.add('flyout-visible');

                    // Clamp to viewport bottom
                    requestAnimationFrame(function() {
                        var flyoutRect = flyout.getBoundingClientRect();
                        var viewportH = window.innerHeight;
                        if (flyoutRect.bottom > viewportH) {
                            flyout.style.top = (((viewportH - flyoutRect.height - 8)) / sidebarZoom) + 'px';
                        }
                    });
                }

                function scheduleHide() {
                    hideTimer = setTimeout(function() {
                        flyout.classList.remove('flyout-visible');
                    }, 120);
                }

                function cancelHide() {
                    clearTimeout(hideTimer);
                }

                group.addEventListener('mouseenter', showFlyout);
                group.addEventListener('mouseleave', scheduleHide);
                flyout.addEventListener('mouseenter', cancelHide);
                flyout.addEventListener('mouseleave', scheduleHide);
            });

            // Loading animation for navigation links
            document.querySelectorAll(".general-sidebar a").forEach(link => {
                link.addEventListener("click", function() {
                    this.classList.add("loading-bg");
                    setTimeout(() => {
                        this.classList.remove("loading-bg");
                    }, 5000);
                });
            });

            // Handle page expired
            if (document.body.innerText.includes('Page Expired')) {
                window.location.reload();
            }

            // Handle escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileDrawer();
                }
            });

            // Responsive behavior
            function handleResize() {
                if (window.innerWidth >= 768) {
                    // Desktop: ensure sidebar is visible and drawer state is cleared
                    sidebar.classList.remove('mobile-open');
                    sidebar.classList.remove('hidden');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
                // On mobile: sidebar is off-screen via translateX(-100%), no need for 'hidden'
            }

            window.addEventListener('resize', handleResize);
            handleResize(); // Initial call

            // Keyboard shortcut - Alt + S to toggle sidebar
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 's') {
                    e.preventDefault();
                    if (!isMobile() && sidebarToggle) {
                        sidebarToggle.click();
                    }
                }
            });
        });

        // Session expiry check
        document.addEventListener('DOMContentLoaded', function() {
            let swalOpen = false;

            setInterval(() => {
                fetch("{{ route('check.session') }}")
                    .then(response => response.json())
                    .then(data => {
                        if (!data.authenticated && !swalOpen) {
                            swalOpen = true;
                            Swal.fire({
                                title: 'Session Expired',
                                text: 'Your session has expired. Please re-login to continue.',
                                icon: 'warning',
                                confirmButtonText: 'Re-login',
                                confirmButtonColor: '#dc2626',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                didClose: () => swalOpen = false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "{{ route('logout') }}";
                                }
                            });
                        } else if (data.authenticated && swalOpen) {
                            Swal.close();
                            swalOpen = false;
                        }
                    })
                    .catch(console.error);
            }, 30000);
        });
    </script>
</body>

</html>
