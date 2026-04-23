<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicyWithEval
{
    /**
     * CSP variant for pages that require unsafe-eval (ApexCharts, form submissions, etc.)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow CSP to be disabled for specific routes if needed
        if ($request->routeIs('orders.approve')) {
            // For approve route, we can use a more permissive policy
            $nonce = base64_encode(random_bytes(16));
            View::share('cspNonce', $nonce);

            $response = $next($request);

            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                "img-src 'self' data: blob:",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "frame-src 'none'",
                "worker-src 'self' blob:",
                "manifest-src 'self'",
            ]));

            return $response;
        }

        // For non-production, skip strict CSP
        if (!app()->environment('production')) {
            return $next($request);
        }

        // Default CSP for other routes (still without unsafe-eval)
        $nonce = $request->attributes->get('csp_nonce', base64_encode(random_bytes(16)));
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' 'sha256-IfnVKjJJSxCjbxejvAj6OflFqLGfwVDrmy+RDMXiE6k=' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-src 'none'",
            "worker-src 'self' blob:",
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
