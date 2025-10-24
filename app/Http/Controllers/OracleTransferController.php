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

            // ✅ Get store products table dynamically
            $storeTable = "products_" . strtolower($order->requesting_store);

            if (!DB::getSchemaBuilder()->hasTable($storeTable)) {
                throw new Exception("Store table {$storeTable} does not exist.");
            }

            // 🔍 Join with products table to get department codes
            $itemsWithDept = $items->map(function ($i) use ($storeTable) {
                $dept = DB::table($storeTable)
                    ->where('sku', $i->sku)
                    ->value('department_code');

                return (object) array_merge((array) $i, ['department_code' => $dept ?? 'UNKNOWN']);
            });

            // 🧩 Group items by department_code
            $grouped = $itemsWithDept->groupBy('department_code');

            // 🧮 Generate next TSF number once (base reference)
            $latest = DB::connection('oracle_rms')->table('tsfhead')
                ->select('tsf_no')
                ->whereRaw("REGEXP_LIKE(tsf_no, '^[0-9]+$')")
                ->orderByRaw('TO_NUMBER(tsf_no) DESC')
                ->first();

            $nextTsfBase = $latest
                ? (int)$latest->tsf_no + 1
                : 3006000001;

            $payloads = [];
            $responses = [];

            // 🔁 Process each department group
            foreach ($grouped as $dept => $deptItems) {
                $nextTsf = str_pad($nextTsfBase, 10, '0', STR_PAD_LEFT);
                $nextTsfBase++;

                $data = [
                    'from_loc_type' => 'W',
                    'from_loc' => $order->warehouse,
                    'to_loc_type' => 'S',
                    'to_loc' => $order->requesting_store,
                    'delivery_date' => $order->delivery_date,
                    'dept' => $dept,
                    'freight_code' => 'N',
                    'tsf_type' => 'EG',
                    'status' => 'A',
                    'user_id' => 'External',
                    'comment_desc' => "Generated from SOF# {$order->sof_id} (Dept: {$dept})",
                    'tsf_no' => $nextTsf,
                    'details' => $deptItems->map(fn($i) => [
                        'item' => $i->sku,
                        'tsf_qty' => (string) $i->total_qty,
                        'supp_pack_size' => (string) $i->qty_per_pc,
                    ])->toArray(),
                ];

                $payloads[] = $data;

                // 🚀 Send each payload
                $response = OracleRibXMLService::sendTransfer($data);
                $responses[$dept] = $response;

                if ($response['success']) {
                    DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->whereIn('sku', $deptItems->pluck('sku')->toArray())
                        ->update(['store_order_no' => $nextTsf]);

                    OrderNote::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id() ?? null,
                        'status' => 'updated',
                        'note' => "Transfer successfully sent to Oracle RIB for Dept {$dept} with SO#: {$nextTsf}",
                    ]);

                    Log::info("✅ Oracle RIB success for Dept {$dept}. TSF#: {$nextTsf}");
                } else {
                    Log::warning("⚠️ Oracle RIB failed for Dept {$dept}.");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfers processed per department.',
                'payloads' => $payloads,
                'responses' => $responses,
            ]);

        } catch (Exception $e) {
            Log::error("🔥 OracleTransferController: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
