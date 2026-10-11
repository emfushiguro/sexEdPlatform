@extends('layouts.connector-app')

@section('title', 'Access Management')
@section('page-title', 'Access Management')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <section class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Access Management</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $entitlement->module->title }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ $entitlement->used_seats }} used · {{ $entitlement->available_seats }} available</p>
        <form method="POST" action="{{ route('connector.modules.access-rules.store', [$connector, $entitlement]) }}" class="mt-5 flex flex-wrap items-end gap-3">
            @csrf
            <label class="text-sm font-semibold text-gray-700">Grant to
                <select name="target_type" class="mt-1 block rounded-lg border-gray-300 text-sm">
                    <option value="all_members">All members</option>
                    <option value="role">Role</option>
                    <option value="member">Member</option>
                </select>
            </label>
            <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Add Rule</button>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Rules</h2>
            <span class="text-sm text-gray-500">Reconcile Access on page load</span>
        </div>
        <ul class="mt-4 divide-y divide-gray-100">
            @forelse($entitlement->rules->where('is_active', true) as $rule)
                <li class="flex items-center justify-between gap-3 py-3 text-sm">
                    <span>{{ str_replace('_', ' ', ucfirst($rule->target_type)) }}{{ $rule->target_id ? ': '.$rule->target_id : '' }}</span>
                    <form method="POST" action="{{ route('connector.modules.access-rules.destroy', [$connector, $entitlement, $rule]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-700">Remove</button>
                    </form>
                </li>
            @empty
                <li class="py-3 text-sm text-gray-500">No active rules.</li>
            @endforelse
        </ul>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
        <h2 class="text-lg font-bold text-gray-900">Members</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead><tr class="border-b text-xs uppercase text-gray-500"><th class="px-3 py-2">Member</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Reason</th></tr></thead>
                <tbody>
                    @forelse($entitlement->assignments as $assignment)
                        <tr class="border-b border-gray-100"><td class="px-3 py-3">{{ $assignment->user?->name ?? 'Unknown member' }}</td><td class="px-3 py-3">{{ $assignment->status }}</td><td class="px-3 py-3">{{ $assignment->inactive_reason ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-3 text-gray-500">No member assignments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
