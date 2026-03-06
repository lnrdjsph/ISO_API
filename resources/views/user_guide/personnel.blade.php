@extends('layouts.app')

@section('content')
    <style>
        /* ── Fixed Docs Sidebar ── */
        .guide-sidebar {
            position: fixed;
            top: 8rem;
            width: 220px;
            max-height: calc(100vh - 10rem);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }

        .guide-sidebar::-webkit-scrollbar {
            width: 3px;
        }

        .guide-sidebar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }

        .guide-nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #6b7280;
            border-radius: 6px;
            transition: all 0.15s;
            text-decoration: none;
        }

        .guide-nav-link:hover {
            color: #1f2937;
            background: #f3f4f6;
        }

        .guide-nav-link.active {
            color: #1d4ed8;
            background: #eff6ff;
            font-weight: 600;
        }

        .guide-nav-sub {
            display: block;
            padding: 4px 10px 4px 34px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #9ca3af;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.15s;
        }

        .guide-nav-sub:hover {
            color: #374151;
            background: #f9fafb;
        }

        .guide-nav-sub.active {
            color: #1d4ed8;
            font-weight: 600;
        }

        /* ── Screenshot frame ── */
        .screenshot-frame {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #f9fafb;
            margin: 14px 0 18px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            cursor: pointer;
            transition: box-shadow 0.2s;
        }

        .screenshot-frame:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .screenshot-frame .screenshot-img-wrap {
            height: 280px;
            overflow: hidden;
        }

        .screenshot-frame.compact .screenshot-img-wrap {
            height: 160px;
        }

        .screenshot-frame img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }

        .screenshot-caption {
            font-size: 0.75rem;
            font-weight: 500;
            color: #9ca3af;
            padding: 6px 12px;
            background: #f3f4f6;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-style: italic;
        }

        /* ── Image Lightbox Modal (iOS-style) ── */
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
            -webkit-backdrop-filter: blur(0px);
            backdrop-filter: blur(0px);
        }

        .lightbox-overlay.active .lightbox-backdrop {
            background: rgba(0, 0, 0, 0.6);
            -webkit-backdrop-filter: blur(12px);
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
            transition: transform 0.4s cubic-bezier(0.32, 0.72, 0, 1),
                opacity 0.3s cubic-bezier(0.32, 0.72, 0, 1);
        }

        .lightbox-overlay.active .lightbox-content {
            transform: scale(1);
            opacity: 1;
        }

        .lightbox-overlay.closing .lightbox-backdrop {
            background: rgba(0, 0, 0, 0);
            -webkit-backdrop-filter: blur(0px);
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

        .lightbox-caption {
            padding: 10px 16px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #6b7280;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .lightbox-close {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transition: background 0.15s;
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }

        .lightbox-close:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        /* ── Callouts ── */
        .guide-callout {
            display: flex;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            margin: 12px 0 16px;
            font-size: 0.8rem;
            line-height: 1.6;
        }

        .guide-callout.tip {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .guide-callout.warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .guide-callout.important {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .guide-callout.info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }

        /* ── Steps ── */
        .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            background: #2563eb;
            color: #fff;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ── Scroll offset ── */
        .guide-section {
            scroll-margin-top: 100px;
        }

        html {
            scroll-behavior: smooth;
        }

        /* ── Mobile sidebar ── */
        @media (max-width: 1024px) {
            .guide-sidebar-col {
                display: none;
            }

            .guide-sidebar-col.open {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 50;
                background: rgba(0, 0, 0, 0.3);
                padding: 5rem 1rem 1rem;
            }

            .guide-sidebar-col.open .guide-sidebar {
                position: relative;
                top: 0;
                max-height: calc(100vh - 6rem);
                background: #fff;
                border-radius: 10px;
                padding: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
                width: 100%;
                max-width: 280px;
            }

            .guide-main-col {
                margin-left: 0 !important;
            }
        }

        @media (min-width: 1025px) {
            .guide-mobile-toggle {
                display: none;
            }
        }
    </style>

    <div class="">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ═══ PAGE HEADER ═══ --}}
            <div class="mb-8">
                <div class="flex items-center space-x-4">
                    <div class="rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 p-3 shadow-lg">
                        <svg class="h-7 w-7 flex-shrink-0" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Store Personnel User Guide</h1>
                        <p class="mt-1 text-gray-600">Quick-start guide for the ISO B2B2C ordering platform — Version 1.1 · February 2026</p>
                    </div>
                </div>
            </div>

            {{-- Mobile sidebar toggle --}}
            <div class="guide-mobile-toggle mb-4">
                <button onclick="document.getElementById('guideSidebarCol').classList.toggle('open')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Navigate Guide
                </button>
            </div>

            {{-- ═══ LAYOUT: SIDEBAR + CONTENT ═══ --}}
            <div class="relative">

                {{-- ─── Fixed Sidebar ─── --}}
                <div class="guide-sidebar-col" id="guideSidebarCol" onclick="if(event.target===this)this.classList.remove('open')">
                    <nav class="guide-sidebar">
                        <div class="mb-2 text-[0.65rem] font-bold uppercase tracking-wider text-gray-400">Getting Started</div>
                        <a href="#dashboard" class="guide-nav-link active" data-section="dashboard">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                                <rect x="14" y="14" width="7" height="7" rx="1" />
                            </svg>
                            Dashboard
                        </a>

                        <div class="mb-2 mt-4 text-[0.65rem] font-bold uppercase tracking-wider text-gray-400">Order Management</div>
                        <a href="#sales-order-form" class="guide-nav-link" data-section="sales-order-form">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                                <path d="M14 2v6h6" />
                            </svg>
                            Sales Order Form
                        </a>
                        <a href="#sof-header" class="guide-nav-sub">Header Fields</a>
                        <a href="#sof-items" class="guide-nav-sub">Order Items</a>
                        <a href="#sof-submit" class="guide-nav-sub">Submitting</a>

                        <a href="#orders-list" class="guide-nav-link" data-section="orders-list">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                            </svg>
                            Orders List
                        </a>
                        <a href="#orders-filters" class="guide-nav-sub">Filtering &amp; Search</a>
                        <a href="#orders-table" class="guide-nav-sub">Order Table</a>

                        <a href="#order-details" class="guide-nav-link" data-section="order-details">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 16v-4M12 8h.01" />
                            </svg>
                            Order Details
                        </a>
                        <a href="#od-panels" class="guide-nav-sub">Info Panels</a>
                        <a href="#od-actions" class="guide-nav-sub">Actions &amp; Printing</a>
                        <a href="#od-invoice" class="guide-nav-sub">Invoice Summary</a>

                        <div class="mb-2 mt-4 text-[0.65rem] font-bold uppercase tracking-wider text-gray-400">Catalog</div>
                        <a href="#products" class="guide-nav-link" data-section="products">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Products Page
                        </a>

                        <div class="mb-2 mt-4 text-[0.65rem] font-bold uppercase tracking-wider text-gray-400">Reference</div>
                        <a href="#statuses" class="guide-nav-link" data-section="statuses">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Status Reference
                        </a>
                        <a href="#workflows" class="guide-nav-link" data-section="workflows">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Common Workflows
                        </a>
                        <a href="#troubleshooting" class="guide-nav-link" data-section="troubleshooting">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01" />
                            </svg>
                            Troubleshooting
                        </a>
                    </nav>
                </div>

                {{-- ─── Main Content ─── --}}
                <div class="guide-main-col" style="margin-left: 240px;">


                    {{-- ═══════════ 1. DASHBOARD ═══════════ --}}
                    <section class="guide-section" id="dashboard">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">1</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Dashboard</h2>
                            </div>
                            <p class="text-sm leading-relaxed text-gray-600">
                                The Dashboard is your home screen. It provides a real-time snapshot of order activity, revenue, and store performance the moment you log in.
                            </p>

                            <div class="screenshot-frame">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/dashboard-full.png') }}"
                                        alt="Dashboard overview with KPI cards, order distribution, and recent activity"></div>
                                <div class="screenshot-caption">Dashboard — Full overview with KPI cards, order distribution, and recent activity</div>
                            </div>

                            <h3 class="mt-5 text-base font-semibold text-gray-800">What You See at a Glance</h3>
                            <p class="mt-1 text-sm text-gray-600">Color-coded badges on the <strong>Orders</strong> card show real-time counts:</p>

                            <div class="screenshot-frame compact">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/kpi-cards.png') }}" alt="KPI cards showing order counts by status"></div>
                                <div class="screenshot-caption">KPI cards showing order counts by status</div>
                            </div>

                            <div class="mt-3 space-y-2">
                                @foreach ([['New Orders', 'bg-blue-500', 'Orders waiting to be processed'], ['Pending', 'bg-yellow-500', 'Orders in progress'], ['For Approval', 'bg-purple-500', 'Orders needing manager review']] as $badge)
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                                            <span class="{{ $badge[1] }} inline-block h-2 w-2 rounded-full"></span> {{ $badge[0] }}
                                        </span>
                                        <span class="text-sm text-gray-600">{{ $badge[2] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-4 text-sm text-gray-600">Click any <strong>Orders</strong> link to jump straight to the full Orders List.</p>

                            <h3 class="mt-5 text-base font-semibold text-gray-800">Main Sections</h3>
                            <div class="mt-2 space-y-2">
                                @foreach ([['Orders Card', 'Quick access to Sales Orders and Request Orders, with status badges showing pending items.'], ['Forms Card', 'Direct links to Sales Order Form and Request Order Form (for non-manager users).'], ['Products Card', 'Manage your product catalog, add new products, or import via CSV.'], ['Reports Card', 'One-click access to Sales Overview, Orders Report, and Payments Report.']] as $card)
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1.5 inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-500"></span>
                                        <p class="text-sm text-gray-600"><strong class="text-gray-800">{{ $card[0] }}</strong> — {{ $card[1] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="guide-callout tip mt-4">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>The Dashboard is your command center — use it as your first stop each day to check pending work and navigate to key tasks.</span>
                            </div>
                        </div>
                    </section>


                    {{-- ═══════════ 2. SALES ORDER FORM ═══════════ --}}
                    <section class="guide-section mt-6" id="sales-order-form">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">2</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Sales Order Form (SOF)</h2>
                            </div>
                            <p class="text-sm leading-relaxed text-gray-600">
                                This is where you create new B2B sales orders. The form is divided into header sections (auto-filled and editable fields) and a dynamic order items area.
                            </p>

                            {{-- Step 1: Header --}}
                            <h3 class="mt-6 text-base font-semibold text-gray-800" id="sof-header">Step 1 — Fill in Order Information</h3>

                            <div class="screenshot-frame">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-header.png') }}"
                                        alt="Header area with Request Details, Customer Info, Payment, and Dispatch"></div>
                                <div class="screenshot-caption">Header area — Request Details, Customer Info, Payment, and Dispatch</div>
                            </div>

                            <h4 class="mt-4 text-sm font-bold text-gray-700">Request Details</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                <strong>SOF Order ID</strong>, <strong>Requesting Store</strong>, and <strong>Requested By</strong> are pre-filled and read-only (shown with a light-blue
                                background). You need to select the <strong>Channel of Order</strong> and confirm the <strong>Date &amp; Time of Order</strong>.
                            </p>

                            <h4 class="mt-4 text-sm font-bold text-gray-700">Customer Information</h4>
                            <div class="screenshot-frame compact">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-customer-fields.png') }}" alt="Customer fields with MBC Card auto-fill"></div>
                                <div class="screenshot-caption">Customer fields — MBC Card triggers auto-fill</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">
                                Type the 16-digit <strong>MBC Card Number</strong> and the system auto-fills Customer Name, Contact Number, and Email. If the card is not found, you will see an
                                error popup and can enter details manually.
                            </p>
                            <div class="guide-callout warning">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-2a1 1 0 01-1-1V8a1 1 0 112 0v2a1 1 0 01-1 1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Make sure the MBC Card Number is exactly <strong>16 digits</strong>. An incomplete number will trigger a warning.</span>
                            </div>

                            <h4 class="mt-4 text-sm font-bold text-gray-700">Payment Information</h4>
                            <div class="screenshot-frame compact">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-payment.png') }}" alt="Payment Center, Mode of Payment, and Payment Date"></div>
                                <div class="screenshot-caption">Payment Center (auto-filled), Mode of Payment, and Payment Date</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">
                                <strong>Payment Center</strong> is pre-filled based on your store. Select the <strong>Mode of Payment</strong> (PO15% or Cash / Bank Card) and verify the
                                <strong>Payment Date</strong>.
                            </p>

                            <h4 class="mt-4 text-sm font-bold text-gray-700">Dispatch Details</h4>
                            <div class="screenshot-frame compact">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-dispatch.png') }}" alt="Delivery date and dispatch mode selection"></div>
                                <div class="screenshot-caption">Delivery date and dispatch mode selection</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">
                                Choose a <strong>Delivery/Pick-up Date</strong> and a <strong>Mode of Dispatching</strong>. If you select <em>"Delivery Direct to Customer"</em>, additional
                                Address and Landmark fields will appear.
                            </p>
                            <div class="guide-callout important">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>The Order Items section only appears after <strong>all required header fields</strong> are completed. If you don't see it, check for any missing fields
                                    above.</span>
                            </div>

                            {{-- Step 2: Items --}}
                            <div class="mt-6 border-t border-gray-100 pt-5">
                                <h3 class="text-base font-semibold text-gray-800" id="sof-items">Step 2 — Add Order Items</h3>
                                <div class="screenshot-frame">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-order-items.png') }}"
                                            alt="Order Items section with product search, quantities, and real-time breakdown"></div>
                                    <div class="screenshot-caption">Order Items section — product search, quantities, and real-time breakdown</div>
                                </div>

                                <h4 class="mt-3 text-sm font-bold text-gray-700">Adding a Product</h4>
                                <div class="mt-2 space-y-2.5">
                                    @foreach (['Select a <strong>Sale Type</strong> (Freebie or Discount).', 'Search for the product by typing a SKU or description in the <strong>Main Product</strong> field. Select from the dropdown results.', '<strong>Price per Piece</strong> and <strong>Pieces per Case</strong> auto-fill from the product catalog. Adjust <strong>Case/s Ordered</strong> to set the quantity.'] as $step)
                                        <div class="flex items-start gap-3">
                                            <span class="step-num">{{ $loop->iteration }}</span>
                                            <p class="text-sm text-gray-600">{!! $step !!}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <h4 class="mt-4 text-sm font-bold text-gray-700">Item Breakdown Panel</h4>
                                <div class="screenshot-frame compact">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-breakdown.png') }}" alt="Real-time price and quantity breakdown per item"></div>
                                    <div class="screenshot-caption">Real-time price and quantity breakdown per item</div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    The right-side breakdown updates live as you change quantities or pricing. It shows the Price per case, any Freebies, Total Cases, Freebie Amount, and Total
                                    Payable.
                                </p>
                            </div>

                            {{-- Submit --}}
                            <div class="mt-6 border-t border-gray-100 pt-5" id="sof-submit">
                                <h3 class="text-base font-semibold text-gray-800">Submitting the Order</h3>
                                <div class="screenshot-frame compact">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/sof-submit-buttons.png') }}" alt="Add Another Item and Submit Order buttons"></div>
                                    <div class="screenshot-caption">Add Another Item and Submit Order buttons</div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    Use <strong>+ Add Another Item</strong> to add more products. When finished, click <strong>Submit Order</strong>. The button will show a loading state to
                                    prevent double submission.
                                </p>
                            </div>
                        </div>
                    </section>


                    {{-- ═══════════ 3. ORDERS LIST ═══════════ --}}
                    <section class="guide-section mt-6" id="orders-list">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">3</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Orders List</h2>
                            </div>
                            <p class="text-sm leading-relaxed text-gray-600">Your central view for tracking all submitted sales orders. Use filters to quickly find what you need.</p>

                            <h3 class="mt-5 text-base font-semibold text-gray-800" id="orders-filters">Filtering &amp; Searching</h3>
                            <div class="screenshot-frame compact">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/orders-filter-bar.png') }}"
                                        alt="Filter bar with search, store, channel, status, and date range"></div>
                                <div class="screenshot-caption">Filter bar — search, store, channel, status, and date range</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">
                                The filter bar lets you narrow results by <strong>keyword search</strong> (customer name, SOF ID), <strong>store</strong>, <strong>channel</strong>,
                                <strong>status</strong>, and <strong>date range</strong>. Click <strong>Apply</strong> to update results, or <strong>Reset</strong> to clear all filters.
                            </p>

                            <div class="mt-6 border-t border-gray-100 pt-5">
                                <h3 class="text-base font-semibold text-gray-800" id="orders-table">Order Table</h3>
                                <div class="screenshot-frame">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/orders-table.png') }}" alt="Orders table with status badges and channel indicators">
                                    </div>
                                    <div class="screenshot-caption">Orders table with status badges and channel indicators</div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">Each row shows the SOF ID, customer name, store, channel, status, and key dates. Click any row to open the Order Details
                                    page. Orders are color-coded by status for quick visual scanning.</p>
                            </div>

                            <div class="guide-callout tip mt-4">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>The table updates via AJAX — filters and pagination don't reload the entire page, keeping your workflow fast.</span>
                            </div>
                        </div>
                    </section>


                    {{-- ═══════════ 4. ORDER DETAILS ═══════════ --}}
                    <section class="guide-section mt-6" id="order-details">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">4</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Order Details</h2>
                            </div>
                            <p class="text-sm leading-relaxed text-gray-600">Click any order from the Orders List to view its complete details. This page lets you review, edit, and take action
                                on individual orders.</p>

                            <h3 class="mt-5 text-base font-semibold text-gray-800" id="od-panels">Information Panels</h3>
                            <div class="screenshot-frame">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/od-info-panels.png') }}"
                                        alt="Four info panels: Customer, Payment, Delivery, and Order information"></div>
                                <div class="screenshot-caption">Four info panels: Customer, Payment, Delivery, and Order information</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">The top area is split into four panels. Fields with editable data can be updated directly inline. Read-only fields show
                                system-assigned values like SOF ID, Order Status, and Warehouse.</p>

                            <h4 class="mt-4 text-sm font-bold text-gray-700">Ordered Items Table</h4>
                            <div class="screenshot-frame">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/od-items-table.png') }}"
                                        alt="Items table with SKU, pricing, quantities, and item-level status"></div>
                                <div class="screenshot-caption">Items table with SKU, pricing, quantities, and item-level status</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600"><strong>Editable cells</strong> (like Qty/CS, Price/PC) highlight yellow when changed. Use checkboxes to select items for bulk
                                cancellation.</p>

                            {{-- Actions --}}
                            <div class="mt-6 border-t border-gray-100 pt-5" id="od-actions">
                                <h3 class="text-base font-semibold text-gray-800">Sidebar Actions</h3>
                                <div class="screenshot-frame compact">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/od-sidebar-actions.png') }}"
                                            alt="Right sidebar with Order Actions, Print options, and Notes"></div>
                                    <div class="screenshot-caption">Right sidebar — Order Actions, Print options, and Notes</div>
                                </div>

                                <h4 class="mt-3 text-sm font-bold text-gray-700">Order Actions</h4>
                                <p class="mt-1 text-sm text-gray-600">The <strong>Order Actions</strong> dropdown lets you change the order lifecycle. Available actions depend on current
                                    status:</p>
                                <div class="mt-2 space-y-1.5">
                                    @foreach ([['Request For Approval', 'Sends the order to a manager for review.'], ['Cancel Order', 'Cancels the entire order (requires a reason).'], ['Complete Order', 'Marks an approved order as fulfilled.'], ['Restore Order', 'Re-opens a cancelled order.']] as $action)
                                        <div class="flex items-start gap-2">
                                            <span class="mt-1.5 inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-500"></span>
                                            <p class="text-sm text-gray-600"><strong class="text-gray-800">{{ $action[0] }}</strong> — {{ $action[1] }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <h4 class="mt-4 text-sm font-bold text-gray-700">Print Buttons</h4>
                                <div class="screenshot-frame compact">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/od-print-buttons.png') }}" alt="Print SOF, Invoice, Freebies Form, and Order Slip">
                                    </div>
                                    <div class="screenshot-caption">Print SOF, Invoice, Freebies Form, and Order Slip</div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">Quick-access buttons to generate printable PDFs for the Sales Order Form, Invoice, Freebies Form, and Order Slip.</p>

                                <h4 class="mt-4 text-sm font-bold text-gray-700">Order Notes</h4>
                                <div class="screenshot-frame compact">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/od-order-notes.png') }}" alt="Timeline of order notes and status changes"></div>
                                    <div class="screenshot-caption">Timeline of order notes and status changes</div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">A scrollable log of all actions taken on the order, including who made changes and when. Useful for auditing and
                                    communication.</p>
                            </div>

                            {{-- Invoice --}}
                            <div class="mt-6 border-t border-gray-100 pt-5" id="od-invoice">
                                <h3 class="text-base font-semibold text-gray-800">Invoice Summary</h3>
                                <div class="screenshot-frame compact">
                                    <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/od-invoice.png') }}"
                                            alt="Invoice panel showing Grand Total, Freebies, and Payable amount"></div>
                                    <div class="screenshot-caption">Invoice panel showing Grand Total, Freebies, and Payable amount</div>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">The invoice sidebar recalculates as items are edited. It breaks down the Grand Total, any Freebie or Discount amounts, and
                                    the final Total Payable.</p>

                                <div class="guide-callout tip mt-4">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>When you edit fields on this page, the <strong>Update</strong> button activates and shows how many fields you have changed. Don't forget to click Update
                                        to save.</span>
                                </div>
                            </div>
                        </div>
                    </section>


                    {{-- ═══════════ 5. PRODUCTS ═══════════ --}}
                    <section class="guide-section mt-6" id="products">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">5</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Products Page</h2>
                            </div>
                            <p class="text-sm leading-relaxed text-gray-600">Your complete inventory catalog. Use search to quickly find products — filtering by warehouse/depot is restricted to
                                authorized personnel only.</p>

                            <div class="screenshot-frame compact">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/products-search.png') }}" alt="Search bar with keyword search and depot selector"></div>
                                <div class="screenshot-caption">Search bar — keyword search and depot selector</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">Find products by <strong>SKU</strong>, <strong>product description</strong>, or <strong>sub-department</strong>. Results update
                                as you type.</p>

                            <div class="guide-callout info mt-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>The depot selector is <strong>not available for store personnel</strong> — warehouse filtering is restricted to authorized users only.</span>
                            </div>

                            <div class="screenshot-frame">
                                <div class="screenshot-img-wrap"><img src="{{ asset('images/guide/products-table.png') }}" alt="Products table with inventory levels and pricing details">
                                </div>
                                <div class="screenshot-caption">Products table with inventory levels and pricing details</div>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">All data is <strong>view-only</strong> — no editing capabilities. Contact admin for inventory changes.</p>
                        </div>
                    </section>


                    {{-- ═══════════ 6. STATUS REFERENCE ═══════════ --}}
                    <section class="guide-section mt-6" id="statuses">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">6</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Quick Reference</h2>
                            </div>

                            <h3 class="text-base font-semibold text-gray-800">Order Statuses</h3>
                            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Meaning</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">What to Do</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ([['New Order', 'bg-blue-500', 'text-blue-700', 'Just submitted, not yet processed', 'Review and request approval'], ['Pending', 'bg-yellow-500', 'text-yellow-700', 'Processing in progress', 'Wait for updates'], ['For Approval', 'bg-purple-500', 'text-purple-700', 'Sent to manager for review', 'Manager will approve or reject'], ['Approved', 'bg-green-500', 'text-green-700', 'Manager approved the order', 'Generate SO# and fulfill'], ['Completed', 'bg-teal-500', 'text-teal-700', 'Order fully fulfilled', 'No further action needed'], ['Rejected', 'bg-red-500', 'text-red-700', 'Manager rejected the order', 'Check notes for reason, revise if needed'], ['Cancelled', 'bg-gray-400', 'text-gray-500', 'Order was cancelled', 'Can be restored if needed']] as $s)
                                            <tr class="border-t border-gray-100 hover:bg-gray-50/50">
                                                <td class="px-4 py-2.5">
                                                    <span class="{{ $s[2] }} inline-flex items-center gap-1.5 text-xs font-semibold">
                                                        <span class="{{ $s[1] }} inline-block h-2 w-2 rounded-full"></span>
                                                        {{ $s[0] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $s[3] }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $s[4] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h3 class="mt-6 text-base font-semibold text-gray-800">Item Statuses (Transfer Tracking)</h3>
                            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Meaning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ([['N/A', 'No transfer number generated yet'], ['Pending', 'Transfer is pending at the warehouse'], ['Picking', 'Items are being picked at the warehouse'], ['Processing', 'Transfer is being processed for shipment'], ['Shipped', 'Items are in transit to the store'], ['Received', 'Store has received the items'], ['Error / Not Found', 'Issue with tracking — contact IT support']] as $ts)
                                            <tr class="border-t border-gray-100 hover:bg-gray-50/50">
                                                <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $ts[0] }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $ts[1] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>


                    {{-- ═══════════ 7. WORKFLOWS ═══════════ --}}
                    <section class="guide-section mt-6" id="workflows">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">7</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Common Workflows</h2>
                            </div>

                            <div class="space-y-6">
                                {{-- Workflow 1 --}}
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">Creating and Submitting a New Order</h3>
                                    <div class="mt-2 space-y-2.5">
                                        @foreach (['Go to <strong>Forms → Sales Order Form</strong> from the sidebar.', 'Complete the header fields — enter <strong>16-digit MBC Card Number</strong> to auto-fill customer details, select <strong>Mode of Payment</strong>, choose <strong>Delivery/Pick-up Date</strong> and <strong>Mode of Dispatching</strong>.', 'Add products using the search field, set <strong>Case/s Ordered</strong>.', 'Click <strong>Submit Order</strong>.', 'From the Order Details page, use <strong>Request For Approval</strong> to send to your manager.'] as $step)
                                            <div class="flex items-start gap-3">
                                                <span class="step-num">{{ $loop->iteration }}</span>
                                                <p class="text-sm text-gray-600">{!! $step !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <hr class="border-gray-100">

                                {{-- Workflow 2 --}}
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">After Manager Approval</h3>
                                    <div class="mt-2 space-y-2.5">
                                        @foreach (['Open the approved order from the <strong>Orders List</strong>.', 'Click <strong>Generate SO#</strong> to create transfer numbers in Oracle.', 'Track item statuses as they move through <strong>Picking → Shipped → Received</strong>.', 'Once all items are received, use <strong>Complete Order</strong>.'] as $step)
                                            <div class="flex items-start gap-3">
                                                <span class="step-num">{{ $loop->iteration }}</span>
                                                <p class="text-sm text-gray-600">{!! $step !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <hr class="border-gray-100">

                                {{-- Workflow 3 --}}
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">Editing an Existing Order</h3>
                                    <div class="mt-2 space-y-2.5">
                                        @foreach (['Click any order row from the <strong>Orders List</strong> to open Order Details.', 'Edit editable fields directly inline (<strong>yellow highlight</strong> indicates changes).', 'Use checkboxes to select items for <strong>bulk cancellation</strong>.', 'Click <strong>Update</strong> button to save all changes.'] as $step)
                                            <div class="flex items-start gap-3">
                                                <span class="step-num">{{ $loop->iteration }}</span>
                                                <p class="text-sm text-gray-600">{!! $step !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <hr class="border-gray-100">

                                {{-- Workflow 4 --}}
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">Cancelling an Order or Items</h3>
                                    <div class="mt-2 space-y-1.5">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-1.5 inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-500"></span>
                                            <p class="text-sm text-gray-600"><strong class="text-gray-800">Cancel entire order:</strong> Use <strong>Order Actions → Cancel Order</strong> and
                                                provide a reason.</p>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="mt-1.5 inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-500"></span>
                                            <p class="text-sm text-gray-600"><strong class="text-gray-800">Cancel specific items:</strong> Select item checkboxes → Click <strong>Cancel
                                                    Selected Items</strong>.</p>
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-100">

                                {{-- Workflow 5 --}}
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">Restoring a Cancelled Order</h3>
                                    <div class="mt-2 space-y-2.5">
                                        @foreach (['Open the <strong>cancelled order</strong> from Orders List.', 'Use <strong>Order Actions → Restore Order</strong>.'] as $step)
                                            <div class="flex items-start gap-3">
                                                <span class="step-num">{{ $loop->iteration }}</span>
                                                <p class="text-sm text-gray-600">{!! $step !!}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>


                    {{-- ═══════════ 8. TROUBLESHOOTING ═══════════ --}}
                    <section class="guide-section mt-6" id="troubleshooting">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">8</span>
                                <h2 class="text-2xl font-semibold text-gray-800">Troubleshooting</h2>
                            </div>

                            <h3 class="text-base font-semibold text-gray-800">Error Messages</h3>
                            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Error</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Cause</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Solution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ([['MBC Card not found', 'Invalid or incomplete 16-digit card', 'Check card number and re-enter manually if needed'], ['Order Items section missing', 'Required header fields incomplete', 'Complete all fields in Request, Customer, Payment, and Dispatch sections'], ['Cannot submit order', 'Missing products or quantities', 'Add at least one product with quantity > 0'], ['Update button not active', 'No changes made', 'Edit at least one field to activate']] as $err)
                                            <tr class="border-t border-gray-100 hover:bg-gray-50/50">
                                                <td class="px-4 py-2.5">
                                                    <span class="inline-block rounded bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">"{{ $err[0] }}"</span>
                                                </td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $err[1] }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $err[2] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <h3 class="mt-6 text-base font-semibold text-gray-800">Quick Tips</h3>
                            <div class="mt-3 space-y-2">
                                @foreach (['<strong>Dashboard</strong> refreshes each time you navigate to it — use as your first stop.', '<strong>Orders List</strong> updates via AJAX — filters and pagination don\'t reload the page.', '<strong>MBC Card Number</strong> must be exactly <strong>16 digits</strong> — incomplete numbers trigger a warning.', '<strong>Yellow highlights</strong> show unsaved changes — don\'t forget to click <strong>Update</strong>.', '<strong>Products page</strong> is for <strong>reference only</strong> — contact admin for inventory changes.', '<strong>Status badges</strong> are <strong>color-coded</strong> for quick visual scanning.', '<strong>Order Notes</strong> log all actions — check here for audit trail and comments.'] as $tip)
                                    <div class="flex items-start gap-2.5">
                                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path d="M5 13l4 4L19 7" />
                                        </svg>
                                        <p class="text-sm text-gray-600">{!! $tip !!}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    {{-- Footer --}}
                    <div class="mt-8 pb-4 text-center">
                        <p class="text-xs text-gray-400">ISO B2B2C Ordering System — Store Personnel User Guide — Version 1.1 — February 2026</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Image Lightbox Modal ═══ --}}
    <div class="lightbox-overlay" id="lightboxOverlay">
        <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
        <div class="lightbox-content" id="lightboxContent">
            <button class="lightbox-close" id="lightboxClose" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M1 1l12 12M13 1L1 13" />
                </svg>
            </button>
            <img id="lightboxImg" src="" alt="">
            <div class="lightbox-caption" id="lightboxCaption"></div>
        </div>
    </div>

    {{-- ═══ Sidebar scroll-spy + Lightbox JS ═══ --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Sidebar scroll-spy ──
            const navLinks = document.querySelectorAll('.guide-nav-link[data-section]');
            const subLinks = document.querySelectorAll('.guide-nav-sub');
            const sections = document.querySelectorAll('.guide-section');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        navLinks.forEach(link => link.classList.toggle('active', link.dataset.section === id));
                        subLinks.forEach(sub => {
                            const href = sub.getAttribute('href');
                            const target = href ? document.querySelector(href) : null;
                            if (target) {
                                const rect = target.getBoundingClientRect();
                                sub.classList.toggle('active', rect.top < window.innerHeight / 2 && rect.bottom > 0);
                            }
                        });
                    }
                });
            }, {
                rootMargin: '-100px 0px -60% 0px',
                threshold: 0
            });

            sections.forEach(s => observer.observe(s));

            // Close mobile sidebar on link click
            document.querySelectorAll('.guide-nav-link, .guide-nav-sub').forEach(link => {
                link.addEventListener('click', () => {
                    const sidebar = document.getElementById('guideSidebarCol');
                    if (sidebar) sidebar.classList.remove('open');
                });
            });


            // ── Image Lightbox (iOS-style) ──
            const overlay = document.getElementById('lightboxOverlay');
            const content = document.getElementById('lightboxContent');
            const lbImg = document.getElementById('lightboxImg');
            const lbCaption = document.getElementById('lightboxCaption');
            const lbClose = document.getElementById('lightboxClose');
            const backdrop = document.getElementById('lightboxBackdrop');

            // Open lightbox from any screenshot-frame click
            document.querySelectorAll('.screenshot-frame').forEach(frame => {
                frame.addEventListener('click', function() {
                    const img = this.querySelector('img');
                    const caption = this.querySelector('.screenshot-caption');
                    if (!img) return;

                    // Get source image position for origin transform
                    const rect = img.getBoundingClientRect();
                    const vpW = window.innerWidth;
                    const vpH = window.innerHeight;

                    // Calculate offset from center for transform-origin feel
                    const originX = rect.left + rect.width / 2;
                    const originY = rect.top + rect.height / 2;
                    const translateX = originX - vpW / 2;
                    const translateY = originY - vpH / 2;

                    // Set initial transform from the clicked image position
                    content.style.transition = 'none';
                    content.style.transform = `translate(${translateX}px, ${translateY}px) scale(0.4)`;
                    content.style.opacity = '0';

                    // Set image and caption
                    lbImg.src = img.src;
                    lbImg.alt = img.alt;
                    lbCaption.textContent = caption ? caption.textContent : '';

                    // Show overlay
                    overlay.classList.remove('closing');
                    overlay.classList.add('active');

                    // Animate to center (next frame for transition to kick in)
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            content.style.transition = 'transform 0.45s cubic-bezier(0.32, 0.72, 0, 1), opacity 0.3s cubic-bezier(0.32, 0.72, 0, 1)';
                            content.style.transform = 'translate(0, 0) scale(1)';
                            content.style.opacity = '1';
                        });
                    });

                    document.body.style.overflow = 'hidden';
                });
            });

            // Close lightbox
            function closeLightbox() {
                overlay.classList.add('closing');
                content.style.transform = 'translate(0, 0) scale(0.85)';
                content.style.opacity = '0';

                setTimeout(() => {
                    overlay.classList.remove('active', 'closing');
                    content.style.transition = 'none';
                    content.style.transform = '';
                    content.style.opacity = '';
                    lbImg.src = '';
                    document.body.style.overflow = '';
                }, 380);
            }

            lbClose.addEventListener('click', function(e) {
                e.stopPropagation();
                closeLightbox();
            });

            backdrop.addEventListener('click', closeLightbox);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && overlay.classList.contains('active')) {
                    closeLightbox();
                }
            });
        });
    </script>
@endsection
