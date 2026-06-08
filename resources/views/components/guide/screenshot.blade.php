@props([
    'src' => null,          // filename inside public/images/guide/, e.g. "dashboard-personnel.png"
    'caption' => null,
    'alt' => null,
    'height' => 'h-64',     // tailwind height utility for the image frame
    'placeholder' => false, // true => render a "suggested screenshot" dashed frame (no asset required)
])

@if ($placeholder)
    {{-- Suggested screenshot slot: a designer/admin can drop the named asset in later. --}}
    <div class="mt-4 overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50/60">
        <div class="flex {{ $height }} flex-col items-center justify-center gap-2 text-center">
            <svg class="h-7 w-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V4.5a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21z" />
            </svg>
            <p class="text-xs font-medium text-gray-400">Suggested screenshot</p>
            @if ($src)
                <code class="rounded bg-gray-200/70 px-1.5 py-0.5 text-[0.65rem] text-gray-500">images/guide/{{ $src }}</code>
            @endif
        </div>
        @if ($caption)
            <p class="border-t border-dashed border-gray-200 bg-white px-3 py-2 text-center text-xs text-gray-400">{{ $caption }}</p>
        @endif
    </div>
@else
    <div class="screenshot-frame mt-4 overflow-hidden rounded-lg bg-gray-50 ring-1 ring-gray-200">
        <div class="flex {{ $height }} items-center justify-center overflow-hidden">
            <img class="w-full object-cover {{ $height === 'h-auto' ? '' : 'h-full' }}" src="{{ asset('images/guide/' . $src) }}" alt="{{ $alt ?? $caption ?? 'Screenshot' }}">
        </div>
        @if ($caption)
            <p class="border-t border-gray-100 bg-white px-3 py-2 text-center text-xs text-gray-400">{{ $caption }}</p>
        @endif
    </div>
@endif
