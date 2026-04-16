<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $storeMapping = [
            'lz' => ['6012'],
            'vs' => ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010'],
        ];

        $allStoreLocations = [
            '4002' => 'Metro Wholesalemart Colon',
            '2010' => 'Metro Maasin',
            '2017' => 'Metro Tacloban',
            '2019' => 'Metro Bay-Bay',
            '3018' => 'Metro Alang-Alang',
            '3019' => 'Metro Hilongos',
            '2008' => 'Metro Toledo',
            '6012' => 'Super Metro Antipolo',
            '6009' => 'Super Metro Carcar',
            '6010' => 'Super Metro Bogo',
        ];

        $locationToWarehouse = [
            '4002' => '80181',
            '2010' => '80181',
            '2017' => '80181',
            '2019' => '80181',
            '3018' => '80181',
            '3019' => '80181',
            '2008' => '80181',
            '6009' => '80181',
            '6010' => '80181',
            '6012' => '80151',
        ];

        $warehouseNames = [
            '80151' => 'Silangan Warehouse',
            '80181' => 'Bacolod Depot',
        ];

        $query = Order::query();

        if ($user->role === 'manager') {
            if ($user->user_location && isset($storeMapping[$user->user_location])) {
                $query->whereIn('requesting_store', $storeMapping[$user->user_location]);
            }
        } elseif ($user->role !== 'super admin') {
            if ($user->user_location) {
                $query->where('requesting_store', $user->user_location);
            }
        }

        $base = clone $query;

        // Status counts
        $counts = [];
        foreach (['new order', 'pending', 'for approval', 'approved', 'completed', 'rejected', 'cancelled', 'archived'] as $status) {
            $key = str_replace(' ', '_', $status) . '_count';
            $counts[$key] = (clone $base)->where('order_status', $status)->count();
        }

        $totalOrders = (clone $base)->count();

        // Time-scoped
        $ordersToday     = (clone $base)->whereDate('created_at', Carbon::today())->count();
        $ordersThisWeek  = (clone $base)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $ordersThisMonth = (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // Revenue
        $revenueOrderIds = (clone $base)->whereIn('order_status', ['completed', 'approved'])->pluck('id');
        $totalRevenue = DB::table('order_items')
            ->whereIn('order_id', $revenueOrderIds)
            ->where(function ($q) {
                $q->whereNull('remarks')->orWhere('remarks', '!=', 'Item Cancelled');
            })
            ->sum('amount');

        // Recent 7 orders
        $recentOrders = (clone $base)
            ->select('id', 'sof_id', 'customer_name', 'order_status', 'requesting_store', 'channel_order', 'created_at')
            ->orderByDesc('created_at')
            ->limit(7)
            ->get()
            ->map(function ($o) use ($allStoreLocations) {
                $o->store_name = $allStoreLocations[$o->requesting_store] ?? $o->requesting_store;
                return $o;
            });

        // Top stores
        $topStores = (clone $base)
            ->select('requesting_store', DB::raw('COUNT(*) as total'))
            ->groupBy('requesting_store')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($s) use ($allStoreLocations) {
                $s->name = $allStoreLocations[$s->requesting_store] ?? $s->requesting_store;
                return $s;
            });

        // Channel breakdown
        $channelBreakdown = (clone $base)
            ->select('channel_order', DB::raw('COUNT(*) as total'))
            ->whereNotNull('channel_order')
            ->groupBy('channel_order')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Status breakdown
        $statusMap = [
            'New Order'    => ['count' => $counts['new_order_count'],    'color' => '#2563eb'],
            'Pending'      => ['count' => $counts['pending_count'],      'color' => '#ca8a04'],
            'For Approval' => ['count' => $counts['for_approval_count'], 'color' => '#7c3aed'],
            'Approved'     => ['count' => $counts['approved_count'],     'color' => '#047857'],
            'Completed'    => ['count' => $counts['completed_count'],    'color' => '#0d9488'],
            'Rejected'     => ['count' => $counts['rejected_count'],     'color' => '#dc2626'],
            'Cancelled'    => ['count' => $counts['cancelled_count'],    'color' => '#71717a'],
        ];
        $statusBreakdown = collect($statusMap)->filter(fn($v) => $v['count'] > 0);

        // ── Inventory: Store Allocation + WMS Virtual (grouped) ──
        $totalProducts = $lowStockCount = $outOfStockCount = $healthyStockCount = 0;
        $totalStoreAllocation = 0;
        $warehouseName = null;
        $warehouseCode = null;
        $wmsVirtualTotal = 0;
        $wmsSkuCount = 0;
        $wmsZeroCount = 0;
        $wmsLastUpdated = null;

        if ($user->user_location && $user->role !== 'manager') {
            $tbl = 'products_' . strtolower($user->user_location);
            $warehouseCode = $locationToWarehouse[$user->user_location] ?? null;
            $warehouseName = $warehouseNames[$warehouseCode] ?? $warehouseCode;

            if (Schema::connection('mysql')->hasTable($tbl)) {
                // Store allocation stats
                $totalProducts   = DB::connection('mysql')->table($tbl)->whereNull('archived_at')->count();
                $lowStockCount   = DB::connection('mysql')->table($tbl)->whereNull('archived_at')->where('allocation_per_case', '>', 0)->where('allocation_per_case', '<=', 5)->count();
                $outOfStockCount = DB::connection('mysql')->table($tbl)->whereNull('archived_at')->where('allocation_per_case', '<=', 0)->count();
                $healthyStockCount = max(0, $totalProducts - $lowStockCount - $outOfStockCount);

                // Total store allocation (sum of allocation_per_case)
                $totalStoreAllocation = (int) DB::connection('mysql')
                    ->table($tbl)
                    ->whereNull('archived_at')
                    ->sum('allocation_per_case');

                // WMS virtual inventory
                if ($warehouseCode) {
                    $activeSkus = DB::connection('mysql')
                        ->table($tbl)
                        ->whereNull('archived_at')
                        ->pluck('sku')
                        ->map(fn($s) => strtoupper($s));

                    $wmsStats = DB::connection('mysql')
                        ->table('product_wms_allocations')
                        ->where('warehouse_code', $warehouseCode)
                        ->whereIn('sku', $activeSkus)
                        ->selectRaw('
                            COUNT(*) as sku_count,
                            COALESCE(SUM(wms_virtual_allocation), 0) as virtual_total,
                            SUM(CASE WHEN COALESCE(wms_virtual_allocation, 0) <= 0 THEN 1 ELSE 0 END) as zero_count,
                            MAX(updated_at) as last_updated
                        ')
                        ->first();

                    if ($wmsStats) {
                        $wmsSkuCount     = $wmsStats->sku_count ?? 0;
                        $wmsVirtualTotal = $wmsStats->virtual_total ?? 0;
                        $wmsZeroCount    = $wmsStats->zero_count ?? 0;
                        $wmsLastUpdated  = $wmsStats->last_updated ? Carbon::parse($wmsStats->last_updated) : null;
                    }
                }
            }
        }

        return view('dashboard.index', array_merge($counts, compact(
            'totalOrders',
            'ordersToday',
            'ordersThisWeek',
            'ordersThisMonth',
            'totalRevenue',
            'recentOrders',
            'topStores',
            'channelBreakdown',
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
        )));
    }
}
