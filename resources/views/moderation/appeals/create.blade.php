@extends('layouts.moderation')

@section('title', 'Suspension Appeal | Conscious Connections')

@section('content')
@php
    $hasAppeal = $suspension->appeals->isNotEmpty();
    $needsClarification = $suspension->appeals->sortByDesc('id')->first()?->status === 'clarification_requested';
    $showAppealForm = ! $hasAppeal || ($needsClarification && request()->boolean('clarify'));
@endphp
<div>
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">{{ $showAppealForm ? 'Submit Suspension Appeal' : 'Suspension Appeal' }}</h1>
            <p class="mt-2 text-sm text-gray-600">
                {{ $showAppealForm ? 'Provide a clear explanation for moderator review. Appeals are processed in order and may request clarification.' : 'Your appeal has been submitted. View its status and history below.' }}
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suspension Status</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst((string) $suspension->status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Appeal Status</p>
                    <p class="mt-1 text-sm text-gray-800">{{ str_replace('_', ' ', (string) $suspension->appeal_status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Case Reference</p>
                    <p class="mt-1 text-sm text-gray-800">{{ $suspension->moderationCase?->case_reference_code ?? 'Not available' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Duration</p>
                    <p class="mt-1 text-sm text-gray-800">
                        {{ $suspension->starts_at?->format('M d, Y h:i A') ?? '-' }} - {{ $suspension->ends_at?->format('M d, Y h:i A') ?? 'Permanent' }}
                    </p>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if($showAppealForm)
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('moderation.appeals.store', $suspension) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="appeal_reason" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Appeal reason</label>
                    <textarea id="appeal_reason"
                              name="appeal_reason"
                              rows="6"
                              required
                              class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">{{ old('appeal_reason') }}</textarea>
                </div>

                <div>
                    <label for="evidence_notes" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Evidence notes (optional)</label>
                    <textarea id="evidence_notes"
                              name="evidence_payload[notes]"
                              rows="3"
                              class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">{{ old('evidence_payload.notes') }}</textarea>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Submit Appeal
                    </button>
                    <a href="{{ route('moderation.suspension-status') }}" class="inline-flex min-h-11 items-center rounded-xl border border-purple-300 bg-white px-4 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Back to Suspension Status
                    </a>
                </div>
            </form>
        </div>
        @else
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('moderation.suspension-status') }}" class="inline-flex min-h-11 items-center rounded-xl border border-purple-300 bg-white px-4 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50">Back to Suspension Status</a>
                @if($needsClarification)
                    <a href="{{ route('moderation.appeals.create', [$suspension, 'clarify' => 1]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Provide clarification</a>
                @endif
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900">Appeal History</h2>

            <div class="mt-4 space-y-4">
                @forelse($suspension->appeals->sortByDesc('id') as $appeal)
                    <article class="rounded-xl border border-gray-200 bg-gray-50/50 p-4">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span class="rounded-full bg-white px-2.5 py-1 font-semibold text-gray-700">{{ str_replace('_', ' ', (string) $appeal->status) }}</span>
                            <span>Submitted {{ optional($appeal->submitted_at)?->format('M d, Y h:i A') ?? '-' }}</span>
                        </div>
                        <p class="mt-3 text-sm text-gray-800">{{ $appeal->appeal_reason }}</p>

                        @if($appeal->threadMessages->isNotEmpty())
                            <div class="mt-4 space-y-2 border-t border-gray-200 pt-3">
                                @foreach($appeal->threadMessages->sortBy('id') as $message)
                                    <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $message->sender_role }} • {{ $message->sender?->name ?? 'System' }}</p>
                                        <p class="mt-1">{{ $message->message_body }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-gray-600">No appeals submitted yet for this suspension.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
