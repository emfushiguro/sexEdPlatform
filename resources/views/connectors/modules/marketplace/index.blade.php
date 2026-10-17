@extends('layouts.connector-app')

@section('title', 'Module Marketplace')
@section('page-title', 'Module Marketplace')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <section class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Connector marketplace</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h1>
        <p class="mt-2 text-sm text-gray-500">Browse approved modules and fixed seat packages for your organization.</p>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($listings as $listing)
            @php
                $entitlement = $entitlements->get($listing->module_id);
                $packages = $listing->packages();
                $lowestPrice = $packages->min('price_amount');
            @endphp
            <div class="space-y-2">
                <x-learner.module-card-recommended :module="$listing->module" :show-enrollment-state="false"
                    :href="route('connector.modules.marketplace.show', [$connector, $listing])"
                    :badge="$entitlement ? 'Purchased license' : 'Organization license'"
                    action-label="View details"
                    :price-label="($packages->count() > 1 ? 'From ' : '') . $listing->currency . ' ' . number_format((float) $lowestPrice, 2)"
                    :seat-label="$packages->count() > 1 ? $packages->min('seat_capacity') . '–' . $packages->max('seat_capacity') . ' seats' : $listing->seat_capacity . ' seats'" />
                @if($entitlement)
                    <a href="{{ route('connector.modules.library.show', [$connector, $entitlement]) }}" class="block rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-center text-sm font-semibold text-emerald-800">View purchased license</a>
                @elseif($canManage)
                    <a href="{{ route('connector.modules.marketplace.show', [$connector, $listing]) }}#organization-license" class="block rounded-xl border border-brand-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-brand-700">Purchase for Connector</a>
                @endif
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">No approved module packages are available.</p>
        @endforelse
    </div>
</div>
@endsection
