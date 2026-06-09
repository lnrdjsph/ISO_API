<x-guest-layout>
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">

            <div class="mb-6 flex items-center justify-center">
                <img src="{{ asset('images/MarengEms_Logo.png') }}" alt="Logo"
                    width="200" height="auto" class="h-auto max-w-full">
            </div>

            <h2 class="mb-6 text-center text-xl font-bold text-gray-800">Set New Password</h2>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
                    <input type="email" name="email" id="email"
                        value="{{ old('email', $email) }}"
                        readonly
                        class="w-full cursor-not-allowed rounded-md border-gray-300 bg-gray-50 shadow-sm"
                        required>
                    @error('email')
                        <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="password">New Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password"
                            class="w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                            required>
                        <button type="button" id="toggle-password-btn"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                            <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="password_confirmation">
                        Confirm New Password
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                        required>
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700">
                    Reset Password
                </button>
            </form>

        </div>
    </div>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.getElementById('toggle-password-btn').addEventListener('click', function () {
            var field = document.getElementById('password');
            field.type = field.type === 'password' ? 'text' : 'password';
        });
    </script>
</x-guest-layout>
