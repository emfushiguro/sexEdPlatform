@php
    $version = $version ?? null;
    $isEdit = $guide->exists;
    $targetRoleLocked = $guide->current_version_id !== null;
@endphp

@include('instructor.topics.partials.upload-progress', [
    'savingLabel' => 'Saving guide...',
])

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    data-video-upload-form
    data-video-max-bytes="104857600"
    data-video-saving-label="Saving guide..."
    data-video-finalizing-label="Upload complete. Finalizing the guide..."
    data-video-failure-label="The guide could not be saved. Please try again."
    class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div>
        <label for="guide-title" class="text-sm font-semibold text-gray-700">Title</label>
        <input id="guide-title" name="title" value="{{ old('title', $version?->title) }}" maxlength="255" required class="mt-1 w-full rounded-xl border-gray-300">
        @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="guide-description" class="text-sm font-semibold text-gray-700">Description</label>
        <textarea id="guide-description" name="description" rows="5" maxlength="5000" required class="mt-1 w-full rounded-xl border-gray-300">{{ old('description', $version?->description) }}</textarea>
        @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="guide-target-role" class="text-sm font-semibold text-gray-700">Target role</label>
        <select id="guide-target-role" name="target_role" required @disabled($targetRoleLocked) class="mt-1 w-full rounded-xl border-gray-300 disabled:bg-gray-100 disabled:text-gray-500">
            @foreach(['learner' => 'Learner', 'instructor' => 'Instructor'] as $value => $label)
                <option value="{{ $value }}" @selected(old('target_role', $guide->target_role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if($targetRoleLocked)
            <p class="mt-1 text-xs text-gray-500">The target role is locked after the guide has been published.</p>
        @endif
        @error('target_role')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    @if($version)
        <div class="rounded-xl border border-purple-100 bg-purple-50/60 p-4">
            <p class="text-sm font-semibold text-gray-900">Current guide video</p>
            <video controls preload="metadata" class="mt-3 aspect-video w-full rounded-xl bg-black object-contain" src="{{ route('onboarding-guides.video', $version) }}" aria-label="Current onboarding guide video"></video>
            <p class="mt-2 text-xs text-gray-600">{{ $version->video_original_name }} · {{ $version->video_mime_type }} · {{ number_format($version->video_size / 1048576, 1) }} MB</p>
        </div>
    @endif

    <div>
        <label for="guide-video" class="text-sm font-semibold text-gray-700">{{ $isEdit ? 'Replace video (optional)' : 'Video' }}</label>
        <input id="guide-video" name="video" type="file" accept=".mp4,.mpeg,.mpg,.mov,.avi,.webm,video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm" data-video-file-input {{ $isEdit ? '' : 'required' }} class="mt-1 block w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:border-0 file:bg-purple-50 file:px-4 file:py-3 file:font-semibold file:text-purple-700 hover:file:bg-purple-100">
        <p data-video-file-name class="mt-2 text-xs text-gray-500">MP4, MPEG, MOV, AVI, or WebM up to 100 MB</p>
        <p data-video-error class="mt-1 hidden text-sm text-rose-600" role="alert"></p>
        @error('video')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    @include('instructor.topics.partials.video-file-preview', [
        'previewLabel' => 'Selected guide video preview',
        'previewStatusId' => 'onboardingGuideVideoPreviewStatus',
    ])

    <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
        <a href="{{ route('admin.onboarding-guides.index') }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">{{ $submitLabel }}</button>
    </div>
</form>
