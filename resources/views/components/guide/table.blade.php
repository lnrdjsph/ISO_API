@props([
    'headers' => [],   // array of column header labels
    'rows' => [],      // array of rows; each row is an array of cells (strings, may contain inline HTML)
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
                    @foreach ($row as $cell)
                        <td class="px-4 py-2.5 text-gray-600">{!! $cell !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
