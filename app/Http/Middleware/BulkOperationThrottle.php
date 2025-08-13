<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class BulkOperationThrottle
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 10, $decayMinutes = 10)
    {
        $user = $request->user();

        // Skip throttling if user is admin
        if ($user && ($user->role === 'admin' || $user->role === 'super admin')) {
            return $next($request);
        }

        $key = 'bulk_operation:' . $request->ip() . ':' . ($user?->id ?? 'guest');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many bulk operations. Please try again in {$seconds} seconds.",
                'retry_after' => $seconds
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60); // decayMinutes to seconds

        return $next($request);
    }
}
