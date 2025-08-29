<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     * This is where users will be redirected after authentication.
     *
     * Controlled by .env FORTIFY_REDIRECT else defaults to "/".
     *
     * @var string
     */
    public static string $HOME;

    /**
     * Define route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Read redirect target from .env
        self::$HOME = env('FORTIFY_REDIRECT', '/');

        $this->configureRateLimiting();

        $this->routes(function () {
            // API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web routes (prefix taken from .env APP_URL base if needed)
            Route::middleware('web')
                ->prefix(trim(parse_url(env('APP_URL', ''), PHP_URL_PATH) ?? '', '/'))
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}
