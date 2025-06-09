<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OracleRmsController extends Controller
{
    public function fetchItemData(Request $request)
    {
        $itemId = $request->input('item_id'); // expects item_id in POST body

        $data = DB::connection('oracle_rms')->select("
            SELECT
                item_master.item AS sku,
                item_master.item_desc AS description,
                deps.dept_name AS department,
                uda_values.uda_value_desc AS brand,
                groups.group_name
            FROM item_supplier
            LEFT JOIN item_master 
                ON item_supplier.item = item_master.item
            LEFT JOIN uda_item_lov 
                ON item_supplier.item = uda_item_lov.item
                AND item_master.item = uda_item_lov.item
            LEFT JOIN uda_values 
                ON uda_item_lov.uda_value = uda_values.uda_value 
                AND uda_item_lov.uda_id = uda_values.uda_id
            LEFT JOIN deps 
                ON deps.dept = item_master.dept
            LEFT JOIN groups 
                ON groups.group_no = deps.group_no
            WHERE uda_values.uda_id = 9
              AND item_master.item = :item_id
        ", ['item_id' => $itemId]);

        return response()->json($data);
    }
}
