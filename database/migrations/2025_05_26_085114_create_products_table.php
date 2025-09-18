<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected array $storeCodes = [
        'f2',
        's10',
        's17',
        's19',
        'f18',
        'f19',
        's8',
        'h8',
        'h9',
        'h10',
    ];

    public function up()
    {
        foreach ($this->storeCodes as $code) {
            Schema::connection('mysql')->create("products_{$code}", function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('sku')->unique();
                $table->string('description');
                $table->string('department')->nullable();
                

                // New columns
                $table->string('case_pack')->nullable(); // Number of items per case
                $table->decimal('srp', 10, 2); // Suggested Retail Price
                $table->integer('wms_allocation_per_case')->nullable();
                $table->integer('allocation_per_case')->nullable();
                $table->string('cash_bank_card_scheme', 10)->nullable();
                $table->string('po15_scheme', 10)->nullable();
                $table->string('discount_scheme', 10)->nullable();
                $table->string('freebie_sku')->nullable();

                // Archiving
                $table->timestamp('archived_at')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->string('archive_reason')->nullable();
                $table->index('archived_at');

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        foreach ($this->storeCodes as $code) {
            Schema::connection('mysql')->dropIfExists("products_{$code}");
        }
    }
};
