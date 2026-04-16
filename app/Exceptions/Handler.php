<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Return bare 302 with no body — prevents Big Redirect alert
        // CSP headers will be added by your middleware automatically
        return response('', 302)
            ->header('Location', '/login');
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            return response()->view('errors.419', [], 419);
        }

        return parent::render($request, $exception);
    }
}
