@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@php
    $statusLabels = [
        'pending_review' => 'Pending',
        'action_required' => 'Action required',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
    $statusClasses = [
        'pending_review' => 'bg-amber-100 text-amber-800',
        'action_required' => 'bg-orange-100 text-orange-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
    ];
    $statusUrl = fn (string $value): string => route('admin.instructor-applications.identities.index', array_filter([
        'status' => $value,
        'stage' => $stage !== 'all' ? $stage : null,
        'search' => $search !== '' ? $search : null,
    ]));
    $cards = [
        ['label' => 'Pending', 'key' => 'pending_review', 'icon' => 'clock', 'tone' => 'amber'],
        ['label' => 'Action required', 'key' => 'action_required', 'icon' => 'alert', 'tone' => 'orange'],
        ['label' => 'Approved', 'key' => 'approved', 'icon' => 'check', 'tone' => 'emerald'],
        ['label' => 'Rejected', 'key' => 'rejected', 'icon' => 'close', 'tone' => 'rose'],
    ];
@endphp

@section('content')
    <div class="space-y-8">
        @foreach(['success' => 'bg-emerald-50 border-emerald-200 text-emerald-800', 'error' => 'bg-rose-50 border-rose-200 text-rose-800', 'warning' => 'bg-amber-50 border-amber-200 text-amber-800'] as $flash => $flashClass)
            @if(session($flash))
                <div class="rounded-2xl border px-4 py-3 text-sm {{ $flashClass }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($cards as $card)
                @php
                    $tone = $card['tone'];
                    $toneClasses = ['border' => 'border-brand-200/80', 'from' => 'from-brand-50', 'to' => 'to-brand-100/70', 'label' => 'text-brand-700/80', 'icon' => 'bg-brand-700'];
                @endphp
                <article class="rounded-[24px] border bg-gradient-to-br via-white p-6 shadow-sm {{ $toneClasses['border'] }} {{ $toneClasses['from'] }} {{ $toneClasses['to'] }}">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $toneClasses['label'] }}">{{ $card['label'] }}</p>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full {{ $toneClasses['icon'] }} text-white shadow-sm" aria-hidden="true">
                            @if($card['icon'] === 'clock')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @elseif($card['icon'] === 'alert')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @elseif($card['icon'] === 'check')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 12.75 2.25 2.25L15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m8 8 8 8M16 8l-8 8"/></svg>
                            @endif
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($statusCounts[$card['key']] ?? 0) }}</p>
                </article>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-sm" data-testid="unified-instructor-applications-table">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Applications table</h2>
                    </div>
                    <form method="GET" action="{{ route('admin.instructor-applications.identities.index') }}" class="grid gap-3 sm:grid-cols-3" data-testid="admin-table-filter-bar">
                        <label class="block sm:col-span-1">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input name="search" value="{{ $search }}" type="search" placeholder="Name or email" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-300 focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Stage</span>
                            <select name="stage" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-300 focus:ring-2 focus:ring-brand-100">
                                <option value="all" @selected($stage === 'all')>All stages</option>
                                <option value="stage_one" @selected($stage === 'stage_one')>Stage One</option>
                                <option value="stage_two" @selected($stage === 'stage_two')>Stage Two</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select name="status" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-brand-300 focus:ring-2 focus:ring-brand-100">
                                <option value="all" @selected($status === 'all')>All statuses</option>
                                @foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <div class="flex items-end gap-2 sm:col-span-3">
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Apply filters</button>
                            <a href="{{ route('admin.instructor-applications.identities.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-brand-200 bg-brand-50/60 px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-brand-50/45 text-xs uppercase tracking-[0.18em] text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Applicant</th>
                            <th class="px-6 py-4">Stage</th>
                            <th class="px-6 py-4">Round</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Submitted</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            @php
                                $record = $row['case'] ?? $row['application'];
                                $applicant = $record?->user;
                                $reviewUrl = $row['type'] === 'identity'
                                    ? route('admin.instructor-applications.identities.show', $row['case'])
                                    : route('admin.instructor-applications.review', $row['application']);
                                $rowStatus = $row['status'];
                                $rowStatusLabel = $statusLabels[$rowStatus] ?? ucwords(str_replace('_', ' ', $rowStatus));
                            @endphp
                            <tr class="transition hover:bg-brand-50/45">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $applicant?->full_name ?? $applicant?->name ?? 'Unknown applicant' }}</p>
                                    <p class="text-gray-500">{{ $applicant?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-700">{{ $row['stage_label'] }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $row['round'] ?? '—' }}</td>
                                <td class="px-6 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusClasses[$rowStatus] ?? 'bg-gray-100 text-gray-700' }}">{{ $rowStatusLabel }}</span></td>
                                <td class="px-6 py-4 text-gray-600">{{ $row['submitted_at']?->format('M j, Y g:i A') ?? 'Not submitted' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ $reviewUrl }}" aria-label="Open review for {{ $applicant?->full_name ?? $applicant?->name ?? 'applicant' }}" title="Open review" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-brand-50/60 text-brand-700 transition hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                        <span class="sr-only">Open review</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600" aria-hidden="true"><svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm-9 9a5 5 0 0 1 10 0v1H7v-1Z"/></svg></div>
                                <h3 class="mt-4 text-sm font-semibold text-gray-900">No instructor applications match these filters</h3>
                                <p class="mt-1 text-sm text-gray-500">Try another status, stage, or search term.</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rows->hasPages())
                <div class="border-t border-gray-100 px-6 py-4">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>
@endsection
