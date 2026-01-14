?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtomController;

Route::prefix('atom-api')->group(function () {
// Test endpoint (no auth)
Route::post('/test', [AtomController::class, 'test']);

// Main endpoint (with Bearer token)
Route::post('/', [AtomController::class, 'receiveOrder'])
->middleware('verify.api.token');
});


// ============================================
// FILE: app/Http/Controllers/AtomController.php
// ============================================
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AtomController extends Controller
{
    /**
     * Store location code mapping
     */
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

    /**
     * Warehouse to store mapping
     */
    protected array $warehouseConfig = [
        '80181' => ['stores' => ['4002', '2010']],
        '80141' => ['stores' => ['2017', '2019']],
    ];

    /**
     * Receive order from WooCommerce
     */
    public function receiveOrder(Request $request)
    {
        try {
            Log::channel('orders')->info('Order received from WooCommerce', [
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
                'payment_method_title' => 'nullable|string',
                'billing_address' => 'required|array',
                'billing_address.email' => 'required|email',
                'shipping_address' => 'required|array',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer',
                'items.*.sku' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.total' => 'required|numeric|min:0',
                'meta_data' => 'required|array',
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

            $orderData = $validator->validated();

            Log::channel('orders')->info('Order validated successfully', [
                'order_id' => $orderData['order_id'],
                'order_number' => $orderData['order_number'],
                'total' => $orderData['total'],
                'items_count' => count($orderData['items']),
                'payment_method' => $orderData['payment_method_title']
            ]);

            // Process the order
            $this->processOrder($orderData);

            return response()->json([
                'success' => true,
                'message' => 'Order received and processed successfully',
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
     * Process the order - Complete implementation
     */
    private function processOrder(array $orderData)
    {
        // Check for duplicate order
        $exists = DB::table('orders')
            ->where('sof_id', $orderData['order_number'])
            ->exists();

        if ($exists) {
            Log::channel('orders')->warning('Duplicate order ignored', [
                'order_number' => $orderData['order_number']
            ]);
            return;
        }

        // Extract store code from meta data
        $storeCode = $this->extractStoreCode($orderData['meta_data'] ?? []);

        if (!$storeCode) {
            throw new \Exception('Pickup store not found in order metadata');
        }

        // Resolve warehouse for this store
        $warehouse = $this->resolveWarehouse($storeCode);

        try {
            DB::beginTransaction();

            $billing = $orderData['billing_address'] ?? [];
            $shipping = $orderData['shipping_address'] ?? [];

            // Get customer name safely
            $customerName = $billing['name'] ??
                trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
            $customerName = $customerName ?: 'Guest Customer';

            // Combine shipping address
            $fullAddress = collect([
                $shipping['address_1'] ?? '',
                $shipping['address_2'] ?? '',
                $shipping['city'] ?? '',
                $shipping['state'] ?? '',
                $shipping['postcode'] ?? '',
                $shipping['country'] ?? ''
            ])->filter()->implode(', ');

            // Create main order
            $orderId = DB::connection('mysql')->table('orders')->insertGetId([
                'sof_id' => $orderData['order_number'],
                'requesting_store' => 'MarengEms Online',
                'requested_by' => $customerName,
                'mbc_card_no' => $this->getMeta($orderData['meta_data'], 'mbc_card_no'),
                'customer_name' => $customerName,
                'contact_number' => $billing['phone'] ?? null,
                'email' => $billing['email'] ?? null,
                'channel_order' => 'E-Commerce',
                'warehouse' => $warehouse,
                'time_order' => $orderData['date'],
                'payment_center' => $this->getMeta($orderData['meta_data'], 'payment_center'),
                'mode_payment' => $orderData['payment_method_title'] ?? 'N/A',
                'payment_date' => now()->toDateString(),
                'mode_dispatching' => $this->getMeta($orderData['meta_data'], 'mode_dispatching') ?? 'Delivery',
                'delivery_date' => $this->getMeta($orderData['meta_data'], 'delivery_date'),
                'address' => $fullAddress ?: 'N/A',
                'landmark' => $shipping['address_2'] ?? null,
                'order_status' => $this->mapOrderStatus($orderData['status']),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::channel('orders')->info('Order created', [
                'order_id' => $orderId,
                'sof_id' => $orderData['order_number'],
                'warehouse' => $warehouse,
                'store_code' => $storeCode
            ]);

            // Process each item
            $paymentMode = $orderData['payment_method_title'] ?? '';

            foreach ($orderData['items'] as $itemIndex => $item) {
                $this->processOrderItem($orderId, $item, $storeCode, $warehouse, $paymentMode, $itemIndex);
            }

            DB::commit();

            Log::channel('orders')->info('Order processed successfully', [
                'order_id' => $orderId,
                'woocommerce_order_id' => $orderData['order_id'],
                'items_count' => count($orderData['items'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('orders')->error('Failed to process order', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'woocommerce_order_id' => $orderData['order_id']
            ]);

            throw $e;
        }
    }

    /**
     * Process individual order item with complete discount and freebie logic
     */
    private function processOrderItem($orderId, array $item, string $storeCode, string $warehouse, string $paymentMode, int $index)
    {
        $sku = $item['sku'] ?? '';

        if (empty($sku)) {
            throw new \Exception("SKU is required for item at index {$index}");
        }

        // 1. Fetch product from store-specific table
        $product = $this->getProduct($sku, $storeCode);

        if (!$product) {
            throw new \Exception("SKU {$sku} not found in store {$storeCode}");
        }

        // 2. Get allocation and check stock
        $allocation = $this->getAllocation($sku, $warehouse);

        if (!$allocation) {
            throw new \Exception("No allocation record for SKU {$sku} in warehouse {$warehouse}");
        }

        // 3. Calculate quantities (WooCommerce sends 'quantity' not 'qty')
        $qtyCs = (int) ($item['quantity'] ?? $item['qty'] ?? 0);
        $casePack = (int) ($product->case_pack ?? 1);
        $qtyPc = $qtyCs * $casePack;
        $requiredQty = $qtyPc;

        // 4. Determine freebie scheme based on payment mode
        $freebieScheme = '';

        if ($paymentMode === 'PO15%') {
            $freebieScheme = $product->po15_scheme ?? '';
        } elseif (in_array($paymentMode, ['Cash / Bank Card', 'Cash'])) {
            $freebieScheme = $product->cash_bank_card_scheme ?? '';
        }

        Log::channel('orders')->debug('Processing item', [
            'sku' => $sku,
            'qty_cases' => $qtyCs,
            'payment_mode' => $paymentMode,
            'freebie_scheme' => $freebieScheme ?: 'none',
            'discount_scheme' => $product->discount_scheme ?? 'none'
        ]);

        // 5. Parse freebie scheme and check qualification
        $schemeData = $this->parseScheme($freebieScheme);
        $buyQty = $schemeData['buy'] ?? 0;
        $freeQty = $schemeData['free'] ?? 0;
        $schemeQualified = false;
        $totalFreebies = 0;

        if ($buyQty > 0 && $freeQty > 0 && $qtyCs >= $buyQty) {
            $schemeQualified = true;
            $schemeSets = floor($qtyCs / $buyQty);
            $totalFreebies = $schemeSets * $freeQty;

            // Add freebies to required quantity
            if (!empty($product->freebie_sku) && $totalFreebies > 0) {
                $requiredQty += ($totalFreebies * $casePack);
            }
        }

        // 6. Check available stock
        $availableQty = (int) ($allocation->wms_virtual_allocation ?? 0);

        if ($availableQty < $requiredQty) {
            throw new \Exception(
                "Insufficient stock for SKU {$sku}. Required: {$requiredQty}, Available: {$availableQty}"
            );
        }

        // 7. Calculate pricing with discount_scheme
        // WooCommerce sends 'total' not 'price'
        $itemTotal = (float) ($item['total'] ?? $item['price'] ?? 0);
        $pricePerPc = $itemTotal / $qtyPc;
        $rawPricePerCase = $pricePerPc * $casePack;

        // Apply discount_scheme (always applied if exists)
        $discountScheme = $product->discount_scheme ?? '';
        $pricePerCaseAfterDiscount = $rawPricePerCase;
        $discountApplied = 0;

        if (!empty($discountScheme)) {
            if (str_ends_with($discountScheme, '%')) {
                // Percentage discount
                $percentValue = (float) rtrim($discountScheme, '%');
                $discountApplied = $rawPricePerCase * ($percentValue / 100);
                $pricePerCaseAfterDiscount = $rawPricePerCase - $discountApplied;

                Log::channel('orders')->debug('Percentage discount applied', [
                    'sku' => $sku,
                    'discount' => $discountScheme,
                    'original' => $rawPricePerCase,
                    'final' => $pricePerCaseAfterDiscount
                ]);
            } else {
                // Fixed value discount
                $discountApplied = (float) $discountScheme;
                $pricePerCaseAfterDiscount = max(0, $rawPricePerCase - $discountApplied);

                Log::channel('orders')->debug('Fixed discount applied', [
                    'sku' => $sku,
                    'discount' => $discountScheme,
                    'original' => $rawPricePerCase,
                    'final' => $pricePerCaseAfterDiscount
                ]);
            }
        }

        $totalAmount = $pricePerCaseAfterDiscount * $qtyCs;

        // 8. Apply additional item discount if provided
        $itemDiscountRaw = $item['discount'] ?? 0;
        $itemDiscount = 0;

        if (is_string($itemDiscountRaw) && str_ends_with($itemDiscountRaw, '%')) {
            $itemDiscount = $totalAmount * ((float) rtrim($itemDiscountRaw, '%') / 100);
        } elseif (!empty($itemDiscountRaw)) {
            $itemDiscount = (float) $itemDiscountRaw;
        }

        $finalPrice = max($totalAmount - $itemDiscount, 0);

        // 9. Determine item type
        $itemType = 'MAIN';
        if ($discountApplied > 0 || $itemDiscount > 0) {
            $itemType = 'DISCOUNT';
        }

        // 10. Insert main item
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'sku' => $sku,
            'item_description' => $product->description ?? 'Unknown Item',
            'scheme' => $freebieScheme ?: null,
            'price_per_pc' => round($pricePerPc, 2),
            'price' => round($pricePerCaseAfterDiscount, 2),
            'qty_per_pc' => $casePack,
            'qty_per_cs' => $qtyCs,
            'freebies_per_cs' => $schemeQualified ? $freeQty : 0,
            'discount' => $discountScheme ?: ($itemDiscountRaw ?: 0),
            'total_qty' => $qtyPc,
            'amount' => round($finalPrice, 2),
            'remarks' => $discountScheme ? "Discount: {$discountScheme}" : null,
            'store_order_no' => null,
            'item_type' => $itemType,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::channel('orders')->info('Main item inserted', [
            'order_id' => $orderId,
            'sku' => $sku,
            'qty_cases' => $qtyCs,
            'price' => $pricePerCaseAfterDiscount,
            'amount' => $finalPrice,
            'item_type' => $itemType
        ]);

        // 11. Deduct from allocation
        DB::table('product_wms_allocations')
            ->where('sku', $sku)
            ->where('warehouse_code', $warehouse)
            ->decrement('wms_virtual_allocation', $requiredQty);

        Log::channel('orders')->debug('Allocation decremented', [
            'sku' => $sku,
            'warehouse' => $warehouse,
            'deducted' => $requiredQty,
            'remaining' => $availableQty - $requiredQty
        ]);

        // 12. Insert freebie item if qualified
        if ($schemeQualified && !empty($product->freebie_sku) && $totalFreebies > 0) {

            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'sku' => $product->freebie_sku,
                'item_description' => 'Freebie - ' . ($product->description ?? 'Item'),
                'scheme' => $freebieScheme,
                'price_per_pc' => 0,
                'price' => 0,
                'qty_per_pc' => 0,
                'qty_per_cs' => 0,
                'freebies_per_cs' => $freeQty,
                'discount' => 0,
                'total_qty' => $totalFreebies * $casePack,
                'amount' => 0,
                'remarks' => "Freebie: {$freebieScheme} (Payment: {$paymentMode})",
                'store_order_no' => null,
                'item_type' => 'FREEBIE',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::channel('orders')->info('Freebie item added', [
                'order_id' => $orderId,
                'main_sku' => $sku,
                'freebie_sku' => $product->freebie_sku,
                'scheme' => $freebieScheme,
                'qty_cases' => $qtyCs,
                'sets' => $schemeSets,
                'total_freebies' => $totalFreebies
            ]);
        }
    }

    /**
     * Get product from store-specific table
     */
    private function getProduct(string $sku, string $storeCode)
    {
        return DB::table("products_{$storeCode}")
            ->where('sku', $sku)
            ->whereNull('archived_at')
            ->first();
    }

    /**
     * Get allocation with lock for concurrency
     */
    private function getAllocation(string $sku, string $warehouse)
    {
        return DB::table('product_wms_allocations')
            ->where('sku', $sku)
            ->where('warehouse_code', $warehouse)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Extract store code from meta data
     */
    private function extractStoreCode(array $metaData): ?string
    {
        foreach ($metaData as $meta) {
            if (($meta['key'] ?? '') === '_pickup_store') {
                return $this->locationMap[$meta['value']] ?? null;
            }
        }
        return null;
    }

    /**
     * Resolve warehouse from store code
     */
    private function resolveWarehouse(string $storeCode): string
    {
        foreach ($this->warehouseConfig as $warehouse => $config) {
            if (in_array($storeCode, $config['stores'], true)) {
                return $warehouse;
            }
        }

        throw new \Exception("No warehouse mapped for store {$storeCode}");
    }

    /**
     * Get meta value by key
     */
    private function getMeta(array $meta, string $key)
    {
        foreach ($meta as $m) {
            if (($m['key'] ?? null) === $key) {
                return $m['value'] ?? null;
            }
        }
        return null;
    }

    /**
     * Parse scheme string (format: "12+1")
     */
    private function parseScheme(?string $scheme): array
    {
        if (empty($scheme)) {
            return ['buy' => 0, 'free' => 0];
        }

        // Remove invalid characters
        $scheme = preg_replace('/[^0-9+]/', '', $scheme);
        $parts = array_filter(explode('+', $scheme), fn($v) => $v !== '');

        if (count($parts) === 0 || $scheme === '0') {
            return ['buy' => 0, 'free' => 0];
        }

        if (count($parts) === 1) {
            return ['buy' => (int) $parts[0], 'free' => 1];
        }

        return [
            'buy' => (int) ($parts[0] ?? 0),
            'free' => (int) ($parts[1] ?? 0)
        ];
    }

    /**
     * Map WooCommerce status to internal status
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
     * Test endpoint
     */
    public function test(Request $request)
    {
        Log::channel('orders')->info('Test endpoint called', [
            'payload' => $request->all()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API is working correctly',
            'received_data' => $request->all(),
            'timestamp' => now()->toIso8601String(),
            'server_time' => now()->toDateTimeString()
        ], 200);
    }
}
