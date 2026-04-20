<?php

namespace App\Support;

/**
 * LocationConfig
 *
 * Static helper that wraps config('locations.*') so controllers and
 * Blade views never need to maintain local mapping arrays.
 *
 * Place this file at:  app/Support/LocationConfig.php
 *
 * No service-provider registration is required – it is just a plain
 * static class.  Import it wherever needed:
 *
 *   use App\Support\LocationConfig;
 */
class LocationConfig
{
    // -----------------------------------------------------------------------
    // Stores
    // -----------------------------------------------------------------------

    /**
     * All stores as  code => display-label.
     *
     * @return array<string, string>
     */
    public static function stores(): array
    {
        return config('locations.stores', []);
    }

    /**
     * Display label for a store code.
     *
     * @param  string      $code  e.g. '4002'
     * @param  string|null $default Returned when $code is unknown.
     */
    public static function storeName(string $code, ?string $default = null): string
    {
        return config("locations.stores.{$code}", $default ?? $code);
    }

    /**
     * Store code from a short name (WooCommerce / Atom integration).
     *
     * @param  string      $name  e.g. 'Metro Maasin'
     * @param  string|null $default
     */
    public static function storeCode(string $name, ?string $default = null): ?string
    {
        return config("locations.store_name_to_code.{$name}", $default);
    }

    // -----------------------------------------------------------------------
    // Warehouses
    // -----------------------------------------------------------------------

    /**
     * All active warehouses as  code => display-label.
     *
     * @return array<string, string>
     */
    public static function warehouses(): array
    {
        return config('locations.warehouses', []);
    }

    /**
     * Display name for a warehouse code.
     *
     * @param  string      $code  e.g. '80181'
     * @param  string|null $default
     */
    public static function warehouseName(string $code, ?string $default = null): string
    {
        return config("locations.warehouses.{$code}", $default ?? $code);
    }

    // -----------------------------------------------------------------------
    // Cross-mappings
    // -----------------------------------------------------------------------

    /**
     * Warehouse code that serves a given store code (or region alias).
     *
     * @param  string      $storeCode  e.g. '4002' or 'vs'
     * @param  string|null $default
     */
    public static function warehouseForStore(string $storeCode, ?string $default = null): ?string
    {
        return config("locations.store_to_warehouse.{$storeCode}", $default);
    }

    /**
     * All store codes served by a warehouse.
     *
     * @param  string $warehouseCode  e.g. '80181'
     * @return array<int, string>
     */
    public static function storesForWarehouse(string $warehouseCode): array
    {
        return config("locations.warehouse_to_stores.{$warehouseCode}", []);
    }

    /**
     * Oracle WMS facility ID for a warehouse code.
     *
     * @param  string      $warehouseCode
     * @param  string|null $default Falls back to the warehouse code itself.
     */
    public static function facilityForWarehouse(string $warehouseCode, ?string $default = null): string
    {
        return config("locations.warehouse_to_facility.{$warehouseCode}", $default ?? $warehouseCode);
    }

    // -----------------------------------------------------------------------
    // Regions / Manager groups
    // -----------------------------------------------------------------------

    /**
     * Store codes belonging to a manager region alias.
     *
     * @param  string $region  e.g. 'vs' or 'lz'
     * @return array<int, string>
     */
    public static function regionStores(string $region): array
    {
        return config("locations.regions.{$region}", []);
    }

    /**
     * All region aliases.
     *
     * @return array<string, array<int, string>>
     */
    public static function regions(): array
    {
        return config('locations.regions', []);
    }

    // -----------------------------------------------------------------------
    // Convenience helpers used in multiple controllers
    // -----------------------------------------------------------------------

    /**
     * Resolve the warehouse code for the currently authenticated user,
     * mirroring the logic that was copy-pasted across controllers.
     *
     * Priority:
     *  1. $requestedWarehouse (from query-string / form) when user is NOT personnel
     *  2. user_location → warehouse mapping
     *
     * @param  string|null $requestedWarehouse  Value from request input 'warehouse'.
     * @param  string|null $userLocation        auth()->user()->user_location
     * @param  bool        $isPersonnel         Whether the user has a personnel role.
     * @return string|null
     */
    public static function resolveWarehouseCode(
        ?string $requestedWarehouse,
        ?string $userLocation,
        bool $isPersonnel = false
    ): ?string {
        $allowed = array_keys(self::warehouses());

        if (!$isPersonnel && $requestedWarehouse && in_array($requestedWarehouse, $allowed, true)) {
            return $requestedWarehouse;
        }

        return $userLocation ? self::warehouseForStore($userLocation) : null;
    }

    /**
     * Filter the full stores list to only those accessible by a given user.
     *
     * - super admin  → all stores
     * - manager      → their region stores
     * - personnel    → their single store
     *
     * @param  string $role          auth()->user()->role
     * @param  string|null $location auth()->user()->user_location
     * @return array<string, string>  store_code => label
     */
    public static function accessibleStores(string $role, ?string $location): array
    {
        $all = self::stores();

        if ($role === 'super admin') {
            return $all;
        }

        if ($role === 'manager' && $location) {
            $codes = self::regionStores($location);
            return $codes ? array_intersect_key($all, array_flip($codes)) : $all;
        }

        if ($location && isset($all[$location])) {
            return [$location => $all[$location]];
        }

        return $all;
    }
}
