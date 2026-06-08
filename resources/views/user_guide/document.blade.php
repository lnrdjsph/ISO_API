@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Facades\Auth;

        $userRole = Auth::user()->role ?? 'store personnel';

        /*
        |--------------------------------------------------------------------------
        | Role → guide views
        |--------------------------------------------------------------------------
        | Each "view token" is a single word (no spaces) so it can live in a
        | space-separated `data-roles` attribute and be matched in JS via
        | string.split(' ').includes(token).
        */
        $availableRoles = match ($userRole) {
            'super admin'         => ['personnel', 'manager', 'admin', 'whmanager', 'whpersonnel'],
            'store manager'       => ['manager'],
            'warehouse manager'   => ['whmanager'],
            'warehouse personnel' => ['whpersonnel'],
            default               => ['personnel'],
        };

        $defaultTab = match ($userRole) {
            'super admin'         => 'admin',
            'store manager'       => 'manager',
            'warehouse manager'   => 'whmanager',
            'warehouse personnel' => 'whpersonnel',
            default               => 'personnel',
        };

        // Metadata for the "Switch view" buttons (shown to super admins).
        $roleMeta = [
            'personnel'   => ['label' => 'Store Personnel',     'icon' => 'user'],
            'manager'     => ['label' => 'Manager',             'icon' => 'shield'],
            'admin'       => ['label' => 'Super Admin',         'icon' => 'cog'],
            'whmanager'   => ['label' => 'Warehouse Manager',   'icon' => 'truck'],
            'whpersonnel' => ['label' => 'Warehouse Personnel', 'icon' => 'truck'],
        ];
        $switchIcons = [
            'user'   => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
            'shield' => '<path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
            'cog'    => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />',
            'truck'  => '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7zM5.5 18.5a2 2 0 11-4 0 2 2 0 014 0zM20.5 18.5a2 2 0 11-4 0 2 2 0 014 0z" />',
        ];

        /*
        |--------------------------------------------------------------------------
        | Sidebar navigation (single source of truth)
        |--------------------------------------------------------------------------
        | Item shapes:
        |   ['label' => 'Section heading']
        |   ['href' => '#id', 'section' => 'id', 'icon' => 'grid', 'text' => '...']
        |   ['href' => '#id', 'text' => '...', 'sub' => true]
        */
        $sidebars = [
            'personnel' => [
                ['label' => 'Getting Started'],
                ['href' => '#dashboard', 'section' => 'dashboard', 'icon' => 'grid', 'text' => 'Dashboard'],
                ['label' => 'Order Management'],
                ['href' => '#sales-order-form', 'section' => 'sales-order-form', 'icon' => 'file', 'text' => 'Sales Order Form'],
                ['href' => '#sof-header', 'text' => 'Header Fields', 'sub' => true],
                ['href' => '#sof-items', 'text' => 'Order Items', 'sub' => true],
                ['href' => '#sof-submit', 'text' => 'Submitting', 'sub' => true],
                ['href' => '#orders-list', 'section' => 'orders-list', 'icon' => 'list', 'text' => 'Orders List'],
                ['href' => '#order-details', 'section' => 'order-details', 'icon' => 'info', 'text' => 'Order Details'],
                ['href' => '#od-actions', 'text' => 'Actions', 'sub' => true],
                ['href' => '#od-invoice', 'text' => 'Invoice & Print', 'sub' => true],
                ['label' => 'Catalog'],
                ['href' => '#products', 'section' => 'products', 'icon' => 'box', 'text' => 'Products'],
                ['label' => 'Reference'],
                ['href' => '#statuses', 'section' => 'statuses', 'icon' => 'check', 'text' => 'Statuses'],
                ['href' => '#workflows', 'section' => 'workflows', 'icon' => 'bolt', 'text' => 'Workflows'],
                ['href' => '#troubleshooting', 'section' => 'troubleshooting', 'icon' => 'help', 'text' => 'Troubleshooting'],
            ],
            'manager' => [
                ['label' => 'Getting Started'],
                ['href' => '#dashboard', 'section' => 'dashboard', 'icon' => 'grid', 'text' => 'Dashboard'],
                ['label' => 'Order Review'],
                ['href' => '#orders-list', 'section' => 'orders-list', 'icon' => 'list', 'text' => 'Orders List'],
                ['href' => '#order-details', 'section' => 'order-details', 'icon' => 'info', 'text' => 'Order Details'],
                ['href' => '#od-panels', 'text' => 'Info Panels', 'sub' => true],
                ['href' => '#od-actions', 'text' => 'Actions', 'sub' => true],
                ['href' => '#od-invoice', 'text' => 'Invoice & Print', 'sub' => true],
                ['label' => 'Approval'],
                ['href' => '#approving-orders', 'section' => 'approving-orders', 'icon' => 'shield', 'text' => 'Approve / Reject'],
                ['label' => 'Notifications'],
                ['href' => '#email-notifications', 'section' => 'email-notifications', 'icon' => 'mail', 'text' => 'Email Notifications'],
                ['label' => 'Reference'],
                ['href' => '#statuses', 'section' => 'statuses', 'icon' => 'check', 'text' => 'Statuses'],
            ],
            'admin' => [
                ['label' => 'Getting Started'],
                ['href' => '#dashboard', 'section' => 'dashboard', 'icon' => 'grid', 'text' => 'Dashboard'],
                ['label' => 'Orders'],
                ['href' => '#sales-order-form', 'section' => 'sales-order-form', 'icon' => 'file', 'text' => 'Sales Order Form'],
                ['href' => '#orders-list', 'section' => 'orders-list', 'icon' => 'list', 'text' => 'Orders List'],
                ['href' => '#order-details', 'section' => 'order-details', 'icon' => 'info', 'text' => 'Order Details'],
                ['label' => 'Administration'],
                ['href' => '#user-management', 'section' => 'user-management', 'icon' => 'users', 'text' => 'User Management'],
                ['href' => '#products', 'section' => 'products', 'icon' => 'box', 'text' => 'Products'],
                ['label' => 'Reference'],
                ['href' => '#statuses', 'section' => 'statuses', 'icon' => 'check', 'text' => 'Statuses'],
            ],
            'whmanager' => [
                ['label' => 'Getting Started'],
                ['href' => '#dashboard', 'section' => 'dashboard', 'icon' => 'grid', 'text' => 'Dashboard'],
                ['label' => 'Orders'],
                ['href' => '#orders-list', 'section' => 'orders-list', 'icon' => 'list', 'text' => 'Orders List'],
                ['href' => '#order-details', 'section' => 'order-details', 'icon' => 'info', 'text' => 'Order Details'],
                ['label' => 'Fulfilment'],
                ['href' => '#warehouse-fulfillment', 'section' => 'warehouse-fulfillment', 'icon' => 'truck', 'text' => 'Fulfilment Tracking'],
                ['label' => 'Catalog'],
                ['href' => '#products', 'section' => 'products', 'icon' => 'box', 'text' => 'Products'],
                ['label' => 'Reference'],
                ['href' => '#statuses', 'section' => 'statuses', 'icon' => 'check', 'text' => 'Statuses'],
            ],
        ];
        // Warehouse personnel uses the same map as warehouse manager.
        $sidebars['whpersonnel'] = $sidebars['whmanager'];

        $titles = [
            'personnel'   => 'Store Personnel User Guide',
            'manager'     => 'Manager User Guide',
            'admin'       => 'Super Admin User Guide',
            'whmanager'   => 'Warehouse Manager User Guide',
            'whpersonnel' => 'Warehouse Personnel User Guide',
        ];
    @endphp

    <style nonce="{{ $cspNonce ?? '' }}">
        [data-roles] { display: none; }
        [data-roles].role-visible { display: block; }

        .guide-nav-link.active { color: #1d4ed8; background: #eff6ff; font-weight: 600; }
        .guide-nav-sub.active { color: #1d4ed8; font-weight: 600; }

        .guide-sidebar-scroll { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .guide-sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .guide-sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        .screenshot-frame { cursor: pointer; transition: box-shadow .2s, transform .2s; }
        .screenshot-frame:hover { box-shadow: 0 8px 25px -5px rgba(0,0,0,.1); transform: translateY(-2px); }

        .lightbox-overlay { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 24px; pointer-events: none; opacity: 0; visibility: hidden; transition: opacity .3s; }
        .lightbox-overlay.active { pointer-events: auto; opacity: 1; visibility: visible; }
        .lightbox-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background .35s cubic-bezier(.32,.72,0,1); backdrop-filter: blur(0); }
        .lightbox-overlay.active .lightbox-backdrop { background: rgba(0,0,0,.75); backdrop-filter: blur(12px); }
        .lightbox-content { position: relative; max-width: 90vw; max-height: 85vh; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 25px 70px rgba(0,0,0,.25); transform: scale(.85); opacity: 0; transition: transform .4s cubic-bezier(.32,.72,0,1), opacity .3s; }
        .lightbox-overlay.active .lightbox-content { transform: scale(1); opacity: 1; }
        .lightbox-overlay.closing .lightbox-backdrop { background: rgba(0,0,0,0); backdrop-filter: blur(0); }
        .lightbox-overlay.closing .lightbox-content { transform: scale(.85); opacity: 0; }
        .lightbox-content img { display: block; max-width: 90vw; max-height: 80vh; width: auto; height: auto; }

        .step-row { transition: all .15s; }
        .step-row:hover { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.06); transform: translateX(4px); }

        .section-card { transition: box-shadow .2s, transform .2s; }
        .section-card:hover { box-shadow: 0 10px 25px -5px rgba(0,0,0,.08); transform: translateY(-1px); }

        @media (max-width:1024px) {
            .guide-sidebar-col { display: none !important; }
            .guide-sidebar-col.open { display: flex !important; position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,.4); backdrop-filter: blur(4px); padding: 2rem; align-items: flex-start; justify-content: center; }
            .guide-sidebar-col.open > div { max-height: calc(100vh - 4rem); }
            .guide-main { margin-left: 0 !important; }
        }
        @media (min-width:1025px) { .mobile-toggle { display: none !important; } }

        html, body { scroll-behavior: smooth; scroll-padding-top: 2rem; overflow-x: clip !important; }

        .field-row { display: flex; gap: 8px; padding: 8px 12px; border-radius: 8px; background: #f9fafb; }
        .field-row:nth-child(even) { background: #f0f4ff; }
        .field-label { font-weight: 600; color: #1e293b; min-width: 140px; flex-shrink: 0; font-size: .8125rem; }
        .field-desc { color: #4b5563; font-size: .8125rem; line-height: 1.5; }
    </style>

    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ═══ PAGE HEADER ═══ --}}
        <div class="mb-8 overflow-hidden rounded-2xl bg-gray-950 p-8 shadow-xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 id="role-title" class="text-2xl font-bold tracking-tight text-white sm:text-3xl">User Guide</h1>
                        <p class="mt-1 text-sm text-gray-300">ISO B2B2C Ordering Platform · v1.3 · June 2026</p>
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
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
            {{ count($availableRoles) > 1 ? 'Menu & Switch Role' : 'Navigation' }}
        </button>

        <div class="relative flex gap-6">

            {{-- ═══ SIDEBAR ═══ --}}
            <div class="guide-sidebar-col w-60 flex-shrink-0 self-stretch" id="guideSidebarCol"
                onclick="if(event.target===this)this.classList.remove('open')">
                <div class="sticky top-24 w-60 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100" style="max-height: calc(100vh - 7rem);">
                    @if (count($availableRoles) > 1)
                        <div class="border-b border-gray-100 px-3 py-3">
                            <p class="mb-2 px-2 text-[0.65rem] font-semibold uppercase tracking-wider text-gray-400">Switch view</p>
                            <div class="flex flex-col gap-0.5">
                                @foreach ($availableRoles as $token)
                                    @php $meta = $roleMeta[$token]; @endphp
                                    <button data-role="{{ $token }}"
                                        class="sidebar-role-btn {{ $defaultTab === $token ? 'active bg-blue-50 !text-blue-700 font-semibold' : '' }} flex items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-gray-600 transition-all hover:bg-gray-50">
                                        <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $switchIcons[$meta['icon']] !!}</svg>
                                        {{ $meta['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <nav class="guide-sidebar-scroll overflow-y-auto p-2" id="guideSidebar"
                        style="max-height: {{ count($availableRoles) > 1 ? 'calc(100vh - 16rem)' : 'calc(100vh - 8rem)' }};"></nav>
                </div>
            </div>

            {{-- ═══ MAIN CONTENT ═══ --}}
            <div class="guide-main min-w-0 flex-1">
                @include('user_guide.partials.dashboard')
                @include('user_guide.partials.sales-order-form')
                @include('user_guide.partials.orders-list')
                @include('user_guide.partials.order-details')
                @include('user_guide.partials.approving-orders')
                @include('user_guide.partials.email-notifications')
                @include('user_guide.partials.warehouse-fulfillment')
                @include('user_guide.partials.products')
                @include('user_guide.partials.statuses')
                @include('user_guide.partials.workflows')
                @include('user_guide.partials.user-management')
                @include('user_guide.partials.troubleshooting')

                <div class="pb-8 pt-4 text-center">
                    <p class="text-xs text-gray-400">ISO B2B2C Ordering System — User Guide — v1.3 — June 2026</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ LIGHTBOX ═══ --}}
    <div class="lightbox-overlay" id="lightboxOverlay">
        <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
        <div class="lightbox-content" id="lightboxContent">
            <button id="lightboxClose"
                class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow-md backdrop-blur transition hover:rotate-90 hover:bg-white">
                <svg width="12" height="12" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 1l12 12M13 1L1 13" /></svg>
            </button>
            <img id="lightboxImg" src="" alt="">
            <div class="border-t border-gray-100 bg-white px-4 py-2.5 text-center text-sm text-gray-500" id="lightboxCaption"></div>
        </div>
    </div>

    {{-- ═══ JS ═══ --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            const availableRoles = @json($availableRoles);
            const sidebarConfig  = @json($sidebars);
            const titles         = @json($titles);

            const icons = {
                grid:  '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
                file:  '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/>',
                list:  '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
                info:  '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
                box:   '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                check: '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                shield:'<path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                users: '<path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
                bolt:  '<path d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                help:  '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/>',
                mail:  '<path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/>',
                truck: '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/>',
            };

            let activeRole = @json($defaultTab);
            let observer = null;

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
                        a.className = 'guide-nav-link flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-all hover:bg-gray-50 hover:text-gray-900';
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
                    const on = b.dataset.role === role;
                    b.classList.toggle('active', on);
                    b.classList.toggle('bg-blue-50', on);
                    b.classList.toggle('!text-blue-700', on);
                    b.classList.toggle('font-semibold', on);
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
                const subs  = document.querySelectorAll('.guide-nav-sub');
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
                }, { rootMargin: '-80px 0px -60% 0px', threshold: 0.1 });
                sections.forEach(s => observer.observe(s));
            }

            if (availableRoles.length > 1) {
                document.querySelectorAll('.sidebar-role-btn').forEach(b => b.addEventListener('click', () => setActiveRole(b.dataset.role)));
            }
            document.addEventListener('click', e => {
                if (e.target.closest('.guide-nav-link,.guide-nav-sub')) document.getElementById('guideSidebarCol')?.classList.remove('open');
            });

            // ── Lightbox ──
            const ov = document.getElementById('lightboxOverlay'),
                  ct = document.getElementById('lightboxContent'),
                  li = document.getElementById('lightboxImg'),
                  lc = document.getElementById('lightboxCaption');
            document.addEventListener('click', e => {
                const f = e.target.closest('.screenshot-frame');
                if (!f) return;
                const img = f.querySelector('img'), cap = f.querySelector('p');
                if (!img) return;
                const r = img.getBoundingClientRect(),
                      cx = r.left + r.width / 2 - innerWidth / 2,
                      cy = r.top + r.height / 2 - innerHeight / 2;
                ct.style.transition = 'none';
                ct.style.transform = `translate(${cx}px,${cy}px) scale(0.4)`;
                ct.style.opacity = '0';
                li.src = img.src; li.alt = img.alt;
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
                    ct.style.transform = ''; ct.style.opacity = '';
                    li.src = '';
                    document.body.style.overflow = '';
                }, 350);
            }
            document.getElementById('lightboxClose').addEventListener('click', e => { e.stopPropagation(); closeLB(); });
            document.getElementById('lightboxBackdrop').addEventListener('click', closeLB);
            document.addEventListener('keydown', e => { if (e.key === 'Escape' && ov.classList.contains('active')) closeLB(); });

            setActiveRole(activeRole);
        });
    </script>
@endsection
