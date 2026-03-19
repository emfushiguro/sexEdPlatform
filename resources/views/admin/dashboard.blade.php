@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hybrid Command Center</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Prioritized operational metrics with direct actions for admin teams.</p>
    </div>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Immediate Risk</h2>
            <a href="{{ route('admin.subscribers.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View Subscribers</a>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($riskMetrics as $metric)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metric['value']) }}</p>
                    <a href="{{ $metric['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $metric['cta_label'] }}</a>
                </article>
            @endforeach
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue Leakage</h2>
            <a href="{{ route('admin.payments.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View Payments</a>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($leakageMetrics as $metric)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metric['value']) }}</p>
                    <a href="{{ $metric['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $metric['cta_label'] }}</a>
                </article>
            @endforeach
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Growth and Platform Health</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View Users</a>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach($growthMetrics as $metric)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metric['value']) }}</p>
                    <a href="{{ $metric['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $metric['cta_label'] }}</a>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
