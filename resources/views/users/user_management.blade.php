@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="rounded-xl bg-gradient-to-br from-slate-800 to-indigo-900 p-3 shadow-lg">
                <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 sm:text-3xl">User Management</h1>
                <p class="text-xs text-gray-500 sm:text-sm">Manage system users, roles, and permissions</p>
            </div>
        </div>
        <button id="openAddUserModal"
            class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add User
        </button>
    </div>

    {{-- Filter bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Search --}}
            <div class="relative min-w-0 flex-1 sm:max-w-xs">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="user-search" value="{{ request('search') }}"
                    placeholder="Search name or email…"
                    class="h-8 w-full rounded-md border border-gray-300 pl-9 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:h-9" />
                <button type="button" id="clear-search-btn"
                    class="{{ request('search') ? '' : 'hidden' }} absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Role filter --}}
            <select id="role-filter"
                class="h-8 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                <option value="">All Roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                        {{ ucwords($role) }}
                    </option>
                @endforeach
            </select>

            {{-- Location filter --}}
            <select id="location-filter"
                class="h-8 rounded-md border border-gray-300 px-2 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 sm:h-9">
                <option value="">All Locations</option>
                <optgroup label="Regions">
                    @foreach ($regionLabels as $regionCode => $regionName)
                        <option value="{{ $regionCode }}" {{ request('user_location') == $regionCode ? 'selected' : '' }}>
                            Region: {{ $regionName }}
                        </option>
                    @endforeach
                </optgroup>
                <optgroup label="Individual Stores">
                    @foreach ($storeLocations as $code => $label)
                        <option value="{{ $code }}" {{ request('user_location') == $code ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </optgroup>
            </select>

            <button type="button" id="clear-filters-btn"
                class="h-8 rounded-md border border-gray-300 bg-white px-3 text-xs font-medium text-gray-600 transition hover:bg-gray-50 sm:h-9">
                Clear
            </button>
        </div>
    </div>

    {{-- Table card --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div id="users-table-wrapper" class="relative">
            <div id="users-loading"
                class="absolute inset-0 z-20 hidden items-center justify-center bg-white/70 backdrop-blur-[1px]">
                <div class="h-7 w-7 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
            </div>
            <div id="users-table-container">
                @include('users.partials.table')
            </div>
        </div>
    </div>

</div>

{{-- ── Add User Modal ── --}}
<div id="addUserModal" class="user-modal fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Create New User</h2>
            <button class="modal-close rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="addUserForm" class="space-y-4 px-6 py-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    @include('users.partials.form-field', ['id' => 'add_name', 'name' => 'name', 'label' => 'Full Name', 'type' => 'text', 'placeholder' => 'Enter full name', 'required' => true])
                </div>
                <div class="col-span-2">
                    @include('users.partials.form-field', ['id' => 'add_email', 'name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'placeholder' => 'user@example.com', 'required' => true])
                </div>
                <div>
                    @include('users.partials.form-field', ['id' => 'add_password', 'name' => 'password', 'label' => 'Password', 'type' => 'password', 'placeholder' => '••••••••', 'required' => true])
                </div>
                <div>
                    @include('users.partials.form-field', ['id' => 'add_password_confirmation', 'name' => 'password_confirmation', 'label' => 'Confirm Password', 'type' => 'password', 'placeholder' => '••••••••', 'required' => true])
                </div>
                <div>
                    @include('users.partials.form-select', ['id' => 'add_role', 'name' => 'role', 'label' => 'Role', 'placeholder' => 'Select role', 'options' => $roles, 'required' => true])
                </div>
                <div>
                    @include('users.partials.form-location', ['id' => 'add_location', 'name' => 'user_location', 'label' => 'Location'])
                </div>
            </div>
            <div id="add-form-errors" class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700"></div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" class="modal-close h-9 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="add-submit-btn"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit User Modal ── --}}
<div id="editUserModal" class="user-modal fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Edit User</h2>
            <button class="modal-close rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="editUserForm" class="space-y-4 px-6 py-5">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_user_id">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    @include('users.partials.form-field', ['id' => 'edit_name', 'name' => 'name', 'label' => 'Full Name', 'type' => 'text', 'placeholder' => 'Enter full name', 'required' => true])
                </div>
                <div class="col-span-2">
                    @include('users.partials.form-field', ['id' => 'edit_email', 'name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'placeholder' => 'user@example.com', 'required' => true])
                </div>
                <div>
                    @include('users.partials.form-field', ['id' => 'edit_password', 'name' => 'password', 'label' => 'New Password', 'type' => 'password', 'placeholder' => 'Leave blank to keep current', 'required' => false])
                </div>
                <div>
                    @include('users.partials.form-field', ['id' => 'edit_password_confirmation', 'name' => 'password_confirmation', 'label' => 'Confirm Password', 'type' => 'password', 'placeholder' => '••••••••', 'required' => false])
                </div>
                <div>
                    @include('users.partials.form-select', ['id' => 'edit_role', 'name' => 'role', 'label' => 'Role', 'placeholder' => 'Select role', 'options' => $roles, 'required' => true])
                </div>
                <div>
                    @include('users.partials.form-location', ['id' => 'edit_location', 'name' => 'user_location', 'label' => 'Location'])
                </div>
            </div>
            <div id="edit-form-errors" class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700"></div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" class="modal-close h-9 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="edit-submit-btn"
                    class="inline-flex h-9 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Config for JS --}}
<script nonce="{{ $cspNonce ?? '' }}">
    window.userMgmtConfig = {
        indexUrl:  @json(route('users.index')),
        storeUrl:  @json(route('users.store')),
        usersBase: @json(url('users')),
        csrfToken: @json(csrf_token()),
    };
</script>

@vite(['resources/js/pages/users/index.js'])
@endsection
