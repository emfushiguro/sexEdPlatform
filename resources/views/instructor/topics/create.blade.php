<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Lessons', 'url' => route('instructor.lessons.index')],
            ['label' => $lesson->title, 'url' => route('instructor.lessons.show', $lesson)],
            ['label' => 'Add Topic']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('instructor.lessons.show', $lesson) }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Topic</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 flex flex-col items-center">
            <svg class="animate-spin h-12 w-12 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-lg font-semibold text-gray-700">Creating topic...</p>
            <p class="text-sm text-gray-500 mt-2">Please wait while we process your request</p>
        </div>
    </div>

    <form action="{{ route('instructor.topics.store') }}" method="POST" enctype="multipart/form-data" id="topicForm">
        @csrf
        <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">

        <!-- Basic Information Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Basic Information</h2>

            <!-- Topic Title -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Topic Title <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title') border-red-500 @enderror"
                    required
                >
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Duration -->
            <div class="mb-6">
                <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
                    Duration (minutes) <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number" 
                    name="duration" 
                    id="duration" 
                    value="{{ old('duration') }}"
                    min="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('duration') border-red-500 @enderror"
                    required
                >
                @error('duration')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Topic Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-4">
                    Topic Type <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Video Type -->
                    <label class="relative flex flex-col items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:shadow-md transition-all topic-type-card">
                        <input 
                            type="radio" 
                            name="type" 
                            value="video" 
                            class="sr-only topic-type-radio"
                            {{ old('type') === 'video' ? 'checked' : '' }}
                            required
                        >
                        <svg class="w-12 h-12 text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Video</span>
                    </label>

                    <!-- Text Type -->
                    <label class="relative flex flex-col items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:shadow-md transition-all topic-type-card">
                        <input 
                            type="radio" 
                            name="type" 
                            value="text" 
                            class="sr-only topic-type-radio"
                            {{ old('type') === 'text' ? 'checked' : '' }}
                            required
                        >
                        <svg class="w-12 h-12 text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Text</span>
                    </label>

                    <!-- Worksheet Type -->
                    <label class="relative flex flex-col items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:shadow-md transition-all topic-type-card">
                        <input 
                            type="radio" 
                            name="type" 
                            value="worksheet" 
                            class="sr-only topic-type-radio"
                            {{ old('type') === 'worksheet' ? 'checked' : '' }}
                            required
                        >
                        <svg class="w-12 h-12 text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Worksheet</span>
                    </label>

                    <!-- Interactive Type -->
                    <label class="relative flex flex-col items-center p-6 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:shadow-md transition-all topic-type-card">
                        <input 
                            type="radio" 
                            name="type" 
                            value="interactive" 
                            class="sr-only topic-type-radio"
                            {{ old('type') === 'interactive' ? 'checked' : '' }}
                            required
                        >
                        <svg class="w-12 h-12 text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">Interactive</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Video Content -->
        <div id="videoContent" class="bg-white rounded-lg shadow-md p-6 content-section hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Video Content</h2>

            <!-- Video Source Dropdown -->
            <div class="mb-6">
                <label for="video_source" class="block text-sm font-medium text-gray-700 mb-2">
                    Video Source <span class="text-red-500">*</span>
                </label>
                <select
                    name="video_source"
                    id="video_source"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    onchange="toggleVideoSource()"
                >
                    <option value="">Select video source</option>
                    <option value="url" {{ old('video_source') === 'url' ? 'selected' : '' }}>YouTube/Vimeo URL</option>
                    <option value="upload" {{ old('video_source') === 'upload' ? 'selected' : '' }}>Upload Video File</option>
                </select>
            </div>

            <!-- YouTube/Vimeo URL -->
            <div id="videoUrlField" class="mb-6 hidden">
                <label for="video_url" class="block text-sm font-medium text-gray-700 mb-2">
                    Video URL <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="video_url" 
                    id="video_url" 
                    value="{{ old('video_url') }}"
                    placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="mt-1 text-sm text-gray-500">Supports YouTube and Vimeo URLs</p>
            </div>

            <!-- Upload Video File -->
            <div id="videoFileField" class="mb-6 hidden">
                <label for="video_file" class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Video <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input 
                        type="file" 
                        name="video_file" 
                        id="video_file" 
                        accept="video/*"
                        class="hidden"
                        onchange="updateFileName(this, 'videoFileName')"
                    >
                    <label for="video_file" class="flex items-center justify-center w-full px-6 py-4 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-blue-600">Click to upload video</span></p>
                            <p class="text-xs text-gray-500 mt-1" id="videoFileName">MP4, WebM, MOV up to 100MB</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Video Description/Instructions -->
            <div class="mb-6">
                <label for="video_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Video Description/Instructions
                </label>
                <textarea
                    name="video_description"
                    id="video_description"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Add instructions or description for this video..."
                >{{ old('video_description') }}</textarea>
                <p class="mt-1 text-sm text-gray-500">Optional: Provide context or instructions for learners</p>
            </div>
        </div>

        <!-- Text Content -->
        <div id="textContent" class="bg-white rounded-lg shadow-md p-6 content-section hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Text Content</h2>

            <!-- Rich Text Editor -->
            <div class="mb-6">
                <label for="text_content" class="block text-sm font-medium text-gray-700 mb-2">
                    Content
                </label>
                <textarea 
                    name="text_content" 
                    id="text_content" 
                    rows="15"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >{{ old('text_content') }}</textarea>
            </div>

            <!-- Image Attachments with Preview -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Image Attachments (Optional)
                </label>
                <div class="relative">
                    <input 
                        type="file" 
                        name="image_attachments[]" 
                        id="image_attachments" 
                        accept="image/*"
                        multiple
                        class="hidden"
                        onchange="previewImages(this)"
                    >
                    <label for="image_attachments" class="flex items-center justify-center w-full px-6 py-4 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-blue-600">Click to upload</span> or drag and drop</p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 2MB (Max 5 images)</p>
                        </div>
                    </label>
                </div>
                
                <!-- Image Previews Container -->
                <div id="imagePreviews" class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
            </div>

            <!-- Image Display Mode Dropdown -->
            <div class="mb-6">
                <label for="image_display_mode" class="block text-sm font-medium text-gray-700 mb-2">
                    Image Display Mode
                </label>
                <select
                    name="image_display_mode"
                    id="image_display_mode"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="none" {{ old('image_display_mode') === 'none' || !old('image_display_mode') ? 'selected' : '' }}>None (Inline with text)</option>
                    <option value="gallery" {{ old('image_display_mode') === 'gallery' ? 'selected' : '' }}>Gallery</option>
                    <option value="slideshow" {{ old('image_display_mode') === 'slideshow' ? 'selected' : '' }}>Slideshow</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Choose how images will be displayed to learners</p>
            </div>
        </div>

        <!-- Worksheet Content -->
        <div id="worksheetContent" class="bg-white rounded-lg shadow-md p-6 content-section hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Worksheet Content</h2>

            <!-- File Upload -->
            <div class="mb-6">
                <label for="worksheet_file" class="block text-sm font-medium text-gray-700 mb-2">
                    Worksheet File <span class="text-red-500">*</span>
                </label>
                <input 
                    type="file" 
                    name="worksheet_file" 
                    id="worksheet_file" 
                    accept=".pdf,.doc,.docx"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="mt-1 text-sm text-gray-500">Supported formats: PDF, DOC, DOCX (Max: 10MB)</p>
            </div>

            <!-- Instructions -->
            <div class="mb-6">
                <label for="worksheet_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                    Instructions
                </label>
                <textarea 
                    name="worksheet_instructions" 
                    id="worksheet_instructions" 
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Provide instructions for completing this worksheet..."
                >{{ old('worksheet_instructions') }}</textarea>
            </div>
        </div>

        <!-- Interactive Content -->
        <div id="interactiveContent" class="bg-white rounded-lg shadow-md p-6 content-section hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Interactive Activity</h2>

            <!-- Activity Type -->
            <div class="mb-6">
                <label for="interactive_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Activity Type <span class="text-red-500">*</span>
                </label>
                <select 
                    name="interactive_type" 
                    id="interactive_type"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Select activity type</option>
                    <option value="activity" {{ old('interactive_type') === 'activity' ? 'selected' : '' }}>Activity</option>
                    <option value="simulation" {{ old('interactive_type') === 'simulation' ? 'selected' : '' }}>Simulation</option>
                    <option value="exercise" {{ old('interactive_type') === 'exercise' ? 'selected' : '' }}>Exercise</option>
                </select>
            </div>

            <!-- Instructions -->
            <div class="mb-6">
                <label for="interactive_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                    Instructions <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="interactive_instructions" 
                    id="interactive_instructions" 
                    rows="6"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Provide detailed instructions for this interactive activity..."
                >{{ old('interactive_instructions') }}</textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-4">
            <a 
                href="{{ route('instructor.lessons.show', $lesson) }}" 
                class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
            >
                Cancel
            </a>
            <button 
                type="submit" 
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                id="submitButton"
            >
                Create Topic
            </button>
        </div>
    </form>

        </div>
    </div>

