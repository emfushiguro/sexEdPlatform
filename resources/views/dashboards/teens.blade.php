<x-app-layout>
    <!-- Gamification Top Bar -->
    @if($gamification)
    <x-gamification-bar :gamification="$gamification" :isPremium="$isPremium" />
    @endif
    <x-slot name="header">
        <div class="flex justify-between items-center bg-gradient-to-r from-purple-600 to-indigo-600 p-4 rounded-lg shadow-lg">
            <h2 class="font-bold text-2xl text-white">
                Welcome back, {{ $user->first_name }} ✨
            </h2>
        </div>
    </x-slot>

    <div class="py-10 overflow-x-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Profile & Stats Row -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Profile Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="text-center">
                        <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-3xl text-white font-bold">
                            {{ strtoupper(substr($user->first_name, 0, 1)) }}
                        </div>
                        <h3 class="font-bold text-lg text-gray-800">{{ $user->first_name }} {{ $user->last_name }}</h3>
                        <p class="text-sm text-gray-600">{{ $learnerProfile->username }}</p>
                        <div class="mt-2 inline-block px-3 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                            {{ $age }} years old
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('profile.learner.edit') }}" class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg text-sm transition">
                                ✏️ Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Gamification Stats -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Enrolled Courses -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg">
                        <div class="p-6 text-white">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium opacity-90">My Courses</p>
                                    <p class="text-4xl font-bold mt-1">{{ $totalEnrolled }}</p>
                                    <p class="text-xs opacity-75 mt-2">Active enrollments</p>
                                </div>
                                <div class="text-4xl opacity-80">📚</div>
                            </div>
                        </div>
                    </div>

                    <!-- Level & Progress -->
                    @if($gamification)
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg">
                        <div class="p-6 text-white">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium opacity-90">Level</p>
                                    <p class="text-4xl font-bold mt-1">{{ $gamification->level }}</p>
                                    @php
                                        $progress = min(100, (($gamification->score % 100) / 100) * 100);
                                    @endphp
                                    <div class="mt-2 w-32 bg-purple-400 rounded-full h-2">
                                        <div class="bg-white h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <p class="text-xs opacity-75 mt-1">{{ $gamification->score % 100 }}/100 XP to next level</p>
                                </div>
                                <div class="text-4xl opacity-80">🎯</div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Points -->
                    <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg">
                        <div class="p-6 text-white">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium opacity-90">Total XP</p>
                                    <p class="text-4xl font-bold mt-1">{{ number_format($gamification->score) }}</p>
                                    <p class="text-xs opacity-75 mt-2">Experience points</p>
                                </div>
                                <div class="text-4xl opacity-80">⭐</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Continue Learning -->
            @if($enrolledModules->count() > 0)
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Continue Your Journey</h3>
                    <span class="text-sm text-gray-500">{{ $enrolledModules->count() }} active</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($enrolledModules as $enrollment)
                        @php
                            $module = $enrollment->module;
                            $progress = $progressData[$module->id] ?? null;
                        @endphp
                        <div class="min-w-0 bg-gray-50 rounded-lg p-5 border border-gray-200 hover:border-purple-400 hover:shadow-md transition">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="font-semibold text-lg text-gray-800 flex-1 min-w-0 break-words">{{ $module->title }}</h4>
                                @if($module->is_premium)
                                    <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">PRO</span>
                                @endif
                            </div>
                            
                            @if($progress)
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600">Progress</span>
                                        <span class="text-sm font-bold text-purple-600">{{ $progress->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-gradient-to-r from-purple-500 to-indigo-500 h-2.5 rounded-full transition-all" 
                                             style="width: {{ $progress->progress_percentage }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1.5">
                                        {{ $progress->completed_lessons }}/{{ $progress->total_lessons }} lessons completed
                                        @if($progress->progress_percentage == 100) ✓ @endif
                                    </p>
                                </div>
                            @endif

                            <a href="{{ route('learner.modules.show', $module->id) }}" 
                               class="block w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white text-center font-semibold py-2.5 px-4 rounded-lg transition">
                                Continue →
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Discover New Courses -->
            @if($recommendedModules->count() > 0)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">Discover New Topics</h3>
                    <span class="text-sm text-gray-500">Recommended for you</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($recommendedModules as $module)
                        <div class="min-w-0 bg-gradient-to-br from-gray-50 to-white rounded-lg p-5 border border-gray-200 hover:border-indigo-400 hover:shadow-md transition">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="font-semibold text-lg text-gray-800 flex-1 min-w-0 break-words">{{ $module->title }}</h4>
                                @if($module->is_premium)
                                    <span class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">PRO</span>
                                @endif
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ $module->description }}</p>
                            
                            <div class="flex items-center gap-4 mb-4 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    {{ $module->lessons_count }} lessons
                                </span>
                                @if($module->duration_minutes)
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $module->duration_minutes }}m
                                    </span>
                                @endif
                            </div>

                            <form action="{{ route('learner.modules.enroll', $module->id) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-2.5 px-4 rounded-lg transition">
                                    Start Course
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Empty State -->
            @if($enrolledModules->count() == 0 && $recommendedModules->count() == 0)
            <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl shadow-lg p-12 text-center">
                <div class="text-6xl mb-4">📖</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">No Courses Available Yet</h3>
                <p class="text-gray-600 mb-6">Check back soon for new learning content!</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
