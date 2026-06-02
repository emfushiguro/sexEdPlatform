@extends('layouts.learner-app')

@section('title', 'Connectors')

@php
    $connectorStatusClasses = [
        'verified' => 'bg-green-100 text-green-700',
        'pending' => 'bg-purple-100 text-purple-700',
        'rejected' => 'bg-red-100 text-red-700',
        'suspended' => 'bg-amber-100 text-amber-700',
        'withdrawn' => 'bg-gray-100 text-gray-700',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-6xl space-y-8">
    @if($hasConnectorAccess)
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-purple-700">Connectors</p>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900">My Connectors</h1>
                </div>
                <a href="{{ route('connectors.register') }}" class="inline-flex items-center justify-center rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Register Connector</a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach($managedConnectors as $connector)
                    <article class="rounded-xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">{{ $connector->name }}</h2>
                                <p class="mt-1 text-sm text-gray-500">{{ $categories[$connector->category] ?? str($connector->category)->headline() }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase {{ $connectorStatusClasses[$connector->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $connector->status }}
                            </span>
                        </div>
                        <p class="mt-4 line-clamp-2 text-sm text-gray-600">{{ $connector->description ?: 'No description provided yet.' }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span>{{ $connector->memberships_count }} members</span>
                            <span aria-hidden="true">/</span>
                            <span>{{ $connector->invitations_count }} invitations</span>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @if($connector->status === 'verified')
                                <a href="{{ route('connector.dashboard', $connector) }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Dashboard</a>
                            @endif
                            <a href="{{ route('connector.status', $connector) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Status</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-purple-700">Discovery</p>
                    <h2 class="text-xl font-bold text-gray-900">Discover Connectors</h2>
                </div>
            </div>
            @include('connectors.partials.discovery-feed', ['connectors' => $discoveryConnectors, 'categories' => $categories])
        </section>
    @else
        <section class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-purple-700">Connectors</p>
                <div class="mt-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Connector Discovery</h1>
                        <p class="mt-1 text-sm text-gray-500">Find verified community partners already on the platform.</p>
                    </div>
                    <a href="{{ route('connectors.register') }}" class="inline-flex items-center justify-center rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Register Connector</a>
                </div>
            </div>
            @include('connectors.partials.discovery-feed', ['connectors' => $discoveryConnectors, 'categories' => $categories])
        </section>
    @endif
</div>
@endsection
