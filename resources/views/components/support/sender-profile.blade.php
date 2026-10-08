@props([
    'user' => null,
    'role' => null,
    'compact' => false,
])

@php
    $displayName = $user?->name ?? 'Deleted user';
    $displayEmail = $user?->email ?? 'No email available';
    $roleValue = $role ?: $user?->role ?: 'user';
    $roleLabel = ucwords(str_replace(['-', '_'], ' ', (string) $roleValue));
    $avatarPath = $user?->learnerProfile?->avatar_path
        ?? $user?->instructorProfile?->profile_photo_path
        ?? $user?->profile?->avatar;
    $avatarUrl = $avatarPath ? asset('storage/'.ltrim((string) $avatarPath, '/')) : null;
    $initials = collect(preg_split('/\s+/', trim($displayName)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $initials = $initials !== '' ? $initials : 'U';
@endphp

<div data-feedback-sender-profile @class([
    'flex items-center gap-3' => $compact,
    'flex items-center gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4' => ! $compact,
])>
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" @class(['shrink-0 rounded-2xl object-cover', 'h-10 w-10' => $compact, 'h-14 w-14' => ! $compact])>
    @else
        <span aria-hidden="true" @class(['inline-flex shrink-0 items-center justify-center rounded-2xl bg-brand-100 font-bold text-brand-700', 'h-10 w-10 text-sm' => $compact, 'h-14 w-14 text-lg' => ! $compact])>{{ $initials }}</span>
    @endif
    <span class="min-w-0">
        <span class="block truncate text-sm font-semibold text-gray-900">{{ $displayName }}</span>
        <span class="block truncate text-xs text-gray-500">{{ $displayEmail }}</span>
        <span class="mt-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-700">{{ $roleLabel }}</span>
    </span>
</div>
