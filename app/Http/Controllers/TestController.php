<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TestController extends Controller
{
    public function testConnection()
    {
        try {
            $users = DB::table('VDC_P_CRD.CRD_DM_CRD AS CRD')
                ->leftJoin('VDC_P_CRD.CMN_DM_CNTC_DET AS CNTC', 'CRD.CUST_SERIAL_NO', '=', 'CNTC.CNCT_REF')
                ->select('CRD.*')
                ->addSelect('CNTC.CNCT_LINE_TYP', 'CNTC.CNCT_VAL')
                ->where('CRD.CARD_TYP', 'LLTY')
                ->whereIn('CRD.PRODUCT_TYP', ['INST_CUST_CARD', 'INST_LOY'])
                ->where('CRD.STATUS_CODE', '1')
                ->where(function ($query) {
                    $query->where('CRD.CARD_NO', 'like', '88887241%')
                          ->orWhere('CRD.CARD_NO', 'like', '88887240%');
                })
                ->limit(200)
                ->get()
                ->groupBy('card_no')
                ->map(function ($items) {
                    $first = $items->first();
                    $meta = [];
                    foreach ($items as $item) {
                        if ($item->cnct_line_typ) {
                            $meta[$item->cnct_line_typ] = $item->cnct_val;
                        }
                    }
                    $data = (array) $first;
                    unset($data['cnct_line_typ'], $data['cnct_val']); // Remove CNCT fields
                    $data['cnct_meta'] = $meta;
                    return $data;
                });
        
            return response()->json([
                "message" => "success",
                "status" => "200",
                "data" => $users->values()
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            return $ex->getMessage();
        }
        
        
        
        
        // return 'testing';
        
    }

    
    
}
// ->where('CARD_NO', '8888721716844858')