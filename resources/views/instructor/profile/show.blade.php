@extends('layouts.instructor-app')

@section('content')
<div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
    <section class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
        <div class="px-6 py-8 md:px-8" style="background: linear-gradient(120deg, #A30EB2 0%, #730DB1 55%, #3B0CB1 100%);">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-24 w-24 overflow-hidden rounded-full border-4 border-white/80 bg-white shadow-lg">
                        <img src="{{ $profile->profile_photo_path ? Storage::url($profile->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&color=1d4ed8&background=eff6ff' }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-white md:text-3xl">{{ $user->full_name ?: $user->name }}</h1>
                        <p class="mt-1 text-sm font-medium text-white/90 md:text-base">{{ $profile->primary_expertise ?: 'Instructor' }}</p>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm text-white/90">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                {{ $user->email }}
                            </span>
                            @if($learnerProfile?->barangay)
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $learnerProfile->barangay }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <a href="{{ route('instructor.profile.edit') }}" class="inline-flex items-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 shadow-sm ring-1 ring-inset ring-white/70 transition hover:bg-brand-50">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit Profile
                </a>
            </div>
        </div>

        <div class="border-t border-brand-100 bg-white px-6 py-4 md:px-8">
            @if(!empty($profile->expertise_tags) && count($profile->expertise_tags) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($profile->expertise_tags as $tag)
                        <span class="inline-flex items-center rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $tag }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No expertise tags added yet.</p>
            @endif
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Impact Overview</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-brand-100 bg-brand-50/40 p-4">
                <p class="text-sm font-medium text-gray-600">Modules Created</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $overview['modules_created'] }}</p>
            </article>
            <article class="rounded-xl border border-green-100 bg-green-50/50 p-4">
                <p class="text-sm font-medium text-gray-600">Learners Enrolled</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $overview['total_learners_enrolled'] }}</p>
            </article>
            <article class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                <p class="text-sm font-medium text-gray-600">Quizzes Created</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $overview['total_quizzes_created'] }}</p>
            </article>
            <article class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                <p class="text-sm font-medium text-gray-600">Average Rating</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $overview['average_rating'] }}</p>
            </article>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-12">
        <aside class="space-y-6 lg:col-span-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">About Me</h2>
                <div class="mt-4 text-sm leading-7 text-gray-700">
                    @if($profile->bio)
                        <p class="whitespace-pre-line">{{ $profile->bio }}</p>
                    @else
                        <p class="italic text-gray-400">Bio not provided yet.</p>
                    @endif
                </div>
                <div class="mt-5 border-t border-gray-100 pt-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Years of Experience</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $profile->years_experience ? $profile->years_experience . ' years' : 'Not specified' }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Credentials</h2>
                @if(!empty($profile->credentials) && count($profile->credentials) > 0)
                    <ul class="mt-4 space-y-2 text-sm text-gray-700">
                        @foreach($profile->credentials as $credential)
                            <li class="flex gap-2">
                                <span class="mt-1 h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                <span>{{ $credential }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm italic text-gray-400">No credentials listed yet.</p>
                @endif
            </section>
        </aside>

        <section class="space-y-6 lg:col-span-8">
            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-900">Professional Background</h2>
                <div class="mt-4 text-sm leading-7 text-gray-700">
                    @if($profile->professional_background)
                        <p class="whitespace-pre-line">{{ $profile->professional_background }}</p>
                    @else
                        <p class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 italic text-gray-400">No professional background details provided yet.</p>
                    @endif
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-900">Certifications</h2>
                @if(!empty($certifications) && count($certifications) > 0)
                    <div class="mt-4 space-y-3">
                        @foreach($certifications as $certification)
                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">{{ $certification['title'] }}</h3>
                                        <p class="mt-1 text-sm text-gray-600">{{ $certification['organization'] ?: 'Issuing organization not specified' }}</p>
                                        <p class="mt-1 text-xs font-medium text-gray-500">Completed: {{ !empty($certification['completion_date']) && strtotime((string) $certification['completion_date']) !== false ? date('M d, Y', strtotime((string) $certification['completion_date'])) : 'Date not provided' }}</p>
                                    </div>
                                    @if(!empty($certification['attachment_path']))
                                        @php
                                            $proofPath = (string) $certification['attachment_path'];
                                            $proofUrl = Storage::url($proofPath);
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <a
                                                href="{{ $proofUrl }}"
                                                target="_blank"
                                                class="inline-flex items-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-50"
                                            >
                                                View certificate proof
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm italic text-gray-400">No certifications added yet.</p>
                @endif
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-900">Educational Background</h2>
                @if(!empty($educationalEntries) && count($educationalEntries) > 0)
                    <div class="mt-4 space-y-3">
                        @foreach($educationalEntries as $entry)
                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $entry['degree_program'] ?: 'Degree or program not provided' }}</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $entry['school_name'] ?: 'School not specified' }}</p>
                                <p class="mt-1 text-xs font-medium text-gray-500">Graduation: {{ !empty($entry['graduation_date']) && strtotime((string) $entry['graduation_date']) !== false ? date('M d, Y', strtotime((string) $entry['graduation_date'])) : 'Date not provided' }}</p>
                            </div>
                        @endforeach
                    </div>
                @elseif($profile->educational_background)
                    <p class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-700">{{ $profile->educational_background }}</p>
                @else
                    <p class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm italic text-gray-400">No educational background details provided yet.</p>
                @endif
            </article>
        </section>
    </div>

</div>
@endsection