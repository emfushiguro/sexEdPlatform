@extends($supportLayout)

@section('title', $feedback->subject.' | My Tickets')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-10">
        <nav aria-label="Breadcrumb" class="mt-7 text-sm">
            <a href="{{ route($supportRoutes['feedback']['index']['name'], $supportRoutes['feedback']['index']['parameters']) }}" class="font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300">← Back to My Tickets</a>
        </nav>

        @if(session('success'))
            <div role="status" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                <p class="font-semibold">{{ session('success') }}</p>
                <p class="mt-1">You can return here any time to track progress.</p>
            </div>
        @endif

        <div class="mt-7">
            <x-support.page-header icon="feedback" eyebrow="Support ticket" :title="$feedback->subject" :description="'Reference '.$feedback->reference_number" />
        </div>

        <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
            <div class="space-y-6">
                <section class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-support.status-badge :status="$feedback->status" />
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300"><x-support.type-icon :type="$feedback->type" class="h-4 w-4 text-purple-700 dark:text-purple-300" />{{ $feedback->type->label() }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Submitted {{ $feedback->created_at->format('M j, Y \a\t g:i A') }}</span>
                    </div>

                    <h2 class="mt-7 text-sm font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Your ticket</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-gray-800 sm:text-base dark:text-gray-200">{{ $feedback->description }}</p>

                    <dl class="mt-6 grid gap-4 border-t border-gray-100 pt-6 text-sm sm:grid-cols-2 dark:border-gray-800">
                        @if($feedback->rating)
                            <div><dt class="font-medium text-gray-500 dark:text-gray-400">Experience rating</dt><dd class="mt-1"><x-reviews.heart-rating :rating="$feedback->rating" :show-numeric="false" size-class="h-5 w-5" /></dd></div>
                        @endif
                        <div><dt class="font-medium text-gray-500 dark:text-gray-400">Follow-up permission</dt><dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $feedback->may_contact ? 'You may contact me' : 'No follow-up requested' }}</dd></div>
                    </dl>

                    @if($feedback->attachment_path)
                        @php($attachmentUrl = route($supportRoutes['feedback']['attachment']['name'], array_merge($supportRoutes['feedback']['attachment']['parameters'], ['platformFeedback' => $feedback])))
                        <figure data-feedback-attachment-preview class="mt-6 overflow-hidden rounded-2xl border border-purple-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                            <figcaption class="flex flex-wrap items-center justify-between gap-3 border-b border-purple-100 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-800 dark:text-gray-200">
                                <span>Submitted image</span>
                                <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="text-purple-700 hover:text-purple-900 dark:text-purple-300">Open full size</a>
                            </figcaption>
                            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="block bg-white p-3 dark:bg-gray-900">
                                <img data-feedback-attachment-preview-image src="{{ $attachmentUrl }}" alt="Submitted image for {{ $feedback->subject }}" class="max-h-[28rem] w-full rounded-xl object-contain">
                            </a>
                        </figure>
                    @endif
                </section>

                <x-support.ticket-conversation
                    :ticket="$feedback"
                    :action="route($supportRoutes['feedback']['message_store']['name'], array_merge($supportRoutes['feedback']['message_store']['parameters'], ['platformFeedback' => $feedback]))"
                />

                @if($feedback->histories->isNotEmpty())
                    <section aria-labelledby="ticket-history-heading" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900">
                        <h2 id="ticket-history-heading" class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Status history</h2>
                        <ol class="mt-4 space-y-3">
                            @foreach($feedback->histories as $history)
                                @php($historyStatus = \App\Enums\PlatformFeedbackStatus::tryFrom((string) $history->to_status))
                                <li class="flex items-start justify-between gap-4 border-l-2 border-purple-200 pl-4 text-sm dark:border-purple-800">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $historyStatus?->label() ?? 'Updated' }}</span>
                                    <time class="shrink-0 text-xs text-gray-500 dark:text-gray-400" datetime="{{ $history->created_at?->toIso8601String() }}">{{ $history->created_at?->format('M j, Y') }}</time>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if($feedback->status === \App\Enums\PlatformFeedbackStatus::New)
                    <form method="POST" action="{{ route($supportRoutes['feedback']['withdraw']['name'], array_merge($supportRoutes['feedback']['withdraw']['parameters'], ['platformFeedback' => $feedback])) }}" data-confirm-submit data-confirm-title="Withdraw this ticket?" data-confirm-text="You will not be able to reopen or reply to this ticket." data-confirm-button="Withdraw ticket" class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        @csrf @method('DELETE')
                        <button class="min-h-11 rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950">Withdraw ticket</button>
                    </form>
                @endif
            </div>

            <aside class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm lg:sticky lg:top-6 dark:border-gray-800 dark:bg-gray-900" aria-labelledby="progress-heading">
                <h2 id="progress-heading" class="font-semibold text-gray-950 dark:text-white">Ticket progress</h2>
                @if(in_array($feedback->status, [\App\Enums\PlatformFeedbackStatus::Closed, \App\Enums\PlatformFeedbackStatus::Withdrawn], true))
                    <div data-feedback-current-status="{{ $feedback->status->value }}" class="mt-4 rounded-xl border border-gray-300 bg-gray-100 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $feedback->status->label() }}</p>
                        <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-400">This ticket is no longer active.</p>
                    </div>
                @else
                    <ol class="mt-4 space-y-3">
                        @foreach([\App\Enums\PlatformFeedbackStatus::New, \App\Enums\PlatformFeedbackStatus::Reviewed, \App\Enums\PlatformFeedbackStatus::Resolved, \App\Enums\PlatformFeedbackStatus::Closed] as $position)
                            <li @if($feedback->status === $position) data-feedback-current-status="{{ $position->value }}" @endif @class(['rounded-xl border px-3 py-3 text-sm', 'border-brand-700 bg-purple-50 font-semibold text-purple-900 dark:bg-purple-950 dark:text-purple-100' => $feedback->status === $position, 'border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400' => $feedback->status !== $position])>
                                {{ $position->label() }}
                                @if($feedback->status === $position)<span class="mt-1 block text-xs font-normal">Current status</span>@endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </aside>
        </div>
    </div>
</main>
@endsection
