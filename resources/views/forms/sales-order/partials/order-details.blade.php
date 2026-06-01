@php
    use App\Support\LocationConfig;
    $locationMap = LocationConfig::stores();
    $userLocation = auth()->user()->user_location ?? null;
    $isSuperAdmin = strtolower(Auth::user()?->role ?? '') === 'super admin';
    $regionStores = LocationConfig::regionStores($userLocation ?? '');
    $hasRegion = !empty($regionStores);
    $dropdownStores = $isSuperAdmin ? $locationMap : array_intersect_key($locationMap, array_flip($regionStores));
    $selectedRequestingStore = old('requesting_store', $isSuperAdmin ? $userLocation ?? '' : '');
    $warehouseMap = $hasRegion
        ? LocationConfig::warehousesForRegion($userLocation)
        : ($isSuperAdmin
            ? LocationConfig::wareshouse()
            : array_filter(LocationConfig::warehouses(), fn($code) => $code === LocationConfig::warehouseForStore($userLocation), ARRAY_FILTER_USE_KEY));
    $selectedWarehouseCode = old('warehouse', LocationConfig::warehouseForStore($userLocation));
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
    <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Order Details</h2>
        <p class="text-sm text-gray-500">Basic information about this sales order</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- SOF ID -->
            <div>
                <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">SOF Order ID</label>
                <input type="text" name="sof_id" value="{{ old('sof_id', $nextSofId) }}" readonly
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 font-mono text-sm text-gray-700 shadow-sm">
            </div>

            <!-- Requesting Store -->
            <div>
                <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Requesting Store</label>
                @if ($isSuperAdmin || $hasRegion)
                    <select name="requesting_store" id="requesting_store"
                        class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled {{ $selectedRequestingStore === '' ? 'selected' : '' }}>Select Store</option>
                        @foreach ($dropdownStores as $code => $name)
                            <option value="{{ $code }}" {{ $selectedRequestingStore === $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="requesting_store" value="{{ $userLocation }}">
                    <input type="text" value="{{ $locationMap[$userLocation] ?? $userLocation }}" readonly
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 text-gray-700 shadow-sm">
                @endif
            </div>

            <!-- Requested By -->
            <div>
                <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Requested By</label>
                <input type="text" value="{{ auth()->user()->name ?? 'Personnel Sample' }}" readonly
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 text-gray-700 shadow-sm">
                <input type="hidden" name="requested_by" value="{{ auth()->user()->id ?? '' }}">
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Channel of Order -->
            <div>
                <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Channel of Order</label>
                <input type="text" value="Wholesale" readonly
                    class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 text-gray-700 shadow-sm">
                <input type="hidden" name="channel_order" value="Wholesale">
            </div>

            <!-- Serving Warehouse -->
            <div>
                <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Serving Warehouse</label>
                @if ($isSuperAdmin || ($hasRegion && count($warehouseMap) > 1))
                    <select name="warehouse" id="warehouse"
                        class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled {{ $selectedWarehouseCode == '' ? 'selected' : '' }}>Select Warehouse</option>
                        @foreach ($warehouseMap as $code => $name)
                            <option value="{{ $code }}" {{ $selectedWarehouseCode == $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="warehouse" value="{{ $selectedWarehouseCode }}">
                    <input type="text" value="{{ $warehouseMap[$selectedWarehouseCode] ?? $selectedWarehouseCode }}" readonly
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 px-4 py-3 text-gray-700 shadow-sm">
                @endif
            </div>

            <!-- Date & Time -->
            <div>
                <label class="block text-xs font-medium uppercase tracking-wider text-gray-500">Date & Time of Order</label>
                <input type="datetime-local" name="time_order" value="{{ old('time_order', date('Y-m-d\TH:i')) }}"
                    class="required-input mt-1 block w-full rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </div>
</div>
