@extends('layouts.admin')

@section('title', 'Moderation Report Details')
@section('page-title', 'Moderation Report Details')

@php
    $sourceLabel = is_object($case->case_source) && method_exists($case->case_source, 'label')
        ? $case->case_source->label()
        : str_replace('_', ' ', ucwords((string) $case->case_source));
    $caseStatus = is_object($case->status) ? $case->status->value : (string) $case->status;
@endphp

@section('content')
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.moderation-suspensions.index') }}" class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Suspension Dashboard
            </a>
        </div>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">{{ $sourceLabel }}</p>
                <h2 class="mt-2 text-xl font-bold text-gray-900">Case {{ $case->case_reference_code }}</h2>
                <p class="mt-1 text-sm text-gray-600">Centralized report review with escalation into enforcement and suspension workflow.</p>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reporter</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $case->reporter?->name ?? 'Unknown reporter' }}</p>
                    <p class="text-xs text-gray-500">{{ $case->reporter?->email ?? 'No email' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reported User</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $case->reportedUser?->name ?? 'Unknown user' }}</p>
                    <p class="text-xs text-gray-500">{{ $case->reportedUser?->email ?? 'No email' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Case Status</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', ucfirst($caseStatus)) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reported</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ optional($sourceReport?->created_at ?? $case->created_at)?->format('M d, Y g:i A') }}</p>
                </div>
            </div>
        </section>

        @if($sourceType === 'chat' && $sourceReport)
            @php
                $reasonCode = $sourceReport->reason_code instanceof \BackedEnum
                    ? $sourceReport->reason_code->value
                    : (string) $sourceReport->reason_code;
            @endphp

            <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-brand-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Reported Message</h3>
                    <p class="mt-1 text-sm text-gray-600">Conversation #{{ $sourceReport->conversation_id }}</p>
                </div>
                <div class="space-y-5 px-6 py-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Message Content</p>
                        <p class="mt-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-800">{{ $sourceReport->message?->message_body ?? 'Message content unavailable.' }}</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reason</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', ucfirst($reasonCode)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Custom Reason</p>
                            <p class="mt-1 text-sm text-gray-700">{{ $sourceReport->custom_reason ?: 'None provided' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-brand-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Conversation Context</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($conversationMessages as $message)
                        <div class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span class="font-semibold text-gray-700">{{ $message->sender?->name ?? 'Unknown sender' }}</span>
                                <span>{{ $message->sender?->email }}</span>
                                <span>{{ optional($message->created_at)?->format('M d, Y g:i A') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-800">{{ $message->message_body }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-gray-500">No conversation messages are available.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-brand-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Moderation Action</h3>
                </div>
                <form method="POST" action="{{ route('admin.moderation-suspensions.reports.update', $case) }}" class="grid gap-4 px-6 py-6 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                        <select name="status" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm">
                            @foreach($messageStatuses as $statusOption)
                                <option value="{{ $statusOption }}" @selected((string) $sourceReport->status === $statusOption)>{{ str_replace('_', ' ', ucfirst($statusOption)) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Action</span>
                        <select name="action_taken" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm">
                            @foreach($messageActions as $action)
                                <option value="{{ $action->value }}" @selected((string) $sourceReport->action_taken === $action->value)>{{ $action->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Moderation Notes</span>
                        <textarea name="moderation_notes" rows="4" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm">{{ old('moderation_notes', $sourceReport->moderation_notes) }}</textarea>
                    </label>
                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-brand-200 bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                            Record Decision
                        </button>
                    </div>
                </form>
            </section>
        @elseif($sourceType === 'learner' && $sourceReport)
            @php
                $targetType = $sourceReport->target_type instanceof \BackedEnum
                    ? $sourceReport->target_type->value
                    : (string) $sourceReport->target_type;
                $reportStatus = $sourceReport->status instanceof \BackedEnum
                    ? $sourceReport->status->value
                    : (string) $sourceReport->status;
            @endphp

            <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-brand-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Learner Report Context</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ ucfirst($targetType) }} report</p>
                </div>
                <div class="space-y-5 px-6 py-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Target</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $targetModel?->title ?? $targetModel?->name ?? 'Reported target unavailable' }}</p>
                            @if($targetModel instanceof \App\Models\Module && $targetModel->creator)
                                <p class="text-xs text-gray-500">{{ $targetModel->creator?->email }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reason</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', (string) $sourceReport->reason_code) }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Report Details</p>
                        <div class="mt-2 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-800">
                            {!! $sourceReport->details_html ?: 'No details provided.' !!}
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-brand-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Moderation Action</h3>
                </div>
                <form method="POST" action="{{ route('admin.moderation-suspensions.reports.update', $case) }}" class="grid gap-4 px-6 py-6 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                        <select name="status" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm">
                            @foreach($contentStatuses as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($reportStatus === $statusOption->value)>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Action</span>
                        <select name="action" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm">
                            @foreach($contentActions as $action)
                                <option value="{{ $action->value }}">{{ $action->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Moderation Notes</span>
                        <textarea name="moderation_notes" rows="4" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm">{{ old('moderation_notes') }}</textarea>
                    </label>
                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-brand-200 bg-brand-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                            Record Decision
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-brand-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-gray-900">Report History</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($sourceReport->activities as $activity)
                        <div class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', ucfirst((string) $activity->activity_type)) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $activity->actor?->email ?? 'System' }} • {{ optional($activity->created_at)?->format('M d, Y g:i A') }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-sm text-gray-500">No learner report history is available.</div>
                    @endforelse
                </div>
            </section>
        @else
            <section class="rounded-[30px] border border-gray-200 bg-white px-6 py-10 text-center text-sm text-gray-500 shadow-theme-xs">
                The source report for this case is no longer available.
            </section>
        @endif
    </div>
@endsection
