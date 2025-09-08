<?php
namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Don't override URLs in console commands
        if (app()->runningInConsole()) {
            return;
        }

        $request = app('request');
        
        // Handle reverse proxy configuration
        if ($request->hasHeader('X-Forwarded-Prefix')) {
            $prefix = rtrim($request->header('X-Forwarded-Prefix'), '/');
            $host = $request->header('X-Forwarded-Host', $request->getHost());
            $proto = $request->header('X-Forwarded-Proto', 'http');
            
            URL::forceRootUrl("{$proto}://{$host}{$prefix}");
            URL::forceScheme($proto);
        }
    }
}