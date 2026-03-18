<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Cookie;

class VerifyCsrfToken extends Middleware
{
    protected $except = [];

    protected function newCookie($request, $config)
    {
        return Cookie::make(
            'XSRF-TOKEN',
            $this->encrypter->encrypt(
                $request->session()->token(),
                static::serialized()
            ),
            $config['lifetime'],
            $config['path'],
            $config['domain'],
            $config['secure'],
            true,  // ✅ HttpOnly = true
            false,
            $config['same_site'] ?? null
        );
    }
}
