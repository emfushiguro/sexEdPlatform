@extends('layouts.learner-app')

@section('title', 'Admin Creator Information')

@section('content')
@php
    $displayName = $profile?->public_display_name ?: ($adminUser->full_name ?: $adminUser->name ?: 'Platform Developer');
    $affiliation = $profile?->affiliation ?: 'Conscious Connections Team';
    $bio = $profile?->bio;
    $avatarPath = $profile?->avatar_path;
    $avatarUrl = $avatarPath ? asset('storage/' . ltrim((string) $avatarPath, '/')) : null;
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
            <p class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/20">
                View Full Information page
            </p>
            <div class="mt-4 flex items-center gap-4">
                <div class="h-20 w-20 overflow-hidden rounded-full border-4 border-white/70 bg-white shadow-lg">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" class="h-full w-full object-cover">
                    @else
                        <div class="h-full w-full bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold">
                            {{ strtoupper(substr($displayName, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ $displayName }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <p class="text-sm font-medium text-white/90">Platform Developer</p>
                    </div>
                    <p class="mt-1 text-xs text-white/80">{{ $affiliation }}</p>
                </div>
                @if(auth()->check() && (int) auth()->id() !== (int) $adminUser->id)
                    <button
                        type="button"
                        @click="$dispatch('open-global-chat', { target_user_id: {{ (int) $adminUser->id }}, conversation_type: 'direct', name: @js($displayName) })"
                        class="ml-auto inline-flex items-center rounded-xl border border-white/40 bg-white/15 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/25"
                    >
                        Direct Message
                    </button>
                @endif
            </div>
        </div>

        <div class="px-6 py-5 border-t border-gray-100 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">About</h2>
            <p class="mt-2 text-sm leading-7 text-gray-700 dark:text-gray-300">
                {{ $bio ?: 'No public bio provided yet.' }}
            </p>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Contribution Summary</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Transparent platform contribution signals for learners reviewing creator credibility.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Modules Published</p>
            <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($modulesPublished) }}</p>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Learners Reached</p>
            <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($learnersReached) }}</p>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latest Updated Module</p>
            <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white line-clamp-2">
                {{ $latestUpdatedModule?->title ?: 'No module updates yet.' }}
            </p>
        </article>
        </div>
    </section>
</div>
@endsection
