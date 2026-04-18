@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($overviewCards as $card)
                @php
                $delta = (float) data_get($card, 'delta_percent', 0);
                $isPositive = $delta >= 0;
                $isCurrency = (bool) data_get($card, 'is_currency', false);
            @endphp
            <article class="group relative overflow-hidden rounded-2xl border border-brand-200/80 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-6 shadow-soft ring-1 ring-brand-200/40 transition duration-200 hover:-translate-y-0.5 hover:shadow-medium dark:border-slate-700/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 dark:ring-slate-700/40 before:pointer-events-none before:absolute before:inset-0 before:content-[''] before:bg-gradient-to-br before:from-brand-100/60 before:via-transparent before:to-transparent before:opacity-70 dark:before:opacity-0">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700/80 dark:text-brand-200/80">{{ $card['label'] }}</p>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-500 text-white shadow-glow-purple ring-1 ring-brand-600/40 dark:bg-brand-500/25 dark:text-brand-100 dark:ring-brand-500/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M7 16V8m5 8V5m5 11v-6" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                    {{ $isCurrency ? 'PHP ' . number_format((float) $card['value'], 0) : number_format((int) $card['value']) }}
                </p>
                <div class="mt-3 flex items-center justify-between">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $isPositive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' }}">
                        {{ $isPositive ? '+' : '' }}{{ number_format($delta, 2) }}%
                    </span>
                    <p class="text-xs text-slate-400 dark:text-slate-500">vs last month</p>
                </div>
            </article>
        @endforeach
    </section>`r`n
    <section class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <article class="overflow-hidden rounded-2xl border border-brand-200/80 bg-white shadow-soft ring-1 ring-brand-200/30 dark:border-slate-700/70 dark:bg-slate-900 dark:ring-slate-700/30">
                <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50/70 via-white to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Learners Demographic</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Learner accounts by age bracket.</p>
                </div>
                <div class="grid gap-6 px-6 py-6 lg:grid-cols-5">
                    <div class="h-72 lg:col-span-3">
                        <canvas id="learnerDemographicChart"></canvas>
                    </div>
                    <div class="space-y-4 lg:col-span-2">
                        @foreach($learnerDemographics as $group)
                            @php
                                $accent = $group['accent'] ?? 'sky';
                                $barClass = $accent === 'violet' ? 'bg-brand-500' : ($accent === 'emerald' ? 'bg-emerald-500' : 'bg-brand-700');
                            @endphp
                            <div class="rounded-xl bg-gradient-to-br from-brand-50/80 via-white to-white p-4 ring-1 ring-brand-100/70 dark:bg-slate-800/60 dark:ring-slate-700/60">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $group['label'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format((int) $group['count']) }} learners</p>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ number_format((float) $group['percent'], 2) }}%</p>
                                </div>
                                <div class="mt-3 h-2 rounded-full bg-brand-100/60 dark:bg-slate-700">
                                    <div class="h-2 rounded-full {{ $barClass }}" style="width: {{ min(100, max(0, (float) $group['percent'])) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-brand-200/80 bg-white shadow-soft ring-1 ring-brand-200/30 dark:border-slate-700/70 dark:bg-slate-900 dark:ring-slate-700/30">
                <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50/70 via-white to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Monthly Subscribers</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Approved subscriptions trend for the last 12 months.</p>
                </div>
                <div class="px-6 py-6">
                    <div class="h-80">
                        <canvas id="monthlySubscribersChart"></canvas>
                    </div>
                </div>
            </article>
        </div>

        <div class="space-y-6 xl:col-span-4">
            <article class="overflow-hidden rounded-2xl border border-brand-200/80 bg-white shadow-soft ring-1 ring-brand-200/30 dark:border-slate-700/70 dark:bg-slate-900 dark:ring-slate-700/30">
                <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50/70 via-white to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Moderation Queue</h2>
                </div>
                <div class="space-y-3 px-6 py-6">
                    @foreach($moderationQueues as $queue)
                        <div class="flex items-center justify-between rounded-xl bg-gradient-to-br from-brand-50/80 via-white to-white px-4 py-3 ring-1 ring-brand-100/70 dark:bg-slate-800/60 dark:ring-slate-700/60">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $queue['label'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $queue['description'] }}</p>
                            </div>
                            <a href="{{ $queue['cta_route'] }}" class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200/70 transition hover:bg-brand-50 dark:bg-brand-500/10 dark:text-brand-200 dark:ring-brand-500/25 dark:hover:bg-brand-500/20">
                                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-100 px-1 text-[10px] font-bold text-brand-700 dark:bg-brand-500/25 dark:text-brand-200">{{ number_format((int) $queue['count']) }}</span>
                                Open
                            </a>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-brand-200/80 bg-white shadow-soft ring-1 ring-brand-200/30 dark:border-slate-700/70 dark:bg-slate-900 dark:ring-slate-700/30">
                <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50/70 via-white to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Quick Actions</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Frequently used administration tools.</p>
                </div>
                <div class="space-y-3 px-6 py-6">
                    <a href="{{ route('admin.users.index') }}" class="group flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </span>
                            <span class="text-sm font-medium text-slate-700 group-hover:text-brand-700 dark:text-slate-300 dark:group-hover:text-brand-300">Manage Users</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                    
                    <a href="{{ route('admin.payments.index') }}" class="group flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            </span>
                            <span class="text-sm font-medium text-slate-700 group-hover:text-brand-700 dark:text-slate-300 dark:group-hover:text-brand-300">Review Payments</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>

                    <a href="{{ route('admin.modules.index') }}" class="group flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-600 dark:bg-brand-500/20 dark:text-brand-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </span>
                            <span class="text-sm font-medium text-slate-700 group-hover:text-brand-700 dark:text-slate-300 dark:group-hover:text-brand-300">Manage Modules</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>
            </article>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-brand-200/80 bg-white shadow-soft ring-1 ring-brand-200/30 dark:border-slate-700/70 dark:bg-slate-900 dark:ring-slate-700/30">
        <div class="border-b border-brand-100 bg-gradient-to-r from-brand-50/70 via-white to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Recent System Activity</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Latest updates across applications, reviews, and payments.</p>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($recentActivity as $activity)
                <a href="{{ $activity['href'] }}" class="block px-6 py-4 transition hover:bg-brand-50/60 dark:hover:bg-slate-800/50">
                    <div class="flex items-start gap-4">
                        <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold {{ $activity['tone'] === 'emerald' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : ($activity['tone'] === 'rose' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300' : ($activity['tone'] === 'amber' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300')) }}">{{ strtoupper(substr($activity['type'], 0, 2)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $activity['title'] }}</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">{{ $activity['type'] }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $activity['meta'] }}</p>
                        </div>
                        <span class="whitespace-nowrap text-xs text-slate-400 dark:text-slate-500">{{ optional($activity['occurred_at'])->diffForHumans() }}</span>
                    </div>
                </a>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">No recent system activity to display yet.</div>
            @endforelse
        </div>`r`n    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyLabels = @json(data_get($dashboardAnalytics, 'monthly_labels', []));
        const monthlyCounts = @json(data_get($dashboardAnalytics, 'monthly_subscriber_counts', []));
        const demographicLabels = @json(collect($learnerDemographics)->pluck('label')->values());
        const demographicCounts = @json(collect($learnerDemographics)->pluck('count')->values());

        if (typeof Chart !== 'undefined') {
            const subscribersCanvas = document.getElementById('monthlySubscribersChart');
            if (subscribersCanvas) {
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(148, 163, 184, 0.25)' : '#f0c4fe';
                const tickColor = isDark ? 'rgba(226, 232, 240, 0.72)' : 'rgba(71, 85, 105, 0.75)';
                new Chart(subscribersCanvas, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Subscribers',
                            data: monthlyCounts,
                            backgroundColor: '#730DB1',
                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 28,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor,
                                },
                                ticks: {
                                    precision: 0,
                                    color: tickColor,
                                },
                            },
                            x: {
                                grid: {
                                    display: false,
                                },
                                ticks: {
                                    color: tickColor,
                                },
                            },
                        },
                    },
                });
            }

            const demographicCanvas = document.getElementById('learnerDemographicChart');
            if (demographicCanvas) {
                const isDark = document.documentElement.classList.contains('dark');
                const brandPalette = ['#A30EB2', '#730DB1', '#3B0CB1'];
                new Chart(demographicCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: demographicLabels,
                        datasets: [{
                            data: demographicCounts,
                            backgroundColor: demographicCounts.map((_, index) => brandPalette[index % brandPalette.length]),
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '64%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    padding: 16,
                                    color: isDark ? 'rgba(226, 232, 240, 0.72)' : 'rgba(71, 85, 105, 0.85)',
                                },
                            },
                        },
                    },
                });
            }
        }

    });
</script>
@endpush
