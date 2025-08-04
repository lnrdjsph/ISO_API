
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My App</title>
    {{-- <link href="{{ mix('css/app.css') }}" rel="stylesheet" /> --}}
    @vite('resources/css/app.css')

</head>
{{-- <style>
aside a {
    position: relative;
    text-decoration: none;
    display: inline-block; /* Makes ::after match the text width */
}

aside a::after {
    content: '';
    position: absolute;
    bottom: 1px;
    left: 0;
    height: 2px;
    width: 97.5%;
    background-image: linear-gradient(to right, #2563eb, #4f46e5); /* Tailwind: from-blue-600 to-indigo-600 */
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.2s ease-in-out;
}


aside a:hover::after {
    transform: scaleX(1);
}

</style> --}}
<body class="bg-gray-100 md:flex relative">

    <!-- Mobile overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="hidden md:block w-64 bg-white sticky top-0 h-screen z-30">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold">ISO B2B</h2>
            <!-- Close button for mobile -->
            <button id="closeSidebar" class="md:hidden p-1 rounded hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <nav class="p-4">
            <ul class="space-y-2    ">
                {{-- Dashboard --}}
                <li>
                    <a href="{{ route('dashboard') }}" 
                    class="block px-4 py-2 rounded hover:bg-gray-100
                    {{ request()->routeIs('') ? 'bg-gray-200 font-bold' : '' }}">
                        Dashboard
                    </a>
                </li>
                
                {{-- Orders Group --}}
                <li class="rounded">
                    <div class="{{ request()->routeIs('orders*') ? 'bg-gray-100' : '' }} rounded">
                        @if (request()->routeIs('orders*'))
                            <h3 class="px-4 py-1 text-xs text-gray-500 uppercase tracking-wider">Orders</h3>
                            <ul class="mt-1 rounded transition-all duration-300">
                                <li class="relative">
                                    <a href="{{ route('orders.index') }}"
                                    class="block pl-6 py-2 hover:text-indigo-500 rounded transition-all duration-300 relative
                                    {{ request()->routeIs('orders.index') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }}">
                                        Sales Order List
                                    </a>
                                </li>
                                @if (preg_match('/orders\/\d+$/', request()->path()))
                                <li class="relative">
                                    <a href="{{ url()->current() }}"
                                        class="block pl-6 py-2 hover:text-indigo-500 rounded transition-all duration-300 relative
                                        before:content-[''] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium">
                                        Sales Order Details
                                    </a>
                                </li>
                                @endif
                            </ul>

                        @else
                            <a href="{{ route('orders.index') }}" 
                            class="block px-4 py-2 rounded hover:bg-gray-100">
                                Orders
                            </a>
                        @endif
                    </div>
                </li>

                {{-- Forms Group --}}
                <li class="rounded">
                    <div class="{{ request()->routeIs('forms*') ? 'bg-gray-100' : '' }} rounded">
                        @if (request()->routeIs('forms*'))
                            <h3 class="px-4 py-1 text-xs text-gray-500 uppercase tracking-wider">Forms</h3>
                            <ul class="mt-1 rounded transition-all duration-300">
                                <li class="relative">
                                    <a href="{{ route('forms.sof') }}"
                                    class="block pl-6 py-2 hover:text-indigo-500 rounded-md transition-all duration-300 relative
                                    {{ request()->routeIs('forms.sof') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }}">
                                        Sales Order Form
                                    </a>
                                </li>
                                <li class="relative">
                                    <a href="{{ route('forms.rof') }}"
                                    class="block pl-6 py-2 hover:text-indigo-500 rounded-md transition-all duration-300 relative
                                    {{ request()->routeIs('forms.rof') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }}">
                                        Request Order Form
                                    </a>
                                </li>    
                            </ul>
                        @else
                            <a href="{{ route('forms.sof') }}" 
                            class="block px-4 py-2 rounded hover:bg-gray-100">
                                Forms
                            </a>
                        @endif
                        

                    </div>
                </li>

                {{-- Products Group --}}
                <li class="rounded">
                    <div class="{{ request()->routeIs('products*') ? 'bg-gray-100' : '' }} rounded">
                        @if (request()->routeIs('products*'))
                            <h3 class="px-4 py-1 text-xs text-gray-500 uppercase tracking-wider">Products</h3>
                            <ul class="mt-1 rounded transition-all duration-300">
                                <li class="relative">
                                    <a href="{{ route('products.index') }}"
                                    class="block pl-6 py-2 hover:text-indigo-500 rounded-md transition-all duration-300 relative
                                    {{ request()->routeIs('products.index') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }}">
                                        Product List
                                    </a>
                                </li>
                                <li class="relative">
                                    <a href="{{ route('products.create') }}"
                                    class="block pl-6 py-2 hover:text-indigo-500 rounded-md transition-all duration-300 relative
                                    {{ request()->routeIs('products.create') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }}">
                                        Add New Product
                                    </a>
                                </li>
                                <li class="relative">
                                    <a href="{{ route('products.import.show') }}"
                                    class="block pl-6 py-2 hover:text-indigo-500 rounded-md transition-all duration-300 relative
                                    {{ request()->routeIs('products.import.show') ? 'before:content-[\'\'] before:absolute before:left-2 before:top-1/2 before:-translate-y-1/2 before:w-2 before:h-2 before:rounded-full before:bg-blue-600 text-blue-800 font-medium' : '' }}">
                                        Import CSV
                                    </a>
                                </li>
                            </ul>
                        @else
                            <a href="{{ route('products.index') }}" 
                            class="block px-4 py-2 rounded hover:bg-gray-100">
                                Products
                            </a>
                        @endif
                    </div>
                </li>



            </ul>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="absolute bottom-6 left-0 w-full px-4">
                @csrf
                <button type="submit" class="w-full text-left py-2 rounded hover:bg-red-100 text-red-600 font-medium text-center">
                    Logout
                </button>
            </form>
        </nav>
    </aside>

    <!-- Mobile Header -->
    <header class="block md:hidden bg-white border-b px-6 py-4 flex justify-between items-center z-30 relative">
        <div class="flex items-center space-x-4">
            <button id="toggleMobileMenu" class="p-2 rounded hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h2 class="text-lg font-semibold">ISO B2B</h2>
        </div>
    </header>

    <!-- Mobile Nav -->
    <nav id="mobileMenu" class="hidden md:hidden bg-white p-4 border-b">
        <ul class="space-y-2">
            {{-- Dashboard --}}
            <li>
                <a href="{{ route('dashboard') }}"
                    class="block px-4 py-2 rounded hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-200 font-bold' : '' }}">
                    Dashboard
                </a>
            </li>
            <!-- Orders Group -->
            @php $isOrders = request()->routeIs('orders*'); @endphp
            <li class="rounded {{ $isOrders ? 'bg-gray-100' : '' }}">
                <button
                    class="w-full text-left px-4 py-2 rounded hover:bg-gray-100 flex justify-between items-center {{ $isOrders ? 'bg-gray-100' : '' }}"
                    data-toggle="mobile-orders">
                    Orders
                    <svg class="w-4 h-4 transform transition-transform {{ $isOrders ? 'rotate-180' : '' }}"
                        data-icon="mobile-orders" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul id="mobile-orders" class="{{ $isOrders ? '' : 'hidden' }} mt-1 rounded transition-all duration-300">
                    <li>
                        <a href="{{ route('orders.index') }}"
                            class="block pl-6 py-2 hover:text-indigo-500 {{ request()->routeIs('orders.index') ? 'text-blue-800 font-medium' : '' }}">
                            Sales Order List
                        </a>
                    </li>
                    @if (preg_match('/orders\/\d+$/', request()->path()))
                        <li>
                            <a href="{{ url()->current() }}"
                                class="block pl-6 py-2 hover:text-indigo-500 text-blue-800 font-medium">
                                Sales Order Details
                            </a>
                        </li>
                    @endif
                </ul>
            </li>

            <!-- Forms Group -->
            @php $isForms = request()->routeIs('forms*'); @endphp
            <li class="rounded {{ $isForms ? 'bg-gray-100' : '' }}">
                <button
                    class="w-full text-left px-4 py-2 rounded hover:bg-gray-100 flex justify-between items-center"
                    data-toggle="mobile-forms">
                    Forms
                    <svg class="w-4 h-4 transform transition-transform {{ $isForms ? 'rotate-180' : '' }}"
                        data-icon="mobile-forms" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul id="mobile-forms" class="{{ $isForms ? '' : 'hidden' }} mt-1 rounded transition-all duration-300">
                    <li>
                        <a href="{{ route('forms.sof') }}"
                            class="block pl-6 py-2 hover:text-indigo-500 {{ request()->routeIs('forms.sof') ? 'text-blue-800 font-medium' : '' }}">
                            Sales Order Form
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('forms.rof') }}"
                            class="block pl-6 py-2 hover:text-indigo-500 {{ request()->routeIs('forms.rof') ? 'text-blue-800 font-medium' : '' }}">
                            Request Order Form
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Products Group -->
            @php $isProducts = request()->routeIs('products*'); @endphp
            <li class="rounded {{ $isProducts ? 'bg-gray-100' : '' }}">
                <button
                    class="w-full text-left px-4 py-2 rounded hover:bg-gray-100 flex justify-between items-center"
                    data-toggle="mobile-products">
                    Products
                    <svg class="w-4 h-4 transform transition-transform {{ $isProducts ? 'rotate-180' : '' }}"
                        data-icon="mobile-products" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <ul id="mobile-products" class="{{ $isProducts ? '' : 'hidden' }} mt-1 rounded transition-all duration-300">
                    <li>
                        <a href="{{ route('products.index') }}"
                            class="block pl-6 py-2 hover:text-indigo-500 {{ request()->routeIs('products.index') ? 'text-blue-800 font-medium' : '' }}">
                            Product List
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.create') }}"
                            class="block pl-6 py-2 hover:text-indigo-500 {{ request()->routeIs('products.create') ? 'text-blue-800 font-medium' : '' }}">
                            Add New Product
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.import.show') }}"
                            class="block pl-6 py-2 hover:text-indigo-500 {{ request()->routeIs('products.import.show') ? 'text-blue-800 font-medium' : '' }}">
                            Import CSV
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}" class="absolute bottom-6 left-0 w-full px-4">
            @csrf
            <button type="submit" class="w-full text-left px-4 py-2 rounded hover:bg-red-100 text-red-600 font-medium">
                Logout
            </button>
        </form>
    </nav>


     <!-- Main Content -->
    <div class="w-full md:flex-1 min-h-screen">
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
</script>

</body>
</html>