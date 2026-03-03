<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Explore Modules
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


            @if($modules->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No modules available</h3>
                    <p class="mt-1 text-sm text-gray-500">Check back later for new learning content.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($modules as $module)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                            <!-- Module Thumbnail -->
                            <div class="h-48 bg-gradient-to-br from-blue-400 to-blue-600 relative">
                                @if($module->thumbnail)
                                    <img src="{{ asset('storage/' . $module->thumbnail) }}" 
                                         alt="{{ $module->title }}" 
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <svg class="h-20 w-20 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                @endif
                                
                                <!-- Difficulty Badge -->
                                <div class="absolute top-3 right-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded 
                                        {{ $module->difficulty_level === 'beginner' ? 'bg-green-500' : '' }}
                                        {{ $module->difficulty_level === 'intermediate' ? 'bg-yellow-500' : '' }}
                                        {{ $module->difficulty_level === 'advanced' ? 'bg-red-500' : '' }} 
                                        text-white">
                                        {{ ucfirst($module->difficulty_level) }}
                                    </span>
                                </div>

                                <!-- Enrollment Badge -->
                                @if(in_array($module->id, $enrolledModuleIds))
                                    <div class="absolute top-3 left-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-600 text-white">
                                            ✓ Enrolled
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Module Info -->
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    {{ $module->title }}
                                </h3>
                                
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    {{ $module->description }}
                                </p>

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span> {{ $module->lessons_count }} lessons</span>
                                    <span> {{ $module->duration_minutes }} min</span>
                                </div>

                                <!-- Progress Bar (if enrolled) -->
                                @if(isset($progress[$module->id]))
                                    <div class="mb-4">
                                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span>{{ round($progress[$module->id]->progress_percentage) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" 
                                                 style="width: {{ $progress[$module->id]->progress_percentage }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('learner.modules.show', $module) }}" 
                                   class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                                    {{ in_array($module->id, $enrolledModuleIds) ? 'Continue Learning' : 'View Module' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
