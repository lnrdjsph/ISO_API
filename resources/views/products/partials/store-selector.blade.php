{{--
    Reusable store selector for create / import pages.
    Requires: $accessibleStores, $hasMultipleStores, $singleStoreCode, $singleStoreName, $currentStore
    Emits a <select name="store"> or a read-only badge — consumed by resolveTableStoreCode().
--}}
<div class="flex items-center gap-3">
    <span class="text-xs font-medium text-gray-500">Target store:</span>

    @if (empty($accessibleStores))
        <span class="inline-flex h-8 items-center rounded-md border border-yellow-200 bg-yellow-50 px-3 text-xs text-yellow-700">
            No stores assigned
        </span>

    @elseif (!$hasMultipleStores)
        {{-- Single store: show as badge and pass value as hidden input --}}
        <span class="inline-flex h-8 items-center gap-1.5 rounded-md border border-indigo-200 bg-indigo-50 px-3 text-xs font-medium text-indigo-700">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            {{ $singleStoreCode }} – {{ $singleStoreName }}
        </span>
        <input type="hidden" name="store" value="{{ $singleStoreCode }}">

    @else
        {{-- Multiple stores: show dropdown --}}
        <select name="store"
            class="h-8 rounded-md border border-gray-300 px-2 text-xs font-medium shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:h-9">
            @foreach ($accessibleStores as $code => $name)
                <option value="{{ $code }}" {{ $currentStore == $code ? 'selected' : '' }}>
                    {{ $code }} – {{ $name }}
                </option>
            @endforeach
        </select>
    @endif
</div>
