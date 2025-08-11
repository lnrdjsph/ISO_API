<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\Request;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $redirectTo = config('fortify.home', env('FORTIFY_HOME', '/'));

        return redirect()->intended($redirectTo);
    }
}
