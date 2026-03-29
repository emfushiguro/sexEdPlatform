@extends('layouts.instructor-app')

@section('content')
<div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Instructor Profile</h1>
                <p class="text-sm text-gray-600 mt-1">Your public-facing instructor profile summary.</p>
            </div>
            <a href="{{ route('instructor.profile.edit') }}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit Profile</a>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <section class="bg-white shadow-sm sm:rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>
            <dl class="mt-4 space-y-2 text-sm text-gray-700">
                <div><dt class="font-medium">Name</dt><dd>{{ $user->full_name ?: $user->name }}</dd></div>
                <div><dt class="font-medium">Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt class="font-medium">Age</dt><dd>{{ $user->calculateAge() ?? 'N/A' }}</dd></div>
                <div><dt class="font-medium">Location</dt><dd>{{ $learnerProfile?->barangay ?: 'N/A' }}</dd></div>
            </dl>
        </section>

        <section class="bg-white shadow-sm sm:rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900">Educational Background</h2>
            <p class="mt-4 text-sm text-gray-700">{{ $profile->educational_background ?: 'Not provided yet.' }}</p>
        </section>

        <section class="bg-white shadow-sm sm:rounded-lg p-6 md:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">Professional Background</h2>
            <p class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ $profile->professional_background ?: ($profile->bio ?: 'Not provided yet.') }}</p>
            <div class="mt-4 text-sm text-gray-700">
                <span class="font-medium">Primary Expertise:</span>
                <span>{{ $profile->primary_expertise ?: 'Not provided yet.' }}</span>
            </div>
        </section>

        <section class="bg-white shadow-sm sm:rounded-lg p-6 md:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">Instructor Overview</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                <div class="rounded-md bg-gray-50 p-3"><p class="text-gray-500">Modules Created</p><p class="text-xl font-semibold text-gray-900">{{ $overview['modules_created'] }}</p></div>
                <div class="rounded-md bg-gray-50 p-3"><p class="text-gray-500">Total Learners Enrolled</p><p class="text-xl font-semibold text-gray-900">{{ $overview['total_learners_enrolled'] }}</p></div>
                <div class="rounded-md bg-gray-50 p-3"><p class="text-gray-500">Total Quizzes Created</p><p class="text-xl font-semibold text-gray-900">{{ $overview['total_quizzes_created'] }}</p></div>
                <div class="rounded-md bg-gray-50 p-3"><p class="text-gray-500">Average Rating</p><p class="text-xl font-semibold text-gray-900">{{ $overview['average_rating'] }}</p></div>
            </div>
        </section>
    </div>
</div>
@endsection
