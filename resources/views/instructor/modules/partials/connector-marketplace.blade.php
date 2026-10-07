@php
    $marketplaceListing = $module->marketplaceListing;
    $marketplaceRoutePrefix = $contentRoutePrefix ?? 'instructor';
    $canManageMarketplace = !($isReadOnlyAdminPanel ?? false);
@endphp

<section class="mt-6 rounded-2xl border border-violet-200 bg-violet-50 p-6" aria-labelledby="connector-marketplace-heading">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 id="connector-marketplace-heading" class="text-base font-semibold text-violet-950">Connector marketplace</h2>
            <p class="mt-1 text-sm text-violet-800">Sell fixed seat packages to verified Connectors without changing individual learner pricing.</p>
        </div>
        @if($marketplaceListing)
            <span class="rounded-full border border-violet-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-violet-800">
                {{ ucfirst($marketplaceListing->status) }}
            </span>
        @endif
    </div>

    @if($canManageMarketplace)
        <form method="POST" action="{{ route($marketplaceRoutePrefix . '.modules.marketplace.update', $module) }}" class="mt-5 grid gap-4 sm:grid-cols-4">
            @csrf
            @method('PUT')
            <div>
                <label for="marketplace-price" class="block text-xs font-semibold text-violet-950">Package price</label>
                <input id="marketplace-price" name="price_amount" type="number" min="0.01" step="0.01"
                    value="{{ old('price_amount', $marketplaceListing?->price_amount) }}"
                    class="mt-1 w-full rounded-lg border-violet-200 bg-white text-sm">
            </div>
            <div>
                <label for="marketplace-currency" class="block text-xs font-semibold text-violet-950">Currency</label>
                <input id="marketplace-currency" name="currency" value="{{ old('currency', $marketplaceListing?->currency ?? config('billing.currency.code')) }}"
                    maxlength="3" class="mt-1 w-full rounded-lg border-violet-200 bg-white text-sm">
            </div>
            <div>
                <label for="marketplace-seats" class="block text-xs font-semibold text-violet-950">Seats</label>
                <input id="marketplace-seats" name="seat_capacity" type="number" min="1"
                    value="{{ old('seat_capacity', $marketplaceListing?->seat_capacity ?? 1) }}"
                    class="mt-1 w-full rounded-lg border-violet-200 bg-white text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button name="action" value="save" type="submit" class="rounded-lg border border-violet-300 bg-white px-3 py-2 text-sm font-semibold text-violet-800">Save</button>
                <button name="action" value="publish" type="submit" class="rounded-lg bg-violet-700 px-3 py-2 text-sm font-semibold text-white">Publish</button>
            </div>
        </form>

        @if($marketplaceListing?->isPublished())
            <form method="POST" action="{{ route($marketplaceRoutePrefix . '.modules.marketplace.destroy', $module) }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-violet-800 underline">Unpublish listing</button>
            </form>
        @endif
    @endif

    @if($errors->has('listing'))
        <p class="mt-3 text-sm font-medium text-red-700">{{ $errors->first('listing') }}</p>
    @endif
</section>
