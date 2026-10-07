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
            @php($entitlement = $entitlements->get($listing->module_id))
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ ucfirst($listing->seller_type) }} seller</p>
                <h2 class="mt-2 text-lg font-bold text-gray-900">{{ $listing->module->title }}</h2>
                <p class="mt-2 line-clamp-3 text-sm text-gray-600">{{ strip_tags((string) $listing->module->description) }}</p>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="font-semibold text-gray-900">{{ $listing->currency }} {{ number_format((float) $listing->price_amount, 2) }}</span>
                    <span class="text-gray-600">{{ $listing->seat_capacity }} seats</span>
                </div>
                @if($entitlement)
                    <p class="mt-3 text-xs font-semibold text-emerald-700">Existing entitlement: {{ ucfirst($entitlement->status) }}</p>
                @endif
                <div class="mt-4 flex items-center gap-2">
                    <a href="{{ route('connector.modules.marketplace.show', [$connector, $listing]) }}" class="flex-1 rounded-lg border border-brand-200 px-3 py-2 text-center text-sm font-semibold text-brand-700">View details</a>
                    @if($canManage)
                        <a href="{{ route('connector.modules.purchase.checkout', [$connector, $listing]) }}" class="rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white">Purchase for Connector</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 md:col-span-2 xl:col-span-3">No approved module packages are available.</p>
        @endforelse
    </div>
</div>
@endsection
