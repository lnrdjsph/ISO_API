@props([
    'id',
    'title',
    'number' => null,
    'roles' => 'personnel manager admin',
    'color' => 'blue',
    'tag' => null,
])

@php
    // Space-separated role tokens that control which switcher view this section appears in.
    $roleTokens = is_array($roles) ? implode(' ', $roles) : $roles;

    // Literal class strings (kept literal so Tailwind's content scanner picks them up).
    $palette = [
        'blue'   => ['badge' => 'bg-blue-600',   'tag' => 'bg-blue-50 text-blue-700'],
        'green'  => ['badge' => 'bg-green-600',  'tag' => 'bg-green-50 text-green-700'],
        'indigo' => ['badge' => 'bg-indigo-600', 'tag' => 'bg-indigo-50 text-indigo-700'],
        'purple' => ['badge' => 'bg-purple-600', 'tag' => 'bg-purple-50 text-purple-700'],
        'amber'  => ['badge' => 'bg-amber-600',  'tag' => 'bg-amber-50 text-amber-700'],
        'teal'   => ['badge' => 'bg-teal-600',   'tag' => 'bg-teal-50 text-teal-700'],
    ];
    $c = $palette[$color] ?? $palette['blue'];
@endphp

<section id="{{ $id }}" data-roles="{{ $roleTokens }}"
    class="section-card mb-6 scroll-mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
    <div class="px-6 pt-5">
        <div class="flex items-center gap-3">
            @if (!is_null($number))
                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $c['badge'] }} text-xs font-bold text-white shadow-sm">{{ $number }}</span>
            @endif
            <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
            @if ($tag)
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $c['tag'] }}">{{ $tag }}</span>
            @endif
        </div>
    </div>
    <div class="px-6 pb-6 pt-3">
        {{ $slot }}
    </div>
</section>
