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
						content="ISO B2B2C Ordering System"
				>
				<meta
						name="theme-color"
						content="#23282d"
				/>
				<title>ISO B2B2C Ordering System</title>

				<link
						rel="icon"
						type="image/png"
						href="{{ asset('images/MarengEms_Logo.png') }}"
				>
				@vite('resources/css/app.css')

				<style>
						/* WordPress Admin Sidebar Styling */
						* {
								box-sizing: border-box;
						}

						body {
								margin: 0;
								padding: 0;
								font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
								background: #f1f1f1;
						}

						/* Sidebar Base Styles */
						#adminmenuwrap {
								position: fixed;
								top: 0;
								left: 0;
								height: 100vh;
								width: 160px;
								background: #23282d;
								color: #eee;
								z-index: 9990;
								overflow: hidden;
								transition: width 0.15s ease-in-out;
								box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
						}

						#adminmenuwrap.folded {
								width: 36px;
						}

						/* WordPress Admin Header */
						.wp-admin-bar {
								height: 46px;
								background: #23282d;
								border-bottom: 1px solid #3c434a;
								display: flex;
								align-items: center;
								padding: 0 10px;
								position: relative;
						}

						.wp-logo {
								display: flex;
								align-items: center;
								color: #00a0d2;
								text-decoration: none;
								font-size: 20px;
								font-weight: 400;
						}

						.wp-logo img {
								width: 20px;
								height: 20px;
								margin-right: 8px;
								border-radius: 3px;
						}

						.folded .wp-logo .logo-text {
								display: none;
						}

						/* Collapse Button */
						.collapse-button {
								position: absolute;
								right: 8px;
								top: 50%;
								transform: translateY(-50%);
								background: none;
								border: none;
								color: #a7aaad;
								cursor: pointer;
								padding: 4px;
								border-radius: 2px;
								font-size: 14px;
								line-height: 1;
						}

						.collapse-button:hover {
								color: #00a0d2;
						}

						.folded .collapse-button {
								transform: translateY(-50%) rotate(180deg);
						}

						/* Admin Menu */
						#adminmenu {
								list-style: none;
								margin: 0;
								padding: 0;
								background: #23282d;
								height: calc(100vh - 46px);
								overflow-y: auto;
								overflow-x: hidden;
						}

						#adminmenu::-webkit-scrollbar {
								width: 12px;
						}

						#adminmenu::-webkit-scrollbar-track {
								background: #23282d;
						}

						#adminmenu::-webkit-scrollbar-thumb {
								background: #3c434a;
								border-radius: 6px;
						}

						#adminmenu::-webkit-scrollbar-thumb:hover {
								background: #50575e;
						}

						/* Menu Items */
						.menu-top {
								position: relative;
								margin: 0;
						}

						.menu-top>a {
								display: block;
								padding: 8px;
								color: #eee;
								text-decoration: none;
								position: relative;
								border: none;
								background: none;
								cursor: pointer;
								width: 100%;
								text-align: left;
						}

						.menu-top>a:hover,
						.menu-top:hover>a {
								color: #00a0d2;
								background: #191e23;
						}

						/* Current/Active Menu Item */
						.menu-top.current>a,
						.menu-top.wp-has-current-submenu>a {
								background: #0073aa;
								color: #fff;
								font-weight: 600;
						}

						.menu-top.current>a:after,
						.menu-top.wp-has-current-submenu>a:after {
								content: "";
								position: absolute;
								top: 0;
								right: 0;
								width: 0;
								height: 100%;
								border-left: 4px solid #00a0d2;
						}

						/* Menu Icons */
						.wp-menu-image {
								float: left;
								width: 20px;
								height: 20px;
								margin: 1px 8px 0 0;
								text-align: center;
								font-size: 16px;
						}

						.wp-menu-image svg {
								width: 20px;
								height: 20px;
								fill: currentColor;
						}

						.folded .wp-menu-image {
								margin: 1px 0 0 4px;
						}

						/* Menu Text */
						.wp-menu-name {
								font-size: 14px;
								padding: 2px 0;
								white-space: nowrap;
								overflow: hidden;
						}

						.folded .wp-menu-name {
								display: none;
						}

						/* Submenu Styles */
						.wp-submenu {
								list-style: none;
								margin: 0;
								padding: 0;
								background: #32373c;
								display: none;
						}

						.wp-has-current-submenu .wp-submenu,
						.menu-top:hover .wp-submenu {
								display: block;
						}

						.wp-submenu li {
								margin: 0;
								padding: 0;
						}

						.wp-submenu a {
								display: block;
								padding: 6px 12px;
								color: #eee;
								text-decoration: none;
								font-size: 13px;
								line-height: 18px;
								border: none;
								background: none;
								position: relative;
						}

						.wp-submenu a:hover {
								color: #00a0d2;
								background: #1d2327;
						}

						.wp-submenu .current a {
								color: #fff;
								background: #1d2327;
								font-weight: 600;
						}

						/* Folded Submenu (Flyout) */
						.folded .wp-submenu {
								position: absolute;
								left: 36px;
								top: 0;
								min-width: 160px;
								background: #32373c;
								box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.4);
								z-index: 999999;
								border-radius: 3px;
								display: none;
						}

						.folded .menu-top:hover .wp-submenu {
								display: block;
						}

						.folded .wp-submenu:before {
								content: "";
								position: absolute;
								left: -4px;
								top: 15px;
								width: 0;
								height: 0;
								border-right: 4px solid #32373c;
								border-top: 4px solid transparent;
								border-bottom: 4px solid transparent;
						}

						/* Tooltip for folded state */
						.folded .menu-top>a .wp-menu-tooltip {
								position: absolute;
								left: 36px;
								top: 0;
								background: #32373c;
								color: #eee;
								padding: 6px 12px;
								border-radius: 3px;
								font-size: 12px;
								white-space: nowrap;
								box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.4);
								z-index: 999999;
								display: none;
						}

						.folded .menu-top:hover>a .wp-menu-tooltip {
								display: block;
						}

						.folded .menu-top>a .wp-menu-tooltip:before {
								content: "";
								position: absolute;
								left: -4px;
								top: 50%;
								transform: translateY(-50%);
								width: 0;
								height: 0;
								border-right: 4px solid #32373c;
								border-top: 4px solid transparent;
								border-bottom: 4px solid transparent;
						}

						/* Separator */
						.wp-menu-separator {
								height: 5px;
								padding: 0;
								background: #3c434a;
								border: none;
								margin: 0;
						}

						/* Main Content Area */
						.main-content {
								margin-left: 160px;
								/* padding: 20px; */
								min-height: 100vh;
								background: #f1f1f1;
								transition: margin-left 0.15s ease-in-out;
						}

						.folded~.main-content {
								margin-left: 36px;
						}

						/* Mobile Responsive */
						@media (max-width: 782px) {
								#adminmenuwrap {
										position: fixed;
										left: -160px;
										width: 160px;
								}

								#adminmenuwrap.mobile-open {
										left: 0;
								}

								.main-content {
										margin-left: 0;
								}

								.mobile-menu-toggle {
										display: block;
										position: fixed;
										top: 8px;
										left: 8px;
										z-index: 99999;
										background: #23282d;
										color: #eee;
										border: none;
										padding: 8px;
										border-radius: 3px;
										cursor: pointer;
								}

								.mobile-overlay {
										position: fixed;
										top: 0;
										left: 0;
										width: 100%;
										height: 100%;
										background: rgba(0, 0, 0, 0.7);
										z-index: 9980;
										display: none;
								}

								.mobile-overlay.active {
										display: block;
								}
						}

						@media (min-width: 783px) {

								.mobile-menu-toggle,
								.mobile-overlay {
										display: none !important;
								}
						}

						/* Logout Button Special Styling */
						.wp-menu-separator+.menu-top {
								margin-top: 10px;
						}

						.logout-menu>a {
								color: #f56565 !important;
						}

						.logout-menu>a:hover {
								color: #fff !important;
								background: #e53e3e !important;
						}

						/* WordPress Badge Animation */
						.wp-badge {
								position: relative;
								overflow: hidden;
						}

						.wp-badge:before {
								content: "";
								position: absolute;
								top: 0;
								left: -100%;
								width: 100%;
								height: 100%;
								background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
								transition: left 0.5s;
						}

						.wp-badge:hover:before {
								left: 100%;
						}
				</style>
		</head>


		<body>

				<!-- Mobile Menu Toggle -->
				<button
						class="mobile-menu-toggle"
						id="mobileToggle"
				>
						<svg
								width="20"
								height="20"
								viewBox="0 0 24 24"
								fill="currentColor"
						>
								<path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z" />
						</svg>
				</button>

				<!-- Mobile Overlay -->
				<div
						class="mobile-overlay"
						id="mobileOverlay"
				></div>

				<!-- WordPress Admin Sidebar -->
				<div id="adminmenuwrap">
						<!-- Admin Bar -->
						<div class="wp-admin-bar p-5">
								<a
										href="{{ route('dashboard') }}"
										class="wp-logo"
								>
										<img
												src="{{ asset('images/MarengEms_Logo.png') }}"
												alt="Logo"
										>
										<span class="logo-text">ISO B2B2C</span>
								</a>
								<button
										class="collapse-button"
										id="collapseBtn"
								>◀</button>
						</div>

						<!-- Admin Menu -->
						<ul id="adminmenu">
								<!-- Dashboard -->
								<li class="menu-top {{ request()->routeIs('dashboard') ? 'current' : '' }}">
										<a href="{{ route('dashboard') }}">
												<div class="wp-menu-image">
														<svg viewBox="0 0 24 24">
																<path d="M3 3h7v7H3V3zM14 3h7v7h-7V3zM14 14h7v7h-7v-7zM3 14h7v7H3v-7z" />
														</svg>
												</div>
												<div class="wp-menu-name">Dashboard</div>

										</a>
								</li>

								<!-- Orders -->
								<li class="menu-top {{ request()->routeIs('orders*') ? 'wp-has-current-submenu' : '' }}">
										<a href="{{ route('orders.index') }}">
												<div class="wp-menu-image">
														<svg viewBox="0 0 24 24">
																<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
														</svg>
												</div>
												<div class="wp-menu-name">Orders</div>

										</a>
										<ul class="wp-submenu">
												<li>
														<a
																href="{{ route('orders.index') }}"
																class="{{ request()->routeIs('orders.index') ? 'current' : '' }}"
														>
																Sales Order List
														</a>
												</li>
												@if (preg_match('/orders\/\d+$/', request()->path()))
														<li>
																<a
																		href="{{ url()->current() }}"
																		class="current"
																>
																		Sales Order Details
																</a>
														</li>
												@endif
										</ul>
								</li>

								<!-- Forms -->
								<li class="menu-top {{ request()->routeIs('forms*') ? 'wp-has-current-submenu' : '' }}">
										<a href="{{ route('forms.sof') }}">
												<div class="wp-menu-image">
														<svg viewBox="0 0 24 24">
																<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
														</svg>
												</div>
												<div class="wp-menu-name">Forms</div>

										</a>
										<ul class="wp-submenu">
												<li>
														<a
																href="{{ route('forms.sof') }}"
																class="{{ request()->routeIs('forms.sof') ? 'current' : '' }}"
														>
																Sales Order Form
														</a>
												</li>
												<li>
														<a
																href="{{ route('forms.rof') }}"
																class="{{ request()->routeIs('forms.rof') ? 'current' : '' }}"
														>
																Request Order Form
														</a>
												</li>
										</ul>
								</li>

								<!-- Products -->
								<li class="menu-top {{ request()->routeIs('products*') ? 'wp-has-current-submenu' : '' }}">
										<a href="{{ route('products.index') }}">
												<div class="wp-menu-image">
														<svg viewBox="0 0 24 24">
																<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
														</svg>
												</div>
												<div class="wp-menu-name">Products</div>

										</a>
										<ul class="wp-submenu">
												<li>
														<a
																href="{{ route('products.index') }}"
																class="{{ request()->routeIs('products.index') ? 'current' : '' }}"
														>
																Product List
														</a>
												</li>
												<li>
														<a
																href="{{ route('products.create') }}"
																class="{{ request()->routeIs('products.create') ? 'current' : '' }}"
														>
																Add New Product
														</a>
												</li>
												<li>
														<a
																href="{{ route('products.import.show') }}"
																class="{{ request()->routeIs('products.import.show') ? 'current' : '' }}"
														>
																Import CSV
														</a>
												</li>
										</ul>
								</li>

								@auth
										@if (auth()->user()->role === 'super admin')
												<!-- Users -->
												<li class="menu-top {{ request()->routeIs('users*') ? 'wp-has-current-submenu' : '' }}">
														<a href="{{ route('users.index') }}">
																<div class="wp-menu-image">
																		<svg viewBox="0 0 24 24">
																				<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
																				<circle
																						cx="9"
																						cy="7"
																						r="4"
																				/>
																				<path d="M23 21v-2a4 4 0 0 0-3-3.87" />
																				<path d="M16 3.13a4 4 0 0 1 0 7.75" />
																		</svg>
																</div>
																<div class="wp-menu-name">Users</div>

														</a>
														<ul class="wp-submenu">
																<li>
																		<a
																				href="{{ route('users.index') }}"
																				class="{{ request()->routeIs('users.index') ? 'current' : '' }}"
																		>
																				User List
																		</a>
																</li>
																@if (preg_match('/users\/\d+$/', request()->path()))
																		<li>
																				<a
																						href="{{ url()->current() }}"
																						class="current"
																				>
																						User Details
																				</a>
																		</li>
																@endif
														</ul>
												</li>

												<!-- Others -->
												<li class="menu-top {{ request()->routeIs('others.*') ? 'wp-has-current-submenu' : '' }}">
														<a href="{{ route('inventory.form') }}">
																<div class="wp-menu-image">
																		<svg viewBox="0 0 24 24">
																				<path
																						d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
																				/>
																		</svg>
																</div>
																<div class="wp-menu-name">Others</div>

														</a>
														<ul class="wp-submenu">
																<li>
																		<a
																				href="{{ route('inventory.form') }}"
																				class="{{ request()->routeIs('inventory.form') ? 'current' : '' }}"
																		>
																				Inventory Upload
																		</a>
																</li>
																@if (request()->routeIs('inventory.export'))
																		<li>
																				<a
																						href="{{ url()->current() }}"
																						class="current"
																				>
																						Inventory Export
																				</a>
																		</li>
																@endif
														</ul>
												</li>
										@endif
								@endauth

								<!-- Separator -->
								<li class="wp-menu-separator"></li>

								<!-- Logout -->
								<li class="menu-top logout-menu">
										<form
												method="POST"
												action="{{ route('logout') }}"
												style="margin: 0;"
										>
												@csrf
												<a
														href="#"
														onclick="this.parentElement.submit(); return false;"
												>
														<div class="wp-menu-image">
																<svg viewBox="0 0 24 24">
																		<path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
																</svg>
														</div>
														<div class="wp-menu-name">Logout</div>

												</a>
										</form>
								</li>
						</ul>
				</div>

				<!-- Main Content -->
				<div
						class="main-content"
						id="mainContent"
				>
						<main>
								@yield('content')
						</main>
				</div>

				<script>
						document.addEventListener('DOMContentLoaded', function() {
								const adminWrap = document.getElementById('adminmenuwrap');
								const collapseBtn = document.getElementById('collapseBtn');
								const mainContent = document.getElementById('mainContent');
								const mobileToggle = document.getElementById('mobileToggle');
								const mobileOverlay = document.getElementById('mobileOverlay');

								// Load saved state
								const isCollapsed = localStorage.getItem('wp-sidebar-collapsed') === 'true';
								if (isCollapsed) {
										adminWrap.classList.add('folded');
								}

								// Desktop collapse functionality
								if (collapseBtn) {
										collapseBtn.addEventListener('click', function() {
												adminWrap.classList.toggle('folded');
												const collapsed = adminWrap.classList.contains('folded');
												localStorage.setItem('wp-sidebar-collapsed', collapsed);
										});
								}

								// Mobile menu functionality
								if (mobileToggle) {
										mobileToggle.addEventListener('click', function() {
												adminWrap.classList.toggle('mobile-open');
												mobileOverlay.classList.toggle('active');
										});
								}

								if (mobileOverlay) {
										mobileOverlay.addEventListener('click', function() {
												adminWrap.classList.remove('mobile-open');
												mobileOverlay.classList.remove('active');
										});
								}

								// Handle escape key
								document.addEventListener('keydown', function(e) {
										if (e.key === 'Escape' && adminWrap.classList.contains('mobile-open')) {
												adminWrap.classList.remove('mobile-open');
												mobileOverlay.classList.remove('active');
										}
								});

								// Auto-close mobile menu on resize
								window.addEventListener('resize', function() {
										if (window.innerWidth >= 783) {
												adminWrap.classList.remove('mobile-open');
												mobileOverlay.classList.remove('active');
										}
								});

								// Keyboard shortcut (Alt + M for menu)
								document.addEventListener('keydown', function(e) {
										if (e.altKey && e.key === 'm' && window.innerWidth >= 783) {
												e.preventDefault();
												collapseBtn.click();
										}
								});

								// Prevent horizontal scrolling
								document.body.style.overflowX = 'hidden';
						});
				</script>
		</body>

</html>
