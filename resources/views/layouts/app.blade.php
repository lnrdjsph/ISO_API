
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My App</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet" />
</head>
<style>
<style>
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

</style>
<body class="bg-gray-100 md:flex relative">

    <!-- Mobile overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white sticky top-0 relative z-30 md:relative fixed transform -trangray-x-full md:trangray-x-0 transition-transform duration-300 ease-in-out">
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
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('orders.index') }}" 
                    class="block px-4 py-2 rounded hover:bg-gray-100
                    {{ request()->routeIs('orders.index') ? 'bg-gray-200 font-bold' : '' }}">
                    Orders
                    </a>
                </li>
                <li class="rounded">
                    <div class="{{ request()->is('products*') ? 'bg-gray-100  border-l-8 border-blue-600' : '' }} rounded">

                        

                        @if (request()->is('products*'))
                            <ul class="mt-1 rounded transition-all duration-300">
                                <li class="relative">
                                    <a href="{{ route('products.index') }}"
                                    class="block pl-6 py-2 hover:text-gray-400 rounded-r-md transition-all duration-300
                                    {{ request()->routeIs('products.index') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' : '' }}">
                                        Product List
                                    </a>
                                </li>
                                <li class="relative">
                                    <a href="{{ route('products.create') }}"
                                    class="block pl-6 py-2 hover:text-gray-400 rounded-r-md transition-all duration-300
                                    {{ request()->routeIs('products.create') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' : '' }}">
                                        Add New Product
                                    </a>
                                </li>
                                <li class="relative">
                                    <a href="{{ route('products.import') }}"
                                    class="block pl-6 py-2 hover:text-gray-400 rounded-r-md transition-all duration-300
                                    {{ request()->routeIs('products.import.show') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' : '' }}">
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


                <li>
                    <a href="#" 
                    class="block px-4 py-2 rounded hover:bg-gray-100
                    {{ request()->routeIs('dashboard') ? 'bg-gray-200 font-bold' : '' }}">
                    Dashboard
                    </a>
                </li>

            </ul>
        </nav>
    </aside>

     <!-- Main Content -->
    <div class="w-full md:flex-1 min-h-screen">
        <!-- Top bar with hamburger menu -->
        <div class="bg-white border-b px-6 py-4 md:hidden">
            <button id="toggleSidebar" class="p-2 rounded hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content area -->
        <main>
            @yield('content')
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        const toggleSidebarDesktop = document.getElementById('toggleSidebarDesktop');
        const mainContent = document.querySelector('.flex-1');
        
        let sidebarCollapsed = false;

        // Mobile sidebar toggle
        function openSidebar() {
            sidebar.classList.remove('-trangray-x-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebarMobile() {
            sidebar.classList.add('-trangray-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Desktop sidebar toggle
        function toggleSidebarDesktopFunc() {
            sidebarCollapsed = !sidebarCollapsed;
            
            if (sidebarCollapsed) {
                sidebar.classList.add('w-0', 'overflow-hidden');
                sidebar.classList.remove('w-64');
            } else {
                sidebar.classList.remove('w-0', 'overflow-hidden');
                sidebar.classList.add('w-64');
            }
        }

        // Event listeners
        toggleSidebar?.addEventListener('click', openSidebar);
        closeSidebar?.addEventListener('click', closeSidebarMobile);
        overlay?.addEventListener('click', closeSidebarMobile);
        toggleSidebarDesktop?.addEventListener('click', toggleSidebarDesktopFunc);

        // Close sidebar on window resize if mobile
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeSidebarMobile();
                // Reset desktop sidebar state
                if (sidebarCollapsed) {
                    sidebar.classList.remove('w-0', 'overflow-hidden');
                    sidebar.classList.add('w-64');
                    sidebarCollapsed = false;
                }
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !toggleSidebar.contains(e.target) && !sidebar.classList.contains('-trangray-x-full')) {
                    closeSidebarMobile();
                }
            }
        });
    </script>

</body>
</html>