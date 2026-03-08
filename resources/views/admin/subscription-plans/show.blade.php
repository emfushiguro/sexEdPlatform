@extends('layouts.admin')

@section('title', $subscriptionPlan->name)
@section('page-title', 'Plan: ' . $subscriptionPlan->name)

@section('content')
    {{-- Back link --}}
    <div class="mb-5">
        <a href="{{ route('admin.subscription-plans.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Plans
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        {{-- Plan Details --}}
        <div class="xl:col-span-2 space-y-5">
            {{-- Info Card --}}
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $subscriptionPlan->name }}</h2>
                        @if($subscriptionPlan->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $subscriptionPlan->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($subscriptionPlan->is_active)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">Active</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Price</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">₱{{ number_format($subscriptionPlan->price, 2) }}<span class="text-xs font-normal text-gray-400">/mo</span></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Trial</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $subscriptionPlan->trial_days ? $subscriptionPlan->trial_days . ' days' : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Max Modules</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $subscriptionPlan->max_modules ?: 'Unlimited' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Sort Order</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $subscriptionPlan->sort_order }}</p>
                    </div>
                </div>

                {{-- Features --}}
                @if(!empty($subscriptionPlan->features))
                    <div class="mt-4">
                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Features</p>
                        <ul class="space-y-2">
                            @foreach($subscriptionPlan->features as $feature)
                                <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="flex items-center gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.subscription-plans.edit', $subscriptionPlan) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors shadow-theme-xs">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Plan
                    </a>
                    <form method="POST" action="{{ route('admin.subscription-plans.toggle', $subscriptionPlan) }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            {{ $subscriptionPlan->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.subscription-plans.delete', $subscriptionPlan) }}" class="inline"
                          onsubmit="return confirm('Delete this plan? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-error-200 dark:border-error-500/30 text-error-500 dark:text-error-400 text-sm font-medium hover:bg-error-50 dark:hover:bg-error-500/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Subscribers Table --}}
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Subscribers</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-white/[0.02]">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($subscriptionPlan->subscriptions as $subscription)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 text-xs font-bold">
                                                {{ strtoupper(substr($subscription->user->name ?? '?', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $subscription->user->name ?? 'Unknown' }}</p>
                                                <p class="text-xs text-gray-400">{{ $subscription->user->email ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $subscription->status->value === 'active' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">
                                            {{ ucfirst($subscription->status->value ?? $subscription->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $subscription->starts_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $subscription->ends_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No subscribers yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Stats Sidebar --}}
        <div class="space-y-5">
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Plan Stats</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Subscribers</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $stats['total_subscribers'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Active</span>
                        <span class="text-sm font-bold text-success-600 dark:text-success-400">{{ $stats['active_subscribers'] }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800 pt-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Monthly Revenue</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">₱{{ number_format($stats['monthly_revenue'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Metadata</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Slug</span>
                        <code class="text-xs bg-gray-100 dark:bg-white/5 px-2 py-0.5 rounded text-gray-700 dark:text-gray-300">{{ $subscriptionPlan->slug }}</code>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Created</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $subscriptionPlan->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Updated</span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $subscriptionPlan->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
