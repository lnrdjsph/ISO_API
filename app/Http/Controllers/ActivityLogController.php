<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->role !== 'super admin') {
            abort(403);
        }

        $query = ActivityLog::with('user')->latest();

        // Category filter (e.g. 'order' matches all 'order.*')
        if ($request->filled('category')) {
            $query->where('action', 'like', $request->category . '.%');
        }

        // Specific action filter
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs    = $query->paginate(25)->withQueryString();
        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $users   = User::orderBy('name')->get(['id', 'name', 'email', 'role']);

        // Build category list from action prefixes (e.g. 'order.created' → 'order')
        $categories = $actions->map(fn($a) => explode('.', $a)[0])->unique()->sort()->values();

        return view('activity_logs.index', compact('logs', 'actions', 'users', 'categories'));
    }
}
