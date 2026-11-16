@extends('layouts.admin')
@section('title', 'Appeal Review')
@section('page-title', 'Appeal Review')

@section('content')
@php
    $canReviewAppeal = ! in_array($appeal->status, ['approved', 'rejected'], true);
@endphp
@include('admin.moderation.partials.workspace-styles')
<div class="moderation-workspace space-y-6">
    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.moderation-appeals.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-brand-700 hover:underline">&larr; Back to appeals queue</a>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">Appeal #{{ $appeal->id }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ $canReviewAppeal ? 'Review the context, follow the discussion and record your decision.' : 'View the recorded decision and appeal history.' }}</p>
        </div>
        @if($appeal->suspension)
            <a href="{{ route('admin.moderation-suspensions.show', $appeal->suspension) }}" data-testid="appeal-suspension" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-white px-4 py-3 text-sm font-semibold text-brand-700 hover:bg-brand-50">View suspension</a>
        @endif
    </header>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="appeal-summary-title">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-6">
            <h3 id="appeal-summary-title" class="font-semibold text-gray-900">Appeal overview</h3>
            @include('admin.moderation.partials.appeal-status', ['appealStatusValue' => $appeal->status])
        </div>
        <dl class="grid gap-5 p-4 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
            <div class="min-w-0"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">User</dt><dd class="mt-2 break-words text-sm font-semibold text-gray-900">{{ $appeal->user?->name ?? 'Unknown user' }}<span class="mt-1 block break-all text-xs font-normal text-gray-500">{{ $appeal->user?->email ?? 'No email' }}</span></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suspension</dt><dd class="mt-2 text-sm font-semibold text-gray-900">{{ ucfirst((string) ($appeal->suspension?->status ?? 'Unavailable')) }}<span class="mt-1 block text-xs font-normal text-gray-500">{{ ucfirst(str_replace('_', ' ', (string) ($appeal->suspension?->enforcementAction?->action_type?->value ?? 'Action unavailable'))) }}</span></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Case reference</dt><dd class="mt-2 break-words text-sm font-semibold text-gray-900">{{ $appeal->suspension?->moderationCase?->case_reference_code ?? 'Not available' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</dt><dd class="mt-2 text-sm font-semibold text-gray-900">{{ $appeal->submitted_at?->format('M d, Y g:i A') ?? 'Not available' }}</dd></div>
        </dl>
        <div class="border-t border-gray-100 px-4 py-4 text-sm text-gray-600 sm:px-6">
            <span class="font-semibold text-gray-700">Suspension window:</span>
            {{ $appeal->suspension?->starts_at?->format('M d, Y g:i A') ?? 'Start unavailable' }}
            &ndash; {{ $appeal->suspension?->ends_at?->format('M d, Y g:i A') ?? ($appeal->suspension ? 'No scheduled end' : 'End unavailable') }}
        </div>
    </section>

    <div class="grid min-w-0 items-start gap-6 {{ $canReviewAppeal ? 'xl:grid-cols-3' : '' }}">
        <div class="min-w-0 space-y-6 {{ $canReviewAppeal ? 'xl:col-span-2' : '' }}">
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h3 class="font-semibold text-gray-900">Appeal reason</h3>
                <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-gray-700">{{ $appeal->appeal_reason }}</p>
                @if(filled($appeal->review_decision_notes))
                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <h4 class="text-sm font-semibold text-gray-900">Recorded decision notes</h4>
                        <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-7 text-gray-700">{{ $appeal->review_decision_notes }}</p>
                        <p class="mt-3 text-xs text-gray-500">{{ $appeal->reviewedByAdmin?->name ?? 'Administrator' }} &middot; {{ $appeal->reviewed_at?->format('M d, Y g:i A') ?? 'Date unavailable' }}</p>
                    </div>
                @endif
            </section>
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="appeal-discussion-title">
                <div class="border-b border-gray-100 px-4 py-4 sm:px-6"><h3 id="appeal-discussion-title" class="font-semibold text-gray-900">Appeal discussion <span class="ml-2 text-sm font-normal text-gray-500">{{ $threadMessages->count() }} {{ $threadMessages->count() === 1 ? 'message' : 'messages' }}</span></h3></div>
                <div class="space-y-4 p-4 sm:p-6">
                    @forelse($threadMessages as $message)
                        <article class="rounded-xl border {{ $message->sender_role === 'admin' ? 'border-purple-100 bg-purple-50/50' : 'border-gray-200 bg-gray-50' }} p-4">
                            <header class="flex flex-wrap justify-between gap-2 text-xs text-gray-500"><span class="break-words font-semibold text-gray-700">{{ $message->sender?->name ?? 'Unavailable user' }} &middot; {{ ucfirst($message->sender_role) }}</span><time datetime="{{ $message->created_at?->toIso8601String() }}">{{ $message->created_at?->format('M d, Y g:i A') }}</time></header>
                            <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-7 text-gray-700">{{ $message->message_body }}</p>
                        </article>
                    @empty
                        <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500">No messages in this appeal.</p>
                    @endforelse
                </div>
            </section>
        </div>
        @if($canReviewAppeal)
        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="review-decision-title">
            <h3 id="review-decision-title" class="font-semibold text-gray-900">Review decision</h3>
            <p class="mt-2 text-sm leading-6 text-gray-500">Review the appeal and suspension context before recording an outcome.</p>
            <form method="POST" action="{{ route('admin.moderation-appeals.review', $appeal) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="appeal_action" class="mb-2 block text-sm font-semibold text-gray-700">Decision</label>
                    <select id="appeal_action" name="action" required @if($errors->has('action')) aria-invalid="true" aria-describedby="appeal-action-error" @endif class="min-h-11 w-full rounded-xl border-gray-300 bg-white px-3 py-3 text-sm focus:border-purple-600 focus:ring-purple-600">
                        <option value="">Select a decision</option>
                        @foreach(['approve' => 'Approve appeal', 'reject' => 'Reject appeal', 'clarification_requested' => 'Request clarification'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('action') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('action')<p id="appeal-action-error" role="alert" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="review_decision_notes" class="mb-2 block text-sm font-semibold text-gray-700">Decision notes</label>
                    <textarea id="review_decision_notes" name="review_decision_notes" rows="5" required minlength="5" maxlength="5000" @if($errors->has('review_decision_notes')) aria-invalid="true" aria-describedby="decision-notes-error" @endif class="w-full rounded-xl border-gray-300 px-3 py-3 text-sm focus:border-purple-600 focus:ring-purple-600">{{ old('review_decision_notes') }}</textarea>
                    @error('review_decision_notes')<p id="decision-notes-error" role="alert" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-800">Record review decision</button>
            </form>
        </section>
        @endif
    </div>
</div>
@endsection
