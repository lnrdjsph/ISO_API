<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class PrefixRedirects
{
    public function handle($request, Closure $next)
    {
        // Get the prefix from X-Forwarded-Prefix header
        if ($prefix = $request->header('X-Forwarded-Prefix')) {
            $prefix = rtrim($prefix, '/');
            
            // Set the root URL to include the prefix
            $rootUrl = rtrim(config('app.url'), '/');
            URL::forceRootUrl($rootUrl);
            
            // Force the scheme based on X-Forwarded-Proto
            if ($forwardedProto = $request->header('X-Forwarded-Proto')) {
                URL::forceScheme($forwardedProto);
            }
            
            // Store the prefix in the request for later use
            $request->attributes->set('app.prefix', $prefix);
        }

        return $next($request);
    }
}