@extends('layouts.admin')

@section('title', 'Subscription Management Center')
@section('page-title', 'Subscribers')

@section('content')

@php
    $statusColors = [
        'active' => 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400',
        'trialing' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400',
        'cancelled' => 'bg-error-100 text-error-700 dark:bg-error-500/10 dark:text-error-400',
        'expired' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        'past_due' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400',
    ];

    $subscriberCards = [
        [
            'label' => 'Total Subscribers',
            'value' => $subscriptionStats['total'] ?? 0,
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z',
            'ring' => 'ring-brand-200 dark:ring-brand-800',
            'bg' => 'bg-brand-50 dark:bg-brand-500/10',
            'color' => 'text-brand-600 dark:text-brand-400',
        ],
        [
            'label' => 'Active',
            'value' => $subscriptionStats['active'] ?? 0,
            'icon' => 'M9 12.75l2.25 2.25L15 9.75m6 2.25a9 9 0 11-18 0 9 9 0 0118 0z',
            'ring' => 'ring-success-200 dark:ring-success-800',
            'bg' => 'bg-success-50 dark:bg-success-500/10',
            'color' => 'text-success-600 dark:text-success-400',
        ],
        [
            'label' => 'Total Plans',
            'value' => $planStats['total'] ?? 0,
            'icon' => 'M3 7.5A2.25 2.25 0 015.25 5.25h13.5A2.25 2.25 0 0121 7.5v9A2.25 2.25 0 0118.75 18.75H5.25A2.25 2.25 0 013 16.5v-9zm3.75 2.25a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5zm0 3.75a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z',
            'ring' => 'ring-indigo-200 dark:ring-indigo-800',
            'bg' => 'bg-indigo-50 dark:bg-indigo-500/10',
            'color' => 'text-indigo-600 dark:text-indigo-400',
        ],
        [
            'label' => 'Active Plans',
            'value' => $planStats['active'] ?? 0,
            'icon' => 'M5 13l4 4L19 7',
            'ring' => 'ring-success-200 dark:ring-success-800',
            'bg' => 'bg-success-50 dark:bg-success-500/10',
            'color' => 'text-success-600 dark:text-success-400',
        ],
        [
            'label' => 'Expiring Soon',
            'value' => $subscriptionStats['expiring_soon'] ?? 0,
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'ring' => 'ring-warning-200 dark:ring-warning-800',
            'bg' => 'bg-warning-50 dark:bg-warning-500/10',
            'color' => 'text-warning-600 dark:text-warning-400',
        ],
    ];
@endphp

    <!-- Combined Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
        @foreach($subscriberCards as $card)
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5 ring-1 {{ $card['ring'] }}">
            <div class="w-10 h-10 rounded-xl {{ $card['bg'] }} flex items-center justify-center mb-3">
                <svg class="w-5 h-5 {{ $card['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('admin.subscribers.index') }}" class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-4 mb-5 shadow-theme-xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <div>
            <label for="status" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" id="status" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                <option value="">All</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="trialing" {{ request('status') == 'trialing' ? 'selected' : '' }}>Trialing</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="past_due" {{ request('status') == 'past_due' ? 'selected' : '' }}>Past Due</option>
            </select>
        </div>
        <div>
            <label for="plan_id" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Plan</label>
            <select name="plan_id" id="plan_id" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                <option value="">All</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="search" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Search</label>
            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Name or Email" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition" />
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Filter</button>
            <a href="{{ route('admin.subscribers.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Clear</a>
        </div>
        </div>
    </form>
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden mb-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Subscribers</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage active learners, plan assignments, and renewal risk at a glance.</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-brand-50 dark:bg-brand-500/10 text-brand-600 dark:text-brand-400 text-xs font-bold">
                {{ $subscriptions->total() ?? $subscriptions->count() }} total
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-brand-50 dark:bg-brand-500/10">
                    <tr>
                        <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Subscriber</th>
                        <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-brand-50 dark:hover:bg-brand-500/10 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 font-bold text-sm">
                                    {{ strtoupper(substr($subscription->user?->name ?? '?', 0, 1)) }}
                                </span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $subscription->user?->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $subscription->user?->email ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php $statusKey = is_object($subscription->status) ? $subscription->status->value : (string) $subscription->status; $statusColor = $statusColors[$statusKey] ?? 'bg-gray-100 text-gray-600'; @endphp
                            <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full {{ $statusColor }}">
                                {{ ucfirst($statusKey) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $subscription->plan?->name ?? ucfirst($subscription->plan ?? 'N/A') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('admin.subscribers.show', $subscription->id) }}" title="View" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            No subscribers found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subscriptions instanceof \Illuminate\Pagination\LengthAwarePaginator && $subscriptions->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
            {{ $subscriptions->links() }}
        </div>
        @endif
    </div>
@endsection