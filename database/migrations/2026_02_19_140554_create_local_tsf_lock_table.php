<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_tsf_lock', function (Blueprint $table) {
            $table->id(); // single row with id = 1
            $table->unsignedBigInteger('last_tsf')->default(3006000000);
            $table->timestamps();
        });

        // Insert initial row
        DB::table('local_tsf_lock')->insert([
            'id' => 1,
            'last_tsf' => 3006000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('local_tsf_lock');
    }
};
