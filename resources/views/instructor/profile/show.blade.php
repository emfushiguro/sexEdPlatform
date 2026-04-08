@extends('layouts.instructor-app')

@section('content')
<div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

    <!-- Hero Section Card -->
    <div class="bg-white shadow-sm sm:rounded-xl p-8 relative overflow-hidden">
        <!-- Abstract gradient background pattern (optional soft branding) -->
        <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-brand-50 to-indigo-50"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center gap-6 mt-12 md:mt-8">
            <div class="shrink-0 relative h-28 w-28 md:h-32 md:w-32 rounded-full overflow-hidden border-4 border-white shadow-md bg-white flex items-center justify-center">
                <img src="{{ $profile->profile_photo_path ? Storage::url($profile->profile_photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&color=1d4ed8&background=eff6ff' }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
            </div>
            
            <div class="flex-1">
                <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $user->full_name ?: $user->name }}</h1>
                        <p class="text-lg text-gray-600 mt-1">{{ $profile->primary_expertise ?: 'Instructor' }}</p>
                        
                        <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                {{ $user->email }}
                            </span>
                            @if($learnerProfile?->barangay)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $learnerProfile->barangay }}
                            </span>
                            @endif
                        </div>
                    </div>
                    
                    <a href="{{ route('instructor.profile.edit') }}" class="inline-flex items-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-600 border border-brand-200 shadow-sm hover:bg-brand-50 transition-colors whitespace-nowrap">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Edit Profile
                    </a>
                </div>

                @if(!empty($profile->expertise_tags) && count($profile->expertise_tags) > 0)
                <div class="flex flex-wrap gap-2 mt-5">
                    @foreach($profile->expertise_tags as $tag)
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-100 shadow-sm">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Overview Bento -->
    <div class="bg-white shadow-sm sm:rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 block md:hidden">Impact Overview</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm"> 
            <div class="rounded-xl bg-gray-50/50 border border-gray-100 p-5 flex flex-col items-center text-center justify-center transition-all hover:bg-brand-50/30">
                <span class="p-3 bg-brand-100 text-brand-600 rounded-full mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </span>
                <p class="text-3xl font-bold text-gray-900">{{ $overview['modules_created'] }}</p>
                <p class="text-gray-500 font-medium mt-1">Modules Created</p>
            </div>
            
            <div class="rounded-xl bg-gray-50/50 border border-gray-100 p-5 flex flex-col items-center text-center justify-center transition-all hover:bg-green-50/30">
                <span class="p-3 bg-green-100 text-green-600 rounded-full mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
                <p class="text-3xl font-bold text-gray-900">{{ $overview['total_learners_enrolled'] }}</p>
                <p class="text-gray-500 font-medium mt-1">Learners Enrolled</p>
            </div>
            
            <div class="rounded-xl bg-gray-50/50 border border-gray-100 p-5 flex flex-col items-center text-center justify-center transition-all hover:bg-purple-50/30">
                <span class="p-3 bg-purple-100 text-purple-600 rounded-full mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </span>
                <p class="text-3xl font-bold text-gray-900">{{ $overview['total_quizzes_created'] }}</p>
                <p class="text-gray-500 font-medium mt-1">Quizzes Created</p>
            </div>
            
            <div class="rounded-xl bg-gray-50/50 border border-gray-100 p-5 flex flex-col items-center text-center justify-center transition-all hover:bg-yellow-50/30">
                <span class="p-3 bg-yellow-100 text-yellow-600 rounded-full mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                </span>
                <p class="text-3xl font-bold text-gray-900">{{ $overview['average_rating'] }}</p>
                <p class="text-gray-500 font-medium mt-1">Average Rating</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid gap-6 md:grid-cols-3 items-start">
        <!-- Left Column: Certs & Bio Summary -->
        <div class="space-y-6 md:col-span-1">
            <section class="bg-white shadow-sm sm:rounded-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">About Me</h2>
                <div class="prose prose-sm text-gray-700 max-w-none">
                    @if($profile->bio)
                        <p class="whitespace-pre-line">{{ $profile->bio }}</p>
                    @else
                        <p class="italic text-gray-400">Bio not provided yet.</p>
                    @endif
                </div>
                
                @if($profile->years_experience)
                <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-sm text-gray-500">Experience</span>
                    <span class="text-sm font-semibold text-gray-900 bg-gray-100 px-3 py-1 rounded-full">{{ $profile->years_experience }} Years</span>
                </div>
                @endif
            </section>

            @if(!empty($profile->certifications) && count($profile->certifications) > 0)
            <section class="bg-white shadow-sm sm:rounded-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    Certifications
                </h2>
                <ul class="space-y-3">
                    @foreach($profile->certifications as $cert)
                        <li class="flex items-start text-sm text-gray-700">
                            <span class="mr-2 text-brand-500 mt-0.5">•</span>
                            <span>{{ $cert }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif

            @if(!empty($profile->credentials) && count($profile->credentials) > 0)
            <section class="bg-white shadow-sm sm:rounded-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                    Credentials
                </h2>
                <ul class="space-y-3">
                    @foreach($profile->credentials as $cred)
                        <li class="flex items-start text-sm text-gray-700">
                            <span class="mr-2 text-brand-500 mt-0.5">•</span>
                            <span>{{ $cred }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif
        </div>

        <!-- Right Column: Detailed Backgrounds -->
        <div class="space-y-6 md:col-span-2">
            <section class="bg-white shadow-sm sm:rounded-xl p-6 h-full">
                <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Professional Background
                </h2>
                <div class="prose prose-blue text-gray-700 max-w-none">
                    @if($profile->professional_background)
                        <div class="whitespace-pre-line leading-relaxed">{{ $profile->professional_background }}</div>
                    @else
                        <p class="italic text-gray-400 bg-gray-50 p-4 rounded-lg border border-dashed border-gray-200">No professional background details provided yet.</p>
                    @endif
                </div>

                <div class="mt-8 pt-8 border-t border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        Educational Background
                    </h2>
                    <div class="prose prose-blue text-gray-700 max-w-none">
                        @if($profile->educational_background)
                            <div class="whitespace-pre-line leading-relaxed">{{ $profile->educational_background }}</div>
                        @else
                            <p class="italic text-gray-400 bg-gray-50 p-4 rounded-lg border border-dashed border-gray-200">No educational background details provided yet.</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection