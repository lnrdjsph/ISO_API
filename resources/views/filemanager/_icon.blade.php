{{--
    Partial: resources/views/filemanager/_icon.blade.php
    Usage:   @include('filemanager._icon', ['type' => $entry['ext']])
    Pass 'folder' for directories, 'up' for the parent link.
--}}
@php
    $icons = [
        // Folders
        'folder' => ['bg' => 'bg-amber-100', 'fg' => 'text-amber-500', 'path' => 'M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z'],

        // Spreadsheets
        'xlsx' => [
            'bg' => 'bg-green-100',
            'fg' => 'text-green-600',
            'path' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],
        'xls' => [
            'bg' => 'bg-green-100',
            'fg' => 'text-green-600',
            'path' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],
        'csv' => [
            'bg' => 'bg-green-100',
            'fg' => 'text-green-600',
            'path' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],

        // Word docs
        'docx' => [
            'bg' => 'bg-blue-100',
            'fg' => 'text-blue-600',
            'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],
        'doc' => [
            'bg' => 'bg-blue-100',
            'fg' => 'text-blue-600',
            'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],

        // PDFs
        'pdf' => ['bg' => 'bg-red-100', 'fg' => 'text-red-500', 'path' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],

        // Images
        'png' => [
            'bg' => 'bg-pink-100',
            'fg' => 'text-pink-500',
            'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'jpg' => [
            'bg' => 'bg-pink-100',
            'fg' => 'text-pink-500',
            'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'jpeg' => [
            'bg' => 'bg-pink-100',
            'fg' => 'text-pink-500',
            'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'gif' => [
            'bg' => 'bg-pink-100',
            'fg' => 'text-pink-500',
            'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'svg' => [
            'bg' => 'bg-pink-100',
            'fg' => 'text-pink-500',
            'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'webp' => [
            'bg' => 'bg-pink-100',
            'fg' => 'text-pink-500',
            'path' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],

        // Code
        'php' => ['bg' => 'bg-violet-100', 'fg' => 'text-violet-600', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'js' => ['bg' => 'bg-yellow-100', 'fg' => 'text-yellow-600', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'ts' => ['bg' => 'bg-blue-100', 'fg' => 'text-blue-500', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'html' => ['bg' => 'bg-orange-100', 'fg' => 'text-orange-500', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'css' => ['bg' => 'bg-sky-100', 'fg' => 'text-sky-500', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'json' => ['bg' => 'bg-gray-100', 'fg' => 'text-gray-600', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'env' => ['bg' => 'bg-gray-100', 'fg' => 'text-gray-600', 'path' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
        'sql' => ['bg' => 'bg-cyan-100', 'fg' => 'text-cyan-600', 'path' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],

        // Archives
        'zip' => ['bg' => 'bg-amber-100', 'fg' => 'text-amber-600', 'path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        'rar' => ['bg' => 'bg-amber-100', 'fg' => 'text-amber-600', 'path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        '7z' => ['bg' => 'bg-amber-100', 'fg' => 'text-amber-600', 'path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],

        // Text
        'txt' => [
            'bg' => 'bg-gray-100',
            'fg' => 'text-gray-500',
            'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],
        'md' => [
            'bg' => 'bg-gray-100',
            'fg' => 'text-gray-500',
            'path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z',
        ],

        // Fallback
        'default' => ['bg' => 'bg-gray-100', 'fg' => 'text-gray-400', 'path' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
    ];

    $icon = $icons[$type ?? 'default'] ?? $icons['default'];
@endphp

<div class="{{ $icon['bg'] }} flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $icon['fg'] }} h-4 w-4"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['path'] }}" />
    </svg>
</div>
