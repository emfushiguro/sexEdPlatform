{{-- Lesson Creation Slide-Over Panel --}}
{{-- Triggered via: $store.modals.openLessonSlideout(moduleId) --}}

@php
    $slideoutModules = \App\Models\Module::where('created_by', auth()->id())->get();
@endphp

{{-- Backdrop --}}
<div x-show="$store.modals.lessonSlideout"
     x-cloak
     x-transition:enter="transition-opacity ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40"
     @click="$store.modals.closeLessonSlideout()"></div>

{{-- Slide-Over Panel --}}
<div x-show="$store.modals.lessonSlideout"
     x-cloak
     x-transition:enter="transition transform ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition transform ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     class="fixed top-0 right-0 h-full w-full max-w-lg z-50 flex flex-col"
     x-data="{
         mode: 'create',
         editLessonId: null,
         selectedModuleId: null,
         title: '',
         description: '',
         isPublished: true,
         actionBase: '{{ url('instructor/lessons') }}',
         syncFromStore() {
             const draft = $store.modals.lessonSlideoutDraft;
             if (draft) {
                 this.mode = 'edit';
                 this.editLessonId = draft.id;
                 this.selectedModuleId = draft.module_id;
                 this.title = draft.title || '';
                 this.description = draft.description || '';
                 this.isPublished = !!draft.is_published;
                 return;
             }

             this.mode = 'create';
             this.editLessonId = null;
             this.selectedModuleId = $store.modals.lessonSlideoutModuleId;
             this.title = '';
             this.description = '';
             this.isPublished = true;
         },
         get isEdit() {
             return this.mode === 'edit' && this.editLessonId !== null;
         },
         get formAction() {
             return this.isEdit ? `${this.actionBase}/${this.editLessonId}` : '{{ route('instructor.lessons.store') }}';
         },
         init() {
             this.syncFromStore();
             this.$watch('$store.modals.lessonSlideoutModuleId', val => { this.selectedModuleId = val; });
             this.$watch('$store.modals.lessonSlideoutDraft', () => { this.syncFromStore(); });
             this.$watch('$store.modals.lessonSlideout', open => {
                 if (open) this.syncFromStore();
             });
         }
     }"
     @keydown.escape.window="$store.modals.closeLessonSlideout()"
     @click.stop>

    <div class="h-full bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-100 dark:border-gray-700 flex flex-col">

        {{-- Panel Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="isEdit ? 'Edit Lesson' : 'New Lesson'"></h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500" x-text="isEdit ? 'Update lesson details and status' : 'Add a lesson to your module'"></p>
                </div>
            </div>
            <button @click="$store.modals.closeLessonSlideout()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Panel Body (scrollable) --}}
        <div class="flex-1 overflow-y-auto">
            <form method="POST" :action="formAction" id="lessonSlideoutForm" class="p-6 space-y-5">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Module Selector --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                        Module <span class="text-red-500">*</span>
                    </label>
                    <select name="module_id"
                            x-model="selectedModuleId"
                            :disabled="mode === 'create' && selectedModuleId !== null && {{ json_encode($slideoutModules->count()) }} > 0 && $store.modals.lessonSlideoutModuleId !== null"
                            required
                            class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <option value="">— Select a module —</option>
                        @foreach($slideoutModules as $m)
                        <option value="{{ $m->id }}">{{ $m->title }}</option>
                        @endforeach
                    </select>
                    {{-- Hidden fallback when disabled --}}
                    <input type="hidden" name="module_id"
                              x-show="mode === 'create' && selectedModuleId !== null && $store.modals.lessonSlideoutModuleId !== null"
                           :value="selectedModuleId">
                    @error('module_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Title --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                        Lesson Title <span class="text-red-500">*</span>
                    </label>
                          <input type="text" name="title" x-model="title" required
                           placeholder="e.g., Introduction to the Topic"
                           class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                        Description
                        <span class="text-gray-400 font-normal normal-case tracking-normal ml-1">(optional)</span>
                    </label>
                    <textarea name="description" x-model="description" rows="4"
                              placeholder="What will learners gain from this lesson?"
                              class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors resize-none"></textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Active status --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50/50 dark:bg-gray-800/50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Active lesson</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5" x-text="isEdit ? 'Toggle to activate or deactivate this lesson.' : 'New lessons default to active.'"></p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                            <input type="hidden" name="is_published" :value="isPublished ? 1 : 0">
                            <input type="checkbox" class="sr-only peer" x-model="isPublished">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:ring-2 peer-focus:ring-purple-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"
                                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"></div>
                        </label>
                    </div>
                </div>

                {{-- Info banner --}}
                <div class="rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800/40 px-4 py-3">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-xs text-purple-700 dark:text-purple-300 leading-relaxed">
                            <span x-show="!isEdit">After creating the lesson, you'll be taken to the lesson page where you can add <strong>topics</strong> and attach a <strong>quiz</strong>.</span>
                            <span x-show="isEdit">After saving changes, you'll return to the lesson details page.</span>
                        </p>
                    </div>
                </div>
            </form>
        </div>

        {{-- Panel Footer --}}
        <div class="flex-shrink-0 border-t border-gray-100 dark:border-gray-700 px-6 py-4 flex items-center justify-end gap-3 bg-gray-50/50 dark:bg-gray-900/50">
            <button type="button" @click="$store.modals.closeLessonSlideout()"
                    class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors">
                Cancel
            </button>
            <button type="submit" form="lessonSlideoutForm"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!isEdit">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span x-text="isEdit ? 'Save Lesson Changes' : 'Create & Add Topics'"></span>
            </button>
        </div>
    </div>
</div>
