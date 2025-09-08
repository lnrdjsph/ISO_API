<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class ProxyAwareMiddleware
{
    public function handle($request, Closure $next)
    {
        // Handle nginx reverse proxy
        if ($prefix = $request->header('X-Forwarded-Prefix')) {
            $prefix = rtrim($prefix, '/');
            $host = $request->header('X-Forwarded-Host', $request->getHost());
            $proto = $request->header('X-Forwarded-Proto', $request->getScheme());
            
            // Set the application URL context
            URL::forceRootUrl("{$proto}://{$host}{$prefix}");
            URL::forceScheme($proto);
            
            // Fix session path for cookies
            config(['session.path' => $prefix]);
        }

        return $next($request);
    }
}