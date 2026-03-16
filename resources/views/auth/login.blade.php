<x-guest-layout>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">

        {{-- Login Form --}}

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
            {{-- <h2 class="mb-6 text-center text-2xl font-bold">Login</h2> --}}

            @if (session('status'))
                <div class="mb-4 font-medium text-green-600">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-300 bg-red-100 p-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('login') }}">
                @csrf


                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium"
                        for="email">Email</label>
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
                    <label
                        class="mb-1 block text-sm font-medium"
                        for="password">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                        required>
                    @error('password')
                        <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-6 flex items-center justify-between">
                    <label class="flex items-center text-sm">
                        <input
                            type="checkbox"
                            name="remember"
                            class="mr-2">
                        Remember Me
                    </label>
                    {{-- <a
            href="{{ route('password.request') }}"
            class="text-sm text-indigo-600 hover:underline">Forgot Password?</a> --}}
                </div>

                <button
                    type="submit"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700">
                    Login
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
