@props([
    'ticket',
    'action',
])

@php
    $viewer = auth()->user();
    $isAdmin = (bool) ($viewer?->hasRole('admin') || $viewer?->role === 'admin');
    $isOwner = (int) $viewer?->id === (int) $ticket->user_id;
    $hasAdminMessage = $ticket->messages->contains(fn ($message) => $message->sender_role === 'admin');
    $isOpenForMessages = in_array($ticket->status, [
        \App\Enums\PlatformFeedbackStatus::New,
        \App\Enums\PlatformFeedbackStatus::Reviewed,
        \App\Enums\PlatformFeedbackStatus::Resolved,
    ], true);
    $canReply = $isOpenForMessages && ($isAdmin ? (bool) $ticket->may_contact : ($isOwner && $hasAdminMessage));
@endphp

<section data-ticket-conversation aria-labelledby="ticket-conversation-heading" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700 dark:text-purple-300">Private conversation</p>
            <h2 id="ticket-conversation-heading" class="mt-1 text-lg font-bold text-gray-950 dark:text-white">Messages</h2>
        </div>
        <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950 dark:text-purple-300">{{ $ticket->messages->count() }} {{ \Illuminate\Support\Str::plural('message', $ticket->messages->count()) }}</span>
    </div>

    <div class="mt-5 space-y-4">
        @forelse($ticket->messages as $message)
            @php
                $fromAdmin = $message->sender_role === 'admin';
                $senderName = $fromAdmin
                    ? ($message->sender?->name ?: 'Platform team')
                    : ($message->sender?->name ?: 'Ticket author');
                $roleLabel = $fromAdmin ? 'Platform team' : ucfirst($message->sender_role);
            @endphp
            <article @class(['flex', 'justify-start' => $fromAdmin, 'justify-end' => ! $fromAdmin])>
                <div @class([
                    'max-w-[90%] rounded-2xl px-4 py-3 sm:max-w-[78%]',
                    'rounded-bl-md border border-purple-100 bg-purple-50 text-gray-900 dark:border-purple-900 dark:bg-purple-950/50 dark:text-gray-100' => $fromAdmin,
                    'rounded-br-md bg-brand-700 text-white' => ! $fromAdmin,
                ])>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span class="font-bold">{{ $senderName }}</span>
                        <span @class(['text-purple-700 dark:text-purple-300' => $fromAdmin, 'text-purple-100' => ! $fromAdmin])>{{ $roleLabel }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line break-words text-sm leading-6">{{ $message->body }}</p>
                    <time datetime="{{ $message->created_at?->toIso8601String() }}" @class(['mt-2 block text-[11px]', 'text-gray-500 dark:text-gray-400' => $fromAdmin, 'text-purple-100' => ! $fromAdmin])>{{ $message->created_at?->format('M j, Y \a\t g:i A') }}</time>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">No messages yet</p>
                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">The platform team will reply here after reviewing the ticket.</p>
            </div>
        @endforelse
    </div>

    @if($canReply)
        <form method="POST" action="{{ $action }}" class="mt-6 border-t border-gray-100 pt-5 dark:border-gray-800" x-data="{ submitting: false, messageLength: {{ mb_strlen((string) old('body', '')) }} }" @submit="submitting = true" :aria-busy="submitting.toString()">
            @csrf
            <div class="flex items-end justify-between gap-3">
                <label for="ticket-message-body-{{ $ticket->id }}" class="text-sm font-semibold text-gray-800 dark:text-gray-200">Reply</label>
                <span class="text-xs text-gray-400"><span x-text="messageLength">{{ mb_strlen((string) old('body', '')) }}</span>/1,000</span>
            </div>
            <textarea id="ticket-message-body-{{ $ticket->id }}" name="body" rows="4" maxlength="1000" required x-on:input="messageLength = $event.target.value.length" aria-describedby="ticket-message-help-{{ $ticket->id }} @error('body') ticket-message-error-{{ $ticket->id }} @enderror" class="mt-2 w-full rounded-xl border-gray-300 text-sm leading-6 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('body') }}</textarea>
            <p id="ticket-message-help-{{ $ticket->id }}" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Messages are private between the ticket author and the platform team.</p>
            @error('body')<p id="ticket-message-error-{{ $ticket->id }}" class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
            <div class="mt-4 flex justify-end">
                <button type="submit" :disabled="submitting" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                    <span x-show="!submitting">Send reply</span>
                    <span x-cloak x-show="submitting">Sending…</span>
                </button>
            </div>
        </form>
    @elseif($isAdmin && ! $ticket->may_contact && $isOpenForMessages)
        <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">Follow-up was not requested. The platform team cannot send a reply to this ticket.</p>
    @elseif($isOwner && ! $hasAdminMessage && $isOpenForMessages)
        <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">You can reply after the platform team sends its first response.</p>
    @else
        <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">This conversation is closed to new replies.</p>
    @endif
</section>
