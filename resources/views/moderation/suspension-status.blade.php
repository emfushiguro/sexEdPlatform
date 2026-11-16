@extends('layouts.moderation')

@section('title', 'Account Suspension Status | Conscious Connections')
@section('meta_description', 'View your suspension details and appeal guidance.')

@section('content')
<div>
    <div class="mx-auto max-w-3xl space-y-6">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-red-200 bg-red-50 px-6 py-5">
            <h1 class="text-2xl font-bold text-red-800">Your account is suspended</h1>
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
                    <dd class="mt-1 text-sm text-gray-800">{{ $suspension->ends_at?->format('M d, Y h:i A T') ?? 'Permanent' }}</dd>
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

            @if($suspension->ends_at)
                <p class="mt-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Access is expected to resume after {{ $suspension->ends_at->format('M d, Y h:i A T') }}. Sign in again after that time.
                </p>
            @endif

            @if($suspension->notes)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <span class="font-semibold">Moderator Note:</span>
                    <p class="mt-1">{{ $suspension->notes }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if((string) $suspension->status === 'active')
                    <a href="{{ route('moderation.appeals.create', $suspension) }}"
                       class="inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Submit Appeal
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}" data-logout-form>
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-purple-300 bg-white px-4 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
