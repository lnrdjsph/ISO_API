<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql')->create('product_import_presets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('store_code', 20);
            $table->date('target_month');                 // first day of the target month
            $table->enum('status', ['draft', 'applied', 'discarded'])->default('draft');
            $table->json('rows');                          // upsert-ready product rows (staged, not live)
            $table->unsignedInteger('insert_count')->default(0);
            $table->unsignedInteger('update_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable();            // validation messages from the staged CSV
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('store_code');
            $table->index('target_month');
        });
    }

    public function down()
    {
        Schema::connection('mysql')->dropIfExists('product_import_presets');
    }
};
