<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-4">
        

        {{-- Logo --}}
        <div class="mb-6">
            <img src="{{ asset('images/MarengEms_Logo.png') }}" alt="Logo" class="h-[300px] w-auto mx-auto">
        </div>


        {{-- Login Form --}}
        
        <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>

            @if (session('status'))
                <div class="mb-4 text-green-600 font-medium">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf


                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1" for="email">Email</label>
                    <input type="email" name="email" id="email" class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-300" required autofocus>
                    @error('email')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1" for="password">Password</label>
                    <input type="password" name="password" id="password" class="w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:border-indigo-300" required>
                    @error('password')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remember" class="mr-2">
                        Remember Me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot Password?</a>
                </div>

                <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition">
                    Login
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
