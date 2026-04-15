@extends('layouts.admin')

@section('title', 'Learner Reports')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Learner Reports</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">Moderate learner-submitted module and instructor reports.</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search reason, reporter name, or email"
                class="md:col-span-2 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
            >
            <select name="status" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                <option value="">All statuses</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                        {{ $statusOption->label() }}
                    </option>
                @endforeach
            </select>
            <select name="target_type" class="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                <option value="">All targets</option>
                <option value="module" @selected($targetType === 'module')>Module</option>
                <option value="instructor" @selected($targetType === 'instructor')>Instructor</option>
            </select>
            <div class="md:col-span-4 flex items-center gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                >
                    Apply Filters
                </button>
                <a href="{{ route('admin.learner-reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Reporter</th>
                        <th class="px-4 py-3">Target</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($reports as $report)
                        @php
                            $targetTypeValue = is_object($report->target_type) ? $report->target_type->value : (string) $report->target_type;
                            $statusValue = is_object($report->status) ? $report->status->value : (string) $report->status;
                            $targetLabel = $targetTypeValue === 'module'
                                ? ($moduleTitles[(int) $report->target_id] ?? ('Module #' . $report->target_id))
                                : ($instructorNames[(int) $report->target_id] ?? ('Instructor #' . $report->target_id));
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $report->reporter?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $report->reporter?->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $targetTypeValue === 'module' ? 'bg-brand-100 text-brand-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $targetTypeValue }}
                                </span>
                                <p class="mt-1 text-gray-700 dark:text-gray-200">{{ $targetLabel }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ str_replace('_', ' ', $report->reason_code) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $statusValue === 'submitted' ? 'bg-rose-100 text-rose-700' : ($statusValue === 'under_review' ? 'bg-amber-100 text-amber-700' : ($statusValue === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700')) }}">
                                    {{ is_object($report->status) ? $report->status->label() : ucfirst(str_replace('_', ' ', (string) $report->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $report->created_at?->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.learner-reports.show', $report) }}" class="text-brand-600 hover:text-brand-700 font-semibold">
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No learner reports found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $reports->links() }}
        </div>
    </div>
</div>
@endsection
