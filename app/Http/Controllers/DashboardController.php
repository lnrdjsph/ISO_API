<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;

class DashboardController extends Controller
{
    public function index()
    {
        // Total orders count
        $ordersCount = Order::count();

        // Filtered counts
        $pendingCount   = Order::where('order_status', 'pending')->count();
        $cancelledCount = Order::where('order_status', 'cancelled')->count();
        $completedCount = Order::where('order_status', 'completed')->count();
        $newOrderCount = Order::where('order_status', 'new order')->count();
        $forApprovalCount = Order::where('order_status', 'for approval')->count();

        return view('dashboard.index', compact(
            'ordersCount',
            'pendingCount',
            'cancelledCount',
            'completedCount',
            'newOrderCount',
            'forApprovalCount'

            
        ));
    }
}
