<?php

 namespace App\Providers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {	 
	// Force Laravel to always use APP_URL as base
    	URL::forceRootUrl(config('app.url')); 
        URL::forceScheme('http');    
       //
    }
}
