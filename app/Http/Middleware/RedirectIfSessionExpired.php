<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfSessionExpired
{
    public function handle($request, Closure $next)
    {
        // If user is not logged in but trying to access an authenticated route
        if (!Auth::check() && $request->expectsHtml()) {
            return redirect()->route('login')
                ->with('error', 'Session expired, please log in again.');
        }

        return $next($request);
    }
}