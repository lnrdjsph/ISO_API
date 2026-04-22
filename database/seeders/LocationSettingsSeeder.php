<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds settings_* tables.
 * Source of truth: config/locations.php
 *
 * Run once after migration:
 *   php artisan db:seed --class=LocationSettingsSeeder
 *
 * Safe to re-run — uses updateOrInsert.
 */
class LocationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Warehouses ────────────────────────────────────────────────
        // Source: config/locations.php → 'warehouses' + 'warehouse_to_facility'
        $warehouses = [
            ['warehouse_code' => '80151', 'name' => 'Opao Fulfillment Warehouse', 'facility_id' => 'LT'],
            ['warehouse_code' => '80191', 'name' => 'Tacloban Depot',             'facility_id' => 'TD'],
        ];

        foreach ($warehouses as $wh) {
            DB::table('settings_warehouses')->updateOrInsert(
                ['warehouse_code' => $wh['warehouse_code']],
                array_merge($wh, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // ── Regions ───────────────────────────────────────────────────
        // Source: config/locations.php → 'region_labels'
        $regions = [
            ['region_key' => 'lz',  'label' => 'Luzon'],
            ['region_key' => 'ntc', 'label' => 'North Cebu'],
            ['region_key' => 'stc', 'label' => 'South Cebu'],
            ['region_key' => 'vs',  'label' => 'Non-Cebu (Visayas)'],
        ];

        foreach ($regions as $r) {
            DB::table('settings_regions')->updateOrInsert(
                ['region_key' => $r['region_key']],
                array_merge($r, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // ── Stores ────────────────────────────────────────────────────
        // Source: config/locations.php → 'stores' + 'store_to_warehouse' + 'regions'
        //
        // Warehouse mapping (from store_to_warehouse):
        //   2002, 5011, 2001, 4002 → 80151 Opao
        //   2010, 2017, 2019, 2020, 6006 → 80191 Tacloban
        //   6012 → null (Silangan commented out in config)
        $stores = [
            // Luzon
            ['store_code' => '6012', 'display_name' => 'Super Metro Antipolo',         'short_name' => 'Super Metro Antipolo',         'warehouse_code' => null,    'region_key' => 'lz',  'status' => 'active'],

            // North Cebu → Opao (80151)
            ['store_code' => '2002', 'display_name' => 'Metro Mandaue',                'short_name' => 'Metro Mandaue',                'warehouse_code' => '80151', 'region_key' => 'ntc', 'status' => 'active'],
            ['store_code' => '5011', 'display_name' => 'Super Metro Bogo Pop-up Store', 'short_name' => 'Super Metro Bogo Pop-up Store', 'warehouse_code' => '80151', 'region_key' => 'ntc', 'status' => 'active'],

            // South Cebu → Opao (80151)
            ['store_code' => '2001', 'display_name' => 'Metro Colon',                  'short_name' => 'Metro Colon',                  'warehouse_code' => '80151', 'region_key' => 'stc', 'status' => 'active'],
            ['store_code' => '4002', 'display_name' => 'Metro Wholesalemart Colon',    'short_name' => 'Metro Wholesalemart Colon',    'warehouse_code' => '80151', 'region_key' => 'stc', 'status' => 'active'],

            // Non-Cebu Visayas → Tacloban Depot (80191)
            ['store_code' => '2010', 'display_name' => 'Metro Maasin',                 'short_name' => 'Metro Maasin',                 'warehouse_code' => '80191', 'region_key' => 'vs',  'status' => 'active'],
            ['store_code' => '2017', 'display_name' => 'Metro Store Tacloban',         'short_name' => 'Metro Store Tacloban',         'warehouse_code' => '80191', 'region_key' => 'vs',  'status' => 'active'],
            ['store_code' => '2019', 'display_name' => 'Metro Store Baybay',           'short_name' => 'Metro Store Baybay',           'warehouse_code' => '80191', 'region_key' => 'vs',  'status' => 'active'],
            ['store_code' => '2020', 'display_name' => 'Metro Store Catbalogan',       'short_name' => 'Metro Store Catbalogan',       'warehouse_code' => '80191', 'region_key' => 'vs',  'status' => 'pending'],
            ['store_code' => '6006', 'display_name' => 'Super Metro Calbayog',         'short_name' => 'Super Metro Calbayog',         'warehouse_code' => '80191', 'region_key' => 'vs',  'status' => 'pending'],
        ];

        foreach ($stores as $s) {
            DB::table('settings_stores')->updateOrInsert(
                ['store_code' => $s['store_code']],
                array_merge($s, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command->info('✅ Seeded: ' . count($warehouses) . ' warehouses, ' . count($regions) . ' regions, ' . count($stores) . ' stores.');
    }
}
