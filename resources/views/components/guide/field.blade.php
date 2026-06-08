@props([
    'name',          // field label, e.g. "SOF Order ID"
])

{{-- One labelled field row. Slot may contain inline HTML (e.g. <strong>). --}}
<div class="field-row"><span class="field-label">{{ $name }}</span><span class="field-desc">{{ $slot }}</span></div>
