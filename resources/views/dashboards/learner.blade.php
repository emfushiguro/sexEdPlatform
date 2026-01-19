<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Welcome back, {{ $user->first_name }}!
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Enrolled Modules -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Enrolled Modules</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $totalEnrolled }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Age Group</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $learnerProfile->age_range }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Type -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 {{ $isPremium ? 'bg-yellow-500' : 'bg-gray-400' }} rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Account</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $isPremium ? 'Premium' : 'Free' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gamification Stats -->
            @if($gamification)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Level -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90">Level</p>
                                <p class="text-3xl font-bold">{{ $gamification->level }}</p>
                                @php
                                    $nextLevelScore = $gamification->level * 100;
                                    $progress = min(100, (($gamification->score % 100) / 100) * 100);
                                @endphp
                                <div class="mt-2 w-full bg-purple-400 rounded-full h-1.5">
                                    <div class="bg-white h-1.5 rounded-full" style="width: {{ $progress }}%"></div>
                                </div>
                                <p class="text-xs opacity-75 mt-1">{{ $gamification->score % 100 }}/100 to Level {{ $gamification->level + 1 }}</p>
                            </div>
                            <div class="text-4xl opacity-30">🎯</div>
                        </div>
                    </div>
                </div>

                <!-- Total Points -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90">Total Points</p>
                                <p class="text-3xl font-bold">{{ number_format($gamification->score) }}</p>
                                <p class="text-xs opacity-75 mt-1">Keep learning!</p>
                            </div>
                            <div class="text-4xl opacity-30">⭐</div>
                        </div>
                    </div>
                </div>

                <!-- Streak -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90">Current Streak</p>
                                <p class="text-3xl font-bold">{{ $gamification->streak_count }}</p>
                                <p class="text-xs opacity-75 mt-1">{{ $gamification->streak_count > 0 ? 'day(s)' : 'Start today!' }}</p>
                            </div>
                            <div class="text-4xl opacity-30">🔥</div>
                        </div>
                    </div>
                </div>

                <!-- Achievements -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-5 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90">Achievements</p>
                                <p class="text-3xl font-bold">{{ $user->certificates()->count() }}</p>
                                <p class="text-xs opacity-75 mt-1">Milestones earned</p>
                            </div>
                            <div class="text-4xl opacity-30">🏆</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Continue Learning -->
                    @if($enrolledModules->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Continue Learning</h3>
                                    <a href="{{ route('learner.modules.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                                        View All →
                                    </a>
                                </div>
                                
                                <div class="space-y-4">
                                    @foreach($enrolledModules as $enrollment)
                                        @php
                                            $module = $enrollment->module;
                                            $progress = $progressData[$module->id] ?? null;
                                            $progressPercent = $progress ? $progress->progress_percentage : 0;
                                        @endphp
                                        <div class="border rounded-lg p-4 hover:shadow-md transition cursor-pointer"
                                             onclick="window.location='{{ route('learner.modules.show', $module) }}'">
                                            <div class="flex justify-between items-start mb-2">
                                                <h4 class="font-medium text-gray-900">{{ $module->title }}</h4>
                                                <span class="text-xs px-2 py-1 rounded
                                                    {{ $module->difficulty_level === 'beginner' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $module->difficulty_level === 'intermediate' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $module->difficulty_level === 'advanced' ? 'bg-red-100 text-red-800' : '' }}">
                                                    {{ ucfirst($module->difficulty_level) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-3">{{ $module->lessons_count }} lessons · {{ $module->duration_minutes }} min</p>
                                            
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progressPercent }}%"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">{{ round($progressPercent) }}% complete</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Recommended Modules -->
                    @if($recommendedModules->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Recommended for You</h3>
                                    <a href="{{ route('learner.modules.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                                        Browse All →
                                    </a>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($recommendedModules->take(4) as $module)
                                        <div class="border rounded-lg p-4 hover:shadow-md transition cursor-pointer"
                                             onclick="window.location='{{ route('learner.modules.show', $module) }}'">
                                            <h4 class="font-medium text-gray-900 mb-1">{{ $module->title }}</h4>
                                            <p class="text-sm text-gray-600 mb-2 line-clamp-2">{{ Str::limit($module->description, 80) }}</p>
                                            <div class="flex justify-between items-center text-xs text-gray-500">
                                                <span>📚 {{ $module->lessons_count }} lessons</span>
                                                <span class="px-2 py-0.5 rounded
                                                    {{ $module->difficulty_level === 'beginner' ? 'bg-green-100 text-green-700' : '' }}
                                                    {{ $module->difficulty_level === 'intermediate' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                    {{ $module->difficulty_level === 'advanced' ? 'bg-red-100 text-red-700' : '' }}">
                                                    {{ ucfirst($module->difficulty_level) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Empty State -->
                    @if($enrolledModules->isEmpty() && $recommendedModules->isEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No modules yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by exploring available modules.</p>
                                <div class="mt-6">
                                    <a href="{{ route('learner.modules.index') }}" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Browse Modules
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Profile Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            @if($learnerProfile->avatar_path)
                                <img src="{{ Storage::url($learnerProfile->avatar_path) }}" alt="{{ $user->full_name }}" 
                                     class="w-20 h-20 rounded-full mx-auto mb-3 object-cover border-2 border-blue-200">
                            @else
                                <div class="w-20 h-20 bg-blue-500 rounded-full mx-auto mb-3 flex items-center justify-center border-2 border-blue-200">
                                    <span class="text-2xl font-bold text-white">
                                        {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                            <h3 class="font-semibold text-gray-900">{{ $user->full_name }}</h3>
                            <p class="text-sm text-gray-500">{{ $learnerProfile->username }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $learnerProfile->municipality }}</p>
                            
                            <a href="{{ route('profile.learner.edit') }}" 
                               class="mt-4 inline-block text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Edit Profile
                            </a>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                            <div class="space-y-2">
                                <a href="{{ route('learner.modules.index') }}" 
                                   class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Browse Modules</span>
                                </a>

                                @if($isPremium)
                                <a href="{{ route('learner.certificates.index') }}" 
                                   class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">My Certificates</span>
                                </a>
                                @endif

                                <a href="{{ route('profile.learner.edit') }}" 
                                   class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">My Profile</span>
                                </a>

                                @if(!$isPremium)
                                <a href="{{ route('subscription.upgrade') }}" 
                                   class="flex items-center gap-3 p-3 rounded-lg bg-gradient-to-r from-purple-50 to-blue-50 hover:from-purple-100 hover:to-blue-100 transition border border-purple-200">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                    </svg>
                                    <span class="text-sm font-semibold text-purple-700">Upgrade to Premium</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
