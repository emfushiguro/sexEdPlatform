@extends('layouts.instructor-app')

@section('title', 'Assessment Insights')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h1 class="text-xl font-semibold text-gray-900">Assessment Insights</h1>
        <p class="text-sm text-gray-500 mt-1">Per-module score distribution, learner attempt counts, and at-risk flags.</p>
        <div class="mt-3 text-xs text-gray-500">
            Low score threshold: <span class="font-semibold text-gray-700">{{ $assessmentThresholds['low_score_threshold'] }}</span>
            · Low activity threshold: <span class="font-semibold text-gray-700">{{ $assessmentThresholds['low_activity_threshold'] }}</span>
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
                                <td class="py-2 text-right text-gray-500">{{ \Illuminate\Support\Carbon::parse($row['last_attempt_at'])->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
