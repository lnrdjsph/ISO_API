<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql')->create('products_f2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sku')->unique();
            $table->string('description');

            // New columns
            $table->string('case_pack'); // Number of items per case
            $table->decimal('srp', 10, 2); // Suggested Retail Price
            $table->integer('wms_allocation_per_case')->nullable(); // Inventory allocation
            $table->integer('allocation_per_case')->nullable(); // Inventory allocation
            $table->string('cash_bank_card_scheme', 10)->nullable(); // Discount or pricing scheme
            $table->string('po15_scheme', 10)->nullable(); // PO15-related scheme
            $table->string('discount_scheme', 10)->nullable(); // Discount scheme
            $table->string('freebie_sku')->nullable();

            // Archiving
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->string('archive_reason')->nullable();
            $table->index('archived_at'); // Better query performance

            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down()
    {
        Schema::connection('mysql')->dropIfExists('products_f2');
    }
};
