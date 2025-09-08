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
        // Only force root URL in non-CLI environments
        if (!app()->runningInConsole()) {
            $request = app('request');
            
            // Set the root URL based on APP_URL
            URL::forceRootUrl(rtrim(config('app.url'), '/'));
            
            // Set scheme based on proxy headers or default to http
            $scheme = $request->header('X-Forwarded-Proto', 
                      $request->header('X-Forwarded-Ssl') === 'on' ? 'https' : 'http');
            URL::forceScheme($scheme);
        }
    }
}