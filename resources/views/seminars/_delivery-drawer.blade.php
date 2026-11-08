@php
    $deliveryErrors = $errors->hasAny(['external_url', 'external_link_visible_at', 'external_link_expiry_mode', 'external_link_expires_at', 'delivery_instructions']);
@endphp
<div x-data="{
    open: false, trigger: null, previousOverflow: '',
    init() { if (@js($deliveryErrors)) this.$nextTick(() => this.show()); },
    show() { this.trigger = document.activeElement; this.previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; this.open = true; this.$nextTick(() => this.$refs.close.focus()); },
    close() { this.open = false; document.body.style.overflow = this.previousOverflow; this.trigger?.focus(); },
    trap(event) { const items = [...this.$refs.panel.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')].filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length); const first = items[0], last = items[items.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); } }
}" @keydown.escape.window="if (open) { $event.preventDefault(); $event.stopPropagation(); close(); }">
    <button type="button" @click="show()" aria-haspopup="dialog" :aria-expanded="open" class="rounded-lg border border-purple-200 bg-white px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50">Manage event access</button>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[100000]" @keydown.tab="trap($event)">
            <div class="absolute inset-0 bg-gray-950/50" @click="close()" aria-hidden="true"></div>
            <section x-ref="panel" role="dialog" aria-modal="true" aria-labelledby="event-access-title" class="absolute inset-y-0 right-0 w-full max-w-xl overflow-y-auto overscroll-contain bg-white p-5 shadow-xl" @click.stop>
                <div class="mb-4 flex items-center justify-between gap-4">
                    <h2 id="event-access-title" class="text-lg font-bold text-gray-900">Manage event access</h2>
                    <button x-ref="close" type="button" @click="close()" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">Close</button>
                </div>
                @include('seminars._delivery-form', ['action' => $action])
            </section>
        </div>
    </template>
</div>
