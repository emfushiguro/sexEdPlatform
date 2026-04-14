@extends('layouts.learner-app')

@section('title', 'Instructor Background')

@section('content')
@php
    $photoPath = $profile?->profile_photo_path;
    $photoUrl = $photoPath ? asset('storage/' . ltrim($photoPath, '/')) : null;
    $displayName = $instructor->full_name ?: $instructor->name;
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-purple-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="px-6 py-7" style="background: linear-gradient(120deg, #A30EB2 0%, #730DB1 55%, #3B0CB1 100%);">
            <div class="flex items-center gap-4">
                <div class="h-20 w-20 overflow-hidden rounded-full border-4 border-white/70 bg-white shadow-lg">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $displayName }}" class="h-full w-full object-cover">
                    @else
                        <div class="h-full w-full bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold">
                            {{ strtoupper(substr($displayName, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ $displayName }}</h1>
                    <p class="mt-1 text-sm font-medium text-white/90">{{ $profile?->primary_expertise ?: 'Instructor' }}</p>
                    <p class="mt-2 text-xs text-white/80">
                        {{ $profile?->years_experience !== null ? $profile->years_experience . ' years of teaching experience' : 'Teaching experience details are being updated.' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            @if(!empty($profile?->expertise_tags) && count($profile->expertise_tags) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($profile->expertise_tags as $tag)
                        <span class="inline-flex items-center rounded-full border border-purple-200 bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 dark:border-purple-800/60 dark:bg-purple-900/30 dark:text-purple-300">{{ $tag }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No expertise tags added yet.</p>
            @endif
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-12">
        <aside class="space-y-6 lg:col-span-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">About</h2>
                <div class="mt-4 text-sm leading-7 text-gray-700 dark:text-gray-300">
                    @if($profile?->bio)
                        <p class="whitespace-pre-line">{{ $profile->bio }}</p>
                    @else
                        <p class="italic text-gray-400 dark:text-gray-500">Bio not provided yet.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Credentials</h2>
                @if(!empty($profile?->credentials) && count($profile->credentials) > 0)
                    <ul class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @foreach($profile->credentials as $credential)
                            <li class="flex gap-2">
                                <span class="mt-1 h-1.5 w-1.5 rounded-full bg-purple-500"></span>
                                <span>{{ $credential }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm italic text-gray-400 dark:text-gray-500">No credentials listed yet.</p>
                @endif
            </section>
        </aside>

        <section class="space-y-6 lg:col-span-8">
            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Professional Background</h2>
                <div class="mt-4 text-sm leading-7 text-gray-700 dark:text-gray-300">
                    @if($profile?->professional_background)
                        <p class="whitespace-pre-line">{{ $profile->professional_background }}</p>
                    @else
                        <p class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 italic text-gray-400 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-500">No professional background details provided yet.</p>
                    @endif
                </div>
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Certifications</h2>
                @if(!empty($certifications) && count($certifications) > 0)
                    <div class="mt-4 space-y-3">
                        @foreach($certifications as $certification)
                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $certification['title'] }}</h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $certification['organization'] ?: 'Issuing organization not specified' }}</p>
                                        <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Completed: {{ !empty($certification['completion_date']) && strtotime((string) $certification['completion_date']) !== false ? date('M d, Y', strtotime((string) $certification['completion_date'])) : 'Date not provided' }}</p>
                                    </div>
                                    @if(!empty($certification['attachment_path']))
                                        <a
                                            href="{{ \Illuminate\Support\Facades\Storage::url((string) $certification['attachment_path']) }}"
                                            target="_blank"
                                            class="inline-flex items-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-200 transition hover:bg-purple-50 dark:bg-gray-800 dark:text-purple-300 dark:ring-purple-800"
                                        >
                                            View certificate proof
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm italic text-gray-400 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-500">No certifications added yet.</p>
                @endif
            </article>

            <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Educational Background</h2>
                @if(!empty($educationalEntries) && count($educationalEntries) > 0)
                    <div class="mt-4 space-y-3">
                        @foreach($educationalEntries as $entry)
                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $entry['degree_program'] ?: 'Degree or program not provided' }}</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $entry['school_name'] ?: 'School not specified' }}</p>
                                <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Graduation: {{ !empty($entry['graduation_date']) && strtotime((string) $entry['graduation_date']) !== false ? date('M d, Y', strtotime((string) $entry['graduation_date'])) : 'Date not provided' }}</p>
                            </div>
                        @endforeach
                    </div>
                @elseif($profile?->educational_background)
                    <p class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-700 dark:text-gray-300">{{ $profile->educational_background }}</p>
                @else
                    <p class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-sm italic text-gray-400 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-500">No educational background details provided yet.</p>
                @endif
            </article>
        </section>
    </div>
</div>
@endsection
