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
		<meta
				name="description"
				content="ISO B2B Ordering System"
		>
		<meta
				name="theme-color"
				content="darkblue"
		/>
		<title>ISO B2B Ordering System</title>

		<link
				rel="icon"
				type="image/png"
				href="{{ asset('images/MarengEms_Logo.png') }}"
		>
		@vite('resources/css/app.css')

		<style>
				/* Prevent horizontal scroll completely */
				html,
				body {
						overflow-x: hidden;
						max-width: 100vw;
				}

				/* Remove all list styling */
				ul,
				li {
						list-style: none;
						list-style-type: none;
						margin: 0;
						padding: 0;
				}

				/* Sidebar width states with strict containment */
				.sidebar-expanded {
						width: 13rem;
						max-width: 13rem;
						min-width: 13rem;
				}

				.sidebar-collapsed {
						width: 4.5rem;
						max-width: 4.5rem;
						min-width: 4.5rem;
				}


				/* Sidebar container with strict overflow control */
				#sidebar {
						transition: width 0.3s ease-in-out;
						flex-shrink: 0;
						position: relative;
						display: flex;
						flex-direction: column;
						/* overflow: hidden; */
				}

				/* Animate text fade + slide */
				.nav-text,
				.logo-text,
				.nav-item {
						transition: opacity 0.25s ease, transform 0.25s ease;
						opacity: 1;
						transform: translateX(0);
				}

				/* Navigation container overflow control */
				nav {
						/* overflow: hidden; */
						flex: 1;
						display: flex;
						flex-direction: column;
				}

				/* Hide text when collapsed with strict containment */
				.sidebar-collapsed .nav-text {
						display: none;
				}

				.sidebar-collapsed .logo-text {
						display: none;
				}

				/* Ensure nav items don't overflow */
				.nav-item,
				.sub-item {
						overflow: hidden;
						white-space: nowrap;
				}


				/* Show tooltips when collapsed */
				.tooltip {
						position: absolute;
						left: 100%;
						top: 50%;
						transform: translateY(-50%);
						margin-left: 0.5rem;
						background-color: #1f2937;
						color: white;
						padding: 0.25rem 0.5rem;
						border-radius: 0.375rem;
						font-size: 0.75rem;
						white-space: nowrap;
						opacity: 0;
						pointer-events: none;
						transition: opacity 0.2s;
						z-index: 50;
				}

				.sidebar-collapsed .nav-item:hover .tooltip,
				.sidebar-collapsed .logout-btn:hover .tooltip,
				.sidebar-collapsed .toggle-btn:hover .tooltip {
						opacity: 1;
				}

				/* Center content when collapsed with proper containment */
				.sidebar-collapsed .nav-item {
						justify-content: center;
						padding-left: 0.75rem;
						padding-right: 0.75rem;
				}

				.sidebar-collapsed .nav-item>div {
						justify-content: center;
				}

				/* Hide submenus when collapsed */
				.sidebar-collapsed .submenu {
						display: none;
				}

				/* Logo container in collapsed state */
				.sidebar-collapsed .flex.items-center.space-x-3 {
						justify-content: center;
						space-x: 0;
				}

				/* Strict width control for main container */
				/* .main-container {
								width: calc(100vw - 16rem);
								max-width: calc(100vw - 16rem);
								overflow-x: hidden;
						}

						.sidebar-collapsed~.main-container {
								width: calc(100vw - 4.5rem);
								max-width: calc(100vw - 4.5rem);
						} */

				/* Active states */
				/* Parent active but not main selection */
				/* Remove background from whole item */
				.nav-item.active {
						background: transparent;
						color: inherit;
						font-weight: 500;
				}

				/* Style only the icon when active */
				.group.active {
						background-color: #EFF6FF;
						/* light gray or your highlight color */
						color: #1D4ED8;
						/* icon stroke color */
						border-radius: 0.8rem;
						/* padding: 0px; */
						width: 11.5rem;
				}

				.sidebar-collapsed .group.active {
						width: auto;
						/* or width: 4.5rem to match collapsed sidebar */
				}

				/* Make SVG inherit the color */
				.nav-item .icon-wrapper svg {
						stroke: currentColor;
				}


				/* Child active = strong */
				.sub-item.active {
						/* background-color: rgb(239 246 255); */
						/* blue-50 */
						color: rgb(29 78 216);
						/* blue-700 */
						font-weight: 600;
				}

				/* Toggle button rotation */
				.sidebar-collapsed .toggle-btn svg {
						transform: rotate(180deg);
				}

				/* Flyout menu positioning for collapsed sidebar */
				/* .sidebar-collapsed .group:hover .flyout-menu {
								left: 4.5rem;
						} */

				/* Default flyout positioning */
				.flyout-menu {
						left: 13rem;
				}

				/* Parent active state (background + icon color + text) */


				/* Active sub-item inside collapsed flyout */
				.flyout-menu a.active {
						/* background-color: rgb(239 246 255); */
						color: rgb(29 78 216);
						font-weight: 500;
				}

				/* Flyout should always appear in collapsed mode */
				.group:hover:not(.active) .flyout-menu {
						display: block;
						left: 10rem;
				}



				.sidebar-collapsed .group:hover .flyout-menu {
						display: block;
						left: 3.25rem;
				}


				/* Loading animation */
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
				}

				/* Flex layout for nav content */
				.nav-content {
						flex: 1;
				}

				.nav-bottom {
						margin-top: auto;
				}

				/* Flyout arrow pointing back to parent */
				.flyout-menu::before {
						content: "";
						position: absolute;
						top: 1rem;
						/* adjust to align vertically with parent icon */
						left: -6px;
						/* position before the menu */
						width: 0;
						height: 0;
						border-top: 6px solid transparent;
						border-bottom: 6px solid transparent;
						border-right: 6px solid white;
						/* match flyout bg */
						z-index: 51;
				}

				/* Optional: border around arrow (to match border-gray-200) */
				.flyout-menu::after {
						content: "";
						position: absolute;
						top: 1rem;
						left: -7px;
						width: 0;
						height: 0;
						border-top: 7px solid transparent;
						border-bottom: 7px solid transparent;
						border-right: 7px solid rgb(229, 231, 235);
						/* gray-200 border */
						z-index: 50;
				}
		</style>
