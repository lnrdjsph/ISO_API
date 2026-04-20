<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Support\LocationConfig;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Order::query();

        if ($user->role === 'manager') {
            if ($user->user_location) {
                $stores = LocationConfig::regionStores($user->user_location);
                if (!empty($stores)) {
                    $query->whereIn('requesting_store', $stores);
                }
            }
        } elseif ($user->role !== 'super admin') {
            if ($user->user_location) {
                $query->where('requesting_store', $user->user_location);
            }
        }

        $base = clone $query;

        $pendingCount     = (clone $base)->where('order_status', 'pending')->count();
        $cancelledCount   = (clone $base)->where('order_status', 'cancelled')->count();
        $newOrderCount    = (clone $base)->where('order_status', 'new order')->count();
        $forApprovalCount = (clone $base)->where('order_status', 'for approval')->count();

        return view('dashboard.index', compact(
            'pendingCount',
            'cancelledCount',
            'newOrderCount',
            'forApprovalCount',
        ));
    }
}
