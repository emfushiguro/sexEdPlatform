@extends('layouts.admin')

@section('title', 'Financial Reports')
@section('page-title', 'Financial Reports')

@section('content')
    @php
        $summary = (array) ($summaryPayload['summary'] ?? []);
        $trend = (array) ($summaryPayload['trend'] ?? []);
        $sourceBreakdown = collect($breakdownPayload['source_breakdown'] ?? []);
        $topInstructors = collect($breakdownPayload['top_instructors'] ?? []);
        $topModules = collect($breakdownPayload['top_modules'] ?? []);
    @endphp

    <div class="space-y-8">
        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Revenue Analytics</p>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Financial Reporting Dashboard</h2>
                        <p class="mt-1 text-sm text-gray-600">Timezone: {{ $reportFilter->timezone }}</p>
                    </div>

                    <form method="GET" action="{{ route('admin.financial-reports.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6" data-testid="admin-table-filter-bar">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Report Type</span>
                            <select name="report_type" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                                <option value="weekly" @selected($reportFilter->reportType === 'weekly')>Weekly</option>
                                <option value="monthly" @selected($reportFilter->reportType === 'monthly')>Monthly</option>
                                <option value="yearly" @selected($reportFilter->reportType === 'yearly')>Yearly</option>
                                <option value="custom" @selected($reportFilter->reportType === 'custom')>Custom Range</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date From</span>
                            <input type="date" name="date_from" value="{{ request('date_from', $reportFilter->localStart->toDateString()) }}" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</span>
                            <input type="date" name="date_to" value="{{ request('date_to', $reportFilter->localEnd->toDateString()) }}" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</span>
                            <input type="number" min="1" name="instructor_id" value="{{ request('instructor_id') }}" placeholder="Optional" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Module</span>
                            <input type="number" min="1" name="module_id" value="{{ request('module_id') }}" placeholder="Optional" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        </label>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="inline-flex h-[46px] items-center justify-center rounded-2xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Apply</button>
                            <a href="{{ route('admin.financial-reports.index') }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-b border-gray-100 px-6 py-4">
                <a href="{{ route('admin.financial-reports.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100">Export PDF</a>
                <a href="{{ route('admin.financial-reports.export', ['format' => 'csv'] + request()->query()) }}" class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100">Export CSV</a>
                <a href="{{ route('admin.financial-reports.export', ['format' => 'xlsx'] + request()->query()) }}" class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100">Export XLSX</a>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Total Revenue', 'value' => (float) ($summary['total_revenue'] ?? 0)],
                    ['label' => 'Subscription Revenue', 'value' => (float) ($summary['subscription_revenue'] ?? 0)],
                    ['label' => 'Module Revenue', 'value' => (float) ($summary['module_revenue'] ?? 0)],
                    ['label' => 'Platform Earnings', 'value' => (float) ($summary['platform_earnings'] ?? 0)],
                ];
            @endphp
            @foreach($cards as $card)
                <div class="rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-5 shadow-theme-xs min-h-[116px]">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">{{ $card['label'] }}</p>
                    <p class="mt-2 text-4xl font-bold leading-none text-gray-900">₱{{ number_format($card['value'], 2) }}</p>
                </div>
            @endforeach
        </div>

        <section class="rounded-[30px] border border-gray-200 bg-white p-6 shadow-theme-xs">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Revenue Trend</h3>
                <p class="text-xs text-gray-500">{{ ucfirst($reportFilter->granularity) }} granularity</p>
            </div>
            <canvas id="financialTrendChart" height="110"></canvas>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs" data-testid="financial-report-breakdown">
            <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50/80 via-white to-indigo-50/60 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Performance details</p>
                <h3 class="mt-1 text-lg font-bold text-gray-900">Revenue Breakdown</h3>
                <p class="mt-1 text-sm text-gray-500">Completed payments grouped by source, instructor, and module.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" data-testid="financial-source-table">
                    <caption class="sr-only">Revenue grouped by source</caption>
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Source</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($sourceBreakdown as $row)
                            @php
                                $sourceAmount = (float) data_get($row, 'amount', 0);
                                $sourceShare = (float) ($summary['total_revenue'] ?? 0) > 0
                                    ? min(100, ($sourceAmount / (float) $summary['total_revenue']) * 100)
                                    : 0;
                            @endphp
                            <tr class="transition hover:bg-brand-50/40">
                                <td class="px-6 py-3">
                                    <p class="text-sm font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', (string) data_get($row, 'source', 'other'))) }}</p>
                                    <div class="mt-2 h-1.5 w-full max-w-32 overflow-hidden rounded-full bg-gray-100" aria-hidden="true">
                                        <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-700" style="width: {{ $sourceShare }}%"></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold text-gray-900">₱{{ number_format($sourceAmount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-sm text-gray-500">No source breakdown available for selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-600">Top Instructors</h3>
                    <p class="mt-1 text-xs text-gray-500">Earnings and platform fees for the selected period.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100" data-testid="financial-instructors-table">
                        <caption class="sr-only">Top instructors by gross revenue</caption>
                        <thead class="bg-brand-50/45">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Instructor</th>
                                <th scope="col" class="px-5 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Sales</th>
                                <th scope="col" class="px-5 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Gross</th>
                                <th scope="col" class="px-5 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Earnings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($topInstructors as $row)
                                <tr class="transition hover:bg-brand-50/40">
                                    <td class="px-5 py-3">
                                        <p class="max-w-[13rem] truncate text-sm font-semibold text-gray-900">{{ $row->instructor?->name ?? 'Unknown instructor' }}</p>
                                        <p class="max-w-[13rem] truncate text-xs text-gray-500">{{ $row->instructor?->email ?? 'No email' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-gray-700">{{ number_format((int) $row->sales_count) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-gray-900">₱{{ number_format((float) $row->gross_amount, 2) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-emerald-700">₱{{ number_format((float) $row->instructor_earnings_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No instructor data available for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-600">Top Modules</h3>
                    <p class="mt-1 text-xs text-gray-500">Module sales ranked by gross revenue.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100" data-testid="financial-modules-table">
                        <caption class="sr-only">Top modules by gross revenue</caption>
                        <thead class="bg-brand-50/45">
                            <tr>
                                <th scope="col" class="w-12 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">#</th>
                                <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Module</th>
                                <th scope="col" class="px-5 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Sales</th>
                                <th scope="col" class="px-5 py-3 text-right text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Gross</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($topModules as $row)
                                <tr class="transition hover:bg-brand-50/40">
                                    <td class="px-5 py-3 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <p class="max-w-[13rem] truncate text-sm font-semibold text-gray-900">{{ $row->module?->title ?? 'Unknown module' }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">Module revenue</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-gray-700">{{ number_format((int) $row->sales_count) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-gray-900">₱{{ number_format((float) $row->gross_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No module data available for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.financial-reports.partials.chart-scripts', ['trend' => $trend])
@endpush
