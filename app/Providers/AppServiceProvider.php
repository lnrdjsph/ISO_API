<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        // ── Activity log: auth events ──────────────────────────────
        Event::listen(Login::class, function (Login $event) {
            ActivityLog::create([
                'user_id'     => $event->user->id,
                'action'      => 'auth.login',
                'description' => "{$event->user->name} logged in",
                'properties'  => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'role'       => $event->user->role ?? null,
                    'email'      => $event->user->email ?? null,
                ],
            ]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (!$event->user) {
                return;
            }

            ActivityLog::create([
                'user_id'     => $event->user->id,
                'action'      => 'auth.logout',
                'description' => "{$event->user->name} logged out",
                'properties'  => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);
        });

        Event::listen(Failed::class, function (Failed $event) {
            ActivityLog::create([
                'user_id'     => null,
                'action'      => 'auth.failed',
                'description' => 'Failed login attempt',
                'properties'  => [
                    'email'      => $event->credentials['email'] ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);
        });
        // ───────────────────────────────────────────────────────────

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
