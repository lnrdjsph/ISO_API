<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $defaultRedirect = rtrim(config('app.url'), '/'); // respects APP_URL with /iso-api
        return redirect()->intended($defaultRedirect);
    }
}

