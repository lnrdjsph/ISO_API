<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Response;


class ReportsController extends Controller
{
    private array $allStoreLocations = [
        'f2'  => 'F2 - Metro Wholesalemart Colon',
        's10' => 'S10 - Metro Maasin',
        's17' => 'S17 - Metro Tacloban',
        's19' => 'S19 - Metro Bay-Bay',
        'f18' => 'F18 - Metro Alang-Alang',
        'f19' => 'F19 - Metro Hilongos',
        's8'  => 'S8 - Metro Toledo',
        'h8'  => 'H8 - Super Metro Antipolo',
        'h9'  => 'H9 - Super Metro Carcar',
        'h10' => 'H10 - Super Metro Bogo',
    ];

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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->where('order_items.item_type', '!=', 'Freebie')
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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->where('order_items.item_type', '!=', 'Freebie') // 🚫 exclude freebies
            ->when($store, $storeFilter)
            ->selectRaw('DATE(order_items.created_at) as day, SUM(order_items.amount) as sales')
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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->where('order_items.item_type', '!=', 'Freebie') // 🚫 exclude freebies
            ->selectRaw('
                DATE(order_items.created_at) as day,
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
            $storeName = $this->allStoreLocations[strtolower($storeCode)] ?? $storeCode;

            return [$storeName => $dailyData];
        });


        // ✅ Top products
        $topProducts = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('order_items.created_at', [$from, $to])
            ->where('order_items.item_type', '!=', 'Freebie') // 🚫 exclude freebies
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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->where('order_items.item_type', '!=', 'Freebie') // 🚫 exclude freebies
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
            ->whereBetween('order_items.created_at', [$from, $to])
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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('DATE(order_items.created_at) as day, SUM(order_items.total_qty) as qty, SUM(order_items.amount) as amount')
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
            ->whereBetween('order_items.created_at', [$from, $to])
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
            ->whereBetween('order_items.created_at', [$from, $to])
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
            ->whereBetween('order_items.created_at', [$from, $to])
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
            ->whereBetween('order_items.created_at', [$from, $to])
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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('DATE(order_items.created_at) as day, SUM(order_items.total_qty) as qty, SUM(order_items.amount) as amount')
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
            ->whereBetween('order_items.created_at', [$from, $to])
            ->when($store, $storeFilter)
            ->selectRaw('
                order_items.sku, 
                order_items.item_description, 
                SUM(order_items.total_qty) as total_qty, 
                SUM(order_items.amount) as total_value
            ')
            ->groupBy('order_items.sku','order_items.item_description')
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
            ->whereBetween('order_items.created_at', [$from, $to])
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
        'sof_id', 'customer_name', 'channel_order', 'time_order',
        'requesting_store', 'grand_total', 'total_payable', 'total_freebies'
    ]);

    // 📊 Query orders
    $orders = Order::leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
        ->whereBetween('order_items.created_at', [$from, $to])
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
    $stores = [
        'f2'  => 'F2 - Metro Wholesalemart Colon',
        's10' => 'S10 - Metro Maasin',
        's17' => 'S17 - Metro Tacloban',
        's19' => 'S19 - Metro Bay-Bay',
        'f18' => 'F18 - Metro Alang-Alang',
        'f19' => 'F19 - Metro Hilongos',
        's8'  => 'S8 - Metro Toledo',
        'h8'  => 'H8 - Super Metro Antipolo',
        'h9'  => 'H9 - Super Metro Carcar',
        'h10' => 'H10 - Super Metro Bogo',
    ];

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