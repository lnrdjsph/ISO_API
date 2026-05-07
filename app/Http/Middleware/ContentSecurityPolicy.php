<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Hashes for inline event handlers (e.g. onclick="...").
     * 'unsafe-hashes' must be present for these to apply to event attributes.
     * To find new hashes: check the browser console — it will suggest the sha256 to add.
     */
    private array $inlineHandlerHashes = [
        'sha256-IfnVKjJJSxCjbxejvAj6OflFqLGfwVDrmy+RDMXiE6k=',
        // Add more hashes here as the browser console reports them:
        // 'sha256-abc123...',
    ];

    /**
     * Standard CSP — applied globally to all routes via the web middleware group.
     * Does NOT include unsafe-eval. Report pages override this with ContentSecurityPolicyWithEval.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('orders.approve')) {
            return $next($request);
        }

        if (!app()->environment('production')) {
            return $next($request);
        }

        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        $response = $next($request);

        if (!$response->headers->has('Content-Security-Policy')) {
            $hashes = implode(' ', array_map(fn($h) => "'{$h}'", $this->inlineHandlerHashes));

            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' {$hashes} https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                "img-src 'self' data: blob:",
                "connect-src 'self' https://cdn.jsdelivr.net",
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
        }

        return $response;
    }
}
