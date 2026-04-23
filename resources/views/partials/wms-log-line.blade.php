{{--
    Partial: resources/views/partials/wms-log-line.blade.php
    Expects: $line = ['timestamp' => string|null, 'body' => string, 'type' => string]
    Types: 'error', 'warning', 'info', 'separator', 'header', 'summary', 'table', 'plain'
--}}
@php
    $lineClass = 'log-line';
    $lineClass .= match ($line['type']) {
        'error' => ' line-error',
        'warning' => ' line-warning',
        'header' => ' line-header',
        'separator' => ' line-separator',
        'summary' => ' line-summary',
        default => '',
    };
@endphp

<div class="{{ $lineClass }}">
    @if ($line['timestamp'])
        <span class="log-timestamp">[{{ $line['timestamp'] }}]</span>
    @else
        <span class="log-timestamp select-none opacity-0" aria-hidden="true">[00:00:00]</span>
    @endif

    <span class="log-body">{!! $line['html'] !!}</span>
</div>
