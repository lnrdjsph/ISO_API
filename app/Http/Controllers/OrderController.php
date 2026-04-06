<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;

use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Mail\OrderApprovalRequestMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;


class OrderController extends Controller
{

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Order::query()->with('items');

        // 🗺️ Region -> store mapping
        $storeMapping = [
            'lz' => ['6012'], // Luzon = only Antipolo
            'vs' => ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010'], // Visayas
        ];

        // Default
        $allowedStatuses = null;

        // 👔 Role-based filters
        if ($user->role === 'manager') {
            $allowedStatuses = ['for approval', 'approved', 'rejected'];
            $query->whereIn('order_status', $allowedStatuses);

            // Restrict managers by region
            if ($user->user_location && isset($storeMapping[$user->user_location])) {
                $query->whereIn('requesting_store', $storeMapping[$user->user_location]);
            }
        } elseif ($user->role === 'super admin') {
            // 🔓 Super admin sees all — no restrictions
            $allowedStatuses = null;
        } else {
            // Regular user → single store restriction
            if ($user->user_location) {
                $query->where('requesting_store', $user->user_location);
            }
        }

        // 🔍 Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('sof_id', 'like', "%{$search}%")
                    ->orWhere('requesting_store', 'like', "%{$search}%");
            });
        }

        // 📦 Channel filter
        if ($channel = $request->input('channel')) {
            $query->where('channel_order', $channel);
        }

        // 🏬 Store filter
        if ($storeCode = $request->input('store_code')) {
            $query->where('requesting_store', $storeCode);
        }

        // ✅ Status filter
        if ($status = $request->input('status')) {
            if ($allowedStatuses) {
                if (in_array($status, $allowedStatuses)) {
                    $query->where('order_status', $status);
                }
            } else {
                $query->where('order_status', $status);
            }
        }

        // 📅 Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('time_order', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('time_order', '<=', $endDate);
        }

        // ✅ Apply ordering based on role
        if ($user->role === 'manager') {
            // For managers: show 'for approval' first, then order by created_at desc
            $query->orderByRaw("CASE WHEN order_status = 'for approval' THEN 0 ELSE 1 END")
                ->orderByDesc('created_at');
        } else {
            // For other roles: just order by created_at desc
            $query->orderByDesc('created_at');
        }

        // ✅ Pagination
        $perPage = $request->input('per_page', 10);

        $orders = $query->paginate($perPage)
            ->onEachSide(2)
            ->withQueryString();

        // Dropdown data
        $channels = Order::select('channel_order')->distinct()->pluck('channel_order');
        $statuses = $allowedStatuses ?? Order::select('order_status')->distinct()->pluck('order_status');

        // 🏪 All store names
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

        // 🎯 Dropdown restriction
        if ($user->role === 'super admin') {
            $storeLocations = $allStoreLocations; // show all stores
        } elseif ($user->role === 'manager') {
            if ($user->user_location && isset($storeMapping[$user->user_location])) {
                $storeLocations = Arr::only($allStoreLocations, $storeMapping[$user->user_location]);
            } else {
                $storeLocations = $allStoreLocations;
            }
        } else {
            if ($user->user_location) {
                $storeLocations = Arr::only($allStoreLocations, [$user->user_location]);
            } else {
                $storeLocations = $allStoreLocations;
            }
        }

        if ($request->ajax()) {
            return view('orders.partials.table', compact('orders'))->render();
        }
        return view('orders.orders', compact('orders', 'channels', 'statuses', 'perPage', 'storeLocations'));
    }






    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.show', compact('order'));
    }

    // public function create()
    // {
    //     $orders = Order::with('items')->get();
    //     return view('orders.create', compact('orders'));
    // }



    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'channel_order' => 'required|string',
    //         'time_order' => 'required',
    //         'payment_center' => 'required|string',
    //         'mode_payment' => 'required|string',
    //         'payment_date' => 'required|date',
    //         'mode_dispatching' => 'required|string',
    //         'delivery_date' => 'required|date',
    //         'address' => 'nullable|string',
    //         'landmark' => 'nullable|string',

    //         // New: Customer + Requesting Info
    //         'requesting_store' => 'required|string',
    //         'requested_by' => 'required|string',
    //         'mbc_card_no' => 'required|digits:16',
    //         'customer_name' => 'required|string',
    //         'contact_number' => 'required|string|regex:/^[0-9]{11}$/',

    //         // Order items
    //         'orders' => 'required|array',
    //     ]);

    //     foreach ($request->input('orders', []) as $index => $order) {
    //         $validated['orders'][$index] = validator($order, [
    //             'sku' => 'required|string',
    //             'item_description' => 'required|string',
    //             'price_per_pc' => 'required|numeric',
    //             'price' => 'required|numeric',
    //             'qty_per_cs' => 'required|integer',
    //             'qty_per_pc' => 'required|integer',
    //             'scheme' => 'required|string',
    //             'total_qty' => 'required|integer',
    //             'freebies_per_cs' => 'required|integer',
    //             'amount' => 'required|numeric',
    //             'remarks' => 'required|string',
    //             'store_order_no' => 'required|string',
    //         ])->validate();
    //     }
    //             // Save main order info
    //     $order = Order::create([
    //         'channel_order' => $validated['channel_order'],
    //         'time_order' => $validated['time_order'],
    //         'payment_center' => $validated['payment_center'],
    //         'mode_payment' => $validated['mode_payment'],
    //         'payment_date' => $validated['payment_date'],
    //         'mode_dispatching' => $validated['mode_dispatching'],
    //         'delivery_date' => $validated['delivery_date'],
    //         'address' => $validated['address'] ?? null,
    //         'landmark' => $validated['landmark'] ?? null,

    //         // Save customer + request info
    //         'requesting_store' => $validated['requesting_store'],
    //         'requested_by' => $validated['requested_by'],
    //         'mbc_card_no' => $validated['mbc_card_no'],
    //         'customer_name' => $validated['customer_name'],
    //         'contact_number' => $validated['contact_number'],
    //     ]);

    //     // Save each item
    //     foreach ($validated['orders'] as $item) {
    //         $order->items()->create([
    //             'sku' => $item['sku'] ?? null,
    //             'item_description' => $item['item_description'] ?? null,
    //             'scheme' => $item['scheme'] ?? null,
    //             'price_per_pc' => $item['price_per_pc'] ?? 0,
    //             'price' => $item['price'] ?? 0,
    //             'qty_per_pc' => $item['qty_per_pc'] ?? 0,
    //             'qty_per_cs' => $item['qty_per_cs'] ?? 0,
    //             'freebies_per_cs' => $item['freebies_per_cs'] ?? 0,
    //             'total_qty' => $item['total_qty'] ?? 0,
    //             'amount' => $item['amount'] ?? 0,
    //             'remarks' => $item['remarks'] ?? null,
    //             'store_order_no' => $item['store_order_no'] ?? null,
    //         ]);
    //     }

    //     return redirect()->route('orders.create')->with('success', 'Order created successfully.');
    // }

    public function update(Request $request, $id)
    {
        // Log incoming payload for debugging
        Log::info('Order update request payload', $request->all());

        // Use manual validator so we can log validation errors and return a friendly response
        $validator = Validator::make($request->all(), [
            // Customer Info
            'mbc_card_no' => 'nullable|string|max:16',
            'customer_name' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:12',
            'email' => 'nullable|email|max:100',

            // Payment Info
            'payment_center' => 'nullable|string',
            'mode_payment' => 'nullable|string',
            'payment_date' => 'nullable|date',

            // Delivery Info
            'mode_dispatching' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'landmark' => 'nullable|string|max:255',

            // Items validation
            'items' => 'required|array',
            'items.*.id' => 'required|exists:order_items,id',
            'items.*.sku' => 'required|string|max:255',
            'items.*.item_description' => 'required|string|max:500',
            'items.*.scheme' => 'required|string',
            'items.*.price_per_pc' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty_per_pc' => 'required|integer|min:0',
            'items.*.qty_per_cs' => 'required|integer|min:0',
            'items.*.freebies_per_cs' => 'nullable|integer|min:0',
            'items.*.freebie_sku' => 'nullable|string|max:255',
            'items.*.sale_type' => 'nullable|string|max:50',
            'items.*.total_qty' => 'required|integer|min:0',
            'items.*.discount' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:255',
            'items.*.store_order_no' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Order update validation failed', $validator->errors()->toArray());
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            // Find the order
            $order = Order::findOrFail($id);

            // Track changes for notes
            $changes = [];

            // === ORDER FIELDS CHANGES ===
            $orderFields = [
                'mbc_card_no',
                'customer_name',
                'contact_number',
                'email',
                'payment_center',
                'mode_payment',
                'payment_date',
                'mode_dispatching',
                'delivery_date',
                'address',
                'landmark',
            ];

            foreach ($orderFields as $field) {
                $old = $order->$field;
                $new = $validated[$field] ?? null;

                if ($old != $new) {
                    $changes[] = ucfirst(str_replace('_', ' ', $field)) . " changed from '{$old}' to '{$new}'";
                }
            }

            // Update order fields
            $order->update(Arr::only($validated, $orderFields));

            // Get warehouse for inventory adjustments
            $warehouseCode = $order->warehouse ?? null;

            if (!$warehouseCode) {
                throw new \Exception("Order does not have a warehouse assigned. Cannot adjust inventory.");
            }

            // === ORDER ITEMS CHANGES WITH INVENTORY ADJUSTMENT ===
            foreach ($validated['items'] as $itemData) {
                $orderItem = OrderItem::findOrFail($itemData['id']);
                $oldData = $orderItem->toArray();

                // === ADJUST INVENTORY BEFORE UPDATING ===
                $this->adjustInventoryOnUpdate($orderItem, $itemData, $warehouseCode, $changes);

                // Calculate amount
                $price = $itemData['price'];
                $discount = $itemData['discount'] ?? 0;
                $price = $price - floatval($discount);
                $calculatedAmount = $price * $itemData['total_qty'];

                // Update order item
                $orderItem->update([
                    'sku' => $itemData['sku'],
                    'item_description' => $itemData['item_description'],
                    'scheme' => $itemData['scheme'],
                    'price_per_pc' => $itemData['price_per_pc'],
                    'price' => $itemData['price'],
                    'qty_per_pc' => $itemData['qty_per_pc'],
                    'qty_per_cs' => $itemData['qty_per_cs'],
                    'freebies_per_cs' => $itemData['freebies_per_cs'] ?? null,
                    'freebie_sku' => $itemData['freebie_sku'] ?? null,
                    'sale_type' => $itemData['sale_type'] ?? null,
                    'total_qty' => $itemData['total_qty'],
                    'discount' => $itemData['discount'],
                    'amount' => $calculatedAmount,
                    'remarks' => $itemData['remarks'],
                    'store_order_no' => $itemData['store_order_no'],
                ]);

                // Compare old vs new for notes
                foreach ($orderItem->getChanges() as $field => $newVal) {
                    if ($field == 'updated_at') continue;

                    $oldVal = $oldData[$field] ?? null;
                    if ($oldVal != $newVal) {
                        $changes[] = "Item {$orderItem->sku} - " . ucfirst(str_replace('_', ' ', $field)) . " changed from '{$oldVal}' to '{$newVal}'";
                    }
                }
            }

            // === SAVE NOTES IF THERE ARE CHANGES ===
            if (!empty($changes)) {
                $order->notes()->create([
                    'user_id' => auth()->id(),
                    'status'  => "updated",
                    'note' => nl2br("• " . implode("\n• ", $changes)),
                ]);
            }

            DB::commit();

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order updated successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Order update exception: ' . $e->getMessage(), ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while updating the order: ' . $e->getMessage()]);
        }
    }

    /**
     * Adjust inventory allocation based on quantity changes
     * 
     * @param OrderItem $orderItem - The existing order item
     * @param array $newData - The new item data from request
     * @param string $warehouseCode - The warehouse code
     * @param array &$changes - Reference to changes array for logging
     */
    private function adjustInventoryOnUpdate($orderItem, $newData, $warehouseCode, &$changes)
    {
        $userLocation = auth()->user()->user_location;
        $tableName = 'products_' . strtolower($userLocation);

        // === MAIN ITEM ADJUSTMENT ===
        $oldQty = $orderItem->total_qty;
        $newQty = $newData['total_qty'];
        $qtyDifference = $newQty - $oldQty; // Positive = increase (need to deduct more), Negative = decrease (need to add back)

        if ($qtyDifference != 0) {
            $sku = strtoupper($newData['sku']);

            // Get product
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', $sku)
                ->first();

            if (!$product) {
                Log::warning("Product not found in {$tableName} for SKU: {$sku}");
            } else {
                // === 1) ADJUST allocation_per_case ===
                $currentCaseAllocation = $product->allocation_per_case ?? 0;

                // If qtyDifference is positive (increased), we deduct more (subtract)
                // If qtyDifference is negative (decreased), we add back (add)
                $newCaseAllocation = max(0, $currentCaseAllocation - $qtyDifference);

                DB::connection('mysql')
                    ->table($tableName)
                    ->where('id', $product->id)
                    ->update([
                        'allocation_per_case' => $newCaseAllocation,
                        'updated_at' => now(),
                    ]);

                $changeType = $qtyDifference > 0 ? 'increased' : 'decreased';
                $absQtyDiff = abs($qtyDifference);
                Log::info("Main Item Allocation Adjusted - SKU: {$sku}, Qty {$changeType} by {$absQtyDiff}, Previous: {$currentCaseAllocation}, New Balance: {$newCaseAllocation}");
                $changes[] = "Inventory adjusted for SKU {$sku}: Qty {$changeType} by {$absQtyDiff} (Allocation: {$currentCaseAllocation} → {$newCaseAllocation})";

                // === 2) ADJUST wms_virtual_allocation ===
                $qtyPerPc = $newData['qty_per_pc'] ?? $product->qty_per_pc ?? 0;

                if ($qtyPerPc == 0) {
                    Log::warning("qty_per_pc is 0 for SKU: {$sku}. Skipping WMS adjustment.");
                } else {
                    $piecesDifference = $qtyDifference * $qtyPerPc;

                    $wmsAllocation = DB::connection('mysql')
                        ->table('product_wms_allocations')
                        ->where('sku', $sku)
                        ->where('warehouse_code', $warehouseCode)
                        ->first();

                    if (!$wmsAllocation) {
                        Log::warning("WMS allocation record not found for SKU: {$sku}, Warehouse: {$warehouseCode}");
                    } else {
                        $currentWmsPieces = $wmsAllocation->wms_virtual_allocation ?? 0;

                        // If piecesDifference is positive (increased), we deduct more (subtract)
                        // If piecesDifference is negative (decreased), we add back (add)
                        $newWmsPieces = max(0, $currentWmsPieces - $piecesDifference);

                        DB::connection('mysql')
                            ->table('product_wms_allocations')
                            ->where('sku', $sku)
                            ->where('warehouse_code', $warehouseCode)
                            ->update([
                                'wms_virtual_allocation' => $newWmsPieces,
                                'updated_at' => now(),
                            ]);

                        Log::info("Main Item WMS Adjusted - SKU: {$sku}, Warehouse: {$warehouseCode}, Pieces {$changeType} by " . abs($piecesDifference) . ", Previous: {$currentWmsPieces}, New Balance: {$newWmsPieces}");
                    }
                }
            }
        }

        // === FREEBIE ITEM ADJUSTMENT ===
        $oldFreebieQty = $orderItem->freebies_per_cs ?? 0;
        $newFreebieQty = $newData['freebies_per_cs'] ?? 0;
        $oldSaleType = $orderItem->sale_type ?? '';
        $newSaleType = $newData['sale_type'] ?? '';

        // Check if freebies changed and sale_type is 'Freebie'
        $shouldProcessFreebies = ($newSaleType == 'Freebie') && ($oldFreebieQty != $newFreebieQty);

        if ($shouldProcessFreebies) {
            $freebieQtyDifference = $newFreebieQty - $oldFreebieQty;
            $freebieSku = strtoupper($newData['freebie_sku'] ?? $newData['sku']);

            // Get freebie product
            $freebieProduct = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', $freebieSku)
                ->first();

            if (!$freebieProduct) {
                Log::warning("Freebie product not found in {$tableName} for SKU: {$freebieSku}");
            } else {
                // === 1) ADJUST freebie allocation_per_case ===
                $currentFreebieCaseAllocation = $freebieProduct->allocation_per_case ?? 0;
                $newFreebieCaseAllocation = max(0, $currentFreebieCaseAllocation - $freebieQtyDifference);

                DB::connection('mysql')
                    ->table($tableName)
                    ->where('id', $freebieProduct->id)
                    ->update([
                        'allocation_per_case' => $newFreebieCaseAllocation,
                        'updated_at' => now(),
                    ]);

                $freebieChangeType = $freebieQtyDifference > 0 ? 'increased' : 'decreased';
                $absFreebieQtyDiff = abs($freebieQtyDifference);
                Log::info("Freebie Allocation Adjusted - SKU: {$freebieSku}, Qty {$freebieChangeType} by {$absFreebieQtyDiff}, Previous: {$currentFreebieCaseAllocation}, New Balance: {$newFreebieCaseAllocation}");
                $changes[] = "Freebie inventory adjusted for SKU {$freebieSku}: Qty {$freebieChangeType} by {$absFreebieQtyDiff} (Allocation: {$currentFreebieCaseAllocation} → {$newFreebieCaseAllocation})";

                // === 2) ADJUST freebie wms_virtual_allocation ===
                $freebieQtyPerPc = $freebieProduct->qty_per_pc ?? 0;

                if ($freebieQtyPerPc == 0) {
                    Log::warning("qty_per_pc is 0 for Freebie SKU: {$freebieSku}. Skipping WMS adjustment.");
                } else {
                    $freebiePiecesDifference = $freebieQtyDifference * $freebieQtyPerPc;

                    $freebieWmsAllocation = DB::connection('mysql')
                        ->table('product_wms_allocations')
                        ->where('sku', $freebieSku)
                        ->where('warehouse_code', $warehouseCode)
                        ->first();

                    if (!$freebieWmsAllocation) {
                        Log::warning("WMS allocation record not found for Freebie SKU: {$freebieSku}, Warehouse: {$warehouseCode}");
                    } else {
                        $currentFreebieWmsPieces = $freebieWmsAllocation->wms_virtual_allocation ?? 0;
                        $newFreebieWmsPieces = max(0, $currentFreebieWmsPieces - $freebiePiecesDifference);

                        DB::connection('mysql')
                            ->table('product_wms_allocations')
                            ->where('sku', $freebieSku)
                            ->where('warehouse_code', $warehouseCode)
                            ->update([
                                'wms_virtual_allocation' => $newFreebieWmsPieces,
                                'updated_at' => now(),
                            ]);

                        Log::info("Freebie WMS Adjusted - SKU: {$freebieSku}, Warehouse: {$warehouseCode}, Pieces {$freebieChangeType} by " . abs($freebiePiecesDifference) . ", Previous: {$currentFreebieWmsPieces}, New Balance: {$newFreebieWmsPieces}");
                    }
                }
            }
        }
    }

    public function archive(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->id);
        $this->revertAllocationStock($order->id);

        $order->order_status = 'archived';
        $order->save();

        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'archived',
            'note'    => 'Order archived',
        ]);

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order archived successfully.');
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'id'   => 'required|exists:orders,id',
            'note' => 'required|string', // require reason
        ]);

        $order = Order::findOrFail($request->id);

        // Only revert allocation if the order was NOT already rejected
        if ($order->order_status !== 'rejected') {
            $this->revertAllocationStock($order->id);
        }

        $order->order_status = 'cancelled';
        $order->save();

        // Log note with reason
        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'cancelled',
            'note'    => 'Order was cancelled. <br> Reason: ' . $request->note,
        ]);

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order cancelled successfully.');
    }


    public function restore(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->id);
        $order->order_status = 'new order';
        $this->deductAllocationStock($order->id);
        $order->save();

        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'restored',
            'note'    => 'Order restored',
        ]);

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order restored successfully.');
    }

    //complete order
    public function complete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->id);
        $order->order_status = 'completed';
        // $this->deductAllocationStock($order->id);
        $order->save();

        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'completed',
            'note'    => 'Order completed',
        ]);

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order completed successfully.');
    }

    /**
     * Sends an order for approval, updates status, logs a note, and notifies the appropriate recipients via email.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request containing the order ID.
     * @return \Illuminate\Http\RedirectResponse Redirects to the order details page with a success message.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the order is not found.
     * @throws \Illuminate\Validation\ValidationException If validation fails.
     */
    public function forApproval(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->id);
        $previousStatus = $order->order_status;
        $order->order_status = 'for approval';

        if ($previousStatus === 'rejected') {
            $this->deductAllocationStock($order->id);
        }

        $order->save();

        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'for approval',
            'note'    => 'Order sent for approval',
        ]);

        // 🔔 Determine recipient based on requesting_store
        $recipients = [];

        if (in_array($order->requesting_store, ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010'])) {
            $recipients[] = 'akehide.tecson@metroretail.ph';
        }

        if (in_array($order->requesting_store, ['6012'])) {
            $recipients[] = 'akehide.tecson@metroretail.ph';
        }

        // If recipients found, send email
        $recipients = array_unique($recipients);
        if (!empty($recipients)) {
            Mail::to($recipients)->send(new OrderApprovalRequestMail($order));
        }
        // Check if mail was attempted (Laravel's Mail::send does not throw unless there is a hard error)
        $emailAttempted = !empty($recipients);

        $successMsg = 'Order requested for approval successfully.';
        if ($emailAttempted) {
            $successMsg .= ' Email notification was attempted to be sent.';
        } else {
            $successMsg .= ' No email recipients found, so no email was sent.';
        }

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', $successMsg)
            ->with('success', 'Order requested for approval successfully and email sent.');
    }


    public function approveOrder(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB limit
        ]);

        $order = Order::findOrFail($request->id);

        // ✅ Save the file
        $filePath = null;
        if ($request->hasFile('attachment')) {
            // Optional: Delete old file if you want to replace it
            if ($order->approval_document) {
                Storage::disk('public')->delete($order->approval_document);
            }

            $filePath = $request->file('attachment')->store(
                'order_approvals/' . $order->id, // folder per order
                'public' // use the `public` disk
            );
        }

        // ✅ Update order
        $order->order_status = 'approved';
        $order->approval_document = $filePath;
        $order->save();

        // ✅ Add note
        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'approved',
            'note'    => "Order approved by:<br>" .
                "<strong>" . auth()->user()->name . "</strong><br>" .
                ucfirst(auth()->user()->role)
        ]);

        // ✅ Send email to requester
        $requester = \App\Models\User::find($order->requested_by);

        if ($requester && $requester->email) {
            Mail::to($requester->email)->send(new \App\Mail\OrderApprovedMail($order));
        }

        $successMessage = $filePath
            ? 'Order approved successfully with document attached, and requester notified.'
            : 'Order approved successfully without an attachment. Requester notified.';

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', $successMessage);
    }


    public function rejectOrder(Request $request)
    {
        $request->validate([
            'id'   => 'required|exists:orders,id',
            'note' => 'required|string', // require reason
        ]);

        $order = Order::findOrFail($request->id);
        $order->order_status = 'rejected';
        // $this->revertAllocationStock($order->id);
        $order->save();

        // Log note with reason
        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'rejected',
            'note'    => 'Order was rejected. Reason: ' . $request->note,
        ]);

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order rejected successfully.');
    }




    // public function handleCompletedOrProcessing($order)
    // {
    //     // Example: call your deductAllocations logic
    //     $this->deductAllocations($order);

    //     // or do logging
    //     Log::info("Order {$order->id} status set to {$order->order_status}, allocations updated.");
    // }

    // public function deductAllocationStock($orderId)
    // {
    //     $userLocation = strtolower(auth()->user()->user_location);
    //     $tableName = 'products_' . $userLocation;

    //     // Get order with items
    //     $order = Order::with('items')->findOrFail($orderId);

    //     foreach ($order->items as $item) {
    //         // Find product by SKU in location-specific table
    //         $product = DB::connection('mysql')
    //             ->table($tableName)
    //             ->where('sku', strtoupper($item->sku))
    //             ->first();

    //         if ($product) {
    //             // Deduct grand total qty directly (no case conversion)
    //             $newAllocation = max(0, ($product->allocation_per_case ?? 0) - $item->total_qty);

    //             DB::connection('mysql')
    //                 ->table($tableName)
    //                 ->where('id', $product->id)
    //                 ->update([
    //                     'allocation_per_case' => $newAllocation,
    //                     'updated_at' => now(),
    //                 ]);
    //         }
    //     }

    //     return true;
    // }



    public function revertAllocationStock($orderId)
    {
        $userLocation = auth()->user()->user_location;
        if (!$userLocation) return false;

        $tableName = 'products_' . strtolower($userLocation);
        $warehouseCode = $this->getWarehouseCodeByLocation($userLocation);

        // Load order with items
        $order = Order::with('items')->findOrFail($orderId);

        // Normalize items (if ever a collection of orders is passed)
        if ($order instanceof \Illuminate\Database\Eloquent\Collection || $order instanceof \Illuminate\Support\Collection) {
            $items = $order->pluck('items')->flatten();
        } else {
            $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        }

        foreach ($items as $item) {

            /* -------------------------------
            * 1) REVERT CASE ALLOCATION
            * ------------------------------- */
            $product = DB::table($tableName)
                ->where('sku', strtoupper($item->sku))
                ->first();

            if ($product) {

                $casesToReturn = $item->total_qty;
                $currentCaseAllocation = $product->allocation_per_case ?? 0;
                $newCaseAllocation = $currentCaseAllocation + $casesToReturn;

                // Update case allocation
                DB::table($tableName)
                    ->where('id', $product->id)
                    ->update([
                        'allocation_per_case' => $newCaseAllocation,
                        'updated_at' => now(),
                    ]);

                $itemType = $item->item_type ?? 'MAIN';
                Log::info("Case Revert - SKU: {$item->sku}, Type: {$itemType}, Returned: {$casesToReturn}, Previous: {$currentCaseAllocation}, New Balance: {$newCaseAllocation}");


                /* ---------------------------------------
                * 2) REVERT WMS VIRTUAL ALLOCATION (PIECES)
                * --------------------------------------- */

                // get qty_per_pc from item (preferred), fallback to product
                $qtyPerPc = $item->qty_per_pc ?? $product->qty_per_pc ?? 0;

                if ($qtyPerPc == 0) {
                    Log::warning("qty_per_pc is 0 for SKU: {$item->sku}. Cannot revert WMS pieces.");
                    continue;
                }

                // pieces = total_qty × qty_per_pc
                $piecesToReturn = $item->total_qty * $qtyPerPc;

                $wmsAllocation = DB::table('product_wms_allocations')
                    ->where('sku', strtoupper($item->sku))
                    ->where('warehouse_code', $warehouseCode)
                    ->first();

                if (!$wmsAllocation) {
                    Log::warning("WMS revert failed — record missing for SKU: {$item->sku}, Warehouse: {$warehouseCode}");
                    continue;
                }

                $currentWmsPieces = $wmsAllocation->wms_virtual_allocation ?? 0;
                $newWmsPieces = $currentWmsPieces + $piecesToReturn;

                // Update WMS pieces
                DB::table('product_wms_allocations')
                    ->where('sku', strtoupper($item->sku))
                    ->where('warehouse_code', $warehouseCode)
                    ->update([
                        'wms_virtual_allocation' => $newWmsPieces,
                        'updated_at' => now(),
                    ]);

                Log::info("WMS Pieces Revert - SKU: {$item->sku}, Type: {$itemType}, Warehouse: {$warehouseCode}, Pieces Returned: {$piecesToReturn}, Previous: {$currentWmsPieces}, New Balance: {$newWmsPieces}");
            } else {
                Log::warning("Product not found in {$tableName} for SKU: {$item->sku}, cannot revert.");
            }
        }

        return true;
    }


    public function deductAllocationStock($orderId)
    {
        $userLocation = auth()->user()->user_location;
        $tableName = 'products_' . strtolower($userLocation);
        $warehouseCode = $this->getWarehouseCodeByLocation($userLocation);

        // Load order and items
        $order = Order::with('items')->findOrFail($orderId);

        // Normalize items: handle cases where $order might be a Collection of orders or a single model
        if ($order instanceof \Illuminate\Database\Eloquent\Collection || $order instanceof \Illuminate\Support\Collection) {
            // $order is a collection of orders; collect all items across them
            $items = $order->pluck('items')->flatten();
        } else {
            // $order is a single model; get relation collection safely
            $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();
        }

        foreach ($items as $item) {
            // Find product in location table
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', strtoupper($item->sku))
                ->first();

            if ($product) {
                /** --------------------------------------------
                 * 1) Deduct allocation_per_case by total_qty (number of cases or freebies)
                 * -------------------------------------------- */
                $casesDeduction = $item->total_qty; // Number of cases/freebies to deduct
                $currentCaseAllocation = $product->allocation_per_case ?? 0;
                $newCaseAllocation = max(0, $currentCaseAllocation - $casesDeduction);

                // Update allocation_per_case
                DB::connection('mysql')
                    ->table($tableName)
                    ->where('id', $product->id)
                    ->update([
                        'allocation_per_case' => $newCaseAllocation,
                        'updated_at' => now(),
                    ]);

                $itemType = $item->item_type ?? 'MAIN';
                Log::info("Case Deduction - SKU: {$item->sku}, Type: {$itemType}, Cases Deducted: {$casesDeduction}, Previous: {$currentCaseAllocation}, New Balance: {$newCaseAllocation}");

                /** ----------------------------------------------------------
                 * 2) Deduct wms_virtual_allocation by (total_qty × qty_per_pc)
                 *    This converts cases/freebies to pieces for WMS tracking
                 * ---------------------------------------------------------- */

                // Get qty_per_pc from item, fallback to product
                $qtyPerPc = $item->qty_per_pc ?? $product->qty_per_pc ?? 0;

                // If qty_per_pc is still 0, log warning and skip WMS deduction
                if ($qtyPerPc == 0) {
                    Log::warning("qty_per_pc is 0 for SKU: {$item->sku}, Item ID: {$item->id}, Type: {$itemType}. Skipping WMS deduction.");
                    continue;
                }

                // Calculate pieces deduction: total_qty × pieces per case
                $piecesDeduction = $item->total_qty * $qtyPerPc;

                // Get current wms allocation
                $wmsAllocation = DB::connection('mysql')
                    ->table('product_wms_allocations')
                    ->where('sku', strtoupper($item->sku))
                    ->where('warehouse_code', $warehouseCode)
                    ->first();

                if (!$wmsAllocation) {
                    Log::warning("WMS allocation record not found for SKU: {$item->sku}, Warehouse: {$warehouseCode}, Type: {$itemType}");
                    continue;
                }

                $currentWmsPieces = $wmsAllocation->wms_virtual_allocation ?? 0;
                $newWmsPieces = max(0, $currentWmsPieces - $piecesDeduction);

                // Update wms_virtual_allocation
                $updated = DB::connection('mysql')
                    ->table('product_wms_allocations')
                    ->where('sku', strtoupper($item->sku))
                    ->where('warehouse_code', $warehouseCode)
                    ->update([
                        'wms_virtual_allocation' => $newWmsPieces,
                        'updated_at' => now(),
                    ]);

                Log::info("WMS Pieces Deduction - SKU: {$item->sku}, Type: {$itemType}, Warehouse: {$warehouseCode}, Qty: {$item->total_qty}, Pieces/Unit: {$qtyPerPc}, Total Pieces Deducted: {$piecesDeduction}, Previous: {$currentWmsPieces}, New Balance: {$newWmsPieces}, Rows Updated: {$updated}");
            } else {
                Log::warning("Product not found in {$tableName} for SKU: {$item->sku}");
            }
        }

        return true;
    }

    /**
     * Map user location to warehouse code
     * 
     * @param string $location
     * @return string
     */
    private function getWarehouseCodeByLocation(string $location): string
    {
        // Warehouse mapping
        $locationToWarehouse = [
            '4002' => '80181',
            '2010' => '80181', //bacolod
            '2017' => '80181', //bacolod
            '2019' => '80181', //bacolod
            '3018' => '80181', //bacolod
            '3019' => '80181', //bacolod
            '2008' => '80181', // Bacolod
            '6009' => '80181', // Bacolod
            '6010' => '80181', // Bacolod
            '6012' => '80141', // Silangan
            'lz'    => '80141', // LZ
            'vs'    => '80181', // Silangan

        ];

        $warehouseCode = $locationToWarehouse[$location] ?? null;

        if (!$warehouseCode) {
            throw new \Exception("Warehouse code not found for location: {$location}");
        }

        return $warehouseCode;
    }



    // public function managementOrders(Request $request){
    //     $allowedStatuses = ['for approval', 'approved', 'rejected'];

    //     $query = Order::query()
    //     ->with('items')
    //     ->whereIn('order_status', $allowedStatuses); // ✅ limit statuses;

    //     // Search
    //     if ($search = $request->input('search')) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('customer_name', 'like', "%{$search}%")
    //             ->orWhere('sof_id', 'like', "%{$search}%");
    //         });
    //     }

    //     // Channel filter
    //     if ($channel = $request->input('channel')) {
    //         $query->where('channel_order', $channel);
    //     }


    //     // Status filter
    //     if ($status = $request->input('status')) {
    //         if (in_array($status, $allowedStatuses)) {
    //             $query->where('order_status', $status);
    //         }
    //     }

    //     // Date range filter
    //     if ($startDate = $request->input('start_date')) {
    //         $query->whereDate('time_order', '>=', $startDate);
    //     }
    //     if ($endDate = $request->input('end_date')) {
    //         $query->whereDate('time_order', '<=', $endDate);
    //     }

    //     // ✅ Rows per page (default 10)
    //     $perPage = $request->input('per_page', 10);

    //     $orders = $query->orderByDesc('created_at')
    //         ->paginate($perPage)
    //         ->withQueryString();

    //     $channels = Order::select('channel_order')->distinct()->pluck('channel_order');
    //     $statuses = $allowedStatuses;

    //     return view('orders.management_orders', compact('orders', 'channels', 'statuses', 'perPage'));

    // }

    public function printSOF($id)
    {
        $order = Order::with('items')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.pdf_sof', compact('order'))
            ->setPaper('A4', 'landscape'); // switched to landscape

        return $pdf->stream("{$order->id}.pdf"); // Opens in browser
        // return $pdf->download("SOF-Order-{$order->id}.pdf"); // Downloads directly
    }
    public function printSOFInvoice($id)
    {
        $order = Order::with('items')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.pdf_sof_invoice', compact('order'))
            ->setPaper('A4', 'portrait'); // switched to landscape

        return $pdf->stream("{$order->id}.pdf"); // Opens in browser
        // return $pdf->download("SOF-Order-{$order->id}.pdf"); // Downloads directly
    }



    public function generateFreebiesForm($orderId)
    {
        // Fetch order info from orders table
        $orderInfo = DB::table('orders')
            ->where('id', $orderId)
            ->first();

        // Get all items for the order, ordered by ID
        $items = DB::table('order_items')
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();

        $rows = [];
        $lastMain = null;

        foreach ($items as $item) {
            if ($item->item_type === 'MAIN') {
                // Remember the last MAIN item
                $lastMain = $item;
            } elseif ($item->item_type === 'FREEBIE' && $lastMain) {
                // Pair the FREEBIE with the MAIN item before it
                $totalMainQty = $lastMain->qty_per_pc * $lastMain->qty_per_cs;
                $totalFreebieQty = $item->qty_per_pc * $item->freebies_per_cs;

                $rows[] = [
                    'main_sku' => $lastMain->sku,
                    'main_description' => $lastMain->item_description,
                    'main_qty' => $lastMain->qty_per_cs,
                    'total_main_qty' => $totalMainQty,
                    'main_scheme' => $lastMain->scheme,
                    'freebie_sku' => $item->sku,
                    'freebie_description' => $item->item_description,
                    'freebie_qty' => $item->freebies_per_cs,
                    'total_freebie_qty' => $totalFreebieQty,
                ];
            }
        }

        $pdf = Pdf::loadView('pdf.freebies_form', [
            'rows' => $rows,
            'customer_name' => $orderInfo->customer_name ?? '',
            'date' => optional($orderInfo->time_order)->format('F j, Y') ?? now()->format('F j, Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('freebies_form.pdf');
    }

    public function generateOrderSlip($orderId)
    {
        // Get order info
        $orderInfo = DB::table('orders')->where('id', $orderId)->first();

        // Get all items for the order
        $items = DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('item_type', '!=', 'FREEBIE')
            ->orderBy('id')
            ->get();


        $schemes = $items->pluck('scheme')->filter()->unique()->values();

        $schemeDisplay = '';
        if ($schemes->count() === 1) {
            // Only one scheme
            $schemeDisplay = $schemes->first();
        } elseif ($schemes->count() > 1) {
            // Multiple schemes → label them as Scheme 1, Scheme 2, ...
            $schemeDisplay = $schemes->map(function ($s, $i) {
                return 'Scheme ' . ($i + 1) . ': ' . $s;
            })->implode(', ');
        }

        // Build rows for table
        $rows = [];
        foreach ($items as $item) {
            $totalQty = $item->qty_per_pc * $item->qty_per_cs;

            $rows[] = [
                'no_of_case'      => $item->qty_per_cs,
                'item_description' => $item->item_description,
                'remarks'         => $item->remarks,
                'qty_per_case'    => $item->qty_per_pc,
                'total_qty'       => $totalQty,
                'punch'           => '???', // placeholder if needed
                'sku'             => $item->sku,
                'price_per_piece' => $item->price_per_pc,
                'total_amount'    => $item->amount,
                'trans_no'        => $item->store_order_no,
                'terminal'        => '???', // you can adjust if stored
            ];
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.order_slip', [
            'rows'           => $rows,
            'customer_name'  => $orderInfo->customer_name ?? '',
            'date'           => optional($orderInfo->time_order)->format('F j, Y') ?? now()->format('F j, Y'),
            'address'        => $orderInfo->address ?? '',
            'telephone'      => $orderInfo->contact_number ?? '',
            'email'          => $orderInfo->email ?? '',
            'payment_mode'   => $orderInfo->mode_payment ?? '',
            'scheme'         => $schemeDisplay ?? '',
            'cashier'        => $orderInfo->cashier ?? '',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('order_slip.pdf');
    }

    public function cancelItems(Request $request)
    {
        // Log incoming payload for debugging
        Log::info('Order cancel items request payload', $request->all());

        // Use manual validator so we can log validation errors and return a friendly response
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'required|exists:order_items,id'
        ]);

        if ($validator->fails()) {
            Log::warning('Order cancel items validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            // Find the order
            $order = Order::findOrFail($validated['order_id']);

            // Track changes for notes
            $changes = [];
            $cancelledItems = [];

            // Get the items to be cancelled
            $itemsToCancel = OrderItem::whereIn('id', $validated['item_ids'])
                ->where('order_id', $order->id)
                ->get();

            if ($itemsToCancel->isEmpty()) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'No valid items found to cancel.'
                ], 404);
            }

            // === CANCEL ITEMS ===
            foreach ($itemsToCancel as $item) {
                $oldRemarks = $item->remarks;
                $oldAmount = $item->amount;

                // Update item remarks to "Item Cancelled"
                $item->update([
                    'remarks' => 'Item Cancelled',
                    'amount' => 0, // Optional: Set amount to 0 for cancelled items
                ]);

                // Track the change
                $changes[] = "Item {$item->sku} ({$item->item_description}) - Remarks changed from '{$oldRemarks}' to 'Item Cancelled'";

                if ($oldAmount != 0) {
                    $changes[] = "Item {$item->sku} - Amount changed from '{$oldAmount}' to '0'";
                }

                $cancelledItems[] = [
                    'id' => $item->id,
                    'sku' => $item->sku,
                    'description' => $item->item_description
                ];

                Log::info('Item cancelled', [
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'sku' => $item->sku,
                    'old_remarks' => $oldRemarks,
                    'user_id' => auth()->id()
                ]);
            }

            // === RECALCULATE ORDER TOTAL ===
            $order->refresh();
            $newTotal = $order->items()
                ->where('remarks', '!=', 'Item Cancelled')
                ->sum('amount');

            $oldTotal = $order->total_amount ?? 0;

            if ($oldTotal != $newTotal) {
                $order->update(['total_amount' => $newTotal]);
                $changes[] = "Order total amount changed from '{$oldTotal}' to '{$newTotal}'";
            }

            // === SAVE NOTES IF THERE ARE CHANGES ===
            if (!empty($changes)) {
                $order->notes()->create([
                    'user_id' => auth()->id(),
                    'status'  => "updated", // use current order status
                    'note'    => "Items Cancelled:\n• " . implode("\n• ", $changes), // bulleted list
                ]);
            }

            DB::commit();

            Log::info('Items cancelled successfully', [
                'order_id' => $order->id,
                'cancelled_count' => count($cancelledItems),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => count($cancelledItems) . ' item(s) cancelled successfully.',
                'cancelled_items' => $cancelledItems,
                'new_total' => $newTotal
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Order cancel items exception: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling items. Please check the application logs.'
            ], 500);
        }
    }
}
