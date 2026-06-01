<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\LocationConfig;

class SwitchUserContextController extends Controller
{
    public function switchRole(Request $request)
    {
        $user = Auth::user();

        // Only users 1,2,3 can switch
        if (!in_array($user->id, [1, 2, 3])) {
            abort(403);
        }

        $request->validate([
            'role' => 'required|in:super admin,store personnel,manager'
        ]);

        session(['switched_role' => $request->role]);

        return response()->json(['success' => true, 'role' => $request->role]);
    }

    public function switchLocation(Request $request)
    {
        $user = Auth::user();

        // Only users 1,2,3 can switch
        if (!in_array($user->id, [1, 2, 3])) {
            abort(403);
        }

        $request->validate([
            'location' => 'required|string'
        ]);

        $locationCode = $request->location;

        // Validate the location exists using LocationConfig
        $validLocations = array_merge(
            array_keys(LocationConfig::stores()),
            array_keys(LocationConfig::regionLabels())
        );

        if (!in_array($locationCode, $validLocations)) {
            return response()->json(['success' => false, 'message' => 'Invalid location'], 422);
        }

        session(['switched_location' => $locationCode]);

        return response()->json(['success' => true, 'location' => $locationCode]);
    }
}
