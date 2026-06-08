@props([
    'items' => [],     // array of strings; each may contain inline HTML
    'color' => 'blue', // blue | green | red
    'start' => 0,      // numbering offset (e.g. continuing a previous list)
])

@php
    $dots = [
        'blue'  => 'bg-blue-600',
        'green' => 'bg-green-600',
        'red'   => 'bg-red-600',
    ];
    $dot = $dots[$color] ?? $dots['blue'];
@endphp

<div class="mt-3 space-y-1.5">
    @foreach ($items as $i => $step)
        <div class="step-row flex items-start gap-3 rounded-lg bg-gray-50 px-3 py-2.5">
            <span class="flex h-5 w-5 items-center justify-center rounded-full {{ $dot }} text-[0.65rem] font-bold text-white">{{ $i + 1 + $start }}</span>
            <span class="text-sm text-gray-600">{!! $step !!}</span>
        </div>
    @endforeach
</div>
