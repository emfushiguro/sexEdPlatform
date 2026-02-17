<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Modules', 'url' => route('instructor.modules.index')],
            ['label' => $lesson->module->title, 'url' => route('instructor.modules.show', $lesson->module)],
            ['label' => $lesson->title]
        ]" />
        
        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('instructor.modules.show', $lesson->module) }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $lesson->title }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('instructor.lessons.edit', $lesson) }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Edit Lesson
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Lesson Details Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Lesson Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Module</p>
                            <p class="font-medium text-gray-900">{{ $lesson->module->title }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Duration</p>
                            <p class="font-medium text-gray-900">
                                {{ $lesson->topics()->sum('duration') ?? 0 }} minutes
                                <span class="text-xs text-gray-500">(auto-calculated)</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Order</p>
                            <p class="font-medium text-gray-900">Lesson {{ $lesson->order }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="text-gray-900 mt-1">{{ $lesson->description ?? 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Topics Management Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Lesson Topics</h3>
                            <p class="text-sm text-gray-500 mt-1">Manage the content sections for this lesson</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('instructor.quizzes.create', ['lesson_id' => $lesson->id]) }}" 
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Create Quiz
                            </a>
                            <a href="{{ route('instructor.topics.create', ['lesson' => $lesson->id]) }}" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Topic
                            </a>
                        </div>
                    </div>

                    @if($lesson->topics->isEmpty())
                        <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-4 text-gray-500">No topics added yet</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Topic" to create content sections</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prerequisite</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($lesson->topics()->ordered()->get() as $topic)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $topic->order }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $topic->title }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $topic->type === 'video' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $topic->type === 'text' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $topic->type === 'worksheet' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $topic->type === 'quiz' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ $topic->type === 'interactive' ? 'bg-orange-100 text-orange-800' : '' }}">
                                                    {{ ucfirst($topic->type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $topic->duration }} min
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($topic->is_prerequisite)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Prerequisite
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Optional
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button onclick="previewTopicModal({{ $topic->id }})" 
                                                        class="text-purple-600 hover:text-purple-900 font-medium">
                                                        Preview
                                                    </button>
                                                    <a href="{{ route('instructor.topics.edit', $topic) }}" 
                                                        class="text-blue-600 hover:text-blue-900">Edit</a>
                                                    <form action="{{ route('instructor.topics.destroy', $topic) }}" method="POST" 
                                                        onsubmit="return confirm('Are you sure you want to delete this topic?');"
                                                        class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quiz Section -->
            @php
                $lessonQuiz = $lesson->quizzes()->where('is_active', true)->first();
            @endphp
            @if($lessonQuiz)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Lesson Quiz</h3>
                                <p class="text-sm text-gray-500 mt-1">Quiz linked to this lesson</p>
                            </div>
                            <a href="{{ route('instructor.quizzes.edit', $lessonQuiz) }}" 
                                class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                Edit Quiz
                            </a>
                        </div>
                        
                        <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-purple-900 mb-1">{{ $lessonQuiz->title }}</h4>
                                    <p class="text-sm text-purple-700 mb-3">{{ $lessonQuiz->description }}</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Questions:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $lessonQuiz->questions->count() }}</span>
                                        </div>
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Time Limit:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $lessonQuiz->time_limit ?? 'None' }} min</span>
                                        </div>
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Passing Score:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $lessonQuiz->passing_score }}%</span>
                                        </div>
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Status:</span>
                                            <span class="font-semibold {{ $lessonQuiz->is_active ? 'text-green-600' : 'text-red-600' }} ml-1">
                                                {{ $lessonQuiz->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Topic Preview Modal -->
    <div id="topicPreviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-900">Topic Preview</h3>
                <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="previewContent" class="mt-4">
                <div class="flex justify-center items-center py-12">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function previewTopicModal(topicId) {
            const modal = document.getElementById('topicPreviewModal');
            const previewContent = document.getElementById('previewContent');
            modal.classList.remove('hidden');
            
            // Show loading state
            previewContent.innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            `;
            
            // Fetch topic data
            fetch(`/instructor/topics/${topicId}/preview`)
                .then(response => response.json())
                .then(data => {
                    previewContent.innerHTML = renderTopicPreview(data);
                })
                .catch(error => {
                    previewContent.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Error loading preview</h3>
                            <p class="mt-1 text-sm text-gray-500">${error.message}</p>
                        </div>
                    `;
                });
        }

        function closePreviewModal() {
            document.getElementById('topicPreviewModal').classList.add('hidden');
        }

        function renderTopicPreview(topic) {
            let content = `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-xl font-semibold text-gray-900">${topic.title}</h4>
                        <div class="flex gap-4 mt-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full ${getTypeColor(topic.type)}">${capitalizeFirst(topic.type)}</span>
                            <span class="text-sm text-gray-600">Duration: ${topic.duration} min</span>
                            ${topic.is_prerequisite ? '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Prerequisite</span>' : ''}
                        </div>
                    </div>
            `;

            // Render based on type
            if (topic.type === 'video') {
                if (topic.video_url) {
                    content += `<div class="aspect-video bg-black rounded-lg overflow-hidden">
                        <iframe src="${topic.video_url}" class="w-full h-full" allowfullscreen></iframe>
                    </div>`;
                } else if (topic.video_file_path) {
                    content += `<video controls class="w-full rounded-lg">
                        <source src="/storage/${topic.video_file_path}" type="video/mp4">
                    </video>`;
                }
                if (topic.video_description) {
                    content += `<div class="prose max-w-none p-4 bg-gray-50 rounded-lg">${topic.video_description}</div>`;
                }
            } else if (topic.type === 'text') {
                content += `<div class="prose max-w-none p-4 bg-gray-50 rounded-lg">${topic.text_content || ''}</div>`;
                if (topic.image_attachments && topic.image_attachments.length > 0) {
                    content += `<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">`;
                    topic.image_attachments.forEach(img => {
                        content += `<img src="/storage/${img.path}" alt="${img.caption || ''}" class="rounded-lg w-full h-48 object-cover">`;
                    });
                    content += `</div>`;
                }
            } else if (topic.type === 'worksheet') {
                content += `<div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg">
                    <svg class="w-12 h-12 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">Worksheet File</p>
                        <a href="/storage/${topic.worksheet_file_path}" target="_blank" class="text-blue-600 hover:underline text-sm">Download/View</a>
                    </div>
                </div>`;
                if (topic.worksheet_instructions) {
                    content += `<div class="prose max-w-none p-4 bg-gray-50 rounded-lg">${topic.worksheet_instructions}</div>`;
                }
            } else if (topic.type === 'interactive') {
                content += `<div class="p-4 bg-orange-50 rounded-lg">
                    <p class="text-sm text-gray-700">Interactive activity type: ${topic.interactive_type || 'Not specified'}</p>
                </div>`;
            }

            content += `</div>`;
            return content;
        }

        function getTypeColor(type) {
            const colors = {
                'video': 'bg-red-100 text-red-800',
                'text': 'bg-blue-100 text-blue-800',
                'worksheet': 'bg-green-100 text-green-800',
                'quiz': 'bg-purple-100 text-purple-800',
                'interactive': 'bg-orange-100 text-orange-800'
            };
            return colors[type] || 'bg-gray-100 text-gray-800';
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Close modal when clicking outside
        document.getElementById('topicPreviewModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closePreviewModal();
            }
        });
    </script>
    @endpush
</x-app-layout>
