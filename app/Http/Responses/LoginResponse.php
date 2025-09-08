<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Always rely on Fortify home
        $defaultRedirect = config('fortify.home', '/');

        return redirect()->intended($defaultRedirect);
    }
}