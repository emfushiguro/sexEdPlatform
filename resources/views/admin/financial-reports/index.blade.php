@extends('layouts.admin')

@section('title', 'Financial Reports')
@section('page-title', 'Financial Reports')

@section('content')
    @php
        $summary = (array) ($summaryPayload['summary'] ?? []);
        $trend = (array) ($summaryPayload['trend'] ?? []);
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

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Revenue Source Breakdown</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse(($breakdownPayload['source_breakdown'] ?? []) as $row)
                            <tr>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', (string) data_get($row, 'source', 'other'))) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">₱{{ number_format((float) data_get($row, 'amount', 0), 2) }}</td>
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
    </div>
@endsection

@push('scripts')
    @include('admin.financial-reports.partials.chart-scripts', ['trend' => $trend])
@endpush
