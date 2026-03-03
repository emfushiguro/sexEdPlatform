<!-- Quiz Creation Modal -->
<!-- Modal Backdrop -->
<div x-show="$store.modals.quizModal" 
     x-cloak
     class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity"
     @click="$store.modals.closeQuizModal()"
     @keydown.escape.window="$store.modals.closeQuizModal()"></div>

<!-- Modal Dialog -->
<div x-show="$store.modals.quizModal" 
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
     @keydown.escape.window="$store.modals.closeQuizModal()">
    
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
         @click.stop
         x-data="{ 
            selectedModule: '',
            selectedLesson: '{{ $lesson->id }}',
            allLessons: {{ json_encode($modules->flatMap(function($module) {
                return $module->lessons->map(function($lesson) use ($module) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'module_id' => $module->id,
                        'module_title' => $module->title
                    ];
                });
            })) }},
            get filteredLessons() {
                if (!this.selectedModule) return this.allLessons;
                return this.allLessons.filter(lesson => lesson.module_id == this.selectedModule);
            }
        }">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
                <h3 class="text-xl font-semibold text-gray-900">Create Quiz for {{ $lesson->title }}</h3>
                <button @click="$store.modals.closeQuizModal()" 
                        class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form method="POST" action="{{ route('instructor.quizzes.store') }}" class="p-6">
                @csrf
                
                <!-- Hidden lesson_id -->
                <input type="hidden" name="lesson_id" :value="selectedLesson">

                <!-- Title -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text" 
                           name="title" 
                           value="{{ old('title') }}" 
                           required
                           placeholder="e.g., Lesson 1 Quiz: Understanding Topics"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" 
                              rows="3"
                              placeholder="Optional description for the quiz..."
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Passing Score -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                    <input type="number" 
                           name="passing_score" 
                           value="{{ old('passing_score', 75) }}" 
                           required 
                           min="0" 
                           max="100"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    @error('passing_score')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info Box -->
                <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-purple-800">
                            <p class="font-medium mb-1">This quiz will be attached to: {{ $lesson->title }}</p>
                            <p class="text-purple-700">Learners will see this quiz after completing all topics in this lesson.</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <button type="button" 
                            @click="$store.modals.closeQuizModal()"
                            class="px-5 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

<style>
    [x-cloak] { 
        display: none !important; 
    }
</style>
