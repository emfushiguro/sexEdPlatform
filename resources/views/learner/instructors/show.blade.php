@extends('layouts.learner-app')

@section('title', 'Instructor Background')

@section('content')
@php
    $photoPath = $profile?->profile_photo_path;
    $photoUrl = $photoPath ? asset('storage/' . ltrim($photoPath, '/')) : null;
    $displayName = $instructor->full_name ?: $instructor->name;
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <a href="{{ url()->previous() }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-purple-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-4">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $displayName }}" class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-gray-600">
                @else
                    <div class="w-16 h-16 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 flex items-center justify-center text-xl font-bold">
                        {{ strtoupper(substr($displayName, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $displayName }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Instructor</p>
                </div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Professional Background</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $profile?->professional_background ?: ($profile?->bio ?: 'No professional background available yet.') }}</p>
            </div>
            <div class="space-y-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Educational Background</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $profile?->educational_background ?: 'No educational background available yet.' }}</p>
            </div>
            <div class="space-y-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Primary Expertise</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $profile?->primary_expertise ?: ($profile?->specialization ?: 'Not specified') }}</p>
            </div>
            <div class="space-y-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Years of Experience</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $profile?->years_experience !== null ? $profile->years_experience . ' years' : 'Not specified' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
