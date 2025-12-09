<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Set maximum execution time to 25 seconds (before nginx timeout)
        set_time_limit(25);
        
        // Set memory limit if needed
        ini_set('memory_limit', '256M');
        
        // Disable output buffering for streaming responses
        if (ob_get_level()) ob_end_clean();
        
        return $next($request);
    }
}