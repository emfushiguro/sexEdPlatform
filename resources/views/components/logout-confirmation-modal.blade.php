<div
    x-data="{
        isOpen: false,
        pendingForm: null,
        returnFocus: null,
        request(detail) {
            this.pendingForm = detail?.form || null;
            this.returnFocus = detail?.trigger || document.activeElement;
            this.isOpen = true;
            this.$nextTick(() => this.$refs.dialog?.focus());
        },
        close() {
            this.isOpen = false;
            const trigger = this.returnFocus;
            this.pendingForm = null;
            this.returnFocus = null;
            this.$nextTick(() => trigger?.focus?.());
        },
        confirm() {
            const form = this.pendingForm;
            this.isOpen = false;
            this.pendingForm = null;
            this.returnFocus = null;

            if (!form) {
                return;
            }

            form.dataset.logoutConfirmed = 'true';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                HTMLFormElement.prototype.submit.call(form);
            }
        },
        handleKeydown(event) {
            if (event.key !== 'Tab') {
                return;
            }

            const focusable = Array.from(this.$refs.dialog.querySelectorAll(
                'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]',
            )).filter((element) => element.tabIndex >= 0);

            if (focusable.length === 0) {
                event.preventDefault();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    }"
    x-show="isOpen"
    x-cloak
    x-transition.opacity
    x-on:logout-confirmation-request.window="request($event.detail)"
    x-on:keydown.escape.window="if (isOpen) close()"
    class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-950/60 p-4"
    data-logout-confirmation-modal
    role="presentation"
    @click.self="close()"
>
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-ref="dialog"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-confirmation-title"
        aria-describedby="logout-confirmation-description"
        tabindex="-1"
        @click.stop
        @keydown="handleKeydown($event)"
    >
        <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 8.25 19.5 12l-3.75 3.75M19.5 12H8.25m3-8.25H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25h5.25" />
                    </svg>
                </div>
                <div>
                    <h2 id="logout-confirmation-title" class="text-lg font-semibold text-slate-900">Log out?</h2>
                    <p id="logout-confirmation-description" class="mt-1 text-sm leading-6 text-slate-600">
                        Are you sure you want to log out of Conscious Connections?
                    </p>
                </div>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:ring-offset-2"
                    @click="close()"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"
                    @click="confirm()"
                >
                    Log out
                </button>
            </div>
        </div>
    </div>
</div>
