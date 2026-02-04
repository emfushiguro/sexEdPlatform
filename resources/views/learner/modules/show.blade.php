<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $module->title }}
            </h2>
            <a href="{{ route('learner.modules.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to Modules
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

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Module Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <!-- Module Header -->
                        <div class="h-64 bg-gradient-to-br from-blue-400 to-blue-600 relative">
                            @if($module->thumbnail)
                                <img src="{{ asset('storage/' . $module->thumbnail) }}" 
                                     alt="{{ $module->title }}" 
                                     class="w-full h-full object-cover">
                            @endif
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-6">
                                <h1 class="text-3xl font-bold text-white mb-2">{{ $module->title }}</h1>
                                <div class="flex gap-4 text-sm text-white/90">
                                    <span>📚 {{ $lessons->count() }} lessons</span>
                                    <span>⏱️ {{ $module->duration_minutes }} minutes</span>
                                    <span class="px-2 py-0.5 rounded bg-white/20">
                                        {{ ucfirst($module->difficulty_level) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Module Description -->
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold mb-3">About this module</h3>
                            <p class="text-gray-700 whitespace-pre-line">{{ $module->description }}</p>
                        </div>

                        <!-- Lessons List -->
                        <div class="p-6">
                            <h3 class="text-lg font-semibold mb-4">Module Curriculum</h3>
                            
                            @if($lessons->isEmpty())
                                <p class="text-gray-500 text-center py-8">No lessons available yet.</p>
                            @else
                                <div class="space-y-2" x-data="{ expandedLesson: null }">
                                    @foreach($lessons as $index => $lesson)
                                        @php
                                            $isCompleted = in_array($lesson->id, $completedLessonIds);
                                            $topics = $lesson->topics()->ordered()->get();
                                            $topicsCount = $topics->count();
                                            $completedTopicsCount = 0;
                                            if ($topicsCount > 0) {
                                                $completedTopicsCount = \App\Models\LessonTopicProgress::where('user_id', auth()->id())
                                                    ->whereIn('lesson_topic_id', $topics->pluck('id'))
                                                    ->where('completed', true)
                                                    ->count();
                                            }
                                        @endphp
                                        <div class="border rounded-lg overflow-hidden">
                                            <!-- Lesson Header -->
                                            <div class="p-4 hover:bg-gray-50 transition {{ $isEnrolled ? 'cursor-pointer' : '' }}"
                                                 @if($topicsCount > 0 && $isEnrolled)
                                                     @click="expandedLesson = expandedLesson === {{ $lesson->id }} ? null : {{ $lesson->id }}"
                                                 @elseif($isEnrolled)
                                                     onclick="window.location='{{ route('learner.lessons.show', $lesson) }}'"
                                                 @endif>
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-4 flex-1">
                                                        @if($isCompleted)
                                                            <div class="flex-shrink-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white">
                                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                </svg>
                                                            </div>
                                                        @else
                                                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-sm">
                                                                {{ $index + 1 }}
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2">
                                                                @if($lesson->content_type === 'video')
                                                                    <span class="text-xl">🎥</span>
                                                                @elseif($lesson->content_type === 'text')
                                                                    <span class="text-xl">📄</span>
                                                                @elseif($lesson->content_type === 'worksheet')
                                                                    <span class="text-xl">📋</span>
                                                                @else
                                                                    <span class="text-xl">🎮</span>
                                                                @endif
                                                                <h4 class="font-medium text-gray-900">{{ $lesson->title }}</h4>
                                                            </div>
                                                            <div class="flex items-center gap-3 mt-1">
                                                                @if($topicsCount > 0)
                                                                    <span class="text-sm font-semibold {{ $completedTopicsCount === $topicsCount ? 'text-green-600' : 'text-blue-600' }}">
                                                                        {{ $completedTopicsCount }}/{{ $topicsCount }} complete
                                                                    </span>
                                                                @else
                                                                    <p class="text-sm text-gray-500">
                                                                        {{ $lesson->duration }} min · {{ ucfirst(str_replace('_', ' ', $lesson->content_type)) }}
                                                                    </p>
                                                                @endif
                                                                @if($isCompleted && $topicsCount === 0)
                                                                    <span class="text-xs text-green-600 font-semibold">✓ Completed</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2">
                                                        @if(!$isEnrolled)
                                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                            </svg>
                                                        @elseif($topicsCount > 0)
                                                            <svg class="w-5 h-5 text-gray-400 transition-transform" 
                                                                 :class="expandedLesson === {{ $lesson->id }} ? 'rotate-180' : ''"
                                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                        @else
                                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                            </svg>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Topics/Sections (Collapsible) -->
                                            @if($topicsCount > 0)
                                                <div x-show="expandedLesson === {{ $lesson->id }}"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 -translate-y-2"
                                                     x-transition:enter-end="opacity-100 translate-y-0"
                                                     class="bg-gray-50 border-t"
                                                     style="display: none;">
                                                    <div class="py-2">
                                                        @foreach($topics as $topic)
                                                            @php
                                                                $isTopicCompleted = \App\Models\LessonTopicProgress::where('user_id', auth()->id())
                                                                    ->where('lesson_topic_id', $topic->id)
                                                                    ->where('completed', true)
                                                                    ->exists();
                                                            @endphp
                                                            <a href="{{ route('learner.lessons.show', $lesson) }}#topic-{{ $topic->id }}" 
                                                               class="flex items-start gap-3 px-6 py-3 hover:bg-white transition group">
                                                                <!-- Completion Circle -->
                                                                <div class="flex-shrink-0 mt-1">
                                                                    @if($isTopicCompleted)
                                                                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                            </svg>
                                                                        </div>
                                                                    @else
                                                                        <div class="w-5 h-5 border-2 border-gray-300 rounded-full group-hover:border-blue-500"></div>
                                                                    @endif
                                                                </div>

                                                                <!-- Topic Content -->
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex items-start justify-between gap-2">
                                                                        <div class="flex-1">
                                                                            <h5 class="text-sm font-medium text-gray-900 group-hover:text-blue-600">
                                                                                {{ $topic->title }}
                                                                            </h5>
                                                                            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                                                                @if($topic->type === 'video')
                                                                                    <span class="flex items-center gap-1">
                                                                                        🎥 VIDEO
                                                                                    </span>
                                                                                @elseif($topic->type === 'text')
                                                                                    <span class="flex items-center gap-1">
                                                                                        📄 TEXT
                                                                                    </span>
                                                                                @elseif($topic->type === 'quiz')
                                                                                    <span class="flex items-center gap-1">
                                                                                        ❓ QUIZ
                                                                                    </span>
                                                                                @else
                                                                                    <span class="flex items-center gap-1">
                                                                                        🎮 INTERACTIVE
                                                                                    </span>
                                                                                @endif
                                                                                @if($topic->duration)
                                                                                    <span>· {{ $topic->duration }} MIN</span>
                                                                                @endif
                                                                                @if($topic->is_prerequisite)
                                                                                    <span class="text-red-600 font-semibold">· PREREQUISITE</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Module Quiz Section -->
                        @if($isEnrolled && $moduleQuizzes->isNotEmpty())
                            <div class="p-6 border-t">
                                <h3 class="text-lg font-semibold mb-4">📝 Module Assessment</h3>
                                @foreach($moduleQuizzes as $quiz)
                                    @php
                                        $attempts = $quizAttempts->get($quiz->id, collect());
                                        $bestAttempt = $attempts->sortByDesc('score')->first();
                                        $allCompleted = $progress->completed_lessons === $progress->total_lessons;
                                    @endphp
                                    
                                    <div class="border rounded-lg p-4 mb-3 {{ $allCompleted ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200' }}">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900">{{ $quiz->title }}</h4>
                                                @if($quiz->description)
                                                    <p class="text-sm text-gray-600 mt-1">{{ $quiz->description }}</p>
                                                @endif
                                                <div class="flex gap-3 mt-2 text-xs">
                                                    <span class="text-gray-600">{{ $quiz->questions->count() }} questions</span>
                                                    <span class="text-gray-600">Passing: {{ $quiz->passing_score }}%</span>
                                                    @if($quiz->time_limit)
                                                        <span class="text-gray-600">{{ $quiz->time_limit }} min</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if($bestAttempt)
                                            <div class="mt-3 p-3 bg-white rounded border {{ $bestAttempt->passed ? 'border-green-300' : 'border-yellow-300' }}">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium">Best Score:</span>
                                                    <span class="font-bold {{ $bestAttempt->passed ? 'text-green-600' : 'text-yellow-600' }}">
                                                        {{ $bestAttempt->score }}%
                                                    </span>
                                                </div>
                                            </div>
                                        @endif

                                        @if($allCompleted)
                                            <a href="{{ route('quizzes.start', $quiz) }}" 
                                               class="mt-3 block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                                                {{ $bestAttempt ? 'Retake Quiz' : 'Start Quiz' }}
                                            </a>
                                        @else
                                            <div class="mt-3 text-center text-sm text-gray-600 bg-gray-100 py-2 rounded">
                                                Complete all lessons first
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sticky top-6">
                        @if($isEnrolled)
                            <!-- Progress -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-2">Your Progress</h3>
                                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                    <div class="bg-blue-600 h-3 rounded-full" 
                                         style="width: {{ $progress->progress_percentage ?? 0 }}%"></div>
                                </div>
                                <p class="text-sm text-gray-600">
                                    {{ round($progress->progress_percentage ?? 0) }}% Complete
                                </p>
                            </div>

                            <a href="{{ route('learner.lessons.show', $lessons->first()) }}" 
                               class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg mb-3">
                                {{ ($progress->progress_percentage ?? 0) > 0 ? 'Continue Learning' : 'Start Learning' }}
                            </a>

                            <!-- Certificate Section (Premium) -->
                            @if(Auth::user()->isPremium())
                                @php
                                    $hasCertificate = Auth::user()->certificates()->where('module_id', $module->id)->exists();
                                    $allLessonsCompleted = $lessons->count() > 0 && count($completedLessonIds) === $lessons->count();
                                @endphp

                                @if($hasCertificate)
                                    <a href="{{ route('learner.certificates.index') }}" 
                                       class="block w-full text-center bg-gradient-to-r from-yellow-400 to-yellow-600 hover:from-yellow-500 hover:to-yellow-700 text-white font-semibold py-3 px-4 rounded-lg mb-3">
                                        🏆 View Certificate
                                    </a>
                                @elseif($allLessonsCompleted)
                                    <form method="POST" action="{{ route('learner.certificates.check', $module) }}" class="mb-3">
                                        @csrf
                                        <button type="submit" 
                                                class="w-full bg-gradient-to-r from-green-400 to-green-600 hover:from-green-500 hover:to-green-700 text-white font-semibold py-3 px-4 rounded-lg">
                                            🏆 Get Certificate
                                        </button>
                                    </form>
                                    @if($module->final_quiz_id)
                                        <p class="text-xs text-gray-600 text-center mb-3">
                                            * Requires passing final quiz ({{ $module->certificate_pass_score }}%+)
                                        </p>
                                    @endif
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-3">
                                        <p class="text-xs text-yellow-800 text-center">
                                            🏆 Complete all lessons to unlock certificate
                                        </p>
                                    </div>
                                @endif
                            @else
                                @if($lessons->count() > 0 && count($completedLessonIds) === $lessons->count())
                                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-200 rounded-lg p-4 mb-3">
                                        <p class="text-sm font-semibold text-purple-800 text-center mb-2">
                                            🏆 Upgrade to Premium
                                        </p>
                                        <p class="text-xs text-gray-700 text-center mb-3">
                                            Get a certificate for completing this module!
                                        </p>
                                        <a href="{{ route('subscription.upgrade') }}" 
                                           class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold py-2 px-4 rounded">
                                            Upgrade Now
                                        </a>
                                    </div>
                                @endif
                            @endif
                        @else
                            <!-- Enroll Button -->
                            <form method="POST" action="{{ route('learner.modules.enroll', $module) }}">
                                @csrf
                                <button type="submit" 
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg mb-4">
                                    Enroll Now - Free
                                </button>
                            </form>

                            <div class="text-sm text-gray-600 space-y-2">
                                <p class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Free access
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ $lessons->count() }} lessons
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Learn at your own pace
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
