@extends('layouts.instructor-app')

@section('content')
    <div class="space-y-6" x-data="{}">
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Learner avatar" class="w-16 h-16 rounded-full object-cover border border-gray-200">
                    @else
                        <div class="w-16 h-16 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold">
                            {{ strtoupper(substr($user->full_name ?: $user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->full_name ?: $user->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ $learnerCategoryLabel }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ ($user->status ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst((string) ($user->status ?? 'active')) }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $user->email_verified_at ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $user->email_verified_at ? 'Email Verified' : 'Email Not Verified' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                       @click="window.dispatchEvent(new CustomEvent('open-global-chat', { detail: { target_user_id: {{ (int) $user->id }}, conversation_type: 'direct', name: @js($user->full_name ?: $user->name) } }))"
                       class="inline-flex items-center px-3.5 py-2 rounded-lg text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-100 transition-colors">
                        Message Learner
                    </button>
                    <a href="{{ route('instructor.users.index') }}"
                       class="inline-flex items-center px-3.5 py-2 rounded-lg text-sm font-semibold bg-gray-700 text-white hover:bg-gray-800 transition-colors">
                        Back to Learners
                    </a>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3.5 bg-gray-50/80 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500">Joined</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $user->created_at?->format('M d, Y') }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3.5 bg-gray-50/80 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500">Engagement</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $engagementStatus }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3.5 bg-gray-50/80 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500">Modules Enrolled</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $moduleProgress->count() }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3.5 bg-gray-50/80 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500">Achievements</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $user->achievements->count() }}</p>
                </div>
            </div>
        </div>

        @if($parentLink && $parentLink->relationship_verified_at && $parentLink->parent)
            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Parent Transparency</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This learner is linked to a parent account. You may coordinate updates when guidance is needed.</p>
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-xl border border-gray-100 dark:border-gray-700 p-4 bg-gray-50/80 dark:bg-gray-900/30">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $parentLink->parent->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $parentLink->parent->email }}</p>
                    </div>
                    <button type="button"
                       @click="window.dispatchEvent(new CustomEvent('open-global-chat', { detail: { target_user_id: {{ (int) $parentLink->parent->id }}, conversation_type: 'direct', name: @js($parentLink->parent->name) } }))"
                       class="inline-flex items-center justify-center px-3.5 py-2 rounded-lg text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700 transition-colors">
                        Message Parent
                    </button>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Module Progress</h2>
                    @if($lastLessonCompleted)
                        <p class="text-xs text-gray-500 dark:text-gray-400">Last completed: {{ $lastLessonCompleted->lesson?->title ?? 'Lesson' }} ({{ optional($lastLessonCompleted->completed_at ?? $lastLessonCompleted->updated_at)->diffForHumans() }})</p>
                    @endif
                </div>

                @if($moduleProgress->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No module enrollment progress available.</p>
                @else
                    <div class="space-y-3">
                        @foreach($moduleProgress as $entry)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entry['module']?->title ?? 'Unknown Module' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['completed_lessons'] }} / {{ $entry['total_lessons'] }} lessons completed</p>
                                    </div>
                                    <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">{{ $entry['progress_percentage'] }}%</span>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ max(0, min(100, (int) $entry['progress_percentage'])) }}%; background: linear-gradient(135deg, #A30EB2, #3B0CB1);"></div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <p>Quiz Attempts: {{ $entry['quiz_attempts_count'] }}</p>
                                    <p>Quizzes Passed: {{ $entry['quiz_passed_count'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Gamification</h2>
                    <div class="mt-4 grid grid-cols-1 gap-3">
                        <div class="rounded-xl bg-purple-50 dark:bg-purple-900/20 p-3.5">
                            <p class="text-xs text-purple-600">Level</p>
                            <p class="text-lg font-bold text-purple-900 dark:text-purple-300">{{ $user->gamification?->level ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-brand-50 dark:bg-brand-900/20 p-3.5">
                            <p class="text-xs text-brand-600">Score</p>
                            <p class="text-lg font-bold text-brand-900 dark:text-brand-300">{{ $user->gamification?->score ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-green-50 dark:bg-green-900/20 p-3.5">
                            <p class="text-xs text-green-600">Streak</p>
                            <p class="text-lg font-bold text-green-900 dark:text-green-300">{{ $user->gamification?->streak_count ?? 0 }} days</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-3.5">
                            <p class="text-xs text-amber-600">Achievements</p>
                            <p class="text-lg font-bold text-amber-900 dark:text-amber-300">{{ $user->achievements->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quiz Insights</h2>
                    <div class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <p>Total Attempts: <span class="font-semibold">{{ $quizPerformanceSummary['attempts'] }}</span></p>
                        <p>Passed Attempts: <span class="font-semibold">{{ $quizPerformanceSummary['passed'] }}</span></p>
                        <p>Average Score: <span class="font-semibold">{{ number_format((float) ($quizPerformanceSummary['average_score'] ?? 0), 1) }}%</span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Certificates</h2>
                @if($user->certificates->isEmpty())
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No certificates from your modules yet.</p>
                @else
                    <div class="mt-4 space-y-2">
                        @foreach($user->certificates->sortByDesc('issued_at') as $certificate)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3.5">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $certificate->module?->title ?? ($certificate->module_title ?? 'Module Certificate') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $certificate->certificate_number }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Issued {{ optional($certificate->issued_at)->format('M d, Y') ?: 'N/A' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Learning Timeline</h2>
                @if($recentProgressTimeline->isEmpty())
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No recent learner activity recorded yet.</p>
                @else
                    <div class="mt-4 space-y-2">
                        @foreach($recentProgressTimeline as $timeline)
                            <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-3.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $timeline->lesson?->title ?? 'Lesson Activity' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Updated {{ optional($timeline->updated_at)->diffForHumans() }}</p>
                                <p class="text-xs mt-1 {{ $timeline->completed ? 'text-green-600' : 'text-amber-600' }}">{{ $timeline->completed ? 'Marked completed' : 'In progress' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
