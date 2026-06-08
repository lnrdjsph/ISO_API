@php
    $currentSort      = request('sort', 'description');
    $currentDirection = request('direction', 'asc');

    $sortUrl = fn(string $col): string => route('products.index', array_merge(
        request()->except(['page', 'direction', 'sort']),
        [
            'sort'      => $col,
            'direction' => ($currentSort === $col && $currentDirection === 'asc') ? 'desc' : 'asc',
        ]
    ));

    $sortIcon = function (string $col) use ($currentSort, $currentDirection): string {
        $active = $currentSort === $col;
        $rotate = ($active && $currentDirection === 'desc') ? 'style="transform:rotate(180deg)"' : '';
        $color  = $active ? 'color:#fff' : 'color:#6b7280';
        return "<svg {$rotate} style=\"{$color};width:10px;height:10px;flex-shrink:0;transition:transform .15s\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2.5\" d=\"M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4\"/>
                </svg>";
    };
@endphp

<thead class="sticky top-0 z-10">
    <tr style="background:#1e293b">
        {{-- Checkbox --}}
        @if (!$isPersonnel)
            <th style="width:36px;padding:10px 8px 10px 12px">
                <input type="checkbox" id="select-all"
                    class="h-3.5 w-3.5 rounded border-gray-500 bg-gray-700 focus:ring-2 focus:ring-indigo-400">
            </th>
        @else
            <th style="width:36px;padding:10px 8px;text-align:center;font-size:9px;color:#6b7280;font-weight:500;text-transform:uppercase;letter-spacing:.06em">#</th>
        @endif

        {{-- SKU + Description --}}
        <th style="padding:10px 12px;text-align:left;min-width:200px" colspan="2">
            <div style="display:flex;align-items:center;gap:10px">
                <a href="{{ $sortUrl('sku') }}"
                    style="display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:600;color:{{ $currentSort === 'sku' ? '#fff' : '#94a3b8' }};text-transform:uppercase;letter-spacing:.07em;text-decoration:none;white-space:nowrap">
                    SKU {!! $sortIcon('sku') !!}
                </a>
                <span style="color:#374151;font-size:9px">/</span>
                <a href="{{ $sortUrl('description') }}"
                    style="display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:600;color:{{ $currentSort === 'description' ? '#fff' : '#94a3b8' }};text-transform:uppercase;letter-spacing:.07em;text-decoration:none;white-space:nowrap">
                    Description {!! $sortIcon('description') !!}
                </a>
            </div>
        </th>

        {{-- Sub-dept --}}
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Sub-dept</th>

        {{-- Price --}}
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Price</th>

        {{-- WMS columns --}}
        @if ($isSuperAdmin)
            <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;border-left:1px solid #334155">
                <a href="{{ $sortUrl('warehouse_actual_allocation') }}"
                    style="display:inline-flex;align-items:center;gap:3px;color:{{ $currentSort === 'warehouse_actual_allocation' ? '#fff' : '#94a3b8' }};text-decoration:none">
                    WMS Actual {!! $sortIcon('warehouse_actual_allocation') !!}
                </a>
            </th>
        @endif
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;{{ $isSuperAdmin ? '' : 'border-left:1px solid #334155' }}">
            <a href="{{ $sortUrl('warehouse_allocation') }}"
                style="display:inline-flex;align-items:center;gap:3px;color:{{ $currentSort === 'warehouse_allocation' ? '#fff' : '#94a3b8' }};text-decoration:none">
                WMS Virtual {!! $sortIcon('warehouse_allocation') !!}
            </a>
        </th>

        {{-- Store inventory --}}
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;border-left:1px solid #334155">
            <a href="{{ $sortUrl('allocation_per_case') }}"
                style="display:inline-flex;align-items:center;gap:3px;color:{{ $currentSort === 'allocation_per_case' ? '#fff' : '#94a3b8' }};text-decoration:none">
                Stocks {!! $sortIcon('allocation_per_case') !!}
            </a>
        </th>
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Alloc.</th>
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Case pack</th>

        {{-- Schemes --}}
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;border-left:1px solid #334155">C/BC</th>
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">PO15%</th>
        <th style="padding:10px 10px;text-align:left;font-size:9px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Discount</th>

        {{-- Freebie --}}
        <th style="padding:10px 12px 10px 10px;text-align:left;font-size:9px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap;border-left:1px solid #334155">Freebie</th>
    </tr>
</thead>
