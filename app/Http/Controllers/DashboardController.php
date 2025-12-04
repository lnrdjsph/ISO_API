<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use Illuminate\Support\Arr;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Base query
        $query = Order::query();

        // Store grouping by region
        $storeMapping = [
            'lz' => ['6012'], // Luzon
            'vs' => ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010'], // Visayas
        ];

        // Restrict accessible orders
        if ($user->role === 'manager') {

            if ($user->user_location && isset($storeMapping[$user->user_location])) {
                $query->whereIn('requesting_store', $storeMapping[$user->user_location]);
            }

        } elseif ($user->role !== 'super admin') {

            // Regular user = single store only
            if ($user->user_location) {
                $query->where('requesting_store', $user->user_location);
            }
        }

        // Clone the base query for each counter
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
