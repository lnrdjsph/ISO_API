<!DOCTYPE html>
<html lang="en">

		<head>
				<meta charset="UTF-8" />
				<meta
						name="viewport"
						content="width=device-width, initial-scale=1"
				/>
				<meta
						name="csrf-token"
						content="{{ csrf_token() }}"
				>
				<title>My App</title>
				<link
						rel="icon"
						type="image/png"
						href="{{ asset('images/MarengEms_Logo.png') }}"
				>
				{{-- <link href="{{ mix('css/app.css') }}" rel="stylesheet" /> --}}
				@vite('resources/css/app.css')

		</head>

		<body class="relative bg-gray-100 md:flex">

				<!-- Mobile overlay -->
				<div
						id="overlay"
						class="fixed inset-0 z-20 hidden bg-black bg-opacity-50 md:hidden"
				></div>

				<!-- Sidebar -->
				<aside
						id="sidebar"
						class="sticky top-0 z-30 hidden h-screen w-64 bg-white md:block"
				>
						<div class="flex items-center justify-center border-b border-gray-200 p-6">
								{{-- <h2 class="text-lg font-semibold">ISO B2B</h2> --}}
								{{-- Logo --}}
								<div class="">
										<img
												src="{{ asset('images/MarengEms_Logo.png') }}"
												alt="Logo"
												class="mx-auto h-[125px] w-auto"
										>
								</div>

						</div>
						<nav class="p-4">
								<ul class="space-y-2">
										{{-- Dashboard --}}
										<li>
												<a
														href="{{ route('dashboard') }}"
														class="{{ request()->routeIs('') ? 'bg-gray-200 font-bold' : '' }} block rounded px-4 py-2 hover:bg-gray-100"
												>
														Dashboard
												</a>
										</li>

										{{-- Orders Group --}}
										<li class="rounded">
												<div class="{{ request()->routeIs('orders*') ? 'bg-gray-100' : '' }} rounded">
														@if (request()->routeIs('orders*'))
																<h3 class="px-4 py-1 text-xs uppercase tracking-wider text-gray-500">Orders</h3>
																<ul class="mt-1 rounded transition-all duration-300">
																		<li class="relative">
																				<a
																						href="{{ route('orders.index') }}"
																						class="{{ request()->routeIs('orders.index') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }} relative block rounded py-2 pl-6 transition-all duration-300 hover:text-indigo-500"
																				>
																						Sales Order List
																				</a>
																		</li>
																		@if (preg_match('/orders\/\d+$/', request()->path()))
																				<li class="relative">
																						<a
																								href="{{ url()->current() }}"
																								class="relative block rounded py-2 pl-6 font-medium text-blue-800 transition-all duration-300 before:absolute before:left-2 before:top-1/2 before:h-2 before:w-2 before:-translate-y-1/2 before:rounded-full before:bg-blue-600 before:content-[''] hover:text-indigo-500"
																						>
																								Sales Order Details
																						</a>
																				</li>
																		@endif
																</ul>
														@else
																<a
																		href="{{ route('orders.index') }}"
																		class="block rounded px-4 py-2 hover:bg-gray-100"
																>
																		Orders
																</a>
														@endif
												</div>
										</li>

										{{-- Forms Group --}}
										<li class="rounded">
												<div class="{{ request()->routeIs('forms*') ? 'bg-gray-100' : '' }} rounded">
														@if (request()->routeIs('forms*'))
																<h3 class="px-4 py-1 text-xs uppercase tracking-wider text-gray-500">Forms</h3>
																<ul class="mt-1 rounded transition-all duration-300">
																		<li class="relative">
																				<a
																						href="{{ route('forms.sof') }}"
																						class="{{ request()->routeIs('forms.sof') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }} relative block rounded-md py-2 pl-6 transition-all duration-300 hover:text-indigo-500"
																				>
																						Sales Order Form
																				</a>
																		</li>
																		<li class="relative">
																				<a
																						href="{{ route('forms.rof') }}"
																						class="{{ request()->routeIs('forms.rof') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }} relative block rounded-md py-2 pl-6 transition-all duration-300 hover:text-indigo-500"
																				>
																						Request Order Form
																				</a>
																		</li>
																</ul>
														@else
																<a
																		href="{{ route('forms.sof') }}"
																		class="block rounded px-4 py-2 hover:bg-gray-100"
																>
																		Forms
																</a>
														@endif

												</div>
										</li>

										{{-- Products Group --}}
										<li class="rounded">
												<div class="{{ request()->routeIs('products*') ? 'bg-gray-100' : '' }} rounded">
														@if (request()->routeIs('products*'))
																<h3 class="px-4 py-1 text-xs uppercase tracking-wider text-gray-500">Products</h3>
																<ul class="mt-1 rounded transition-all duration-300">
																		<li class="relative">
																				<a
																						href="{{ route('products.index') }}"
																						class="{{ request()->routeIs('products.index') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }} relative block rounded-md py-2 pl-6 transition-all duration-300 hover:text-indigo-500"
																				>
																						Product List
																				</a>
																		</li>
																		<li class="relative">
																				<a
																						href="{{ route('products.create') }}"
																						class="{{ request()->routeIs('products.create') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }} relative block rounded-md py-2 pl-6 transition-all duration-300 hover:text-indigo-500"
																				>
																						Add New Product
																				</a>
																		</li>
																		<li class="relative">
																				<a
																						href="{{ route('products.import.show') }}"
																						class="{{ request()->routeIs('products.import.show') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }} relative block rounded-md py-2 pl-6 transition-all duration-300 hover:text-indigo-500"
																				>
																						Import CSV
																				</a>
																		</li>
																</ul>
														@else
																<a
																		href="{{ route('products.index') }}"
																		class="block rounded px-4 py-2 hover:bg-gray-100"
																>
																		Products
																</a>
														@endif
												</div>
										</li>

								</ul>

								<!-- Logout -->
								<form
										method="POST"
										action="{{ route('logout') }}"
										class="absolute bottom-6 left-0 w-full px-4"
								>
										@csrf
										<button
												type="submit"
												class="w-full rounded py-2 text-left text-center font-medium text-red-600 hover:bg-red-100"
										>
												Logout
										</button>
								</form>
						</nav>
				</aside>

				<!-- Mobile Header -->
				<header class="relative z-30 block flex items-center justify-between border-b bg-white px-6 py-4 md:hidden">
						<div class="flex items-center space-x-4">
								<button
										id="toggleMobileMenu"
										class="rounded p-2 hover:bg-gray-100"
								>
										<svg
												class="h-6 w-6"
												fill="none"
												stroke="currentColor"
												viewBox="0 0 24 24"
										>
												<path
														stroke-linecap="round"
														stroke-linejoin="round"
														stroke-width="2"
														d="M4 6h16M4 12h16M4 18h16"
												/>
										</svg>
								</button>
								<h2 class="text-lg font-semibold">ISO B2BC</h2>

						</div>
				</header>

				<!-- Mobile Nav -->
				<nav
						id="mobileMenu"
						class="hidden border-b bg-white p-4 md:hidden"
				>
						<ul class="space-y-2">
								@php
										$isDashboard = url()->current() === route('dashboard');
								@endphp

								<li>
										<a
												href="{{ route('dashboard') }}"
												class="{{ $isDashboard ? 'bg-gray-200 font-bold' : '' }} block rounded px-4 py-2 hover:bg-gray-100"
										>
												Dashboard
										</a>
								</li>

								<!-- Orders Group -->
								@php $isOrders = request()->routeIs('orders*'); @endphp
								<li class="{{ $isOrders ? 'bg-gray-100' : '' }} rounded">
										<button
												class="{{ $isOrders ? 'bg-gray-100' : '' }} flex w-full items-center justify-between rounded px-4 py-2 text-left hover:bg-gray-100"
												data-toggle="mobile-orders"
										>
												Orders
												<svg
														class="{{ $isOrders ? 'rotate-180' : '' }} h-4 w-4 transform transition-transform"
														data-icon="mobile-orders"
														fill="none"
														stroke="currentColor"
														viewBox="0 0 24 24"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M19 9l-7 7-7-7"
														/>
												</svg>
										</button>
										<ul
												id="mobile-orders"
												class="{{ $isOrders ? '' : 'hidden' }} mt-1 rounded transition-all duration-300"
										>
												<li>
														<a
																href="{{ route('orders.index') }}"
																class="{{ request()->routeIs('orders.index') ? 'text-blue-800 font-medium' : '' }} block py-2 pl-6 hover:text-indigo-500"
														>
																Sales Order List
														</a>
												</li>
												@if (preg_match('/orders\/\d+$/', request()->path()))
														<li>
																<a
																		href="{{ url()->current() }}"
																		class="block py-2 pl-6 font-medium text-blue-800 hover:text-indigo-500"
																>
																		Sales Order Details
																</a>
														</li>
												@endif
										</ul>
								</li>

								<!-- Forms Group -->
								@php $isForms = request()->routeIs('forms*'); @endphp
								<li class="{{ $isForms ? 'bg-gray-100' : '' }} rounded">
										<button
												class="flex w-full items-center justify-between rounded px-4 py-2 text-left hover:bg-gray-100"
												data-toggle="mobile-forms"
										>
												Forms
												<svg
														class="{{ $isForms ? 'rotate-180' : '' }} h-4 w-4 transform transition-transform"
														data-icon="mobile-forms"
														fill="none"
														stroke="currentColor"
														viewBox="0 0 24 24"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M19 9l-7 7-7-7"
														/>
												</svg>
										</button>
										<ul
												id="mobile-forms"
												class="{{ $isForms ? '' : 'hidden' }} mt-1 rounded transition-all duration-300"
										>
												<li>
														<a
																href="{{ route('forms.sof') }}"
																class="{{ request()->routeIs('forms.sof') ? 'text-blue-800 font-medium' : '' }} block py-2 pl-6 hover:text-indigo-500"
														>
																Sales Order Form
														</a>
												</li>
												<li>
														<a
																href="{{ route('forms.rof') }}"
																class="{{ request()->routeIs('forms.rof') ? 'text-blue-800 font-medium' : '' }} block py-2 pl-6 hover:text-indigo-500"
														>
																Request Order Form
														</a>
												</li>
										</ul>
								</li>

								<!-- Products Group -->
								@php $isProducts = request()->routeIs('products*'); @endphp
								<li class="{{ $isProducts ? 'bg-gray-100' : '' }} rounded">
										<button
												class="flex w-full items-center justify-between rounded px-4 py-2 text-left hover:bg-gray-100"
												data-toggle="mobile-products"
										>
												Products
												<svg
														class="{{ $isProducts ? 'rotate-180' : '' }} h-4 w-4 transform transition-transform"
														data-icon="mobile-products"
														fill="none"
														stroke="currentColor"
														viewBox="0 0 24 24"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M19 9l-7 7-7-7"
														/>
												</svg>
										</button>
										<ul
												id="mobile-products"
												class="{{ $isProducts ? '' : 'hidden' }} mt-1 rounded transition-all duration-300"
										>
												<li>
														<a
																href="{{ route('products.index') }}"
																class="{{ request()->routeIs('products.index') ? 'text-blue-800 font-medium' : '' }} block py-2 pl-6 hover:text-indigo-500"
														>
																Product List
														</a>
												</li>
												<li>
														<a
																href="{{ route('products.create') }}"
																class="{{ request()->routeIs('products.create') ? 'text-blue-800 font-medium' : '' }} block py-2 pl-6 hover:text-indigo-500"
														>
																Add New Product
														</a>
												</li>
												<li>
														<a
																href="{{ route('products.import.show') }}"
																class="{{ request()->routeIs('products.import.show') ? 'text-blue-800 font-medium' : '' }} block py-2 pl-6 hover:text-indigo-500"
														>
																Import CSV
														</a>
												</li>
										</ul>
								</li>

						</ul>
						<!-- Logout -->
						<form
								method="POST"
								action="{{ route('logout') }}"
								class="absolute bottom-6 left-0 w-full px-4"
						>
								@csrf
								<button
										type="submit"
										class="w-full rounded px-4 py-2 text-left font-medium text-red-600 hover:bg-red-100"
								>
										Logout
								</button>
						</form>
				</nav>

				<!-- Main Content -->
				<div class="min-h-screen w-full md:flex-1">
						{{-- <!-- Top bar with hamburger menu -->
        <div class="bg-white border-b px-6 py-4 md:hidden">
            <button id="toggleSidebar" class="p-2 rounded hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div> --}}

						<!-- Content area -->
						<main>
								@yield('content')
						</main>
				</div>
				<style>
						@keyframes loading-shimmer {
								0% {
										background-position: -200px 0;
								}

								100% {
										background-position: 200px 0;
								}
						}

						.loading-bg {
								background: linear-gradient(90deg,
												rgba(229, 231, 235, 0) 25%,
												rgba(229, 231, 235, 0.6) 50%,
												rgba(229, 231, 235, 0) 75%);
								background-size: 400px 100%;
								animation: loading-shimmer 1.2s linear 4;
								/* ~4.8s total */
						}
				</style>
				@vite('resources/js/app.js')
				<script>
						// Mobile menu main toggle
						document.getElementById('toggleMobileMenu')?.addEventListener('click', () => {
								document.getElementById('mobileMenu')?.classList.toggle('hidden');
						});

						// Handle section toggles (Products, Orders, Forms)
						document.querySelectorAll('[data-toggle]').forEach(btn => {
								btn.addEventListener('click', () => {
										const targetId = btn.getAttribute('data-toggle');
										const submenu = document.getElementById(targetId);
										const icon = document.querySelector(`[data-icon="${targetId}"]`);

										submenu?.classList.toggle('hidden');
										icon?.classList.toggle('rotate-180');
								});
						});

						document.addEventListener("DOMContentLoaded", () => {
								document.querySelectorAll("nav a").forEach(link => {
										link.addEventListener("click", function() {
												this.classList.add("loading-bg");

												setTimeout(() => {
														this.classList.remove("loading-bg");
												}, 5000); // remove after 5s
										});
								});
						});
				</script>

		</body>

</html>
