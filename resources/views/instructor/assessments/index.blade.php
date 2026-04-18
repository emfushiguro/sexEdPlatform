@extends('layouts.instructor-app')

@section('title', 'Assessment Insights')

@section('content')
<div class="space-y-6">
    @php
        $attemptRows = collect($attemptCountByLearner ?? []);
        $previewRows = collect($recentAttemptPreviews ?? []);
        $totalAttempts = (int) $attemptRows->sum('attempt_count');
        $uniqueLearners = (int) $attemptRows->count();
        $overallAverage = $attemptRows->avg('avg_score');
        $averageScore = $overallAverage !== null ? number_format((float) $overallAverage, 1) : '0.0';
        $passRate = $previewRows->count() > 0
            ? number_format(((int) $previewRows->where('passed', true)->count() / (int) $previewRows->count()) * 100, 1)
            : '0.0';
    @endphp

    <div class="rounded-2xl border border-brand-100 bg-white p-6 shadow-theme-xs">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Assessment Insights</h1>
                <p class="mt-1 text-sm text-gray-500">Per-module score distribution, learner attempt previews, and at-risk learner visibility.</p>
            </div>

            <form method="GET" action="{{ route('instructor.assessments.index') }}" class="grid gap-2 sm:grid-cols-2 lg:w-auto">
                <label class="block">
                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Low Score Threshold</span>
                    <input type="number" name="low_score_threshold" min="0" max="100" value="{{ $assessmentThresholds['low_score_threshold'] }}" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                </label>
                <div class="flex items-end gap-2">
                    <label class="block w-full">
                        <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Low Activity Threshold</span>
                        <input type="number" name="low_activity_threshold" min="0" max="50" value="{{ $assessmentThresholds['low_activity_threshold'] }}" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                    </label>
                    <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white transition hover:bg-brand-700">Apply</button>
                </div>
            </form>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-brand-100 bg-brand-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Attempts</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($totalAttempts) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Unique Learners</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($uniqueLearners) }}</p>
            </div>
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700">Average Score</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $averageScore }}%</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Recent Pass Rate</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $passRate }}%</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5" data-testid="assessment-score-distribution">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Per-Module Score Distribution</h2>
        @if(empty($scoreDistributionByModule))
            <p class="text-sm text-gray-400">No quiz attempts yet for your modules.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                            <th class="py-2 pr-4">Module</th>
                            <th class="py-2 pr-4 text-right">Low</th>
                            <th class="py-2 pr-4 text-right">Mid</th>
                            <th class="py-2 pr-4 text-right">High</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($scoreDistributionByModule as $row)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-4 font-medium text-gray-900">{{ $row['module_title'] }}</td>
                                <td class="py-2 pr-4 text-right text-red-600">{{ $row['low_band'] }}</td>
                                <td class="py-2 pr-4 text-right text-amber-600">{{ $row['mid_band'] }}</td>
                                <td class="py-2 pr-4 text-right text-green-600">{{ $row['high_band'] }}</td>
                                <td class="py-2 text-right text-gray-700">{{ $row['total_attempts'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5" data-testid="assessment-attempt-count-table">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Attempt Count per Learner</h2>
        @if(empty($attemptCountByLearner))
            <p class="text-sm text-gray-400">No learner attempt data yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                            <th class="py-2 pr-4">Learner</th>
                            <th class="py-2 pr-4 text-right">Attempts</th>
                            <th class="py-2 pr-4 text-right">Avg Score</th>
                            <th class="py-2 text-right">Last Attempt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attemptCountByLearner as $row)
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-4 font-medium text-gray-900">{{ $row['learner_name'] }}</td>
                                <td class="py-2 pr-4 text-right text-gray-700">{{ $row['attempt_count'] }}</td>
                                <td class="py-2 pr-4 text-right text-gray-700">{{ $row['avg_score'] }}%</td>
                                <td class="py-2 text-right text-gray-500">
                                    @if(!empty($row['last_attempt_at']))
                                        {{ Illuminate\Support\Carbon::parse($row['last_attempt_at'])->diffForHumans() }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5" data-testid="assessment-attempt-preview-table">
        <h2 class="text-sm font-semibold text-gray-900 mb-1">Recent Attempt Previews</h2>
        <p class="text-xs text-gray-500 mb-4">Preview each attempt with learner answers, expected answers, and correctness per question.</p>

        @if(empty($recentAttemptPreviews ?? []))
            <p class="text-sm text-gray-400">No attempt previews are available yet.</p>
        @else
            <div class="space-y-3">
                @foreach(($recentAttemptPreviews ?? []) as $attempt)
                    <details class="group rounded-xl border border-gray-200 bg-gray-50/50 overflow-hidden">
                        <summary class="cursor-pointer list-none px-4 py-3">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $attempt['learner_name'] }}
                                        <span class="text-gray-400 font-normal">· {{ $attempt['quiz_title'] }}</span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $attempt['module_title'] }}
                                        @if(!empty($attempt['attempted_at']))
                                            · {{ Illuminate\Support\Carbon::parse($attempt['attempted_at'])->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold {{ $attempt['passed'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $attempt['passed'] ? 'Passed' : 'Failed' }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-0.5 font-semibold">
                                        Score {{ $attempt['score'] }}%
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-0.5 font-semibold">
                                        {{ $attempt['correct_answers'] }}/{{ $attempt['total_questions'] }} correct
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-0.5 font-semibold">
                                        {{ $attempt['incorrect_answers'] ?? max(0, (int) ($attempt['total_questions'] ?? 0) - (int) ($attempt['correct_answers'] ?? 0)) }} incorrect
                                    </span>
                                </div>
                            </div>
                        </summary>

                        <div class="border-t border-gray-200 bg-white p-4">
                            @if(empty($attempt['questions'] ?? []))
                                <p class="text-sm text-gray-400">No answer detail available for this attempt.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                                                <th class="py-2 pr-4">Question</th>
                                                <th class="py-2 pr-4">Learner Answer</th>
                                                <th class="py-2 pr-4">Correct Answer</th>
                                                <th class="py-2 text-right">Result</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($attempt['questions'] ?? []) as $question)
                                                <tr class="border-b border-gray-50 align-top">
                                                    <td class="py-2 pr-4">
                                                        <p class="font-medium text-gray-900">{{ $question['question_text'] }}</p>
                                                        <p class="text-xs text-gray-400 mt-0.5">{{ str_replace('_', ' ', $question['question_type']) }}</p>
                                                    </td>
                                                    <td class="py-2 pr-4 text-gray-700">{{ $question['learner_answer'] }}</td>
                                                    <td class="py-2 pr-4 text-gray-700">{{ $question['correct_answer'] }}</td>
                                                    <td class="py-2 text-right">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $question['is_correct'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                            {{ $question['is_correct'] ? 'Correct' : 'Incorrect' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-5" data-testid="assessment-at-risk-table">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">At-Risk Learners</h2>
        @if(empty($atRiskLearners))
            <p class="text-sm text-gray-400">No learners currently flagged at risk.</p>
        @else
            <ul class="space-y-2">
                @foreach($atRiskLearners as $row)
                    <li class="rounded-xl border border-red-100 bg-red-50 px-3 py-2 flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-red-700">{{ $row['learner_name'] }}</span>
                        <span class="text-xs text-red-600">{{ $row['attempt_count'] }} attempts · {{ $row['avg_score'] }}% average</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
