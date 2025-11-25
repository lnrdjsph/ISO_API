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
                    'message' => 'No items to process.',
                    'error_type' => 'no_items'
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
            $overallSuccess = true;

            // 🔁 Process each department group
            foreach ($grouped as $dept => $deptItems) {
                $nextTsf = str_pad($nextTsfBase, 10, '0', STR_PAD_LEFT);
                $nextTsfBase++;

                // 🔄 Group by SKU and sum quantities
                $skuGroups = [];
                foreach ($deptItems as $item) {
                    $item = (object) $item;
                    $sku = (string) ($item->sku ?? '');

                    $qtyPerPc = floatval($item->qty_per_pc ?? 0);
                    $totalQty = floatval($item->total_qty ?? 0);

                    if (!isset($skuGroups[$sku])) {
                        // First occurrence: set supp_pack_size = qty_per_pc, tsf_qty = total_qty
                        $skuGroups[$sku] = [
                            'item' => $sku,
                            'tsf_qty' => $totalQty,
                            'supp_pack_size' => $qtyPerPc,
                        ];
                    } else {
                        // Subsequent occurrences: accumulate total_qty only
                        $skuGroups[$sku]['tsf_qty'] += $totalQty;
                        // keep supp_pack_size as the first encountered qty_per_pc
                    }
                }

                // Convert to array and format quantities as strings
                $consolidatedItems = array_values(array_map(function($group) {
                    return [
                        'item' => $group['item'],
                        'tsf_qty' => (string) $group['tsf_qty'],
                        'supp_pack_size' => (string) $group['supp_pack_size'],
                    ];
                }, $skuGroups));

                $data = [
                    'create_date' => $order->time_order,
                    'from_loc_type' => 'W',
                    'from_loc' => $order->warehouse,
                    'to_loc_type' => 'S',
                    'to_loc' => $order->requesting_store,
                    'delivery_date' => $order->delivery_date,
                    'dept' => $dept,
                    'freight_code' => 'N',
                    'tsf_type' => 'AIP',
                    'status' => 'A',
                    'user_id' => 'External',
                    'comment_desc' => "Generated from SOF# {$order->sof_id} (Dept: {$dept}) [Ref:" . strtoupper(substr(md5(uniqid()), 0, 10)) . "]",
                    'tsf_no' => $nextTsf,
                    'details' => $consolidatedItems,
                ];

                $payloads[] = $data;

                // 🚀 Send payload to Oracle RIB
                $response = OracleRibXMLService::sendTransfer($data);

                // 📊 Build detailed response structure
                $deptResponse = [
                    'tsf_no' => $nextTsf,
                    'department' => $dept,
                    'item_count' => count($consolidatedItems), // Count unique SKUs
                    'original_item_count' => $deptItems->count(), // Original count before consolidation
                    'success' => $response['success'],
                    'status' => 'unknown',
                    'details' => [],
                    'errors' => [],
                    'verification' => null
                ];

                $warehouseMap = [
                    '80141' => 'Silangan Warehouse',
                    '80001' => 'Central Warehouse',
                    '80041' => 'Procter Warehouse',
                    '80051' => 'Opao-ISO Warehouse',
                    '80071' => 'Big Blue Warehouse',
                    '80131' => 'Lower Tingub Warehouse',
                    '80211' => 'Sta. Rosa Warehouse',
                    '80181' => 'Bacolod Depot',
                    '80191' => 'Tacloban Depot',
                ];

            
                $allStoreLocations = [
                    '4002' => 'F2 - Metro Wholesalemart Colon',
                    '2010' => 'S10 - Metro Maasin',
                    '2017' => 'S17 - Metro Tacloban',
                    '2019' => 'S19 - Metro Bay-Bay',
                    '3018' => 'F18 - Metro Alang-Alang',
                    '3019' => 'F19 - Metro Hilongos',
                    '2008' => 'S8 - Metro Toledo',
                    '6012' => 'H8 - Super Metro Antipolo',
                    '6009' => 'H9 - Super Metro Carcar',
                    '6010' => 'H10 - Super Metro Bogo',
                ];
                

                // ⚠️ Check for various failure types
                $hasRibErrors = !empty($response['errors']) && is_array($response['errors']);
                $hasVerification = isset($response['verification']);
                $verificationPassed = $hasVerification && ($response['verification']['exists'] ?? false);

                if ($response['success'] && !$hasRibErrors && $verificationPassed) {
                    // ✅ Complete Success
                    $deptResponse['status'] = 'success';
                    $deptResponse['verification'] = $response['verification'];
                    
                    if (isset($response['verification']['tsf_data'])) {
                        $tsfData = $response['verification']['tsf_data'];
                        $deptResponse['details'][] = "TSF created in Oracle database";
                        $deptResponse['details'][] = "From: {$tsfData['from_loc']} → To: {$tsfData['to_loc']}";
                        $deptResponse['details'][] = "Status: {$tsfData['status']}";
                        $deptResponse['details'][] = "Verified in TSFHEAD table";
                    }

                    // Update order items - update ALL items with matching SKUs
                    DB::table('order_items')
                        ->where('order_id', $order->id)
                        ->whereIn('sku', $deptItems->pluck('sku')->toArray())
                        ->update(['store_order_no' => $nextTsf]);

                    OrderNote::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id() ?? null,
                        'status' => 'updated',
                        'note' => "✅ Transfer successfully sent to Oracle RIB for Dept {$dept} with TSF#: {$nextTsf}.",
                    ]);

                    Log::info("✅ Oracle RIB success for Dept {$dept}. TSF#: {$nextTsf}");

                } elseif ($hasRibErrors) {
                    // ⚠️ RIB Message Failures
                    $deptResponse['status'] = 'rib_errors';
                    $overallSuccess = false;
                    
                    foreach ($response['errors'] as $err) {
                        $errorMsg = $err['CLEAN_ERROR'] ?? $err['message'] ?? 'Unknown RIB error';
                        $deptResponse['errors'][] = [
                            'type' => 'rib_failure',
                            'message' => $errorMsg,
                            'timestamp' => $err['TIME'] ?? null
                        ];
                    }

                    $errorSummary = implode("\n", array_map(fn($e) => "- " . $e['message'], $deptResponse['errors']));

                    OrderNote::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id() ?? null,
                        'status' => 'failed',
                        'note' => "⚠️ RIB errors for Dept {$dept}:\n{$errorSummary}",
                    ]);

                    Log::warning("⚠️ Oracle RIB errors for Dept {$dept}. TSF#: {$nextTsf}");

                } elseif (!$verificationPassed) {
                    // ⚠️ Verification Failed (TSF not in database)
                    $deptResponse['status'] = 'verification_failed';
                    $overallSuccess = false;

                    // Get user-friendly message
                    $verifyMsg = 'TSF could not be verified in Oracle database';
                    $attempts = $response['verification']['attempt'] ?? 0;
                    
                    // Check if it's a database error vs not found
                    if (isset($response['verification']['error'])) {
                        $verifyMsg = 'Database verification error occurred';
                        // Log full error for debugging
                        Log::error("🔥 [VERIFICATION ERROR] TSF {$nextTsf}: " . $response['verification']['error']);
                    } elseif (isset($response['verification']['message'])) {
                        $verifyMsg = 'TSF not found in TSFHEAD table after multiple attempts';
                    }

                    $deptResponse['errors'][] = [
                        'type' => 'verification_failure',
                        'message' => $verifyMsg,
                        'attempts' => $attempts
                    ];

                    // Don't include verification details in response if there was an error
                    if (!isset($response['verification']['error'])) {
                        $deptResponse['verification'] = [
                            'exists' => false,
                            'attempt' => $attempts
                        ];
                    }

                    OrderNote::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id() ?? null,
                        'status' => 'failed',
                        'note' => "⚠️ Verification failed for Dept {$dept} (TSF: {$nextTsf}). {$verifyMsg} ({$attempts} attempts)",
                    ]);

                    Log::warning("⚠️ Verification failed for Dept {$dept}. TSF#: {$nextTsf}. {$verifyMsg}");

                } else {
                    // ⚠️ Other failure (SFTP, script execution, etc.)
                    $deptResponse['status'] = 'processing_failed';
                    $overallSuccess = false;

                    $errorMsg = $response['message'] ?? 'Unknown error during processing';
                    $deptResponse['errors'][] = [
                        'type' => 'processing_failure',
                        'message' => $errorMsg
                    ];

                    OrderNote::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id() ?? null,
                        'status' => 'failed',
                        'note' => "⚠️ Processing failed for Dept {$dept} (TSF: {$nextTsf}). {$errorMsg}",
                    ]);

                    Log::warning("⚠️ Processing failed for Dept {$dept}. TSF#: {$nextTsf}");
                }

                $responses[$dept] = $deptResponse;
            }

            return response()->json([
                'success' => $overallSuccess,
                'message' => $overallSuccess 
                    ? 'All transfers completed successfully.' 
                    : 'Some transfers encountered issues. See details below.',
                'payloads' => $payloads,
                'responses' => $responses,
                'summary' => [
                    'total_departments' => count($responses),
                    'successful' => count(array_filter($responses, fn($r) => $r['status'] === 'success')),
                    'failed' => count(array_filter($responses, fn($r) => $r['status'] !== 'success')),
                ]
            ]);

        } catch (Exception $e) {
            Log::error("🔥 OracleTransferController: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'exception'
            ], 500);
        }
    }

