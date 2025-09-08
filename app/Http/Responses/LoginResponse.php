<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Always respect intended URL first
        return redirect()->intended(
            config('fortify.home', '/iso-api')
        );
    }
}
