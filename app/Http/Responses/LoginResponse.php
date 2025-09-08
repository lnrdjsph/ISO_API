<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\Request;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $base = rtrim(config('app.url'), '/');
        $home = ltrim(config('fortify.home', '/'), '');

        return redirect()->intended("{$base}/{$home}");
    }
}