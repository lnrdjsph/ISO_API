<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\OracleRibXMLService;
use App\Models\ISO_B2B\Order;
use Exception;

class OracleTransferController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'sof_id' => 'required|string'
        ]);

        try {
            // ✅ Step 1: Get order header
            $order = Order::where('sof_id', $request->sof_id)->firstOrFail();

            // ✅ Step 2: Count total items and how many already have store_order_no
            $totalItems = DB::table('order_items')
                ->where('order_id', $order->id)
                ->count();

            $itemsWithSO = DB::table('order_items')
                ->where('order_id', $order->id)
                ->whereNotNull('store_order_no')
                ->count();

            // ✅ Stop if all items already have store_order_no
            if ($totalItems > 0 && $totalItems === $itemsWithSO) {
                return response()->json([
                    'success' => false,
                    'message' => 'All items in this order already have a Store Order Number. No processing needed.'
                ], 400);
            }

            // ✅ Step 3: Get only items without store_order_no
            $items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->whereNull('store_order_no')
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items available for transfer.'
                ], 404);
            }

            // ✅ Step 4: Prepare header data
            $data = [
                'from_loc_type' => 'S',
                'from_loc' => '7000',
                'to_loc_type' => 'S',
                'to_loc' => '2010',
                'delivery_date' => $order->delivery_date,
                'dept' => '8150',
                'freight_code' => 'N',
                'tsf_type' => 'EG',
                'status' => 'A',
                'user_id' => 'External',
                'comment_desc' => 'Generated from SOF# ' . $order->sof_id,
            ];

            // ✅ Step 5: Build item details
            $data['details'] = $items->map(function ($item) {
                return [
                    'item' => $item->sku,
                    'tsf_qty' => (string) $item->total_qty,
                    'supp_pack_size' => (string) $item->qty_per_pc,
                ];
            })->toArray();

            // ✅ Step 6: Get latest TSF number from Oracle
            $latestTsf = DB::connection('oracle_rms')
                ->table('tsfhead')
                ->select('tsf_no')
                ->whereRaw("REGEXP_LIKE(tsf_no, '^[0-9]+$')")
                ->orderByRaw('TO_NUMBER(tsf_no) DESC')
                ->first();

            $nextTsfNo = $latestTsf && isset($latestTsf->tsf_no)
                ? (string)((int)$latestTsf->tsf_no + 1)
                : '3006000001';

            $nextTsfNo = str_pad($nextTsfNo, 10, '0', STR_PAD_LEFT);
            $data['tsf_no'] = $nextTsfNo;

            // ✅ Step 7: Send XML to Oracle
            $response = OracleRibXMLService::sendTransfer($data);

            // ✅ Step 8: Update only items sent
            if ($response['success']) {
                DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->whereNull('store_order_no')
                    ->update(['store_order_no' => $nextTsfNo]);
            }

            // ✅ Step 9: Return structured response
            return response()->json([
                'success' => true,
                'sof_id' => $order->sof_id,
                'generated_tsf_no' => $nextTsfNo,
                'header' => $data,
                'details_sent' => $data['details'],
                'rib_response' => $response
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing order: ' . $e->getMessage()
            ], 500);
        }
    }
}
