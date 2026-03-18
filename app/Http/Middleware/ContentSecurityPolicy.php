<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     * Generates a per-request nonce and injects it into all views via $cspNonce.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a cryptographically secure nonce for this request
        $nonce = base64_encode(random_bytes(16));

        // Share nonce with all Blade views
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",

            // ✅ Nonce replaces unsafe-inline for scripts
            "script-src 'self' 'nonce-{$nonce}' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",

            // ✅ Nonce replaces unsafe-inline for styles
            "style-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",

            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-src 'none'",
            "worker-src 'self'",
            "manifest-src 'self'",
        ]));

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        return $response;
    }
}
