<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(ISO_B2B\Order::class);
    }

    public function user()
    {
        return $this->belongsTo(ISO_B2B\Order::class);
    }
}
