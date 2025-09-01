<?php

namespace App\Models\ISO_B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\ProductController;

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
        'delivery_date',
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
