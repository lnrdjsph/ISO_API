@php
    $roleColors = [
        'super admin'          => 'bg-indigo-50 border-indigo-200 text-indigo-800',
        'store admin'          => 'bg-blue-50 border-blue-200 text-blue-800',
        'store manager'        => 'bg-cyan-50 border-cyan-200 text-cyan-800',
        'store personnel'      => 'bg-green-50 border-green-200 text-green-800',
        'warehouse manager'    => 'bg-amber-50 border-amber-200 text-amber-800',
        'warehouse personnel'  => 'bg-orange-50 border-orange-200 text-orange-800',
    ];

    $initials = fn($name) => collect(explode(' ', trim($name)))
        ->filter(fn($w) => preg_match('/^[a-zA-Z]/u', $w))
        ->map(fn($w) => strtoupper($w[0]))
        ->take(2)
        ->implode('');

    $avatarColors = ['#4f46e5','#0284c7','#0891b2','#16a34a','#d97706','#ea580c','#9333ea','#db2777'];
    $avatarColor  = fn($name) => $avatarColors[crc32($name) % count($avatarColors)];
@endphp

<div class="overflow-x-auto">
    <table class="users-table w-full min-w-full text-sm">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400 whitespace-nowrap">User</th>
                <th class="px-3.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400 whitespace-nowrap">Email</th>
                <th class="px-3.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400 whitespace-nowrap">Role</th>
                <th class="px-3.5 py-2.5 text-left text-[9px] font-semibold uppercase tracking-[.07em] text-slate-400 whitespace-nowrap">Location</th>
                <th class="px-4 py-2.5 text-right text-[9px] font-semibold uppercase tracking-[.07em] text-slate-500 whitespace-nowrap">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($users as $user)
                @php
                    $role     = strtolower($user->role ?? '');
                    $badgeCls = $roleColors[$role] ?? 'bg-gray-50 border-gray-200 text-gray-700';
                    $ini      = $initials($user->name);
                    $bgClr    = $avatarColor($user->name);
                    $loc      = $regionLabels[$user->user_location] ?? ($storeLocations[$user->user_location] ?? $user->user_location);
                @endphp
                <tr class="user-row border-b border-slate-100 transition-colors duration-100 hover:bg-slate-50/60">

                    {{-- User (avatar + name) --}}
                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full"
                                style="background:{{ $bgClr }}">
                                <span class="text-[11px] font-bold tracking-[.03em] text-white">{{ $ini }}</span>
                            </div>
                            <span class="text-[13px] font-medium text-gray-900">{{ $user->name }}</span>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="px-3.5 py-3 text-xs text-gray-500">{{ $user->email }}</td>

                    {{-- Role badge (dynamic per-role colors kept as inline — cannot be static Tailwind) --}}
                    <td class="px-3.5 py-3">
                        <span class="inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $badgeCls }}">
                            {{ ucwords($user->role) }}
                        </span>
                    </td>

                    {{-- Location --}}
                    <td class="px-3.5 py-3 text-xs text-gray-700">{{ $loc }}</td>

                    {{-- Actions --}}
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <button class="edit-user-btn mr-1.5 inline-flex cursor-pointer items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-medium text-indigo-600 transition-all duration-150 hover:bg-indigo-100"
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-email="{{ $user->email }}"
                            data-role="{{ $user->role }}"
                            data-location="{{ $user->user_location }}">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </button>
                        <button class="delete-user-btn inline-flex cursor-pointer items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-600 transition-all duration-150 hover:bg-red-100"
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-delete-url="{{ route('users.destroy', $user) }}">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-2.5">
                            <div class="flex h-[52px] w-[52px] items-center justify-center rounded-full bg-slate-100">
                                <svg width="26" height="26" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 12c2.485 0 4.5-2.015 4.5-4.5S14.485 3 12 3 7.5 5.015 7.5 7.5 9.515 12 12 12zM4.5 21a7.5 7.5 0 0115 0"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-700">No users found</p>
                            <p class="text-xs text-gray-400">Try adjusting your search or filters</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination footer --}}
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white px-4 py-2.5">
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500">Rows per page:</span>
        <select id="per-page-select"
            class="h-8 appearance-none rounded-md border border-gray-300 bg-white py-0 pl-2 pr-7 text-xs text-gray-700 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 cursor-pointer">
            @foreach ([10, 15, 25, 50, 100] as $size)
                <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                    {{ $size }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        {{ $users->withQueryString()->links() }}
    </div>
</div>
