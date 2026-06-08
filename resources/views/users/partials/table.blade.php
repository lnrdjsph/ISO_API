@php
    $roleColors = [
        'super admin'          => ['bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#3730a3'],
        'store admin'          => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e40af'],
        'store manager'        => ['bg' => '#ecfeff', 'border' => '#a5f3fc', 'text' => '#155e75'],
        'store personnel'      => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#166534'],
        'warehouse manager'    => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e'],
        'warehouse personnel'  => ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#9a3412'],
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
        <thead style="background:#1e293b">
            <tr>
                <th style="padding:11px 16px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">User</th>
                <th style="padding:11px 14px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Email</th>
                <th style="padding:11px 14px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Role</th>
                <th style="padding:11px 14px;text-align:left;font-size:9px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Location</th>
                <th style="padding:11px 16px;text-align:right;font-size:9px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Actions</th>
            </tr>
        </thead>
        <tbody style="divide-y divide-gray-100">
            @forelse ($users as $user)
                @php
                    $role   = strtolower($user->role ?? '');
                    $colors = $roleColors[$role] ?? ['bg' => '#f9fafb', 'border' => '#e5e7eb', 'text' => '#374151'];
                    $ini    = $initials($user->name);
                    $bgClr  = $avatarColor($user->name);
                    $loc    = $regionLabels[$user->user_location] ?? ($storeLocations[$user->user_location] ?? $user->user_location);
                @endphp
                <tr class="user-row" style="border-bottom:1px solid #f1f5f9;transition:background .1s"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">

                    {{-- User (avatar + name) --}}
                    <td style="padding:12px 16px;white-space:nowrap">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:50%;background:{{ $bgClr }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <span style="font-size:11px;font-weight:700;color:#fff;letter-spacing:.03em">{{ $ini }}</span>
                            </div>
                            <span style="font-size:13px;font-weight:500;color:#111827">{{ $user->name }}</span>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td style="padding:12px 14px;font-size:12px;color:#6b7280">{{ $user->email }}</td>

                    {{-- Role badge --}}
                    <td style="padding:12px 14px">
                        <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:3px 10px;font-size:11px;font-weight:600;background:{{ $colors['bg'] }};border:1px solid {{ $colors['border'] }};color:{{ $colors['text'] }};white-space:nowrap">
                            {{ ucwords($user->role) }}
                        </span>
                    </td>

                    {{-- Location --}}
                    <td style="padding:12px 14px;font-size:12px;color:#374151">{{ $loc }}</td>

                    {{-- Actions --}}
                    <td style="padding:12px 16px;text-align:right;white-space:nowrap">
                        <button class="edit-user-btn"
                            style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:11px;font-weight:500;color:#4f46e5;border:1px solid #c7d2fe;border-radius:6px;background:#eef2ff;transition:all .15s;cursor:pointer;margin-right:6px"
                            onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'"
                            data-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-email="{{ $user->email }}"
                            data-role="{{ $user->role }}"
                            data-location="{{ $user->user_location }}">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </button>
                        <button class="delete-user-btn"
                            style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;font-size:11px;font-weight:500;color:#dc2626;border:1px solid #fca5a5;border-radius:6px;background:#fef2f2;transition:all .15s;cursor:pointer"
                            onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'"
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
                    <td colspan="5" style="padding:60px 24px;text-align:center">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
                            <div style="width:52px;height:52px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                                <svg width="26" height="26" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 12c2.485 0 4.5-2.015 4.5-4.5S14.485 3 12 3 7.5 5.015 7.5 7.5 9.515 12 12 12zM4.5 21a7.5 7.5 0 0115 0"/></svg>
                            </div>
                            <p style="font-size:14px;font-weight:500;color:#374151">No users found</p>
                            <p style="font-size:12px;color:#9ca3af">Try adjusting your search or filters</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination footer --}}
<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:10px 16px;border-top:1px solid #f1f5f9;background:#fff">
    <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;color:#6b7280">Rows per page:</span>
        <select id="per-page-select"
            style="-webkit-appearance:none;-moz-appearance:none;appearance:none;height:32px;border:1px solid #d1d5db;border-radius:6px;padding:0 28px 0 8px;font-size:12px;color:#374151;background:#fff url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E\") right 6px center / 1.1em no-repeat;cursor:pointer">
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
