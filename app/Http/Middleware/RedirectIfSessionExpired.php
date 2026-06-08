<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Session\TokenMismatchException;

class RedirectIfSessionExpired
{
    public function handle($request, Closure $next)
    {
        try {
            $response = $next($request);

            // Check for session expiration (419 status code)
            if ($response->getStatusCode() === 419) {
                return $this->handleExpiredSession($request);
            }

            return $response;
        } catch (TokenMismatchException $e) {
            // Catch CSRF token mismatch
            return $this->handleExpiredSession($request);
        }
    }

    private function handleExpiredSession($request)
    {
        // For AJAX/API requests - return JSON instead of HTML
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'session_expired' => true,
                'message' => 'Your session has expired. Please re-login to continue.',
                'redirect' => route('login')
            ], 419);
        }

        // For normal web requests - redirect to login with flash message
        return redirect()->route('login')
            ->with('error', 'Your session has expired. Please re-login to continue.');
    }
}
