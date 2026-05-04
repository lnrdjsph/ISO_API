<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(
            fn() => Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()
        );

        if (app()->runningInConsole()) {
            return;
        }

        $request = app('request');

        if ($request->hasHeader('X-Forwarded-Prefix')) {
            $prefix = rtrim($request->header('X-Forwarded-Prefix'), '/');
            $host   = $request->header('X-Forwarded-Host', $request->getHost());
            $proto  = $request->header('X-Forwarded-Proto', 'http');

            URL::forceRootUrl("{$proto}://{$host}{$prefix}");
            URL::forceScheme($proto);
        }
    }
}
