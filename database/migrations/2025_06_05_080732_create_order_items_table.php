<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::connection('oracle_local')->create('order_items', function (Blueprint $table) {
            $table->increments('id'); // Use increments for Oracle
            $table->unsignedInteger('order_id'); // Foreign key, unsigned int
            
            // your other columns here, example:
            $table->string('sku')->nullable();
            $table->string('item_description')->nullable();
            $table->decimal('price_per_pc', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('order_per_cs')->nullable();
            $table->integer('total_qty')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('store_order_no')->nullable();

            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}

