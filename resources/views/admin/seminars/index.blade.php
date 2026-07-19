@extends('layouts.admin')

@section('title', 'Seminar Moderation')
@section('page-title', 'Seminar Moderation')

@section('content')
@php
    $cards = [
        ['label' => 'Total Seminars', 'value' => $stats['total'] ?? 0, 'icon' => 'M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h10.5'],
        ['label' => 'Pending Review', 'value' => $stats['pending'] ?? 0, 'icon' => 'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Approved Seminars', 'value' => $stats['approved'] ?? 0, 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Rejected Seminars', 'value' => $stats['rejected'] ?? 0, 'icon' => 'M18 6 6 18M6 6l12 12'],
    ];
@endphp

<div class="space-y-8">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $card)
            <div class="min-h-[116px] rounded-[28px] border border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60 p-5 shadow-theme-xs">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">{{ $card['label'] }}</p>
                        <p class="mt-2 text-4xl font-bold leading-none text-gray-900">{{ number_format((int) $card['value']) }}</p>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 via-brand-700 to-brand-900 text-white shadow-lg shadow-brand-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}" />
                        </svg>
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Seminar Review</h2>
                    <p class="mt-1 text-sm text-gray-500">Filter connector seminars by moderation status.</p>
                </div>
                <form method="GET" class="grid gap-3 sm:grid-cols-4">
                    <input name="search" value="{{ request('search') }}" placeholder="Search seminar or speaker" class="rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    <select name="status" class="rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        @foreach(\App\Enums\SeminarStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected(($status ?? request('status', 'pending_review')) === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        <option value="">All types</option>
                        @foreach(\App\Enums\SeminarType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        <option value="">All categories</option>
                        @foreach(config('seminars.categories') as $key => $label)
                            <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-brand-600 to-brand-800 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-300/40 transition hover:brightness-105 sm:col-span-4">Filter</button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-brand-50/45">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Seminar</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Connector</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Schedule</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($seminars as $seminar)
                        <tr>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.seminars.show', $seminar) }}" class="font-semibold text-brand-700 hover:text-brand-900">{{ $seminar->title }}</a>
                                <div class="mt-1 text-xs text-gray-500">{{ $seminar->categoryDisplayName() }} / {{ ucfirst($seminar->type) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $seminar->connector?->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ \App\Enums\SeminarStatus::tryFrom($seminar->status)?->label() ?? str($seminar->status)->headline() }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $seminar->localStartsAt()?->format('M d, Y g:i A') }} PHT</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.seminars.show', $seminar) }}" title="View seminar review" aria-label="View seminar review" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-brand-100 text-brand-700 transition hover:bg-brand-50">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Zm9.75 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No seminars found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-6 py-4">{{ $seminars->links() }}</div>
    </section>
</div>
@endsection
