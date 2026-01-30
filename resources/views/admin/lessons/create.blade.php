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
                                placeholder="e.g., Understanding Puberty Changes"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Lesson Type -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Type *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label class="lesson-type-card relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm hover:border-blue-500 transition-all">
                                    <input type="radio" name="content_type" value="text" 
                                        {{ old('content_type', 'text') === 'text' ? 'checked' : '' }}
                                        class="absolute opacity-0">
                                    <div class="flex flex-1 flex-col pointer-events-none">
                                        <span class="text-2xl mb-2">📄</span>
                                        <span class="block text-sm font-medium text-gray-900">Text</span>
                                        <span class="text-xs text-gray-500">Rich content</span>
                                    </div>
                                </label>

                                <label class="lesson-type-card relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm hover:border-blue-500 transition-all">
                                    <input type="radio" name="content_type" value="video" 
                                        {{ old('content_type') === 'video' ? 'checked' : '' }}
                                        class="absolute opacity-0">
                                    <div class="flex flex-1 flex-col pointer-events-none">
                                        <span class="text-2xl mb-2">🎥</span>
                                        <span class="block text-sm font-medium text-gray-900">Video</span>
                                        <span class="text-xs text-gray-500">Upload/Link</span>
                                    </div>
                                </label>

                                <label class="lesson-type-card relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm hover:border-blue-500 transition-all">
                                    <input type="radio" name="content_type" value="worksheet" 
                                        {{ old('content_type') === 'worksheet' ? 'checked' : '' }}
                                        class="absolute opacity-0">
                                    <div class="flex flex-1 flex-col pointer-events-none">
                                        <span class="text-2xl mb-2">📋</span>
                                        <span class="block text-sm font-medium text-gray-900">Worksheet</span>
                                        <span class="text-xs text-gray-500">PDF/Docs</span>
                                    </div>
                                </label>

                                <label class="lesson-type-card relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm hover:border-blue-500 transition-all">
                                    <input type="radio" name="content_type" value="interactive" 
                                        {{ old('content_type') === 'interactive' ? 'checked' : '' }}
                                        class="absolute opacity-0">
                                    <div class="flex flex-1 flex-col pointer-events-none">
                                        <span class="text-2xl mb-2">🎮</span>
                                        <span class="block text-sm font-medium text-gray-900">Interactive</span>
                                        <span class="text-xs text-gray-500">Activities</span>
                                    </div>
                                </label>
                            </div>
                            @error('content_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Description/Instructions (for all types) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Description/Instructions
                                <span class="text-xs text-gray-500 font-normal">(Optional - context for learners)</span>
                            </label>
                            <textarea name="description" rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Add instructions, context, or learning objectives...">{{ old('description') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">This will appear above the main content to guide learners</p>
                            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- TEXT LESSON CONTENT -->
                        <div id="textContent" class="content-section">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Content</label>
                                <textarea id="textEditor" name="text_content" rows="15"
                                    class="w-full rounded-md border-gray-300 shadow-sm">{{ old('text_content') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Use the editor to format text, add headings, lists, and more</p>
                                @error('text_content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Image Attachments (Optional)</label>
                                <input type="file" name="image_attachments[]" accept="image/*" multiple
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">Add diagrams, illustrations, or photos (JPEG, PNG, GIF - max 5MB each)</p>
                                @error('image_attachments.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- VIDEO LESSON CONTENT -->
                        <div id="videoContent" class="content-section hidden">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Video Source *</label>
                                <select name="video_source" id="video_source_select" onchange="toggleVideoSource()"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-4">
                                    <option value="url">YouTube/Vimeo URL</option>
                                    <option value="file">Upload Video File</option>
                                </select>

                                <div id="video_url_section">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Video URL</label>
                                    <input type="url" name="video_url" id="video_url" value="{{ old('video_url') }}"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
                                    <p class="mt-1 text-xs text-gray-500">📺 Paste a YouTube or Vimeo video link</p>
                                    @error('video_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                    
                                <div id="video_file_section" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Video File</label>
                                    <input type="file" name="video_file" id="video_file" accept="video/*"
                                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-xs text-gray-500">📹 MP4, AVI, MOV - max 100MB</p>
                                    @error('video_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <!-- WORKSHEET LESSON CONTENT -->
                        <div id="worksheetContent" class="content-section hidden">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Worksheet Instructions (Optional)</label>
                                <textarea id="worksheetEditor" name="text_content" rows="8"
                                    class="w-full rounded-md border-gray-300 shadow-sm">{{ old('text_content') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Add instructions or context for the worksheet</p>
                                @error('text_content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Worksheet File *</label>
                                <input type="file" name="worksheet_file" accept=".pdf,.doc,.docx"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">📎 PDF, DOC, or DOCX format - max 10MB</p>
                                @error('worksheet_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- INTERACTIVE LESSON CONTENT -->
                        <div id="interactiveContent" class="content-section hidden">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                                <select name="interactive_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select Activity Type</option>
                                    <option value="picture_comparison">Picture Comparison (Spot Differences)</option>
                                    <option value="body_parts">Body Parts Identification</option>
                                    <option value="drag_drop">Drag & Drop Matching</option>
                                    <option value="matching">Match Pairs</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">🎮 Choose an interactive activity for hands-on learning (Best for ages 5-12)</p>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Activity Content</label>
                                <textarea name="text_content" rows="8"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Add detailed instructions for the activity...">{{ old('text_content') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Explain how to complete the activity</p>
                            </div>

                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm text-yellow-800">
                                    <strong>📝 Note:</strong> Interactive activities are still in development. Currently, you can add instructions and we'll implement the interactive features in future updates.
                                </p>
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
                            <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition">
                                Create Lesson
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Initialize TinyMCE for text lessons
        tinymce.init({
            selector: '#textEditor',
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

        // Form submission handler - ensure TinyMCE content is saved
        document.getElementById('lessonForm').addEventListener('submit', function(e) {
            // Trigger save on all TinyMCE instances
            tinymce.triggerSave();
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
                }\n            }
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
    </script>
</x-app-layout>
