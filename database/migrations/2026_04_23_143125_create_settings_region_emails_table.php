<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings_region_emails', function (Blueprint $table) {
            $table->id();
            $table->string('region_key', 10);
            $table->string('email', 255);
            $table->string('label', 100)->nullable(); // e.g. "Manager - Visayas"
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('region_key')
                ->references('region_key')
                ->on('settings_regions')
                ->onDelete('cascade');

            $table->unique(['region_key', 'email']); // no duplicates per region
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings_region_emails');
    }
};
