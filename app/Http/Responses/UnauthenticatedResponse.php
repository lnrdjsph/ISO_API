<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;

class UnauthenticatedResponse
{
    public function toResponse(Request $request)
    {
        // Always send to iso-api login if session expired
        return redirect('/login');
    }
}
