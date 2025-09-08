<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Get the intended URL or fallback to home
        $intended = redirect()->intended()->getTargetUrl();
        
        // If no specific intended URL, use the configured home path
        if (str_contains($intended, '/login') || $intended === url('/')) {
            $home = config('fortify.home', '/');
            $baseUrl = rtrim(config('app.url'), '/');
            
            // Ensure home path starts with /
            $home = '/' . ltrim($home, '/');
            
            return redirect()->to($baseUrl . $home);
        }
        
        return redirect($intended);
    }
}