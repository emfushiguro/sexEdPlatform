@extends('layouts.admin')

@section('title', $feedback->reference_number)
@section('page-title', 'Review Support Ticket')

@section('content')
<main class="mx-auto max-w-6xl space-y-6 px-4 py-8">
    <nav aria-label="Breadcrumb"><a href="{{ route('admin.feedback.index') }}" class="text-sm font-semibold text-purple-700 hover:text-purple-900">← Back to Ticket Inbox</a></nav>

    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">{{ $feedback->reference_number }}</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">{{ $feedback->subject }}</h1><p class="mt-2 text-sm text-gray-500">Submitted {{ $feedback->created_at->format('M j, Y \a\t g:i A') }}</p></div>
        <x-support.status-badge :status="$feedback->status" />
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <div class="space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <x-support.sender-profile :user="$feedback->user" :role="$feedback->user_role" />
                <div class="flex flex-wrap gap-4 text-sm text-gray-600"><span class="inline-flex items-center gap-2"><x-support.type-icon :type="$feedback->type" class="h-4 w-4 text-purple-700" />{{ $feedback->type->label() }}</span>@if($feedback->rating)<x-reviews.heart-rating :rating="$feedback->rating" :show-numeric="false" size-class="h-5 w-5" />@endif</div>
                <h2 class="mt-6 text-sm font-semibold uppercase tracking-[0.12em] text-gray-500">Ticket details</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-gray-800">{{ $feedback->description }}</p>
                @if($feedback->attachment_path)
                    <figure data-feedback-attachment-preview class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                        <figcaption class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800">
                            <span>Submitted image</span>
                            <a href="{{ route('admin.feedback.attachment.show', $feedback) }}" target="_blank" rel="noopener" class="text-purple-700 hover:text-purple-900">Open full size</a>
                        </figcaption>
                        <a href="{{ route('admin.feedback.attachment.show', $feedback) }}" target="_blank" rel="noopener" class="block bg-white p-3">
                            <img data-feedback-attachment-preview-image src="{{ route('admin.feedback.attachment.show', $feedback) }}" alt="Submitted image for {{ $feedback->subject }}" class="max-h-[28rem] w-full rounded-xl object-contain">
                        </a>
                    </figure>
                @endif
            </section>
            <x-support.ticket-conversation :ticket="$feedback" :action="route('admin.feedback.messages.store', $feedback)" />
            @if($feedback->histories->isNotEmpty())
                <section aria-labelledby="ticket-history-heading" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 id="ticket-history-heading" class="text-lg font-bold text-gray-950">Status history</h2>
                    <ol class="mt-4 space-y-4">
                        @foreach($feedback->histories as $history)
                            @php
                                $historyStatus = \App\Enums\PlatformFeedbackStatus::tryFrom((string) $history->to_status);
                            @endphp
                            <li class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-gray-900">{{ $historyStatus?->label() ?? 'Updated' }}</span>
                                    <time class="text-xs text-gray-500" datetime="{{ $history->created_at?->toIso8601String() }}">{{ $history->created_at?->format('M j, Y g:i A') }}</time>
                                </div>
                                @if($history->actor)<p class="mt-1 text-xs text-gray-500">By {{ $history->actor->name }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        @php
            $hasAdminMessage = $feedback->messages->contains(fn ($message) => $message->sender_role === 'admin');
            $nextStatuses = match ($feedback->status) {
                \App\Enums\PlatformFeedbackStatus::New => array_values(array_filter([
                    \App\Enums\PlatformFeedbackStatus::Reviewed,
                    $hasAdminMessage ? \App\Enums\PlatformFeedbackStatus::Resolved : null,
                    \App\Enums\PlatformFeedbackStatus::Closed,
                ])),
                \App\Enums\PlatformFeedbackStatus::Reviewed => array_values(array_filter([
                    $hasAdminMessage ? \App\Enums\PlatformFeedbackStatus::Resolved : null,
                    \App\Enums\PlatformFeedbackStatus::Closed,
                ])),
                \App\Enums\PlatformFeedbackStatus::Resolved => [\App\Enums\PlatformFeedbackStatus::Reviewed, \App\Enums\PlatformFeedbackStatus::Closed],
                default => [],
            };
        @endphp
        <aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
            <h2 class="text-lg font-bold text-gray-950">Ticket status</h2>
            <div class="mt-4 rounded-xl border border-purple-100 bg-purple-50 p-4">
                <x-support.status-badge :status="$feedback->status" />
                <p class="mt-2 text-xs leading-5 text-gray-600">
                    @if($feedback->status === \App\Enums\PlatformFeedbackStatus::Resolved) The author may reply and return this ticket to In Review.
                    @elseif($feedback->status === \App\Enums\PlatformFeedbackStatus::Closed) This ticket is final and cannot receive replies.
                    @elseif($feedback->status === \App\Enums\PlatformFeedbackStatus::Withdrawn) The author withdrew this ticket before review.
                    @else Use the actions below as the review progresses.
                    @endif
                </p>
            </div>

            @if($nextStatuses !== [])
                <div class="mt-5 space-y-3">
                    @foreach($nextStatuses as $status)
                        <form method="POST" action="{{ route('admin.feedback.update', $feedback) }}" @if($status === \App\Enums\PlatformFeedbackStatus::Closed) data-confirm-submit data-confirm-title="Close this ticket?" data-confirm-text="Closing is final and stops all replies." data-confirm-button="Close ticket" @endif>
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="{{ $status->value }}">
                            <button type="submit" @class([
                                'min-h-11 w-full rounded-xl px-4 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2',
                                'border border-rose-200 text-rose-700 hover:bg-rose-50 focus:ring-rose-400' => $status === \App\Enums\PlatformFeedbackStatus::Closed,
                                'bg-brand-700 text-white hover:bg-brand-900 focus:ring-purple-400' => $status !== \App\Enums\PlatformFeedbackStatus::Closed,
                            ])>
                                {{ match ($status) {
                                    \App\Enums\PlatformFeedbackStatus::Reviewed => 'Move to In Review',
                                    \App\Enums\PlatformFeedbackStatus::Resolved => 'Mark as resolved',
                                    \App\Enums\PlatformFeedbackStatus::Closed => 'Close ticket',
                                    default => $status->label(),
                                } }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
</main>
@endsection
