@extends('layouts.instructor')
@section('title', $lesson->title)
@section('page-title', $lesson->title)
@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('instructor.modules.show', $lesson->module) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to {{ $lesson->module->title }}
    </a>
    <a href="{{ route('instructor.lessons.edit', $lesson) }}"
        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Lesson
    </a>
    <form action="{{ route('instructor.lessons.destroy', $lesson) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this lesson? This will remove all topics and quizzes belonging to it.');" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-error-200 text-error-700 bg-error-50 hover:bg-error-100 text-sm font-medium shadow-none transition-colors ml-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete
        </button>
    </form>
</div>

<div class="space-y-6">
            
            <!-- Lesson Details Card -->
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Lesson Details</h3>
                </div>
                <div class="p-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Module</p>
                            <p class="font-medium text-gray-900 dark:text-white mt-0.5">{{ $lesson->module->title }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Duration</p>
                            <p class="font-medium text-gray-900 dark:text-white mt-0.5">
                                {{ $lesson->topics()->sum('duration') ?? 0 }} minutes
                                <span class="text-xs text-gray-400">(auto-calculated)</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Order</p>
                            <p class="font-medium text-gray-900 dark:text-white mt-0.5">Lesson {{ $lesson->order }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Description</p>
                        <p class="text-gray-900 dark:text-gray-300 mt-1">{{ $lesson->description ?? 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Topics Management Card -->
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Lesson Topics</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manage the content sections for this lesson</p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="$store.modals.openQuizModal()"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Create Quiz
                            </button>
                            <a href="{{ route('instructor.topics.create', ['lesson' => $lesson->id]) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-success-500 hover:bg-success-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Topic
                            </a>
                        </div>
                    </div>
                </div>
                <div class="p-6">

                    @if($lesson->topics->isEmpty())
                        <div class="text-center py-12 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                            <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">No topics added yet</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click "Add Topic" to create content sections</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prerequisite</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($lesson->topics()->ordered()->get() as $topic)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $topic->order }}</td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $topic->title }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                                    {{ $topic->type === 'video' ? 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400' : '' }}
                                                    {{ $topic->type === 'text' ? 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400' : '' }}
                                                    {{ $topic->type === 'worksheet' ? 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400' : '' }}
                                                    {{ $topic->type === 'quiz' ? 'bg-purple-100 text-purple-800 dark:bg-purple-500/20 dark:text-purple-400' : '' }}
                                                    {{ $topic->type === 'interactive' ? 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-400' : '' }}">
                                                    {{ ucfirst($topic->type) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $topic->duration }} min</td>
                                            <td class="px-4 py-3">
                                                @if($topic->is_prerequisite)
                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-400">Prerequisite</span>
                                                @else
                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">Optional</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button onclick="previewTopicModal({{ $topic->id }})"
                                                        class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-900 font-medium cursor-pointer">Preview</button>
                                                    <a href="{{ route('instructor.topics.edit', $topic) }}"
                                                        class="text-sm text-brand-600 dark:text-brand-400 hover:text-brand-900">Edit</a>
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
                $lessonQuizzes = $lesson->quizzes()->with('questions')->latest()->get();
            @endphp
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Lesson Quizzes</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Quizzes linked to this lesson</p>
                    </div>
                    <button @click="$store.modals.openQuizModal()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Quiz
                    </button>
                </div>
                <div class="p-6">

                    @if($lessonQuizzes->isEmpty())
                        <div class="text-center py-8 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                            <svg class="mx-auto w-10 h-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No quizzes yet. Click <strong>Add Quiz</strong> to create one.</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($lessonQuizzes as $lq)
                                    <div class="flex items-center justify-between py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="shrink-0 w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $lq->title }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lq->questions->count() }} questions &bull; {{ $lq->passing_score }}% passing</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0 ml-4">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $lq->is_active ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                                {{ $lq->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <a href="{{ route('instructor.quizzes.show', $lq) }}" class="text-sm text-brand-600 dark:text-brand-400 hover:text-brand-800 font-medium">View</a>
                                            <a href="{{ route('instructor.quizzes.edit', $lq) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">Edit</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                </div>
            </div>

        </div><!-- end space-y-6 -->

<!-- Topic Preview Modal -->
<div id="topicPreviewModal" class="fixed inset-0 bg-gray-900/50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-gray-700 w-11/12 md:w-3/4 shadow-lg rounded-xl bg-white dark:bg-gray-900">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Topic Preview</h3>
            <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        <div id="previewContent" class="mt-4">
            <div class="flex justify-center items-center py-12">
                <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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

    <!-- Quiz Creation Modal -->
    @include('instructor.lessons.partials.quiz-modal')
@endsection
