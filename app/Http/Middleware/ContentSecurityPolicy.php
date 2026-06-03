<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Standard CSP — applied globally to all routes via the web middleware group.
     * Does NOT include unsafe-eval. Report pages override this with ContentSecurityPolicyWithEval.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('orders.approve')) {
            return $next($request);
        }

        // Check if this is a document viewing request
        $isDocumentRequest = $request->routeIs('document.view') ||
            str_contains($request->url(), '/storage/') ||
            $request->routeIs('orders.show');

        if (!app()->environment('production')) {
            return $next($request);
        }

        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        $response = $next($request);

        if (!$response->headers->has('Content-Security-Policy')) {
            // For document viewing routes, allow frames
            if ($isDocumentRequest) {
                $csp = implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' 'sha256-IfnVKjJJSxCjbxejvAj6OflFqLGfwVDrmy+RDMXiE6k=' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                    "img-src 'self' data: blob:",
                    "connect-src 'self' https://cdn.jsdelivr.net",
                    "frame-ancestors 'self'",  // Changed from 'none' to 'self'
                    "form-action 'self'",
                    "object-src 'self'",  // Changed from 'none' to allow PDF objects
                    "base-uri 'self'",
                    "frame-src 'self' https://docs.google.com",  // Changed from 'none' - allow Google Viewer too
                    "worker-src 'self' blob:",
                    "manifest-src 'self'",
                ]);

                // Don't set X-Frame-Options to DENY for document routes
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // Changed from DENY
            } else {
                // Strict CSP for other routes
                $csp = implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' 'sha256-IfnVKjJJSxCjbxejvAj6OflFqLGfwVDrmy+RDMXiE6k=' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                    "img-src 'self' data: blob:",
                    "connect-src 'self' https://cdn.jsdelivr.net",
                    "frame-ancestors 'self'",
                    "form-action 'self'",
                    "object-src 'self'",
                    "base-uri 'self'",
                    "frame-src 'self' https://docs.google.com",
                    "worker-src 'self' blob:",
                    "manifest-src 'self'",
                ]);
                $response->headers->set('X-Frame-Options', 'DENY');
            }

            $response->headers->set('Content-Security-Policy', $csp);
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        }

        return $response;
    }
}
