<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwitchRoleController extends Controller
{
    public function switch(Request $request)
    {
        $user = Auth::user();

        // Only users 1,2,3 can switch
        if (!in_array($user->id, [1, 2, 3])) {
            abort(403);
        }

        $request->validate([
            'role' => 'required|in:super admin,store personnel,manager'
        ]);

        // Store the chosen role in the session
        session(['switched_role' => $request->role]);

        // Redirect back (or return JSON for AJAX)
        return redirect()->back()->with('success', 'Role switched to ' . $request->role);
    }
}
