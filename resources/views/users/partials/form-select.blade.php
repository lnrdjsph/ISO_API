<div>
    <label for="{{ $id }}" class="mb-1 block text-xs font-medium text-gray-700">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" {{ ($required ?? false) ? 'required' : '' }}
        class="h-9 w-full rounded-md border border-gray-300 px-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <option value="" disabled selected>{{ $placeholder ?? 'Select…' }}</option>
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ ucwords($option) }}</option>
        @endforeach
    </select>
</div>
