@php
    $captionTopic = $topic ?? null;
    $oldCaptionTracks = old('captions');
    $hasOldCaptions = is_array($oldCaptionTracks);
    $captionTracks = $hasOldCaptions
        ? collect($oldCaptionTracks)
        : ($captionTopic?->captions ?? collect());
    $defaultIndex = $hasOldCaptions ? old('caption_default') : $captionTracks->search(
        fn ($caption): bool => (bool) data_get($caption, 'is_default'),
    );
    $defaultIndex = $defaultIndex === false ? null : $defaultIndex;
@endphp

<section
    class="hidden p-6 mb-6 bg-white border border-gray-100 shadow-sm rounded-2xl"
    data-caption-tracks-form
    data-next-index="{{ $captionTracks->count() }}"
>
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Caption tracks</h2>
            <p class="mt-1 text-sm text-gray-500">WebVTT up to 2 MB. Add language and learner-facing label.</p>
        </div>
        <button type="button" data-add-caption class="px-4 py-2 text-sm font-semibold text-white bg-purple-600 rounded-lg hover:bg-purple-700">
            Add caption track
        </button>
    </div>

    <div class="mb-4">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input
                type="radio"
                name="caption_default"
                value=""
                data-caption-no-default
                @checked($defaultIndex === null || $defaultIndex === '')
            >
            No default
        </label>
        @error('caption_default')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-4" data-caption-rows>
        @foreach($captionTracks as $index => $caption)
            @php
                $captionId = data_get($caption, 'id');
                $captionLanguage = data_get($caption, 'language_code', '');
                $captionLabel = data_get($caption, 'label', '');
                $captionPath = data_get($caption, 'file_path');
                $captionFileName = $captionPath ? basename($captionPath) : '';
                $captionRemoved = filter_var(data_get($caption, 'remove', false), FILTER_VALIDATE_BOOL);
                $captionFileUrl = is_object($caption) && $captionPath ? $caption->file_url : null;
            @endphp
            <div class="p-4 border border-gray-200 rounded-xl" data-caption-row data-removed="{{ $captionRemoved ? 'true' : 'false' }}" @if($captionRemoved) hidden @endif>
                <input type="hidden" name="captions[{{ $index }}][id]" value="{{ $captionId }}" data-caption-id>
                <input type="hidden" name="captions[{{ $index }}][remove]" value="{{ $captionRemoved ? 1 : 0 }}" data-caption-remove-value>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="caption_language_{{ $index }}" class="block mb-1 text-sm font-medium text-gray-700">Language code</label>
                        <input
                            type="text"
                            id="caption_language_{{ $index }}"
                            name="captions[{{ $index }}][language_code]"
                            value="{{ old('captions.'.$index.'.language_code', $captionLanguage) }}"
                            list="caption-language-codes"
                            maxlength="35"
                            placeholder="en"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                        >
                        @error('captions.'.$index.'.language_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="caption_label_{{ $index }}" class="block mb-1 text-sm font-medium text-gray-700">Label</label>
                        <input
                            type="text"
                            id="caption_label_{{ $index }}"
                            name="captions[{{ $index }}][label]"
                            value="{{ old('captions.'.$index.'.label', $captionLabel) }}"
                            maxlength="100"
                            placeholder="English"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                        >
                        @error('captions.'.$index.'.label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label for="caption_file_{{ $index }}" class="block mb-1 text-sm font-medium text-gray-700">
                        {{ $captionId ? 'Replace caption file' : 'Caption file' }}
                    </label>
                    <input
                        type="file"
                        id="caption_file_{{ $index }}"
                        name="captions[{{ $index }}][file]"
                        accept=".vtt,text/vtt,text/plain"
                        data-caption-file
                        aria-describedby="caption_file_name_{{ $index }} caption_file_error_{{ $index }}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg"
                    >
                    <p id="caption_file_name_{{ $index }}" data-caption-file-name class="mt-1 text-xs text-gray-500">{{ $captionFileName }}</p>
                    <p id="caption_file_error_{{ $index }}" data-caption-file-error class="hidden mt-1 text-sm text-red-600" role="alert"></p>
                    @error('captions.'.$index.'.file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if($captionFileUrl)
                        <a href="{{ $captionFileUrl }}" target="_blank" rel="noopener" class="inline-block mt-2 text-sm font-medium text-purple-700 hover:underline">View caption file</a>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="caption_default"
                            value="{{ $index }}"
                            @checked((string) $defaultIndex === (string) $index)
                        >
                        Use as default
                    </label>
                    <button type="button" data-remove-caption class="text-sm font-medium text-red-600 hover:text-red-700">Remove track</button>
                </div>
                @error('captions.'.$index.'.id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>

    <datalist id="caption-language-codes">
        <option value="en"></option>
        <option value="fil"></option>
        <option value="ko"></option>
        <option value="es"></option>
        <option value="fr"></option>
        <option value="de"></option>
        <option value="ja"></option>
        <option value="zh"></option>
    </datalist>

    <template data-caption-template>
        <div class="p-4 border border-gray-200 rounded-xl" data-caption-row data-removed="false">
            <input type="hidden" name="captions[__INDEX__][id]" value="" data-caption-id>
            <input type="hidden" name="captions[__INDEX__][remove]" value="0" data-caption-remove-value>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="caption_language___INDEX__" class="block mb-1 text-sm font-medium text-gray-700">Language code</label>
                    <input type="text" id="caption_language___INDEX__" name="captions[__INDEX__][language_code]" list="caption-language-codes" maxlength="35" placeholder="en" class="w-full px-3 py-2 border border-gray-200 rounded-lg">
                </div>
                <div>
                    <label for="caption_label___INDEX__" class="block mb-1 text-sm font-medium text-gray-700">Label</label>
                    <input type="text" id="caption_label___INDEX__" name="captions[__INDEX__][label]" maxlength="100" placeholder="English" class="w-full px-3 py-2 border border-gray-200 rounded-lg">
                </div>
            </div>
            <div class="mt-4">
                <label for="caption_file___INDEX__" class="block mb-1 text-sm font-medium text-gray-700">Caption file</label>
                <input type="file" id="caption_file___INDEX__" name="captions[__INDEX__][file]" accept=".vtt,text/vtt,text/plain" data-caption-file aria-describedby="caption_file_name___INDEX__ caption_file_error___INDEX__" class="w-full px-3 py-2 border border-gray-200 rounded-lg">
                <p id="caption_file_name___INDEX__" data-caption-file-name class="mt-1 text-xs text-gray-500"></p>
                <p id="caption_file_error___INDEX__" data-caption-file-error class="hidden mt-1 text-sm text-red-600" role="alert"></p>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" name="caption_default" value="__INDEX__">
                    Use as default
                </label>
                <button type="button" data-remove-caption class="text-sm font-medium text-red-600 hover:text-red-700">Remove track</button>
            </div>
        </div>
    </template>
</section>
