@extends('layouts.connector-app')

@section('title', 'Removed Members')
@section('page-title', 'Removed Members')

@php
    $avatarUrlFor = function ($user) {
        $path = $user?->learnerProfile?->avatar_path ?? $user?->instructorProfile?->profile_photo_path;

        return $path ? asset('storage/' . ltrim((string) $path, '/')) : null;
    };

    $removedRows = $connector->memberships->values()->map(function ($membership) use ($avatarUrlFor) {
        $user = $membership->user;

        return [
            'name' => $user?->name ?? 'Unknown user',
            'email' => $user?->email ?? 'No email',
            'avatar' => $avatarUrlFor($user),
            'initial' => strtoupper(mb_substr($user?->name ?? 'U', 0, 1)),
            'role' => $membership->role?->name ?? 'No role',
            'joined' => $membership->accepted_at?->format('M d, Y g:i A') ?? 'Unknown',
            'removed' => $membership->removed_at?->format('M d, Y g:i A') ?? 'Unknown',
        ];
    });
@endphp

@section('content')
<section class="overflow-hidden rounded-[24px] border border-gray-200 bg-white shadow-theme-xs">
    <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Archive</p>
            <h2 class="mt-1 text-xl font-bold text-gray-900">Removed Members</h2>
        </div>
        <a href="{{ route('connector.members.index', $connector) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19 8 12l7-7"/></svg>
            Active Members
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Profile</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Role</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Joined</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-gray-500">Removed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($removedRows as $member)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($member['avatar'])
                                    <img src="{{ $member['avatar'] }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-100 text-sm font-bold text-gray-700">{{ $member['initial'] }}</span>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $member['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $member['email'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $member['role'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $member['joined'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $member['removed'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No removed members.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
