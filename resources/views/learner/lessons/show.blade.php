<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $lesson->title }}
            </h2>
            <a href="{{ route('learner.modules.show', $module) }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Module
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <!-- Lesson Content -->
                        <div class="p-6">
                            <h1 class="text-2xl font-bold mb-4">{{ $lesson->title }}</h1>
                            
                            <!-- Video Content -->
                            @if($lesson->content_type === 'video' && $lesson->video_embed_url)
                                <div class="aspect-video bg-black rounded-lg overflow-hidden mb-6">
                                    <iframe 
                                        src="{{ $lesson->video_embed_url }}" 
                                        class="w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen>
                                    </iframe>
                                </div>
                                @if($lesson->text_content)
                                    <div class="prose max-w-none">
                                        {!! nl2br(e($lesson->text_content)) !!}
                                    </div>
                                @endif
                            @endif

                            <!-- Text Content -->
                            @if($lesson->content_type === 'text')
                                @if($lesson->text_content)
                                    <div class="prose max-w-none mb-6">
                                        {!! nl2br(e($lesson->text_content)) !!}
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                        <p class="text-yellow-800">No text content available for this lesson yet.</p>
                                    </div>
                                @endif
                            @endif

                            <!-- Worksheet Download -->
                            @if($lesson->content_type === 'worksheet')
                                @if($lesson->file_path)
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="flex-shrink-0">
                                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-lg">Downloadable Worksheet</h3>
                                                <p class="text-gray-600">{{ basename($lesson->file_path) }}</p>
                                            </div>
                                            <a href="{{ asset('storage/' . $lesson->file_path) }}" 
                                               target="_blank"
                                               download
                                               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg">
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center mb-6">
                                        <p class="text-yellow-800">No worksheet file available for this lesson yet.</p>
                                    </div>
                                @endif
                                @if($lesson->text_content)
                                    <div class="prose max-w-none">
                                        <h3 class="text-lg font-semibold mb-3">Instructions:</h3>
                                        {!! nl2br(e($lesson->text_content)) !!}
                                    </div>
                                @endif
                            @endif

                            <!-- Interactive Content -->
                            @if($lesson->content_type === 'interactive')
                                @if($lesson->video_embed_url)
                                    <!-- Interactive Embed (H5P, Google Forms, etc.) -->
                                    <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden mb-6">
                                        <iframe 
                                            src="{{ $lesson->video_embed_url }}" 
                                            class="w-full h-full"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" 
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                @endif
                                @if($lesson->text_content)
                                    <div class="prose max-w-none">
                                        <h3 class="text-lg font-semibold mb-3">Activity Instructions:</h3>
                                        {!! nl2br(e($lesson->text_content)) !!}
                                    </div>
                                @endif
                                @if(!$lesson->video_embed_url && !$lesson->text_content)
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                        <p class="text-yellow-800">No interactive content available for this lesson yet.</p>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Lesson Quiz Section (Optional - can take anytime) -->
                        @if($lessonQuiz)
                            <div class="border-t p-6 bg-purple-50">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-shrink-0 w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center">
                                        <span class="text-white text-xl">📝</span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">Lesson Quiz: {{ $lessonQuiz->title }}</h3>
                                        @if($lessonQuiz->description)
                                            <p class="text-sm text-gray-600 mt-1">{{ $lessonQuiz->description }}</p>
                                        @endif
                                        <div class="flex gap-3 mt-2 text-xs text-gray-600">
                                            <span>{{ $lessonQuiz->questions->count() }} questions</span>
                                            <span>Passing: {{ $lessonQuiz->passing_score }}%</span>
                                            @if($lessonQuiz->time_limit)
                                                <span>{{ $lessonQuiz->time_limit }} min</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if($quizAttempt)
                                    <div class="mb-3 p-3 bg-white rounded border {{ $quizAttempt->passed ? 'border-green-300' : 'border-yellow-300' }}">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">Your Best Score:</span>
                                            <span class="font-bold {{ $quizAttempt->passed ? 'text-green-600' : 'text-yellow-600' }}">
                                                {{ $quizAttempt->score }}% {{ $quizAttempt->passed ? '✓' : '' }}
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('quizzes.start', $lessonQuiz) }}" 
                                   class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                                    {{ $quizAttempt ? 'Retake Quiz' : 'Take Quiz' }}
                                </a>
                                <p class="text-xs text-gray-600 text-center mt-2">
                                    Test your knowledge of this lesson (optional)
                                </p>
                            </div>
                        @endif

                        <!-- Lesson Navigation -->
                        <div class="border-t p-6">
                            <div class="flex items-center justify-between">
                                @if($previousLesson)
                                    <a href="{{ route('learner.lessons.show', $previousLesson) }}" 
                                       class="flex items-center gap-2 text-blue-600 hover:text-blue-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        <span>Previous Lesson</span>
                                    </a>
                                @else
                                    <div></div>
                                @endif

                                @if(!$isLessonCompleted)
                                    <form method="POST" action="{{ route('learner.lessons.complete', $lesson) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                                            ✓ Mark as Completed
                                        </button>
                                    </form>
                                @else
                                    <div class="bg-green-100 text-green-800 font-semibold py-2 px-6 rounded-lg flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Completed</span>
                                    </div>
                                @endif

                                @if($nextLesson)
                                    <a href="{{ route('learner.lessons.show', $nextLesson) }}" 
                                       class="flex items-center gap-2 text-blue-600 hover:text-blue-700 transition">
                                        <span>Next Lesson</span>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                @else
                                    <div></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Lesson List -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-6">
                        <div class="p-4 border-b">
                            <h3 class="font-semibold">{{ $module->title }}</h3>
                            <p class="text-sm text-gray-500">{{ $allLessons->count() }} lessons</p>
                        </div>
                        <div class="max-h-[calc(100vh-12rem)] overflow-y-auto">
                            @foreach($allLessons as $index => $l)
                                <a href="{{ route('learner.lessons.show', $l) }}" 
                                   class="block p-4 border-b hover:bg-gray-50 {{ $l->id === $lesson->id ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}">
                                    <div class="flex items-start gap-3">
                                        @if(in_array($l->id, $completedLessonIds))
                                            <div class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="flex-shrink-0 w-6 h-6 {{ $l->id === $lesson->id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }} rounded-full flex items-center justify-center text-xs font-semibold">
                                                {{ $index + 1 }}
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $l->title }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $l->duration }} min</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
