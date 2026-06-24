<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile-POS receiving store.
 *
 * Every Visayas order is transacted/received through store 4002 and every
 * Luzon order through 6012, regardless of which store requested it. We store
 * the assigned mobile-POS store per region (configurable in Settings) and
 * snapshot it onto each order so transfers + allocation key off it instead of
 * requesting_store. requesting_store is untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Per-region mobile-POS store (configurable in Settings).
        Schema::connection('mysql')->table('settings_regions', function (Blueprint $table) {
            $table->string('mobile_pos_store', 20)->nullable()->after('label');
        });

        // Seed: default every region to the Visayas POS (4002); Luzon overrides to 6012.
        DB::table('settings_regions')->update(['mobile_pos_store' => '4002']);
        DB::table('settings_regions')->where('region_key', 'lz')->update(['mobile_pos_store' => '6012']);

        // 2. Snapshot on the order.
        Schema::connection('mysql')->table('orders', function (Blueprint $table) {
            $table->string('mobile_pos_store', 20)->nullable()->after('requesting_store');
        });

        // Backfill existing orders from their requesting store's region POS.
        // COLLATE bridges the orders (general_ci) vs settings (unicode_ci) mismatch.
        DB::statement("
            UPDATE orders o
            JOIN settings_stores s  ON s.store_code = o.requesting_store COLLATE utf8mb4_unicode_ci
            JOIN settings_regions r ON r.region_key = s.region_key
            SET o.mobile_pos_store = r.mobile_pos_store
            WHERE o.mobile_pos_store IS NULL
        ");
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('orders', function (Blueprint $table) {
            $table->dropColumn('mobile_pos_store');
        });
        Schema::connection('mysql')->table('settings_regions', function (Blueprint $table) {
            $table->dropColumn('mobile_pos_store');
        });
    }
};
