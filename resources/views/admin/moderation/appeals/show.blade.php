@extends('layouts.admin')

@section('title', 'Appeal Review')
@section('page-title', 'Appeal Review')

@section('content')
    <div class="space-y-8">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.moderation-appeals.index') }}" class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Back to Appeals Queue
            </a>
        </div>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 px-6 py-5">
                <h2 class="text-xl font-bold text-gray-900">Appeal #{{ $appeal->id }}</h2>
                <p class="mt-1 text-sm text-gray-600">Status: {{ str_replace('_', ' ', (string) $appeal->status) }}</p>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Learner</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $appeal->user?->name ?? 'Unknown user' }}</p>
                    <p class="text-xs text-gray-500">{{ $appeal->user?->email ?? 'No email' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suspension Status</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst((string) ($appeal->suspension?->status ?? 'unknown')) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action Type</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', (string) ($appeal->suspension?->enforcementAction?->action_type?->value ?? 'n/a')) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Case Reference</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $appeal->suspension?->moderationCase?->case_reference_code ?? 'Not available' }}</p>
                </div>
            </div>

            <div class="border-t border-gray-100 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Appeal Reason</p>
                <p class="mt-2 text-sm text-gray-700">{{ $appeal->appeal_reason }}</p>
            </div>

            @if(!empty($appeal->review_decision_notes))
                <div class="border-t border-gray-100 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Review Decision Notes</p>
                    <p class="mt-2 text-sm text-gray-700">{{ $appeal->review_decision_notes }}</p>
                </div>
            @endif
        </section>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Review Decision</h3>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('admin.moderation-appeals.review', $appeal) }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Action</span>
                            <select name="action" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                                <option value="clarification_requested">Clarification Requested</option>
                            </select>
                        </label>
                    </div>

                    <div>
                        <label for="review_decision_notes" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Decision Notes</label>
                        <textarea id="review_decision_notes"
                                  name="review_decision_notes"
                                  rows="4"
                                  required
                                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"></textarea>
                    </div>

                    <button type="submit" class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        Record Review Decision
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Appeal Thread</h3>
            </div>

            <div class="space-y-3 px-6 py-5">
                @forelse($threadMessages as $message)
                    <article class="rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $message->sender_role }} • {{ $message->sender?->name ?? 'System' }} • {{ optional($message->created_at)?->format('M d, Y h:i A') }}
                        </p>
                        <p class="mt-1 text-sm text-gray-700">{{ $message->message_body }}</p>
                    </article>
                @empty
                    <p class="text-sm text-gray-600">No thread messages yet.</p>
                @endforelse
            </div>

            <div class="border-t border-gray-100 px-6 py-5">
                <form method="POST" action="{{ route('admin.moderation-appeals.thread.store', $appeal) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="message_body" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Post Response</label>
                        <textarea id="message_body"
                                  name="message_body"
                                  rows="4"
                                  required
                                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center rounded-xl border border-brand-200 bg-brand-50/70 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100/80">
                        Post Thread Response
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
