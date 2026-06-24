<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\OracleRibXMLService;
use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderNote;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Support\LocationConfig;


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
                })
                ->where('remarks', '!=', 'Item Cancelled')
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items to process.',
                    'error_type' => 'no_items'
                ], 400);
            }

            // ✅ Get store products table dynamically — use the assigned mobile-POS
            // store (Visayas → 4002, Luzon → 6012), falling back to requesting_store
            // for any pre-backfill order.
            $posStore   = $order->mobile_pos_store ?: $order->requesting_store;
            $storeTable = "products_" . strtolower($posStore);

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
            // Count how many department groups we'll need BEFORE generating TSF numbers
            $grouped = $itemsWithDept->groupBy('department_code');
            $deptCount = $grouped->count(); // ← know how many we need upfront

            // 1️⃣ Get latest TSF from Oracle RMS BEFORE the transaction
            $latestRms = DB::connection('oracle_rms')
                ->table('tsfhead')
                ->select('tsf_no')
                ->whereRaw("REGEXP_LIKE(tsf_no, '^[0-9]+$')")
                ->orderByRaw('TO_NUMBER(tsf_no) DESC')
                ->first();

            $latestRmsValue = $latestRms
                ? (int) $latestRms->tsf_no
                : 3006000000;

            // 2️⃣ Reserve a BLOCK of TSF numbers atomically
            $nextTsfBase = DB::transaction(function () use ($latestRmsValue, $deptCount) {

                $localRow = DB::table('local_tsf_lock')
                    ->where('id', 1)
                    ->lockForUpdate()
                    ->first();

                $localValue = (int) $localRow->last_tsf;
                $base = max($latestRmsValue, $localValue);

                $firstTsf = $base + 1;
                $lastTsf  = $base + $deptCount; // reserve the whole block

                DB::table('local_tsf_lock')
                    ->where('id', 1)
                    ->update(['last_tsf' => $lastTsf]); // ← advance by full count

                return $firstTsf;
            });

            $payloads = [];
            $responses = [];
            $overallSuccess = true;

            // 🔁 Process each department group
            foreach ($grouped as $dept => $deptItems) {
                $nextTsf = str_pad($nextTsfBase, 10, '0', STR_PAD_LEFT);
                $nextTsfBase++;

                // 🔄 Group by SKU and sum quantities
                $skuGroups = [];
                $hasRemarks = false;
                $remarksList = [];

                foreach ($deptItems as $item) {
                    $item = (object) $item;
                    $sku = (string) ($item->sku ?? '');
                    $qtyPerPc = floatval($item->qty_per_pc ?? 1);
                    $totalQty = floatval($item->total_qty ?? 0);

                    // skip zero / invalid quantities
                    if ($totalQty <= 0 || $sku === '') {
                        continue;
                    }

                    // supp_pack_size should be qty_per_pc
                    $suppPackSize = $qtyPerPc;

                    if (!isset($skuGroups[$sku])) {
                        // Check if this item has remarks
                        $hasRemark = (!empty($item->remarks) && $item->remarks !== 'Item Cancelled');

                        $skuGroups[$sku] = [
                            'item' => $sku,
                            'tsf_qty' => $qtyPerPc * $totalQty,
                            'supp_pack_size' => $suppPackSize,
                        ];

                        // Collect remarks with SKU
                        if ($hasRemark) {
                            $hasRemarks = true;
                            $remarksList[] = "{$sku}: " . trim($item->remarks);
                        }
                    } else {
                        // subsequent occurrences: accumulate quantities
                        $skuGroups[$sku]['tsf_qty'] += ($qtyPerPc * $totalQty);

                        // Check if this occurrence has remarks (and we haven't captured it yet)
                        if (!empty($item->remarks) && $item->remarks !== 'Item Cancelled') {
                            $hasRemarks = true;
                            // Check if we already have this SKU in remarks
                            $skuExists = false;
                            foreach ($remarksList as &$remark) {
                                if (strpos($remark, "{$sku}:") === 0) {
                                    $skuExists = true;
                                    break;
                                }
                            }
                            if (!$skuExists) {
                                $remarksList[] = "{$sku}: " . trim($item->remarks);
                            }
                        }
                    }
                }

                // Build the comment
                $baseComment = "SOF#{$order->sof_id} Dept:{$dept}";
                $orderComment = trim((string) ($order->comment ?? ''));

                // Start with normal comment
                $fullComment = $baseComment;
                if ($orderComment) {
                    $fullComment .= " | {$orderComment}";
                }

                // Append remarks if any items have them
                if ($hasRemarks && !empty($remarksList)) {
                    $fullComment .= " | Remarks: " . implode('; ', $remarksList);
                }

                // Convert to array and format quantities as strings
                $consolidatedItems = array_values(array_map(function ($group) {
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
                    'to_loc' => $posStore,
                    'delivery_date' => $order->delivery_date,
                    'dept' => $dept,
                    'freight_code' => 'N',
                    'tsf_type' => 'AIP',
                    'status' => 'A',
                    'user_id' => 'External',
                    'comment_desc' => mb_substr($fullComment, 0, 2000), // Oracle VARCHAR2(2000) limit
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

                $warehouseMap      = LocationConfig::warehouses();
                $allStoreLocations = LocationConfig::stores();


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
                        // $deptResponse['details'][] = "Status: {$tsfData['status']}";
                        // $deptResponse['details'][] = "Verified in TSFHEAD table";
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

    /**
     * API endpoint — wraps resolveItemStatus() with a JSON response.
     * Returns cached result when available; falls back to last cached value on WMS error.
     */
    public function getItemStatus($storeOrderNo, $sku)
    {
        $data = $this->resolveItemStatus($storeOrderNo, $sku);

        $httpStatus = ($data['status'] === 'Error' && !($data['from_cache'] ?? false)) ? 500 : 200;

        return response()->json($data, $httpStatus);
    }

    /**
     * Resolve the WMS status for a single item.
     * Caches successful results for 10 minutes.
     * On WMS error: returns last cached value (stale) if available, otherwise Error.
     */
    protected function resolveItemStatus(string $storeOrderNo, string $sku): array
    {
        if (empty($storeOrderNo)) {
            return ['status' => 'N/A', 'bol_number' => null, 'store_order_no' => $storeOrderNo];
        }

        $cacheKey = 'item_status_' . md5($storeOrderNo . '_' . $sku);

        try {
            // Step 1: tsfhead
            $tsfHead = DB::connection('oracle_rms')
                ->table('tsfhead')
                ->where('tsf_no', $storeOrderNo)
                ->first();

            if (!$tsfHead) {
                $result = ['status' => 'Not Found', 'bol_number' => null, 'store_order_no' => $storeOrderNo];
                \Cache::put($cacheKey, $result, now()->addMinutes(10));
                return $result;
            }

            $status    = 'Processing';
            $bolNumber = null;

            // Step 1.5: WMS pick / container
            $pickDirective = DB::connection('oracle_wms')
                ->table('rwms.pick_directive')
                ->where('distro_nbr', $storeOrderNo)
                ->first();

            $containerItem = DB::connection('oracle_wms')
                ->table('rwms.container_item')
                ->where('distro_nbr', $storeOrderNo)
                ->first();

            $container = null;
            if ($containerItem) {
                $container = DB::connection('oracle_wms')
                    ->table('rwms.container')
                    ->where('container_id', $containerItem->container_id)
                    ->whereNotNull('bol_nbr')
                    ->first();

                if ($container) {
                    $bolNumber = $container->bol_nbr;
                }
            }

            if ($pickDirective || $containerItem || $container) {
                $status = 'Picking';
            }

            // Step 2: Shipped
            $shipped = false;
            if ($containerItem && $container && $tsfHead && in_array($tsfHead->status, ['S', 'C'])) {
                $shipped = true;
                $status  = 'Shipped';
            }

            // Step 3: Received
            if ($shipped && !empty($sku)) {
                $received = DB::connection('oracle_rms')
                    ->table('shipsku')
                    ->where('distro_no', $storeOrderNo)
                    ->where('item', $sku)
                    ->whereRaw('NVL(qty_received,0) > 0')
                    ->exists();

                if ($received) {
                    $status = 'Received';
                }
            }

            $result = ['status' => $status, 'bol_number' => $bolNumber, 'store_order_no' => $storeOrderNo];
            \Cache::put($cacheKey, $result, now()->addMinutes(10));

            Log::info('Item status resolved', ['tsf' => $storeOrderNo, 'sku' => $sku, 'status' => $status]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Item status error', [
                'store_order_no' => $storeOrderNo,
                'sku'            => $sku,
                'error'          => $e->getMessage(),
            ]);

            // Return last cached value if available (stale-on-error)
            $cached = \Cache::get($cacheKey);
            if ($cached) {
                Log::info('Returning cached item status after error', ['tsf' => $storeOrderNo, 'status' => $cached['status']]);
                return array_merge($cached, ['from_cache' => true]);
            }

            return ['status' => 'Error', 'bol_number' => null, 'store_order_no' => $storeOrderNo];
        }
    }

    /**
     * Aggregate WMS statuses for all active items in a given order.
     * Called by the orders list page to show per-order item status summary.
     * Returns a tally: { "Received": 2, "Shipped": 1, "Processing": 0, ... }
     */
    public function getOrderItemsStatusSummary($orderId)
    {
        $order = Order::with('items')->find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $activeItems = $order->items->filter(
            fn($item) => !empty($item->store_order_no) && $item->remarks !== 'Item Cancelled'
        );

        if ($activeItems->isEmpty()) {
            return response()->json(['statuses' => [], 'total' => 0]);
        }

        // Cache the aggregated summary for 5 minutes.
        // Individual item statuses have their own 10-minute cache inside resolveItemStatus().
        $summaryCacheKey = 'order_items_status_summary_' . $orderId;

        $tally = \Cache::remember($summaryCacheKey, now()->addMinutes(5), function () use ($activeItems) {
            $tally = [];
            foreach ($activeItems as $item) {
                $result = $this->resolveItemStatus($item->store_order_no, $item->sku);
                $status = $result['status'] ?? 'Unknown';
                $tally[$status] = ($tally[$status] ?? 0) + 1;
            }
            return $tally;
        });

        return response()->json([
            'statuses' => $tally,
            'total'    => $activeItems->count(),
        ]);
    }
}
