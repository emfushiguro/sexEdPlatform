@extends('layouts.admin')

@section('title', 'Review Module Access')
@section('page-title', 'Review Module Access')

@section('content')
<section class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
    <h1 class="text-xl font-bold text-gray-900">Review {{ $data['action'] === 'grant' ? 'grant' : 'revocation' }} for {{ $selected->count() }} {{ $selected->count() === 1 ? 'user' : 'users' }}</h1>
    <p class="mt-2 text-sm text-gray-600">{{ $connector->name }} · {{ $entitlement->module?->title ?? 'Unavailable module' }} · License #{{ $entitlement->id }}</p>
    <ul class="mt-5 divide-y divide-gray-100">
        @foreach($selected as $row)
            <li class="break-words py-3 text-sm"><strong>{{ $row['user']->name }}</strong> · {{ $row['user']->email }}
                <span class="mt-1 block text-gray-500">{{ $row['membership']?->role?->name ?? 'Former member' }} · {{ ucfirst($row['membership']?->status ?? 'removed') }} · {{ str($row['assignment']?->status ?? 'unassigned')->headline() }}</span>
            </li>
        @endforeach
    </ul>
    <p class="mt-5 text-sm text-gray-600">{{ $data['action'] === 'grant' ? 'Access uses existing organizational seats. Guardian approval may still be required. No learner is charged.' : 'Only access through this Connector license will be revoked. Separate valid individual access remains available.' }} Progress, quiz results, completion records, and certificates are preserved.</p>
    <p class="mt-3 text-sm text-gray-600">Eligibility and seats will be checked again. If any selected user cannot be processed, the entire operation is cancelled.</p>
    <form method="POST" action="{{ route('admin.connectors.module-access.update', [$connector, $entitlement]) }}" class="mt-5">
        @csrf
        <input type="hidden" name="action" value="{{ $data['action'] }}">
        @foreach($selected as $row)<input type="hidden" name="user_ids[]" value="{{ $row['user']->id }}">@endforeach
        <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="confirmed" value="1" required class="mt-0.5 rounded border-gray-300">I reviewed the selected users and confirm this {{ $data['action'] === 'grant' ? 'grant' : 'revocation' }}.</label>
        <div class="mt-5 flex flex-wrap gap-3">
            <button class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Confirm {{ $data['action'] }}</button>
            <a href="{{ route('admin.connectors.module-access.index', ['connector_id' => $connector->id, 'entitlement_id' => $entitlement->id]) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</a>
        </div>
    </form>
</section>
@endsection
