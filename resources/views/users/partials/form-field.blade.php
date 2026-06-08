<div>
    <label for="{{ $id }}" class="mb-1 block text-xs font-medium text-gray-700">
        {{ $label }}
        @if(!($required ?? false)) <span class="text-gray-400 font-normal">(optional)</span> @endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder ?? '' }}"
        {{ ($required ?? false) ? 'required' : '' }}
        class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
</div>
