@extends('layouts.admin')

@section('title', 'Suspension Dashboard')
@section('page-title', 'Suspension Dashboard')

@php
    $search = (string) ($filters['search'] ?? '');
    $role = (string) ($filters['role'] ?? '');
    $severity = (string) ($filters['severity'] ?? '');
    $trigger = (string) ($filters['trigger'] ?? '');
    $status = (string) ($filters['status'] ?? '');
    $appealStatus = (string) ($filters['appeal_status'] ?? '');
    $sort = (string) ($filters['sort'] ?? 'latest');
    $perPage = (int) ($filters['per_page'] ?? 15);

    $cards = [
        ['label' => 'All Suspensions', 'value' => (int) ($stats['total'] ?? 0)],
        ['label' => 'Active Suspensions', 'value' => (int) ($stats['active'] ?? 0)],
        ['label' => 'Appeals Pending', 'value' => (int) ($stats['appeals_pending'] ?? 0)],
        ['label' => 'Permanent Suspensions', 'value' => (int) ($stats['permanent'] ?? 0)],
        ['label' => 'Open Report Cases', 'value' => (int) ($stats['report_queue'] ?? 0)],
    ];
@endphp

@section('content')
    <div class="space-y-8">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach($cards as $card)
                <div class="rounded-[28px] border border-brand-100 bg-gradient-to-br from-white via-brand-50/50 to-brand-100/40 p-5 shadow-theme-xs min-h-[116px]">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">{{ $card['label'] }}</p>
                    <p class="mt-2 text-4xl leading-none font-bold text-gray-900">{{ number_format($card['value']) }}</p>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-white px-6 py-5">
                <h2 class="text-xl font-bold text-gray-900">Report Review Queue</h2>
                <p class="mt-1 text-sm text-gray-600">Chat message and learner reports are reviewed here before escalation to violations, enforcement, suspension, or appeal workflow.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Case</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Source</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Report</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Reporter</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Reported User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($reportCases as $case)
                            @php
                                $sourceLabel = is_object($case->case_source) && method_exists($case->case_source, 'label')
                                    ? $case->case_source->label()
                                    : str_replace('_', ' ', ucwords((string) $case->case_source));
                                $statusValue = is_object($case->status) ? $case->status->value : (string) $case->status;
                                $summary = (array) ($case->dashboard_report_summary ?? []);
                            @endphp
                            <tr class="transition hover:bg-brand-50/55">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $case->case_reference_code }}</p>
                                    <p class="text-xs text-gray-500">{{ optional($case->created_at)?->format('M d, Y g:i A') }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ $sourceLabel }}</td>
                                <td class="max-w-md px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $summary['title'] ?? 'Reported item' }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ str_replace('_', ' ', (string) ($summary['detail'] ?? 'No reason recorded')) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $case->reporter?->name ?? 'Unknown reporter' }}</p>
                                    <p class="text-xs text-gray-500">{{ $case->reporter?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $case->reportedUser?->name ?? 'Unknown user' }}</p>
                                    <p class="text-xs text-gray-500">{{ $case->reportedUser?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ str_replace('_', ' ', ucfirst($statusValue)) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.moderation-suspensions.reports.show', $case) }}"
                                       class="inline-flex items-center justify-center rounded-2xl border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No report cases are waiting for review.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.partials.table-pagination-footer', ['paginator' => $reportCases->links()])
        </section>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Suspension Dashboard</h2>
                        <p class="mt-1 text-sm text-gray-600">Unified moderation suspension records with appeal and enforcement context.</p>
                    </div>

                    <form method="GET"
                          action="{{ route('admin.moderation-suspensions.index') }}"
                          class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6"
                          data-testid="admin-table-filter-bar">
                        <label class="block xl:col-span-2">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="User, email, case reference..."
                                   class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Role</span>
                            <select name="role" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="">All roles</option>
                                @foreach(['learner', 'instructor', 'parent', 'admin'] as $roleOption)
                                    <option value="{{ $roleOption }}" @selected($role === $roleOption)>{{ ucfirst($roleOption) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Severity</span>
                            <select name="severity" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="">All severities</option>
                                @foreach(['minor', 'moderate', 'major', 'critical'] as $severityOption)
                                    <option value="{{ $severityOption }}" @selected($severity === $severityOption)>{{ ucfirst($severityOption) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Trigger</span>
                            <select name="trigger" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="">All triggers</option>
                                @foreach(['manual', 'automated', 'system'] as $triggerOption)
                                    <option value="{{ $triggerOption }}" @selected($trigger === $triggerOption)>{{ ucfirst($triggerOption) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select name="status" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="">All statuses</option>
                                @foreach(['active', 'expired', 'revoked'] as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="grid gap-3 sm:grid-cols-2 xl:col-span-6 xl:grid-cols-4">
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Appeal</span>
                                <select name="appeal_status" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                    <option value="">Any appeal state</option>
                                    @foreach(['none', 'appeal_pending', 'pending_review', 'clarification_requested', 'approved', 'rejected'] as $appealOption)
                                        <option value="{{ $appealOption }}" @selected($appealStatus === $appealOption)>{{ str_replace('_', ' ', ucfirst($appealOption)) }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Sort</span>
                                <select name="sort" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                    <option value="latest" @selected($sort === 'latest')>Newest first</option>
                                    <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                                    <option value="ending_soon" @selected($sort === 'ending_soon')>Ending soon</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Per Page</span>
                                <select name="per_page" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                    @foreach([10, 15, 25, 50] as $size)
                                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="flex items-end gap-2">
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-2xl border border-brand-200 bg-brand-50/70 px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/80">
                                    Apply Filters
                                </button>
                                <a href="{{ route('admin.moderation-suspensions.index') }}"
                                   class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="px-6 py-4">
                @include('admin.partials.row-actions', [
                    'actions' => [
                        'Aligned to Payment Management table conventions',
                        'Use filters to triage by role, severity, trigger, and appeal state',
                    ],
                ])
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Severity</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Trigger</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Appeal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Duration</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($suspensions as $index => $suspension)
                            @php
                                $severityRaw = $suspension->enforcementAction?->severity_level;
                                $severityValue = is_object($severityRaw) ? $severityRaw->value : (string) ($severityRaw ?? 'unknown');
                                $triggerValue = (string) ($suspension->enforcementAction?->trigger_type ?? 'manual');
                                $rowNumber = ($suspensions->firstItem() ?? 1) + $index;
                            @endphp
                            <tr class="transition hover:bg-brand-50/55">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $rowNumber }}</td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $suspension->user?->name ?? 'Unknown user' }}</p>
                                    <p class="text-xs text-gray-500">{{ $suspension->user?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ ucfirst((string) ($suspension->user?->role ?? 'unknown')) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ ucfirst(str_replace('_', ' ', $severityValue)) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $triggerValue)) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $suspension->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($suspension->status === 'revoked' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst((string) $suspension->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ str_replace('_', ' ', (string) $suspension->appeal_status) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ optional($suspension->starts_at)?->format('M d, Y') ?? '-' }}
                                    -
                                    {{ optional($suspension->ends_at)?->format('M d, Y') ?? 'Permanent' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.moderation-suspensions.show', $suspension) }}"
                                       class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50"
                                       title="View suspension">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">No suspension records matched your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('admin.partials.table-pagination-footer', ['paginator' => $suspensions->links()])
        </section>
    </div>
@endsection
