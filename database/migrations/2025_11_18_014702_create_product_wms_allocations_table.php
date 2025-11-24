<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected array $warehouses = [
        '80141', // Silangan Warehouse
        '80001', // Central Warehouse
        '80041', // Procter Warehouse
        '80051', // Opao-ISO Warehouse
        '80071', // Big Blue Warehouse
        '80131', // Lower Tingub Warehouse
        '80211', // Sta. Rosa Warehouse
        '80181', // Bacolod Depot
        '80191', // Tacloban Depot
    ];

    public function up()
    {
        Schema::connection('mysql')->create('product_wms_allocations', function (Blueprint $table) {
            $table->string('sku');
            $table->string('warehouse_code', 10);
            
            $table->integer('wms_actual_allocation')->nullable();
            $table->integer('wms_virtual_allocation')->nullable();
            
            $table->timestamps();
            
            $table->primary(['sku', 'warehouse_code']);
            $table->index('warehouse_code');
        });
    }

    public function down()
    {
        Schema::connection('mysql')->dropIfExists('product_wms_allocations');
    }
};