<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\Request;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Use intended URL or fallback to FORTIFY_REDIRECT
        $redirectTo = $request->session()->pull('url.intended', env('FORTIFY_REDIRECT', '/'));

        return redirect()->to($redirectTo);
    }
}