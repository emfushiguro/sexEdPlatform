@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8">
    <section class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Admin Operations</p>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Admin Command Center</h1>
        <p class="max-w-3xl text-sm leading-6 text-gray-500">
            Track platform health, resolve moderation queues quickly, and monitor the latest operational activity from one central dashboard.
        </p>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Platform Snapshot</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Manage Users</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($snapshotMetrics as $metric)
                @php
                    $accent = $metric['accent'] ?? 'sky';
                    $styles = [
                        'sky' => 'border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 text-brand-600',
                        'violet' => 'border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 text-brand-600',
                        'emerald' => 'border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 text-emerald-600',
                        'amber' => 'border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 text-amber-600',
                        'fuchsia' => 'border-brand-100 bg-gradient-to-br from-brand-50 via-white to-rose-50 text-brand-600',
                        'orange' => 'border-orange-100 bg-gradient-to-br from-orange-50 via-white to-amber-50 text-orange-600',
                        'indigo' => 'border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-50 text-brand-600',
                        'rose' => 'border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 text-rose-600',
                    ];
                    $cardClass = $styles[$accent] ?? $styles['sky'];
                @endphp

                <article class="rounded-[28px] border p-5 shadow-theme-xs {{ $cardClass }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em]">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format((int) $metric['value']) }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-5">
        <article class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs lg:col-span-2">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_35%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-5">
                <h2 class="text-lg font-bold text-gray-900">Moderation Queues</h2>
                <p class="mt-1 text-sm text-gray-500">Fast access to high-impact reviews.</p>
            </div>

            <div class="space-y-4 px-6 py-5">
                @foreach($moderationQueues as $queue)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $queue['label'] }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $queue['description'] }}</p>
                            </div>
                            <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-brand-50 px-3 py-1 text-sm font-bold text-brand-600">
                                {{ number_format((int) $queue['count']) }}
                            </span>
                        </div>
                        <a href="{{ $queue['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $queue['cta_label'] }}</a>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs lg:col-span-3">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.12),_transparent_35%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-5">
                <h2 class="text-lg font-bold text-gray-900">Recent System Activity</h2>
                <p class="mt-1 text-sm text-gray-500">Latest updates across applications, reviews, and payments.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($recentActivity as $activity)
                    <a href="{{ $activity['href'] }}" class="block px-6 py-4 transition hover:bg-gray-50">
                        <div class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-2xl text-xs font-bold {{ $activity['tone'] === 'emerald' ? 'bg-emerald-100 text-emerald-700' : ($activity['tone'] === 'rose' ? 'bg-rose-100 text-rose-700' : ($activity['tone'] === 'amber' ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700')) }}">
                                {{ strtoupper(substr($activity['type'], 0, 2)) }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $activity['title'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-gray-400">{{ $activity['type'] }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $activity['meta'] }}</p>
                            </div>
                            <span class="ml-auto whitespace-nowrap text-xs text-gray-400">
                                {{ optional($activity['occurred_at'])->diffForHumans() }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-gray-500">No recent system activity to display yet.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection
