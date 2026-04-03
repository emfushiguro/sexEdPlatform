<button type="button"
    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
    @click="approveModalOpen = true">
    Approve
</button>

<div x-show="approveModalOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 backdrop-blur-sm"
     @click.self="approveModalOpen = false">
    <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-2xl overflow-hidden">
        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-5">
            <h3 class="text-lg font-semibold text-gray-900">Confirm Module Approval</h3>
            <p class="mt-1 text-sm text-gray-500">Approving this module will make it available to the specified audience.</p>
        </div>

        <form method="POST" action="{{ route('admin.content-reviews.approve', $reviewRequest) }}">
            @csrf
            
            <div class="px-6 py-5">
                <div class="rounded-lg bg-emerald-50 p-4 border border-emerald-100 mb-5">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-emerald-600 mt-0.5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-emerald-800">You are about to finalize this request. An approval notification will be sent to the instructor and the module will be updated.</p>
                    </div>
                </div>

                <div>
                    <label for="approve_moderation_notes" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Moderation Notes (optional)</label>
                    <textarea id="approve_moderation_notes"
                        name="moderation_notes"
                        rows="6"
                        class="js-moderation-editor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        placeholder="Add rich-text notes representing approval remarks or minor adjustments made.">{{ old('moderation_notes') }}</textarea>
                    <p class="mt-2 text-xs text-gray-500">These notes are shared with the instructor in the approval notification.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                <button type="button"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"      
                    @click="approveModalOpen = false">
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors shadow-sm">
                    Confirm Approval
                </button>
            </div>
        </form>
    </div>
</div>
