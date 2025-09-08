<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class PrefixRedirects
{
    public function handle($request, Closure $next)
    {
        if ($prefix = $request->header('X-Forwarded-Prefix')) {
            URL::forceRootUrl(rtrim(config('app.url'), '/'));
            app()->instance('request', $request);
        }

        return $next($request);
    }
}