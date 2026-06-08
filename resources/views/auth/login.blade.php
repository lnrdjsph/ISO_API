<x-guest-layout>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">

        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
            <div class="flex items-center justify-center">
                <img
                    src="{{ asset('images/MarengEms_Logo.png') }}"
                    alt="Logo"
                    loading="lazy"
                    width="250"
                    height="auto"
                    class="h-auto max-w-full md:w-[300px] lg:w-[300px]">
            </div>

            @if (session('status'))
                <div class="mb-4 font-medium text-green-600">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-300 bg-red-100 p-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium" for="email">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                        required
                        autofocus>
                    @error('email')
                        <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium" for="password">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                            required>
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-600 hover:text-gray-800 focus:outline-none">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <!-- Closed Eye with Downward Lashes (default) -->
                                <g id="eye-closed">
                                    <!-- Eyelid curve (lower arc, closed from top) -->
                                    <path d="M3 12 Q12 18 21 12" />
                                    <!-- Downward lashes -->
                                    <path d="M5 13 L4 16" />
                                    <path d="M8 14.5 L8 17.5" />
                                    <path d="M12 15 L12 18" />
                                    <path d="M16 14.5 L16 17.5" />
                                    <path d="M19 13 L20 16" />
                                </g>
                                <!-- Open Eye (hidden by default) -->
                                <g id="eye-open" class="hidden">
                                    <path d="M2 12 Q12 5 22 12" />
                                    <circle cx="12" cy="12" r="3" />
                                </g>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-6 flex items-center justify-between">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remember" class="mr-2">
                        Remember Me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot Password?</a>
                </div>

                <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700">
                    Login
                </button>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeClosedIcon = document.getElementById('eye-closed');
        const eyeOpenIcon = document.getElementById('eye-open');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'password') {
                eyeClosedIcon.classList.remove('hidden');
                eyeOpenIcon.classList.add('hidden');
            } else {
                eyeClosedIcon.classList.add('hidden');
                eyeOpenIcon.classList.remove('hidden');
            }
        });
    </script>
</x-guest-layout>