<script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission loading indicator
    const form = document.getElementById('topicForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const submitButton = document.getElementById('submitButton');

    form.addEventListener('submit', function(e) {
        // Show loading overlay
        loadingOverlay.classList.remove('hidden');
        submitButton.disabled = true;
        submitButton.innerHTML = '<svg class=\"animate-spin -ml-1 mr-3 h-5 w-5 text-white inline\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg> Creating...';
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
        images_upload_url: '{{ route("instructor.upload.image") }}',
        automatic_uploads: true,
        images_reuse_filename: true,
        file_picker_types: 'image',
        file_picker_callback: function(callback, value, meta) {
            if (meta.filetype === 'image') {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function() {
                        callback(reader.result, {
                            alt: file.name
                        });
                    };
                    reader.readAsDataURL(file);
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
            card.classList.remove('border-blue-500', 'bg-blue-50', 'shadow-md');
            card.classList.add('border-gray-300');
        });
        
        const selectedCard = radio.closest('.topic-type-card');
        if (selectedCard) {
            selectedCard.classList.remove('border-gray-300');
            selectedCard.classList.add('border-blue-500', 'bg-blue-50', 'shadow-md');
        }
    }

    // Function to show content section based on type
    function showContentSection(type) {
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
    const videoSource = document.getElementById('video_source').value;
    const urlField = document.getElementById('videoUrlField');
    const fileField = document.getElementById('videoFileField');

    urlField.classList.add('hidden');
    fileField.classList.add('hidden');

    if (videoSource === 'url') {
        urlField.classList.remove('hidden');
    } else if (videoSource === 'upload') {
        fileField.classList.remove('hidden');
    }
}

// Update file name display
function updateFileName(input, elementId) {
    const fileNameElement = document.getElementById(elementId);
    if (input.files && input.files[0]) {
        fileNameElement.textContent = input.files[0].name;
        fileNameElement.classList.add('text-blue-600', 'font-medium');
    }
}

// Preview images with captions
function previewImages(input) {
    const container = document.getElementById('imagePreviews');
    container.innerHTML = '';

    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewCard = document.createElement('div');
                previewCard.className = 'relative border-2 border-gray-300 rounded-lg overflow-hidden';
                
                previewCard.innerHTML = `
                    <div class="relative">
                        <img src="${e.target.result}" alt="Preview ${index + 1}" class="w-full h-40 object-cover">
                        <div class="absolute top-2 left-2 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded">
                            #${index + 1}
                        </div>
                    </div>
                    <div class="p-2">
                        <input 
                            type="text" 
                            name="image_captions[]" 
                            placeholder="Caption for image ${index + 1}"
                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500"
                            value=""
                        >
                        <p class="text-xs text-gray-500 mt-1 truncate">${file.name}</p>
                    </div>
                `;
                
                container.appendChild(previewCard);
            };
            
            reader.readAsDataURL(file);
        });
    }
}
</script>
</x-app-layout>
