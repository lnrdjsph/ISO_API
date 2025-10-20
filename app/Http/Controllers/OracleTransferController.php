<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\OracleRibXMLService;
use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderNote;
use Illuminate\Support\Facades\Log;
use Exception;

class OracleTransferController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['sof_id' => 'required|string']);

        try {
            $order = Order::where('sof_id', $request->sof_id)->firstOrFail();

            $items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->where(function ($q) {
                    $q->whereNull('store_order_no')->orWhere('store_order_no', '');
                })->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items to process.'
                ], 400);
            }

            // 🔢 Build transfer payload
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
                'details' => $items->map(fn($i) => [
                    'item' => $i->sku,
                    'tsf_qty' => (string) $i->total_qty,
                    'supp_pack_size' => (string) $i->qty_per_pc,
                ])->toArray(),
            ];

            // 🧮 Generate next TSF number
            $latest = DB::connection('oracle_rms')->table('tsfhead')
                ->select('tsf_no')
                ->whereRaw("REGEXP_LIKE(tsf_no, '^[0-9]+$')")
                ->orderByRaw('TO_NUMBER(tsf_no) DESC')
                ->first();

            $nextTsf = $latest
                ? str_pad((int)$latest->tsf_no + 1, 10, '0', STR_PAD_LEFT)
                : '3006000001';

            $data['tsf_no'] = $nextTsf;

            // 🚀 Send XML to Oracle RIB
            $response = OracleRibXMLService::sendTransfer($data);

            if ($response['success']) {
                // ✅ Update items
                DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->where(function ($q) {
                        $q->whereNull('store_order_no')->orWhere('store_order_no', '');
                    })
                    ->update(['store_order_no' => $nextTsf]);

                // 🗒️ Add order note
                OrderNote::create([
                    'order_id' => $order->id,
                    'user_id' => auth()->id() ?? null,
                    'status' => 'TSF GENERATED',
                    'note' => "Transfer successfully sent to Oracle RIB with TSF#: {$nextTsf}",
                ]);

                Log::info("✅ Oracle RIB success. Updated TSF#: {$nextTsf}");

                return response()->json([
                    'success' => true,
                    'generated_tsf_no' => $nextTsf,
                    'message' => 'RIB successfully processed the transfer.'
                ]);
            }

            Log::warning("⚠️ Oracle RIB did not confirm processing.");

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'RIB did not confirm processing.'
            ], 202);

        } catch (Exception $e) {
            Log::error("🔥 OracleTransferController: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
