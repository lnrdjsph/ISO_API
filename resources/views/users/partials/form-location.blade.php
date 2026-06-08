<div>
    <label for="{{ $id }}" class="mb-1 block text-xs font-medium text-gray-700">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" required
        class="h-9 w-full rounded-md border border-gray-300 px-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <option value="" disabled selected>Select location</option>
        <optgroup label="Regions">
            @foreach ($regionLabels as $regionCode => $regionName)
                <option value="{{ $regionCode }}">Region: {{ $regionName }}</option>
            @endforeach
        </optgroup>
        <optgroup label="Individual Stores">
            @foreach ($storeLocations as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </optgroup>
    </select>
</div>
