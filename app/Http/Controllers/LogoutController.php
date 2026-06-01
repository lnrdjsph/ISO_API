<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        // Capture the user BEFORE logout — Auth::user() is null afterwards.
        if ($user) {
            try {
                DB::table('activity_logs')->insert([
                    'user_id'     => $user->id,
                    'action'      => 'auth.logout',
                    'description' => $user->name . ' logged out',
                    'properties'  => json_encode([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'timestamp'  => now()->toISOString(),
                    ]),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } catch (\Throwable $e) {
                // Never let a logging failure block logout.
                Log::error('Failed to log logout activity: ' . $e->getMessage());
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(env('FORTIFY_LOGOUT_REDIRECT', '/login'));
    }
}
