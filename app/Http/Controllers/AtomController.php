<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\OrderRequest;

class AtomController extends Controller
{

    protected array $locationMap = [
        'Metro Wholesalemart Colon' => '4002',
        'Metro Maasin' => '2010',
        'Metro Tacloban' => '2017',
        'Metro Bay-Bay' => '2019',
        'Metro Alang-Alang' => '3018',
        'Metro Hilongos' => '3019',
        'Metro Toledo' => '2008',
        'Super Metro Antipolo' => '6012',
        'Super Metro Carcar' => '6009',
        'Super Metro Bogo' => '6010',
    ];

    protected array $warehouseConfig = [
        '80001' => ['stores' => ['4002','2010']],
        '80141' => ['stores' => ['2017','2019']],
    ];

    /**
     * Receive order from WooCommerce
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function receiveOrder(Request $request)
    {
        try {
            // Log incoming request
            Log::channel('orders')->info('Order received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload_size' => strlen(json_encode($request->all()))
            ]);

            // Validate incoming data
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|integer',
                'order_number' => 'required',
                'date' => 'required|date',
                'status' => 'required|string',
                'currency' => 'required|string|size:3',
                'total' => 'required|numeric',
                'billing_address' => 'required|array',
                'billing_address.email' => 'required|email',
                'shipping_address' => 'required|array',
                'items' => 'required|array|min:1',
                'items.*.sku' => 'required|string',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'meta_data' => 'required|array',
                'meta_data.*.key' => 'required|string',
                'meta_data.*.value' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::channel('orders')->error('Validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'order_id' => $request->input('order_id')
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get validated data
            $orderData = $validator->validated();

            // Log complete order data
            Log::channel('orders')->info('Order validated successfully', [
                'order_id' => $orderData['order_id'],
                'order_number' => $orderData['order_number'],
                'total' => $orderData['total'],
                'items_count' => count($orderData['items']),
                'customer_email' => $orderData['billing_address']['email']
            ]);

            // ============================================
            // TODO: Process the order data here
            // ============================================
            // Examples:
            // - Save to database
            // - Send to another system
            // - Trigger business logic
            // - Queue a job
            // ============================================

            // Blank query placeholder for future implementation
            $this->processOrder($orderData);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Order received successfully',
                'order_id' => $orderData['order_id'],
                'order_number' => $orderData['order_number'],
                'timestamp' => now()->toIso8601String()
            ], 200);

        } catch (\Exception $e) {
            Log::channel('orders')->error('Error processing order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $request->input('order_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Process the order - Save to orders and order_items tables
     *
     * @param array $orderData
     * @return void
     */
    private function processOrder(array $orderData)
    {
        $exists = DB::table('orders')
            ->where('sof_id', $orderData['order_number'])
            ->exists();

        if ($exists) {
            Log::channel('orders')->warning('Duplicate order ignored', [
                'order_number' => $orderData['order_number']
            ]);
            return;
        }

        $storeCode = $this->extractStoreCode($orderData['meta_data'] ?? []);

        if (!$storeCode) {
            throw new \Exception('Pickup store not found in order metadata');
        }

        $warehouse = $this->resolveWarehouse($storeCode);

        try {
            DB::beginTransaction();

            // Extract billing and shipping addresses
            $billing = $orderData['billing_address'] ?? [];
            $shipping = $orderData['shipping_address'] ?? [];

            // Get customer name safely
            $customerName = $billing['name'] ?? 
                           ($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '');
            $customerName = trim($customerName) ?: 'Guest Customer';

            // Combine shipping address parts
            $fullAddress = collect([
                $shipping['address_1'] ?? '',
                $shipping['address_2'] ?? '',
                $shipping['city'] ?? '',
                $shipping['state'] ?? '',
                $shipping['postcode'] ?? '',
                $shipping['country'] ?? ''
            ])->filter()->implode(', ');

            // Create the main order
            $order = DB::connection('mysql')->table('orders')->insertGetId([
                'sof_id' => $orderData['order_number'], // Using WooCommerce order number as SOF ID
                'requesting_store' => 'MarengEms Online',
                'requested_by' => $customerName,
                'mbc_card_no' => $this->meta($orderData['meta_data'], 'mbc_card_no') ?? null,
                'customer_name' => $customerName,
                'contact_number' => $billing['phone'] ?? null,
                'email' => $billing['email'] ?? null,
                'channel_order' => 'E-Commerce',
                'warehouse' => $warehouse ?? null,
                'time_order' => $orderData['date'],
                'payment_center'   => $this->meta($orderData['meta_data'], 'payment_center'),
                'mode_payment' => $orderData['payment_method_title'] ?? 'N/A',
                'payment_date' => now()->toDateString(),
                'mode_dispatching' => $this->meta($orderData['meta_data'], 'mode_dispatching') ?? 'Delivery',
                'delivery_date'    => $this->meta($orderData['meta_data'], 'delivery_date'),
                'address' => $fullAddress ?: 'N/A',
                'landmark' => $shipping['address_2'] ?? null,
                'order_status' => $this->mapOrderStatus($orderData['status']),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::channel('orders')->info('Order inserted', [
                'order_id' => $order,
                'woocommerce_order_id' => $orderData['order_id']
            ]);

            foreach ($orderData['items'] as $item) {

                if (empty($item['sku'])) {
                    throw new \Exception('SKU is required');
                }


                if (!$warehouse) {
                    throw new \Exception('Warehouse not provided');
                }

                // 1. Fetch product
                $product = $this->getProduct($item['sku'], $warehouse);

                if (!$product) {
                    throw new \Exception("SKU {$item['sku']} not found in warehouse {$warehouse}");
                }

                $allocation = $this->getAllocation($product->sku, $warehouse);

                if (!$allocation) {
                    throw new \Exception("No allocation record for SKU {$product->sku} in warehouse {$warehouse}");
                }

                $qtyCs   = (int) $item['qty'];        // Woo = cases
                $casePack = (int) ($product->case_pack ?? 1);
                $qtyPc   = $qtyCs * $casePack;

                $requiredQty = $qtyPc;

                $availableQty = (int) ($allocation->wms_virtual_allocation ?? 0);

                if ($availableQty < $requiredQty) {
                    throw new \Exception(
                        "Insufficient stock for SKU {$product->sku}. Required {$requiredQty}, available {$availableQty}"
                    );
                }



                // 3. Pricing
                $pricePerPc = (float) $item['price'] / $qtyPc;
                $rawPrice   = $pricePerPc * $qtyPc;

                // 4. Discount (if any)
                $discountRaw = $item['discount'] ?? 0;
                $discount = 0;

                if (is_string($discountRaw) && str_ends_with($discountRaw, '%')) {
                    $discount = $rawPrice * ((float) rtrim($discountRaw, '%') / 100);
                } else {
                    $discount = (float) $discountRaw;
                }

                $finalPrice = max($rawPrice - $discount, 0);

                // 5. Insert MAIN / DISCOUNT item
                DB::table('order_items')->insert([
                    'order_id' => $order,
                    'sku' => $product->sku,
                    'item_description' => $product->description,
                    'scheme' => $product->discount_scheme,
                    'price_per_pc' => $pricePerPc,
                    'price' => $finalPrice,
                    'qty_per_pc' => $casePack,
                    'qty_per_cs' => $qtyCs,
                    'freebies_per_cs' => 0,
                    'discount' => $discountRaw,
                    'total_qty' => $qtyPc,
                    'amount' => $finalPrice,
                    'remarks' => null,
                    'store_order_no' => null,
                    'item_type' => $discount > 0 ? 'DISCOUNT' : 'MAIN',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $totalDeduct = $requiredQty;
                
                if (!empty($product->freebie_sku)) {
                    $totalDeduct += $product->allocation_per_case * $qtyCs;
                }
                    
                DB::table('product_wms_allocations')
                    ->where('sku', $product->sku)
                    ->where('warehouse_code', $warehouse)
                    ->decrement('wms_virtual_allocation', $totalDeduct);

                // 6. Freebie logic (DB-driven)
                if (!empty($product->freebie_sku) && !empty($product->allocation_per_case)) {

                    DB::table('order_items')->insert([
                        'order_id' => $order,
                        'sku' => $product->freebie_sku,
                        'item_description' => 'Freebie',
                        'scheme' => 'Freebie',
                        'price_per_pc' => 0,
                        'price' => 0,
                        'qty_per_pc' => 0,
                        'qty_per_cs' => 0,
                        'freebies_per_cs' => $product->allocation_per_case,
                        'discount' => 0,
                        'total_qty' => $product->allocation_per_case * $qtyCs,
                        'amount' => 0,
                        'remarks' => null,
                        'store_order_no' => null,
                        'item_type' => 'FREEBIE',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }


            DB::commit();

            Log::channel('orders')->info('Order processed successfully', [
                'order_id' => $order,
                'woocommerce_order_id' => $orderData['order_id'],
                'items_count' => count($orderData['items'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('orders')->error('Failed to process order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'woocommerce_order_id' => $orderData['order_id']
            ]);

            throw $e;
        }
    }

    /**
     * Map WooCommerce order status to internal status
     *
     * @param string $wooStatus
     * @return string
     */
    private function mapOrderStatus(string $wooStatus): string
    {
        $statusMap = [
            'pending' => 'new order',
            'processing' => 'new order',
            'on-hold' => 'pending',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'cancelled'
        ];

        return $statusMap[$wooStatus] ?? 'new order';
    }

    /**
     * Test endpoint without authentication
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        Log::channel('orders')->info('Test endpoint called', [
            'payload' => $request->all()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test endpoint working',
            'received_data' => $request->all(),
            'timestamp' => now()->toIso8601String()
        ], 200);
    }


    private function getProduct(string $sku, string $warehouse)
    {
        return DB::table("products_{$warehouse}")
            ->where('sku', $sku)
            ->whereNull('archived_at')
            ->first();
    }    

    private function extractStoreCode(array $metaData): ?string
    {
        foreach ($metaData as $meta) {
            if (($meta['key'] ?? '') === '_pickup_store') {
                return $this->locationMap[$meta['value']] ?? null;
            }
        }
        return null;
    }    

    private function resolveWarehouse(string $storeCode): string
    {
        foreach ($this->warehouseConfig as $warehouse => $config) {
            if (in_array($storeCode, $config['stores'], true)) {
                return $warehouse;
            }
        }

        throw new \Exception("No warehouse mapped for store {$storeCode}");
    }

    private function getAllocation(string $sku, string $warehouse)
    {
        return DB::table('product_wms_allocations')
            ->where('sku', $sku)
            ->where('warehouse_code', $warehouse)
            ->lockForUpdate() // CRITICAL for concurrency
            ->first();
    }

    private function meta(array $meta, string $key): mixed {
        foreach ($meta as $m) {
            if (($m['key'] ?? null) === $key) {
                return $m['value'];
            }
        }
        return null;
    }    
}