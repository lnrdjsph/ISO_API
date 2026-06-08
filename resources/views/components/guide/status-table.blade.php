@props([
    'headers' => ['Status', 'Meaning', 'Action'],
    // Each row: ['dot' => 'bg-blue-500', 'status' => 'New Order', 'cells' => ['meaning', 'action', ...]]
    // 'dot' is optional — omit it for a plain first cell.
    'rows' => [],
])

<div class="mt-3 overflow-hidden rounded-lg ring-1 ring-gray-200">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                @foreach ($headers as $h)
                    <th class="px-4 py-2.5">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5">
                        @if (!empty($row['dot']))
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                                <span class="{{ $row['dot'] }} inline-block h-2 w-2 rounded-full"></span>{{ $row['status'] }}
                            </span>
                        @else
                            <span class="font-medium text-gray-800">{{ $row['status'] }}</span>
                        @endif
                    </td>
                    @foreach ($row['cells'] as $cell)
                        <td class="px-4 py-2.5 text-gray-600">{!! $cell !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
