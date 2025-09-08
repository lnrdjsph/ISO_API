<?php
namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Get intended URL, but ensure it includes proxy prefix
        $intended = session()->pull('url.intended');
        
        if ($intended) {
            // Make sure intended URL includes the proxy prefix
            $prefix = $request->header('X-Forwarded-Prefix');
            if ($prefix && !str_contains($intended, $prefix)) {
                $host = $request->header('X-Forwarded-Host', $request->getHost());
                $proto = $request->header('X-Forwarded-Proto', 'http');
                $intended = "{$proto}://{$host}{$prefix}" . parse_url($intended, PHP_URL_PATH);
            }
            return redirect($intended);
        }
        
        // Default redirect to home
        return redirect('/');
    }
}