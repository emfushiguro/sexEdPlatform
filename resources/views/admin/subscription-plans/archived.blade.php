@extends('layouts.admin')

@section('title', 'Archived Plans')
@section('page-title', 'Archived Plans')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Archived Plans</h1>
        <a
            href="{{ route('admin.subscription-plans.index') }}"
            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50"
        >
            Back to Plans
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Plan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Billing</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Archived</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($plans as $plan)
                        <tr class="transition-colors hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $plan->name }}</p>
                                @if($plan->description)
                                    <p class="mt-0.5 line-clamp-1 text-xs text-gray-400">{{ $plan->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ ucfirst($plan->billing_mode ?? 'monthly') }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ optional($plan->archived_at)->format('M d, Y h:i A') }}</td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('admin.subscription-plans.restore', $plan) }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-brand-600"
                                        title="Restore"
                                    >
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-400">No archived plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $plans->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
