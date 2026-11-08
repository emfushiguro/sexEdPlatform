@extends('layouts.admin')

@section('title', 'Connector Module Access')
@section('page-title', 'Connector Module Access')

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.connectors.index') }}" class="text-sm font-semibold text-brand-700">← Back to connectors</a>
    <header class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Organization learning</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Module access management</h1>
        <p class="mt-2 text-sm text-gray-600">Assign specific members and staff using the organization's existing module license.</p>
        <form method="GET" class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end">
            <label class="block min-w-0 flex-1 text-sm font-semibold text-gray-700">Connector organization
                <select name="connector_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                    <option value="">Choose an organization</option>
                    @foreach($connectors as $option)
                        <option value="{{ $option->id }}" @selected($connector?->id === $option->id)>{{ $option->name }} · {{ ucfirst($option->status) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Select organization</button>
        </form>
        @if($connector)
            <form method="GET" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                <input type="hidden" name="connector_id" value="{{ $connector->id }}">
                <label class="block min-w-0 flex-1 text-sm font-semibold text-gray-700">Licensed module
                    <select name="entitlement_id" required class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">Choose a module</option>
                        @foreach($entitlements as $option)
                            <option value="{{ $option->id }}" @selected($entitlement?->id === $option->id)>{{ $option->module?->title ?? 'Unavailable module' }} · License #{{ $option->id }} · {{ $option->status }}{{ !$option->module?->isLearnerVisible() ? ' · Unpublished' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Manage access</button>
            </form>
            @if($entitlements->isEmpty())
                <p class="mt-4 text-sm text-gray-500">This Connector has no organizational module licenses. Access management cannot create a license or purchase.</p>
            @endif
        @endif
    </header>

    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">The access request needs attention.</p>
            <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($entitlement)
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h2 class="break-words text-xl font-bold text-gray-900">{{ $entitlement->module?->title ?? 'Unavailable module' }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ str(strip_tags($entitlement->module?->description ?? ''))->limit(300) }}</p>
            <p class="mt-3 text-sm text-gray-600">
                License #{{ $entitlement->id }} · {{ ucfirst($entitlement->status) }} ·
                {{ $entitlement->module?->isLearnerVisible() ? 'Published' : 'Unpublished' }} ·
                {{ $counts['used'] }} of {{ $entitlement->seat_capacity }} seats reserved ·
                {{ max(0, $entitlement->seat_capacity - $counts['used']) }} available
            </p>
            @if(!$entitlement->isUsable() || $connector->status !== 'verified' || !$entitlement->module?->isLearnerVisible())
                <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Grants are unavailable until the Connector is verified, the module is published, and its license is usable. Previous assignments can still be revoked.</p>
            @endif
            <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                @foreach(['eligible' => 'Eligible users', 'with' => 'Users with access', 'without' => 'Without access'] as $key => $label)
                    <div class="rounded-xl bg-gray-50 p-4"><dt class="text-sm text-gray-600">{{ $label }}</dt><dd class="mt-1 text-2xl font-bold text-gray-900">{{ $counts[$key] }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-3 text-xs text-gray-500">Counts cover the full organization roster. Access includes valid independent grants and other Connector licenses. Pending guardian approval reserves a seat without activating learning access.</p>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h2 class="text-lg font-bold text-gray-900">Members and staff</h2>
            <form method="GET" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <input type="hidden" name="connector_id" value="{{ $connector->id }}">
                <input type="hidden" name="entitlement_id" value="{{ $entitlement->id }}">
                <label class="text-sm text-gray-700">Search
                    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or email" class="mt-1 block w-full rounded-lg border-gray-300">
                </label>
                <label class="text-sm text-gray-700">Organizational role
                    <select name="role_id" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">All roles</option>
                        @foreach($roles as $role)<option value="{{ $role->id }}" @selected(($filters['role_id'] ?? '') == $role->id)>{{ $role->name }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm text-gray-700">Membership
                    <select name="membership" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">All memberships</option>
                        @foreach($membershipStatuses as $status)<option value="{{ $status }}" @selected(($filters['membership'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm text-gray-700">Module access
                    <select name="access" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">All access states</option>
                        @foreach(['with' => 'With access', 'without' => 'Without access', 'pending' => 'Pending guardian', 'revoked' => 'Revoked through Connector'] as $value => $label)<option value="{{ $value }}" @selected(($filters['access'] ?? '') === $value)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <button class="self-end rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Apply filters</button>
            </form>
            <form method="POST" action="{{ route('admin.connectors.module-access.review', [$connector, $entitlement]) }}" class="mt-5" x-data="{ selected: [] }">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Connector members, organizational roles, membership and module access</caption>
                        <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-600"><tr>
                            <th scope="col" class="p-3"><input type="checkbox" aria-label="Select all users on this page" @change="selected = $event.target.checked ? @js($members->getCollection()->filter(fn ($row) => $row['canGrant'] || $row['assignment'])->map(fn ($row) => (string) $row['user']->id)->values()) : []"></th>
                            <th scope="col" class="p-3">User</th><th scope="col" class="p-3">Organizational role</th><th scope="col" class="p-3">Membership</th><th scope="col" class="p-3">Eligibility</th><th scope="col" class="p-3">Module access</th><th scope="col" class="p-3">Actions</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($members as $row)
                                <tr data-module-member="{{ $row['user']->id }}">
                                    <td class="p-3"><input type="checkbox" name="user_ids[]" value="{{ $row['user']->id }}" x-model="selected" @disabled(!$row['canGrant'] && !$row['assignment']) aria-label="Select {{ $row['user']->name }}"></td>
                                    <th scope="row" class="min-w-56 p-3 font-medium text-gray-900">@include('modules.partials.access-member')</th>
                                    <td class="p-3">{{ $row['membership']?->role?->name ?? 'Former member' }}</td>
                                    <td class="p-3">{{ ucfirst($row['membership']?->status ?? 'removed') }}@if(!$row['eligible'])<span class="block text-xs text-gray-500">Ineligible for grant</span>@endif</td>
                                    <td class="min-w-48 p-3">@include('modules.partials.access-eligibility')</td>
                                    <td class="p-3">
                                        {{ $row['hasConnector'] ? 'Connector access active' : ($row['assignment'] ? str($row['assignment']->status)->headline() : 'No Connector access') }}
                                        @if($row['hasIndependent'])<span class="block text-xs font-semibold text-emerald-700">Independent access active</span>@endif
                                        @if($row['hasOtherConnector'])<span class="block text-xs font-semibold text-emerald-700">Access through another Connector</span>@endif
                                        @if($row['assignment']?->inactive_reason)<span class="block text-xs text-gray-500">{{ str($row['assignment']->inactive_reason)->headline() }}</span>@endif
                                    </td>
                                    <td class="p-3">
                                        @include('modules.partials.access-actions', ['adminActions' => true])
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="p-6 text-center text-gray-500">No users match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <label class="text-sm font-semibold text-gray-700">Action
                        <select name="action" class="mt-1 block rounded-lg border-gray-300"><option value="grant">Grant access</option><option value="revoke">Revoke Connector access</option></select>
                    </label>
                    <button class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="selected.length === 0">Review selected users</button>
                    <span class="py-2 text-sm text-gray-500" x-text="selected.length + ' selected'"></span>
                </div>
                <p class="mt-3 text-xs text-gray-500">Selection applies to this page only. Every selected user is checked again on confirmation; a failure leaves the entire selection unchanged.</p>
            </form>
            @foreach($members as $row)
                <form id="admin-member-action-{{ $row['user']->id }}" method="POST" action="{{ route('admin.connectors.module-access.review', [$connector, $entitlement]) }}">
                    @csrf
                    <input type="hidden" name="user_ids[]" value="{{ $row['user']->id }}">
                </form>
            @endforeach
            <div class="mt-5">{{ $members->links() }}</div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h2 class="text-lg font-bold text-gray-900">Admin grant and revoke history</h2>
            <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="border-b text-xs uppercase text-gray-500"><tr><th scope="col" class="p-3">Time</th><th scope="col" class="p-3">Administrator</th><th scope="col" class="p-3">User</th><th scope="col" class="p-3">Action</th><th scope="col" class="p-3">Result</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($history as $log)
                        <tr><td class="p-3">{{ $log->created_at->timezone(config('app.display_timezone'))->format('M d, Y g:i A') }}</td><td class="p-3">{{ $log->adminUser?->name ?? 'Former administrator' }}</td><td class="p-3">{{ $log->meta_json['user_name'] ?? 'User' }} (#{{ $log->meta_json['user_id'] }})</td><td class="p-3">{{ str($log->action)->afterLast('.')->headline() }}</td><td class="p-3">{{ str($log->after_json['status'])->headline() }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="p-4 text-gray-500">No admin access changes recorded for this license.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="mt-5">{{ $history->links() }}</div>
        </section>
    @endif
</div>
@endsection
