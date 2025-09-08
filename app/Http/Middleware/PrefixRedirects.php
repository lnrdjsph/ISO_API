<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class PrefixRedirects
{
    public function handle($request, Closure $next)
    {
        // If nginx is forwarding with X-Forwarded-Prefix, force URL base
        if ($prefix = $request->header('X-Forwarded-Prefix')) {
            $root = rtrim(config('app.url'), '/');
            URL::forceRootUrl($root);

            // Ensure scheme matches proxy
            if ($request->header('X-Forwarded-Proto')) {
                URL::forceScheme($request->header('X-Forwarded-Proto'));
            }
        }

        return $next($request);
    }
}
