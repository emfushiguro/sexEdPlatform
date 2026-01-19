<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Lesson</h2>
            <a href="{{ url()->previous() }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back
            </a>
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
                                placeholder="e.g., Understanding Anatomy: Body Parts"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <!-- Lesson Type -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Type *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="text" 
                                        {{ old('content_type', 'text') === 'text' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">📄</span>
                                        <span class="block text-sm font-medium text-gray-900">Text</span>
                                        <span class="text-xs text-gray-500">Reading content</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="video" 
                                        {{ old('content_type') === 'video' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">🎥</span>
                                        <span class="block text-sm font-medium text-gray-900">Video</span>
                                        <span class="text-xs text-gray-500">YouTube/Vimeo</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="worksheet" 
                                        {{ old('content_type') === 'worksheet' ? 'checked' : '' }}
                                        class="sr-only" onchange="updateFormFields()">
                                    <div class="flex flex-1 flex-col">
                                        <span class="text-2xl mb-2">📋</span>
                                        <span class="block text-sm font-medium text-gray-900">Worksheet</span>
                                        <span class="text-xs text-gray-500">Downloadable</span>
                                    </div>
                                </label>

                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500">
                                    <input type="radio" name="content_type" value="interactive" 
                                        {{ old('content_type') === 'interactive' ? 'checked' : '' }}
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
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('text_content') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                💡 Tip: Use the formatting buttons above to style your text - just like Word or Google Docs!
                            </p>
                            @error('text_content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="videoContentField" class="mb-6 content-field hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Video URL *</label>
                            <input type="url" name="video_url" value="{{ old('video_url') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
                            <p class="mt-1 text-xs text-gray-500">
                                📺 Supports YouTube and Vimeo. You can use unlisted videos for privacy.
                            </p>
                            @error('video_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="worksheetContentField" class="mb-6 content-field hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Worksheet File</label>
                            <input type="file" name="worksheet_file" accept=".pdf,.doc,.docx"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">
                                📎 PDF, DOC, or DOCX format. Max 10MB.
                            </p>
                            @error('worksheet_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="interactiveContentField" class="mb-6 content-field hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Interactive Content</label>
                            <textarea name="text_content" rows="8"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Instructions or embed code for interactive content...">{{ old('text_content') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Add quiz link or interactive activity instructions</p>
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
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Publish immediately</span>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-4 pt-6 border-t">
                            <a href="{{ url()->previous() }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
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
