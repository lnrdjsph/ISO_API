<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class PrefixRedirects
{
    public function handle($request, Closure $next)
    {
        // Handle nginx reverse proxy headers
        $prefix = $request->header('X-Forwarded-Prefix');
        
        if ($prefix) {
            $prefix = rtrim($prefix, '/');
            
            // Force the root URL to include proxy context
            $host = $request->header('X-Forwarded-Host', $request->getHost());
            $proto = $request->header('X-Forwarded-Proto', $request->getScheme());
            
            URL::forceRootUrl("{$proto}://{$host}{$prefix}");
            URL::forceScheme($proto);
            
            // Store prefix for use in responses
            config(['app.proxy_prefix' => $prefix]);
        }

        return $next($request);
    }
}