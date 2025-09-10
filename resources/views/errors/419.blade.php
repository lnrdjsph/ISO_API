@extends('layouts.app')

@section('content')
		<div class="flex h-screen items-center justify-center bg-gradient-to-br from-indigo-100 via-white to-red-100 px-4 sm:px-6 lg:px-8">
				<div class="w-full max-w-md">
						<div class="rounded-2xl border border-gray-200 bg-white/80 p-8 text-center shadow-xl backdrop-blur-md">
								<div class="mb-6">
										<svg
												class="mx-auto h-16 w-16 text-rose-500"
												fill="none"
												stroke="currentColor"
												stroke-width="1.5"
												viewBox="0 0 24 24"
										>
												<path
														stroke-linecap="round"
														stroke-linejoin="round"
														d="M12 9v2m0 4h.01M4.93 4.93a10 10 0 0114.14 0m0 14.14a10 10 0 01-14.14 0m14.14 0L4.93 4.93"
												/>
										</svg>
								</div>
								<h1 class="mb-2 text-6xl font-extrabold text-gray-800">419</h1>
								<h2 class="mb-3 text-lg font-semibold text-gray-700">Page Expired</h2>
								<p class="mb-6 text-sm leading-relaxed text-gray-500">
										Your session has expired due to inactivity. Please reload the page or log in again.
								</p>
								<a
										href="{{ url()->current() }}"
										class="inline-block w-full rounded-xl bg-gradient-to-r from-red-600 to-rose-600 py-3 text-sm font-semibold text-white shadow-md transition hover:from-red-700 hover:to-rose-700"
								>
										⬅ Reload Page
								</a>
						</div>
				</div>
		</div>
@endsection
