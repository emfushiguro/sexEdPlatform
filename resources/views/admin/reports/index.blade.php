@extends('layouts.admin')

@section('title', 'Learner Reports')

@section('content')
<div class="space-y-8">
    <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="mt-2 text-xl font-bold text-gray-900 flex items-center gap-2">
                        Learner Reports
                        <span class="inline-flex items-center justify-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-bold text-brand-700">{{ $reports->total() }}</span>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">Moderate learner-submitted module and instructor reports.</p>
                </div>
                <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 w-full xl:w-auto">       
                    <label class="block xl:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search reason, reporter..."
                            class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                        >
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                        <select name="status" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All statuses</option>
                            @foreach($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                                    {{ $statusOption->label() }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Target</span>
                        <select name="target_type" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All targets</option>
                            <option value="module" @selected($targetType === 'module')>Module</option>
                            <option value="instructor" @selected($targetType === 'instructor')>Instructor</option>
                        </select>
                    </label>
                </form>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 px-6 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.learner-reports.index') }}"
                   class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                    Reset Filters
                </a>
                <button type="button" onclick="event.target.closest('div.flex-wrap').previousElementSibling.querySelector('form').submit()"
                        class="inline-flex items-center rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                    Apply Filters
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-brand-50/45">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Reporter</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Target</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Reason</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Submitted</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($reports as $report)
                        @php
                            $targetTypeValue = is_object($report->target_type) ? $report->target_type->value : (string) $report->target_type;
                            $statusValue = is_object($report->status) ? $report->status->value : (string) $report->status;
                            $targetLabel = $targetTypeValue === 'module'
                                ? ($moduleTitles[(int) $report->target_id] ?? ('Module #' . $report->target_id))
                                : ($instructorNames[(int) $report->target_id] ?? ('Instructor #' . $report->target_id));
                        @endphp
                        <tr class="transition hover:bg-brand-50/55">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $report->reporter?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $report->reporter?->email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full capitalize {{ $targetTypeValue === 'module' ? 'bg-brand-100 text-brand-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $targetTypeValue }}
                                </span>
                                <p class="mt-1 text-sm font-semibold text-gray-700">{{ $targetLabel }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ str_replace('_', ' ', $report->reason_code) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full {{ $statusValue === 'submitted' ? 'bg-rose-100 text-rose-700' : ($statusValue === 'under_review' ? 'bg-amber-100 text-amber-700' : ($statusValue === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700')) }}">
                                    {{ is_object($report->status) ? $report->status->label() : ucfirst(str_replace('_', ' ', (string) $report->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $report->created_at?->diffForHumans() }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.learner-reports.show', $report) }}" class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50" title="View details">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="max-w-sm mx-auto">
                                    <div class="flex items-center justify-center w-16 h-16 mx-auto text-gray-400 bg-gray-100 rounded-full">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h3 class="mt-4 text-sm font-semibold text-gray-900">No learner reports found</h3>
                                    <p class="mt-1 text-sm text-gray-500">Try broadening the search or resetting one of the filter fields.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $reports->links() }}
        </div>
    </section>
</div>
@endsection
