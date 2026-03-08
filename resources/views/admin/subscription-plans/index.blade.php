@extends('layouts.admin')

@section('title', 'Subscription Plans')
@section('page-title', 'Subscription Plans')

@section('content')

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage pricing, visibility, and feature access across your subscription catalog.</p>
        </div>
        <a href="{{ route('admin.subscription-plans.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors shadow-theme-xs">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Plan
        </a>
    </div>

    {{-- Stats --}}
    @php
        $statCards = [
            [
                'label' => 'Total Plans',
                'value' => $stats['total'],
                'icon' => 'M3 7.5A2.25 2.25 0 015.25 5.25h13.5A2.25 2.25 0 0121 7.5v9A2.25 2.25 0 0118.75 18.75H5.25A2.25 2.25 0 013 16.5v-9zm3.75 2.25a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5zm0 3.75a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z',
                'ring' => 'ring-brand-200 dark:ring-brand-800',
                'bg' => 'bg-brand-50 dark:bg-brand-500/10',
                'color' => 'text-brand-600 dark:text-brand-400',
            ],
            [
                'label' => 'Active Plans',
                'value' => $stats['active'],
                'icon' => 'M9 12.75l2.25 2.25L15 9.75m6 2.25a9 9 0 11-18 0 9 9 0 0118 0z',
                'ring' => 'ring-success-200 dark:ring-success-800',
                'bg' => 'bg-success-50 dark:bg-success-500/10',
                'color' => 'text-success-600 dark:text-success-400',
            ],
            [
                'label' => 'Inactive Plans',
                'value' => $stats['inactive'],
                'icon' => 'M18 12H6',
                'ring' => 'ring-gray-200 dark:ring-gray-800',
                'bg' => 'bg-gray-100 dark:bg-white/5',
                'color' => 'text-gray-500 dark:text-gray-400',
            ],
        ];

        $actionStyles = [
            'view' => 'text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400',
            'edit' => 'text-gray-400 hover:bg-warning-50 hover:text-warning-600 dark:hover:bg-warning-500/10 dark:hover:text-warning-400',
            'activate' => 'text-gray-400 hover:bg-success-50 hover:text-success-600 dark:hover:bg-success-500/10 dark:hover:text-success-400',
            'deactivate' => 'text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400',
            'delete' => 'text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400',
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        @foreach($statCards as $card)
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5 ring-1 {{ $card['ring'] }}">
            <div class="w-10 h-10 rounded-xl {{ $card['bg'] }} flex items-center justify-center mb-3">
                <svg class="w-5 h-5 {{ $card['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.subscription-plans.index') }}"
          class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-4 mb-5 shadow-theme-xs flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Plan name or description…"
                   class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition"/>
        </div>
        <div class="min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors shadow-theme-xs">
            Filter
        </button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.subscription-plans.index') }}"
               class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                Clear
            </a>
        @endif
    </form>

    {{-- Plans Table --}}
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Plan Catalog</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Review active pricing, free tiers, and premium access rules.</p>
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500">
                {{ $plans->total() }} {{ \Illuminate\Support\Str::plural('plan', $plans->total()) }} available
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-brand-50 dark:bg-brand-500/10">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Plan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Price</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Features</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Trial</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-brand-50 dark:hover:bg-brand-500/10 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7.5A2.25 2.25 0 015.25 5.25h13.5A2.25 2.25 0 0121 7.5v9A2.25 2.25 0 0118.75 18.75H5.25A2.25 2.25 0 013 16.5v-9zm3.75 2.25a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5zm0 3.75a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $plan->name }}</p>
                                        @if($plan->description)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 max-w-xl">{{ $plan->description }}</p>
                                        @else
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">No description added yet.</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">
                                        ₱{{ number_format($plan->price, 2) }}
                                    </span>
                                    <span class="text-xs text-gray-400">/mo</span>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $plan->isFree() ? 'Free access tier' : 'Recurring monthly billing' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ count($plan->features ?? []) }} {{ \Illuminate\Support\Str::plural('feature', count($plan->features ?? [])) }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($plan->trial_days)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                                        {{ $plan->trial_days }} days
                                    </span>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($plan->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.subscription-plans.show', $plan) }}"
                                       class="p-1.5 rounded-lg transition-colors {{ $actionStyles['view'] }}" title="View">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.subscription-plans.edit', $plan) }}"
                                       class="p-1.5 rounded-lg transition-colors {{ $actionStyles['edit'] }}" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.subscription-plans.toggle', $plan) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-1.5 rounded-lg transition-colors {{ $plan->is_active ? $actionStyles['deactivate'] : $actionStyles['activate'] }}"
                                                title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($plan->is_active)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.subscription-plans.delete', $plan) }}" class="inline"
                                          onsubmit="return confirm('Delete this plan? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-lg transition-colors {{ $actionStyles['delete'] }}" title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="text-sm text-gray-400 dark:text-gray-500">No subscription plans found.</p>
                                <a href="{{ route('admin.subscription-plans.create') }}" class="mt-2 inline-block text-sm text-brand-500 hover:text-brand-600">Create your first plan →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $plans->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
