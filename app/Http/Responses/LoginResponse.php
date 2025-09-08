<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Get full APP_URL (with /iso-api)
        $baseUrl = rtrim(config('app.url'), '/');

        // If there is an intended URL, prepend APP_URL
        $intended = $request->session()->pull('url.intended');

        if ($intended) {
            // Prevent double slashes
            $intended = ltrim($intended, '/');
            return redirect()->to("{$baseUrl}/{$intended}");
        }

        // Default fallback
        return redirect()->to($baseUrl);
    }
}