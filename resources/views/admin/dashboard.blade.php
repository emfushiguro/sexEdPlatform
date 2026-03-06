@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        {{-- Total Users --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \App\Models\User::count() }}</p>
            <a href="{{ route('admin.users.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                View all
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Instructors --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Instructors</span>
                <div class="w-10 h-10 rounded-xl bg-success-50 dark:bg-success-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \App\Models\User::role('instructor')->count() }}</p>
            <a href="{{ route('admin.users.index') }}?role=instructor" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                View all
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Total Modules --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Modules</span>
                <div class="w-10 h-10 rounded-xl bg-warning-50 dark:bg-warning-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-warning-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Module::count() }}</p>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">All published modules</p>
        </div>

        {{-- Active Subscriptions --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscriptions</span>
                <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ \App\Models\Subscription::active()->count() }}</p>
            <a href="{{ route('admin.subscribers.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                View all
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    {{-- Financial & Subscription Stats --}}
    @php
        $revenueMonth  = \App\Models\Payment::completed()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');
        $revenueTotal  = \App\Models\Payment::completed()->sum('amount');
        $newSubs30     = \App\Models\Subscription::where('created_at', '>=', now()->subDays(30))->count();
        $cancelledSubs = \App\Models\Subscription::where('status', 'cancelled')->count();
        $totalSubs     = \App\Models\Subscription::count();
        $churnRate     = $totalSubs > 0 ? round(($cancelledSubs / $totalSubs) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        {{-- Revenue this month --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue (This Month)</span>
                <div class="w-10 h-10 rounded-xl bg-success-50 dark:bg-success-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">₱{{ number_format($revenueMonth, 2) }}</p>
            <a href="{{ route('admin.payments.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                View payments
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Total Revenue --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">₱{{ number_format($revenueTotal, 2) }}</p>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Completed payments</p>
        </div>

        {{-- New Subscribers (30 days) --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">New Subscribers (30d)</span>
                <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $newSubs30 }}</p>
            <a href="{{ route('admin.subscribers.index') }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                View subscribers
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Churn Rate --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Churn Rate</span>
                <div class="w-10 h-10 rounded-xl bg-error-50 dark:bg-error-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-error-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $churnRate }}%</p>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ $cancelledSubs }} cancelled of {{ $totalSubs }} total</p>
        </div>
    </div>

    {{-- Content Row: Recent Subscriptions + Platform Summary --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">

        {{-- Recent Subscriptions Table (2/3) --}}
        <div class="xl:col-span-2 rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Recent Subscriptions</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Latest subscriber activity</p>
                </div>
                <a href="{{ route('admin.subscribers.index') }}"
                   class="text-xs font-bold text-brand-500 hover:text-brand-600 dark:text-brand-400 flex items-center gap-1">
                    View all
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Subscriber</th>
                            <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 bg-brand-50 dark:bg-brand-500/10 text-left text-xs font-bold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach(\App\Models\Subscription::with(['user','plan'])->latest()->limit(7)->get() as $sub)
                        <tr class="hover:bg-brand-50 dark:hover:bg-brand-500/10 transition">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 font-bold text-xs">
                                        {{ strtoupper(substr($sub->user?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{{ $sub->user?->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400">{{ $sub->user?->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $sub->plan?->name ?? ucfirst($sub->plan ?? 'N/A') }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $c = ['active'=>'bg-success-100 text-success-700','trialing'=>'bg-brand-100 text-brand-700','cancelled'=>'bg-error-100 text-error-700','expired'=>'bg-gray-100 text-gray-500','past_due'=>'bg-warning-100 text-warning-700'];
                                    $statusKey = is_object($sub->status) ? $sub->status->value : (string) $sub->status;
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $c[$statusKey] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($statusKey) }}</span>
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-400">{{ $sub->created_at->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Platform Summary (1/3) --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6 flex flex-col gap-1">
            <h2 class="text-base font-bold text-gray-900 dark:text-white mb-3">Platform Summary</h2>

            @php
                $summaryItems = [
                    ['label' => 'Total Users',        'value' => \App\Models\User::count(),                                     'color' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-500'],
                    ['label' => 'Learners',           'value' => \App\Models\User::role('learner')->count(),                    'color' => 'bg-brand-50 dark:bg-brand-500/10 text-brand-500'],
                    ['label' => 'Instructors',        'value' => \App\Models\User::role('instructor')->count(),                 'color' => 'bg-success-50 dark:bg-success-500/10 text-success-500'],
                    ['label' => 'Active Subs',        'value' => \App\Models\Subscription::where('status','active')->count(),   'color' => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500'],
                    ['label' => 'Cancelled Subs',     'value' => \App\Models\Subscription::where('status','cancelled')->count(),'color' => 'bg-error-50 dark:bg-error-500/10 text-error-500'],
                    ['label' => 'Expiring Soon',      'value' => \App\Models\Subscription::expiringSoon()->count(),             'color' => 'bg-warning-50 dark:bg-warning-500/10 text-warning-500'],
                    ['label' => 'Total Modules',      'value' => \App\Models\Module::count(),                                   'color' => 'bg-purple-50 dark:bg-purple-500/10 text-purple-500'],
                    ['label' => 'Active Plans',       'value' => \App\Models\SubscriptionPlan::where('is_active',true)->count(),'color' => 'bg-teal-50 dark:bg-teal-500/10 text-teal-500'],
                ];
            @endphp

            <div class="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($summaryItems as $item)
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item['label'] }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $item['color'] }}">{{ $item['value'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
