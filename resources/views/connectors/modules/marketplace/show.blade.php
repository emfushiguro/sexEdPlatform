@extends('layouts.connector-app')

@section('title', $module->title)
@section('page-title', 'Module Details')

@section('content')
<div class="space-y-6">
    @include('connectors.modules.partials.tabs')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('connector.modules', $connector) }}" class="text-sm font-semibold text-brand-700">Back to marketplace</a>
    </div>
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @include('modules.partials.details-overview', ['module' => $module])
            @include('modules.partials.details-curriculum', ['lessons' => $lessons, 'canOpenLessons' => false])
        </div>
        <aside id="organization-license" class="space-y-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm lg:sticky lg:top-6" aria-labelledby="license-heading">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-700">For {{ $connector->name }}</p>
                <h2 id="license-heading" class="mt-1 text-lg font-bold text-gray-900">Organization license</h2>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">License seats for your members, not a personal module purchase. The module remains owned by its creator.</p>
            </div>
            @if($entitlement)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="font-semibold">Purchased license &middot; {{ ucfirst($entitlement->status) }}</p>
                    <p class="mt-1">{{ $entitlement->seat_capacity }} seats &middot; {{ $entitlement->currency }} {{ number_format((float) $entitlement->gross_amount, 2) }}</p>
                </div>
                <a href="{{ route('connector.modules.library.show', [$connector, $entitlement]) }}" class="block rounded-xl bg-brand-700 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-brand-800">View purchased license</a>
            @elseif($pendingPurchase)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Checkout pending &middot; {{ $pendingPurchase->seat_capacity }} seats</p>
                    <p class="mt-1">{{ $pendingPurchase->currency }} {{ number_format((float) $pendingPurchase->gross_amount, 2) }} reserved until {{ $pendingPurchase->capacity_hold_expires_at->format('H:i') }}. Complete this checkout before choosing another package.</p>
                </div>
                @if($canManage)
                    <a href="{{ route('connector.modules.purchase.checkout', [$connector, $listing, 'seat_capacity' => $pendingPurchase->seat_capacity]) }}" class="block rounded-xl bg-brand-700 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-brand-800">Continue pending checkout</a>
                @endif
            @else
                <form method="GET" action="{{ route('connector.modules.purchase.checkout', [$connector, $listing]) }}" class="space-y-3">
                    <fieldset class="space-y-2">
                        <legend class="mb-3 text-sm font-semibold text-gray-900">Choose a seat package</legend>
                        @foreach($listing->packages() as $package)
                            @php($packageUnavailable = $marketplaceCapacity['available'] !== null && $package['seat_capacity'] > $marketplaceCapacity['available'])
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 {{ $packageUnavailable ? 'opacity-50' : '' }}">
                                <input type="radio" name="seat_capacity" value="{{ $package['seat_capacity'] }}" required
                                    @checked((int) $package['seat_capacity'] === (int) $listing->seat_capacity && !$packageUnavailable)
                                    @disabled($packageUnavailable) class="text-brand-700 focus:ring-brand-500">
                                <span class="flex-1 text-sm">
                                    <span class="flex items-center justify-between gap-3 font-semibold text-gray-900"><span>{{ $package['seat_capacity'] }} seats</span><span>{{ $listing->currency }} {{ number_format((float) $package['price_amount'], 2) }}</span></span>
                                    <span class="mt-1 block text-xs text-gray-500">{{ $packageUnavailable ? 'Not enough shared capacity' : 'One-time payment for the whole package' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                    @if($canManage)
                        <button type="submit" @disabled($listing->packages()->every(fn ($package) => $marketplaceCapacity['available'] !== null && $package['seat_capacity'] > $marketplaceCapacity['available']))
                            class="w-full rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">Purchase for Connector</button>
                    @else
                        <p class="rounded-xl bg-gray-50 p-3 text-sm text-gray-600">Only authorized Connector managers can purchase and assign seats.</p>
                    @endif
                </form>
            @endif
            <dl class="space-y-4 border-t border-gray-100 pt-5 text-sm">
                <div><dt class="font-semibold text-gray-900">Eligible learners</dt><dd class="mt-1 text-gray-600">{{ $module->min_age !== null ? 'Ages ' . $module->min_age . '+' : 'No minimum age' }}{{ $module->max_age !== null ? ' · up to age ' . $module->max_age : '' }}. Members must meet the module age and account-verification requirements.</dd></div>
                <div><dt class="font-semibold text-gray-900">Assign seats after purchase</dt><dd class="mt-1 text-gray-600">Choose all eligible members, a role, or selected members. Paying does not automatically enroll the manager.</dd></div>
                <div><dt class="font-semibold text-gray-900">Reusable seats, personal learning records</dt><dd class="mt-1 text-gray-600">Revoking access or removing a member releases their seat. Their progress, quiz results and certificates are retained.</dd></div>
                <div><dt class="font-semibold text-gray-900">Non-refundable</dt><dd class="mt-1 text-gray-600">One-time organization purchase. No Connector subscription required.</dd></div>
                <div><dt class="font-semibold text-gray-900">Shared capacity</dt><dd class="mt-1 text-gray-600">{{ $marketplaceCapacity['available'] === null ? 'No total learner limit configured.' : $marketplaceCapacity['available'] . ' seats currently available across all purchase channels.' }} Availability is checked again at checkout.</dd></div>
            </dl>
        </aside>
    </div>
</div>
@endsection
