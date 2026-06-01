<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Support\LocationConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get time range from request (for KPI only)
        $timeRange = request()->get('time_range', 'all_time');
        $dateRanges = [
            'today' => [Carbon::today(), Carbon::today()->endOfDay()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'all_time' => [null, null],
        ];
        $dateRange = $dateRanges[$timeRange] ?? $dateRanges['all_time'];
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        // ── Scoped order base query (NO date filter - for non-KPI data) ──
        $query = Order::query();

        // ✅ Role-based filtering (mirrors OrderController exactly)
        if ($user->role === 'store manager') {
            // Managers only see for approval/approved/rejected
            $query->whereIn('order_status', ['for approval', 'approved', 'rejected']);

            if ($user->user_location) {
                $stores = LocationConfig::regionStores($user->user_location);
                if (!empty($stores)) {
                    $query->whereIn('requesting_store', $stores);
                } else {
                    $query->where('requesting_store', $user->user_location);
                }
            }
        } elseif (in_array($user->role, ['warehouse admin', 'warehouse personnel'])) {
            // Warehouse roles → view-only, scoped to approved/completed
            $query->whereIn('order_status', ['approved', 'completed']);

            if ($user->user_location) {
                $stores = LocationConfig::regionStores($user->user_location);
                if (!empty($stores)) {
                    $query->whereIn('requesting_store', $stores);
                } else {
                    $query->where('requesting_store', $user->user_location);
                }
            }
        } elseif ($user->role !== 'super admin') {
            // Regular store personnel → single store restriction
            if ($user->user_location) {
                $stores = LocationConfig::regionStores($user->user_location);
                if (!empty($stores)) {
                    $query->whereIn('requesting_store', $stores);
                } else {
                    $query->where('requesting_store', $user->user_location);
                }
            }
        }
        // Super admin sees all - no restrictions

        $base = clone $query;

        // ── KPI-specific query with date filter (for KPI cards only) ──
        $kpiQuery = clone $query;
        if ($startDate && $endDate) {
            $kpiQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $kpiBase = clone $kpiQuery;

        // ── KPI Status counts (date filtered) ────────────────────────────────
        $statusKeys = ['new order', 'pending', 'for approval', 'approved', 'completed', 'rejected', 'cancelled', 'archived'];
        $kpiCounts = [];  // Rename from $counts to $kpiCounts
        foreach ($statusKeys as $status) {
            $kpiCounts[str_replace(' ', '_', $status) . '_count'] = (clone $kpiBase)->where('order_status', $status)->count();
        }
        $totalOrders = (clone $kpiBase)->count();

        // ── Pipeline counts (using base - no date filter) ────────────────────
        $pipelineCounts = [];
        foreach ($statusKeys as $status) {
            $pipelineCounts[str_replace(' ', '_', $status) . '_count'] = (clone $base)->where('order_status', $status)->count();
        }

        // ── Time-scoped order counts (for admin secondary cards - using base) ──
        $ordersToday     = (clone $base)->whereDate('created_at', Carbon::today())->count();
        $ordersThisWeek  = (clone $base)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $ordersThisMonth = (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // ── Revenue for KPI (date filtered) ───────────────────────────────────
        $revenueIds = (clone $kpiBase)
            ->whereIn('order_status', ['approved', 'completed'])
            ->pluck('id');

        $totalRevenue = DB::table('order_items')
            ->whereIn('order_id', $revenueIds)
            ->where('item_type', '!=', 'FREEBIE')
            ->where(fn($q) => $q->whereNull('remarks')->orWhere('remarks', '!=', 'Item Cancelled'))
            ->sum('amount');

        $mtdRevenue = $totalRevenue;

        $mtdFreebiesValue = DB::table('order_items')
            ->whereIn('order_id', $revenueIds)
            ->where('item_type', 'FREEBIE')
            ->sum('amount');

        // ── Per-status order value (₱) for KPI cards (date filtered) ──────────
        $allOrderIds = (clone $kpiBase)->pluck('id');

        $statusAmounts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('order_items.order_id', $allOrderIds)
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where(fn($q) => $q->whereNull('order_items.remarks')->orWhere('order_items.remarks', '!=', 'Item Cancelled'))
            ->groupBy('orders.order_status')
            ->selectRaw('orders.order_status as status, SUM(order_items.amount) as total')
            ->pluck('total', 'status');

        $amounts = [
            'total'        => (float) $statusAmounts->sum(),
            'new_order'    => (float) ($statusAmounts['new order'] ?? 0),
            'for_approval' => (float) ($statusAmounts['for approval'] ?? 0),
            'approved'     => (float) ($statusAmounts['approved'] ?? 0),
            'completed'    => (float) ($statusAmounts['completed'] ?? 0),
            'rejected'     => (float) ($statusAmounts['rejected'] ?? 0),
        ];

        // ── Top 5 products by revenue (date filtered) ─────────────────────────
        $topProducts = DB::table('order_items')
            ->whereIn('order_id', $revenueIds)
            ->where('item_type', '!=', 'FREEBIE')
            ->where(fn($q) => $q->whereNull('remarks')->orWhere('remarks', '!=', 'Item Cancelled'))
            ->select('sku', 'item_description')
            ->selectRaw('SUM(amount) as total_sales, SUM(total_qty) as total_qty')
            ->groupBy('sku', 'item_description')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // ── Payment mode breakdown (date filtered) ────────────────────────────
        $paymentModeOrderIds = (clone $kpiBase)->pluck('id');

        $paymentModes = DB::table('orders')
            ->whereIn('id', $paymentModeOrderIds)
            ->whereNotNull('mode_payment')
            ->select('mode_payment')
            ->selectRaw('COUNT(*) as order_count')
            ->groupBy('mode_payment')
            ->orderByDesc('order_count')
            ->get();

        // ── Status breakdown for progress bars (using base - all time) ────────
        $baseOrderIds = (clone $base)->pluck('id');
        $baseStatusCounts = [];
        foreach ($statusKeys as $status) {
            $baseStatusCounts[str_replace(' ', '_', $status) . '_count'] = (clone $base)->where('order_status', $status)->count();
        }

        $statusMap = [
            'New Order'    => ['count' => $baseStatusCounts['new_order_count'],    'color' => 'bg-blue-500'],
            'Pending'      => ['count' => $baseStatusCounts['pending_count'],      'color' => 'bg-yellow-500'],
            'For Approval' => ['count' => $baseStatusCounts['for_approval_count'], 'color' => 'bg-purple-500'],
            'Approved'     => ['count' => $baseStatusCounts['approved_count'],     'color' => 'bg-emerald-600'],
            'Completed'    => ['count' => $baseStatusCounts['completed_count'],    'color' => 'bg-teal-500'],
            'Rejected'     => ['count' => $baseStatusCounts['rejected_count'],     'color' => 'bg-red-500'],
            'Cancelled'    => ['count' => $baseStatusCounts['cancelled_count'],    'color' => 'bg-gray-400'],
        ];
        $statusBreakdown = collect($statusMap)->filter(fn($v) => $v['count'] > 0);

        // ── Recent orders (using base - no date filter) ───────────────────────
        $recentOrders = (clone $base)
            ->select('id', 'sof_id', 'customer_name', 'order_status', 'requesting_store', 'channel_order', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($o) {
                $o->store_name = LocationConfig::storeName($o->requesting_store, $o->requesting_store);
                return $o;
            });

        // ── Top stores by order volume (using base - no date filter) ───────────
        $topStoresQuery = (clone $base)
            ->select('requesting_store', DB::raw('COUNT(*) as total'))
            ->groupBy('requesting_store')
            ->orderByDesc('total')
            ->limit(5);

        $topStores = $topStoresQuery->get()
            ->map(function ($s) {
                $s->name = LocationConfig::storeName($s->requesting_store, $s->requesting_store);
                return $s;
            });

        // ── Inventory snapshot (store personnel only) ─────────────
        $totalProducts = $lowStockCount = $outOfStockCount = $healthyStockCount = 0;
        $totalStoreAllocation = $wmsVirtualTotal = $wmsSkuCount = $wmsZeroCount = 0;
        $warehouseName = $warehouseCode = $wmsLastUpdated = null;

        // Only show inventory to store personnel (non-manager, non-super admin)
        if ($user->role !== 'store manager' && $user->role !== 'super admin' && $user->user_location) {
            $accessibleStores = LocationConfig::regionStores($user->user_location);
            $primaryStore = !empty($accessibleStores) ? $accessibleStores[0] : $user->user_location;
            $storeCode = strtolower($primaryStore);

            $tbl = 'products_' . $storeCode;
            $warehouseCode = LocationConfig::warehouseForStore($primaryStore);
            $warehouseName = $warehouseCode
                ? (LocationConfig::warehouses()[$warehouseCode] ?? $warehouseCode)
                : null;

            if (Schema::connection('mysql')->hasTable($tbl)) {
                $totalProducts        = DB::table($tbl)->whereNull('archived_at')->count();
                $lowStockCount        = DB::table($tbl)->whereNull('archived_at')->where('allocation_per_case', '>', 0)->where('allocation_per_case', '<=', 5)->count();
                $outOfStockCount      = DB::table($tbl)->whereNull('archived_at')->where('allocation_per_case', '<=', 0)->count();
                $healthyStockCount    = max(0, $totalProducts - $lowStockCount - $outOfStockCount);
                $totalStoreAllocation = (int) DB::table($tbl)->whereNull('archived_at')->sum('allocation_per_case');

                if ($warehouseCode) {
                    $activeSkus = DB::table($tbl)->whereNull('archived_at')->pluck('sku')->map(fn($s) => strtoupper($s));
                    if ($activeSkus->isNotEmpty()) {
                        $wmsStats   = DB::table('product_wms_allocations')
                            ->where('warehouse_code', $warehouseCode)
                            ->whereIn('sku', $activeSkus)
                            ->selectRaw('COUNT(*) as sku_count, COALESCE(SUM(wms_virtual_allocation),0) as virtual_total, SUM(CASE WHEN COALESCE(wms_virtual_allocation,0)<=0 THEN 1 ELSE 0 END) as zero_count, MAX(updated_at) as last_updated')
                            ->first();

                        if ($wmsStats) {
                            $wmsSkuCount     = $wmsStats->sku_count     ?? 0;
                            $wmsVirtualTotal = $wmsStats->virtual_total ?? 0;
                            $wmsZeroCount    = $wmsStats->zero_count    ?? 0;
                            $wmsLastUpdated  = $wmsStats->last_updated  ? Carbon::parse($wmsStats->last_updated) : null;
                        }
                    }
                }
            }
        }

        // ── Stuck orders (using base - no date filter) ──────────────
        $stuckThresholds = [
            'new order'    => ['days' => 1, 'message' => 'Waiting to be processed',       'color' => 'yellow'],
            'pending'      => ['days' => 2, 'message' => 'Pending — no recent activity',  'color' => 'orange'],
            'for approval' => ['days' => 3, 'message' => 'Awaiting manager approval',     'color' => 'purple'],
            'rejected'     => ['days' => 3, 'message' => 'Rejected — needs store personnel resubmission', 'color' => 'red'],
        ];

        $stuckOrders = collect();
        foreach ($stuckThresholds as $status => $meta) {
            if ($user->role === 'store manager' && !in_array($status, ['for approval', 'approved', 'rejected'])) {
                continue;
            }
            if (in_array($user->role, ['warehouse admin', 'warehouse personnel']) && !in_array($status, ['approved', 'completed'])) {
                continue;
            }
            $rows = (clone $base)
                ->where('order_status', $status)
                ->where('updated_at', '<', now()->subDays($meta['days']))
                ->select('id', 'sof_id', 'customer_name', 'order_status', 'requesting_store', 'updated_at')
                ->orderBy('updated_at')
                ->limit(5)
                ->get()
                ->map(function ($o) use ($meta) {
                    $o->store_name  = LocationConfig::storeName($o->requesting_store, $o->requesting_store);
                    $o->days_stuck  = now()->diffInDays($o->updated_at);
                    $o->reminder    = $meta['message'];
                    $o->color       = $meta['color'];
                    return $o;
                });
            $stuckOrders = $stuckOrders->merge($rows);
        }
        $stuckOrders = $stuckOrders->sortBy('updated_at')->values()->take(10);

        // ── Activity feed (recent order_notes scoped to user) ─────
        $activityQuery = DB::table('order_notes')
            ->join('orders', 'order_notes.order_id', '=', 'orders.id')
            ->leftJoin('users', 'order_notes.user_id', '=', 'users.id');

        if ($user->role === 'store manager') {
            $activityQuery->whereIn('orders.order_status', ['for approval', 'approved', 'rejected']);
            if ($user->user_location) {
                $stores = LocationConfig::regionStores($user->user_location);
                if (!empty($stores)) {
                    $activityQuery->whereIn('orders.requesting_store', $stores);
                } else {
                    $activityQuery->where('orders.requesting_store', $user->user_location);
                }
            }
        } elseif ($user->role !== 'super admin') {
            if ($user->user_location) {
                $stores = LocationConfig::regionStores($user->user_location);
                if (!empty($stores)) {
                    $activityQuery->whereIn('orders.requesting_store', $stores);
                } else {
                    $activityQuery->where('orders.requesting_store', $user->user_location);
                }
            }
        }

        $recentActivityLogs = collect();
        if (auth()->user()->role === 'super admin') {
            $recentActivityLogs = \App\Models\ActivityLog::with('user')
                ->latest()
                ->limit(10)
                ->get();
        }

        $activityFeed = $activityQuery
            ->select(
                'order_notes.status as note_status',
                'order_notes.note',
                'order_notes.created_at',
                'orders.sof_id',
                'orders.id as order_id',
                'orders.requesting_store',
                'users.name as actor_name'
            )
            ->orderByDesc('order_notes.created_at')
            ->limit(10)
            ->get()
            ->map(function ($n) {
                $n->store_name = LocationConfig::storeName($n->requesting_store, $n->requesting_store);
                $n->created_at = Carbon::parse($n->created_at);
                return $n;
            });

        return view('dashboard.index', array_merge($kpiCounts, compact(
            'totalOrders',
            'ordersToday',
            'ordersThisWeek',
            'ordersThisMonth',
            'totalRevenue',
            'mtdRevenue',
            'mtdFreebiesValue',
            'topProducts',
            'paymentModes',
            'recentOrders',
            'topStores',
            'statusBreakdown',
            'totalProducts',
            'lowStockCount',
            'outOfStockCount',
            'healthyStockCount',
            'totalStoreAllocation',
            'warehouseName',
            'warehouseCode',
            'wmsVirtualTotal',
            'wmsSkuCount',
            'wmsZeroCount',
            'wmsLastUpdated',
            'stuckOrders',
            'activityFeed',
            'recentActivityLogs',
            'amounts',
            'timeRange',
            'pipelineCounts',  // Add this
        )));
    }
}
