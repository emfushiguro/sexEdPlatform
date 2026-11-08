@extends('layouts.instructor-app')

@section('title', 'Speaker Invitations')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Seminars</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Speaker Invitations</h1>
        </div>
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ $search }}" placeholder="Search invitations..." class="text-sm border-gray-300 rounded-xl">
            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-brand-700">Search</button>
        </form>
    </div>

    <div class="overflow-hidden bg-white border border-gray-200 rounded-2xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Seminar</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Connector</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Date</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Invitation / Event</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-right text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($invitations as $speaker)
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900"><div class="flex items-center gap-3">@include('seminars._thumbnail', ['seminar' => $speaker->seminar, 'variant' => 'compact'])<span>{{ $speaker->seminar?->title }}</span></div></td>
                        <td class="px-5 py-4 text-sm text-gray-600">
                            @php
                                $organization = $speaker->seminar?->connector;
                                $organizationLogo = $organization?->status === 'verified' && filled($organization?->logo_path)
                                    ? Storage::disk(config('connector_verification.logo_disk', 'public'))->url($organization->logo_path)
                                    : null;
                            @endphp
                            <div class="flex items-center gap-2">
                                @if($organizationLogo)
                                    <img src="{{ $organizationLogo }}" alt="" width="48" height="48" class="h-12 w-12 rounded-lg object-contain" x-on:error="$el.hidden = true">
                                @endif
                                <span>{{ $speaker->seminar?->connector?->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-600">{{ $speaker->seminar?->localStartsAt() ? $speaker->seminar->localStartsAt()->format('M d, Y g:i A').' PHT' : 'TBA' }}</td>
                        <td class="px-5 py-4"><span class="px-3 py-1 text-xs font-bold text-gray-700 capitalize bg-gray-100 rounded-full">{{ $speaker->status === 'pending' && $speaker->expires_at?->isPast() ? 'expired' : $speaker->status }}</span><div class="mt-2">@include('instructor.speaker-invitations._event-state', ['seminar' => $speaker->seminar])</div></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('instructor.speaker-invitations.show', $speaker) }}" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-sm text-center text-gray-500">No speaker invitations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invitations->links() }}
</div>
@endsection
