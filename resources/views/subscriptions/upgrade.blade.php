@extends('layouts.learner-app')

@section('title', 'Upgrade Subscription')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="rounded-2xl border border-purple-200/60 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">Upgrade Subscription</h1>
        <p class="mt-2 text-sm text-gray-600">Compare available durations and continue to payment when ready.</p>
    </div>

    @if($hasActiveSubscription)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            You currently have an active subscription. Cancel current plan to switch.
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @forelse($availablePlans as $plan)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>

                <div class="mt-4 space-y-2">
                    @forelse($plan->planPrices as $price)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                            <span class="text-sm font-medium text-gray-700">{{ $price->duration_label ?? ucfirst($price->duration_unit ?? 'Duration') }}</span>
                            <span class="text-sm font-semibold text-gray-900">PHP {{ number_format(((int) $price->amount_minor) / 100, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No active durations configured.</p>
                    @endforelse
                </div>

                <form class="mt-4" method="POST" action="{{ route('subscription.subscribe') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                        Continue to payment process
                    </button>
                </form>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-sm text-gray-500">
                No plans are currently available.
            </div>
        @endforelse
    </div>
</div>
@endsection
