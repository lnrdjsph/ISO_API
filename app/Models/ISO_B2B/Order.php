<?php

namespace App\Models\ISO_B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\ProductController;
use App\Models\User;

class Order extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'orders';

    protected $fillable = [
        'sof_id',
        'channel_order',
        'time_order',
        'payment_center',
        'mode_payment',
        'payment_date',
        'mode_dispatching',
        'delivery_date' => 'date',
        'address',
        'landmark',
        'requesting_store',
        'requested_by',
        'mbc_card_no',
        'customer_name',
        'contact_number',
        'order_status', // 👈 make sure this is included
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function notes()
    {
        return $this->hasMany(OrderNote::class, 'order_id')
                    ->orderBy('created_at', 'desc');
    }

public function approver()
{
    $map = [
        'f2'  => 1,
        's10' => 1,
        's17' => 1,
        's19' => 1,
        'f18' => 1,
        'f19' => 1,
        's8'  => 1,
        'h9'  => 1,
        'h10' => 1,
        'h8'  => 2,
    ];

    $storeCode = strtolower($this->requesting_store);
    $userId = $map[$storeCode] ?? null;

    return $userId ? User::find($userId) : null;
}

public function getApproverNameAttribute()
{
    return $this->approver()?->name;
}


    // protected static function booted()
    // {
    //     static::updated(function ($order) {
    //         // Check if order_status was changed
    //         if ($order->isDirty('order_status')) {
    //             $newStatus = $order->order_status;

    //             if (in_array($newStatus, ['completed', 'processing',])) {
    //                 // Call ProductController method
    //                 app(ProductController::class)->handleCompletedOrProcessing($order);
    //             }
    //         }
    //     });
    // }
}
