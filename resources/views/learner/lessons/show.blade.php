<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $lesson->title }}
            </h2>
            <a href="{{ route('learner.modules.show', $module) }}" class="text-sm text-blue-600 hover:text-blue-800">
                ← Back to Module
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- Sidebar - Lesson Content Navigation -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Lesson Content</h3>
                        <div class="space-y-2">
                            @foreach($lessonTopics as $index => $topic)
                                @php
                                    $isCompleted = in_array($topic->id, $completedTopicIds);
                                    $isLocked = in_array($topic->id, $lockedTopicIds);
                                    $isCurrent = $currentTopicIndex === $index && !request()->has('quiz');
                                @endphp
                                
                                @if($isLocked)
                                    {{-- Locked Topic --}}
                                    <div class="block p-3 rounded-lg bg-gray-100 opacity-60 cursor-not-allowed">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-500 truncate">{{ $topic->title }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <p class="text-xs text-gray-400">{{ $topic->duration }} min</p>
                                                    @if($topic->is_prerequisite)
                                                        <span class="px-1.5 py-0.5 bg-red-200 text-red-700 text-xs font-bold rounded">REQUIRED</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    {{-- Accessible Topic --}}
                                    <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $index]) }}" 
                                       class="block p-3 rounded-lg transition {{ $isCurrent ? 'bg-blue-500 text-white' : 'bg-gray-50 hover:bg-gray-100' }}">
                                        <div class="flex items-start gap-2">
                                            @if($isCompleted)
                                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $isCurrent ? 'text-white' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <div class="w-5 h-5 flex-shrink-0 mt-0.5 rounded-full border-2 {{ $isCurrent ? 'border-white' : 'border-gray-300' }}"></div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">{{ $topic->title }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <p class="text-xs {{ $isCurrent ? 'text-blue-100' : 'text-gray-500' }}">
                                                        {{ $topic->duration }} min
                                                    </p>
                                                    @if($topic->is_prerequisite)
                                                        <span class="px-1.5 py-0.5 {{ $isCurrent ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700' }} text-xs font-bold rounded">PREREQUISITE</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endif
                            @endforeach

                            @if($lessonQuiz && count($completedTopicIds) === $lessonTopics->count())
                                <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'quiz' => 1]) }}" 
                                   class="block p-3 rounded-lg transition {{ request()->has('quiz') ? 'bg-purple-500 text-white' : 'bg-purple-50 hover:bg-purple-100' }}">
                                    <div class="flex items-start gap-2">
                                        @if($quizAttempt)
                                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ request()->has('quiz') ? 'text-white' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <div class="w-5 h-5 flex-shrink-0 mt-0.5 rounded-full border-2 {{ request()->has('quiz') ? 'border-white' : 'border-purple-300' }}"></div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $lessonQuiz->title }}</p>
                                            <p class="text-xs {{ request()->has('quiz') ? 'text-purple-100' : 'text-purple-600' }} mt-0.5">
                                                {{ $lessonQuiz->questions->count() }} questions
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @endif
                        </div>
                        
                        <!-- Navigation Buttons -->
                        <div class="mt-6 space-y-2">
                            @if($previousLesson)
                                <a href="{{ route('learner.lessons.show', $previousLesson) }}" 
                                   class="flex items-center justify-center gap-2 w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Previous Lesson
                                </a>
                            @endif
                            <a href="{{ route('learner.modules.show', $module) }}" 
                               class="flex items-center justify-center gap-2 w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                Back to Module
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="lg:col-span-3">
                    @if(session('success'))
                        <div x-data="{ show: true }" 
                             x-show="show"
                             x-init="setTimeout(() => show = false, 3000)"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="opacity-100 transform translate-y-0"
                             x-transition:leave-end="opacity-0 transform -translate-y-2"
                             class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ session('success') }}
                            </div>
                            <button @click="show = false" class="text-green-700 hover:text-green-900 ml-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if(request()->has('quiz') && $lessonQuiz)
                        <!-- Quiz Page -->
                        @include('learner.lessons.partials.quiz-page')
                    @elseif($currentTopic)
                        <!-- Topic Page -->
                        @include('learner.lessons.partials.topic-page')
                    @else
                        <!-- No Content -->
                        <div class="bg-white rounded-lg shadow-md p-12 text-center">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No content available</h3>
                            <p class="text-gray-500">This lesson doesn't have any content yet.</p>
                        </div>
                    @endif

                    <!-- Lesson Description -->
                    @if($lesson->description)
                        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-900 mb-2">About this lesson:</h4>
                            <p class="text-blue-800">{!! nl2br(e($lesson->description)) !!}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
