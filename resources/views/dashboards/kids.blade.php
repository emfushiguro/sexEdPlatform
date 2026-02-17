<x-app-layout>
    <!-- Gamification Top Bar -->
    @if($gamification)
    <x-gamification-bar :gamification="$gamification" :isPremium="$isPremium" />
    @endif
    <x-slot name="header">
        <div class="flex justify-between items-center bg-gradient-to-r from-yellow-400 to-pink-400 p-4 rounded-lg shadow-lg">
            <h2 class="font-bold text-2xl text-white drop-shadow-lg flex items-center">
                <span class="text-3xl mr-3"></span>
                Hi {{ $user->first_name }}! Let's Learn!
                <span class="text-3xl ml-3"></span>
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Profile & Gamification Row -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="text-center">
                        <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-4xl text-white">
                            {{ strtoupper(substr($user->first_name, 0, 1)) }}
                        </div>
                        <h3 class="font-bold text-xl text-gray-800">{{ $user->first_name }}</h3>
                        <p class="text-sm text-gray-600 mb-1">{{ $learnerProfile->username }}</p>
                        <p class="text-xs text-gray-500">Age: {{ $age }}</p>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('profile.learner.edit') }}" class="block w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm">
                                 Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Gamification Stats -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- My Lessons -->
                    <div class="bg-gradient-to-br from-blue-400 to-blue-500 rounded-2xl shadow-xl">
                        <div class="p-6 text-white">
                            <div class="text-5xl mb-2"></div>
                            <p class="text-xl font-bold">My Lessons</p>
                            <p class="text-4xl font-black mt-2">{{ $totalEnrolled }}</p>
                        </div>
                    </div>

                    <!-- My Level -->
                    @if($gamification)
                    <div class="bg-gradient-to-br from-purple-400 to-purple-500 rounded-2xl shadow-xl">
                        <div class="p-6 text-white">
                            <div class="text-5xl mb-2"></div>
                            <p class="text-xl font-bold">My Level</p>
                            <p class="text-4xl font-black mt-2">{{ $gamification->level }}</p>
                        </div>
                    </div>

                    <!-- My Stars -->
                    <div class="bg-gradient-to-br from-yellow-400 to-orange-400 rounded-2xl shadow-xl">
                        <div class="p-6 text-white">
                            <div class="text-5xl mb-2"></div>
                            <p class="text-xl font-bold">My Stars</p>
                            <p class="text-4xl font-black mt-2">{{ number_format($gamification->score) }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- My Learning Journey -->
            @if($enrolledModules->count() > 0)
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <span class="text-3xl mr-3"></span>
                    My Learning Journey
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($enrolledModules as $enrollment)
                        @php
                            $module = $enrollment->module;
                            $progress = $progressData[$module->id] ?? null;
                        @endphp
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-5 border-4 border-blue-200 hover:border-blue-400 transition">
                            <h4 class="font-bold text-lg text-gray-800 mb-2">{{ $module->title }}</h4>
                            
                            @if($progress)
                                <div class="mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-semibold text-gray-600">Progress</span>
                                        <span class="text-sm font-bold text-blue-600">{{ $progress->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-4">
                                        <div class="bg-gradient-to-r from-green-400 to-blue-500 h-4 rounded-full transition-all duration-500" 
                                             style="width: {{ $progress->progress_percentage }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1">
                                        {{ $progress->completed_lessons }} of {{ $progress->total_lessons }} lessons done! 
                                        @if($progress->progress_percentage == 100)
                                            🎉
                                        @endif
                                    </p>
                                </div>
                            @endif

                            <a href="{{ route('learner.modules.show', $module->id) }}" 
                               class="block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-center font-bold py-3 px-4 rounded-lg text-lg">
                                Continue Learning! 
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- New Adventures (Recommended Modules) -->
            @if($recommendedModules->count() > 0)
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                    <span class="text-3xl mr-3"></span>
                    New Adventures to Explore!
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($recommendedModules as $module)
                        <div class="bg-gradient-to-br from-pink-50 to-yellow-50 rounded-xl p-5 border-4 border-pink-200 hover:border-pink-400 transition">
                            <div class="flex items-start mb-3">
                                <span class="text-3xl mr-2"></span>
                                <h4 class="font-bold text-lg text-gray-800">{{ $module->title }}</h4>
                            </div>
                            
                            <p class="text-gray-700 mb-3 text-base">{{ Str::limit($module->description, 80) }}</p>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-semibold text-purple-600">
                                    📝 {{ $module->lessons_count }} lessons
                                </span>
                                @if($module->duration_minutes)
                                    <span class="text-sm font-semibold text-blue-600">
                                        ⏱️ {{ $module->duration_minutes }} min
                                    </span>
                                @endif
                            </div>

                            <form action="{{ route('learner.modules.enroll', $module->id) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-4 rounded-lg text-lg">
                                    Start Learning! 
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Empty State -->
            @if($enrolledModules->count() == 0 && $recommendedModules->count() == 0)
            <div class="bg-gradient-to-br from-blue-100 to-purple-100 rounded-2xl shadow-lg p-12 text-center">
                <div class="text-8xl mb-4"></div>
                <h3 class="text-3xl font-bold text-gray-800 mb-3">Ready to Start Learning?</h3>
                <p class="text-xl text-gray-700 mb-6">Ask your teacher or parent to add some fun lessons for you!</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
