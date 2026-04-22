<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LocationConfig
 *
 * Reads from settings_* DB tables (cached 24 h).
 * Falls back to inline static data if DB isn't available yet.
 *
 * Public API is identical to the old config('locations.*') version —
 * no callers need to change.
 *
 * Cache is busted by SettingsController after every write.
 */
class LocationConfig
{
    const CACHE_KEY = 'location_settings';
    const CACHE_TTL = 60 * 60 * 24; // 24 hours

    // ── Internal ──────────────────────────────────────────────────────────

    private static function payload(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                return self::buildFromDb();
            } catch (\Throwable $e) {
                // DB not ready yet (first boot / mid-migration)
                return self::buildFromStatic();
            }
        });
    }

    private static function buildFromDb(): array
    {
        $stores     = DB::table('settings_stores')->whereIn('status', ['active', 'pending'])->get();
        $warehouses = DB::table('settings_warehouses')->where('is_active', true)->get();
        $regions    = DB::table('settings_regions')->where('is_active', true)->get();

        $storesMap       = [];
        $storeNameToCode = [];
        $storeToWh       = [];
        $whToStores      = [];
        $whMap           = [];
        $whToFacility    = [];
        $regionsMap      = [];
        $regionLabels    = [];

        foreach ($warehouses as $w) {
            $whMap[$w->warehouse_code]        = $w->name;
            $whToFacility[$w->warehouse_code] = $w->facility_id;
            $whToStores[$w->warehouse_code]   = [];
        }

        foreach ($stores as $s) {
            $storesMap[$s->store_code]       = $s->display_name;
            $storeNameToCode[$s->short_name] = $s->store_code;
            if ($s->warehouse_code) {
                $storeToWh[$s->store_code] = $s->warehouse_code;
                if (isset($whToStores[$s->warehouse_code])) {
                    $whToStores[$s->warehouse_code][] = $s->store_code;
                }
            }
        }

        foreach ($regions as $r) {
            $regionLabels[$r->region_key] = $r->label;
            $regionsMap[$r->region_key]   = [];
        }

        foreach ($stores as $s) {
            if ($s->region_key && isset($regionsMap[$s->region_key])) {
                $regionsMap[$s->region_key][] = $s->store_code;
            }
        }

        return compact(
            'storesMap',
            'storeNameToCode',
            'whMap',
            'storeToWh',
            'whToStores',
            'whToFacility',
            'regionsMap',
            'regionLabels'
        );
    }

    /**
     * Static fallback — mirrors the seeder data exactly.
     */
    private static function buildFromStatic(): array
    {
        $storesMap = [
            '6012' => 'Super Metro Antipolo',
            '4002' => 'Metro Wholesalemart Colon',
            '2008' => 'Metro Toledo',
            '6009' => 'Super Metro Carcar',
            '6010' => 'Super Metro Bogo',
            '2010' => 'Metro Maasin',
            '2017' => 'Metro Store Tacloban',
            '2019' => 'Metro Store Baybay',
            '3018' => 'Metro Alangalang',
            '3019' => 'Metro Hilongos',
        ];

        $storeNameToCode = array_flip($storesMap);

        $whMap = [
            '80141' => 'Silangan Warehouse',
            '80001' => 'Central Warehouse',
            '80041' => 'Procter Warehouse',
            '80051' => 'Opao-ISO Warehouse',
            '80071' => 'Big Blue Warehouse',
            '80131' => 'Lower Tingub Warehouse',
            '80181' => 'Bacolod Depot',
            '80191' => 'Tacloban Depot',
            '80211' => 'Sta. Rosa Warehouse',
        ];

        $whToFacility = [
            '80141' => 'SL',
            '80001' => 'CW',
            '80041' => 'PR',
            '80051' => 'OP',
            '80071' => 'BB',
            '80131' => 'TG',
            '80181' => 'BC',
            '80191' => 'TD',
            '80211' => 'SR',
        ];

        $storeToWh = [
            '6012' => '80141',
            '4002' => '80051',
            '2008' => '80051',
            '6009' => '80051',
            '6010' => '80051',
            '2010' => '80191',
            '2017' => '80191',
            '2019' => '80191',
            '3018' => '80191',
            '3019' => '80191',
        ];

        $whToStores = [
            '80141' => ['6012'],
            '80051' => ['4002', '2008', '6009', '6010'],
            '80191' => ['2010', '2017', '2019', '3018', '3019'],
            '80001' => [],
            '80041' => [],
            '80071' => [],
            '80131' => [],
            '80181' => [],
            '80211' => [],
        ];

        $regionLabels = [
            'lz' => 'Luzon',
            'ntc' => 'North Cebu',
            'stc' => 'South Cebu',
            'vs' => 'Non-Cebu (Visayas)',
        ];

        $regionsMap = [
            'lz'  => ['6012'],
            'stc' => ['4002', '2008', '6009'],
            'ntc' => ['6010'],
            'vs'  => ['2010', '2017', '2019', '3018', '3019'],
        ];

        return compact(
            'storesMap',
            'storeNameToCode',
            'whMap',
            'storeToWh',
            'whToStores',
            'whToFacility',
            'regionsMap',
            'regionLabels'
        );
    }

    // ── Public API ────────────────────────────────────────────────────────

    public static function stores(): array
    {
        return self::payload()['storesMap'];
    }

    public static function storeName(string $code, ?string $default = null): string
    {
        return self::payload()['storesMap'][$code] ?? ($default ?? $code);
    }

    public static function storeCode(string $name, ?string $default = null): ?string
    {
        return self::payload()['storeNameToCode'][$name] ?? $default;
    }

    public static function warehouses(): array
    {
        return self::payload()['whMap'];
    }

    public static function warehouseName(string $code, ?string $default = null): string
    {
        return self::payload()['whMap'][$code] ?? ($default ?? $code);
    }

    public static function warehouseForStore(string $storeCode, ?string $default = null): ?string
    {
        return self::payload()['storeToWh'][$storeCode] ?? $default;
    }

    public static function storesForWarehouse(string $warehouseCode): array
    {
        return self::payload()['whToStores'][$warehouseCode] ?? [];
    }

    public static function facilityForWarehouse(string $warehouseCode, ?string $default = null): string
    {
        return self::payload()['whToFacility'][$warehouseCode] ?? ($default ?? $warehouseCode);
    }

    public static function regionStores(string $region): array
    {
        return self::payload()['regionsMap'][$region] ?? [];
    }

    public static function regions(): array
    {
        return self::payload()['regionsMap'];
    }

    public static function regionLabels(): array
    {
        return self::payload()['regionLabels'];
    }

    public static function resolveWarehouseCode(
        ?string $requestedWarehouse,
        ?string $userLocation,
        bool    $isPersonnel = false
    ): ?string {
        $allowed = array_keys(self::warehouses());

        if (!$isPersonnel && $requestedWarehouse && in_array($requestedWarehouse, $allowed, true)) {
            return $requestedWarehouse;
        }

        return $userLocation ? self::warehouseForStore($userLocation) : null;
    }

    public static function accessibleStores(string $role, ?string $location): array
    {
        $all = self::stores();

        if ($role === 'super admin') return $all;

        if ($role === 'manager' && $location) {
            $codes = self::regionStores($location);
            return $codes ? array_intersect_key($all, array_flip($codes)) : $all;
        }

        if ($location && isset($all[$location])) {
            return [$location => $all[$location]];
        }

        return $all;
    }

    public static function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::payload();
    }
}
