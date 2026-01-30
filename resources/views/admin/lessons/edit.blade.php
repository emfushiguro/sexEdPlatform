<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Lessons', 'url' => route('admin.lessons.index')],
            ['label' => 'Edit']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('admin.lessons.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Lesson</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.lessons.update', $lesson) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Module Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Module *</label>
                            <select name="module_id" required 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Module</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}" {{ old('module_id', $lesson->module_id) == $module->id ? 'selected' : '' }}>
                                        {{ $module->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('module_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Lesson Title -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Title *</label>
                            <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Lesson Type -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Type *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="text" 
                                        {{ old('content_type', $lesson->content_type) === 'text' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">📄</span>
                                        <span class="block text-sm font-medium text-gray-900">Text</span>
                                        <span class="text-xs text-gray-500">Reading content</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="video" 
                                        {{ old('content_type', $lesson->content_type) === 'video' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">🎥</span>
                                        <span class="block text-sm font-medium text-gray-900">Video</span>
                                        <span class="text-xs text-gray-500">YouTube/Vimeo</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="worksheet" 
                                        {{ old('content_type', $lesson->content_type) === 'worksheet' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">📋</span>
                                        <span class="block text-sm font-medium text-gray-900">Worksheet</span>
                                        <span class="text-xs text-gray-500">Downloadable</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="interactive" 
                                        {{ old('content_type', $lesson->content_type) === 'interactive' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">🎮</span>
                                        <span class="block text-sm font-medium text-gray-900">Interactive</span>
                                        <span class="text-xs text-gray-500">Quiz/Activity</span>
                                    </div>
                                </label>
                            </div>
                            @error('content_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Dynamic Content Fields -->
                        <div id="textContentField" class="mb-6 content-field">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Content *</label>
                            <textarea id="textContentEditor" name="text_content" rows="12"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('text_content', $lesson->text_content) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                💡 Tip: Use the formatting buttons above to style your text - just like Word or Google Docs!
                            </p>
                            @error('text_content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="videoContentField" class="mb-6 content-field hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Video URL *</label>
                            @if($lesson->video_provider && $lesson->video_id)
                                <div class="mb-2 p-3 bg-gray-50 rounded border">
                                    <p class="text-sm text-gray-600">Current: {{ ucfirst($lesson->video_provider) }} video</p>
                                    <a href="{{ $lesson->video_embed_url }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                        Preview current video
                                    </a>
                                </div>
                            @endif
                            <input type="url" name="video_url" 
                                value="{{ old('video_url', $lesson->video_provider ? $lesson->video_embed_url : '') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
                            <p class="mt-1 text-xs text-gray-500">
                                📺 Supports YouTube and Vimeo. Leave blank to keep current video.
                            </p>
                            @error('video_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="worksheetContentField" class="mb-6 content-field hidden">
                            @if($lesson->file_path)
                                <div class="mb-3 p-3 bg-gray-50 rounded border">
                                    <p class="text-sm text-gray-600">Current file:</p>
                                    <a href="{{ asset('storage/' . $lesson->file_path) }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                        📎 {{ basename($lesson->file_path) }}
                                    </a>
                                </div>
                            @endif
                            <label class="block text-sm font-medium text-gray-700 mb-2">Replace Worksheet File</label>
                            <input type="file" name="worksheet_file" accept=".pdf,.doc,.docx"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">
                                📎 PDF, DOC, or DOCX format. Max 10MB. Leave blank to keep current file.
                            </p>
                            @error('worksheet_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="interactiveContentField" class="mb-6 content-field hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Interactive Content</label>
                            <textarea name="text_content" rows="8"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('text_content', $lesson->text_content) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Instructions or embed code for interactive content</p>
                        </div>

                        <!-- Duration -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes) *</label>
                            <input type="number" name="duration" value="{{ old('duration', $lesson->duration) }}" required min="1" max="300"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Estimated time to complete this lesson</p>
                            @error('duration')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Order & Published -->
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                                <input type="number" name="order" value="{{ old('order', $lesson->order) }}" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div class="flex items-center pt-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_published" value="1" 
                                        {{ old('is_published', $lesson->is_published) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Published</span>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-4 pt-6 border-t">
                            <a href="{{ route('admin.modules.show', $lesson->module_id) }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                Cancel
                            </a>
                            <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition">
                                Update Lesson
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateFormFields() {
            const contentType = document.querySelector('input[name="content_type"]:checked')?.value;
            
            if (!contentType) return;
            
            // Hide all content fields
            document.querySelectorAll('.content-field').forEach(field => {
                field.classList.add('hidden');
            });

            // Show relevant field
            const fieldMap = {
                'text': 'textContentField',
                'video': 'videoContentField',
                'worksheet': 'worksheetContentField',
                'interactive': 'interactiveContentField'
            };
            
            const fieldId = fieldMap[contentType];
            if (fieldId) {
                document.getElementById(fieldId)?.classList.remove('hidden');
            }

            // Update radio button styling
            document.querySelectorAll('input[name="content_type"]').forEach(radio => {
                const label = radio.closest('label');
                if (radio.checked) {
                    label.classList.add('border-blue-500', 'ring-2', 'ring-blue-500');
                } else {
                    label.classList.remove('border-blue-500', 'ring-2', 'ring-blue-500');
                }
            });
        }

        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateFormFields);
        } else {
            updateFormFields();
        }
    </script>

    <!-- TinyMCE Rich Text Editor (Community/Open Source) -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#textContentEditor',
            height: 400,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic underline | ' +
                'alignleft aligncenter alignright alignjustify | ' +
                'bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }'
        });
    </script>
</x-app-layout>
