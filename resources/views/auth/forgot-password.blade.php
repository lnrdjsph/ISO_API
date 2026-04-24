<x-guest-layout>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">

      <div class="flex items-center justify-center mb-6">
        <img src="{{ asset('images/MarengEms_Logo.png') }}" alt="Logo"
             width="200" height="auto" class="h-auto max-w-full">
      </div>

      <h2 class="mb-2 text-center text-xl font-bold text-gray-800">Forgot Password?</h2>
      <p class="mb-6 text-center text-sm text-gray-500">
        Enter your email and we'll send you a reset link.
      </p>

      @if (session('status'))
        <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-3 text-sm text-green-700">
          {{ session('status') }}
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-gray-700" for="email">
            Email Address
          </label>
          <input type="email" name="email" id="email"
                 value="{{ old('email') }}"
                 class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                 required autofocus>
          @error('email')
            <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
          @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700">
          Send Reset Link
        </button>

        <div class="mt-4 text-center">
          <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:underline">
            ← Back to Login
          </a>
        </div>
      </form>

    </div>
  </div>
</x-guest-layout>