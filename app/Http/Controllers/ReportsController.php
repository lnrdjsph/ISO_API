<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use App\Support\LocationConfig;

class ReportsController extends Controller
{
    private array $allStoreLocations;

    public function __construct()
    {
        $this->allStoreLocations = LocationConfig::stores();
    }

    /**
     * Combined Sales & Freebies Report Dashboard
     */
    public function salesReport(Request $request)
    {
        // 📅 Filters (defaults = current month)
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now();

        // 🏬 Store filter
        $store = $request->input('store'); // nullable, means "all stores"

        // Helper: conditionally apply store filter
        $storeFilter = function ($query) use ($store) {
            if ($store) {
                $query->where('orders.requesting_store', $store);
            }
        };

        // ===== SALES DATA =====

        // ✅ Sales Totals
        $totals = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->when($store, $storeFilter)
            ->selectRaw('
                COALESCE(SUM(order_items.amount), 0) as total_sales,
                COUNT(DISTINCT order_items.order_id) as total_orders,
                COALESCE(AVG(order_items.amount), 0) as avg_item_amount
            ')
            ->first() ?? (object)[
                'total_sales'   => 0,
                'total_orders'  => 0,
                'avg_item_amount' => 0,
            ];


        // ✅ Sales by day
        $sales = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE') // 🚫 exclude freebies
            ->when($store, $storeFilter)
            ->selectRaw('DATE(orders.time_order) as day, SUM(order_items.amount) as sales')
            ->groupBy('day')
            ->pluck('sales', 'day');

        $period = CarbonPeriod::create($from, $to);
        $salesByDay = collect();
        foreach ($period as $date) {
            $day = $date->toDateString();
            $salesByDay->push([
                'day'   => $day,
                'sales' => (float) ($sales[$day] ?? 0),
            ]);
        }

        // ✅ Sales by Store Over Time
        $salesByStoreOverTime = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE') // 🚫 exclude freebies
            ->selectRaw('
                DATE(orders.time_order) as day,
                COALESCE(orders.requesting_store, "Unknown Store") as store,
                SUM(order_items.amount) as sales
            ')
            ->groupBy('day', 'orders.requesting_store')
            ->orderBy('day')
            ->get();

        // 🔄 Format as [Store Name => [day => sales]]
        $storesList = $salesByStoreOverTime->groupBy('store')->mapWithKeys(function ($rows, $storeCode) use ($from, $to) {
            $period = CarbonPeriod::create($from, $to);
            $dailyData = collect();

            foreach ($period as $date) {
                $day = $date->toDateString();
                $row = $rows->firstWhere('day', $day);
                $dailyData->push((object)[
                    'day'   => $day,
                    'sales' => (float) ($row->sales ?? 0),
                ]);
            }

            // 🔑 Map store code -> store name if found in $allStoreLocations
            // FIX: The allStoreLocations array keys are NOT lowercase, so don't lowercase the lookup
            $storeName = $this->allStoreLocations[$storeCode] ?? $storeCode;

            return [$storeName => $dailyData];
        });


        // ✅ Top products
        $topProducts = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE') // 🚫 exclude freebies
            ->when($store, $storeFilter)
            ->selectRaw('
                order_items.sku, 
                order_items.item_description, 
                SUM(order_items.amount) as total_sales, 
                SUM(order_items.total_qty) as total_qty
            ')
            ->groupBy('order_items.sku', 'order_items.item_description')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        if ($topProducts->isEmpty()) {
            $topProducts = collect([[
                'sku'              => null,
                'item_description' => 'No Data',
                'total_sales'      => 0,
                'total_qty'        => 0,
            ]]);
        }

        // ✅ Sales by store (ignore store filter for comparison)
        $byStore = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE') // 🚫 exclude freebies
            ->selectRaw('
                COALESCE(orders.requesting_store, "Unknown Store") as store,
                CAST(SUM(order_items.amount) AS DECIMAL(15,2)) as total_sales
            ')
            ->groupBy('orders.requesting_store')
            ->orderByDesc('total_sales')
            ->get();

        $byStore = $byStore->map(function ($row) {
            $row->store_name = $this->allStoreLocations[strtolower($row->store)] ?? $row->store;
            return $row;
        });

        // ===== FREEBIES DATA =====

        // ✅ Freebies Totals
        $freebieTotals = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                COALESCE(SUM(order_items.total_qty), 0) as total_freebies_qty,
                COALESCE(SUM(order_items.amount), 0) as total_freebies_value,
                COUNT(DISTINCT order_items.order_id) as orders_with_freebies
            ')
            ->first();

        // ✅ Freebies by day
        $freebies = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('DATE(orders.time_order) as day, SUM(order_items.total_qty) as qty, SUM(order_items.amount) as amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $freebiesByDay = collect();
        foreach ($period as $date) {
            $day = $date->toDateString();
            $freebiesByDay->push([
                'day'    => $day,
                'qty'    => (int) ($freebies[$day]->qty ?? 0),
                'amount' => (float) ($freebies[$day]->amount ?? 0),
            ]);
        }

        // ✅ Top Freebie Products
        $topFreebies = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                order_items.sku, 
                order_items.item_description, 
                SUM(order_items.total_qty) as total_qty, 
                SUM(order_items.amount) as total_value
            ')
            ->groupBy('order_items.sku', 'order_items.item_description')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        if ($topFreebies->isEmpty()) {
            $topFreebies = collect([[
                'sku'              => null,
                'item_description' => 'No Freebies',
                'total_qty'        => 0,
                'total_value'      => 0,
            ]]);
        }

        // ✅ Freebies by Store
        $freebiesByStore = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                COALESCE(orders.requesting_store, "Unknown Store") as store,
                SUM(order_items.amount) as total_amount
            ')
            ->groupBy('orders.requesting_store')
            ->orderByDesc('total_amount')
            ->get();

        $freebiesByStore = $freebiesByStore->map(function ($row) {
            $row->store_name = $this->allStoreLocations[strtolower($row->store)] ?? $row->store;
            return $row;
        });

        // ✅ Orders with totals
        $orders = Order::leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                orders.id,
                orders.sof_id,
                orders.customer_name,
                orders.channel_order,
                orders.time_order,
                orders.delivery_date,
                orders.requesting_store,
                SUM(CASE WHEN order_items.item_type != "FREEBIE" THEN order_items.amount ELSE 0 END) as grand_total,
                SUM(CASE WHEN order_items.item_type = "FREEBIE" THEN order_items.amount ELSE 0 END) as total_freebies
            ')
            ->groupBy(
                'orders.id',
                'orders.sof_id',
                'orders.customer_name',
                'orders.channel_order',
                'orders.time_order',
                'orders.delivery_date',
                'orders.requesting_store'
            )
            ->orderByDesc('orders.time_order')
            ->get()
            ->map(function ($order) {
                $order->total_payable = $order->grand_total - $order->total_freebies;
                return $order;
            });


        // 📊 Combined Data package
        $data = [
            'date_range'          => [$from->toDateString(), $to->toDateString()],
            'totals'              => $totals,
            'sales_by_day'        => $salesByDay,
            'by_store_over_time' => $storesList,
            'top_products'        => $topProducts,
            'by_store'            => $byStore,
            'freebie_totals'      => $freebieTotals,
            'freebies_by_day'     => $freebiesByDay,
            'top_freebies'        => $topFreebies,
            'freebies_by_store'   => $freebiesByStore,
            'orders'              => $orders,
            'selected_store'      => $store,
        ];

        // 🔀 JSON vs Blade
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        $stores = Order::select('requesting_store')->distinct()->get();
        return view('reports.sales', array_merge($data, ['stores' => $stores]));
    }

    public function paymentReport(Request $request)
    {
        // 📅 Filters (defaults = current month) - FIXED: Ensure end date includes full day
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $store = $request->input('store');
        $mode  = $request->input('mode_payment');

        // ===== PAYMENT DATA =====

        // ✅ Enhanced Totals with more metrics
        $totals = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->when($mode, function ($q) use ($mode) {
                return $q->where('orders.mode_payment', $mode);
            })
            ->selectRaw('
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as total_sales,
            COUNT(DISTINCT orders.id) as total_orders,
            COALESCE(AVG(COALESCE(order_items.amount, 0)), 0) as avg_order_value,
            COUNT(order_items.id) as total_items_sold
        ')
            ->first();

        // Previous period comparison for growth metrics
        $previousPeriod = $from->copy()->subDays($to->diffInDays($from) + 1);
        $previousTotals = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$previousPeriod, $from->copy()->subDay()])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->when($mode, function ($q) use ($mode) {
                return $q->where('orders.mode_payment', $mode);
            })
            ->selectRaw('
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as total_sales,
            COUNT(DISTINCT orders.id) as total_orders
        ')
            ->first();

        // Calculate growth percentages
        $salesGrowth = $previousTotals && $previousTotals->total_sales > 0
            ? (($totals->total_sales - $previousTotals->total_sales) / $previousTotals->total_sales) * 100
            : 0;

        $ordersGrowth = $previousTotals && $previousTotals->total_orders > 0
            ? (($totals->total_orders - $previousTotals->total_orders) / $previousTotals->total_orders) * 100
            : 0;

        // Convert totals to object with growth metrics
        $totals = (object)[
            'total_sales'       => (float) ($totals->total_sales ?? 0),
            'total_orders'      => (int) ($totals->total_orders ?? 0),
            'avg_order_value'   => (float) ($totals->avg_order_value ?? 0),
            'total_items_sold'  => (int) ($totals->total_items_sold ?? 0),
            'sales_growth'      => round($salesGrowth, 2),
            'orders_growth'     => round($ordersGrowth, 2),
        ];

        // ✅ Sales by Mode of Payment - Show ALL payment modes, even if they have $0 sales
        $byModeData = DB::table('orders')
            ->leftJoin('order_items', function ($join) {
                $join->on('orders.id', '=', 'order_items.order_id')
                    ->where('order_items.item_type', '!=', 'FREEBIE');
            })
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->when($mode, function ($q) use ($mode) {
                return $q->where('orders.mode_payment', $mode);
            })
            ->selectRaw('
            TRIM(COALESCE(orders.mode_payment, "Unspecified")) as mode_payment,
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as total_sales,
            COUNT(DISTINCT orders.id) as order_count
        ')
            ->groupBy('orders.mode_payment')
            ->get();

        // Format for charts with additional metrics
        $byMode = $byModeData->map(function ($row) {
            return (object)[
                'mode_payment' => $row->mode_payment ?? 'Unspecified',
                'total_sales' => (float) ($row->total_sales ?? 0),
                'order_count' => (int) ($row->order_count ?? 0)
            ];
        });

        // If no data, add empty entries for common payment modes
        if ($byMode->isEmpty()) {
            $byMode = collect([
                (object)['mode_payment' => 'Cash / Bank Card', 'total_sales' => 0, 'order_count' => 0],
                (object)['mode_payment' => 'PO15%', 'total_sales' => 0, 'order_count' => 0]
            ]);
        }

        // ✅ Sales by Store with Payment Mode breakdown
        $byStoreMode = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->when($mode, function ($q) use ($mode) {
                return $q->where('orders.mode_payment', $mode);
            })
            ->selectRaw('
            orders.requesting_store,
            TRIM(COALESCE(orders.mode_payment, "Unspecified")) as mode_payment,
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as total_sales,
            COUNT(DISTINCT orders.id) as order_count
        ')
            ->groupBy('orders.requesting_store', 'orders.mode_payment')
            ->orderBy('total_sales', 'desc')
            ->get()
            ->groupBy('requesting_store');

        // ✅ Basic Sales by Store (for backward compatibility)
        $byStore = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->when($mode, function ($q) use ($mode) {
                return $q->where('orders.mode_payment', $mode);
            })
            ->selectRaw('
            orders.requesting_store,
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as total_sales,
            COUNT(DISTINCT orders.id) as order_count
        ')
            ->groupBy('orders.requesting_store')
            ->orderBy('total_sales', 'desc')
            ->get()
            ->map(function ($row) {
                return (object)[
                    'store' => $row->requesting_store,
                    'total_sales' => (float) ($row->total_sales ?? 0),
                    'order_count' => (int) ($row->order_count ?? 0)
                ];
            });

        // ✅ Hourly Distribution by Payment Mode
        $byHourMode = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->selectRaw('
            HOUR(orders.time_order) as hour,
            TRIM(COALESCE(orders.mode_payment, "Unspecified")) as mode_payment,
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as sales,
            COUNT(DISTINCT orders.id) as orders
        ')
            ->groupBy(DB::raw('HOUR(orders.time_order)'), 'orders.mode_payment')
            ->get()
            ->groupBy('mode_payment');

        // Fill missing hours with zeros for each payment mode
        $hourlyDataByMode = collect();
        $allPaymentModes = $byHourMode->keys();

        foreach ($allPaymentModes as $paymentMode) {
            $modeHourlyData = collect();
            $rawHourData = $byHourMode[$paymentMode]->keyBy('hour');

            for ($hour = 0; $hour < 24; $hour++) {
                $modeHourlyData->push([
                    'hour' => sprintf('%02d:00', $hour),
                    'sales' => (float) ($rawHourData[$hour]->sales ?? 0),
                    'orders' => (int) ($rawHourData[$hour]->orders ?? 0)
                ]);
            }

            $hourlyDataByMode->put($paymentMode, $modeHourlyData);
        }

        // Keep original combined hourly data
        $hourlyData = collect();
        for ($hour = 0; $hour < 24; $hour++) {
            $totalSales = $allPaymentModes->sum(function ($mode) use ($byHourMode, $hour) {
                $modeData = $byHourMode[$mode]->keyBy('hour');
                return (float) ($modeData[$hour]->sales ?? 0);
            });
            $totalOrders = $allPaymentModes->sum(function ($mode) use ($byHourMode, $hour) {
                $modeData = $byHourMode[$mode]->keyBy('hour');
                return (int) ($modeData[$hour]->orders ?? 0);
            });

            $hourlyData->push([
                'hour' => sprintf('%02d:00', $hour),
                'sales' => $totalSales,
                'orders' => $totalOrders
            ]);
        }

        // ✅ Daily Sales Trend by Payment Mode (Enhanced)
        $salesByDayModeRaw = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->where('order_items.item_type', '!=', 'FREEBIE')
            ->where('orders.order_status', '!=', 'cancelled')
            ->when($store, function ($q) use ($store) {
                return $q->where('orders.requesting_store', $store);
            })
            ->selectRaw('
            DATE(orders.time_order) as day,
            TRIM(COALESCE(orders.mode_payment, "Unspecified")) as mode_payment,
            COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as sales,
            COUNT(DISTINCT orders.id) as orders
        ')
            ->groupBy(DB::raw('DATE(orders.time_order)'), 'orders.mode_payment')
            ->get()
            ->groupBy('mode_payment');

        // Get all unique payment modes
        $paymentModes = $salesByDayModeRaw->keys();

        // Create daily data with all dates in range, separated by payment mode
        $period = CarbonPeriod::create($from->toDateString(), $to->toDateString());
        $dailyDataByMode = collect();

        foreach ($paymentModes as $paymentMode) {
            $modeData = collect();
            $rawModeData = $salesByDayModeRaw[$paymentMode]->keyBy('day');

            foreach ($period as $date) {
                $day = $date->toDateString();
                $dayData = $rawModeData[$day] ?? null;
                $modeData->push([
                    'day'    => $day,
                    'sales'  => (float) ($dayData->sales ?? 0),
                    'orders' => (int) ($dayData->orders ?? 0),
                ]);
            }

            $dailyDataByMode->put($paymentMode, $modeData);
        }

        // Also keep the original combined daily data for backward compatibility
        $dailyData = collect();
        foreach ($period as $date) {
            $day = $date->toDateString();
            $totalSales = $paymentModes->sum(function ($mode) use ($salesByDayModeRaw, $day) {
                $modeData = $salesByDayModeRaw[$mode]->keyBy('day');
                return (float) ($modeData[$day]->sales ?? 0);
            });
            $totalOrders = $paymentModes->sum(function ($mode) use ($salesByDayModeRaw, $day) {
                $modeData = $salesByDayModeRaw[$mode]->keyBy('day');
                return (int) ($modeData[$day]->orders ?? 0);
            });

            $dailyData->push([
                'day'    => $day,
                'sales'  => $totalSales,
                'orders' => $totalOrders,
            ]);
        }

        // // ✅ Top Products (if you want to add this)
        // $topProducts = DB::table('order_items')
        //     ->join('orders', 'orders.id', '=', 'order_items.order_id')
        //     ->whereBetween('orders.time_order', [$from, $to])
        //     ->where('order_items.item_type', '!=', 'FREEBIE')
        //     ->where('orders.order_status', '!=', 'cancelled')
        //     ->when($store, function($q) use ($store) {
        //         return $q->where('orders.requesting_store', $store);
        //     })
        //     ->when($mode, function($q) use ($mode) {
        //         return $q->where('orders.mode_payment', $mode);
        //     })
        //     ->selectRaw('
        //         order_items.item_description,
        //         COALESCE(SUM(COALESCE(order_items.amount, 0)), 0) as total_sales,
        //         SUM(order_items.total_qty) as total_qty
        //     ')
        //     ->groupBy('order_items.item_description')
        //     ->orderBy('total_sales', 'desc')
        //     ->limit(10)
        //     ->get()
        //     ->map(function($row) {
        //         return (object)[
        //             'product' => $row->item_description,
        //             'total_sales' => (float) ($row->total_sales ?? 0),
        //             'total_qty' => (int) ($row->total_qty ?? 0)
        //         ];
        //     });

        // ✅ Dropdown filter values
        $stores = DB::table('orders')
            ->select('requesting_store')
            ->distinct()
            ->whereNotNull('requesting_store')
            ->pluck('requesting_store')
            ->filter();

        $modes = DB::table('orders')
            ->select('mode_payment')
            ->distinct()
            ->whereNotNull('mode_payment')
            ->pluck('mode_payment')
            ->filter();

        // 📊 Final Data with new mode-based comparisons
        $data = [
            'date_range'            => [$from->toDateString(), $to->toDateString()],
            'totals'                => $totals,
            'by_mode'               => $byMode,
            'by_store'              => $byStore,
            'by_store_mode'         => $byStoreMode,
            'hourly_data'           => $hourlyData,
            'hourly_data_by_mode'   => $hourlyDataByMode,
            'sales_by_day'          => $dailyData,
            'sales_by_day_by_mode'  => $dailyDataByMode,
            'payment_modes'         => $paymentModes,
            // 'top_products'          => $topProducts,
            'stores'                => $stores,
            'modes'                 => $modes,
            'selected_store'        => $store,
            'selected_mode'         => $mode,
        ];

        // 🔀 JSON vs Blade
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.payment', $data);
    }



    public function ordersReport(Request $request)
    {
        $user = auth()->user();
        $query = Order::query()->with('items');

        // 🗺️ Region -> stores mapping
        $storeMapping = [
            'lz' => ['6012'], // Luzon = only Antipolo
            'vs' => ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010'], // Visayas = everything EXCEPT Antipolo
        ];

        $allowedStatuses = null;

        // 👔 Manager restrictions
        if ($user->role === 'manager') {
            $allowedStatuses = ['for approval', 'approved', 'rejected'];
            $query->whereIn('order_status', $allowedStatuses);

            if ($user->user_location && isset($storeMapping[$user->user_location])) {
                $query->whereIn('requesting_store', $storeMapping[$user->user_location]);
            }
        } else {
            if ($user->user_location) {
                $query->where('requesting_store', $user->user_location);
            }
        }

        // 🔎 Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('sof_id', 'like', "%{$search}%")
                    ->orWhere('requesting_store', 'like', "%{$search}%");
            });
        }

        // 📦 Channel filter
        if ($channel = $request->input('channel')) {
            $query->where('channel_order', $channel);
        }

        // 🏬 Store filter
        if ($storeCode = $request->input('store_code')) {
            $query->where('requesting_store', $storeCode);
        }

        // ✅ Status filter
        if ($status = $request->input('status')) {
            if ($allowedStatuses) {
                if (in_array($status, $allowedStatuses)) {
                    $query->where('order_status', $status);
                }
            } else {
                $query->where('order_status', $status);
            }
        }

        // 📅 Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('time_order', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('time_order', '<=', $endDate);
        }

        // ✅ Pagination
        $perPage = $request->input('per_page', 10);

        $orders = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        // ➕ Add computed amounts
        // After transforming each order with payable/freebies/grand total
        $orders->getCollection()->transform(function ($order) {
            $payableAmount = $order->items->where('item_type', 'MAIN')->sum('amount');
            $freebiesAmount = $order->items->where('item_type', 'FREEBIE')->sum('amount');
            $grandTotal = $payableAmount + $freebiesAmount;

            $order->payable_amount = $payableAmount;
            $order->freebies_amount = $freebiesAmount;
            $order->grand_total = $grandTotal;

            return $order;
        });

        // 📊 Compute totals across the current page
        $totals = [
            'payable'  => $orders->sum('payable_amount'),
            'freebies' => $orders->sum('freebies_amount'),
            'grand'    => $orders->sum('grand_total'),
        ];


        // Dropdowns
        $channels = Order::select('channel_order')->distinct()->pluck('channel_order');
        $statuses = $allowedStatuses ?? Order::select('order_status')->distinct()->pluck('order_status');

        $storeLocations = LocationConfig::accessibleStores($user->role, $user->user_location);


        return view('reports.orders_report', compact('orders', 'channels', 'statuses', 'perPage', 'storeLocations', 'totals'));
    }





    public function exportOrdersReport(Request $request)
    {
        $user = auth()->user();
        $query = Order::query()->with('items');

        $allowedStatuses = null;
        if ($user->user_location) {
            $stores = LocationConfig::regionStores($user->user_location);
            if (!empty($stores)) {
                $query->whereIn('requesting_store', $stores);
            }
        }

        // 🔎 Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('sof_id', 'like', "%{$search}%")
                    ->orWhere('requesting_store', 'like', "%{$search}%");
            });
        }

        // 📦 Channel filter
        if ($channel = $request->input('channel')) {
            $query->where('channel_order', $channel);
        }

        // 🏬 Store filter
        if ($storeCode = $request->input('store_code')) {
            $query->where('requesting_store', $storeCode);
        }

        // ✅ Status filter
        if ($status = $request->input('status')) {
            if ($allowedStatuses) {
                if (in_array($status, $allowedStatuses)) {
                    $query->where('order_status', $status);
                }
            } else {
                $query->where('order_status', $status);
            }
        }

        // 📅 Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('time_order', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('time_order', '<=', $endDate);
        }

        $orders = $query->orderByDesc('created_at')->get();

        // ➕ Add computed amounts
        $orders->transform(function ($order) {
            $payableAmount = $order->items->where('item_type', 'MAIN')->sum('amount');
            $freebiesAmount = $order->items->where('item_type', 'FREEBIE')->sum('amount');
            $grandTotal = $payableAmount + $freebiesAmount;

            $order->payable_amount = $payableAmount;
            $order->freebies_amount = $freebiesAmount;
            $order->grand_total = $grandTotal;

            return $order;
        });

        // 📊 Totals
        $totals = [
            'payable'  => $orders->sum('payable_amount'),
            'freebies' => $orders->sum('freebies_amount'),
            'grand'    => $orders->sum('grand_total'),
        ];

        // 📝 Prepare CSV
        $filename = "orders_report_" . now()->format('Ymd_His') . ".csv";

        $handle = fopen('php://output', 'w');
        ob_start();

        // 👉 Header row
        fputcsv($handle, [
            'Order #',
            'Customer',
            'Channel',
            'Order Date',
            'Requesting Store',
            'Payable Amount',
            'Freebies Amount',
            'Grand Total'
        ]);

        // 👉 Data rows
        foreach ($orders as $order) {
            $storeName = $this->allStoreLocations[$order->requesting_store] ?? $order->requesting_store;

            fputcsv($handle, [
                $order->sof_id,
                $order->customer_name,
                $order->channel_order,
                \Carbon\Carbon::parse($order->time_order)->format('Y-m-d'),
                $storeName,
                number_format($order->payable_amount, 2),
                number_format($order->freebies_amount, 2),
                number_format($order->grand_total, 2),
            ]);
        }

        // 👉 Totals row
        fputcsv($handle, []);
        fputcsv($handle, [
            '',
            '',
            '',
            '',
            'TOTALS',
            number_format($totals['payable'], 2),
            number_format($totals['freebies'], 2),
            number_format($totals['grand'], 2),
        ]);

        $csvOutput = ob_get_clean();
        fclose($handle);

        return response($csvOutput)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }





    /**
     * Separate Freebies Report (if still needed)
     * This maintains the original freebies-only endpoint
     */
    public function freebiesReport(Request $request)
    {
        // 📅 Filters (defaults = current month)
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now();

        // 🏬 Store filter
        $store = $request->input('store'); // null = all stores
        $storeFilter = function ($query) use ($store) {
            if ($store) {
                $query->where('orders.requesting_store', $store);
            }
        };

        // ✅ Totals
        $totals = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                COALESCE(SUM(order_items.total_qty), 0) as total_freebies_qty,
                COALESCE(SUM(order_items.amount), 0) as total_freebies_value,
                COUNT(DISTINCT order_items.order_id) as orders_with_freebies
            ')
            ->first();

        // ✅ Freebies by day
        $freebies = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('DATE(orders.time_order) as day, SUM(order_items.total_qty) as qty, SUM(order_items.amount) as amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $period = CarbonPeriod::create($from, $to);
        $byDay = collect();
        foreach ($period as $date) {
            $day = $date->toDateString();
            $byDay->push([
                'day'    => $day,
                'qty'    => (int) ($freebies[$day]->qty ?? 0),
                'amount' => (float) ($freebies[$day]->amount ?? 0),
            ]);
        }

        // ✅ Top Freebie Products
        $topFreebies = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                order_items.sku, 
                order_items.item_description, 
                SUM(order_items.total_qty) as total_qty, 
                SUM(order_items.amount) as total_value
            ')
            ->groupBy('order_items.sku', 'order_items.item_description')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        if ($topFreebies->isEmpty()) {
            $topFreebies = collect([[
                'sku'              => null,
                'item_description' => 'No Freebies',
                'total_qty'        => 0,
                'total_value'      => 0,
            ]]);
        }

        // ✅ Freebies by Store
        $byStore = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.item_type', 'FREEBIE')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                COALESCE(orders.requesting_store, "Unknown Store") as store,
                SUM(order_items.amount) as total_amount
            ')
            ->groupBy('orders.requesting_store')
            ->orderByDesc('total_amount')
            ->get();

        $byStore = $byStore->map(function ($row) {
            $row->store_name = $this->allStoreLocations[strtolower($row->store)] ?? $row->store;
            return $row;
        });

        // 📊 Data package
        $data = [
            'date_range'   => [$from->toDateString(), $to->toDateString()],
            'totals'       => $totals,
            'byDay'        => $byDay,
            'top_freebies' => $topFreebies,
            'byStore'        => $byStore,
            'selected_store' => $store,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        // 🏬 Pass available stores for dropdown
        $stores = Order::select('requesting_store')->distinct()->get();

        return view('reports.freebies', array_merge($data, ['stores' => $stores]));
    }



    public function exportCsv(Request $request)
    {
        // 📅 Date range options
        $dateRangeType = $request->input('date_range_type', 'this_month');

        switch ($dateRangeType) {
            case 'all':
                $from = Carbon::minValue();
                $to = Carbon::maxValue();
                break;

            case 'today':
                $from = Carbon::today()->startOfDay();
                $to = Carbon::today()->endOfDay();
                break;

            case 'custom':
                $from = $request->input('from')
                    ? Carbon::parse($request->input('from'))->startOfDay()
                    : Carbon::now()->startOfMonth();
                $to = $request->input('to')
                    ? Carbon::parse($request->input('to'))->endOfDay()
                    : Carbon::now();
                break;

            default: // this_month
                $from = Carbon::now()->startOfMonth();
                $to = Carbon::now();
        }

        $store = $request->input('store');
        $channel = $request->input('channel_order');

        // 📝 Columns selected (default if none provided)
        $selectedColumns = $request->input('columns', [
            'sof_id',
            'customer_name',
            'channel_order',
            'time_order',
            'requesting_store',
            'grand_total',
            'total_payable',
            'total_freebies'
        ]);

        // 📊 Query orders
        $orders = Order::leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.time_order', [$from, $to])
            ->when($store, fn($q) => $q->where('orders.requesting_store', $store))
            ->when($channel, fn($q) => $q->where('orders.channel_order', $channel))
            ->selectRaw('
                orders.sof_id,
                orders.customer_name,
                orders.channel_order,
                orders.time_order,
                orders.requesting_store,
                SUM(order_items.amount) as grand_total,
                SUM(CASE WHEN order_items.item_type != "FREEBIE" THEN order_items.amount ELSE 0 END) as total_payable,
                SUM(CASE WHEN order_items.item_type = "FREEBIE" THEN order_items.amount ELSE 0 END) as total_freebies
            ')
            ->groupBy(
                'orders.sof_id',
                'orders.customer_name',
                'orders.channel_order',
                'orders.time_order',
                'orders.requesting_store'
            )
            ->orderByDesc('orders.time_order')
            ->get();

        // Mapping: DB field → CSV header
        $columnHeaders = [
            'sof_id'         => 'SOF ID',
            'customer_name'  => 'Customer Name',
            'channel_order'  => 'Channel',
            'time_order'     => 'Order Date',
            'requesting_store' => 'Store',
            'grand_total'    => 'Grand Total',
            'total_payable'  => 'Total Payable Amount',
            'total_freebies' => 'Total Freebies Amount',
        ];

        // Store mapping
        $stores = LocationConfig::stores();

        // Dynamic filename based on range
        $filename = match ($dateRangeType) {
            'today'      => 'sales_report_' . Carbon::now()->format('Y_m_d') . '.csv',
            'this_month' => 'sales_report_' . Carbon::now()->format('Y_m') . '.csv',
            'this_year'  => 'sales_report_' . Carbon::now()->format('Y') . '.csv',
            'custom'     => 'sales_report_' . ($request->input('from') ?? 'custom') . '_to_' . ($request->input('to') ?? 'custom') . '.csv',
            default      => 'sales_report.csv',
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders, $selectedColumns, $columnHeaders, $stores) {
            $file = fopen('php://output', 'w');

            // CSV header row
            fputcsv($file, array_map(fn($col) => $columnHeaders[$col], $selectedColumns));

            // Totals
            $grandTotalSum = 0;
            $payableSum = 0;
            $freebiesSum = 0;

            foreach ($orders as $order) {
                $row = [];
                foreach ($selectedColumns as $col) {
                    if (in_array($col, ['grand_total', 'total_payable', 'total_freebies'])) {
                        $value = (float) $order->$col;
                        $row[] = number_format($value, 2);

                        if ($col === 'grand_total') $grandTotalSum += $value;
                        if ($col === 'total_payable') $payableSum += $value;
                        if ($col === 'total_freebies') $freebiesSum += $value;
                    } elseif ($col === 'time_order') {
                        $row[] = Carbon::parse($order->$col)->format('Y-m-d H:i:s');
                    } elseif ($col === 'requesting_store') {
                        $row[] = $stores[$order->$col] ?? $order->$col;
                    } else {
                        $row[] = $order->$col;
                    }
                }
                fputcsv($file, $row);
            }

            // 📌 Add totals row at bottom
            $totalsRow = [];
            foreach ($selectedColumns as $col) {
                if ($col === 'grand_total') {
                    $totalsRow[] = number_format($grandTotalSum, 2);
                } elseif ($col === 'total_payable') {
                    $totalsRow[] = number_format($payableSum, 2);
                } elseif ($col === 'total_freebies') {
                    $totalsRow[] = number_format($freebiesSum, 2);
                } else {
                    $totalsRow[] = ''; // leave other cols empty
                }
            }

            // Label row (e.g. "TOTAL" under SOF ID)
            if (in_array('sof_id', $selectedColumns)) {
                $totalsRow[array_search('sof_id', $selectedColumns)] = 'TOTAL';
            }

            fputcsv($file, []); // blank line before totals
            fputcsv($file, $totalsRow);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
