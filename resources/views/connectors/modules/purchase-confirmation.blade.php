@extends('layouts.connector-app')

@section('title', 'Purchase Confirmation')
@section('page-title', 'Purchase Confirmation')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    @include('connectors.modules.partials.tabs', ['entitlement' => $entitlement])
    <section class="rounded-[28px] border border-emerald-200 bg-emerald-50 p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Payment confirmed</p>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">Your organization successfully purchased this module.</h1>
        <p class="mt-3 text-sm text-gray-700">The license is now in your organization learning library. No members were assigned automatically.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('connector.modules.library.index', $connector) }}" class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">View Learning Library</a>
            @if($canManage)
                <a href="{{ route('connector.modules.library.access', [$connector, $entitlement]) }}" class="rounded-lg border border-brand-300 bg-white px-4 py-2 text-sm font-semibold text-brand-700">Configure Access</a>
            @endif
        </div>
    </section>
    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
        <h2 class="text-lg font-bold text-gray-900">{{ $purchase->module_title_snapshot }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ $purchase->currency }} {{ number_format((float) $purchase->gross_amount, 2) }} · {{ $purchase->seat_capacity }} seats · Terms {{ $purchase->terms_version }}</p>
    </section>
</div>
@endsection
