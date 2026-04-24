<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password'          => 'required',
            'password'                  => 'required|min:6|confirmed|different:current_password',
            'password_confirmation'     => 'required',
        ], [
            'password.different' => 'New password must be different from your current password.',
        ]);

        // Verify current password is correct
        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors([
                'current_password' => 'Your current password is incorrect.',
            ])->withInput();
        }

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('password.change')
            ->with('success', 'Password changed successfully.');
    }
}