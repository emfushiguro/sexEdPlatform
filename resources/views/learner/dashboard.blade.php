@extends('layouts.learner-app')

@section('title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ══════════════════════════════════════════════════════════════════
         CENTER COLUMN
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="xl:col-span-2 space-y-8">

        {{-- Greeting --}}
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $greeting }}, {{ $learnerProfile->username ?? Auth::user()->first_name ?? 'Learner' }}!
            </h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Continue your learning journey.</p>
        </div>

        {{-- Active Learning Modules --}}
        <section class="bg-purple-50/40 dark:bg-purple-900/10 rounded-2xl p-5 border border-purple-100/60 dark:border-purple-800/30">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-purple-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Active Learning Modules</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Continue your learning journey</p>
                </div>
                <a href="{{ route('learner.modules.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors duration-150 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-800/50">
                    View All
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 transition-transform duration-150 group-hover:translate-x-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

            @if($enrollmentData->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-10 flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                         style="background: linear-gradient(135deg,#f3e8ff,#ede9fe);">
                        <svg class="w-8 h-8 text-purple-500" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">No active modules yet</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1 mb-4">Enroll in a module below to start your journey.</p>
                    <a href="{{ route('learner.modules.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                       style="background: linear-gradient(135deg,#A30EB2,#3B0CB1);">
                        Explore Modules
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($enrollmentData as $data)
                        <x-learner.module-card-active :moduleData="$data" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Recommended For You --}}
        <section class="bg-indigo-50/30 dark:bg-indigo-900/10 rounded-2xl p-5 border border-indigo-100/50 dark:border-indigo-800/30">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-indigo-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recommended For You</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Age-appropriate modules picked for you</p>
                </div>
                <a href="{{ route('learner.modules.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors duration-150 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-800/50">
                    Browse All
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 transition-transform duration-150 group-hover:translate-x-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

            @if($recommendedModules->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center">
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        You've explored all available modules for your age group — great work! 🎉
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($recommendedModules as $module)
                        <x-learner.module-card-recommended :module="$module" />
                    @endforeach
                </div>
            @endif
        </section>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         RIGHT COLUMN — profile + gamification + calendar
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="space-y-4">

        <x-learner.gamification-panel
            :user="Auth::user()"
            :learnerProfile="$learnerProfile"
            :gamification="$gamification"
            :xpInLevel="$xpInLevel"
            :xpPercent="$xpPercent"
            :totalEnrolled="$totalEnrolled"
            :quizAttemptsUsed="$quizAttemptsUsed"
            :quizAttemptsRemaining="$quizAttemptsRemaining"
            :maxQuizAttempts="$maxQuizAttempts"
            :recentAchievements="$recentAchievements"
        />

        <x-learner.mini-calendar />

    </div>
</div>
@endsection
