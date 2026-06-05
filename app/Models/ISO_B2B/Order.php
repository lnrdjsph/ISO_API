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
        'order_status',
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

    /**
     * Resolve the approver User for this order based on its requesting store's region.
     * The approver is configured per-region in Settings → Regions, stored as a sentinel
     * row in settings_region_emails (email='__approver__', label=user_id).
     */
    public function approver(): ?User
    {
        $storeCode = strtolower($this->requesting_store);

        foreach (LocationConfig::regions() as $regionKey => $storeCodes) {
            if (!in_array($storeCode, array_map('strtolower', $storeCodes), true)) {
                continue;
            }

            $userId = LocationConfig::regionApproverUserId($regionKey);
            if (!$userId) return null;

            return User::find($userId);
        }

        return null;
    }

    public function getApproverNameAttribute(): ?string
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
