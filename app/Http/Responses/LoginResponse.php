<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Always honor APP_URL and Fortify home
        $base = rtrim(config('app.url'), '/');
        $home = ltrim(config('fortify.home', '/'), '/');

        return redirect()->intended("{$base}/{$home}");
    }
}
: