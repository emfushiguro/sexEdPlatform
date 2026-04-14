<div
    x-show="reportModalOpen"
    x-transition.opacity
    style="display: none;"
    class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4"
>
    <div @click.away="reportModalOpen = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Report Content</h3>
            <button type="button" @click="reportModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-5 py-5 space-y-4">
            @if($activeModuleReport)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    You already have an active module report ({{ is_object($activeModuleReport->status) ? $activeModuleReport->status->label() : ucfirst(str_replace('_', ' ', (string) $activeModuleReport->status)) }}).
                </div>
            @endif
            @if($activeInstructorReport)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                    You already have an active instructor report ({{ is_object($activeInstructorReport->status) ? $activeInstructorReport->status->label() : ucfirst(str_replace('_', ' ', (string) $activeInstructorReport->status)) }}).
                </div>
            @endif

            <form method="POST" action="{{ route('learner.reports.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">Report Target</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="reportTarget = 'module'"
                            :class="reportTarget === 'module' ? 'border-purple-300 bg-purple-50 text-purple-700' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                            class="rounded-xl border px-3 py-2 text-sm font-semibold transition-colors"
                        >
                            Report Module
                        </button>
                        @if($creator)
                            <button
                                type="button"
                                @click="reportTarget = 'instructor'"
                                :class="reportTarget === 'instructor' ? 'border-purple-300 bg-purple-50 text-purple-700' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                                class="rounded-xl border px-3 py-2 text-sm font-semibold transition-colors"
                            >
                                Report Instructor
                            </button>
                        @endif
                    </div>
                    <input type="hidden" name="target_type" :value="reportTarget">
                    <input type="hidden" name="target_id" :value="reportTarget === 'instructor' ? {{ (int) ($creator?->id ?? 0) }} : {{ (int) $module->id }}">
                    @error('target_type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('target_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Reason</label>
                    <select name="reason_code" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" required>
                        @foreach($reviewReasons as $reasonValue => $reasonLabel)
                            <option value="{{ $reasonValue }}" @selected(old('reason_code') === $reasonValue)>{{ $reasonLabel }}</option>
                        @endforeach
                    </select>
                    @error('reason_code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Additional Details</label>
                    <textarea name="details" rows="5" class="js-learner-rich-editor w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">{!! old('details') !!}</textarea>
                    @error('details')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="reportModalOpen = false" class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
