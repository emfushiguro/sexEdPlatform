@php
    $credits = ($topic ?? null)?->video_attribution ?? [];
    $creditSource = old('video_source', ($topic ?? null)?->video_provider === 'local' ? 'upload' : 'url');
    $creditLicense = old('video_attribution.license', $credits['license'] ?? 'unknown');
@endphp

<section class="my-6 border-t border-gray-200 pt-6" aria-labelledby="video-attribution-heading"
         x-data="{ source: @js($creditSource), license: @js($creditLicense) }"
         x-on:change.window="if ($event.target.name === 'video_source') source = $event.target.value">
    <h2 id="video-attribution-heading" class="text-base font-semibold text-gray-900">Video Source &amp; Attribution</h2>
    <p class="mt-2 text-sm text-gray-600">Credit the original creator and source. Credit alone does not establish permission to download, edit, redistribute, or re-upload a video.</p>
    <p class="mt-2 text-sm text-gray-600" x-show="source === 'url'">The video URL above is also the original source link. The provider is identified from that URL.</p>
    <p class="mt-2 text-sm text-gray-600" x-show="source === 'upload'">For platform-owned or instructor-created uploads, source and credit fields may be left blank. For third-party uploads, record the original source when known.</p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        @foreach(['original_title' => 'Original video title', 'creator' => 'Creator/channel/publisher'] as $field => $label)
            <div>
                <label for="video_attribution_{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }} <span class="font-normal">(optional)</span></label>
                <input type="text" id="video_attribution_{{ $field }}" name="video_attribution[{{ $field }}]"
                       value="{{ old('video_attribution.'.$field, $credits[$field] ?? '') }}" maxlength="255"
                       class="mt-1 w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                       aria-invalid="{{ $errors->has('video_attribution.'.$field) ? 'true' : 'false' }}"
                       aria-describedby="video_attribution_{{ $field }}_error">
                <p id="video_attribution_{{ $field }}_error" class="mt-1 text-sm text-red-700">{{ $errors->first('video_attribution.'.$field) }}</p>
            </div>
        @endforeach
        <div class="sm:col-span-2" x-show="source === 'upload'">
            <label for="video_attribution_source_url" class="block text-sm font-medium text-gray-700">Original source URL (optional)</label>
            <input type="url" id="video_attribution_source_url" name="video_attribution[source_url]" :disabled="source !== 'upload'"
                   value="{{ old('video_attribution.source_url', $credits['source_url'] ?? '') }}" maxlength="2048"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500"
                   aria-describedby="video_attribution_source_url_error">
            <p id="video_attribution_source_url_error" class="mt-1 text-sm text-red-700">{{ $errors->first('video_attribution.source_url') }}</p>
        </div>
        <div>
            <label for="video_attribution_license" class="block text-sm font-medium text-gray-700">License</label>
            <select id="video_attribution_license" name="video_attribution[license]" x-model="license"
                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500" aria-describedby="video_attribution_license_error">
                @foreach(\App\Services\VideoAttributionService::LICENSES as $value => $label)
                    <option value="{{ $value }}" @selected($creditLicense === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p id="video_attribution_license_error" class="mt-1 text-sm text-red-700">{{ $errors->first('video_attribution.license') }}</p>
        </div>
        <div x-show="license === 'other'">
            <label for="video_attribution_license_name" class="block text-sm font-medium text-gray-700">License name <span class="font-normal">(required for Other)</span></label>
            <input type="text" id="video_attribution_license_name" name="video_attribution[license_name]" :disabled="license !== 'other'"
                   value="{{ old('video_attribution.license_name', $credits['license_name'] ?? '') }}" maxlength="255"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500" aria-describedby="video_attribution_license_name_error">
            <p id="video_attribution_license_name_error" class="mt-1 text-sm text-red-700">{{ $errors->first('video_attribution.license_name') }}</p>
        </div>
        <div class="sm:col-span-2">
            <label for="video_attribution_text" class="block text-sm font-medium text-gray-700">Attribution statement (optional)</label>
            <textarea id="video_attribution_text" name="video_attribution[attribution_text]" rows="3" maxlength="5000"
                      class="mt-1 w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500" aria-describedby="video_attribution_text_error">{{ old('video_attribution.attribution_text', $credits['attribution_text'] ?? '') }}</textarea>
            <p id="video_attribution_text_error" class="mt-1 text-sm text-red-700">{{ $errors->first('video_attribution.attribution_text') }}</p>
        </div>
        <div class="sm:col-span-2">
            <label for="video_attribution_permission_status" class="block text-sm font-medium text-gray-700">Permission/reuse status (authoring only)</label>
            <select id="video_attribution_permission_status" name="video_attribution[permission_status]"
                    class="mt-1 w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500" aria-describedby="video_attribution_permission_help video_attribution_permission_error">
                @foreach(\App\Services\VideoAttributionService::PERMISSION_STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('video_attribution.permission_status', $credits['permission_status'] ?? 'unknown') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p id="video_attribution_permission_help" class="mt-1 text-sm text-gray-600">Record only what you know for the intended use. This status is not shown to learners and does not change publishing eligibility. A Standard YouTube License is not a Creative Commons reuse license.</p>
            <p id="video_attribution_permission_error" class="mt-1 text-sm text-red-700">{{ $errors->first('video_attribution.permission_status') }}</p>
        </div>
    </div>
    @error('video_attribution') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
</section>
