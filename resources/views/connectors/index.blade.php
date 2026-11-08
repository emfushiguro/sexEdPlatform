@extends(auth()->user()?->isInstructor() ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', 'Connectors')

@php
    $connectorStatusClasses = [
        'verified' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'pending' => 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300',
        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        'suspended' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        'withdrawn' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-8 px-4 py-6">
    @if($hasConnectorAccess)
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Connectors</p>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">My Connectors</h1>
                </div>
                <a href="{{ route('connectors.register') }}" class="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900 px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:ring-brand-600 dark:ring-offset-gray-900">Register Connector</a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach($managedConnectors as $connector)
                    <article class="rounded-xl border border-brand-100 bg-brand-50/30 p-5 dark:border-brand-900 dark:bg-brand-950/20">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $connector->name }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $categories[$connector->category] ?? str($connector->category)->headline() }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase {{ $connectorStatusClasses[$connector->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                {{ $connector->status }}
                            </span>
                        </div>
                        <p class="mt-4 line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{{ $connector->description ?: 'No description provided yet.' }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $connector->memberships_count }} members</span>
                            <span aria-hidden="true">/</span>
                            <span>{{ $connector->invitations_count }} invitations</span>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @if($connector->status === 'verified')
                                <a href="{{ route('connector.dashboard', $connector) }}" class="rounded-lg bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900 px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:ring-brand-600 dark:ring-offset-gray-900">Dashboard</a>
                            @endif
                            <a href="{{ route('connector.status', $connector) }}" class="rounded-lg border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-50 focus-visible:ring-brand-600 dark:border-brand-800 dark:bg-gray-900 dark:text-brand-300 dark:hover:bg-brand-950 dark:ring-offset-gray-900">Status</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Discovery</p>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Discover Connectors</h2>
                </div>
            </div>
            <form method="GET" action="{{ route('connectors.index') }}" class="flex gap-2">
                <input name="search" value="{{ $search }}" placeholder="Search connectors..." aria-label="Search connectors" class="min-w-0 w-full rounded-xl border-gray-200 bg-white text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500">
                <button class="rounded-xl bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900 px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:ring-brand-600 dark:ring-offset-gray-900">Search</button>
            </form>
            @include('connectors.partials.discovery-feed', ['connectors' => $discoveryConnectors, 'categories' => $categories])
        </section>
    @else
        <section class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Connectors</p>
                <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Connector Discovery</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Find verified community partners already on the platform.</p>
                    </div>
                    <a href="{{ route('connectors.register') }}" class="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900 px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:ring-brand-600 dark:ring-offset-gray-900">Register Connector</a>
                </div>
            </div>
            <form method="GET" action="{{ route('connectors.index') }}" class="flex gap-2">
                <input name="search" value="{{ $search }}" placeholder="Search connectors..." aria-label="Search connectors" class="min-w-0 w-full rounded-xl border-gray-200 bg-white text-sm text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500">
                <button class="rounded-xl bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900 px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:ring-brand-600 dark:ring-offset-gray-900">Search</button>
            </form>
            @include('connectors.partials.discovery-feed', ['connectors' => $discoveryConnectors, 'categories' => $categories])
        </section>
    @endif
</div>
@endsection
