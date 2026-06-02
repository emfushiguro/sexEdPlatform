@extends('layouts.connector-app')

@section('title', 'Subscription')
@section('page-title', 'Subscription')

@section('content')
<section class="rounded-xl border bg-white p-5 shadow-sm">
    <h2 class="font-bold">Current Plan</h2>
    <p class="mt-2 text-2xl font-bold">{{ $plan?->name ?? 'No active connector plan' }}</p>
    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        @foreach(config('connector_permissions.entitlements') as $label => $key)
            <div class="rounded-lg border px-4 py-3 text-sm">
                <p class="font-semibold capitalize">{{ $label }}</p>
                <p class="{{ in_array($key, $enabledEntitlements, true) ? 'text-green-700' : 'text-gray-500' }}">{{ in_array($key, $enabledEntitlements, true) ? 'Enabled' : 'Locked' }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mt-6 rounded-xl border bg-white p-5 shadow-sm">
    <h2 class="font-bold">Upgrade Options</h2>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        @forelse($plans as $availablePlan)
            <div class="rounded-lg border p-4">
                <p class="font-bold">{{ $availablePlan->name }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ $availablePlan->description }}</p>
                <p class="mt-3 text-lg font-bold">PHP {{ number_format((float) $availablePlan->price, 2) }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-500">No connector upgrade plans are available.</p>
        @endforelse
    </div>
</section>
@endsection
