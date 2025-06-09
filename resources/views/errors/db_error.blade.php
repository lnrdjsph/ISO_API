@extends('layouts.app')

@section('content')
    <div class="flex justify-center items-center min-h-screen bg-gray-50">
        <div class="max-w-xl w-full p-6 bg-white border border-red-200 rounded-2xl shadow-md">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.054 0 1.635-1.14 1.09-2.045L13.09 4.955a1.25 1.25 0 00-2.18 0L3.992 17.955c-.545.905.036 2.045 1.09 2.045z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-red-600">Database Connection Error</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        We encountered a problem connecting to the database. Please try again later or contact support.
                    </p>
                </div>
            </div>
            <div class="mt-4 bg-gray-100 p-4 rounded-lg text-sm text-red-700 font-mono break-all">
                {{ $error }}
            </div>
        </div>
    </div>
@endsection
