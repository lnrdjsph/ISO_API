<x-guest-layout>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">

      <div class="flex items-center justify-center mb-6">
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
                 class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                 required>
          @error('email')
            <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
          @enderror
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-gray-700" for="password">New Password</label>
          <input type="password" name="password" id="password"
                 class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                 required>
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
</x-guest-layout>