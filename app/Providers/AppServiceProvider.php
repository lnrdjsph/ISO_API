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
        // Force root from APP_URL
        URL::forceRootUrl(rtrim(config('app.url'), '/'));

        // Default to http unless proxy overrides
        URL::forceScheme(app('request')->header('X-Forwarded-Proto', 'http'));
    }
}