public function getItemStatus($storeOrderNo)
{
    try {
        // Log the incoming request
        Log::info('Order status request received', ['store_order_no' => $storeOrderNo]);

        // Check if store order number is blank or null
        if (empty($storeOrderNo)) {
            Log::warning('Empty store order number received');
            return response()->json([
                'status' => 'N/A'
            ], 200);
        }

        // Step 1: Check oracle_rms database - tsfhead table
        Log::info('Checking tsfhead table', ['store_order_no' => $storeOrderNo]);
        $tsfHead = DB::connection('oracle_rms')
            ->table('tsfhead')
            ->where('tsf_no', $storeOrderNo)
            ->first();

        if (!$tsfHead) {
            Log::info('Order not found in tsfhead', ['store_order_no' => $storeOrderNo]);
            return response()->json([
                'status' => 'Not Found'
            ], 200);
        }

        // If found in tsfhead, default status is Processing
        $status = 'Processing';
        Log::info('Order found in tsfhead, status: Processing', ['store_order_no' => $storeOrderNo]);

        // Step 2: Check oracle_wms database - container_item table with rwms schema
        Log::info('Checking container_item table', ['store_order_no' => $storeOrderNo]);
        $containerItem = DB::connection('oracle_wms')
            ->table('rwms.container_item')
            ->where('distro_nbr', $storeOrderNo)
            ->first();

        // If found in container_item, status becomes Shipped
        if ($containerItem) {
            $status = 'Shipped';
            Log::info('Order found in container_item, status: Shipped', ['store_order_no' => $storeOrderNo]);
            
            // Step 3: Check oracle_rms database - shipsku table for received qty
            Log::info('Checking shipsku table', ['store_order_no' => $storeOrderNo]);
            $shipSku = DB::connection('oracle_rms')
                ->table('shipsku')
                ->where('distro_no', $storeOrderNo)
                ->where('qty_received', '>', 0)
                ->first();

            // If qty_received > 0, status becomes Received
            if ($shipSku) {
                $status = 'Received';
                Log::info('Order received, status: Received', ['store_order_no' => $storeOrderNo]);
            }
        }

        Log::info('Final status determined', [
            'store_order_no' => $storeOrderNo,
            'status' => $status
        ]);

        return response()->json([
            'status' => $status,
            'store_order_no' => $storeOrderNo
        ], 200);

    } catch (\Exception $e) {
        Log::error('Order status error', [
            'store_order_no' => $storeOrderNo,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'Error',
            'store_order_no' => $storeOrderNo
        ], 500);
    }
}
}