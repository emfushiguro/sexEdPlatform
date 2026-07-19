@extends('layouts.instructor-app')

@section('title', 'Speaker Invitation')
@section('content')
@php($seminar = $speaker->seminar)
<div class="max-w-5xl space-y-6" x-data="{ acceptOpen: false, declineOpen: false, declineReason: 'Schedule conflict', customDeclineReason: '' }">
    <a href="{{ route('instructor.speaker-invitations.index') }}" class="text-sm font-semibold text-brand-700">Back to invitations</a>

    <section class="rounded-2xl border border-gray-200 bg-white p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ $seminar?->type ?? 'Seminar' }}</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $seminar?->title }}</h1>
                <p class="mt-3 text-sm leading-6 text-gray-600">{{ $seminar?->purpose ?: $seminar?->description }}</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold capitalize text-gray-700">{{ $speaker->status }}</span>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-bold text-gray-900">Schedule</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="font-semibold text-gray-500">Starts</dt><dd class="text-gray-900">{{ $seminar?->localStartsAt() ? $seminar->localStartsAt()->format('M d, Y g:i A').' PHT' : 'To be announced' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Ends</dt><dd class="text-gray-900">{{ $seminar?->localEndsAt() ? $seminar->localEndsAt()->format('M d, Y g:i A').' PHT' : 'To be announced' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Location</dt><dd class="text-gray-900">{{ $seminar?->location ?: 'No Location Provided' }}</dd></div>
                @if($seminar?->livestream_channel)
                    <div><dt class="font-semibold text-gray-500">Livestream</dt><dd class="text-gray-900">{{ $seminar->livestream_channel }}</dd></div>
                @endif
            </dl>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-bold text-gray-900">Organizer</h2>
            <p class="mt-2 text-sm font-semibold text-gray-900">{{ $seminar?->connector?->name }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ $seminar?->connector?->organization_email ?: 'Contact not published' }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="font-bold text-gray-900">Other speakers</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse($seminar?->speakers ?? [] as $otherSpeaker)
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">{{ $otherSpeaker->display_name }}</span>
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

    @if($speaker->status === 'pending')
        <section class="flex gap-2">
            <button type="button" @click="acceptOpen = true" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Accept Invitation</button>
            <button type="button" @click="declineOpen = true" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Decline Invitation</button>
        </section>
    @elseif($speaker->status === 'accepted' && $seminar)
        <section>
            @if($joinOpen)
                <a href="{{ route('seminars.join', $seminar) }}" class="inline-flex rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Join Livestream</a>
            @else
                <span class="inline-flex rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-500">Livestream opens before start</span>
            @endif
        </section>
    @endif

    <div x-show="acceptOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/60 p-4">
        <form method="POST" action="{{ route('instructor.speaker-invitations.accept', $speaker) }}" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            @csrf
            <h3 class="text-lg font-bold text-gray-900">Accept invitation?</h3>
            <p class="mt-2 text-sm leading-6 text-gray-600">You become an official seminar speaker. This seminar appears in your schedule, and you may join the livestream when available.</p>
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
</div>
@endsection
