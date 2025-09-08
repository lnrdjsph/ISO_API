<?php
namespace App\Http\Responses;

use Illuminate\Http\Request;

class UnauthenticatedResponse
{
    public function toResponse(Request $request)
    {
        $loginUrl = rtrim(config('app.url'), '/') . '/login';
        
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        return redirect()->guest($loginUrl);
    }
}