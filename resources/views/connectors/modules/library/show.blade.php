@extends('layouts.connector-app')

@section('title', 'Purchased Module')
@section('page-title', 'Purchased Module')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <section class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Immutable purchase record</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $entitlement->module->title }}</h1>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="font-semibold text-gray-500">Status</dt><dd class="mt-1">{{ $entitlement->status }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Purchase Status</dt><dd class="mt-1">{{ $entitlement->purchase?->status ?? 'N/A' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Price</dt><dd class="mt-1">{{ $entitlement->currency }} {{ number_format((float) $entitlement->gross_amount, 2) }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Seats</dt><dd class="mt-1">{{ $entitlement->seat_capacity }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Purchased</dt><dd class="mt-1">{{ $entitlement->purchase?->purchased_at?->format('M d, Y h:i A') ?? 'N/A' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Terms Version</dt><dd class="mt-1">{{ $entitlement->purchase?->terms_version ?? 'N/A' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Seats Available</dt><dd class="mt-1">{{ $entitlement->available_seats }}</dd></div>
        </dl>
    </section>
    @if($canManage)
        <a href="{{ route('connector.modules.library.access', [$connector, $entitlement]) }}" class="inline-flex rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Manage Access</a>
    @endif
</div>
@endsection
