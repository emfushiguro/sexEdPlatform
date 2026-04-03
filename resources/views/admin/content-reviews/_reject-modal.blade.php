@php
    use App\Enums\ModuleReviewRejectionReason;
@endphp

<button type="button"
    class="inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500"
    @click="rejectModalOpen = true">
    Reject
</button>

<div x-show="rejectModalOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
     @click.self="rejectModalOpen = false">
    <div class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white shadow-2xl">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">Reject Module Submission</h3>
        </div>

        <form method="POST" action="{{ route('admin.content-reviews.reject', $reviewRequest) }}" class="space-y-4 px-5 py-4" @submit="syncModerationEditors()">
            @csrf
            <div>
                <label for="reason_code" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Rejection Reason</label>
                <select id="reason_code" name="reason_code" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="">Select a reason</option>
                    @foreach(ModuleReviewRejectionReason::cases() as $reason)
                        <option value="{{ $reason->value }}" @selected(old('reason_code') === $reason->value)>{{ $reason->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reject_moderation_notes" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Moderation Notes (optional)</label>
                <textarea id="reject_moderation_notes"
                    name="moderation_notes"
                    rows="6"
                    class="js-moderation-editor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="Add rich-text coaching notes and concrete fixes for the instructor.">{{ old('moderation_notes') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">These notes are shared with the instructor in the rejection decision notification.</p>
            </div>

            <label for="issue_warning" class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-700">
                <input type="checkbox" id="issue_warning" name="issue_warning" value="1" @checked(old('issue_warning')) class="mt-0.5 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                <span>
                    <span class="font-semibold text-gray-900">Issue Warning to Instructor</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Enable only for policy or safety violations. Disable for non-violation corrections.</span>
                </span>
            </label>

            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="rejectModalOpen = false">
                    Cancel
                </button>

                <button type="submit" class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>
