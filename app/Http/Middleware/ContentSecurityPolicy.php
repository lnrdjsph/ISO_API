cat > app/Http/Middleware/ContentSecurityPolicy.php << 'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
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
EOF