<x-app-layout>
    <!-- Gamification Top Bar -->
    @if($gamification)
    <x-gamification-bar :gamification="$gamification" :isPremium="$isPremium" />
    @endif
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Welcome, {{ $user->first_name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Profile & Stats Row -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Profile Card -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center text-2xl text-white font-bold">
                            {{ strtoupper(substr($user->first_name, 0, 1)) }}
                        </div>
                        <h3 class="font-semibold text-base text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</h3>
                        <p class="text-xs text-gray-600">{{ $learnerProfile->username }}</p>
                        <div class="mt-2 inline-block px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded">
                            {{ $age }} years old
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('profile.learner.edit') }}" class="block w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-lg text-sm transition">
                                ✏️ Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Gamification Stats -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Enrolled Modules -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Enrolled Modules</p>
                                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $totalEnrolled }}</p>
                                </div>
                                <div class="flex-shrink-0 bg-blue-50 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Level -->
                    @if($gamification)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Current Level</p>
                                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $gamification->level }}</p>
                                    @php
                                        $progress = min(100, (($gamification->score % 100) / 100) * 100);
                                    @endphp
                                    <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1.5">{{ $gamification->score % 100 }}/100 XP</p>
                                </div>
                                <div class="flex-shrink-0 bg-purple-50 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Points -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Points</p>
                                    <p class="text-3xl font-semibold text-gray-900 mt-2">{{ number_format($gamification->score) }}</p>
                                </div>
                                <div class="flex-shrink-0 bg-yellow-50 rounded-lg p-3">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Active Modules -->
            @if($enrolledModules->count() > 0)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Active Learning Modules</h3>
                    <p class="text-sm text-gray-600 mt-1">Continue where you left off</p>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($enrolledModules as $enrollment)
                            @php
                                $module = $enrollment->module;
                                $progress = $progressData[$module->id] ?? null;
                            @endphp
                            <div class="border border-gray-200 rounded-lg p-5 hover:border-gray-300 hover:shadow-sm transition">
                                <div class="flex items-start justify-between mb-3">
                                    <h4 class="font-semibold text-base text-gray-900 flex-1">{{ $module->title }}</h4>
                                    @if($module->is_premium)
                                        <span class="ml-2 px-2 py-0.5 bg-yellow-50 text-yellow-700 text-xs font-medium rounded border border-yellow-200">Premium</span>
                                    @endif
                                </div>
                                
                                @if($progress)
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs text-gray-600">Progress</span>
                                            <span class="text-xs font-semibold text-gray-900">{{ $progress->progress_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full transition-all" 
                                                 style="width: {{ $progress->progress_percentage }}%"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            {{ $progress->completed_lessons }}/{{ $progress->total_lessons }} lessons completed
                                        </p>
                                    </div>
                                @endif

                                <a href="{{ route('learner.modules.show', $module->id) }}" 
                                   class="block w-full bg-gray-900 hover:bg-gray-800 text-white text-center font-medium py-2.5 px-4 rounded-lg transition text-sm">
                                    Continue Learning
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Recommended Modules -->
            @if($recommendedModules->count() > 0)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recommended for You</h3>
                    <p class="text-sm text-gray-600 mt-1">Explore age-appropriate sexual health education</p>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($recommendedModules as $module)
                            <div class="border border-gray-200 rounded-lg p-5 hover:border-gray-300 hover:shadow-sm transition">
                                <div class="flex items-start justify-between mb-3">
                                    <h4 class="font-semibold text-base text-gray-900 flex-1">{{ $module->title }}</h4>
                                    @if($module->is_premium)
                                        <span class="ml-2 px-2 py-0.5 bg-yellow-50 text-yellow-700 text-xs font-medium rounded border border-yellow-200">Premium</span>
                                    @endif
                                </div>
                                
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ $module->description }}</p>
                                
                                <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
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
                                            {{ $module->duration_minutes }} min
                                        </span>
                                    @endif
                                </div>

                                <form action="{{ route('learner.modules.enroll', $module->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition text-sm">
                                        Enroll Now
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Empty State -->
            @if($enrolledModules->count() == 0 && $recommendedModules->count() == 0)
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Modules Available</h3>
                <p class="text-gray-600">Check back later for new learning content.</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
