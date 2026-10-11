@extends('layouts.connector-app')

@section('title', 'Purchased Modules')
@section('page-title', 'Purchased Modules')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <section class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Organization learning library</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h1>
        <p class="mt-2 text-sm text-gray-500">Purchased module licenses and their current seat availability.</p>
    </section>

    <div class="grid gap-5 md:grid-cols-2">
        @forelse($entitlements as $entitlement)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ ucfirst($entitlement->status) }}</p>
                <h2 class="mt-2 text-lg font-bold text-gray-900">{{ $entitlement->module->title }}</h2>
                <p class="mt-3 text-sm text-gray-600">
                    {{ $entitlement->currency }} {{ number_format((float) $entitlement->gross_amount, 2) }}
                    · purchased {{ $entitlement->purchase?->purchased_at?->format('M d, Y') ?? 'N/A' }}
                </p>
                <div class="mt-4 flex gap-4 text-sm">
                    <span class="font-semibold text-gray-800">{{ $entitlement->used_seats }} used</span>
                    <span class="text-gray-600">{{ $entitlement->available_seats }} available</span>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('connector.modules.library.show', [$connector, $entitlement]) }}" class="rounded-lg border border-brand-200 px-3 py-2 text-sm font-semibold text-brand-700">View purchase</a>
                    @if($canManage)
                        <a href="{{ route('connector.modules.library.access', [$connector, $entitlement]) }}" class="rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white">Manage Access</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 md:col-span-2">No purchased modules yet.</p>
        @endforelse
    </div>
</div>
@endsection
