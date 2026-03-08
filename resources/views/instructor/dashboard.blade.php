@extends('layouts.instructor')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')

<!-- Statistics Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">

    <!-- Total Learners -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Learners</p>
                <p class="text-3xl font-bold text-brand-600 dark:text-brand-400 mt-1">{{ $stats['total_learners'] }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Premium Users -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Premium Users</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $stats['premium_users'] }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </div>
        </div>
    </div>

    <!-- Published Modules -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Published Modules</p>
                <p class="text-3xl font-bold text-success-600 dark:text-success-400 mt-1">{{ $stats['published_modules'] }}<span class="text-lg text-gray-400 dark:text-gray-500">/{{ $stats['total_modules'] }}</span></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-success-50 dark:bg-success-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
        </div>
    </div>

    <!-- Total Quizzes -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Quizzes</p>
                <p class="text-3xl font-bold text-warning-600 dark:text-warning-400 mt-1">{{ $stats['total_quizzes'] }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-warning-50 dark:bg-warning-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
        </div>
    </div>

    <!-- Pending Enrollment Requests -->
    <a href="{{ route('instructor.enrollments.index') }}" class="block rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5 hover:border-error-300 dark:hover:border-error-600 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pending Requests</p>
                <p class="text-3xl font-bold text-error-600 dark:text-error-400 mt-1">{{ $stats['pending_enrollments'] }}</p>
                @if($stats['pending_enrollments'] > 0)
                    <p class="text-xs text-error-500 dark:text-error-400 mt-1">Needs review →</p>
                @endif
            </div>
            <div class="w-12 h-12 rounded-xl bg-error-50 dark:bg-error-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </a>

    <!-- Certificates Issued -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Certificates Issued</p>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['total_certificates'] }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-yellow-50 dark:bg-yellow-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Top Modules by Enrollment -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Top Modules by Enrollment</h3>
        </div>
        <div class="p-4 space-y-2">
            @forelse($moduleStats as $module)
                <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-gray-50 dark:bg-white/[0.02] hover:bg-brand-50 dark:hover:bg-brand-500/5 transition-colors">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $module->title }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $module->grade_level ?? 'All levels' }}</p>
                    </div>
                    <span class="ml-3 px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400 whitespace-nowrap">
                        {{ $module->enrollments_count }} enrolled
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">No modules yet</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Certificates -->
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Certificates</h3>
        </div>
        <div class="p-4 space-y-2">
            @forelse($recentCertificates as $cert)
                <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-gray-50 dark:bg-white/[0.02]">
                    <div class="w-9 h-9 rounded-xl bg-yellow-50 dark:bg-yellow-500/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $cert->user->learnerProfile->username ?? 'User' }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $cert->module->title }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $cert->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">No certificates issued yet</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