</head>

<body class="relative overflow-x-hidden bg-gray-100 md:flex">

		<!-- Mobile overlay -->
		<div
				id="overlay"
				class="fixed inset-0 z-20 hidden bg-black bg-opacity-50 md:hidden"
		></div>

		<div class="fixed left-0 right-0 top-0 z-40 flex h-8 items-center justify-between bg-gradient-to-r from-green-950 via-indigo-950 to-blue-950 px-4 shadow-sm">
				{{-- <div class="fixed left-0 right-0 top-0 z-40 flex h-8 items-center justify-between bg-gray-900 px-4 shadow-sm"> --}}
				<!-- Left -->
				<div class="flex items-center space-x-3">
						{{-- <span class="text-lg font-semibold text-gray-800">CMS Dashboard</span> --}}
				</div>

				<!-- Right -->
				@php
						$locationMap = [
						    'f2' => 'F2 - Metro Wholesalemart Colon',
						    's10' => 'S10 - Metro Maasin',
						    's17' => 'S17 - Metro Tacloban',
						    's19' => 'S19 - Metro Bay-Bay',
						    'f18' => 'F18 - Metro Alang-Alang',
						    'f19' => 'F19 - Metro Hilongos',
						    's8' => 'S8 - Metro Toledo',
						    'h8' => 'H8 - Super Metro Antipolo',
						    'h9' => 'H9 - Super Metro Carcar',
						    'h10' => 'H10 - Super Metro Bogo',
						];

						$userLocation = Auth::user()->user_location ?? null;
						$fullLocation = $locationMap[$userLocation] ?? $userLocation;
				@endphp

				<div class="flex items-center space-x-4">
						<span class="text-sm text-white">
								Howdy, {{ Auth::user()->name }} ({{ $fullLocation }})
						</span>
				</div>

		</div>


		<aside
				id="sidebar"
				class="sidebar-expanded fixed z-30 flex hidden flex-col bg-white shadow-xl"
		>



				{{-- <!-- Logo Section -->
				<div class="mt-12 w-auto flex-col items-center justify-center border-b border-gray-200 p-4">
						<div class="flex items-center space-x-6">
								<img
										src="{{ asset('images/MarengEms_Logo.png') }}"
										alt="Logo"
										class="h-10 w-10 flex-shrink-0 rounded-lg"
								>
								<span class="logo-text text-lg font-bold text-gray-800">ISO B2B</span>
						</div>
				</div> --}}

				<nav class="fixed flex flex-col px-3 pb-4">
						<a
								href="{{ route('dashboard') }}"
								class="block"
						>
								<div class="mt-8 w-auto flex-col items-center justify-center border-b border-gray-200 py-4">
										<div class="flex items-center space-x-6">
												<img
														src="{{ asset('images/MarengEms_Logo.png') }}"
														alt="Logo"
														class="h-10 w-10 flex-shrink-0 rounded-lg"
												>
												<span class="logo-text text-lg font-bold text-gray-800">ISO B2B</span>
										</div>
								</div>
						</a>

						<div class="nav-content">
								<ul class="space-y-1 pt-4">
										<!-- Dashboard Group -->
										<li class="{{ request()->routeIs('dashboard') ? 'active' : '' }} group relative">
												<!-- Dashboard Main Link -->
												<a
														href="{{ route('dashboard') }}"
														class="nav-item {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm"
												>
														<svg
																xmlns="http://www.w3.org/2000/svg"
																fill="none"
																viewBox="0 0 24 24"
																stroke="currentColor"
																stroke-width="2"
																class="h-5 w-5 flex-shrink-0"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z"
																/>
														</svg>
														<span class="nav-text ml-3">Dashboard</span>
														<div class="tooltip">Dashboard</div>
												</a>

												<!-- Hover Flyout Menu -->
												<ul class="flyout-menu absolute top-0 z-50 hidden w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
														<li>
																<a
																		href="{{ route('dashboard') }}"
																		class="sub-item {{ request()->routeIs('dashboard') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Dashboard
																</a>
														</li>
												</ul>
										</li>



										<!-- Orders Group -->
										<li class="{{ request()->routeIs('orders*') ? 'active' : '' }} group relative">
												@if (request()->routeIs('orders*'))
														<!-- Expanded Orders Section -->
														<div class="nav-item {{ request()->routeIs('orders*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
																<span class="icon-wrapper">
																		<svg
																				class="h-5 w-5 flex-shrink-0"
																				fill="none"
																				stroke="currentColor"
																				viewBox="0 0 24 24"
																		>
																				<path
																						stroke-linecap="round"
																						stroke-linejoin="round"
																						stroke-width="2"
																						d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"
																				/>
																		</svg>
																</span>
																<span class="nav-text ml-3 font-medium">Orders</span>
																<div class="tooltip">Orders</div>
														</div>


														<ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
																<li>
																		<a
																				href="{{ route('orders.index') }}"
																				class="sub-item {{ request()->routeIs('orders.index') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				<span class="nav-text">Sales Order List</span>
																		</a>
																</li>
																@if (preg_match('/orders\/\d+$/', request()->path()))
																		<li>
																				<a
																						href="{{ url()->current() }}"
																						class="sub-item active relative flex items-center rounded-lg px-3 py-2 text-sm"
																				>
																						<span class="nav-text">Sales Order Details</span>
																				</a>
																		</li>
																@endif
														</ul>
												@else
														<!-- Collapsed Orders with Hover Flyout -->
														<a
																href="{{ route('orders.index') }}"
																class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700"
														>
																<svg
																		class="h-5 w-5 flex-shrink-0"
																		fill="none"
																		stroke="currentColor"
																		viewBox="0 0 24 24"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 7M7 13l-2 4h13M10 17a1 1 0 11-2 0 1 1 0 012 0zm8 0a1 1 0 11-2 0 1 1 0 012 0z"
																		/>
																</svg>
																<span class="nav-text ml-3">Orders</span>
																<div class="tooltip">Orders</div>
														</a>

												@endif
												<!-- Hover Flyout Menu -->
												<ul class="flyout-menu absolute top-0 z-50 hidden w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
														<li>
																<a
																		href="{{ route('orders.index') }}"
																		class="sub-item {{ request()->routeIs('orders.index') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Sales Order List
																</a>
														</li>
														@if (preg_match('/orders\/\d+$/', request()->path()))
																<li>
																		<a
																				href="{{ url()->current() }}"
																				class="sub-item active block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				Sales Order Details
																		</a>
																</li>
														@endif
												</ul>
										</li>


										<!-- Forms Group -->
										<li class="{{ request()->routeIs('forms*') ? 'active' : '' }} group relative">
												@if (request()->routeIs('forms*'))
														<!-- Expanded Forms Section -->
														<div class="nav-item {{ request()->routeIs('forms*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
																<span class="icon-wrapper">
																		<svg
																				class="h-5 w-5 flex-shrink-0"
																				fill="none"
																				stroke="currentColor"
																				viewBox="0 0 24 24"
																		>
																				<path
																						stroke-linecap="round"
																						stroke-linejoin="round"
																						stroke-width="2"
																						d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
																				/>
																		</svg>
																</span>
																<span class="nav-text ml-3 font-medium">Forms</span>
																<div class="tooltip">Forms</div>
														</div>

														<!-- Expanded Submenu -->
														<ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
																<li>
																		<a
																				href="{{ route('forms.sof') }}"
																				class="sub-item {{ request()->routeIs('forms.sof') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				<span class="nav-text">Sales Order Form</span>
																		</a>
																</li>
																<li>
																		<a
																				href="{{ route('forms.rof') }}"
																				class="sub-item {{ request()->routeIs('forms.rof') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				<span class="nav-text">Request Order Form</span>
																		</a>
																</li>
														</ul>
												@else
														<!-- Collapsed Forms with Hover Flyout -->
														<a
																href="{{ route('forms.sof') }}"
																class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700"
														>
																<svg
																		class="h-5 w-5 flex-shrink-0"
																		fill="none"
																		stroke="currentColor"
																		viewBox="0 0 24 24"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
																		/>
																</svg>
																<span class="nav-text ml-3">Forms</span>
																<div class="tooltip">Forms</div>
														</a>
												@endif

												<!-- Hover Flyout Menu -->
												<ul class="flyout-menu absolute top-0 z-50 hidden w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
														<li>
																<a
																		href="{{ route('forms.sof') }}"
																		class="sub-item {{ request()->routeIs('forms.sof') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Sales Order Form
																</a>
														</li>
														<li>
																<a
																		href="{{ route('forms.rof') }}"
																		class="sub-item {{ request()->routeIs('forms.rof') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Request Order Form
																</a>
														</li>
												</ul>
										</li>


										<!-- Products Group -->
										<li class="{{ request()->routeIs('products*') ? 'active' : '' }} group relative">
												@if (request()->routeIs('products*'))
														<!-- Expanded Products Section -->
														<div class="nav-item {{ request()->routeIs('products*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
																<span class="icon-wrapper">
																		<svg
																				class="h-5 w-5 flex-shrink-0"
																				fill="none"
																				stroke="currentColor"
																				viewBox="0 0 24 24"
																		>
																				<path
																						stroke-linecap="round"
																						stroke-linejoin="round"
																						stroke-width="2"
																						d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
																				/>
																		</svg>
																</span>
																<span class="nav-text ml-3 font-medium">Products</span>
																<div class="tooltip">Products</div>
														</div>

														<!-- Expanded Submenu -->
														<ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
																<li>
																		<a
																				href="{{ route('products.index') }}"
																				class="sub-item {{ request()->routeIs('products.index') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				<span class="nav-text">Product List</span>
																		</a>
																</li>
																<li>
																		<a
																				href="{{ route('products.create') }}"
																				class="sub-item {{ request()->routeIs('products.create') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				<span class="nav-text">Add New Product</span>
																		</a>
																</li>
																<li>
																		<a
																				href="{{ route('products.import.show') }}"
																				class="sub-item {{ request()->routeIs('products.import.show') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																		>
																				<span class="nav-text">Import CSV</span>
																		</a>
																</li>
														</ul>
												@else
														<!-- Collapsed Products with Hover Flyout -->
														<a
																href="{{ route('products.index') }}"
																class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700"
														>
																<svg
																		class="h-5 w-5 flex-shrink-0"
																		fill="none"
																		stroke="currentColor"
																		viewBox="0 0 24 24"
																>
																		<path
																				stroke-linecap="round"
																				stroke-linejoin="round"
																				stroke-width="2"
																				d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
																		/>
																</svg>
																<span class="nav-text ml-3">Products</span>
																<div class="tooltip">Products</div>
														</a>
												@endif

												<!-- Hover Flyout Menu -->
												<ul class="flyout-menu absolute top-0 z-50 hidden w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
														<li>
																<a
																		href="{{ route('products.index') }}"
																		class="sub-item {{ request()->routeIs('products.index') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Product List
																</a>
														</li>
														<li>
																<a
																		href="{{ route('products.create') }}"
																		class="sub-item {{ request()->routeIs('products.create') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Add New Product
																</a>
														</li>
														<li>
																<a
																		href="{{ route('products.import.show') }}"
																		class="sub-item {{ request()->routeIs('products.import.show') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																>
																		Import CSV
																</a>
														</li>
												</ul>
										</li>


										</li>
										@auth
												@if (auth()->user()->role === 'super admin')
														<!-- Users Group -->
														<li class="{{ request()->routeIs('users*') ? 'active' : '' }} group relative">
																@if (request()->routeIs('users*'))
																		<!-- Expanded Users Section -->
																		<div class="nav-item {{ request()->routeIs('users*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
																				<span class="icon-wrapper">
																						<svg
																								xmlns="http://www.w3.org/2000/svg"
																								fill="none"
																								viewBox="0 0 24 24"
																								stroke="currentColor"
																								stroke-width="2"
																								stroke-linecap="round"
																								stroke-linejoin="round"
																								class="h-5 w-5 flex-shrink-0"
																						>
																								<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
																								<circle
																										cx="9"
																										cy="7"
																										r="4"
																								/>
																								<path d="M23 21v-2a4 4 0 0 0-3-3.87" />
																								<path d="M16 3.13a4 4 0 0 1 0 7.75" />
																						</svg>
																				</span>
																				<span class="nav-text ml-3">Users</span>
																				<div class="tooltip">Users</div>
																		</div>

																		<!-- Expanded Submenu -->
																		<ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
																				<li>
																						<a
																								href="{{ route('users.index') }}"
																								class="sub-item {{ request()->routeIs('users.index') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																						>
																								<span class="nav-text">User List</span>
																						</a>
																				</li>
																				@if (preg_match('/users\/\d+$/', request()->path()))
																						<li>
																								<a
																										href="{{ url()->current() }}"
																										class="sub-item active relative flex items-center rounded-lg px-3 py-2 text-sm"
																								>
																										<span class="nav-text">User Details</span>
																								</a>
																						</li>
																				@endif
																		</ul>
																@else
																		<!-- Collapsed Users with Flyout -->
																		<a
																				href="{{ route('users.index') }}"
																				class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700"
																		>
																				<svg
																						xmlns="http://www.w3.org/2000/svg"
																						fill="none"
																						viewBox="0 0 24 24"
																						stroke="currentColor"
																						stroke-width="2"
																						stroke-linecap="round"
																						stroke-linejoin="round"
																						class="h-5 w-5 flex-shrink-0"
																				>
																						<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
																						<circle
																								cx="9"
																								cy="7"
																								r="4"
																						/>
																						<path d="M23 21v-2a4 4 0 0 0-3-3.87" />
																						<path d="M16 3.13a4 4 0 0 1 0 7.75" />
																				</svg>
																				<span class="nav-text ml-3">Users</span>
																				<div class="tooltip">Users</div>
																		</a>

																		<!-- Flyout Menu -->
																		<ul class="flyout-menu absolute top-0 z-50 hidden w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
																				<li>
																						<a
																								href="{{ route('users.index') }}"
																								class="sub-item block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																						>
																								User List
																						</a>
																				</li>
																		</ul>
																@endif
														</li>

														<!-- Others Group -->
														<li class="{{ request()->routeIs('others.*') ? 'active' : '' }} group relative">
																@if (request()->routeIs('others.*'))
																		<!-- Expanded Others Section -->
																		<div class="nav-item {{ request()->routeIs('others.*') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2.5 text-sm">
																				<span class="icon-wrapper">
																						<svg
																								class="h-5 w-5 flex-shrink-0"
																								fill="none"
																								stroke="currentColor"
																								viewBox="0 0 24 24"
																						>
																								<path
																										stroke-linecap="round"
																										stroke-linejoin="round"
																										stroke-width="2"
																										d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
																								/>
																						</svg>
																				</span>
																				<span class="nav-text ml-3 font-medium">Others</span>
																				<div class="tooltip">Others</div>
																		</div>

																		<!-- Expanded Submenu -->
																		<ul class="submenu ml-5 mt-1 space-y-1 border-l border-gray-200 pl-2">
																				<li>
																						<a
																								href="{{ route('others.inventory.form') }}"
																								class="sub-item {{ request()->routeIs('others.inventory.form') ? 'active' : '' }} relative flex items-center rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																						>
																								<span class="nav-text">Inventory Form</span>
																						</a>
																				</li>
																		</ul>
																@else
																		<!-- Collapsed Others with Hover -->
																		<a
																				href="{{ route('others.inventory.form') }}"
																				class="nav-item relative flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700"
																		>
																				<svg
																						class="h-5 w-5 flex-shrink-0"
																						fill="none"
																						stroke="currentColor"
																						viewBox="0 0 24 24"
																				>
																						<path
																								stroke-linecap="round"
																								stroke-linejoin="round"
																								stroke-width="2"
																								d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
																						/>
																				</svg>
																				<span class="nav-text ml-3">Others</span>
																				<div class="tooltip">Others</div>
																		</a>
																@endif

																<!-- Hover Flyout Menu -->
																<ul class="flyout-menu absolute top-0 z-50 hidden w-56 rounded-lg border border-gray-200 bg-white shadow-lg">
																		<li>
																				<a
																						href="{{ route('others.inventory.form') }}"
																						class="sub-item {{ request()->routeIs('others.inventory.form') ? 'active' : '' }} block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600"
																				>
																						Inventory Form
																				</a>
																		</li>
																</ul>
														</li>


												@endif
										@endauth
								</ul>
						</div>

						<div class="nav-bottom fixed bottom-4 w-auto">
								<!-- Toggle Button -->
								<div class="pb-4">
										<button
												id="sidebarToggle"
												class="toggle-btn relative flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900"
										>
												<svg
														class="h-5 w-5 flex-shrink-0 transition-transform duration-300"
														fill="none"
														stroke="currentColor"
														viewBox="0 0 24 24"
												>
														<path
																stroke-linecap="round"
																stroke-linejoin="round"
																stroke-width="2"
																d="M15 19l-7-7 7-7"
														/>
												</svg>
												<span class="nav-text ml-3">Collapse Menu</span>
												<div class="tooltip">Toggle Menu</div>
										</button>
								</div>

								<!-- Logout -->
								<div class="border-t border-gray-200 pt-4">
										<form
												method="POST"
												action="{{ route('logout') }}"
										>
												@csrf
												<button
														type="submit"
														class="logout-btn nav-item relative flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700"
												>
														<svg
																class="h-5 w-5 flex-shrink-0"
																fill="none"
																stroke="currentColor"
																viewBox="0 0 24 24"
														>
																<path
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		stroke-width="2"
																		d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
																/>
														</svg>
														<span class="nav-text ml-3">Logout</span>
														<div class="tooltip">Logout</div>
												</button>
										</form>
								</div>
						</div>
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
						@php $isDashboard = url()->current() === route('dashboard'); @endphp

						<li>
								<a
										href="{{ route('dashboard') }}"
										class="{{ $isDashboard ? 'bg-gray-200 font-bold' : '' }} block rounded px-4 py-2 hover:bg-gray-100"
								>
										Dashboard
								</a>
						</li>

						<!-- Mobile Orders Group -->
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

						<!-- Mobile Forms Group -->
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

						<!-- Mobile Products Group -->
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

				<!-- Mobile Logout -->
				<form
						method="POST"
						action="{{ route('logout') }}"
						class="mt-4"
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
		<div class="main-container mt-4 min-h-screen flex-1">
				<main class="w-full overflow-x-hidden">
						@yield('content')
				</main>
		</div>

		@vite('resources/js/app.js')
		<script>
				document.addEventListener('DOMContentLoaded', function() {
						const sidebar = document.getElementById('sidebar');
						const sidebarToggle = document.getElementById('sidebarToggle');
						const overlay = document.getElementById('overlay');
						const toggleMobileMenu = document.getElementById('toggleMobileMenu');
						const mobileMenu = document.getElementById('mobileMenu');

						// Initialize sidebar state from localStorage or default to expanded
						const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
						if (isCollapsed && sidebar) {
								sidebar.classList.remove('sidebar-expanded');
								sidebar.classList.add('sidebar-collapsed');
						}

						// Update main container width when sidebar toggles
						if (sidebarToggle) {
								sidebarToggle.addEventListener('click', function() {
										const isCurrentlyCollapsed = sidebar.classList.contains('sidebar-collapsed');
										const mainContainer = document.querySelector('.main-container');

										if (isCurrentlyCollapsed) {
												sidebar.classList.remove('sidebar-collapsed');
												sidebar.classList.add('sidebar-expanded');
												localStorage.setItem('sidebarCollapsed', 'false');
										} else {
												sidebar.classList.remove('sidebar-expanded');
												sidebar.classList.add('sidebar-collapsed');
												localStorage.setItem('sidebarCollapsed', 'true');
										}
								});
						}

						// Mobile menu toggle
						if (toggleMobileMenu) {
								toggleMobileMenu.addEventListener('click', function() {
										if (mobileMenu) {
												mobileMenu.classList.toggle('hidden');
										}
										if (overlay) {
												overlay.classList.toggle('hidden');
										}
								});
						}

						// Close mobile menu when clicking overlay
						if (overlay) {
								overlay.addEventListener('click', function() {
										if (mobileMenu) {
												mobileMenu.classList.add('hidden');
										}
										overlay.classList.add('hidden');
								});
						}

						// Handle mobile section toggles
						document.querySelectorAll('[data-toggle]').forEach(btn => {
								btn.addEventListener('click', function() {
										const targetId = btn.getAttribute('data-toggle');
										const submenu = document.getElementById(targetId);
										const icon = document.querySelector(`[data-icon="${targetId}"]`);

										if (submenu) {
												submenu.classList.toggle('hidden');
										}
										if (icon) {
												icon.classList.toggle('rotate-180');
										}
								});
						});

						// Loading animation for navigation links
						document.querySelectorAll("nav a").forEach(link => {
								link.addEventListener("click", function() {
										this.classList.add("loading-bg");
										setTimeout(() => {
												this.classList.remove("loading-bg");
										}, 5000);
								});
						});

						// Handle page expired
						if (document.body.innerText.includes('Page Expired')) {
								window.location.reload();
						}

						// Handle escape key for mobile menu
						document.addEventListener('keydown', function(e) {
								if (e.key === 'Escape' && mobileMenu && !mobileMenu.classList.contains('hidden')) {
										mobileMenu.classList.add('hidden');
										if (overlay) {
												overlay.classList.add('hidden');
										}
								}
						});

						// Responsive sidebar behavior
						function handleResize() {
								if (window.innerWidth < 768) {
										if (sidebar) {
												sidebar.classList.add('hidden');
										}
								} else {
										if (sidebar) {
												sidebar.classList.remove('hidden');
										}
										if (mobileMenu) {
												mobileMenu.classList.add('hidden');
										}
										if (overlay) {
												overlay.classList.add('hidden');
										}
								}
						}

						window.addEventListener('resize', handleResize);
						handleResize(); // Initial call

						// Keyboard shortcut - Alt + S to toggle sidebar
						document.addEventListener('keydown', function(e) {
								if (e.altKey && e.key === 's') {
										e.preventDefault();
										if (sidebarToggle) {
												sidebarToggle.click();
										}
								}
						});
				});
		</script>

</body>

</html>
