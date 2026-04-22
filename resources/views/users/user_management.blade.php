@extends('layouts.app')

@section('content')
    @php
        if (!auth()->user() || !auth()->user()->role) {
            return redirect()->route('login')->send();
        }

        if (auth()->user()->role !== 'super admin') {
            return redirect('/403')->send();
        }
    @endphp

    <div class="">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header Section -->
            <div class="mb-8 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="rounded-xl bg-gradient-to-br from-gray-900 to-indigo-900 p-3 shadow-lg">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-8 w-8 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 12c2.485 0 4.5-2.015 4.5-4.5S14.485 3 12 3 7.5 5.015 7.5 7.5 9.515 12 12 12zM4.5 21a7.5 7.5 0 0115 0v.75H4.5V21z" />
                        </svg>

                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                        <p class="mt-1 text-gray-600">Manage system users, roles, and permissions</p>
                    </div>
                </div>

            </div>

            <!-- Alert Messages -->
            @if (session('success'))
                <div class="animate-fade-in mb-6 rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-4 shadow-sm">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-full bg-green-100 p-1">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-green-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-green-800">Success!</h3>
                            <p class="mt-1 text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="animate-fade-in mb-6 rounded-xl border border-red-200 bg-gradient-to-r from-red-50 to-pink-50 p-4 shadow-sm">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-full bg-red-100 p-1">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-red-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            <ul class="mt-2 space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li class="flex items-center space-x-2">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-3 w-3"
                                            fill="currentColor"
                                            viewBox="0 0 8 8">
                                            <circle
                                                cx="4"
                                                cy="4"
                                                r="3" />
                                        </svg>
                                        <span>{{ $error }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <form
                    method="GET"
                    action="{{ route('users.index') }}"
                    class="flex flex-wrap items-center gap-3">

                    <!-- Search -->
                    <input
                        type="text"
                        name="search"
                        placeholder="Search name or email"
                        value="{{ request('search') }}"
                        class="w-72 rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                    <!-- Role Filter -->
                    <select
                        name="role"
                        class="rounded border border-gray-300 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Roles</option>
                        @foreach ($roles as $role)
                            <option
                                value="{{ $role }}"
                                {{ request('role') == $role ? 'selected' : '' }}>
                                {{ ucwords($role) }}
                            </option>
                        @endforeach
                    </select>


                    <!-- Location Filter -->
                    <select
                        name="user_location"
                        class="rounded border border-gray-300 py-2 focus:border-indigo-500 focus:ring-indigo-500">
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


                    <!-- Buttons -->
                    <button
                        type="submit"
                        class="rounded bg-gray-800 px-4 py-2 text-white hover:bg-indigo-700">
                        Filter
                    </button>

                    <a
                        href="{{ route('users.index') }}"
                        class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400">
                        Clear
                    </a>
                </form>

                <!-- Add User -->
                <button
                    id="openAddUserModal"
                    class="inline-flex items-center rounded bg-gray-800 px-4 py-2 font-semibold text-white shadow-md transition hover:bg-indigo-700">
                    + Add User
                </button>
            </div>


            <!-- Users Table -->
            <div class="overflow-hidden rounded-2xl border border-white/20 bg-white shadow-xl backdrop-blur-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gradient-to-r from-gray-900 to-blue-900">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-white">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">Email</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">Role</th>
                            <th class="px-4 py-3 text-left font-semibold text-white">Location</th>
                            <th class="py-3 pe-12 text-right font-semibold text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-indigo-50">
                                <td class="px-4 py-4">{{ $user->name }}</td>
                                <td class="px-4 py-4">{{ $user->email }}</td>
                                <td class="px-4 py-4 capitalize">{{ $user->role }}</td>
                                <td class="px-4 py-4">
                                    {{ $regionLabels[$user->user_location] ?? ($storeLocations[$user->user_location] ?? $user->user_location) }}
                                </td>
                                <td class="space-x-2 px-6 py-4 text-right">
                                    <button
                                        class="openEditUserModal text-blue-600 hover:underline"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}"
                                        data-location="{{ $user->user_location }}">
                                        Edit
                                    </button>
                                    <form
                                        action="{{ route('users.destroy', $user) }}"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-6 py-8 text-center text-gray-500">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div
        id="addUserModal"
        class="modal pointer-events-none invisible fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 opacity-0 transition-opacity duration-300">
        <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-xl">
            <h2 class="mb-6 text-2xl font-bold text-gray-900">Create New User</h2>
            <form
                action="{{ route('users.store') }}"
                method="POST"
                class="space-y-6">
                @csrf
                <div>
                    <label
                        for="add_name"
                        class="mb-1 block font-medium text-gray-700">Name</label>
                    <input
                        placeholder="Enter Full Name"
                        id="add_name"
                        name="name"
                        type="text"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label
                        for="add_email"
                        class="mb-1 block font-medium text-gray-700">Email</label>
                    <input
                        placeholder="Enter Email Address"
                        id="add_email"
                        name="email"
                        type="email"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label
                        for="add_password"
                        class="mb-1 block font-medium text-gray-700">Password</label>
                    <input
                        placeholder="Enter Password"
                        id="add_password"
                        name="password"
                        type="password"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label
                        for="add_password_confirmation"
                        class="mb-1 block font-medium text-gray-700">Confirm Password</label>
                    <input
                        placeholder="Confirm Password"
                        id="add_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>


                <div>
                    <label
                        for="add_role"
                        class="mb-1 block font-medium text-gray-700">Role</label>
                    <select
                        id="add_role"
                        name="role"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option
                            value=""
                            disabled
                            selected>Select Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ ucwords($role) }}</option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label
                        for="add_location"
                        class="mb-1 block font-medium text-gray-700">
                        User Location
                    </label>
                    <select
                        id="add_location"
                        name="user_location"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled selected>Select Location</option>
                        <optgroup label="Regions">
                            @foreach ($regionLabels as $regionCode => $regionName)
                                <option value="{{ $regionCode }}">Region: {{ $regionName }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Individual Stores">
                            @foreach ($storeLocations as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <button
                        type="button"
                        id="closeAddUserModal"
                        class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400">Cancel</button>
                    <button
                        type="submit"
                        class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div
        id="editUserModal"
        class="modal pointer-events-none invisible fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 opacity-0 transition-opacity duration-300">
        <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-xl">
            <h2 class="mb-6 text-2xl font-bold text-gray-900">Edit User</h2>
            <form
                id="editUserForm"
                method="POST"
                class="space-y-6">
                @csrf
                @method('PUT')
                <div>
                    <label
                        for="edit_name"
                        class="mb-1 block font-medium text-gray-700">Name</label>
                    <input
                        id="edit_name"
                        name="name"
                        type="text"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label
                        for="edit_email"
                        class="mb-1 block font-medium text-gray-700">Email</label>
                    <input
                        id="edit_email"
                        name="email"
                        type="email"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label
                        for="edit_password"
                        class="mb-1 block font-medium text-gray-700">Password <span class="text-sm font-normal text-gray-500">(leave blank to keep current)</span></label>
                    <input
                        id="edit_password"
                        name="password"
                        type="password"
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="••••••••">
                </div>

                {{-- confirm password --}}
                <div>
                    <label
                        for="edit_password_confirmation"
                        class="mb-1 block font-medium text-gray-700">Confirm Password</label>
                    <input
                        id="edit_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="••••••••">
                </div>

                <div>
                    <label
                        for="edit_role"
                        class="mb-1 block font-medium text-gray-700">Role</label>
                    <select
                        id="edit_role"
                        name="role"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500">
                        <option
                            value=""
                            disabled>Select Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ ucwords($role) }}</option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label
                        for="edit_location"
                        class="mb-1 block font-medium text-gray-700">
                        User Location
                    </label>
                    <select
                        id="edit_location"
                        name="user_location"
                        required
                        class="w-full rounded border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled>Select Location</option>
                        <optgroup label="Regions">
                            @foreach ($regionLabels as $regionCode => $regionName)
                                <option value="{{ $regionCode }}">Region: {{ $regionName }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Individual Stores">
                            @foreach ($storeLocations as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <button
                        type="button"
                        id="closeEditUserModal"
                        class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400">Cancel</button>
                    <button
                        type="submit"
                        class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Update User</button>
                </div>
            </form>
        </div>
    </div>

    {{-- @push('scripts') --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('openAddUserModal').addEventListener('click', () => {
                addUserModal.classList.add('show'); // fade in
            });

            document.getElementById('closeAddUserModal').addEventListener('click', () => {
                addUserModal.classList.remove('show'); // fade out
            });

            document.getElementById('closeEditUserModal').addEventListener('click', () => {
                editUserModal.classList.remove('show'); // fade out
            });

            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('openEditUserModal')) {
                    const button = e.target;
                    const id = button.dataset.id;
                    const name = button.dataset.name;
                    const email = button.dataset.email;
                    const role = button.dataset.role;
                    const location = button.dataset.location;

                    const form = document.getElementById('editUserForm');
                    const baseUrl = "{{ url('users') }}";
                    form.action = `${baseUrl}/${id}`;

                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_role').value = role;
                    document.getElementById('edit_location').value = location;
                    document.getElementById('edit_password').value = '';

                    editUserModal.classList.add('show'); // fade in
                }
            });

        });
    </script>
    {{-- @endpush --}}

    <style nonce="{{ $cspNonce ?? '' }}">
        .modal {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
    </style>
@endsection
