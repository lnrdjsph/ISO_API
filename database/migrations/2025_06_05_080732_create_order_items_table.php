<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql')->create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');

            $table->string('sku')->nullable();
            $table->string('item_description')->nullable();
            $table->string('scheme')->nullable(); // ✅ Added
            $table->decimal('price_per_pc', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('qty_per_pc')->default(0);
            $table->integer('qty_per_cs')->default(0);
            $table->integer('freebies_per_cs')->default(0); // ❗ Change to integer for consistency
            $table->integer('total_qty')->default(0);
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('store_order_no')->nullable();

            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
