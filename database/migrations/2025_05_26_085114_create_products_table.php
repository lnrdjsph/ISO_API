<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql')->create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sku')->unique();
            $table->string('description');

            // New columns
            $table->integer('case_pack'); // Number of items per case
            $table->decimal('srp', 10, 2); // Suggested Retail Price
            $table->integer('allocation_per_case'); // Inventory allocation
            $table->string('cash_bank_card_scheme', 10); // Discount or pricing scheme
            $table->string('po15_scheme', 10); // PO15-related scheme
            $table->string('freebie_sku')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->index('archived_at'); // Add index for better query performance
            $table->timestamps(); // created_at and updated_at
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
