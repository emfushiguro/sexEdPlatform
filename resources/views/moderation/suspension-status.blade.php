@extends('layouts.app')

@section('title', 'Account Suspension Status | Concious Connections')
@section('meta_description', 'View your suspension details and appeal guidance.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white py-10 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-2xl border border-red-200 bg-red-50 px-6 py-5">
            <h1 class="text-2xl font-bold text-red-800">Your access is temporarily restricted</h1>
            <p class="mt-2 text-sm text-red-700">
                An active suspension is currently applied to your account. Review the details below.
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suspension Status</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst((string) $suspension->status) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action Type</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', (string) $suspension->enforcementAction?->action_type?->value ?? 'suspension') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Started At</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $suspension->starts_at?->format('M d, Y h:i A') ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ends At</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $suspension->ends_at?->format('M d, Y h:i A') ?? 'Permanent' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Case Reference</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $suspension->moderationCase?->case_reference_code ?? 'Not available' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Appeal Status</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ str_replace('_', ' ', (string) $suspension->appeal_status) }}</dd>
                </div>
            </dl>

            @if($suspension->notes)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <span class="font-semibold">Moderator Note:</span>
                    <p class="mt-1">{{ $suspension->notes }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
