<!-- Quiz Creation Modal for Index Page -->
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
            selectedLesson: '',
            allLessons: {{ json_encode($modules->flatMap(function($module) {
                return $module->lessons->map(function($lesson) use ($module) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'module_id' => $module->id,
                    ];
                });
            })) }},
            get filteredLessons() {
                if (!this.selectedModule) return [];
                return this.allLessons.filter(lesson => lesson.module_id == this.selectedModule);
            },
            onModuleChange() {
                this.selectedLesson = '';
            }
        }">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
                <h3 class="text-xl font-semibold text-gray-900">Create New Quiz</h3>
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

                <!-- Title -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text" 
                           name="title" 
                           value="{{ old('title') }}" 
                           required
                           placeholder="e.g., Module 1 Quiz: Growing Up"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>

                <!-- Quiz Attachment — cascading module → lesson -->
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h4 class="text-sm font-semibold text-blue-900 mb-1">📌 Quiz Attachment</h4>
                    <p class="text-xs text-blue-600 mb-4">Select a module, then optionally narrow down to a specific lesson within it.</p>

                    <!-- Step 1: Module -->
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            📘 Module
                            <span class="text-xs font-normal text-gray-500">(quiz appears after all lessons)</span>
                        </label>
                        <select name="module_id"
                                x-model="selectedModule"
                                @change="onModuleChange()"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">— Select a Module —</option>
                            @foreach($modules as $module)
                            <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Step 2: Lesson (only shown when a module is selected) -->
                    <div x-show="selectedModule !== ''" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            📗 Specific Lesson
                            <span class="text-xs font-normal text-gray-500">(optional — leave blank to attach to the whole module)</span>
                        </label>
                        <select name="lesson_id"
                                x-model="selectedLesson"
                                @change="if(selectedLesson) { $el.closest('form').querySelector('[name=module_id]').value = '' }"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">None — attach to module level</option>
                            <template x-for="lesson in filteredLessons" :key="lesson.id">
                                <option :value="lesson.id" x-text="lesson.title"></option>
                            </template>
                        </select>
                        <p class="mt-1.5 text-xs text-blue-700" x-show="selectedLesson !== ''" x-cloak>
                            ✅ Quiz will appear after completing all topics in the selected lesson.
                        </p>
                        <p class="mt-1.5 text-xs text-blue-700" x-show="selectedLesson === ''" x-cloak>
                            ✅ Quiz will appear after completing all lessons in the module.
                        </p>
                    </div>

                    @error('module_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Passing Score -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                    <input type="number" 
                           name="passing_score" 
                           value="{{ old('passing_score', 70) }}" 
                           required 
                           min="0" 
                           max="100"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('passing_score')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <button type="button" 
                            @click="$store.modals.closeQuizModal()"
                            class="px-5 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        + Create Quiz
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
