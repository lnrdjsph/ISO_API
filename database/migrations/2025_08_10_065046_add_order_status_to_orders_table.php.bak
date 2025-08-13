<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderStatusToOrdersTable extends Migration
{
    public function up()
    {
        Schema::connection('mysql')->table('orders', function (Blueprint $table) {
            $table->string('order_status')->default('pending')->after('landmark');
            // Change default value and position as needed
        });
    }

    public function down()
    {
        Schema::connection('mysql')->table('orders', function (Blueprint $table) {
            $table->dropColumn('order_status');
        });
    }
}
