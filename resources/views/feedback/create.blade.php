@extends($supportLayout)

@section('title', 'Submit a Ticket | Conscious Connections')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-10">
        <nav aria-label="Breadcrumb" class="mb-7 mt-2 text-sm">
            <a href="{{ route($supportRoutes['help']['index']['name'], $supportRoutes['help']['index']['parameters']) }}" class="font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300">← Back to Help Center</a>
        </nav>

        <div>
            <x-support.page-header icon="feedback" title="Help & Support" description="Submit a ticket to report a problem, request assistance, share a suggestion, or raise a concern. Your ticket and personal information will remain private and can only be reviewed by authorized administrators." />
        </div>

        <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
            <form
                data-platform-feedback-form
                method="POST"
                action="{{ route($supportRoutes['feedback']['store']['name'], $supportRoutes['feedback']['store']['parameters']) }}"
                enctype="multipart/form-data"
                class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900"
                x-data="{ submitting: false, descriptionLength: {{ mb_strlen((string) old('description', '')) }}, rating: {{ (int) old('rating', 0) }}, attachmentName: '', attachmentPreview: '', previewAttachment(event) { const file = event.target.files[0]; if (this.attachmentPreview) URL.revokeObjectURL(this.attachmentPreview); this.attachmentName = file?.name || ''; this.attachmentPreview = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : ''; }, clearAttachment() { if (this.attachmentPreview) URL.revokeObjectURL(this.attachmentPreview); this.$refs.attachment.value = ''; this.attachmentName = ''; this.attachmentPreview = ''; } }"
                @submit="submitting = true"
                :aria-busy="submitting.toString()"
            >
                @csrf
                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">

                @if($errors->any())
                    <div role="alert" class="mb-7 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                        <p class="font-semibold">Please correct the highlighted fields.</p>
                        <p class="mt-1 text-sm">Your answers are still here, so you can make the changes and submit again.</p>
                    </div>
                @endif

                <div>
                    <label for="feedback-type" class="text-base font-semibold text-gray-950 dark:text-white">What would you like to share?</label>
                    <p id="type-help" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose the option that best matches your feedback.</p>
                    <select id="feedback-type" name="type" required aria-describedby="type-help @error('type') type-error @enderror" aria-invalid="{{ $errors->has('type') ? 'true' : 'false' }}" class="mt-3 w-full rounded-xl border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach($feedbackTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('type', $selectedType) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<p id="type-error" class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                </div>

                <div class="mt-8 border-t border-gray-100 pt-8 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tell us what happened</h2>
                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="feedback-subject" class="text-sm font-semibold text-gray-800 dark:text-gray-200">Subject</label>
                            <p id="subject-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">A short summary that helps us understand the issue.</p>
                            <input id="feedback-subject" name="subject" value="{{ old('subject') }}" required maxlength="180" aria-describedby="subject-help @error('subject') subject-error @enderror" aria-invalid="{{ $errors->has('subject') ? 'true' : 'false' }}" class="mt-2 w-full rounded-xl border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white" placeholder="For example: Module progress did not save">
                            @error('subject')<p id="subject-error" class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <div class="flex items-end justify-between gap-3">
                                <label for="feedback-description" class="text-sm font-semibold text-gray-800 dark:text-gray-200">Description</label>
                                <span class="text-xs text-gray-400"><span x-text="descriptionLength">{{ mb_strlen((string) old('description', '')) }}</span>/500</span>
                            </div>
                            <p id="description-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Share the steps you took, what you expected, and what happened instead. Do not include sensitive personal information.</p>
                            <textarea id="feedback-description" name="description" required maxlength="500" rows="7" x-on:input="descriptionLength = $event.target.value.length" aria-describedby="description-help @error('description') description-error @enderror" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" class="mt-2 w-full rounded-xl border-gray-300 bg-white px-3 py-2.5 text-sm leading-6 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white" placeholder="Describe the problem or idea in as much detail as you can.">{{ old('description') }}</textarea>
                            @error('description')<p id="description-error" class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-100 pt-8 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Optional context</h2>
                    <div class="mt-5 space-y-5">
                        <fieldset aria-describedby="rating-help @error('rating') rating-error @enderror">
                            <legend class="text-sm font-semibold text-gray-800 dark:text-gray-200">Overall experience rating</legend>
                            <p id="rating-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">One means very difficult; five means very good.</p>
                            <div class="mt-3 flex flex-wrap gap-1" role="radiogroup" aria-label="Overall experience rating">
                                @foreach(range(1, 5) as $rating)
                                    <label for="feedback-rating-{{ $rating }}" class="cursor-pointer">
                                        <input id="feedback-rating-{{ $rating }}" type="radio" name="rating" value="{{ $rating }}" class="peer sr-only" aria-label="{{ $rating }} out of 5 hearts" aria-describedby="rating-help @error('rating') rating-error @enderror" x-on:change="rating = {{ $rating }}" @checked((string) old('rating') === (string) $rating)>
                                        <span data-feedback-rating-heart class="grid h-11 w-11 place-items-center rounded-xl text-gray-300 transition hover:bg-rose-50 peer-focus-visible:ring-2 peer-focus-visible:ring-rose-400 dark:text-gray-600 dark:hover:bg-rose-950/40" :class="rating >= {{ $rating }} ? 'text-rose-500' : 'text-gray-300 dark:text-gray-600'" aria-hidden="true">
                                            <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor"><path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" /></svg>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('rating')<p id="rating-error" class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                        </fieldset>

                        <div>
                            <label for="feedback-attachment" class="text-sm font-semibold text-gray-800 dark:text-gray-200">Screenshot</label>
                            <p id="attachment-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional JPG, PNG, or WebP image up to 5 MB. Check that it does not show private information.</p>
                            <input x-ref="attachment" id="feedback-attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="attachment-help @error('attachment') attachment-error @enderror" aria-invalid="{{ $errors->has('attachment') ? 'true' : 'false' }}" @change="previewAttachment($event)" class="mt-2 block w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:border-0 file:bg-purple-50 file:px-4 file:py-3 file:font-semibold file:text-purple-700 hover:file:bg-purple-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:file:bg-purple-950 dark:file:text-purple-300">
                            <div x-cloak x-show="attachmentName" class="mt-2 flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <span class="truncate" x-text="attachmentName"></span>
                                <button type="button" class="font-semibold text-rose-700 dark:text-rose-300" @click="clearAttachment()">Remove</button>
                            </div>
                            <div x-cloak x-show="attachmentPreview" data-feedback-attachment-preview class="mt-3 overflow-hidden rounded-xl border border-purple-100 bg-purple-50/50 p-2 dark:border-purple-900 dark:bg-purple-950/30">
                                <img data-feedback-attachment-preview-image :src="attachmentPreview" alt="Selected screenshot preview" class="max-h-64 w-full rounded-lg object-contain">
                                <p class="mt-2 px-1 text-xs text-gray-500 dark:text-gray-400">Preview of the image that will be attached.</p>
                            </div>
                            @error('attachment')<p id="attachment-error" class="mt-2 text-sm font-medium text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex gap-3">
                                <input id="feedback-may-contact" type="checkbox" name="may_contact" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-700 focus:ring-purple-400" @checked(old('may_contact'))>
                                <div>
                                    <label for="feedback-may-contact" class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white">You may contact me about this feedback</label>
                                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">Leave this unchecked if you do not want a follow-up.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                    <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Help &amp; Support is not monitored as an urgent safety channel.</p>
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70" type="submit" :disabled="submitting">
                        <span x-show="!submitting">Submit a Ticket</span>
                        <span x-cloak x-show="submitting">Submitting…</span>
                    </button>
                </div>
            </form>

            <aside class="space-y-4 lg:sticky lg:top-6">
                <section class="rounded-2xl border border-purple-100 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="font-semibold text-gray-950 dark:text-white">What happens next?</h2>
                    <ol class="mt-3 space-y-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
                        <li><strong class="text-gray-900 dark:text-gray-200">1.</strong> You receive a reference number.</li>
                        <li><strong class="text-gray-900 dark:text-gray-200">2.</strong> The platform team reviews your ticket.</li>
                        <li><strong class="text-gray-900 dark:text-gray-200">3.</strong> Track updates under My Tickets.</li>
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</main>
@endsection
