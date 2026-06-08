<div class="relative inline-block">
    <span class="{{ $badgeClasses }} peer inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-medium">
        {{ $value }}
    </span>
    <div class="pointer-events-none absolute left-[calc(100%+4px)] top-1/2 z-50 min-w-[120px] -translate-y-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-[10px] text-white opacity-0 shadow-lg transition-opacity peer-hover:opacity-100">
        <div class="{{ $statusColor }} text-center font-semibold">{{ $status }}</div>
    </div>
</div>
