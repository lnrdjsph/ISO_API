<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{



    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by user_location
        if ($request->filled('user_location')) {
            $query->where('user_location', $request->user_location);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('users.user_management', compact('users'));
    }


    // Show form to create user
    public function create()
    {
        return view('users.user_create');
    }

    // Store new user
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6|confirmed',
            'role'          => ['required', Rule::in(['super admin', 'admin', 'user'])],
            'user_location' => 'required|string|max:10',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    // Show edit form
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password'      => 'nullable|string|min:6|confirmed',
            'role'          => ['required', Rule::in(['super admin', 'admin', 'user', 'manager'])],
            'user_location' => 'required|string|max:10',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    // Delete user
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
       


        public function getUserData(Request $request)
    {
        $cardNo = trim($request->input('card_no'));

        // 1. Validate input format
        if (empty($cardNo) || strlen($cardNo) !== 16 || !is_numeric($cardNo)) {
            return response()->json([
                "message" => "Invalid card number format. Must be 16 numeric digits.",
                "status" => "400",
            ]);
        }

        try {
            // 2. Validate prefix
            $cardType = '';
            if (strpos($cardNo, '88887241') === 0) {
                $cardType = 'MBC 1';
            } elseif (strpos($cardNo, '88887240') === 0) {
                $cardType = 'MBC 2';
            } else {
                return response()->json([
                    "message" => "Card prefix not recognized. Must start with 88887241 or 88887240.",
                    "status" => "400",
                ]);
            }

            // 3. Query the database
            $users = DB::connection('oracle_mbc')
                ->table('VDC_P_CRD.CRD_DM_CRD AS CRD')
                ->leftJoin('VDC_P_CRD.CMN_DM_CNTC_DET AS CNTC', 'CRD.CUST_SERIAL_NO', '=', 'CNTC.CNCT_REF')
                ->leftJoin('LOYALTY_MASTER AS LM', 'CRD.CARD_NO', '=', 'LM.IC_MRC_CARD_NO')
                ->select('CRD.*')
                ->addSelect('CNTC.CNCT_LINE_TYP', 'CNTC.CNCT_VAL')
                ->addSelect('LM.MP_EMAIL') // Add this to get the email from LOYALTY_MASTER
                ->where('CRD.CARD_TYP', 'LLTY')
                ->whereIn('CRD.PRODUCT_TYP', ['INST_CUST_CARD', 'INST_LOY'])
                ->where('CRD.STATUS_CODE', '1')
                ->where('CRD.CARD_NO', $cardNo)
                ->limit(100)
                ->get()
                ->groupBy('card_no')
                ->map(function ($items) use ($cardType) {
                    $first = $items->first();
                    $meta = [];

                    foreach ($items as $item) {
                        if ($item->cnct_line_typ) {
                            $meta[$item->cnct_line_typ] = $item->cnct_val;
                        }
                    }

                    $data = (array) $first;
                    unset($data['cnct_line_typ'], $data['cnct_val']);
                    $data['cnct_meta'] = $meta;
                    $data['card_type'] = $cardType;

                    return $data;
                });


            // 4. No matching record
            if ($users->isEmpty()) {
                return response()->json([
                    "message" => "Card not found or inactive in database.",
                    "status" => "400",
                ]);
            }

            // 5. Success
            return response()->json([
                "message" => "success",
                "status" => "200",
                "data" => $users->values()
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                "message" => "Database query error.",
                "status" => "500",
                "error" => $ex->getMessage()
            ]);
        }
    }
}
