<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
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
            $users = DB::connection('oracle')
                ->table('VDC_P_CRD.CRD_DM_CRD AS CRD')
                ->leftJoin('VDC_P_CRD.CMN_DM_CNTC_DET AS CNTC', 'CRD.CUST_SERIAL_NO', '=', 'CNTC.CNCT_REF')
                ->select('CRD.*')
                ->addSelect('CNTC.CNCT_LINE_TYP', 'CNTC.CNCT_VAL')
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
