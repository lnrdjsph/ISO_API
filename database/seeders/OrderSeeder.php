<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ISO_B2B\Order;
use App\Models\ISO_B2B\OrderItem;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $order = Order::create([
            'channel_order' => 'E-Commerce',
            'time_order' => '14:30:00',
            'payment_center' => 'Main Branch',
            'mode_payment' => 'Credit Card',
            'payment_date' => '2025-02-17',
            'mode_dispatching' => 'Delivery',
            'delivery_date' => '2025-02-20',
            'address' => '123 Example Street, City',
            'landmark' => 'Near Central Park',
        ]);

        $order->items()->createMany([
            [
                'sku' => 'SKU001',
                'item_description' => 'Product 1',
                'price_per_pc' => 10.50,
                'price' => 10.50,
                'order_per_cs' => '5',
                'total_qty' => 5,
                'amount' => 52.50,
                'remarks' => 'Urgent',
                'store_order_no' => 'SO1234',
            ],
            [
                'sku' => 'SKU002',
                'item_description' => 'Product 2',
                'price_per_pc' => 20.00,
                'price' => 20.00,
                'order_per_cs' => '2',
                'total_qty' => 2,
                'amount' => 40.00,
                'remarks' => '',
                'store_order_no' => 'SO1235',
            ],
        ]);
    }
}
