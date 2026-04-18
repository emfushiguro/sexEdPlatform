<div x-show="rejectModalOpen"
     x-cloak
     @keydown.escape.window="closeRejectModal()"
    class="fixed inset-0 z-[100200] flex items-center justify-center p-4 text-left whitespace-normal sm:p-6 lg:p-8"
     data-testid="verification-moderation-modal-shell">
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeRejectModal()"></div>

    <div class="relative z-10 flex max-h-[calc(100vh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white text-left whitespace-normal shadow-2xl sm:max-h-[calc(100vh-3rem)]">
        <div class="shrink-0 border-b border-gray-100 px-6 py-5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                <button type="button" @click="closeRejectModal()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="mt-2 text-sm text-gray-600">Confirm rejection details before submitting this moderation decision.</p>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            @include('admin.parent-verifications.partials.rejection-form-fields', [
                'moderationReasons' => $moderationReasons,
                'showIssueWarning' => $showIssueWarning ?? true,
            ])
        </div>

        <div class="flex shrink-0 flex-col gap-3 border-t border-gray-100 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex w-full flex-col-reverse gap-2 lg:w-auto lg:flex-row lg:items-center lg:justify-end lg:shrink-0">
                <button type="button"
                        @click="closeRejectModal()"
                        :disabled="processingReject"
                        class="inline-flex w-full justify-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60 lg:w-auto">
                    Keep as Pending
                </button>
                <button type="button"
                        @click="submitRejectModal(@js($submitUrl))"
                        :disabled="processingApprove || processingReject"
                        class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60 lg:w-auto">
                    <span x-text="processingReject ? 'Rejecting...' : 'Confirm Rejection'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
