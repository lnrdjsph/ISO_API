<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('mysql')->create('PRODUCTS', function (Blueprint $table) {
            $table->bigIncrements('ID');
            $table->string('SKU')->unique();
            $table->string('NAME');
            $table->timestamps(); // optional
        });
    }

    public function down()
    {
        Schema::connection('oracle_local')->dropIfExists('PRODUCTS');
    }
};
