@extends('layouts.admin')

@section('title', 'Ticket Inbox')
@section('page-title', 'Ticket Inbox')

@section('content')
<main class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <header>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Support management</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Ticket Inbox</h1>
        <p class="mt-2 text-sm text-gray-500">Review private support tickets. Safety reports continue through their dedicated moderation workflow.</p>
    </header>

    <section aria-label="Ticket insights" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $metrics = [
                ['key' => 'total', 'label' => 'Total tickets', 'value' => $insights['total'], 'card' => 'border-purple-200/80 bg-gradient-to-br from-purple-50 via-white to-purple-100/70 ring-purple-200/40', 'labelClass' => 'text-purple-700/80', 'icon' => 'from-purple-600 to-purple-500 shadow-glow-purple ring-purple-600/40', 'path' => 'M7 18.25H5.75a2 2 0 0 1-2-2V6.75a2 2 0 0 1 2-2h12.5a2 2 0 0 1 2 2v9.5a2 2 0 0 1-2 2h-6.5L7 21v-2.75Z'],
                ['key' => 'new', 'label' => 'New', 'value' => $insights['new'], 'card' => 'border-sky-200/80 bg-gradient-to-br from-sky-50 via-white to-sky-100/70 ring-sky-200/40', 'labelClass' => 'text-sky-700/80', 'icon' => 'from-sky-600 to-sky-500 shadow-lg ring-sky-600/40', 'path' => 'M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['key' => 'unresolved', 'label' => 'Unresolved', 'value' => $insights['unresolved'], 'card' => 'border-amber-200/80 bg-gradient-to-br from-amber-50 via-white to-amber-100/70 ring-amber-200/40', 'labelClass' => 'text-amber-700/80', 'icon' => 'from-amber-600 to-amber-500 shadow-lg ring-amber-600/40', 'path' => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['key' => 'resolved', 'label' => 'Resolved', 'value' => $insights['resolved'], 'card' => 'border-emerald-200/80 bg-gradient-to-br from-emerald-50 via-white to-emerald-100/70 ring-emerald-200/40', 'labelClass' => 'text-emerald-700/80', 'icon' => 'from-emerald-600 to-emerald-500 shadow-lg ring-emerald-600/40', 'path' => 'm9 12.75 2.25 2.25L15.5 9.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ];
        @endphp
        @foreach($metrics as $metric)
            <article data-support-metric-card="{{ $metric['key'] }}" class="group relative overflow-hidden rounded-[24px] border p-6 ring-1 transition duration-200 hover:-translate-y-0.5 hover:shadow-medium before:pointer-events-none before:absolute before:inset-0 before:content-[''] before:bg-gradient-to-br before:from-white/70 before:via-transparent before:to-transparent before:opacity-70 dark:border-slate-700/70 dark:bg-slate-900 dark:ring-slate-700/40 dark:before:opacity-0 {{ $metric['card'] }}">
                <div class="relative flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $metric['labelClass'] }}">{{ $metric['label'] }}</p>
                    <span data-support-metric-icon="{{ $metric['key'] }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br text-white ring-1 {{ $metric['icon'] }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $metric['path'] }}" /></svg>
                    </span>
                </div>
                <p class="relative mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ number_format((int) $metric['value']) }}</p>
            </article>
        @endforeach
    </section>

    <form data-feedback-filters method="GET" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-label="Filter ticket inbox">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label for="feedback-search" class="text-xs font-semibold text-gray-700">Search</label>
                <input id="feedback-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, subject, or user" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
            <div>
                <label for="feedback-status" class="text-xs font-semibold text-gray-700">Status</label>
                <select id="feedback-status" name="status" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                    <option value="">All statuses</option>
                    @foreach(\App\Enums\PlatformFeedbackStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="feedback-type" class="text-xs font-semibold text-gray-700">Category</label>
                <select id="feedback-type" name="type" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                    <option value="">All categories</option>
                    @foreach(\App\Enums\PlatformFeedbackType::cases() as $type)<option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="feedback-rating" class="text-xs font-semibold text-gray-700">Rating</label>
                <select id="feedback-rating" name="rating" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                    <option value="">Any rating</option>
                    @foreach(range(1, 5) as $rating)<option value="{{ $rating }}" @selected((string) ($filters['rating'] ?? '') === (string) $rating)>{{ $rating }} of 5</option>@endforeach
                </select>
            </div>
            <div>
                <label for="feedback-from" class="text-xs font-semibold text-gray-700">From date</label>
                <input id="feedback-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
            <div>
                <label for="feedback-to" class="text-xs font-semibold text-gray-700">To date</label>
                <input id="feedback-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-3">
            @if(collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
                <a href="{{ route('admin.feedback.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear filters</a>
            @endif
            <button class="min-h-10 rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900">Apply filters</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-3">Ticket</th><th class="px-5 py-3">Sender</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Rating</th><th class="px-5 py-3">Received</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($feedback as $item)
                    <tr class="hover:bg-purple-50/40">
                        <td class="px-5 py-4"><a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('admin.feedback.show', $item) }}">{{ $item->subject }}</a><p class="mt-1 text-xs text-gray-500">{{ $item->reference_number }}</p></td>
                        <td class="min-w-56 px-5 py-4"><x-support.sender-profile :user="$item->user" :role="$item->user_role" :compact="true" /></td>
                        <td class="px-5 py-4"><x-support.status-badge :status="$item->status" /></td>
                        <td class="px-5 py-4"><span class="inline-flex items-center gap-2 text-gray-700"><x-support.type-icon :type="$item->type" class="h-4 w-4 text-purple-700" />{{ $item->type->label() }}</span></td>
                        <td class="px-5 py-4 text-gray-600">{{ $item->rating ? $item->rating.' / 5' : '—' }}</td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $item->created_at->format('M j, Y') }}</td>
                        <td class="px-5 py-4 text-right">
                            <form method="POST" action="{{ route('admin.feedback.open', $item) }}">
                                @csrf
                                <button type="submit" data-feedback-review-action title="Review ticket" aria-label="Review ticket: {{ $item->subject }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-purple-200 text-purple-700 transition hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400">
                                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center"><p class="font-semibold text-gray-900">No tickets match these filters</p><p class="mt-1 text-sm text-gray-500">Clear the filters to return to the full inbox.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $feedback->links() }}
</main>
@endsection
