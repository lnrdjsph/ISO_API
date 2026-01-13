<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\OrderRequest;

class AtomController extends Controller
{
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
                'items.*.product_id' => 'required|integer',
                'items.*.name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.sku' => 'nullable|string',
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
                'mbc_card_no' => $orderData['meta_data']['mbc_card_no'] ?? null,
                'customer_name' => $customerName,
                'contact_number' => $billing['phone'] ?? null,
                'email' => $billing['email'] ?? null,
                'channel_order' => 'E-Commerce',
                'warehouse' => $orderData['meta_data']['warehouse'] ?? null,
                'time_order' => $orderData['date'],
                'payment_center' => $orderData['meta_data']['payment_center'] ?? null,
                'mode_payment' => $orderData['payment_method_title'] ?? 'N/A',
                'payment_date' => now()->toDateString(),
                'mode_dispatching' => $orderData['meta_data']['mode_dispatching'] ?? 'Delivery',
                'delivery_date' => $orderData['meta_data']['delivery_date'] ?? null,
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

                $warehouse = $orderData['meta_data']['warehouse'] ?? null;
                if (!$warehouse) {
                    throw new \Exception('Warehouse not provided');
                }

                // 1. Fetch product
                $product = $this->getProduct($item['sku'], $warehouse);

                if (!$product) {
                    throw new \Exception("SKU {$item['sku']} not found in warehouse {$warehouse}");
                }

                // 2. Quantities
                $qtyCs     = (int) $item['quantity']; // Woo = cases
                $casePack = (int) ($product->case_pack ?? 1);
                $qtyPc    = $qtyCs * $casePack;

                // 3. Pricing
                $pricePerPc = (float) $product->srp;
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
}