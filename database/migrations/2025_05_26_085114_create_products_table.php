<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected array $storeCodes = [
        '4002',
        '2010',
        '2017',
        '2019',
        '3018',
        '3019',
        '2008',
        '6012',
        '6009',
        '6010',
    ];

    public function up()
    {
        foreach ($this->storeCodes as $code) {
            Schema::connection('mysql')->create("products_{$code}", function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('sku')->unique();
                $table->string('description');
                $table->string('department_code')->nullable();
                $table->string('department')->nullable();
                

                // New columns
                $table->string('case_pack')->nullable(); // Number of items per case
                $table->decimal('srp', 10, 2); // Suggested Retail Price
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
