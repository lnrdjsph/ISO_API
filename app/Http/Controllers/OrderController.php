<?php

namespace App\Http\Controllers;

use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;

use App\Mail\OrderApprovalRequestMail;
use Illuminate\Support\Facades\Mail;


class OrderController extends Controller
{

public function index(Request $request)
{
    $user = auth()->user();
    $query = Order::query()->with('items');

    // 🗺️ Define region -> stores mapping
    $storeMapping = [
        'lz' => ['h8'], // Luzon = only Antipolo
        'vs' => ['f2', 's10', 's17', 's19', 'f18', 'f19', 's8', 'h9', 'h10'], // Visayas = everything EXCEPT Antipolo
    ];

    // Default statuses list
    $allowedStatuses = null;

    // 👔 Manager role restrictions
    if ($user->role === 'manager') {
        $allowedStatuses = ['for approval', 'approved', 'rejected'];
        $query->whereIn('order_status', $allowedStatuses);

        // 🔒 Restrict managers by region stores
        if ($user->user_location && isset($storeMapping[$user->user_location])) {
            $query->whereIn('requesting_store', $storeMapping[$user->user_location]);
        }
    } else {
        // Non-managers: single store restriction
        if ($user->user_location) {
            $query->where('requesting_store', $user->user_location);
        }
    }

    // 🔎 Search
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

    // ✅ Pagination
    $perPage = $request->input('per_page', 10);

    $orders = $query->orderByDesc('created_at')
        ->paginate($perPage)
        ->withQueryString();

    // Dropdown data
    $channels = Order::select('channel_order')->distinct()->pluck('channel_order');
    $statuses = $allowedStatuses ?? Order::select('order_status')->distinct()->pluck('order_status');

    // All store names
    $allStoreLocations = [
        'f2'  => 'F2 - Metro Wholesalemart Colon',
        's10' => 'S10 - Metro Maasin',
        's17' => 'S17 - Metro Tacloban',
        's19' => 'S19 - Metro Bay-Bay',
        'f18' => 'F18 - Metro Alang-Alang',
        'f19' => 'F19 - Metro Hilongos',
        's8'  => 'S8 - Metro Toledo',
        'h8'  => 'H8 - Super Metro Antipolo',
        'h9'  => 'H9 - Super Metro Carcar',
        'h10' => 'H10 - Super Metro Bogo',
    ];

    // 🎯 Restrict dropdown options
    if ($user->role === 'manager') {
        if ($user->user_location && isset($storeMapping[$user->user_location])) {
            // Manager → only their region’s stores
            $storeLocations = Arr::only($allStoreLocations, $storeMapping[$user->user_location]);
        } else {
            // Manager with no region restriction
            $storeLocations = $allStoreLocations;
        }
    } else {
        if ($user->user_location) {
            // Non-manager → only their store
            $storeLocations = Arr::only($allStoreLocations, [$user->user_location]);
        } else {
            $storeLocations = $allStoreLocations;
        }
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
        $validated = $request->validate([
            // 'id' => 'required|exists:orders,id',
            // Customer Info
            'mbc_card_no' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            
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
            'items.*.total_qty' => 'required|integer|min:0',
            'items.*.discount' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:255',
            'items.*.store_order_no' => 'nullable|string|max:255',
        ]);


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

        // === ORDER ITEMS CHANGES ===
        foreach ($validated['items'] as $itemData) {
            $orderItem = OrderItem::findOrFail($itemData['id']);

            $oldData = $orderItem->toArray();

            // Calculate amount
            $price = $itemData['price'];
            $discount = $itemData['discount'] ?? 0;



                    $price = $price - floatval($discount);


            $calculatedAmount = $price * $itemData['total_qty'];

            $orderItem->update([
                'sku' => $itemData['sku'],
                'item_description' => $itemData['item_description'],
                'scheme' => $itemData['scheme'],
                'price_per_pc' => $itemData['price_per_pc'],
                'price' => $itemData['price'], // keep original price
                'qty_per_pc' => $itemData['qty_per_pc'],
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
        // === SAVE NOTES IF THERE ARE CHANGES ===
// === SAVE NOTES IF THERE ARE CHANGES ===
    if (!empty($changes)) {
        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => $order->order_status, // use current order status
            'note'    => "• " . implode("\n• ", $changes), // bulleted list
        ]);
    }



        DB::commit();

        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Order updated successfully!');

    } catch (\Exception $e) {
        DB::rollback();

        return redirect()->back()
            ->withInput()
            ->withErrors(['error' => 'Failed to update order: ' . $e->getMessage()]);
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
        $this->revertAllocationStock($order->id);

        $order->order_status = 'cancelled';
        $order->save();

        // Log note with reason
        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'cancelled',
            'note'    => 'Order was cancelled. Reason: ' . $request->note,
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


    public function forApproval(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->id);
        $order->order_status = 'for approval';
        $this->deductAllocationStock($order->id);
        $order->save();

        $order->notes()->create([
            'user_id' => auth()->id(),
            'status'  => 'for approval',
            'note'    => 'Order sent for approval',
        ]);

        // 🔔 Determine recipient based on requesting_store
        $recipients = [];

        if (in_array($order->requesting_store, ['f2', 's10', 's17', 's19', 'f18', 'f19', 's8', 'h9', 'h10'])) {
            $recipients[] = 'gene.catarina@metroretail.ph';
        }

        if ($order->requesting_store === 'h8') {
            $recipients[] = 'leonard.tomalon@metroretail.ph';
        }

        // If recipients found, send email
        if (!empty($recipients)) {
            Mail::to($recipients)->send(new OrderApprovalRequestMail($order));
        }

        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order requested for approval successfully and email sent.');
    }


    public function approveOrder(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->id);
        $order->order_status = 'approved';
        $this->deductAllocationStock($order->id);
        $order->save();

        $order->notes()->create([
    'user_id' => auth()->id(),
    'status'  => 'approved',
    'note'    => 'Order approved',
]);


        return redirect()
            ->route('orders.show', $order->id)
            ->with('success', 'Order approved successfully.');
    }

public function rejectOrder(Request $request)
{
    $request->validate([
        'id'   => 'required|exists:orders,id',
        'note' => 'required|string', // require reason
    ]);

    $order = Order::findOrFail($request->id);
    $order->order_status = 'rejected';
    $this->deductAllocationStock($order->id);
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

    public function deductAllocationStock($orderId)
    {
        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $userLocation;

        // Get order with items
        $order = Order::with('items')->findOrFail($orderId);

        foreach ($order->items as $item) {
            // Find product by SKU in location-specific table
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', strtoupper($item->sku))
                ->first();

            if ($product) {
                // Deduct grand total qty directly (no case conversion)
                $newAllocation = max(0, ($product->allocation_per_case ?? 0) - $item->total_qty);

                DB::connection('mysql')
                    ->table($tableName)
                    ->where('id', $product->id)
                    ->update([
                        'allocation_per_case' => $newAllocation,
                        'updated_at' => now(),
                    ]);
            }
        }

        return true;
    }



    public function revertAllocationStock($orderId)
    {
        $userLocation = strtolower(auth()->user()->user_location);
        $tableName = 'products_' . $userLocation;

        // Get order with items
        $order = Order::with('items')->findOrFail($orderId);

        foreach ($order->items as $item) {
            $product = DB::connection('mysql')
                ->table($tableName)
                ->where('sku', strtoupper($item->sku))
                ->first();

            if ($product) {
                // Add back the total_qty that was deducted
                $newAllocation = ($product->allocation_per_case ?? 0) + $item->total_qty;

                DB::connection('mysql')
                    ->table($tableName)
                    ->where('id', $product->id)
                    ->update([
                        'allocation_per_case' => $newAllocation,
                        'updated_at' => now(),
                    ]);
            }
        }

        return true;
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

    
}
