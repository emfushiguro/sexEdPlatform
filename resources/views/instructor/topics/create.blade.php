@extends($contentPanelLayout ?? 'layouts.instructor-app')

@section('content')

    <!-- Display All Errors -->
    @if ($errors->any())
        <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
            <strong class="font-bold">Oops! There were some errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('instructor.topics.partials.upload-progress')

    <form action="{{ route($contentRoutePrefix . '.topics.store') }}" method="POST" enctype="multipart/form-data" id="topicForm" data-video-upload-form data-video-max-bytes="104857600">
        @csrf
        <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">

        <!-- Basic Information Card -->
        <div class="p-6 mb-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
            <h2 class="mb-6 text-xl font-semibold text-gray-900">Basic Information</h2>

            <!-- Topic Title -->
            <div class="mb-6">
                <label for="title" class="block mb-2 text-sm font-medium text-gray-700">
                    Topic Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 @error('title') border-red-500 @enderror"
                    required>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <fieldset data-topic-metadata @if(in_array(old('type'), ['interactive', 'interactive_checkpoint'], true)) hidden disabled @endif>
                <!-- Duration -->
                <div class="mb-6">
                    <label for="duration" class="block mb-2 text-sm font-medium text-gray-700">
                        Duration (minutes) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="duration" id="duration" value="{{ old('duration') }}" min="1"
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 @error('duration') border-red-500 @enderror"
                        required>
                    @error('duration')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Prerequisite Checkbox -->
                <div class="mb-6">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="checkbox" name="is_prerequisite" id="is_prerequisite" value="1"
                            {{ old('is_prerequisite', true) ? 'checked' : '' }}
                            class="w-5 h-5 mt-0.5 text-purple-700 border-2 border-gray-200 rounded focus:ring-2 focus:ring-purple-300 focus:ring-offset-0 cursor-pointer transition-all hover:border-purple-300">
                        <div class="flex-1">
                            <span class="text-sm font-semibold text-gray-900 transition-colors group-hover:text-purple-700">
                                Mark as Prerequisite Topic
                            </span>
                            <p class="mt-1 text-xs leading-relaxed text-gray-600">
                                If checked, learners must complete this topic before proceeding to the next prerequisite topic
                                in sequence
                            </p>
                        </div>
                    </label>
                </div>
            </fieldset>

            <!-- Topic Type Selection -->
            <div>
                <label class="block mb-4 text-sm font-medium text-gray-700">
                    Topic Type <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <!-- Video Type -->
                    <label
                        class="relative flex flex-col items-center p-6 transition-all border-2 border-gray-200 cursor-pointer rounded-xl hover:border-purple-400 hover:shadow-md topic-type-card">
                        <input type="radio" name="type" value="video" class="sr-only topic-type-radio"
                            {{ old('type') === 'video' ? 'checked' : '' }} required>
                        <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                            </path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Video</span>
                    </label>

                    <!-- Text Type -->
                    <label
                        class="relative flex flex-col items-center p-6 transition-all border-2 border-gray-200 cursor-pointer rounded-xl hover:border-purple-400 hover:shadow-md topic-type-card">
                        <input type="radio" name="type" value="text" class="sr-only topic-type-radio"
                            {{ old('type') === 'text' ? 'checked' : '' }} required>
                        <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Text</span>
                    </label>

                    <!-- Worksheet Type -->
                    <label
                        class="relative flex flex-col items-center p-6 transition-all border-2 border-gray-200 cursor-pointer rounded-xl hover:border-purple-400 hover:shadow-md topic-type-card">
                        <input type="radio" name="type" value="worksheet" class="sr-only topic-type-radio"
                            {{ old('type') === 'worksheet' ? 'checked' : '' }} required>
                        <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Worksheet</span>
                    </label>

                    <!-- Interactive Checkpoint Type -->
                    <label
                        class="relative flex flex-col items-center p-6 transition-all border-2 border-gray-200 cursor-pointer rounded-xl hover:border-purple-400 hover:shadow-md topic-type-card">
                        <input type="radio" name="type" value="interactive_checkpoint" class="sr-only topic-type-radio"
                            {{ old('type') === 'interactive_checkpoint' ? 'checked' : '' }} required>
                        <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <span class="text-sm font-semibold text-center text-gray-900">Interactive Checkpoint</span>
                    </label>

                    <!-- Interactive Activities Type -->
                    <label data-activity-category="interactive"
                        class="relative flex flex-col items-center p-6 transition-all border-2 border-gray-200 cursor-pointer rounded-xl hover:border-purple-400 focus-within:ring-2 focus-within:ring-purple-400 focus-within:ring-offset-2 hover:shadow-md topic-type-card">
                        <input type="radio" name="type" value="interactive" class="sr-only topic-type-radio"
                            {{ old('type') === 'interactive' ? 'checked' : '' }} required>
                        <svg class="w-12 h-12 mb-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 5h5m-5 5h8M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Interactive Activities</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Video Content -->
        <div id="videoContent" class="hidden p-6 bg-white border border-gray-100 shadow-sm rounded-2xl content-section">
            <h2 class="mb-6 text-xl font-semibold text-gray-900">Video Content</h2>

            <!-- Video Source Dropdown -->
            <div class="mb-6">
                <label for="video_source" class="block mb-2 text-sm font-medium text-gray-700">
                    Video Source <span class="text-red-500">*</span>
                </label>
                <select name="video_source" id="video_source"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400"
                    onchange="toggleVideoSource()">
                    <option value="">Select video source</option>
                    <option value="url" {{ old('video_source') === 'url' ? 'selected' : '' }}>YouTube/Vimeo URL
                    </option>
                    <option value="upload" {{ old('video_source') === 'upload' ? 'selected' : '' }}>Upload Video File
                    </option>
                </select>
            </div>

            <!-- YouTube/Vimeo URL -->
            <div id="videoUrlField" class="hidden mb-6">
                <label for="video_url" class="block mb-2 text-sm font-medium text-gray-700">
                    Video URL <span class="text-red-500">*</span>
                </label>
                <input type="text" name="video_url" id="video_url" value="{{ old('video_url') }}"
                    placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400 @error('video_url') border-red-500 @enderror">
                @error('video_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Supports YouTube and Vimeo URLs</p>
            </div>

            <!-- Upload Video File -->
            <div id="videoFileField" class="hidden mb-6">
                <label for="video_file" class="block mb-2 text-sm font-medium text-gray-700">
                    Upload Video <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="file" name="video_file" id="video_file"
                        accept=".mp4,.mpeg,.mpg,.mov,.avi,.webm,video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm"
                        class="sr-only" data-video-file-input
                        aria-describedby="videoFileName videoFileClientError{{ $errors->has('video_file') ? ' videoFileServerError' : '' }}"
                        aria-invalid="{{ $errors->has('video_file') ? 'true' : 'false' }}">
                    <label for="video_file"
                        class="flex items-center justify-center w-full px-6 py-4 transition border-2 border-gray-200 border-dashed cursor-pointer rounded-xl hover:border-purple-400 hover:bg-purple-50 focus-within:border-purple-400 focus-within:bg-purple-50 focus-within:ring-2 focus-within:ring-purple-300">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-purple-700">Click to
                                    upload video</span></p>
                            <p class="mt-1 text-xs text-gray-500" id="videoFileName" data-video-file-name>MP4, MPEG, MOV, AVI, or WebM up to 100 MB</p>
                        </div>
                    </label>
                </div>
                <p id="videoFileClientError" data-video-error class="hidden mt-1 text-sm text-red-600" role="alert"></p>
                @error('video_file')
                    <p id="videoFileServerError" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Video Description/Instructions -->
            <div class="mb-6">
                <label for="video_description" class="block mb-2 text-sm font-medium text-gray-700">
                    Video Description/Instructions
                </label>
                <textarea name="video_description" id="video_description" rows="4"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400"
                    placeholder="Add instructions or description for this video...">{{ old('video_description') }}</textarea>
                <p class="mt-1 text-sm text-gray-500">Optional: Provide context or instructions for learners</p>
            </div>
        </div>

        <!-- Text Content -->
        <div id="textContent" class="hidden p-6 bg-white border border-gray-100 shadow-sm rounded-2xl content-section">
            <h2 class="mb-6 text-xl font-semibold text-gray-900">Text Content</h2>

            <!-- Rich Text Editor -->
            <div class="mb-6">
                <label for="text_content" class="block mb-2 text-sm font-medium text-gray-700">
                    Content
                </label>
                <textarea name="text_content" id="text_content" rows="15"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400">{{ old('text_content') }}</textarea>
            </div>

            <!-- Image Attachments with Drag & Drop -->
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                    Image Attachments (Optional)
                </label>
                <div class="relative" id="imageDropZone">
                    <input type="file" name="image_attachments[]" id="image_attachments" accept="image/*" multiple
                        class="hidden" onchange="renderImagePreviewsFromInput()">
                    <label for="image_attachments"
                        class="flex items-center justify-center w-full px-6 py-8 transition border-2 border-gray-200 border-dashed cursor-pointer rounded-xl hover:border-purple-400 hover:bg-purple-50 drop-zone">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-purple-700">Click to
                                    upload</span> or drag and drop</p>
                            <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF up to 2MB each</p>
                        </div>
                    </label>
                </div>

                <!-- Image Previews Container -->
                <div id="imagePreviews" class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                </div>
            </div>
        </div>

        <!-- Worksheet Content -->
        <div id="worksheetContent"
            class="hidden p-6 bg-white border border-gray-100 shadow-sm rounded-2xl content-section">
            <h2 class="mb-6 text-xl font-semibold text-gray-900">Worksheet Content</h2>

            <!-- File Upload with Drag & Drop (Multiple Files) -->
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                    Worksheet Files <span class="text-red-500">*</span>
                </label>
                <div class="relative" id="worksheetDropZone">
                    <input type="file" name="worksheet_files[]" id="worksheet_files" accept=".pdf,.doc,.docx"
                        multiple class="hidden" onchange="handleWorksheetSelection(this.files, false)">
                    <label for="worksheet_files"
                        class="flex items-center justify-center w-full px-6 py-8 transition border-2 border-gray-200 border-dashed cursor-pointer rounded-xl hover:border-purple-400 hover:bg-purple-50 worksheet-drop-zone">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-purple-700">Click to
                                    upload</span> or drag and drop</p>
                            <p class="mt-1 text-xs text-gray-500">PDF, DOC, DOCX up to 10MB each (Multiple files supported)
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Worksheet Previews -->
                <div id="worksheetPreviews" class="mt-4 space-y-2"></div>
            </div>

            <!-- Instructions -->
            <div class="mb-6">
                <label for="worksheet_instructions" class="block mb-2 text-sm font-medium text-gray-700">
                    Instructions
                </label>
                <textarea name="worksheet_instructions" id="worksheet_instructions" rows="6"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-300 focus:border-purple-400"
                    placeholder="Provide instructions for completing this worksheet...">{{ old('worksheet_instructions') }}</textarea>
            </div>
        </div>

        <!-- Interactive Checkpoint Content -->
        <div id="interactive_checkpointContent" class="hidden p-6 bg-white border border-gray-100 shadow-sm rounded-2xl content-section">
            <h2 class="mb-6 text-xl font-semibold text-gray-900">Create Interactive Checkpoint</h2>
            @if($errors->any() && old('type') === 'interactive_checkpoint')
                <div class="px-5 py-4 mb-5 border border-red-200 rounded-2xl bg-red-50" role="alert">
                    <p class="text-sm font-semibold text-red-800">Please fix the checkpoint configuration.</p>
                    <ul class="mt-1 text-xs text-red-700 list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div x-data="{ placement: @js(old('checkpoint_placement', 'between_topics')) }" class="mb-6 space-y-4">
                <fieldset>
                    <legend class="mb-3 text-sm font-semibold text-gray-900">Checkpoint Placement</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="p-4 border border-gray-200 rounded-xl" :class="placement === 'inside_topic' && 'border-purple-300 bg-purple-50'">
                            <input type="radio" name="checkpoint_placement" value="inside_topic" x-model="placement" class="text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 font-semibold">Inside Topic</span>
                            <span class="block mt-1 text-sm text-gray-500">Place this checkpoint within a selected Topic's content.</span>
                        </label>
                        <label class="p-4 border border-gray-200 rounded-xl" :class="placement === 'between_topics' && 'border-purple-300 bg-purple-50'">
                            <input type="radio" name="checkpoint_placement" value="between_topics" x-model="placement" class="text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 font-semibold">Between Topics</span>
                            <span class="block mt-1 text-sm text-gray-500">Place this checkpoint as a separate step in the Lesson flow.</span>
                        </label>
                    </div>
                </fieldset>
                <div x-show="placement === 'inside_topic'">
                    <label for="parent_topic_id" class="block mb-2 text-sm font-medium text-gray-700">Containing Topic</label>
                    <select id="parent_topic_id" name="parent_topic_id" :disabled="placement !== 'inside_topic'" class="w-full border-gray-200 rounded-xl focus:border-purple-400 focus:ring-purple-300">
                        @foreach($lesson->topics->where('type', '!=', 'interactive_checkpoint') as $lessonTopic)
                            <option value="{{ $lessonTopic->id }}" @selected((int) old('parent_topic_id') === $lessonTopic->id)>{{ $lessonTopic->title }}</option>
                        @endforeach
                    </select>
                    @error('parent_topic_id') <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
            <fieldset id="checkpointQuestionFields" @disabled(old('type') !== 'interactive_checkpoint')>
                @include('instructor.quizzes.partials.question-fields', [
                    'selectedType' => old('question_type', 'multiple_choice'),
                    'allowTypeSwitch' => true,
                    'showPoints' => false,
                    'showExplanation' => true,
                    'editorUploadUrl' => route($contentRoutePrefix . '.upload.image'),
                ])
            </fieldset>
        </div>

        <!-- Interactive Activity Content -->
        <div id="interactiveContent" class="hidden p-6 bg-white border border-gray-100 shadow-sm rounded-2xl content-section">
            <h2 class="mb-6 text-xl font-semibold text-gray-900">Create Interactive Activity</h2>
            @if($errors->any() && old('type') === 'interactive')
                <div class="px-5 py-4 mb-5 border border-red-200 rounded-2xl bg-red-50" role="alert">
                    <p class="text-sm font-semibold text-red-800">Please fix the activity configuration.</p>
                    <ul class="mt-1 text-xs text-red-700 list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <fieldset id="interactiveActivitySection" @disabled(old('type') !== 'interactive')>
                <legend class="sr-only">Interactive activity configuration</legend>
                @include('instructor.topics.partials.interactive-activity-fields')
            </fieldset>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end">
            <div class="flex gap-4">
                <a href="{{ route($contentRoutePrefix . '.lessons.show', $lesson) }}"
                    class="px-6 py-2 text-white transition-colors bg-gray-500 rounded-xl hover:bg-gray-600">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" id="submitButton">
                    Create Topic
                </button>
            </div>
        </div>
    </form>

    </div>
    </div>

    <script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('topicForm');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            async function uploadTinyMceImage(file) {
                if (!file) {
                    throw new Error('No file selected.');
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    throw new Error('Only JPG, PNG, GIF, and WEBP images are allowed.');
                }

                const maxBytes = 5 * 1024 * 1024;
                if (file.size > maxBytes) {
                    throw new Error('Image is too large. Maximum allowed size is 5MB.');
                }

                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch('{{ route($contentRoutePrefix . '.upload.image') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || !result.location) {
                    throw new Error(result.error || 'Image upload failed.');
                }

                return result.location;
            }

            form.addEventListener('submit', function(e) {
                console.log('Form submitting...');
                console.log('Total images:', selectedImages.length, 'Excluded:', excludedImageIndices.size);

                // Sync TinyMCE content before submission
                if (tinymce.get('text_content')) {
                    tinymce.get('text_content').save();
                }

                // Add hidden inputs for excluded indices
                excludedImageIndices.forEach(index => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'excluded_image_indices[]';
                    hiddenInput.value = index;
                    form.appendChild(hiddenInput);
                });

            });

            // Initialize TinyMCE with image upload
            tinymce.init({
                selector: '#text_content',
                height: 400,
                menubar: false,
                license_key: 'gpl',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image media link | help',
                images_upload_url: '{{ route($contentRoutePrefix . '.upload.image') }}',
                automatic_uploads: true,
                paste_data_images: false,
                images_reuse_filename: true,
                images_file_types: 'jpeg,jpg,png,gif,webp',
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                document_base_url: '{{ url('/') }}/',
                images_upload_handler: async (blobInfo, progress) => {
                    progress(15);
                    const location = await uploadTinyMceImage(blobInfo.blob());
                    progress(100);
                    return location;
                },
                file_picker_types: 'image',
                file_picker_callback: async function(callback, value, meta) {
                    if (meta.filetype === 'image') {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', '.jpg,.jpeg,.png,.gif,.webp');
                        input.onchange = async function() {
                            const file = this.files[0];

                            if (!file) {
                                return;
                            }

                            try {
                                const location = await uploadTinyMceImage(file);
                                callback(location, {
                                    alt: file.name
                                });
                            } catch (error) {
                                tinymce.activeEditor?.notificationManager.open({
                                    text: error.message || 'Image upload failed.',
                                    type: 'error'
                                });
                            }
                        };
                        input.click();
                    }
                },
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px }'
            });

            // Get all elements
            const typeRadios = document.querySelectorAll('.topic-type-radio');
            const typeCards = document.querySelectorAll('.topic-type-card');
            const contentSections = document.querySelectorAll('.content-section');

            // Initialize - show selected type if exists
            const checkedRadio = document.querySelector('.topic-type-radio:checked');
            if (checkedRadio) {
                showContentSection(checkedRadio.value);
                highlightCard(checkedRadio);
            }

            // Add event listeners to radio buttons
            typeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        showContentSection(this.value);
                        highlightCard(this);
                    }
                });
            });

            // Add event listeners to cards for better UX
            typeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    showContentSection(radio.value);
                    highlightCard(radio);
                });
            });

            // Function to highlight selected card
            function highlightCard(radio) {
                typeCards.forEach(card => {
                    card.classList.remove('border-purple-400', 'bg-purple-50', 'shadow-md');
                    card.classList.add('border-gray-200');
                });

                const selectedCard = radio.closest('.topic-type-card');
                if (selectedCard) {
                    selectedCard.classList.remove('border-gray-200');
                    selectedCard.classList.add('border-purple-400', 'bg-purple-50', 'shadow-md');
                }
            }

            // Function to show content section based on type
            function showContentSection(type) {
                const checkpointQuestionFields = document.getElementById('checkpointQuestionFields');
                const interactiveActivitySection = document.getElementById('interactiveActivitySection');
                const topicMetadata = document.querySelector('[data-topic-metadata]');
                const showTopicMetadata = !['interactive_checkpoint', 'interactive'].includes(type);

                if (checkpointQuestionFields) {
                    checkpointQuestionFields.disabled = type !== 'interactive_checkpoint';
                }

                if (interactiveActivitySection) {
                    interactiveActivitySection.disabled = type !== 'interactive';
                }

                if (topicMetadata) {
                    topicMetadata.hidden = !showTopicMetadata;
                    topicMetadata.disabled = !showTopicMetadata;
                }

                contentSections.forEach(section => {
                    section.classList.add('hidden');
                });

                const targetSection = document.getElementById(type + 'Content');
                if (targetSection) {
                    targetSection.classList.remove('hidden');
                }
            }

            // Initialize video source visibility
            toggleVideoSource();
        });

        // Toggle video source fields
        function toggleVideoSource() {
            const videoSource = document.getElementById('video_source');
            const urlField = document.getElementById('videoUrlField');
            const fileField = document.getElementById('videoFileField');

            if (!videoSource || !urlField || !fileField) {
                return;
            }

            urlField.classList.add('hidden');
            fileField.classList.add('hidden');

            if (videoSource.value === 'url') {
                urlField.classList.remove('hidden');
            } else if (videoSource.value === 'upload') {
                fileField.classList.remove('hidden');
            }
        }

        // Image handling - track excluded indices instead of manipulating FileList
        let selectedImages = [];
        let excludedImageIndices = new Set(); // Track which images user wants to remove

        // Drag and Drop functionality
        function setupDragAndDrop() {
            const dropZone = document.getElementById('imageDropZone');
            const dropZoneLabel = dropZone.querySelector('.drop-zone');
            const fileInput = document.getElementById('image_attachments');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZoneLabel.classList.add('border-purple-400', 'bg-purple-100');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZoneLabel.classList.remove('border-purple-400', 'bg-purple-100');
                }, false);
            });

            dropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                fileInput.files = dt.files;
                excludedImageIndices.clear(); // Reset exclusions
                renderImagePreviewsFromInput();
            }, false);
        }

        // Render previews from file input
        function renderImagePreviewsFromInput() {
            const fileInput = document.getElementById('image_attachments');
            const files = fileInput.files;
            selectedImages = Array.from(files);

            console.log('Rendering previews, file count:', files.length, 'Excluded:', excludedImageIndices.size);

            const container = document.getElementById('imagePreviews');
            container.innerHTML = '';

            let displayedCount = 0; // Track how many images we've actually displayed

            Array.from(files).forEach((file, index) => {
                // Skip excluded images
                if (excludedImageIndices.has(index)) {
                    return;
                }

                displayedCount++; // Increment for each displayed image
                const currentDisplayCount = displayedCount; // Capture current value in closure
                const isPrimary = currentDisplayCount === 1; // First displayed image is primary

                const reader = new FileReader();

                reader.onload = function(e) {
                    const previewCard = document.createElement('div');
                    previewCard.className =
                        'relative border-2 border-gray-200 rounded-xl overflow-hidden transition-all hover:border-purple-400';
                    previewCard.dataset.index = index;

                    previewCard.innerHTML = `
                <div class="relative group">
                    <img src="${e.target.result}" alt="Preview ${currentDisplayCount}" class="object-cover w-full h-40">
                    <div class="absolute top-2 left-2 ${isPrimary ? 'bg-green-600' : 'bg-purple-600'} text-white text-xs font-bold px-2 py-1 rounded shadow-md z-10">
                        #${currentDisplayCount}${isPrimary ? ' (Primary)' : ''}
                    </div>
                    <button 
                        type="button"
                        onclick="markImageForRemoval(${index})"
                        class="absolute z-20 flex items-center justify-center w-8 h-8 text-white transition-transform bg-red-600 rounded-full shadow-lg top-2 right-2 hover:bg-red-700 hover:scale-110"
                        title="Remove image"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-2">
                    <input 
                        type="text" 
                        name="image_captions_${index}" 
                        data-caption-index="${index}"
                        placeholder="Caption for image ${currentDisplayCount}${isPrimary ? ' (will be shown as primary)' : ''}"
                        class="w-full px-2 py-1 text-sm border border-gray-200 rounded focus:ring-1 focus:ring-purple-300"
                        value=""
                    >
                    <p class="mt-1 text-xs text-gray-500 truncate" title="${file.name}">${file.name}</p>
                </div>
            `;

                    container.appendChild(previewCard);
                };

                reader.readAsDataURL(file);
            });

            updateAddMoreButton();
        }

        // Mark image for removal (don't manipulate FileList - just hide it)
        function markImageForRemoval(index) {
            excludedImageIndices.add(index);
            console.log('Marked image', index, 'for removal. Excluded count:', excludedImageIndices.size);
            renderImagePreviewsFromInput();
        }

        // Update "Add More Images" button visibility
        function updateAddMoreButton() {
            const fileInput = document.getElementById('image_attachments');
            const activeImageCount = fileInput.files.length - excludedImageIndices.size;
            let addMoreBtn = document.getElementById('addMoreImagesBtn');

            if (activeImageCount > 0) {
                if (!addMoreBtn) {
                    addMoreBtn = document.createElement('button');
                    addMoreBtn.id = 'addMoreImagesBtn';
                    addMoreBtn.type = 'button';
                    addMoreBtn.className =
                        'mt-4 w-full px-4 py-3 border-2 border-dashed border-purple-300 rounded-xl text-purple-700 hover:bg-purple-50 transition-colors font-medium flex items-center justify-center gap-2';
                    addMoreBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add More Images
            `;
                    addMoreBtn.onclick = function() {
                        alert(
                            'To add more images, please use the main "Click to upload" button above and select all images again.');
                    };
                    document.getElementById('imagePreviews').after(addMoreBtn);
                }
            } else {
                if (addMoreBtn) {
                    addMoreBtn.remove();
                }
            }
        }

        // Initialize drag and drop on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupDragAndDrop();
            setupWorksheetDragAndDrop();
        });

        // Worksheet handling
        let selectedWorksheets = [];

        function setupWorksheetDragAndDrop() {
            const dropZone = document.getElementById('worksheetDropZone');
            const dropZoneLabel = dropZone.querySelector('.worksheet-drop-zone');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaultsWorksheet, false);
            });

            function preventDefaultsWorksheet(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZoneLabel.classList.add('border-green-500', 'bg-green-100');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZoneLabel.classList.remove('border-green-500', 'bg-green-100');
                }, false);
            });

            dropZone.addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                handleWorksheetSelection(files, false);
            }, false);
        }

        function handleWorksheetSelection(files, append = false) {
            if (!append) {
                selectedWorksheets = [];
            }

            const allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (files && files.length > 0) {
                Array.from(files).forEach((file) => {
                    if (allowedTypes.includes(file.type)) {
                        selectedWorksheets.push(file);
                    } else {
                        alert(`File "${file.name}" is not a valid format (PDF, DOC, or DOCX)`);
                    }
                });

                updateWorksheetInput();
                renderWorksheetPreviews();
            }
        }

        function renderWorksheetPreviews() {
            const container = document.getElementById('worksheetPreviews');
            container.innerHTML = '';

            if (selectedWorksheets.length === 0) {
                document.getElementById('worksheetDropZone').querySelector('.worksheet-drop-zone').classList.remove(
                    'hidden');
                return;
            }

            document.getElementById('worksheetDropZone').querySelector('.worksheet-drop-zone').classList.add('hidden');

            selectedWorksheets.forEach((file, index) => {
                const previewCard = document.createElement('div');
                previewCard.className =
                    'flex items-center gap-4 p-4 bg-purple-50 border border-purple-200 rounded-xl';

                previewCard.innerHTML = `
            <div class="flex-shrink-0">
                <svg class="w-10 h-10 text-purple-700" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">${file.name}</p>
                <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
            </div>
            <button 
                type="button"
                onclick="removeWorksheet(${index})"
                class="flex-shrink-0 text-sm font-medium text-red-600 hover:text-red-800"
            >
                Remove
            </button>
        `;

                container.appendChild(previewCard);
            });

            updateAddMoreWorksheetButton();
        }

        function updateAddMoreWorksheetButton() {
            let addMoreBtn = document.getElementById('addMoreWorksheetsBtn');

            if (selectedWorksheets.length > 0) {
                if (!addMoreBtn) {
                    addMoreBtn = document.createElement('button');
                    addMoreBtn.id = 'addMoreWorksheetsBtn';
                    addMoreBtn.type = 'button';
                    addMoreBtn.className =
                        'mt-2 w-full px-4 py-3 border-2 border-dashed border-purple-300 rounded-xl text-purple-700 hover:bg-purple-50 transition-colors font-medium flex items-center justify-center gap-2';
                    addMoreBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add More Files
            `;
                    addMoreBtn.onclick = function() {
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = '.pdf,.doc,.docx';
                        input.multiple = true;
                        input.onchange = function() {
                            handleWorksheetSelection(this.files, true);
                        };
                        input.click();
                    };
                    document.getElementById('worksheetPreviews').after(addMoreBtn);
                }
            } else {
                if (addMoreBtn) {
                    addMoreBtn.remove();
                }
            }
        }

        function removeWorksheet(index) {
            selectedWorksheets.splice(index, 1);
            updateWorksheetInput();
            renderWorksheetPreviews();
        }

        function updateWorksheetInput() {
            const dataTransfer = new DataTransfer();
            selectedWorksheets.forEach(file => {
                dataTransfer.items.add(file);
            });
            document.getElementById('worksheet_files').files = dataTransfer.files;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    </script>
@endsection
