@extends('layouts.instructor-app')

@section('content')
            <!-- Module Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Thumbnail -->
                        <div>
                            @if($module->thumbnail)
                                <img src="{{ asset('storage/' . $module->thumbnail) }}" alt="{{ $module->title }}" class="w-full rounded-lg shadow">
                            @else
                                <div class="w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-400">No thumbnail</span>
                                </div>
                            @endif
                        </div>

                        <!-- Module Details -->
                        <div class="md:col-span-2">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $module->title }}</h3>
                            <p class="text-gray-600 mb-4">{{ $module->description }}</p>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-green-50 p-3 rounded">
                                    <div class="text-sm text-green-600">Duration</div>
                                    <div class="font-semibold text-green-900">{{ $module->duration_minutes }} min</div>
                                </div>
                                <div class="bg-purple-50 p-3 rounded">
                                    <div class="text-sm text-purple-600">Lessons</div>
                                    <div class="font-semibold text-purple-900">{{ $module->lessons->count() }}</div>
                                </div>
                                <div class="bg-orange-50 p-3 rounded">
                                    <div class="text-sm text-orange-600">Status</div>
                                    <div class="font-semibold text-orange-900">{{ $module->is_published ? 'Published' : 'Draft' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lessons List (Thinkific-style) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Lessons ({{ $module->lessons->count() }})</h3>
                        <a href="{{ route('instructor.lessons.create', ['module_id' => $module->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                            + Add Lesson
                        </a>
                    </div>

                    @if($module->lessons->count() > 0)
                        <div class="space-y-2">
                            @foreach($module->lessons->sortBy('order') as $lesson)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition" draggable="true">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4 flex-1">
                                            <!-- Drag Handle -->
                                            <div class="cursor-move text-gray-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                                                </svg>
                                            </div>
                                            
                                            <!-- Order Badge -->
                                            <div class="bg-gray-200 rounded-full w-8 h-8 flex items-center justify-center text-sm font-semibold text-gray-700">
                                                {{ $lesson->order }}
                                            </div>

                                            <!-- Lesson Info -->
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-900">{{ $lesson->title }}</div>
                                                @if($lesson->description)
                                                    <div class="text-sm text-gray-600">{{ Str::limit($lesson->description, 100) }}</div>
                                                @endif
                                                <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded">{{ strtoupper($lesson->type) }}</span>
                                                    @if($lesson->duration)
                                                        <span>â€¢ {{ $lesson->duration }} min</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex items-center gap-2">
                                            <!-- Move Up/Down -->
                                            @if(!$loop->first)
                                                <form action="{{ route('instructor.lessons.move', $lesson) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="direction" value="up">
                                                    <button type="submit" class="p-2 text-gray-600 hover:text-gray-900" title="Move Up">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            @if(!$loop->last)
                                                <form action="{{ route('instructor.lessons.move', $lesson) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="direction" value="down">
                                                    <button type="submit" class="p-2 text-gray-600 hover:text-gray-900" title="Move Down">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('instructor.lessons.show', $lesson) }}" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">View</a>
                                            <a href="{{ route('instructor.lessons.edit', $lesson) }}" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">Edit</a>
                                            <form action="{{ route('instructor.lessons.destroy', $lesson) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm" onclick="return confirm('Delete this lesson?')">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <p class="mt-4">No lessons yet. Start building your module!</p>
                            <a href="{{ route('instructor.lessons.create', ['module_id' => $module->id]) }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                                Create First Lesson
                            </a>
                        </div>
                    @endif
                </div>
            </div>
@endsection
