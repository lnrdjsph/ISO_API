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

        // Central Cebu
        '2093' => 'Metro Ayala (Rebuild)',
        '3010' => 'Metro Banilad',
        '3014' => 'Metro Supermarket Canduman',
        '6005' => 'Super Metro Basak',

        // North Cebu
        '2002' => 'Metro Mandaue',
        '2015' => 'Metro Store Danao',
        '3009' => 'Metro Carmen',
        '5011' => 'Super Metro Bogo Pop-up Store',
        '6003' => 'Super Metro Lapu Lapu',

        // South Cebu
        '2001' => 'Metro Colon',
        '2008' => 'Metro Toledo',
        '2011' => 'Metro Naga',
        '4002' => 'Metro Wholesalemart Colon',
        '6004' => 'Super Metro Colon',
        '6009' => 'Super Metro Carcar',

        // Non-Cebu (Visayas)
        '2010' => 'Metro Maasin',
        '2017' => 'Metro Store Tacloban',
        '2019' => 'Metro Store Baybay',
        '2023' => 'Metro Store Bais',
        '2025' => 'Metro Hinigaran',
        '3017' => 'Metro Sum-ag',
        '3018' => 'Metro Alangalang',
        '3019' => 'Metro Hilongos',
        '6001' => 'Super Metro Negros',

        // Non-Cebu (not yet started)
        '2020' => 'Metro Store Catbalogan',
        '6006' => 'Super Metro Calbayog',
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

        // Central Cebu
        'Metro Ayala (Rebuild)'      => '2093',
        'Metro Banilad'              => '3010',
        'Metro Supermarket Canduman' => '3014',
        'Super Metro Basak'          => '6005',

        // North Cebu
        'Metro Mandaue'                 => '2002',
        'Metro Store Danao'             => '2015',
        'Metro Carmen'                  => '3009',
        'Super Metro Bogo Pop-up Store' => '5011',
        'Super Metro Lapu Lapu'         => '6003',

        // South Cebu
        'Metro Colon'               => '2001',
        'Metro Toledo'              => '2008',
        'Metro Naga'                => '2011',
        'Metro Wholesalemart Colon' => '4002',
        'Super Metro Colon'         => '6004',
        'Super Metro Carcar'        => '6009',

        // Non-Cebu
        'Metro Maasin'           => '2010',
        'Metro Store Tacloban'   => '2017',
        'Metro Store Baybay'     => '2019',
        'Metro Store Bais'       => '2023',
        'Metro Hinigaran'        => '2025',
        'Metro Sum-ag'           => '3017',
        'Metro Alangalang'       => '3018',
        'Metro Hilongos'         => '3019',
        'Super Metro Negros'     => '6001',
        'Metro Store Catbalogan' => '2020',
        'Super Metro Calbayog'   => '6006',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouses
    |--------------------------------------------------------------------------
    */
    'warehouses' => [
        // '80141' => 'Silangan Fulfillment Warehouse',  // Serves Luzon stores
        '80151' => 'Opao Fulfillment Warehouse',      // Serves all Cebu stores
        '80191' => 'Tacloban Depot',                  // Serves Non-Cebu (Visayas) stores
    ],

    /*
    |--------------------------------------------------------------------------
    | Store → Warehouse Mapping
    |--------------------------------------------------------------------------
    */
    'store_to_warehouse' => [
        // Luzon → Silangan
        // '6012' => '80141',

        // Central Cebu → Opao
        '2093' => '80151',
        '3010' => '80151',
        '3014' => '80151',
        '6005' => '80151',

        // North Cebu → Opao
        '2002' => '80151',
        '2015' => '80151',
        '3009' => '80151',
        '5011' => '80151',
        '6003' => '80151',

        // South Cebu → Opao
        '2001' => '80151',
        '2008' => '80151',
        '2011' => '80151',
        '4002' => '80151',
        '6004' => '80151',
        '6009' => '80151',

        // Non-Cebu → Tacloban Depot
        '2010' => '80191',
        '2017' => '80191',
        '2019' => '80191',
        '2020' => '80191',
        '2023' => '80191',
        '2025' => '80191',
        '3017' => '80191',
        '3018' => '80191',
        '3019' => '80191',
        '6001' => '80191',
        '6006' => '80191',
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
            // Central Cebu
            '2093', '3010', '3014', '6005',
            // North Cebu
            '2002', '2015', '3009', '5011', '6003',
            // South Cebu
            '2001', '2008', '2011', '4002', '6004', '6009',
        ],
        '80191' => [
            '2010', '2017', '2019', '2020', '2023', '2025',
            '3017', '3018', '3019', '6001', '6006',
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
        'ctc' => ['2093', '3010', '3014', '6005'],
        'ntc'   => ['2002', '2015', '3009', '5011', '6003'],
        'stc'   => ['2001', '2008', '2011', '4002', '6004', '6009'],
        'vs'     => [
            '2010', '2017', '2019', '2020', '2023', '2025',
            '3017', '3018', '3019', '6001', '6006',
        ],
    ],

];