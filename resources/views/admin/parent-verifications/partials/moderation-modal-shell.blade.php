<div x-show="rejectModalOpen"
     x-cloak
     @keydown.escape.window="closeRejectModal()"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 lg:p-8"
     data-testid="verification-moderation-modal-shell">
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeRejectModal()"></div>

    <div class="relative z-10 flex max-h-[calc(100vh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
            <button type="button" @click="closeRejectModal()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-5">
            @include('admin.parent-verifications.partials.rejection-form-fields', [
                'moderationReasons' => $moderationReasons,
                'showIssueWarning' => $showIssueWarning ?? true,
            ])
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
            <button type="button"
                    @click="closeRejectModal()"
                    :disabled="processingReject"
                    class="inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60">
                Cancel
            </button>
            <button type="button"
                    @click="submitRejectModal(@js($submitUrl))"
                    :disabled="processingApprove || processingReject"
                    class="inline-flex rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60">
                <span x-text="processingReject ? 'Rejecting...' : 'Confirm Reject'"></span>
            </button>
        </div>
    </div>
</div>
