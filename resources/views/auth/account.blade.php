@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8">

        {{-- Page Header --}}
        <div class="mb-6 flex items-center space-x-3">
            <div class="rounded-xl bg-indigo-100 p-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5.121 17.804A8.966 8.966 0 0112 15c2.21 0 4.236.797 5.879 2.11M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Account</h1>
                <p class="text-sm text-gray-500">Manage your profile and password</p>
            </div>
        </div>

        {{-- ── Profile Info Card ── --}}
        <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center space-x-2">
                    <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                    <h2 class="text-sm font-semibold text-gray-700">Profile Information</h2>
                </div>
                @if (session('success_profile'))
                    <span class="inline-flex items-center space-x-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success_profile') }}</span>
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('account.update') }}" class="space-y-5 px-6 py-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- Name --}}
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="name"
                            value="{{ old('name', $user->name) }}"
                            class="@error('name') border-red-400 bg-red-50 @else border-gray-300 @enderror w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            required>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="email"
                            value="{{ old('email', $user->email) }}"
                            class="@error('email') border-red-400 bg-red-50 @else border-gray-300 @enderror w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            required>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role (read-only) --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Role
                            <span class="ml-1 text-xs font-normal text-gray-400">(managed by admin)</span>
                        </label>
                        <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5">
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-indigo-700">
                                {{ $user->role }}
                            </span>
                        </div>
                    </div>

                    {{-- Location (read-only) --}}
                    <div>
                        @php
                            $locationMap = [
                                'lz' => 'LZ - Luzon',
                                'vs' => 'VS - Visayas',
                                '4002' => 'F2 - Metro Wholesalemart Colon',
                                '2010' => 'S10 - Metro Maasin',
                                '2017' => 'S17 - Metro Tacloban',
                                '2019' => 'S19 - Metro Bay-Bay',
                                '3018' => 'F18 - Metro Alang-Alang',
                                '3019' => 'F19 - Metro Hilongos',
                                '2008' => 'S8 - Metro Toledo',
                                '6012' => 'H8 - Super Metro Antipolo',
                                '6009' => 'H9 - Super Metro Carcar',
                                '6010' => 'H10 - Super Metro Bogo',
                            ];
                        @endphp
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                            Store Location
                            <span class="ml-1 text-xs font-normal text-gray-400">(managed by admin)</span>
                        </label>
                        <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600">
                            {{ $locationMap[strtolower($user->user_location)] ?? strtoupper($user->user_location) }}
                        </div>
                    </div>

                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Change Password Card ── --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">

            {{-- Card Header — no onclick, use id for JS binding --}}
            <button type="button" id="togglePasswordBtn"
                class="flex w-full items-center justify-between border-b border-gray-100 px-6 py-4 text-left">
                <div class="flex items-center space-x-2">
                    <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                    <h2 class="text-sm font-semibold text-gray-700">Change Password</h2>
                </div>
                <div class="flex items-center space-x-3">
                    @if (session('success_password'))
                        <span class="inline-flex items-center space-x-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ session('success_password') }}</span>
                        </span>
                    @endif
                    <svg id="password-chevron" xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 text-gray-400 transition-transform duration-200"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>

            <div id="password-section"
                class="{{ session('open_password') || session('success_password') || $errors->has('current_password') || $errors->has('password') ? '' : 'hidden' }}">
                <form method="POST" action="{{ route('account.password') }}" class="space-y-5 px-6 py-6">
                    @csrf
                    @method('PUT')

                    {{-- Current Password --}}
                    <div>
                        <label for="current_password" class="mb-1.5 block text-sm font-medium text-gray-700">
                            Current Password
                        </label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password"
                                class="@error('current_password') border-red-400 bg-red-50 @else border-gray-300 @enderror w-full rounded-lg border px-4 py-2.5 pr-10 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                placeholder="Enter current password" autocomplete="current-password">
                            {{-- data-target replaces onclick --}}
                            <button type="button" data-toggle-vis="current_password"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        {{-- New Password --}}
                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">
                                New Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password"
                                    class="@error('password') border-red-400 bg-red-50 @else border-gray-300 @enderror w-full rounded-lg border px-4 py-2.5 pr-10 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                    placeholder="Minimum 6 characters" autocomplete="new-password">
                                <button type="button" data-toggle-vis="password"
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-2">
                                <div class="h-1.5 w-full rounded-full bg-gray-200">
                                    <div id="strength-bar" class="h-1.5 rounded-full bg-gray-300 transition-all duration-300" style="width:0%"></div>
                                </div>
                                <p id="strength-label" class="mt-1 text-xs text-gray-400">Enter a password</p>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-10 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                    placeholder="Re-enter new password" autocomplete="new-password">
                                <button type="button" data-toggle-vis="password_confirmation"
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p id="match-label" class="mt-1 hidden text-xs text-gray-400"></p>
                        </div>

                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="rounded-lg bg-yellow-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {

            // ── Accordion toggle ──────────────────────────────────────────
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const passwordSection = document.getElementById('password-section');
            const chevron = document.getElementById('password-chevron');

            // Set chevron state on load
            if (passwordSection && !passwordSection.classList.contains('hidden')) {
                chevron.style.transform = 'rotate(180deg)';
            }

            toggleBtn.addEventListener('click', function() {
                const isHidden = passwordSection.classList.contains('hidden');
                passwordSection.classList.toggle('hidden', !isHidden);
                chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            });

            // ── Show/hide password via data-toggle-vis ────────────────────
            document.querySelectorAll('[data-toggle-vis]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const input = document.getElementById(btn.dataset.toggleVis);
                    if (input) input.type = input.type === 'password' ? 'text' : 'password';
                });
            });

            // ── Strength meter ────────────────────────────────────────────
            const pwInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const strengthBar = document.getElementById('strength-bar');
            const strengthLbl = document.getElementById('strength-label');
            const matchLbl = document.getElementById('match-label');

            const levels = [{
                    w: '0%',
                    c: 'bg-gray-300',
                    l: 'Enter a password',
                    lc: 'text-gray-400'
                },
                {
                    w: '25%',
                    c: 'bg-red-400',
                    l: 'Weak',
                    lc: 'text-red-500'
                },
                {
                    w: '50%',
                    c: 'bg-yellow-400',
                    l: 'Fair',
                    lc: 'text-yellow-600'
                },
                {
                    w: '75%',
                    c: 'bg-blue-400',
                    l: 'Good',
                    lc: 'text-blue-600'
                },
                {
                    w: '90%',
                    c: 'bg-green-400',
                    l: 'Strong',
                    lc: 'text-green-600'
                },
                {
                    w: '100%',
                    c: 'bg-green-500',
                    l: 'Very Strong',
                    lc: 'text-green-700'
                },
            ];

            pwInput.addEventListener('input', function() {
                const v = this.value;
                let score = 0;
                if (v.length >= 6) score++;
                if (v.length >= 10) score++;
                if (/[A-Z]/.test(v)) score++;
                if (/[0-9]/.test(v)) score++;
                if (/[^A-Za-z0-9]/.test(v)) score++;

                const lvl = v.length === 0 ? levels[0] : levels[Math.min(score, 5)];
                strengthBar.style.width = lvl.w;
                strengthBar.className = `h-1.5 rounded-full transition-all duration-300 ${lvl.c}`;
                strengthLbl.textContent = lvl.l;
                strengthLbl.className = `mt-1 text-xs ${lvl.lc}`;
                checkMatch();
            });

            confirmInput.addEventListener('input', checkMatch);

            function checkMatch() {
                const pw = pwInput.value;
                const cpw = confirmInput.value;
                if (!cpw) {
                    matchLbl.classList.add('hidden');
                    return;
                }
                matchLbl.classList.remove('hidden');
                if (pw === cpw) {
                    matchLbl.textContent = '✓ Passwords match';
                    matchLbl.className = 'mt-1 text-xs text-green-600';
                } else {
                    matchLbl.textContent = '✗ Passwords do not match';
                    matchLbl.className = 'mt-1 text-xs text-red-500';
                }
            }

        });
    </script>
@endsection
