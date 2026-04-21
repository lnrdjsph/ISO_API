<?php

/**
 * Centralized Location & Warehouse Configuration
 *
 * Single source of truth for all store codes, warehouse codes,
 * and their mappings used across the application.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Store Locations
    |--------------------------------------------------------------------------
    | store_code => Display label shown in the UI.
    */
    'stores' => [
        // Luzon
        '6012' => 'Super Metro Antipolo',

        // MBC 2 PAYMENT CENTER STORES ONLY
        // =================================
        
        // Central Cebu (MBC 2)
        // '2093' => 'Metro Ayala (Rebuild)',  // MBC 1 only
        // '3010' => 'Metro Banilad',          // MBC 1 only
        // '3014' => 'Metro Supermarket Canduman', // MBC 1 only
        // '6005' => 'Super Metro Basak',      // MBC 1 only

        // North Cebu (MBC 2)
        '2002' => 'Metro Mandaue',              // MBC 2 ✓
        // '2015' => 'Metro Store Danao',       // MBC 1 only
        // '3009' => 'Metro Carmen',            // MBC 1 only
        '5011' => 'Super Metro Bogo Pop-up Store', // MBC 2 ✓
        // '6003' => 'Super Metro Lapu Lapu',   // MBC 1 only

        // South Cebu (MBC 2)
        '2001' => 'Metro Colon',                // MBC 2 ✓
        // '2008' => 'Metro Toledo',            // MBC 1 only
        // '2011' => 'Metro Naga',              // MBC 1 only
        '4002' => 'Metro Wholesalemart Colon',  // MBC 2 ✓
        // '6004' => 'Super Metro Colon',       // MBC 1 only
        // '6009' => 'Super Metro Carcar',      // MBC 1 only

        // Non-Cebu (Visayas) - MBC 2
        '2010' => 'Metro Maasin',               // MBC 2 ✓
        '2017' => 'Metro Store Tacloban',       // MBC 2 ✓
        '2019' => 'Metro Store Baybay',         // MBC 2 ✓
        '2020' => 'Metro Store Catbalogan',     // MBC 2 (not yet started)
        // '2023' => 'Metro Store Bais',        // MBC 1 only
        // '2025' => 'Metro Hinigaran',         // MBC 1 only
        // '3017' => 'Metro Sum-ag',            // MBC 1 only
        // '3018' => 'Metro Alangalang',        // MBC 1 only
        // '3019' => 'Metro Hilongos',          // MBC 1 only
        // '6001' => 'Super Metro Negros',      // MBC 1 only
        '6006' => 'Super Metro Calbayog',       // MBC 2 (not yet started)
    ],

    /*
    |--------------------------------------------------------------------------
    | Region Display Labels
    |--------------------------------------------------------------------------
    | Used by the UI to render human-readable region names from region codes.
    */
    'region_labels' => [
        'lz'        => 'Region: Luzon',
        'ctc'       => 'Region: Central Cebu',
        'ntc'       => 'Region: North Cebu',
        'stc'       => 'Region: South Cebu',
        'vs'        => 'Region: Non-Cebu (Visayas)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Short Names (used by WooCommerce/Atom integration)
    |--------------------------------------------------------------------------
    */
    'store_name_to_code' => [
        // Luzon
        'Super Metro Antipolo'       => '6012',

        // MBC 2 PAYMENT CENTER STORES ONLY
        // North Cebu (MBC 2)
        'Metro Mandaue'                 => '2002',
        'Super Metro Bogo Pop-up Store' => '5011',

        // South Cebu (MBC 2)
        'Metro Colon'               => '2001',
        'Metro Wholesalemart Colon' => '4002',

        // Non-Cebu (MBC 2)
        'Metro Maasin'           => '2010',
        'Metro Store Tacloban'   => '2017',
        'Metro Store Baybay'     => '2019',
        'Metro Store Catbalogan' => '2020',
        'Super Metro Calbayog'   => '6006',
        
        // MBC 1 STORES (COMMENTED OUT)
        // 'Metro Ayala (Rebuild)'      => '2093',
        // 'Metro Banilad'              => '3010',
        // 'Metro Supermarket Canduman' => '3014',
        // 'Super Metro Basak'          => '6005',
        // 'Metro Store Danao'             => '2015',
        // 'Metro Carmen'                  => '3009',
        // 'Super Metro Lapu Lapu'         => '6003',
        // 'Metro Toledo'              => '2008',
        // 'Metro Naga'                => '2011',
        // 'Super Metro Colon'         => '6004',
        // 'Super Metro Carcar'        => '6009',
        // 'Metro Store Bais'       => '2023',
        // 'Metro Hinigaran'        => '2025',
        // 'Metro Sum-ag'           => '3017',
        // 'Metro Alangalang'       => '3018',
        // 'Metro Hilongos'         => '3019',
        // 'Super Metro Negros'     => '6001',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouses
    |--------------------------------------------------------------------------
    */
    'warehouses' => [
        // '80141' => 'Silangan Fulfillment Warehouse',  // Serves Luzon stores
        '80151' => 'Opao Fulfillment Warehouse',      // Serves all Cebu stores (MBC 2)
        '80191' => 'Tacloban Depot',                  // Serves Non-Cebu (Visayas) stores (MBC 2)
    ],

    /*
    |--------------------------------------------------------------------------
    | Store → Warehouse Mapping
    |--------------------------------------------------------------------------
    */
    'store_to_warehouse' => [
        // Luzon → Silangan
        // '6012' => '80141',

        // MBC 2 PAYMENT CENTER STORES ONLY
        // North Cebu → Opao (MBC 2)
        '2002' => '80151',
        '5011' => '80151',

        // South Cebu → Opao (MBC 2)
        '2001' => '80151',
        '4002' => '80151',

        // Non-Cebu → Tacloban Depot (MBC 2)
        '2010' => '80191',
        '2017' => '80191',
        '2019' => '80191',
        '2020' => '80191',  // not yet started
        '6006' => '80191',  // not yet started
        
        // MBC 1 STORES (COMMENTED OUT)
        // Central Cebu → Opao (MBC 1)
        // '2093' => '80151',
        // '3010' => '80151',
        // '3014' => '80151',
        // '6005' => '80151',
        
        // North Cebu → Opao (MBC 1)
        // '2015' => '80151',
        // '3009' => '80151',
        // '6003' => '80151',
        
        // South Cebu → Opao (MBC 1)
        // '2008' => '80151',
        // '2011' => '80151',
        // '6004' => '80151',
        // '6009' => '80151',
        
        // Non-Cebu → Tacloban Depot (MBC 1)
        // '2023' => '80191',
        // '2025' => '80191',
        // '3017' => '80191',
        // '3018' => '80191',
        // '3019' => '80191',
        // '6001' => '80191',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouse → Stores Mapping (inverse of store_to_warehouse)
    |--------------------------------------------------------------------------
    */
    'warehouse_to_stores' => [
        '80141' => [
            '6012',
        ],
        '80151' => [
            // MBC 2 PAYMENT CENTER STORES ONLY
            '2002', '5011',  // North Cebu
            '2001', '4002',  // South Cebu
        ],
        '80191' => [
            // MBC 2 PAYMENT CENTER STORES ONLY
            '2010', '2017', '2019', '2020', '6006',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouse → Oracle WMS Facility ID
    |--------------------------------------------------------------------------
    */
    'warehouse_to_facility' => [
        // '80141' => 'SL',  // Silangan
        '80151' => 'LT',  // Opao
        '80191' => 'TD',  // Tacloban Depot
    ],

    /*
    |--------------------------------------------------------------------------
    | Regions
    |--------------------------------------------------------------------------
    | Manager region code => store codes that belong to that region.
    */
    'regions' => [
        'lz'        => ['6012'],
        
        // MBC 2 PAYMENT CENTER REGIONS
        'ntc'   => ['2002', '5011'],           // North Cebu (MBC 2 only)
        'stc'   => ['2001', '4002'],           // South Cebu (MBC 2 only)
        'vs'     => [                          // Visayas (MBC 2 only)
            '2010', '2017', '2019', '2020', '6006',
        ],
        
        // MBC 1 REGIONS (COMMENTED OUT)
        // 'ctc' => ['2093', '3010', '3014', '6005'],  // Central Cebu (MBC 1)
        // 'ntc'   => ['2002', '2015', '3009', '5011', '6003'],  // Full North Cebu
        // 'stc'   => ['2001', '2008', '2011', '4002', '6004', '6009'],  // Full South Cebu
        // 'vs'     => [  // Full Visayas
        //     '2010', '2017', '2019', '2020', '2023', '2025',
        //     '3017', '3018', '3019', '6001', '6006',
        // ],
    ],

];