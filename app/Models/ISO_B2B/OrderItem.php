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
        'scheme',
        'price_per_pc',
        'price',
        'qty_per_pc',
        'qty_per_cs',
        'freebies_per_cs',
        'total_qty',
        'discount',
        'amount',
        'remarks',
        'store_order_no',
        'item_type',
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
