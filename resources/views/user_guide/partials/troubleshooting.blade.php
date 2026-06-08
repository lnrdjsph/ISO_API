{{-- ═══ 08 · TROUBLESHOOTING ═══ --}}
<x-guide.section id="troubleshooting" number="08" title="Troubleshooting" roles="personnel">

    <x-guide.table :headers="['Error', 'Cause', 'Solution']" :rows="[
        ['<span class=\'font-medium text-red-600\'>&quot;MBC Card not found&quot;</span>', 'Invalid or incomplete card', 'Check &amp; re-enter the 16 digits'],
        ['<span class=\'font-medium text-red-600\'>&quot;Order Items missing&quot;</span>', 'Incomplete header', 'Complete all required header fields'],
        ['<span class=\'font-medium text-red-600\'>&quot;Cannot submit&quot;</span>', 'No products added', 'Add at least one product with qty &gt; 0'],
        ['<span class=\'font-medium text-red-600\'>&quot;Update inactive&quot;</span>', 'No changes detected', 'Edit a field first — a yellow highlight appears'],
    ]" />

    <h3 class="mt-6 text-sm font-semibold text-gray-900">Quick Tips</h3>
    <div class="mt-3 space-y-2">
        @foreach ([
            '<strong>Dashboard</strong> is your best first stop each day to see what needs attention.',
            '<strong>Orders List</strong> filters and pages update instantly — no need to reload.',
            '<strong>MBC Card</strong> must be exactly 16 digits, numbers only.',
            '<strong>Yellow highlights</strong> on Order Details mean you have unsaved changes. Click Update to save.',
            '<strong>Products page</strong> is view-only — ask your admin for any product changes.',
            '<strong>Order Notes</strong> keep a dated record of every action — handy as your history and proof trail.',
        ] as $t)
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg>
                <p class="text-sm text-gray-600">{!! $t !!}</p>
            </div>
        @endforeach
    </div>
</x-guide.section>
