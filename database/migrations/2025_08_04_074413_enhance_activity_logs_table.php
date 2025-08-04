<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('properties');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->json('metadata')->nullable()->after('user_agent');
            
            $table->index(['user_id', 'action', 'created_at']);
            $table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'action', 'created_at']);
            $table->dropIndex(['ip_address']);
            $table->dropColumn(['ip_address', 'user_agent', 'metadata']);
        });
    }
}
