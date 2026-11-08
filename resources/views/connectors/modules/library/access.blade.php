@extends('layouts.connector-app')

@section('title', 'Access Management')
@section('page-title', 'Access Management')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <section class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Access Management</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $entitlement->module?->title ?? 'Unavailable module' }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ $entitlement->used_seats }} used · {{ $entitlement->available_seats }} available</p>
        <p class="mt-2 text-sm text-gray-600">Module audience: {{ $entitlement->module ? implode(', ', $entitlement->module->learnerCategoryLabels()) : 'Unavailable' }}</p>
        @php($grantableMembers = $members->filter(fn ($row) => $row['canGrant'] && !$row['hasAssignment']))
        <button type="button" aria-haspopup="dialog" aria-controls="grant-module-access" @disabled($grantableMembers->isEmpty())
            @click="document.getElementById('grant-module-access').showModal()" class="mt-5 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Grant Access</button>
        <p class="mt-2 text-xs text-gray-500">Select a specific eligible member. Existing seat limits and guardian approval requirements apply.</p>
    </section>

    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div role="alert" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">The access request needs attention.</p>
            @foreach($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
        <h2 class="text-lg font-bold text-gray-900">Members</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <caption class="sr-only">Members, module eligibility, access status and actions</caption>
                <thead><tr class="border-b text-xs uppercase text-gray-500"><th scope="col" class="px-3 py-2">Member</th><th scope="col" class="px-3 py-2">Role / membership</th><th scope="col" class="px-3 py-2">Eligibility</th><th scope="col" class="px-3 py-2">Access status</th><th scope="col" class="px-3 py-2">Actions</th></tr></thead>
                <tbody>
                    @forelse($members as $row)
                        <tr class="border-b border-gray-100 align-top" data-module-member="{{ $row['user']->id }}">
                            <td class="min-w-56 px-3 py-3">@include('modules.partials.access-member')</td>
                            <td class="px-3 py-3">{{ $row['membership']?->role?->name ?? 'Former member' }}<span class="mt-1 block text-xs text-gray-500">{{ ucfirst($row['membership']?->status ?? 'removed') }}</span></td>
                            <td class="min-w-48 px-3 py-3">@include('modules.partials.access-eligibility')</td>
                            <td class="px-3 py-3">
                                {{ str($row['assignment']?->status ?? 'unassigned')->headline() }}
                                @if($row['assignment']?->inactive_reason)<span class="mt-1 block text-xs text-gray-500">{{ str($row['assignment']->inactive_reason)->headline() }}</span>@endif
                                @if($row['hasIndependent'])<span class="mt-1 block text-xs text-emerald-700">Independent access active</span>@endif
                                @if($row['hasOtherConnector'])<span class="mt-1 block text-xs text-emerald-700">Access through another Connector</span>@endif
                            </td>
                            <td class="px-3 py-3">
                                @include('modules.partials.access-actions')
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-gray-500">No organization members yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($entitlement->rules->where('is_active', true)->isNotEmpty())
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h2 class="text-lg font-bold text-gray-900">Existing access grants</h2>
            <ul class="mt-4 divide-y divide-gray-100">
                @foreach($entitlement->rules->where('is_active', true) as $rule)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span>{{ $rule->target_type === 'member' ? data_get($members->firstWhere('user.id', $rule->target_id), 'user.name', 'Former member') : str_replace('_', ' ', ucfirst($rule->target_type)).($rule->target_id ? ': '.$rule->target_id : '') }}</span>
                        <button type="button" aria-haspopup="dialog" aria-controls="remove-grant-{{ $rule->id }}"
                            @click="document.getElementById('remove-grant-{{ $rule->id }}').showModal()" class="text-sm font-semibold text-rose-700">Remove grant</button>
                        <x-module-access-dialog :id="'remove-grant-'.$rule->id" title="Remove access grant?">
                            <p class="text-sm text-gray-600">Members who depend only on this grant will lose access. Other valid access and learning history are preserved.</p>
                            <form method="POST" action="{{ route('connector.modules.access-rules.destroy', [$connector, $entitlement, $rule]) }}" class="mt-5 space-y-4">
                                @csrf @method('DELETE')
                                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1 rounded border-gray-300">I confirm removing this access grant.</label>
                                <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Remove grant</button>
                            </form>
                        </x-module-access-dialog>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <x-module-access-dialog id="grant-module-access" title="Grant Access">
        <form method="POST" action="{{ route('connector.modules.access.member', [$connector, $entitlement]) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="action" value="grant">
            <label class="block text-sm font-semibold text-gray-700">Eligible member
                <select name="user_id" required class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Select a member</option>
                    @foreach($grantableMembers as $row)<option value="{{ $row['user']->id }}">{{ $row['user']->name }} · {{ $row['ageLabel'] }}</option>@endforeach
                </select>
            </label>
            <p class="text-sm text-gray-600">Access uses an existing organizational seat. Eligibility and capacity are checked again when submitted.</p>
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1 rounded border-gray-300">I confirm granting access to the selected member.</label>
            <button class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Grant Access</button>
        </form>
    </x-module-access-dialog>

    @foreach($members as $row)
        @foreach(['grant', 'revoke'] as $action)
            @if(($action === 'grant' && $row['canGrant'] && !$row['hasAssignment']) || ($action === 'revoke' && $row['canRevoke']))
                <x-module-access-dialog :id="$action.'-access-'.$row['user']->id" :title="$action === 'grant' ? 'Grant Access' : 'Revoke Access'">
                    @include('seminars._participant-profile', ['user' => $row['user']])
                    <p class="mt-4 text-sm text-gray-600">{{ $action === 'grant' ? 'Grant organizational access to this module? Seat limits and guardian approval still apply.' : 'Revoke access through this Connector? Separate valid access and learning records are preserved.' }}</p>
                    <form method="POST" action="{{ route('connector.modules.access.member', [$connector, $entitlement]) }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="action" value="{{ $action }}"><input type="hidden" name="user_id" value="{{ $row['user']->id }}">
                        <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1 rounded border-gray-300">I reviewed this member and confirm the action.</label>
                        <button class="rounded-lg px-4 py-2 text-sm font-semibold text-white {{ $action === 'grant' ? 'bg-brand-700' : 'bg-rose-700' }}">{{ $action === 'grant' ? 'Grant Access' : 'Revoke Access' }}</button>
                    </form>
                </x-module-access-dialog>
            @endif
        @endforeach
    @endforeach
</div>
@endsection
