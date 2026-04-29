@extends('layouts.app')

@section('title', 'Inventory Allocation Logs')

@section('content')
    <style nonce="{{ $cspNonce ?? '' }}">
        /* ════════════════════════════════════════════════
                                   LAYOUT
                                ════════════════════════════════════════════════ */
        .wms-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1rem;
            align-items: start;
        }

        @media (max-width: 900px) {
            .wms-layout {
                grid-template-columns: 1fr;
            }

            .wms-sidebar {
                position: static !important;
            }
        }

        /* ════════════════════════════════════════════════
                                   SIDEBAR — FILE TREE
                                ════════════════════════════════════════════════ */
        .wms-sidebar {
            position: sticky;
            top: 1.5rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .sidebar-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .sidebar-header-title {
            color: #e0e7ff;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sidebar-header-path {
            color: #818cf8;
            font-family: ui-monospace, monospace;
            font-size: 0.65rem;
            margin-top: 0.1rem;
        }

        .sidebar-scroll {
            overflow-y: auto;
            max-height: calc(100vh - 220px);
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }

        /* ── Date group ── */
        .date-group {
            border-bottom: 1px solid #f1f5f9;
        }

        .date-group:last-child {
            border-bottom: none;
        }

        .date-group-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.85rem;
            cursor: pointer;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s;
            user-select: none;
        }

        .date-group-header:hover {
            background: #f1f5f9;
        }

        .date-group-header.has-active {
            background: #eef2ff;
            border-left: 3px solid #6366f1;
            padding-left: calc(0.85rem - 3px);
        }

        .date-chevron {
            flex-shrink: 0;
            width: 13px;
            height: 13px;
            color: #94a3b8;
            transition: transform 0.15s ease;
        }

        .date-group.open .date-chevron {
            transform: rotate(90deg);
        }

        .date-label {
            flex: 1;
            min-width: 0;
        }

        .date-label-main {
            font-size: 0.78rem;
            font-weight: 600;
            color: #1e293b;
        }

        .date-group-header.has-active .date-label-main {
            color: #4338ca;
        }

        .date-label-sub {
            font-size: 0.65rem;
            color: #94a3b8;
            font-family: ui-monospace, monospace;
        }

        .date-file-count {
            font-size: 0.65rem;
            font-weight: 600;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 0.1rem 0.45rem;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        .date-group-header.has-active .date-file-count {
            background: #e0e7ff;
            color: #6366f1;
        }

        /* ── Hour file list ── */
        .hour-list {
            display: none;
            padding: 0.25rem 0;
            background: #fff;
        }

        .date-group.open .hour-list {
            display: block;
        }

        .hour-row {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.85rem 0.4rem 2.1rem;
            cursor: pointer;
            transition: background 0.1s;
            text-decoration: none;
        }

        .hour-row:hover {
            background: #f8fafc;
        }

        .hour-row.active {
            background: #eef2ff;
        }

        .hour-row.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #6366f1;
            border-radius: 0 2px 2px 0;
        }

        .hour-file-icon {
            flex-shrink: 0;
            width: 13px;
            height: 13px;
            color: #94a3b8;
        }

        .hour-row.active .hour-file-icon {
            color: #6366f1;
        }

        .hour-row-name {
            font-size: 0.73rem;
            font-weight: 500;
            color: #475569;
            font-family: ui-monospace, monospace;
        }

        .hour-row.active .hour-row-name {
            color: #4338ca;
            font-weight: 700;
        }

        .hour-row-badge {
            margin-left: auto;
            font-size: 0.63rem;
            color: #94a3b8;
            font-family: ui-monospace, monospace;
            background: #f1f5f9;
            padding: 0.05rem 0.4rem;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .hour-row.active .hour-row-badge {
            background: #e0e7ff;
            color: #6366f1;
        }

        /* ════════════════════════════════════════════════
                                   MAIN PANEL
                                ════════════════════════════════════════════════ */
        .wms-main {
            min-width: 0;
        }

        /* ── Toolbar ── */
        .log-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.6rem;
        }

        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .stat-chip svg {
            width: 11px;
            height: 11px;
            flex-shrink: 0;
        }

        .toolbar-search {
            position: relative;
            flex: 1;
            min-width: 150px;
            max-width: 300px;
        }

        .toolbar-search input {
            width: 100%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.38rem 0.75rem 0.38rem 2rem;
            font-size: 0.775rem;
            color: #1e293b;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .toolbar-search input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
        }

        .toolbar-search-icon {
            position: absolute;
            left: 0.55rem;
            top: 50%;
            transform: translateY(-50%);
            width: 13px;
            height: 13px;
            color: #94a3b8;
            pointer-events: none;
        }

        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.38rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.73rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            cursor: pointer;
            transition: all 0.12s;
            white-space: nowrap;
        }

        .toolbar-btn:hover {
            background: #f8fafc;
            color: #1e293b;
            border-color: #cbd5e1;
        }

        .toolbar-btn svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }

        /* Auto-refresh pulse */
        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.35;
                transform: scale(0.7);
            }
        }

        .pulse-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse-dot 1.4s ease-in-out infinite;
            flex-shrink: 0;
        }

        .pulse-dot.off {
            background: #94a3b8;
            animation: none;
        }

        /* ════════════════════════════════════════════════
                                   TERMINAL LOG PANE  ——  HIGH CONTRAST
                                ════════════════════════════════════════════════ */
        .log-pane {
            background: #0d1117;
            border-radius: 0.875rem;
            overflow: hidden;
            border: 1px solid #21262d;
            box-shadow: 0 4px 28px rgba(0, 0, 0, 0.4);
        }

        .log-pane-titlebar {
            background: #161b22;
            padding: 0.6rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            border-bottom: 1px solid #21262d;
        }

        .tbar-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }

        .log-pane-path {
            margin-left: 0.6rem;
            font-family: ui-monospace, monospace;
            font-size: 0.72rem;
            color: #6e7681;
        }

        .log-pane-path strong {
            color: #adbac7;
            font-weight: 600;
        }

        .log-filtered-count {
            margin-left: auto;
            font-family: ui-monospace, monospace;
            font-size: 0.68rem;
            color: #6e7681;
        }

        /* Scrollable area */
        .log-scroll {
            overflow-y: auto;
            max-height: 70vh;
            padding: 0.5rem 0;
        }

        .log-scroll::-webkit-scrollbar {
            width: 7px;
        }

        .log-scroll::-webkit-scrollbar-track {
            background: #0d1117;
        }

        .log-scroll::-webkit-scrollbar-thumb {
            background: #2d333b;
            border-radius: 4px;
        }

        .log-scroll::-webkit-scrollbar-thumb:hover {
            background: #444c56;
        }

        /* ── Typography: HIGH CONTRAST base ── */
        .log-container {
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'SF Mono', ui-monospace, monospace;
            font-size: 0.775rem;
            line-height: 1.7;
            color: #cdd9e5;
            /* bright default body text */
        }

        /* ── Line wrapper ── */
        .log-line {
            display: flex;
            gap: 0.75rem;
            align-items: baseline;
            padding: 1.5px 1rem;
            transition: background 0.07s;
        }

        .log-line:hover {
            background: rgba(255, 255, 255, 0.035);
        }

        /* Type-specific rows */
        .log-line.line-error {
            background: rgba(248, 81, 73, 0.13);
            border-left: 2.5px solid #f85149;
            padding-left: calc(1rem - 2.5px);
        }

        .log-line.line-error:hover {
            background: rgba(248, 81, 73, 0.2);
        }

        .log-line.line-warning {
            background: rgba(210, 153, 34, 0.11);
            border-left: 2.5px solid #d29922;
            padding-left: calc(1rem - 2.5px);
        }

        .log-line.line-warning:hover {
            background: rgba(210, 153, 34, 0.18);
        }

        .log-line.line-header {
            background: rgba(88, 96, 246, 0.1);
            border-left: 2.5px solid #5860f6;
            padding-left: calc(1rem - 2.5px);
        }

        .log-line.line-summary {
            background: rgba(35, 197, 139, 0.1);
            border-left: 2.5px solid #23c58b;
            padding-left: calc(1rem - 2.5px);
        }

        .log-line.line-separator {
            opacity: 0.28;
            user-select: none;
        }

        /* ── Timestamp ── */
        .log-timestamp {
            flex-shrink: 0;
            white-space: nowrap;
            font-size: 0.68rem;
            color: #444c56;
            /* very muted — stays out of the way */
            min-width: 7.5ch;
            user-select: none;
            padding-top: 1px;
        }

        /* ── Body: high contrast ── */
        .log-body {
            flex: 1;
            word-break: break-word;
            color: #cdd9e5;
        }

        /* ── Syntax tokens ── */
        .tag-error {
            color: #ff7b72;
            font-weight: 700;
        }

        /* red-400     */
        .tag-ok {
            color: #3fb950;
            font-weight: 600;
        }

        /* green-400   */
        .tag-warning {
            color: #e3b341;
            font-weight: 600;
        }

        /* amber-300   */
        .tag-info {
            color: #79c0ff;
        }

        /* sky-300     */
        .tag-updated {
            color: #d2a8ff;
            font-weight: 600;
        }

        /* purple-300  */
        .tag-inserted {
            color: #56d364;
            font-weight: 600;
        }

        /* green-300   */
        .tag-failed {
            color: #ff7b72;
            font-weight: 700;
        }

        .tag-shutdown {
            color: #e3b341;
        }

        .tag-heading {
            color: #a5d6ff;
            font-weight: 700;
        }

        /* blue-200    */
        .tag-sku {
            color: #ffffff;
            font-weight: 700;
        }

        /* pure white  */
        .tag-num {
            color: #79c0ff;
        }

        .tag-duration {
            color: #56d364;
            font-weight: 600;
        }

        .tag-dim {
            color: #545d68;
        }

        /* pipes/dashes */
        .tag-wh {
            color: #ffa657;
            font-weight: 600;
        }

        /* orange-300  */
        .tag-section {
            color: #58a6ff;
        }

        /* Search match */
        mark.log-highlight {
            background: #e3b341;
            color: #0d1117;
            border-radius: 2px;
            padding: 0 2px;
            font-weight: 700;
        }

        /* ── Empty states ── */
        .empty-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 5rem 2rem;
            border-radius: 0.875rem;
            border: 1.5px dashed #e2e8f0;
            background: #fff;
            text-align: center;
            color: #94a3b8;
        }

        .empty-panel p {
            font-size: 0.85rem;
        }
    </style>

    <div class="mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Page header ── --}}
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 p-3 shadow-lg">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Inventory Allocation Logs</h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-indigo-600">storage/logs/wms_logs/</code>
                        — allocation sync output
                    </p>
                </div>
            </div>

            {{-- Auto-refresh toggle --}}
            <label
                class="flex cursor-pointer select-none items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">
                <span class="pulse-dot off" id="refreshDot"></span>
                <input type="checkbox" id="autoRefreshToggle" class="h-3.5 w-3.5 rounded accent-indigo-600">
                Auto-refresh
                <span class="w-6 tabular-nums text-gray-400" id="refreshCountdown"></span>
            </label>
        </div>

        {{-- ── Two-column layout ── --}}
        <div class="wms-layout">

            {{-- ══════════════════════════════
             SIDEBAR — FILE TREE
        ══════════════════════════════ --}}
            <aside class="wms-sidebar">

                <div class="sidebar-header">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="#a5b4fc" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                    </svg>
                    <div>
                        <div class="sidebar-header-title">Log Files</div>
                        <div class="sidebar-header-path">wms_logs/</div>
                    </div>
                </div>

                <div class="sidebar-scroll">
                    @forelse($dates as $date)
                        @php
                            $dateHours = $allDateHours[$date] ?? [];
                            $isActiveDate = $selectedDate === $date;
                            $parsed = \Carbon\Carbon::parse($date);
                        @endphp

                        <div class="date-group {{ $isActiveDate ? 'open' : '' }}"
                            data-date="{{ $date }}">

                            {{-- Date header row --}}
                            <div class="date-group-header {{ $isActiveDate ? 'has-active' : '' }}"
                                data-date-group-header>

                                <svg class="date-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M9 5l7 7-7 7" />
                                </svg>

                                {{-- Folder icon --}}
                                <svg class="{{ $isActiveDate ? 'text-indigo-500' : 'text-amber-400' }} h-4 w-4 flex-shrink-0"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                </svg>

                                <div class="date-label">
                                    <div class="date-label-main">{{ $parsed->format('D, M j') }}</div>
                                    <div class="date-label-sub">{{ $date }}</div>
                                </div>

                                <span class="date-file-count">{{ count($dateHours) }}</span>
                            </div>

                            {{-- Hour file entries --}}
                            <div class="hour-list">
                                @forelse($dateHours as $hour)
                                    @php
                                        $isActiveHour = $isActiveDate && (int) $selectedHour === (int) $hour;
                                        $padded = str_pad($hour, 2, '0', STR_PAD_LEFT);
                                        $href = route('logs', ['date' => $date, 'hour' => $hour]);
                                    @endphp
                                    <a href="{{ $href }}"
                                        class="hour-row {{ $isActiveHour ? 'active' : '' }}">

                                        <svg class="hour-file-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                        </svg>

                                        <span class="hour-row-name">allocations_{{ $padded }}.log</span>
                                        <span class="hour-row-badge">{{ $padded }}:xx</span>
                                    </a>
                                @empty
                                    <div class="px-8 py-2 text-xs italic text-gray-400">No files</div>
                                @endforelse
                            </div>

                        </div>
                    @empty
                        <div class="px-4 py-10 text-center text-sm italic text-gray-400">
                            No log directories found.
                        </div>
                    @endforelse
                </div>

            </aside>

            {{-- ══════════════════════════════
             MAIN — TOOLBAR + LOG PANE
        ══════════════════════════════ --}}
            <div class="wms-main space-y-3">

                @if ($logContent)

                    {{-- Toolbar --}}
                    <div class="log-toolbar">
                        <span class="stat-chip bg-slate-100 text-slate-600">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span id="totalLines">0</span>&thinsp;lines
                        </span>

                        <span class="stat-chip bg-red-50 text-red-600" id="chipError">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span id="errorCountVal">0</span>&thinsp;errors
                        </span>

                        <span class="stat-chip bg-violet-50 text-violet-600">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span id="updatedVal">0</span>&thinsp;updated
                        </span>

                        <span class="stat-chip bg-emerald-50 text-emerald-700">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span id="insertedVal">0</span>&thinsp;inserted
                        </span>

                        {{-- Search --}}
                        <div class="toolbar-search">
                            <svg class="toolbar-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" id="logSearch" placeholder="Filter lines…">
                        </div>

                        <div class="ml-auto flex gap-1.5">
                            <button class="toolbar-btn" id="scrollBottomBtn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 13l-7 7-7-7m14-8l-7 7-7-7" />
                                </svg>
                                Bottom
                            </button>
                            <button class="toolbar-btn" id="copyRawBtn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Copy raw
                            </button>
                        </div>
                    </div>

                    {{-- Terminal pane --}}
                    <div class="log-pane">

                        <div class="log-pane-titlebar">
                            <div class="tbar-dot bg-red-500"></div>
                            <div class="tbar-dot bg-yellow-400"></div>
                            <div class="tbar-dot bg-green-500"></div>
                            <span class="log-pane-path">
                                wms_logs/<strong>{{ $selectedDate }}</strong>/allocations_<strong>{{ str_pad($selectedHour, 2, '0', STR_PAD_LEFT) }}</strong>.log
                            </span>
                            <span class="log-filtered-count" id="filteredCount"></span>
                        </div>

                        <div class="log-scroll log-container" id="logScroll">
                            <div id="logLines">
                                @foreach ($parsedLines as $line)
                                    @include('partials.wms-log-line', ['line' => $line])
                                @endforeach
                            </div>
                            @if (empty($parsedLines))
                                <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                                    <svg class="h-8 w-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm text-gray-500">Log file is empty.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($selectedDate)
                    <div class="empty-panel">
                        <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-400">Select a log file from the sidebar.</p>
                    </div>
                @else
                    <div class="empty-panel">
                        <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 7a2 2 0 012-2h5l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                        </svg>
                        <p>No log files found.</p>
                    </div>
                @endif

            </div>{{-- /wms-main --}}
        </div>{{-- /wms-layout --}}
    </div>

    <textarea id="rawLogContent" class="sr-only" aria-hidden="true">{{ $logContent ?? '' }}</textarea>

    <script nonce="{{ $cspNonce ?? '' }}">
        let autoRefreshTimer = null;
        let autoRefreshCountdown = 0;
        const REFRESH_INTERVAL = 30;

        // ── Sidebar folder toggle ──────────────────────────────
        function toggleDateGroup(headerEl) {
            headerEl.closest('.date-group').classList.toggle('open');
        }

        // ── Log filter ─────────────────────────────────────────
        function filterLog(query) {
            const lines = document.querySelectorAll('#logLines .log-line');
            const q = query.toLowerCase().trim();
            let visible = 0;

            lines.forEach(line => {
                const show = !q || line.textContent.toLowerCase().includes(q);
                line.style.display = show ? '' : 'none';

                if (show) {
                    q ? applyHighlight(line, q) : clearHighlight(line);
                    visible++;
                }
            });

            const el = document.getElementById('filteredCount');
            if (el) el.textContent = q ? `${visible} / ${lines.length} lines` : '';
        }

        function applyHighlight(lineEl, q) {
            lineEl.querySelectorAll('.log-body').forEach(span => {
                const orig = span.dataset.orig ?? span.innerHTML;
                span.dataset.orig = orig;
                const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                span.innerHTML = orig.replace(re, '<mark class="log-highlight">$1</mark>');
            });
        }

        function clearHighlight(lineEl) {
            lineEl.querySelectorAll('.log-body[data-orig]').forEach(span => {
                span.innerHTML = span.dataset.orig;
            });
        }

        // ── Helpers ────────────────────────────────────────────
        function scrollLogBottom() {
            const el = document.getElementById('logScroll');
            if (el) el.scrollTop = el.scrollHeight;
        }

        function copyLogRaw(btn) {
            const raw = document.getElementById('rawLogContent').value;
            const orig = btn.innerHTML;
            navigator.clipboard.writeText(raw).then(() => {
                btn.innerHTML = `<svg class="h-3.5 w-3.5" fill="none" stroke="#22c55e" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            Copied!`;
                setTimeout(() => btn.innerHTML = orig, 2000);
            });
        }

        // ── Auto-refresh ───────────────────────────────────────
        const refreshToggle = document.getElementById('autoRefreshToggle');
        const refreshDot = document.getElementById('refreshDot');
        const refreshCd = document.getElementById('refreshCountdown');

        function startAutoRefresh() {
            stopAutoRefresh();
            autoRefreshCountdown = REFRESH_INTERVAL;
            refreshDot?.classList.remove('off');
            if (refreshCd) refreshCd.textContent = REFRESH_INTERVAL + 's';
            autoRefreshTimer = setInterval(() => {
                if (--autoRefreshCountdown <= 0) {
                    window.location.reload();
                    return;
                }
                if (refreshCd) refreshCd.textContent = autoRefreshCountdown + 's';
            }, 1000);
        }

        function stopAutoRefresh() {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
            refreshDot?.classList.add('off');
            if (refreshCd) refreshCd.textContent = '';
        }

        // ── Stats ──────────────────────────────────────────────
        function computeStats() {
            const lines = document.querySelectorAll('#logLines .log-line');
            let errors = 0,
                updated = 0,
                inserted = 0;

            lines.forEach(line => {
                const txt = line.textContent;
                if (line.classList.contains('line-error')) errors++;
                const um = txt.match(/(\d+)\s+Updated/i);
                const im = txt.match(/(\d+)\s+Inserted/i);
                if (um) updated = Math.max(updated, parseInt(um[1]));
                if (im) inserted = Math.max(inserted, parseInt(im[1]));
            });

            const s = (id, v) => {
                const e = document.getElementById(id);
                if (e) e.textContent = v;
            };
            s('totalLines', lines.length);
            s('errorCountVal', errors);
            s('updatedVal', updated);
            s('insertedVal', inserted);

            if (errors > 0) {
                document.getElementById('chipError')?.classList.replace('bg-red-50', 'bg-red-100');
            }
        }

        // ── Setup event listeners (CSP compliant) ──
        function setupEventListeners() {
            // Date group toggles
            document.querySelectorAll('[data-date-group-header]').forEach(header => {
                header.addEventListener('click', function() {
                    toggleDateGroup(this);
                });
            });

            // Search input
            const searchInput = document.getElementById('logSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    filterLog(e.target.value);
                });
            }

            // Bottom button
            const bottomBtn = document.getElementById('scrollBottomBtn');
            if (bottomBtn) {
                bottomBtn.addEventListener('click', () => scrollLogBottom());
            }

            // Copy button
            const copyBtn = document.getElementById('copyRawBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    copyLogRaw(this);
                });
            }

            // Auto-refresh toggle
            if (refreshToggle) {
                refreshToggle.addEventListener('change', function() {
                    localStorage.setItem('wmsAutoRefresh', this.checked);
                    this.checked ? startAutoRefresh() : stopAutoRefresh();
                });
            }
        }

        // ── Init ───────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            computeStats();
            scrollLogBottom();
            setupEventListeners();

            if (localStorage.getItem('wmsAutoRefresh') === 'true' && refreshToggle) {
                refreshToggle.checked = true;
                startAutoRefresh();
            }
        });
    </script>
@endsection
