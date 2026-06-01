<?php

namespace App\Models\ISO_B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use App\Http\Controllers\ProductController;
use App\Models\User;
use App\Support\LocationConfig;

class Order extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'orders';

    protected $fillable = [
        'sof_id',
        'channel_order',
        'warehouse',
        'time_order',
        'payment_center',
        'mode_payment',
        'payment_date',
        'mode_dispatching',
        'delivery_date',
        'address',
        'landmark',
        'comment',
        'requesting_store',
        'requested_by',
        'mbc_card_no',
        'customer_name',
        'contact_number',
        'email',
        'order_status', // 👈 make sure this is included
        'approval_document',
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

    private const REGION_APPROVER = [
        'lz'  => ['role' => 'store manager', 'location' => 'lz'],
        'stc' => ['role' => 'store manager', 'location' => 'stc'],
        'ntc' => ['role' => 'store manager', 'location' => 'ntc'],
        'vs'  => ['role' => 'store manager', 'location' => 'vs'],
    ];

    public function approver(): ?User
    {
        $storeCode = strtolower($this->requesting_store);

        foreach (LocationConfig::regions() as $regionKey => $storeCodes) {
            if (!in_array($storeCode, array_map('strtolower', $storeCodes), true)) {
                continue;
            }

            $criteria = self::REGION_APPROVER[$regionKey] ?? null;
            if (!$criteria) return null;

            return User::where('role', $criteria['role'])
                ->where('user_location', $criteria['location'])
                ->first();
        }

        return null;
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
