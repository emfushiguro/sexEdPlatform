@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Hybrid Command Center</h1>
        <p class="mt-1 text-sm text-gray-500">Prioritized operational metrics with direct actions for admin teams.</p>
    </div>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Content Governance</h2>
            <a href="{{ route('admin.content-reviews.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Open Review Queue</a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-sm text-gray-500">Pending content reviews</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($pendingContentReviews) }}</p>
                <a href="{{ route('admin.content-reviews.index') }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">Review instructor submissions</a>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-sm text-gray-500">Platform-owned modules</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">Admin</p>
                <a href="{{ route('admin.modules.index') }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">Manage admin modules</a>
            </article>
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Immediate Risk</h2>
            <a href="{{ route('admin.subscribers.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View Subscribers</a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($riskMetrics as $metric)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <p class="text-sm text-gray-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($metric['value']) }}</p>
                    <a href="{{ $metric['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $metric['cta_label'] }}</a>
                </article>
            @endforeach
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Revenue Leakage</h2>
            <a href="{{ route('admin.payments.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View Payments</a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($leakageMetrics as $metric)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <p class="text-sm text-gray-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($metric['value']) }}</p>
                    <a href="{{ $metric['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $metric['cta_label'] }}</a>
                </article>
            @endforeach
        </div>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Growth and Platform Health</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View Users</a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach($growthMetrics as $metric)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <p class="text-sm text-gray-500">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($metric['value']) }}</p>
                    <a href="{{ $metric['cta_route'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">{{ $metric['cta_label'] }}</a>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
