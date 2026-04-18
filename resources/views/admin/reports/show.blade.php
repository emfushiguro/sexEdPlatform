@extends('layouts.admin')

@section('title', 'Learner Report #' . $report->id)

@section('content')
<div class="space-y-6">
    @php
        $targetTypeValue = is_object($report->target_type) ? $report->target_type->value : (string) $report->target_type;
        $statusValue = is_object($report->status) ? $report->status->value : (string) $report->status;
    @endphp

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.learner-reports.index') }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Learner Report #{{ $report->id }}</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">Moderation detail and action timeline.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-5">
            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $targetTypeValue === 'module' ? 'bg-brand-100 text-brand-700' : 'bg-amber-100 text-amber-700' }}">{{ $targetTypeValue }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $statusValue === 'submitted' ? 'bg-rose-100 text-rose-700' : ($statusValue === 'under_review' ? 'bg-amber-100 text-amber-700' : ($statusValue === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700')) }}">
                        {{ is_object($report->status) ? $report->status->label() : ucfirst(str_replace('_', ' ', (string) $report->status)) }}
                    </span>
                </div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $targetTypeValue === 'module' ? ($targetModel?->title ?? ('Module #' . $report->target_id)) : ($targetModel?->name ?? ('Instructor #' . $report->target_id)) }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Reason: <strong>{{ str_replace('_', ' ', $report->reason_code) }}</strong></p>
                @if($report->details_html)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-3 prose prose-sm max-w-none dark:prose-invert">
                        {!! $report->details_html !!}
                    </div>
                @endif
                @if($report->latest_outcome_message)
                    <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                        {{ $report->latest_outcome_message }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Moderation Timeline</h3>
                <div class="space-y-3">
                    @forelse($report->activities as $activity)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at?->format('M d, Y h:i A') }}</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Actor: {{ $activity->actor?->name ?? 'System' }}</p>
                            @if($activity->action_code)
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">Action: {{ str_replace('_', ' ', $activity->action_code) }}</p>
                            @endif
                            @if($activity->notes)
                                <p class="text-sm text-gray-700 dark:text-gray-200 mt-2">{{ $activity->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No activity records yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-5 space-y-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Reporter</h3>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $report->reporter?->name ?? 'Unknown' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $report->reporter?->email }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Submitted {{ $report->created_at?->diffForHumans() }}</p>
            </div>

            <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Moderation Action</h3>
                <form method="POST" action="{{ route('admin.learner-reports.update', $report) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" required>
                            @foreach($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($statusValue === $statusOption->value)>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Action</label>
                        <select name="action" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" required>
                            @foreach($actions as $action)
                                <option value="{{ $action->value }}">{{ $action->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Moderation Notes</label>
                        <textarea name="moderation_notes" rows="4" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" placeholder="Internal moderation notes (optional)"></textarea>
                    </div>
                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                    >
                        Save Moderation Decision
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
