@extends('layouts.instructor')
@section('title', 'Review Enrollment')
@section('page-title', 'Review Enrollment Request')
@section('content')

<div class="mb-5">
    <a href="{{ route('instructor.enrollments.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Enrollment Requests
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main — Learner Profile -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Basic Information -->
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Learner Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Full Name</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $enrollment->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Username</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $enrollment->user->learnerProfile?->username ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Email</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $enrollment->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Age</p>
                        <div class="flex items-center gap-2">
                            @if($enrollment->user->learnerProfile)
                                <p class="text-sm text-gray-900 dark:text-white">{{ $enrollment->user->learnerProfile->getAge() }} years old</p>
                                @php
                                    $age = $enrollment->user->learnerProfile->getAge();
                                    $isAgeAppropriate = $age >= $enrollment->module->min_age && $age <= $enrollment->module->max_age;
                                @endphp
                                @if($isAgeAppropriate)
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">Age-appropriate</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Outside target range</span>
                                @endif
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">—</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Gender</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $enrollment->user->learnerProfile ? ucfirst(str_replace('_', ' ', $enrollment->user->learnerProfile->gender)) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Account Created</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $enrollment->user->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Location</p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            @if($enrollment->user->learnerProfile)
                                @php
                                    $profile = $enrollment->user->learnerProfile;
                                    $barangayName = is_object($profile->barangay) ? $profile->barangay->name : ($profile->barangay ?? '');
                                    $cityName = $profile->city ? $profile->city->name : '';
                                @endphp
                                {{ $barangayName && $cityName ? "$barangayName, $cityName" : ($cityName ?: '—') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Requested -->
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Module Requested</h3>
            </div>
            <div class="p-6">
                <div class="flex gap-4">
                    @if($enrollment->module->thumbnail)
                        <img src="{{ asset('storage/' . $enrollment->module->thumbnail) }}"
                             alt="{{ $enrollment->module->title }}"
                             class="w-20 h-20 object-cover rounded-xl border border-gray-200 dark:border-gray-700 flex-shrink-0">
                    @endif
                    <div class="flex-1 min-w-0">
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ $enrollment->module->title }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($enrollment->module->description, 150) }}</p>
                        <div class="flex flex-wrap gap-3 mt-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">Age: {{ $enrollment->module->min_age }}–{{ $enrollment->module->max_age }} yrs</span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->module->lessons_count ?? 0 }} lessons</span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Requested {{ $enrollment->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning History -->
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Learning Activity</h3>
            </div>
            <div class="p-5">
                @if($recentEnrollments->count() > 0)
                    <div class="space-y-2">
                        @foreach($recentEnrollments as $recent)
                        <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-gray-50 dark:bg-white/[0.02]">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $recent->module->title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Enrolled {{ $recent->enrolled_at?->diffForHumans() ?? $recent->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="ml-3 flex-shrink-0">
                                @if($recent->completed_at)
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">Completed</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ $recent->completion_percentage }}% Progress</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No previous enrollments</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar — Stats & Actions -->
    <div class="space-y-5">

        <!-- Learner Statistics -->
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Learner Statistics</h3>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Total Enrollments</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalEnrollments }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Completed Modules</p>
                    <p class="text-3xl font-bold text-success-600 dark:text-success-400">{{ $completedModules }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Completion Rate</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full bg-success-500" style="width: {{ $completionRate }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $completionRate }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Decision -->
        @if($enrollment->status === \App\Enums\EnrollmentStatus::Pending)
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Review Decision</h3>
            </div>
            <div class="p-5 space-y-3">
                <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" onclick="return confirm('Approve this enrollment request?')"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-success-500 hover:bg-success-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve Enrollment
                    </button>
                </form>
                <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" onclick="return confirm('Reject this enrollment request? The learner will be notified.')"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-error-500 hover:bg-error-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject Request
                    </button>
                </form>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="block w-full text-center px-4 py-2.5 rounded-lg bg-gray-100 dark:bg-white/[0.05] hover:bg-gray-200 dark:hover:bg-white/[0.08] text-gray-700 dark:text-gray-300 text-sm font-medium transition-colors">
                    Back to Requests
                </a>
            </div>
        </div>
        @else
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="p-6 text-center">
                @if($enrollment->status === \App\Enums\EnrollmentStatus::Approved)
                    <div class="rounded-xl bg-success-50 dark:bg-success-500/10 border border-success-200 dark:border-success-500/20 p-5">
                        <svg class="w-10 h-10 text-success-600 dark:text-success-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-semibold text-success-800 dark:text-success-300">Already Approved</p>
                        <p class="text-xs text-success-600 dark:text-success-400 mt-1">{{ $enrollment->enrolled_at->format('M d, Y') }}</p>
                    </div>
                @else
                    <div class="rounded-xl bg-error-50 dark:bg-error-500/10 border border-error-200 dark:border-error-500/20 p-5">
                        <svg class="w-10 h-10 text-error-600 dark:text-error-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-semibold text-error-800 dark:text-error-300">Request Rejected</p>
                    </div>
                @endif
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="block w-full text-center mt-4 px-4 py-2.5 rounded-lg bg-gray-100 dark:bg-white/[0.05] hover:bg-gray-200 dark:hover:bg-white/[0.08] text-gray-700 dark:text-gray-300 text-sm font-medium transition-colors">
                    Back to Requests
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
