<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Support\LocationConfig;

/**
 * Sets up the virtual "All Visayas" super-region and points the cross-region
 * approver at it.
 *
 * The store->region model is one-to-one (settings_stores.region_key), so we do
 * NOT reassign any store. Instead LocationConfig::regionStores('allvis') returns
 * the union of every region's stores (see LocationConfig::ALL_REGIONS_KEY), and
 * the approver's user_location is set to that key — giving them visibility and
 * approval across all regions while real regions / per-region approver routing
 * stay exactly as they are.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Ensure the virtual region exists (for display + user_location validity).
        DB::table('settings_regions')->updateOrInsert(
            ['region_key' => LocationConfig::ALL_REGIONS_KEY],
            ['label' => 'All Visayas', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
        );

        // 2. Point the configured region approver (resolved from the existing
        //    'vs' __approver__ sentinel — Jenelyn on production) at the virtual
        //    region so they see/approve every region's orders.
        $approverId = DB::table('settings_region_emails')
            ->where('email', '__approver__')
            ->where('region_key', 'vs')
            ->value('label');

        if ($approverId) {
            DB::table('users')
                ->where('id', (int) $approverId)
                ->update(['user_location' => LocationConfig::ALL_REGIONS_KEY]);
        }

        // 3. Bust the LocationConfig cache so the new region is picked up at once.
        Cache::forget(LocationConfig::CACHE_KEY);
    }

    public function down(): void
    {
        // Best-effort revert: send the approver back to their 'vs' region and
        // drop the virtual region row.
        $approverId = DB::table('settings_region_emails')
            ->where('email', '__approver__')
            ->where('region_key', 'vs')
            ->value('label');

        if ($approverId) {
            DB::table('users')
                ->where('id', (int) $approverId)
                ->where('user_location', LocationConfig::ALL_REGIONS_KEY)
                ->update(['user_location' => 'vs']);
        }

        DB::table('settings_regions')
            ->where('region_key', LocationConfig::ALL_REGIONS_KEY)
            ->delete();

        Cache::forget(LocationConfig::CACHE_KEY);
    }
};
