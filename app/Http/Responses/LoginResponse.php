<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $base = rtrim(config('app.url'), '/');
        $home = ltrim(config('fortify.home', '/'), '/');

        // 👇 Workaround: prefix session path if missing
        $sessionPath = rtrim(config('session.path', ''), '/');
        if (!empty($sessionPath) && strpos($home, ltrim($sessionPath, '/')) !== 0) {
            $home = ltrim($sessionPath, '/') . '/' . $home;
        }

        return redirect()->intended("{$base}/{$home}");
    }
}
