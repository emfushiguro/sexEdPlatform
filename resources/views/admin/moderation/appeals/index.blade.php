@extends('layouts.admin')

@section('title', 'Moderation Appeals')
@section('page-title', 'Moderation Appeals')

@section('content')
    <div class="space-y-8">
        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Appeal Review Queue</h2>
                        <p class="mt-1 text-sm text-gray-600">Review and process suspension appeals with threaded communication context.</p>
                    </div>

                    <form method="GET" action="{{ route('admin.moderation-appeals.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" data-testid="admin-table-filter-bar">
                        <label class="block xl:col-span-2">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="Appeal reason, learner, email"
                                   class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select name="status" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="">All statuses</option>
                                @foreach(['pending_review', 'clarification_requested', 'approved', 'rejected'] as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ ucwords(str_replace('_', ' ', $statusOption)) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="xl:col-span-3 flex items-center justify-end gap-2">
                            <button type="submit" class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/70 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100/80">
                                Apply
                            </button>
                            <a href="{{ route('admin.moderation-appeals.index') }}" class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Learner</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Submitted</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($appeals as $index => $appeal)
                            <tr class="transition hover:bg-brand-50/55">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ ($appeals->firstItem() ?? 1) + $index }}</td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $appeal->user?->name ?? 'Unknown user' }}</p>
                                    <p class="text-xs text-gray-500">{{ $appeal->user?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold bg-gray-100 text-gray-700">
                                        {{ ucwords(str_replace('_', ' ', (string) $appeal->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ optional($appeal->submitted_at)?->format('M d, Y h:i A') ?? '-' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.moderation-appeals.show', $appeal) }}"
                                       class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50"
                                       title="Review appeal">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No appeals matched your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.partials.table-pagination-footer', ['paginator' => $appeals->links()])
        </section>
    </div>
@endsection
