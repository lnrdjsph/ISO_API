<?php

/**
 * Centralized Location & Warehouse Configuration
 *
 * Single source of truth for all store codes, warehouse codes,
 * and their mappings used across the application.
 *
 * Usage (in PHP):
 *   use App\Support\LocationConfig;
 *
 *   LocationConfig::stores()               // all store code → display name
 *   LocationConfig::storeName('4002')      // 'F2 - Metro Wholesalemart Colon'
 *   LocationConfig::storeCode('Metro Maasin') // '2010'  (WooCommerce reverse-lookup)
 *   LocationConfig::warehouses()           // all warehouse code → display name
 *   LocationConfig::warehouseName('80181') // 'Bacolod Depot'
 *   LocationConfig::warehouseForStore('4002')  // '80181'
 *   LocationConfig::storesForWarehouse('80181') // ['4002', ...]
 *   LocationConfig::regionStores('vs')     // ['4002', '2010', ...]
 *   LocationConfig::facilityForWarehouse('80181') // 'BD'
 *
 * Usage (in Blade via config() helper):
 *   config('locations.stores')
 *   config('locations.warehouses')
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Store Locations
    |--------------------------------------------------------------------------
    | store_code => Display label shown in the UI.
    | The "short" name (without prefix) is available via LocationConfig::shortStoreName().
    */
    'stores' => [
        '2002' => 'Metro Mandaue',
        '4002' => 'Metro Wholesalemart Colon',
        '5011' => 'Super Metro Bogo Pop-up Store',
        '2001' => 'Metro Colon',

        '2010' => 'Metro Maasin',
        '2017' => 'Metro Tacloban',
        '2019' => 'Metro Bay-Bay',

        // NOT YET STARTED
        // '2020' => 'S20 - Metro Store Catbalogan',
        // '6006' => 'H6 - Super Metro Calbayog',
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Short Names  (used by WooCommerce/Atom integration)
    |--------------------------------------------------------------------------
    | Short name (no prefix code) => store_code.
    | Reverse of the display-label map above, stripped of the "F2 - " prefix.
    */
    'store_name_to_code' => [
        'Metro Wholesalemart Colon'     => '4002',
        'Metro Maasin'                  => '2010',
        'Metro Tacloban'                => '2017',
        'Metro Bay-Bay'                 => '2019',
        'Metro Alang-Alang'             => '3018',
        'Metro Hilongos'                => '3019',
        'Metro Toledo'                  => '2008',
        'Super Metro Antipolo'          => '6012',
        'Super Metro Carcar'            => '6009',
        'Super Metro Bogo'              => '6010',
        'Metro Mandaue'                 => '2002',
        'Metro Colon'                   => '2001',
        'Super Metro Bogo Pop-up Store' => '5011',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouses
    |--------------------------------------------------------------------------
    | warehouse_code => Display name shown in the UI.
    | Comment-out rather than delete codes that are temporarily inactive.
    */
    'warehouses' => [

        // =========================
        // ACTIVE WAREHOUSES
        // =========================
        '80151' => 'Opao Fulfillment Warehouse',
        '80191' => 'Tacloban Depot',

        // =========================
        // OLD / DISABLED WAREHOUSES
        // =========================
        // '80181' => 'Bacolod Depot',
        // '80141' => 'Silangan Warehouse',

        // '80051' => 'Opao-ISO Warehouse',
        // '80071' => 'Big Blue Warehouse',
        // '80131' => 'Lower Tingub Warehouse',
        // '80211' => 'Sta. Rosa Warehouse',
    ],

    /*
    |--------------------------------------------------------------------------
    | Store → Warehouse Mapping
    |--------------------------------------------------------------------------
    | Which warehouse serves each store code (and region alias).
    */
    'store_to_warehouse' => [

        // =========================
        // OPERATIONAL (ACTIVE)
        // =========================

        // Opao Fulfillment
        '2002' => '80151',
        '4002' => '80151',
        '5011' => '80151',
        '2001' => '80151',

        // Tacloban cluster
        '2010' => '80191',
        '2017' => '80191',
        '2019' => '80191',

        // NOT YET STARTED
        // '2020' => '80191',
        // '6006' => '80191',


        // =========================
        // REGION ALIASES
        // =========================
        'vs' => '80151',
        'lz' => '80191',

        // =========================
        // OLD BACOLOD CLUSTER (DISABLED)
        // =========================
        // '4002' => '80181',
        // '2010' => '80181',
        // '2017' => '80181',
        // '2019' => '80181',
        // '3018' => '80181',
        // '3019' => '80181',
        // '2008' => '80181',
        // '6009' => '80181',
        // '6010' => '80181',

        // =========================
        // OLD SILANGAN CLUSTER (DISABLED)
        // =========================
        // '6012' => '80141',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouse → Stores Mapping  (inverse of store_to_warehouse)
    |--------------------------------------------------------------------------
    | All store codes served by each warehouse.
    | Used by allocation jobs and WMS sync commands.
    */
    'warehouse_to_stores' => [

        // =========================
        // OPERATIONAL
        // =========================
        '80151' => ['2002', '4002', '5011', '2001'],

        '80191' => [
            '2010',
            '2017',
            '2019',

            // NOT YET STARTED
            // '2020',
            // '6006',
        ],

        // =========================
        // OLD BACOLOD (DISABLED)
        // =========================
        // '80181' => [
        //     '4002','2010','2017','2019','3018','3019','2008','6009','6010'
        // ],

        // =========================
        // OLD SILANGAN (DISABLED)
        // =========================
        // '80141' => ['6012'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouse → Oracle WMS Facility ID
    |--------------------------------------------------------------------------
    | Used when dispatching FetchAllocationJob / UpdateAllProductAllocations.
    */
    'warehouse_to_facility' => [

        // ACTIVE
        '80151' => 'LT',
        '80191' => 'TD',

        // OLD
        // '80181' => 'BD',
        // '80141' => 'SI',
    ],

    /*
    |--------------------------------------------------------------------------
    | Region / Manager Groups
    |--------------------------------------------------------------------------
    | Maps a manager's user_location alias to the store codes they oversee.
    */
    'regions' => [
        'vs' => ['4002', '2010', '2017', '2019', '3018', '3019', '2008', '6009', '6010'],
        'lz' => ['6012'],
    ],

];
