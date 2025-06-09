<?php

namespace App\Models\ISO_B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $connection = 'oracle_local';  // specify connection

    protected $table = 'orders';

    protected $fillable = [
        'channel_order',
        'time_order',
        'payment_center',
        'mode_payment',
        'payment_date',
        'mode_dispatching',
        'delivery_date',
        'address',
        'landmark',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
