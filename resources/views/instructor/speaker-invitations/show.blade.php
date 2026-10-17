@extends('layouts.instructor-app')

@section('title', 'Speaker Invitation')
@section('content')
@php
    $seminar = $speaker->seminar;
    $invitationState = $speaker->status === 'pending' && $speaker->expires_at?->isPast() ? 'expired' : $speaker->status;
@endphp
<div class="max-w-5xl space-y-6" x-data="{ acceptOpen: false, declineOpen: false, declineReason: 'Schedule conflict', customDeclineReason: '' }">
    <a href="{{ route('instructor.speaker-invitations.index') }}" class="text-sm font-semibold text-brand-700">Back to invitations</a>

    <section class="rounded-2xl border border-gray-200 bg-white p-6">
        <div class="mb-5 max-w-3xl">@include('seminars._thumbnail', ['variant' => 'cover', 'loading' => 'eager'])</div>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ $seminar?->type ?? 'Seminar' }}</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $seminar?->title }}</h1>
                <p class="mt-3 text-sm leading-6 text-gray-600">{{ $seminar?->purpose ?: $seminar?->description }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold capitalize {{ $invitationState === 'accepted' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">Invitation: @if($invitationState === 'accepted')&#10003; Confirmed speaker @else{{ $invitationState }}@endif</span>
            @include('instructor.speaker-invitations._event-state')
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-bold text-gray-900">Schedule</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="font-semibold text-gray-500">Starts</dt><dd class="text-gray-900">{{ $seminar?->localStartsAt() ? $seminar->localStartsAt()->format('M d, Y g:i A').' PHT' : 'To be announced' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Ends</dt><dd class="text-gray-900">{{ $seminar?->localEndsAt() ? $seminar->localEndsAt()->format('M d, Y g:i A').' PHT' : 'To be announced' }}</dd></div>
                @if($seminar?->event_format === 'in_person')<div><dt class="font-semibold text-gray-500">Venue</dt><dd class="text-gray-900">{{ $seminar->location ?: 'Venue to be announced' }}</dd></div>@elseif($seminar?->isExternalDelivery())<div><dt class="font-semibold text-gray-500">Platform</dt><dd class="text-gray-900">{{ $seminar->external_platform === 'other' ? $seminar->external_platform_name : ucwords(str_replace('_', ' ', $seminar->external_platform ?? 'To be announced')) }}</dd></div>@endif
                @if($seminar?->isNativeDelivery() && $seminar->livestream_channel)
                    <div><dt class="font-semibold text-gray-500">Livestream</dt><dd class="text-gray-900">{{ $seminar->livestream_channel }}</dd></div>
                @endif
            </dl>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-bold text-gray-900">Organizer</h2>
                @php
                    $organization = $seminar?->connector;
                    $organizationLogo = $organization?->status === 'verified' && filled($organization?->logo_path)
                        ? Storage::disk(config('connector_verification.logo_disk', 'public'))->url($organization->logo_path)
                        : null;
                @endphp
                @if($organizationLogo)
                    <img src="{{ $organizationLogo }}" alt="" width="48" height="48" class="h-12 w-12 rounded-lg object-contain" x-on:error="$el.hidden = true">
                @endif
            <p class="mt-2 text-sm font-semibold text-gray-900">{{ $seminar?->connector?->name }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ $seminar?->connector?->organization_email ?: 'Contact not published' }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="font-bold text-gray-900">Other speakers</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse(($seminar?->speakers ?? collect())->where('status', 'accepted')->where('id', '!=', $speaker->id) as $otherSpeaker)
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">&#10003; {{ $otherSpeaker->display_name }} / Confirmed speaker</span>
            @empty
                <p class="text-sm text-gray-500">No other speakers listed.</p>
            @endforelse
        </div>
    </section>

    @if($speaker->invitation_message)
        <section class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <h2 class="font-bold text-gray-900">Message from the host</h2>
            <p class="mt-2 text-sm leading-6 text-gray-700">{{ $speaker->invitation_message }}</p>
        </section>
    @endif

    @if($invitationState === 'pending' && $seminar?->status !== 'cancelled')
        <section class="flex gap-2">
            <button type="button" @click="acceptOpen = true" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Accept Invitation</button>
            <button type="button" @click="declineOpen = true" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Decline Invitation</button>
        </section>
    @elseif($speaker->status === 'accepted' && $seminar)
        <section>
            @if($seminar->status === 'cancelled')
                <p class="text-sm text-gray-600">This event has been cancelled.</p>
            @elseif(! in_array($seminar->status, ['published', 'completed'], true))
                <p class="text-sm text-gray-600">Event access will be available after publication.</p>
            @elseif($seminar->isExternalDelivery())
                @if($externalJoinOpen)
                    <a href="{{ route('seminars.external.join', $seminar) }}" class="inline-flex rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Open external event</a>
                    <p class="mt-2 text-sm text-gray-600">Confirmed speakers can prepare before participant link release.</p>
                @else
                    <p class="text-sm text-gray-600">{{ $externalMessage }}</p>
                @endif
            @elseif($seminar->event_format === 'in_person')
                <p class="font-semibold text-gray-900">{{ $seminar->location ?: 'Venue to be announced' }}</p>
                <p class="text-sm text-gray-600">{{ $seminar->venue_address }} {{ $seminar->venue_room }}</p>
                @if($seminar->status === 'completed' || ($seminar->ends_at && now()->greaterThanOrEqualTo($seminar->ends_at)))<p class="text-sm text-gray-600">This event has ended.</p>@endif
            @elseif($seminar->status === 'completed' || ($seminar->ends_at && now()->greaterThanOrEqualTo($seminar->ends_at)))
                <p class="text-sm text-gray-600">This event has ended. Livestream access is closed.</p>
            @elseif($joinOpen)
                <a href="{{ route('seminars.join', $seminar) }}" class="inline-flex rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Join Livestream</a>
            @else
                <p class="text-sm text-gray-600">The livestream is not open. Join when the host starts the stream within the event access window.</p>
            @endif
            @if($seminar->delivery_instructions)<p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $seminar->delivery_instructions }}</p>@endif
        </section>
    @endif

    @if($invitationState === 'pending' && $seminar?->status !== 'cancelled')
    <div x-show="acceptOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/60 p-4">
        <form method="POST" action="{{ route('instructor.speaker-invitations.accept', $speaker) }}" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            @csrf
            <h3 class="text-lg font-bold text-gray-900">Accept invitation?</h3>
            <p class="mt-2 text-sm leading-6 text-gray-600">You become a confirmed event speaker. {{ $seminar?->isExternalDelivery() ? 'Open the external event after publication to prepare.' : ($seminar?->event_format === 'in_person' ? 'Attend at the listed venue and follow the host instructions.' : 'Join the livestream when the host opens access.') }}</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="acceptOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Accept</button>
            </div>
        </form>
    </div>

    <div x-show="declineOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/60 p-4">
        <form method="POST" action="{{ route('instructor.speaker-invitations.decline', $speaker) }}" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            @csrf
            <h3 class="text-lg font-bold text-gray-900">Decline invitation?</h3>
            <p class="mt-2 text-sm text-gray-600">Organizer is notified and invitation history stays available.</p>
            <select x-model="declineReason" name="decline_reason" class="mt-4 w-full rounded-xl border-gray-300 text-sm">
                <option>Schedule conflict</option>
                <option>Personal availability</option>
                <option>Not my area of expertise</option>
                <option>Other</option>
            </select>
            <textarea x-show="declineReason === 'Other'" x-model="customDeclineReason" name="custom_decline_reason" rows="3" class="mt-3 w-full rounded-xl border-gray-300 text-sm" placeholder="Custom reason"></textarea>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="declineOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                <button :disabled="declineReason === 'Other' && !customDeclineReason.trim()" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Decline</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
