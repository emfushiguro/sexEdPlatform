@extends('layouts.admin')

@section('title', $seminar->title)
@section('page-title', 'Seminar Review')

@section('content')
@php
    $ageLabels = config('seminars.learner_age_categories');
    $selectedAges = collect($seminar->learner_age_categories ?? [])
        ->map(fn ($key) => $ageLabels[$key] ?? ucfirst((string) $key))
        ->implode(', ');
    $avatarUrlFor = function ($user) {
        $path = $user?->learnerProfile?->avatar_path ?? $user?->instructorProfile?->profile_photo_path;
        return $path ? asset('storage/' . ltrim((string) str_replace('storage/', '', $path), '/')) : null;
    };
@endphp

<div
    x-data="{ approveOpen: false, rejectOpen: @js($errors->any()), rejectReason: @js(old('reason', 'incomplete_information')) }"
    class="space-y-6"
>
    @foreach(['success','error','warning'] as $type)
        @if(session($type))
            <div class="rounded-xl border px-4 py-3 text-sm {{ $type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                {{ session($type) }}
            </div>
        @endif
    @endforeach

    <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <a href="{{ route('admin.seminars.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Back to moderation queue</a>
                    <h1 class="mt-3 text-2xl font-bold text-gray-900">{{ $seminar->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $seminar->connector?->name ?? 'No connector' }} / {{ $seminar->categoryDisplayName() }}</p>
                </div>

                @if($seminar->status === \App\Enums\SeminarStatus::PendingReview->value)
                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="approveOpen = true" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Approve
                        </button>
                        <button type="button" @click="rejectOpen = true" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-white px-5 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6 6 18M6 6l12 12" /></svg>
                            Reject
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-6 p-6 xl:grid-cols-[1.5fr_1fr]">
            <div class="space-y-6">
                <section>
                    <h2 class="border-b border-gray-100 pb-2 text-sm font-bold uppercase tracking-[0.2em] text-gray-500">Basic Information</h2>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Status</dt><dd class="mt-1 font-semibold capitalize text-gray-900">{{ str_replace('_', ' ', $seminar->status) }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Type</dt><dd class="mt-1 font-semibold capitalize text-gray-900">{{ $seminar->type }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Category</dt><dd class="mt-1 font-semibold text-gray-900">{{ $seminar->categoryDisplayName() }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Capacity</dt><dd class="mt-1 font-semibold text-gray-900">{{ $seminar->capacity ? number_format($seminar->capacity) : 'Unlimited' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Purpose</dt><dd class="mt-1 text-sm text-gray-700">{{ $seminar->purpose ?: 'No purpose provided.' }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h2 class="border-b border-gray-100 pb-2 text-sm font-bold uppercase tracking-[0.2em] text-gray-500">Seminar Schedule</h2>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Starts</dt><dd class="mt-1 font-semibold text-gray-900">{{ $seminar->localStartsAt() ? $seminar->localStartsAt()->format('M d, Y g:i A').' PHT' : 'Not set' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Ends</dt><dd class="mt-1 font-semibold text-gray-900">{{ $seminar->localEndsAt() ? $seminar->localEndsAt()->format('M d, Y g:i A').' PHT' : 'Not set' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Location</dt><dd class="mt-1 text-sm text-gray-700">{{ $seminar->location ?: 'No Location Provided' }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Livestream Channel</dt><dd class="mt-1 text-sm text-gray-700">{{ $seminar->livestream_channel ?: 'Not set' }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h2 class="border-b border-gray-100 pb-2 text-sm font-bold uppercase tracking-[0.2em] text-gray-500">Registration Settings</h2>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Registration</dt><dd class="mt-1 font-semibold capitalize text-gray-900">{{ str_replace('_', ' ', $seminar->registration_approval_mode ?? 'open') }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-gray-500">Target Participants</dt><dd class="mt-1 font-semibold capitalize text-gray-900">{{ str_replace('_', ' ', $seminar->target_participants ?? 'Not set') }}</dd></div>
                        <div class="md:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Learner Ages</dt><dd class="mt-1 text-sm text-gray-700">{{ $selectedAges ?: 'No age limits set.' }}</dd></div>
                    </dl>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-2xl border border-gray-100 p-5">
                    <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-gray-500">Connector Information</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700">{{ mb_substr($seminar->connector?->name ?? 'C', 0, 1) }}</span>
                            <div><dt class="font-semibold text-gray-500">Connector</dt><dd class="mt-1 text-gray-900">{{ $seminar->connector?->name ?? 'No connector' }}</dd></div>
                        </div>
                        <div><dt class="font-semibold text-gray-500">Email</dt><dd class="mt-1 text-gray-900">{{ $seminar->connector?->organization_email ?? 'Not set' }}</dd></div>
                        <div><dt class="font-semibold text-gray-500">Contact</dt><dd class="mt-1 text-gray-900">{{ $seminar->connector?->contact_number ?? 'Not set' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-gray-100 p-5">
                    <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-gray-500">Speakers</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($seminar->speakers as $speaker)
                            <div class="flex items-center gap-3 text-sm">
                                @php($speakerAvatar = $avatarUrlFor($speaker->user))
                                @if($speakerAvatar)
                                    <img src="{{ $speakerAvatar }}" alt="" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-100 text-sm font-bold text-gray-600">{{ mb_substr($speaker->display_name ?: 'S', 0, 1) }}</span>
                                @endif
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $speaker->display_name ?: $speaker->user?->full_name }}</div>
                                    <div class="text-gray-500">{{ $speaker->role ?? 'Speaker' }} / {{ $speaker->status }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No speakers listed.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </section>

    <section class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-gray-500">Moderation Information</h2>
        <div class="mt-4 space-y-3">
            @forelse($seminar->moderationReviews as $review)
                <div class="rounded-2xl border border-gray-100 p-4 text-sm">
                    <div class="font-semibold text-gray-900">{{ str_replace('_', ' ', $review->from_status ?? 'none') }} to {{ str_replace('_', ' ', $review->to_status) }}</div>
                    <div class="mt-1 text-gray-500">{{ $review->reason ? (config('seminars.rejection_reasons')[$review->reason] ?? $review->reason) : 'No reason' }}</div>
                    @if($review->note)
                        <p class="mt-2 text-gray-700">{{ $review->note }}</p>
                    @endif
                    <div class="mt-2 text-xs text-gray-400">{{ optional($review->reviewed_at)->format('M d, Y g:i A') }} by {{ $review->moderator?->full_name ?? 'Admin' }}</div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No moderation history.</p>
            @endforelse
        </div>
    </section>

    <div x-show="approveOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/65 backdrop-blur-sm" @click="approveOpen = false"></div>
        <form method="POST" action="{{ route('admin.seminars.approve', $seminar) }}" class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
            @csrf
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Approve seminar?</h3>
                <p class="mt-2 text-sm text-gray-600">This marks the seminar approved and eligible for connector publication.</p>
            </div>
            <div class="flex justify-end gap-3 px-6 py-5">
                <button type="button" @click="approveOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Cancel</button>
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button>
            </div>
        </form>
    </div>

    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/65 backdrop-blur-sm" @click="rejectOpen = false"></div>
        <form method="POST" action="{{ route('admin.seminars.reject', $seminar) }}" class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            @csrf
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Reject seminar</h3>
                <p class="mt-2 text-sm text-gray-600">Connector can edit and resubmit after receiving this reason.</p>
            </div>
            <div class="space-y-4 px-6 py-5">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Reason</span>
                    <select name="reason" x-model="rejectReason" required class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900">
                        @foreach(config('seminars.rejection_reasons') as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="rejectReason === 'other' ? 'Custom explanation' : 'Moderator note'"></span>
                    <textarea name="note" rows="4" :required="rejectReason === 'other'" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900">{{ old('note') }}</textarea>
                    @error('note')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
                </label>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-5">
                <button type="button" @click="rejectOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Cancel</button>
                <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Reject</button>
            </div>
        </form>
    </div>
</div>
@endsection
