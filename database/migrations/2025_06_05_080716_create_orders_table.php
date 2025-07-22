<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql')->create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('requesting_store');
            $table->string('requested_by');
            $table->string('mbc_card_no')->nullable(); // ✅ Added
            $table->string('customer_name')->nullable(); // ✅ Added
            $table->string('contact_number')->nullable(); // ✅ Added

            $table->string('channel_order');
            $table->string('time_order'); // Using string for flexibility
            $table->string('payment_center')->nullable();
            $table->string('mode_payment')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('mode_dispatching')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('address')->nullable();
            $table->string('landmark')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
