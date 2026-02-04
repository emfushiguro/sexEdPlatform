<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Lessons', 'url' => route('admin.lessons.index')],
            ['label' => 'Create']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('admin.lessons.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Lesson</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Display Validation Errors -->
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Whoops! There were some problems with your input.</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.lessons.store') }}" enctype="multipart/form-data" id="lessonForm">
                        @csrf

                        <!-- Module Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Module *</label>
                            <select name="module_id" required 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Module</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}" {{ old('module_id', request('module_id')) == $module->id ? 'selected' : '' }}>
                                        {{ $module->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('module_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Lesson Title -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Title *</label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                placeholder="e.g., Understanding Your Body: Reproductive Anatomy [25 min]"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">💡 Lessons are now containers. Add videos, texts, quizzes as topics below!</p>
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Lesson Description
                                <span class="text-xs text-gray-500 font-normal">(Optional)</span>
                            </label>
                            <textarea name="description" rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Brief overview of what this lesson covers...">{{ old('description') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Appears as lesson summary for learners</p>
                            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Hidden field for content_type (always 'text' for container lessons) -->
                        <input type="hidden" name="content_type" value="text">

                        <!-- Lesson Topics/Sections -->
                        <div class="mb-6 p-6 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Lesson Topics / Sections</h3>
                                    <p class="text-sm text-gray-600">Break down your lesson into organized topics that learners must complete sequentially</p>
                                </div>
                                <button type="button" onclick="addTopic()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add Topic
                                </button>
                            </div>

                            <div id="topicsList" class="space-y-3">
                                <!-- Topics will be added here dynamically -->
                                <p class="text-sm text-gray-500 italic text-center py-8" id="emptyTopicsMessage">No topics added yet. Click "Add Topic" to create sections for this lesson.</p>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                            <input type="number" name="duration" value="{{ old('duration', 10) }}" required min="1" max="300"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Estimated time to complete this lesson</p>
                            @error('duration')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Order & Published -->
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                                <input type="number" name="order" value="{{ old('order') }}" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Leave blank to add at end</p>
                            </div>

                            <div class="flex items-center pt-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_published" value="1" 
                                        {{ old('is_published', true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Publish immediately</span>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-4 pt-6 border-t">
                            <a href="{{ route('admin.lessons.index') }}" class="px-6 py-2 text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" id="submitBtn"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <span id="submitText">Create Lesson</span>
                                <span id="submitLoading" class="hidden flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span id="uploadStatus">Processing...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- TinyMCE Rich Text Editor -->
    <script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
    <script>
        // Initialize TinyMCE for text lessons
        tinymce.init({
            selector: '#textEditor',
            base_url: '{{ asset('build/tinymce') }}',
            suffix: '.min',
            license_key: 'gpl',
            promotion: false,
            height: 500,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline strikethrough | ' +
                'alignleft aligncenter alignright alignjustify | ' +
                'bullist numlist outdent indent | ' +
                'link image media | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });

        // Initialize TinyMCE for worksheet lessons
        tinymce.init({
            selector: '#worksheetEditor',
            base_url: '{{ asset('build/tinymce') }}',
            suffix: '.min',
            license_key: 'gpl',
            promotion: false,
            height: 300,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'charmap',
                'searchreplace', 'visualblocks', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline | ' +
                'alignleft aligncenter alignright | bullist numlist | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });

        // Form submission handler - ensure TinyMCE content is saved and clean disabled fields
        document.getElementById('lessonForm').addEventListener('submit', function(e) {
            // Trigger save on all TinyMCE instances
            tinymce.triggerSave();
            
            // Get the selected content type
            const contentType = document.querySelector('input[name="content_type"]:checked').value;
            
            // Check if video file upload to use AJAX
            const videoFile = document.getElementById('video_file');
            const isVideoUpload = contentType === 'video' && videoFile && videoFile.files && videoFile.files.length > 0;
            
            // Create a hidden field to hold the actual text_content value
            const existingHidden = document.querySelector('input[name="text_content"]');
            if (existingHidden) {
                existingHidden.remove();
            }
            
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'text_content';
            
            // Copy content from the appropriate textarea based on lesson type
            if (contentType === 'text') {
                hiddenInput.value = document.querySelector('[name="text_content_text"]').value || '';
            } else if (contentType === 'worksheet') {
                hiddenInput.value = document.querySelector('[name="text_content_worksheet"]').value || '';
            } else if (contentType === 'interactive') {
                hiddenInput.value = document.querySelector('[name="text_content_interactive"]').value || '';
            }
            
            this.appendChild(hiddenInput);
            
            // Collect image captions and add them to form
            const captionInputs = document.querySelectorAll('.caption-input');
            captionInputs.forEach((input, index) => {
                const captionField = document.createElement('input');
                captionField.type = 'hidden';
                captionField.name = 'image_captions[]';
                captionField.value = input.value || '';
                this.appendChild(captionField);
            });
            
            // Show loading indicator
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitLoading = document.getElementById('submitLoading');
            const uploadStatus = document.getElementById('uploadStatus');
            
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitLoading.classList.remove('hidden');
            
            // Remove disabled file inputs to prevent validation errors
            const videoUrl = document.getElementById('video_url');
            
            if (videoUrl && videoUrl.disabled) {
                videoUrl.removeAttribute('name');
            }
            if (videoFile && videoFile.disabled) {
                videoFile.removeAttribute('name');
            }
            
            // Use AJAX for video file uploads to show progress
            if (isVideoUpload) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();
                
                // Track upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        uploadStatus.textContent = 'Uploading... ' + Math.round(percentComplete) + '%';
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                uploadStatus.textContent = 'Success! Redirecting...';
                                window.location.href = response.redirect || '{{ route("admin.lessons.index") }}';
                            } else {
                                uploadStatus.textContent = 'Upload failed';
                                submitBtn.disabled = false;
                                submitText.classList.remove('hidden');
                                submitLoading.classList.add('hidden');
                                alert('Error: ' + (response.message || 'Unknown error'));
                            }
                        } catch (e) {
                            // Not JSON, probably redirected
                            uploadStatus.textContent = 'Success! Redirecting...';
                            window.location.href = '{{ route("admin.lessons.index") }}';
                        }
                    } else if (xhr.status === 302) {
                        window.location.href = '{{ route("admin.lessons.index") }}';
                    } else {
                        // Handle error
                        uploadStatus.textContent = 'Upload failed';
                        submitBtn.disabled = false;
                        submitText.classList.remove('hidden');
                        submitLoading.classList.add('hidden');
                        alert('Upload failed: ' + xhr.statusText);
                    }
                });
                
                xhr.addEventListener('error', function() {
                    uploadStatus.textContent = 'Upload error';
                    submitBtn.disabled = false;
                    submitText.classList.remove('hidden');
                    submitLoading.classList.add('hidden');
                    alert('An error occurred during upload');
                });
                
                xhr.open('POST', '{{ route("admin.lessons.store") }}');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            }
        });

        // Toggle video source inputs based on dropdown selection
        function toggleVideoSource() {
            const videoSourceSelect = document.getElementById('video_source_select');
            const videoUrlSection = document.getElementById('video_url_section');
            const videoFileSection = document.getElementById('video_file_section');
            const videoUrl = document.getElementById('video_url');
            const videoFile = document.getElementById('video_file');
            
            if (videoSourceSelect) {
                if (videoSourceSelect.value === 'url') {
                    videoUrlSection.classList.remove('hidden');
                    videoFileSection.classList.add('hidden');
                    videoUrl.disabled = false;
                    videoFile.disabled = true;
                    videoFile.value = ''; // Clear file input
                } else {
                    videoUrlSection.classList.add('hidden');
                    videoFileSection.classList.remove('hidden');
                    videoUrl.disabled = true;
                    videoUrl.value = ''; // Clear URL input
                    videoFile.disabled = false;
                }
            }
        }

        // Update content fields based on selected lesson type
        function updateContentFields() {
            const contentType = document.querySelector('input[name="content_type"]:checked');
            if (!contentType) return;
            
            const value = contentType.value;
            const sections = document.querySelectorAll('.content-section');
            
            // Hide all content sections
            sections.forEach(section => section.classList.add('hidden'));
            
            // Show relevant section
            const sectionMap = {
                'text': 'textContent',
                'video': 'videoContent',
                'worksheet': 'worksheetContent',
                'interactive': 'interactiveContent'
            };
            
            const targetSection = document.getElementById(sectionMap[value]);
            if (targetSection) {
                targetSection.classList.remove('hidden');
            }

            // Update border styling on selected card
            document.querySelectorAll('.lesson-type-card').forEach(card => {
                const input = card.querySelector('input[name="content_type"]');
                if (input && input.checked) {
                    card.classList.add('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-500');
                } else {
                    card.classList.remove('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-500');
                }
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add change event listeners to radio buttons
            document.querySelectorAll('input[name="content_type"]').forEach(radio => {
                radio.addEventListener('change', updateContentFields);
            });
            
            updateContentFields();
            toggleVideoSource();
        });

        // Toggle slideshow settings visibility
        function toggleSlideshowSettings() {
            const displayMode = document.getElementById('imageDisplayMode');
            const settingsDiv = document.getElementById('slideshowSettings');
            
            if (displayMode && displayMode.value === 'slideshow') {
                settingsDiv.classList.remove('hidden');
            } else {
                settingsDiv.classList.add('hidden');
            }
        }

        // Image management - Facebook-style incremental upload
        let selectedFiles = [];
        let fileCounter = 0;

        function addImages(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push({ id: fileCounter++, file: file });
                }
            });
            updateImagePreviews();
            document.getElementById('addMoreBtn').classList.remove('hidden');
        }

        function addMoreImages() {
            document.getElementById('imageUpload').click();
        }

        function removeImage(id) {
            selectedFiles = selectedFiles.filter(item => item.id !== id);
            updateImagePreviews();
            if (selectedFiles.length === 0) {
                document.getElementById('addMoreBtn').classList.add('hidden');
            }
        }

        function updateImagePreviews() {
            const container = document.getElementById('imagePreviewContainer');
            const previews = document.getElementById('imagePreviews');
            
            if (selectedFiles.length === 0) {
                container.classList.add('hidden');
                return;
            }
            
            previews.innerHTML = '';
            container.classList.remove('hidden');
            
            selectedFiles.forEach((item, index) => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewCard = document.createElement('div');
                    previewCard.className = 'border border-gray-200 rounded-lg p-3 bg-white relative';
                    previewCard.innerHTML = `
                        <button type="button" onclick="removeImage(${item.id})" 
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <div class="relative mb-2">
                            <img src="${e.target.result}" alt="Preview" 
                                class="w-full h-32 object-cover rounded">
                            <div class="absolute top-1 left-1 bg-blue-600 text-white text-xs px-2 py-1 rounded">
                                #${index + 1}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <p class="text-xs text-gray-600 truncate" title="${item.file.name}">
                                📁 ${item.file.name}
                            </p>
                            <input type="text" class="caption-input" data-index="${index}" 
                                placeholder="Caption (optional)"
                                class="w-full text-xs border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    `;
                    previews.appendChild(previewCard);
                };
                
                reader.readAsDataURL(item.file);
            });
            
            // Update the actual file input for form submission
            updateFileInput();
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(item => {
                dataTransfer.items.add(item.file);
            });
            document.getElementById('imageUpload').files = dataTransfer.files;
        }

        // ========================================
        // LESSON TOPICS MANAGEMENT
        // ========================================
        
        let topicCounter = 0;
        let topics = [];

        function addTopic() {
            topicCounter++;
            const topicId = topicCounter;
            
            const topic = {
                id: topicId,
                title: '',
                type: 'text',
                // Video fields
                video_provider: '',
                video_id: '',
                video_url: '',
                // Text content
                text_content: '',
                // Worksheet
                file_path: '',
                // Quiz
                quiz_id: '',
                // Interactive
                interactive_type: '',
                interactive_instructions: '',
                // Common
                duration: '',
                is_prerequisite: false,
                order: topics.length
            };
            
            topics.push(topic);
            renderTopics();
        }

        function removeTopic(topicId) {
            if (confirm('Are you sure you want to remove this topic?')) {
                topics = topics.filter(t => t.id !== topicId);
                // Reorder remaining topics
                topics.forEach((t, index) => {
                    t.order = index;
                });
                renderTopics();
            }
        }

        function updateTopicField(topicId, field, value) {
            const topic = topics.find(t => t.id === topicId);
            if (topic) {
                topic[field] = value;
            }
        }

        function moveTopicUp(topicId) {
            const index = topics.findIndex(t => t.id === topicId);
            if (index > 0) {
                [topics[index], topics[index - 1]] = [topics[index - 1], topics[index]];
                topics.forEach((t, i) => t.order = i);
                renderTopics();
            }
        }

        function moveTopicDown(topicId) {
            const index = topics.findIndex(t => t.id === topicId);
            if (index < topics.length - 1) {
                [topics[index], topics[index + 1]] = [topics[index + 1], topics[index]];
                topics.forEach((t, i) => t.order = i);
                renderTopics();
            }
        }

        function renderTopics() {
            const container = document.getElementById('topicsList');
            const emptyMessage = document.getElementById('emptyTopicsMessage');
            
            if (topics.length === 0) {
                emptyMessage.classList.remove('hidden');
                container.innerHTML = '';
                container.appendChild(emptyMessage);
                return;
            }
            
            emptyMessage.classList.add('hidden');
            container.innerHTML = '';
            
            topics.forEach((topic, index) => {
                const topicCard = document.createElement('div');
                topicCard.className = 'bg-white border-2 border-gray-200 rounded-lg p-4';
                topicCard.innerHTML = `
                    <div class="flex items-start gap-4">
                        <!-- Order Controls -->
                        <div class="flex flex-col gap-1 pt-1">
                            <button type="button" onclick="moveTopicUp(${topic.id})" 
                                ${index === 0 ? 'disabled' : ''}
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                </svg>
                            </button>
                            <button type="button" onclick="moveTopicDown(${topic.id})" 
                                ${index === topics.length - 1 ? 'disabled' : ''}
                                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Topic Number Badge -->
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold text-sm">
                            ${index + 1}
                        </div>

                        <!-- Topic Fields -->
                        <div class="flex-1 space-y-3">
                            <!-- Title & Type Row -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Topic Title *</label>
                                    <input type="text" 
                                        value="${topic.title}"
                                        onchange="updateTopicField(${topic.id}, 'title', this.value)"
                                        placeholder="e.g., Video: Anatomy of a person with a vulva"
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Type *</label>
                                    <select 
                                        onchange="updateTopicField(${topic.id}, 'type', this.value); renderTopics();"
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                        <option value="text" ${topic.type === 'text' ? 'selected' : ''}>📝 Text</option>
                                        <option value="video" ${topic.type === 'video' ? 'selected' : ''}>🎥 Video</option>
                                        <option value="worksheet" ${topic.type === 'worksheet' ? 'selected' : ''}>📋 Worksheet</option>
                                        <option value="quiz" ${topic.type === 'quiz' ? 'selected' : ''}>❓ Quiz</option>
                                        <option value="interactive" ${topic.type === 'interactive' ? 'selected' : ''}>🎮 Interactive</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Content Field (dynamic based on type) -->
                            <div>
                                ${topic.type === 'video' ? `
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Video URL (YouTube/Vimeo) *</label>
                                    <input type="url" 
                                        value="${topic.video_url || ''}"
                                        onchange="updateTopicField(${topic.id}, 'video_url', this.value)"
                                        placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..."
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">📺 Paste YouTube or Vimeo link (file upload support coming soon)</p>
                                ` : topic.type === 'text' ? `
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Text Content *</label>
                                    <textarea 
                                        onchange="updateTopicField(${topic.id}, 'text_content', this.value)"
                                        rows="4"
                                        placeholder="Add educational content, explanations, or instructions..."
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">${topic.text_content || ''}</textarea>
                                    <p class="mt-1 text-xs text-gray-500">📝 Rich text editor will be available in topic edit view</p>
                                ` : topic.type === 'worksheet' ? `
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Worksheet Instructions</label>
                                    <textarea 
                                        onchange="updateTopicField(${topic.id}, 'text_content', this.value)"
                                        rows="3"
                                        placeholder="Add instructions for using this worksheet..."
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500 mb-2">${topic.text_content || ''}</textarea>
                                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                        📎 <strong>Note:</strong> File upload will be available after lesson creation. You can edit the topic to attach PDF/DOC files.
                                    </div>
                                ` : topic.type === 'quiz' ? `
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Select Quiz *</label>
                                    <select 
                                        onchange="updateTopicField(${topic.id}, 'quiz_id', this.value)"
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Choose an existing quiz...</option>
                                        @foreach($quizzes ?? [] as $quiz)
                                            <option value="{{ $quiz->id }}" ${topic.quiz_id == '{{ $quiz->id }}' ? 'selected' : ''}>{{ $quiz->title }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">❓ Link an existing quiz or create one later</p>
                                ` : topic.type === 'interactive' ? `
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Activity Type *</label>
                                    <select 
                                        onchange="updateTopicField(${topic.id}, 'interactive_type', this.value)"
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500 mb-2">
                                        <option value="">Select activity type...</option>
                                        <option value="body_parts" ${topic.interactive_type === 'body_parts' ? 'selected' : ''}>Body Parts Identification</option>
                                        <option value="touch_scenarios" ${topic.interactive_type === 'touch_scenarios' ? 'selected' : ''}>Good/Bad Touch Scenarios</option>
                                        <option value="feelings_matching" ${topic.interactive_type === 'feelings_matching' ? 'selected' : ''}>Feelings Matching</option>
                                        <option value="hygiene_sequence" ${topic.interactive_type === 'hygiene_sequence' ? 'selected' : ''}>Hygiene Sequence</option>
                                        <option value="privacy_zones" ${topic.interactive_type === 'privacy_zones' ? 'selected' : ''}>Privacy Zones</option>
                                    </select>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Instructions *</label>
                                    <textarea 
                                        onchange="updateTopicField(${topic.id}, 'interactive_instructions', this.value)"
                                        rows="3"
                                        placeholder="Explain the activity objectives and how to complete it..."
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">${topic.interactive_instructions || ''}</textarea>
                                ` : ''}
                            </div>

                            <!-- Duration & Prerequisites Row -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Duration (minutes)</label>
                                    <input type="number" 
                                        value="${topic.duration}"
                                        onchange="updateTopicField(${topic.id}, 'duration', this.value)"
                                        min="1"
                                        placeholder="e.g., 3"
                                        class="w-full text-sm border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="flex items-end pb-2">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                            ${topic.is_prerequisite ? 'checked' : ''}
                                            onchange="updateTopicField(${topic.id}, 'is_prerequisite', this.checked)"
                                            class="rounded border-gray-300 text-blue-600">
                                        <span class="ml-2 text-xs font-medium text-gray-700">Mark as PREREQUISITE</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <button type="button" onclick="removeTopic(${topic.id})" 
                            class="flex-shrink-0 text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                `;
                container.appendChild(topicCard);
            });
        }

        // Add topics to form submission
        const originalSubmitHandler = document.getElementById('lessonForm').onsubmit;
        document.getElementById('lessonForm').addEventListener('submit', function(e) {
            // Add topics as JSON to form
            const topicsInput = document.createElement('input');
            topicsInput.type = 'hidden';
            topicsInput.name = 'topics_json';
            topicsInput.value = JSON.stringify(topics);
            this.appendChild(topicsInput);
        });

    </script>
</x-app-layout>
