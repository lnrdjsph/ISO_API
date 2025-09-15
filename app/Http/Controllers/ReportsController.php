<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
     * Sales Report (API + Blade)
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

        // ✅ Totals
    $totals = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
        ->whereBetween('order_items.created_at', [$from, $to])
        ->where('order_items.item_type', '!=', 'Freebie') // 🚫 exclude freebies
        ->when($store, $storeFilter)
        ->selectRaw('
            COALESCE(SUM(order_items.amount), 0) as total_sales,
            COUNT(DISTINCT order_items.order_id) as total_orders,
            COALESCE(AVG(order_items.amount), 0) as avg_item_amount
        ')
        ->first();

        // ✅ Sales by day
    $sales = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
        ->whereBetween('order_items.created_at', [$from, $to])
        ->where('order_items.item_type', '!=', 'Freebie') // 🚫 exclude freebies
        ->when($store, $storeFilter)
        ->selectRaw('DATE(order_items.created_at) as day, SUM(order_items.amount) as sales')
        ->groupBy('day')
        ->pluck('sales', 'day');

        $period = CarbonPeriod::create($from, $to);
        $byDay = collect();
        foreach ($period as $date) {
            $day = $date->toDateString();
            $byDay->push([
                'day'   => $day,
                'sales' => (float) ($sales[$day] ?? 0),
            ]);
        }

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

        // ✅ Sales by store (ignore store filter so donut always compares all stores)
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

        // 📊 Data package
        $data = [
            'date_range'     => [$from->toDateString(), $to->toDateString()],
            'totals'         => $totals,
            'sales_by_day'   => $byDay,
            'top_products'   => $topProducts,
            'by_store'       => $byStore,
            'selected_store' => $store,
        ];

        // 🔀 JSON vs Blade
        if ($request->wantsJson()) {
            return response()->json($data);
        }
        
        $stores = Order::select('requesting_store')->distinct()->get();
        return view('reports.sales', array_merge($data, ['stores' => $stores]));    
    }

        /**
     * Freebies Report (API + Blade)
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



}
