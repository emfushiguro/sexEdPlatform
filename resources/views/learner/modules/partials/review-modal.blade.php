<div
    x-show="reviewModalOpen"
    x-transition.opacity
    style="display: none;"
    class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4"
>
    <div @click.away="reviewModalOpen = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $userFeedback ? 'Update Review' : 'Write Review' }}</h3>
            <button type="button" @click="reviewModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @if($canSubmitReview)
            <form method="POST" action="{{ route('learner.modules.feedback.store', $module) }}" class="space-y-4 px-5 py-5" x-data="{ selectedRating: {{ (int) old('rating', $userFeedback?->rating ?? 0) }} }">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Rating</label>
                    <input type="hidden" name="rating" :value="selectedRating">
                    <div class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2 dark:border-gray-700">
                        @foreach(range(1, 5) as $ratingOption)
                            <button
                                type="button"
                                aria-label="Select {{ $ratingOption }} hearts"
                                @click="selectedRating = {{ $ratingOption }}"
                                class="transition-transform duration-150 hover:scale-110 focus:outline-none"
                            >
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" :class="selectedRating >= {{ $ratingOption }} ? 'text-rose-500' : 'text-gray-300 dark:text-gray-600'">
                                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="selectedRating > 0 ? `${selectedRating} heart${selectedRating > 1 ? 's' : ''} selected` : 'Select a rating to continue.'"></p>
                    @error('rating')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Review Content</label>
                    <textarea
                        name="review_content"
                        rows="6"
                        class="js-learner-rich-editor w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"
                        required
                    >{!! old('review_content', $userFeedback?->review_html) !!}</textarea>
                    @error('review_content')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="reviewModalOpen = false" class="px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                    >
                        {{ $userFeedback ? 'Update Review' : 'Submit Review' }}
                    </button>
                </div>
            </form>
        @else
            <div class="px-5 py-6">
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $reviewBlocker ?: 'Review submission is currently unavailable.' }}</p>
            </div>
        @endif
    </div>
</div>
