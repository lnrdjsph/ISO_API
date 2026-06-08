@props([
    'type' => 'info',   // info | tip | success | warning | danger | note
    'title' => null,
])

@php
    $styles = [
        'info'    => 'bg-blue-50 text-blue-800',
        'tip'     => 'bg-emerald-50 text-emerald-800',
        'success' => 'bg-green-50 text-green-800',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger'  => 'bg-red-50 text-red-800',
        'note'    => 'bg-indigo-50 text-indigo-800',
    ];
    $cls = $styles[$type] ?? $styles['info'];
@endphp

<div {{ $attributes->merge(['class' => "mt-3 rounded-lg px-4 py-3 text-sm $cls"]) }}>
    @if ($title)<strong>{{ $title }}</strong> @endif{{ $slot }}
</div>
