@extends('layouts.admin')

@section('title', $subscriptionPlan->name)
@section('page-title', 'Plan: ' . $subscriptionPlan->name)

@section('content')
    {{-- Back link --}}
    <div class="mb-5">
        <a
            href="{{ route('admin.subscription-plans.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Plans
        </a>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- Plan Details --}}
        <div class="space-y-5 xl:col-span-2">
            {{-- Info Card --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $subscriptionPlan->name }}</h2>
                        @if($subscriptionPlan->description)
                            <p class="mt-1 text-sm text-gray-500">{{ $subscriptionPlan->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($subscriptionPlan->is_active)
                            <span class="rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700">Active</span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 border-t border-gray-100 py-4 sm:grid-cols-3">
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Price</p>
                        <p class="text-lg font-bold text-gray-900">
                            ₱{{ number_format($subscriptionPlan->price, 2) }}<span class="text-xs font-normal text-gray-400">/mo</span>
                        </p>
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Audience</p>
                        <p class="text-sm font-semibold text-gray-900">{{ ucfirst($subscriptionPlan->plan_audience ?? 'learner') }}</p>
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-gray-400">Billing</p>
                        <p class="text-sm font-semibold text-gray-900">{{ ucfirst($subscriptionPlan->billing_mode ?? 'monthly') }}</p>
                    </div>
                </div>

                {{-- Features --}}
                @if(!empty($subscriptionPlan->features))
                    <div class="mt-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Features</p>
                        <ul class="space-y-2">
                            @foreach($subscriptionPlan->features as $feature)
                                <li class="flex items-center gap-2 text-sm text-gray-700">
                                    <svg class="h-4 w-4 flex-shrink-0 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="mt-6 flex items-center gap-3 border-t border-gray-100 pt-4">
                    <a
                        href="{{ route('admin.subscription-plans.edit', $subscriptionPlan) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs transition-colors hover:bg-brand-600"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Plan
                    </a>
                    <form method="POST" action="{{ route('admin.subscription-plans.toggle', $subscriptionPlan) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            {{ $subscriptionPlan->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form
                        method="POST"
                        action="{{ route('admin.subscription-plans.delete', $subscriptionPlan) }}"
                        class="inline"
                        onsubmit="return confirm('Delete this plan? This cannot be undone.')"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-error-200 px-4 py-2.5 text-sm font-medium text-error-500 transition-colors hover:bg-error-50"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Subscribers Table --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Subscribers</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">User</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Started</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($subscriptionPlan->subscriptions as $subscription)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-600">
                                                {{ strtoupper(substr($subscription->user->name ?? '?', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $subscription->user->name ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-400">{{ $subscription->user->email ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $subscription->status->value === 'active' ? 'bg-success-50 text-success-700' : 'bg-gray-100 text-gray-500' }}">
                                            {{ ucfirst($subscription->status->value ?? $subscription->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-500">
                                        {{ $subscription->starts_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-500">
                                        {{ $subscription->ends_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No subscribers yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Stats Sidebar --}}
        <div class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">Plan Stats</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total Subscribers</span>
                        <span class="text-sm font-bold text-gray-900">{{ $stats['total_subscribers'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Active</span>
                        <span class="text-sm font-bold text-success-600">{{ $stats['active_subscribers'] }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <span class="text-sm text-gray-500">Monthly Revenue</span>
                        <span class="text-sm font-bold text-gray-900">₱{{ number_format($stats['monthly_revenue'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">Metadata</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Slug</span>
                        <code class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $subscriptionPlan->slug }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Created</span>
                        <span class="text-gray-700">{{ $subscriptionPlan->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Updated</span>
                        <span class="text-gray-700">{{ $subscriptionPlan->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
