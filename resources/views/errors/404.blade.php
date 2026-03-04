<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1" />
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}">
    <meta
        name="description"
        content="ISO B2B2C Ordering System">
    <meta
        name="theme-color"
        content="darkblue" />
    <title>ISO B2B2C Ordering System</title>

    <link
        rel="icon"
        type="image/png"
        href="{{ asset('images/MarengEms_Logo.png') }}">
    {{-- <link href="{{ mix('css/app.css') }}" rel="stylesheet" /> --}}
    @vite('resources/css/app.css')

</head>

<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-indigo-100 via-white to-pink-100 px-4 sm:px-6 lg:px-8">
    <div class="">
        <div class="w-full max-w-md">
            <div class="rounded-2xl border border-gray-200 bg-white/80 p-8 text-center shadow-xl backdrop-blur-md">
                <div class="mb-6">
                    <svg
                        class="mx-auto h-16 w-16 text-rose-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v2m0 4h.01M4.93 4.93a10 10 0 0114.14 0m0 14.14a10 10 0 01-14.14 0m14.14 0L4.93 4.93" />
                    </svg>
                </div>
                <h1 class="mb-2 text-6xl font-extrabold text-gray-800">404</h1>
                <h2 class="mb-3 text-lg font-semibold text-gray-700">Page Not Found</h2>
                <p class="mb-6 text-sm leading-relaxed text-gray-500">
                    The page you're looking for doesn’t exist or has been moved. You may want to return to the homepage.
                </p>
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-block w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 py-3 text-sm font-semibold text-white shadow-md transition hover:from-blue-700 hover:to-indigo-700">
                    ⬅ Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>

</html>
