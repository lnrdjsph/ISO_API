<?php

namespace App\Models\ISO_B2B;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'sku',
        'item_description',
        'price_per_pc',
        'price',
        'qty_per_pc',        // New
        'qty_per_cs',        // New
        'freebies_per_cs',      // New
        'scheme',               // New
        'total_qty',
        'amount',
        'remarks',
        'store_order_no',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
