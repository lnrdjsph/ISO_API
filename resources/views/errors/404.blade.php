@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-100 via-white to-pink-100 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="bg-white/80 backdrop-blur-md border border-gray-200 shadow-xl rounded-2xl p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-rose-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v2m0 4h.01M4.93 4.93a10 10 0 0114.14 0m0 14.14a10 10 0 01-14.14 0m14.14 0L4.93 4.93" />
                </svg>
            </div>
            <h1 class="text-6xl font-extrabold text-gray-800 mb-2">404</h1>
            <h2 class="text-lg font-semibold text-gray-700 mb-3">Page Not Found</h2>
            <p class="text-gray-500 mb-6 text-sm leading-relaxed">
                The page you're looking for doesn’t exist or has been moved. You may want to return to the homepage.
            </p>
            <a href="{{ route('dashboard') }}"
               class="inline-block w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold py-3 rounded-xl shadow-md hover:from-blue-700 hover:to-indigo-700 transition">
                ⬅ Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
