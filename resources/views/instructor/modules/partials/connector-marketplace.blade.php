@php
    $marketplaceListing = $module->marketplaceListing;
    $marketplaceRoutePrefix = $contentRoutePrefix ?? 'instructor';
    $canManageMarketplace = !($isReadOnlyAdminPanel ?? false);
    $marketplaceHasErrors = old('marketplace_listing') && $errors->any();
    $marketplaceSeats = old('seat_capacity', $marketplaceListing?->seat_capacity ?? 10);
    $marketplaceAvailable = $marketplaceCapacity['available'];
@endphp

<section class="mb-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
    data-marketplace-errors="{{ $marketplaceHasErrors ? 'true' : 'false' }}"
    x-data="{
        open: @js((bool) $marketplaceHasErrors),
        seats: @js((string) $marketplaceSeats),
        packages: @js(old('seat_packages', old('seat_packages_present') ? [] : ($marketplaceListing?->seat_packages ?? []))),
        available: @js($marketplaceAvailable),
        previousOverflow: '',
        init() {
            this.$watch('open', value => {
                if (value) {
                    this.previousOverflow = document.body.style.overflow;
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => this.$refs.dialog.focus());
                } else {
                    document.body.style.overflow = this.previousOverflow;
                    this.$nextTick(() => this.$refs.trigger.focus());
                }
            });
            if (this.open) {
                this.previousOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => this.$refs.dialog.focus());
            }
        },
        trapFocus(event) {
            const items = Array.from(this.$refs.dialog.querySelectorAll('button, input, a[href], [tabindex]'))
                .filter(item => !item.disabled && item.tabIndex >= 0 && item.getClientRects().length);
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.dialog)) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && (document.activeElement === last || document.activeElement === this.$refs.dialog)) {
                event.preventDefault();
                first?.focus();
            }
        }
    }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-semibold text-gray-900">Connector listing</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ ucfirst($marketplaceListing?->status ?? 'Not listed') }}</span>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                @if($marketplaceListing)
                    {{ $marketplaceListing->currency }} {{ number_format((float) $marketplaceListing->price_amount, 2) }}
                    &middot; {{ $marketplaceListing->seat_capacity }} seats per purchase
                    @if(count($marketplaceListing->seat_packages ?? [])) &middot; {{ count($marketplaceListing->seat_packages) }} additional packages @endif
                @else
                    Offer a seat package to verified Connectors.
                @endif
            </p>
        </div>
        @if($canManageMarketplace)
            <button type="button" x-ref="trigger" @click="open = true" :aria-expanded="open"
                aria-controls="connector-listing-drawer" aria-haspopup="dialog"
                class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                Manage connector listing
            </button>
        @endif
    </div>

    @if($canManageMarketplace)
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[99999]" @keydown.escape.window="if (open) open = false">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="open = false" aria-hidden="true"
                x-show="open" x-transition.opacity></div>
            <aside id="connector-listing-drawer" x-ref="dialog" role="dialog" aria-modal="true"
                aria-labelledby="connector-listing-title" aria-describedby="connector-listing-description" tabindex="-1"
                @keydown.tab="trapFocus($event)" x-show="open"
                x-transition:enter="transition transform ease-out duration-300 motion-reduce:transition-none"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition transform ease-in duration-200 motion-reduce:transition-none"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col bg-white shadow-2xl outline-none">
                <header class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-700">Connector marketplace</p>
                        <h2 id="connector-listing-title" class="mt-1 text-lg font-bold text-gray-900">Configure seat packages</h2>
                        <p id="connector-listing-description" class="mt-1 text-sm text-gray-500">{{ $module->title }}</p>
                    </div>
                    <button type="button" @click="open = false" aria-label="Close connector listing"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </header>
                <div class="flex-1 space-y-6 overflow-y-auto px-6 py-6">
                    @if($marketplaceListing?->status === 'suspended')
                        <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Suspension reason: {{ $marketplaceListing->suspension_reason }}</p>
                    @endif
                    @if($marketplaceHasErrors)
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                            <p class="font-semibold">Check your listing before continuing.</p>
                            @foreach(['listing', 'capacity', 'price_amount', 'currency', 'seat_capacity', 'action'] as $field)
                                @error($field)<p class="mt-1">{{ $message }}</p>@enderror
                            @endforeach
                            @foreach($errors->get('seat_packages*') as $messages)
                                @foreach($messages as $message)<p class="mt-1">{{ $message }}</p>@endforeach
                            @endforeach
                        </div>
                    @endif
                    <form id="connector-listing-form" method="POST" action="{{ route($marketplaceRoutePrefix . '.modules.marketplace.update', $module) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="marketplace_listing" value="1">
                        <input type="hidden" name="seat_packages_present" value="1">
                        <div>
                            <label for="marketplace-price" class="text-sm font-semibold text-gray-900">Package price ({{ config('billing.currency.code') }})</label>
                            <input id="marketplace-price" name="price_amount" type="number" min="0.01" step="0.01" required
                                value="{{ old('price_amount', $marketplaceListing?->price_amount) }}" aria-describedby="marketplace-price-help"
                                class="mt-2 block w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                            <input type="hidden" name="currency" value="{{ config('billing.currency.code') }}">
                            <p id="marketplace-price-help" class="mt-2 text-xs leading-relaxed text-gray-500">One payment for the whole seat package. Individual learner pricing stays unchanged.</p>
                        </div>
                        <div>
                            <label for="marketplace-seats" class="text-sm font-semibold text-gray-900">Seats per Connector purchase</label>
                            <input id="marketplace-seats" name="seat_capacity" type="number" min="1" required x-model="seats"
                                value="{{ $marketplaceSeats }}" aria-describedby="marketplace-seat-help"
                                class="mt-2 block w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                            <div class="mt-3 flex flex-wrap gap-2" aria-label="Seat package suggestions">
                                @foreach([10, 25, 50] as $seatOption)
                                    <button type="button" @click="seats = '{{ $seatOption }}'" :aria-pressed="Number(seats) === {{ $seatOption }}"
                                        :class="Number(seats) === {{ $seatOption }} ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-700'"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:border-brand-300 hover:bg-brand-50">{{ $seatOption }} learners</button>
                                @endforeach
                            </div>
                            <p id="marketplace-seat-help" class="mt-2 text-xs leading-relaxed text-gray-500">For example, 50 seats lets one Connector assign this module to up to 50 eligible members at a time. Released seats can be reassigned.</p>
                            <p x-show="available !== null && Number(seats) > available" x-cloak class="mt-2 text-sm text-amber-800" role="status">This package exceeds the seats currently available. Reduce its size before publishing.</p>
                        </div>
                        <section class="space-y-3 border-t border-gray-100 pt-5" aria-label="Additional fixed seat packages">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900">Additional fixed packages</h3>
                                <button type="button" @click="packages.push({seat_capacity: '', price_amount: ''})" :disabled="packages.length >= 9"
                                    class="rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-brand-700 disabled:opacity-50">Add package</button>
                            </div>
                            <p class="text-xs leading-relaxed text-gray-500">Connectors choose one package. Set a different seat quantity and total price for each option, in {{ config('billing.currency.code') }}. Your default package above remains available.</p>
                            <template x-for="(pkg, index) in packages" :key="index">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="text-xs font-medium text-gray-700">Seats
                                            <input type="number" min="1" required x-model="pkg.seat_capacity" :name="'seat_packages[' + index + '][seat_capacity]'"
                                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                                        </label>
                                        <label class="text-xs font-medium text-gray-700">Package price
                                            <input type="number" min="0.01" step="0.01" required x-model="pkg.price_amount" :name="'seat_packages[' + index + '][price_amount]'"
                                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                                        </label>
                                    </div>
                                    <p x-show="available !== null && Number(pkg.seat_capacity) > available" class="text-xs text-amber-800">Exceeds the seats currently available.</p>
                                    <button type="button" @click="packages.splice(index, 1)" class="text-xs font-semibold text-red-700 underline">Remove package</button>
                                </div>
                            </template>
                        </section>
                    </form>
                    <section class="rounded-xl border border-gray-200 bg-gray-50 p-4" aria-label="Shared module capacity">
                        <p class="text-sm font-semibold text-gray-900">{{ $marketplaceAvailable === null ? 'No total learner limit' : $marketplaceAvailable . ' seats available' }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500">{{ $marketplaceCapacity['used'] }} seats in use or reserved across individual learners and Connector purchases.
                            @if($marketplaceCapacity['limit'] !== null) The module limit is {{ $marketplaceCapacity['limit'] }} learners. @endif
                        </p>
                        <a href="{{ route($marketplaceRoutePrefix . '.modules.index', ['edit_module' => $module->id]) }}" class="mt-3 inline-block text-xs font-semibold text-brand-700 underline">Edit module learner limit</a>
                    </section>
                    @if(!$module->isLearnerVisible())
                        <p class="text-sm text-amber-800">Save your package now. Publish it after the module is approved and published to learners.</p>
                    @endif
                    @if($marketplaceListing?->isPublished())
                        <form method="POST" action="{{ route($marketplaceRoutePrefix . '.modules.marketplace.destroy', $module) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-red-700 underline">Unpublish listing</button>
                            <p class="mt-1 text-xs text-gray-500">Hide this package from new purchases. Existing purchases and learning history remain.</p>
                        </form>
                    @endif
                </div>
                <footer class="flex flex-wrap justify-end gap-2 border-t border-gray-100 bg-white px-6 py-4">
                    <button type="button" @click="open = false" class="rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button type="submit" form="connector-listing-form" name="action" value="save" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ $marketplaceListing?->isPublished() ? 'Save changes' : 'Save draft' }}</button>
                    <button type="submit" form="connector-listing-form" name="action" value="publish" @disabled(!$module->isLearnerVisible())
                        class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50">{{ $marketplaceListing?->isPublished() ? 'Update published listing' : 'Publish listing' }}</button>
                </footer>
            </aside>
        </div>
    </template>
    @endif
</section>
