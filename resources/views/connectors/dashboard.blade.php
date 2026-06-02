@extends('layouts.connector-app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $statusTone = [
        'verified' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
        'pending' => 'text-amber-700 bg-amber-50 border-amber-200',
        'suspended' => 'text-rose-700 bg-rose-50 border-rose-200',
    ][$connector->status] ?? 'text-gray-700 bg-gray-50 border-gray-200';

    $statCards = [
        ['label' => 'Status', 'value' => str($connector->status)->headline(), 'icon' => 'status', 'tone' => $statusTone],
        ['label' => 'Members', 'value' => number_format($connector->memberships_count), 'icon' => 'members', 'tone' => 'text-brand-700 bg-brand-50 border-brand-200'],
        ['label' => 'Pending Invites', 'value' => number_format($connector->invitations_count), 'icon' => 'invites', 'tone' => 'text-amber-700 bg-amber-50 border-amber-200'],
        ['label' => 'Current Plan', 'value' => $plan?->name ?? 'No active plan', 'icon' => 'plan', 'tone' => 'text-gray-700 bg-gray-50 border-gray-200'],
    ];
@endphp

@section('content')
<div class="space-y-6">
    <section class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Connector Workspace</p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">{{ $connector->description ?: 'Manage members, roles, subscriptions, and upcoming connector features from one workspace.' }}</p>
            </div>
            <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12 12 4l9 8M5.5 10.5v8h13v-8"/></svg>
                Learner Dashboard
            </a>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($statCards as $card)
            <article class="rounded-[24px] border p-5 shadow-theme-xs {{ $card['tone'] }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em]">{{ $card['label'] }}</p>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/75">
                        @if($card['icon'] === 'members')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0v1a4 4 0 0 0 4 4m-12-5v1a4 4 0 0 1-4 4m4-4h8"/></svg>
                        @elseif($card['icon'] === 'invites')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.75h16v10.5H4zM4 7.75 12 13l8-5.25"/></svg>
                        @elseif($card['icon'] === 'plan')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.75 6.5h14.5M6 4.75h12A1.25 1.25 0 0 1 19.25 6v12A1.25 1.25 0 0 1 18 19.25H6A1.25 1.25 0 0 1 4.75 18V6A1.25 1.25 0 0 1 6 4.75Z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @endif
                    </span>
                </div>
                <p class="mt-4 text-2xl font-bold tracking-tight text-gray-900">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </div>

    <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-gray-900">Feature Access</h2>
                <p class="mt-1 text-sm text-gray-500">Entitlements are controlled by the active connector plan.</p>
            </div>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach(config('connector_permissions.entitlements') as $label => $key)
                @php $enabled = in_array($key, $enabledEntitlements, true); @endphp
                <div class="rounded-2xl border px-4 py-3 {{ $enabled ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                    <p class="text-sm font-semibold capitalize">{{ $label }}</p>
                    <p class="mt-1 text-xs">{{ $enabled ? 'Enabled by plan' : 'Locked by plan' }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
