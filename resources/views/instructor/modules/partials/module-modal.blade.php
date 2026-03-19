{{-- Module Creation Modal --}}
{{-- Triggered via: $store.modals.openModuleModal() --}}

{{-- Backdrop --}}
<div x-show="$store.modals.moduleModal"
     x-cloak
     class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-40 transition-opacity"
     @click="$store.modals.closeModuleModal()"
     @keydown.escape.window="$store.modals.closeModuleModal()"></div>

{{-- Dialog --}}
<div x-show="$store.modals.moduleModal"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
     @keydown.escape.window="$store.modals.closeModuleModal()">

    <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-100 dark:border-gray-700 max-w-2xl w-full max-h-[90vh] overflow-y-auto"
         @click.stop
         x-data="{
             thumbnailPreview: null,
             isPublished: true,
             previewImage(event) {
                 const file = event.target.files[0];
                 if (file) {
                     this.thumbnailPreview = URL.createObjectURL(file);
                 }
             }
         }">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-900 z-10 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Create New Module</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Fill in the details below to create your module</p>
                </div>
            </div>
            <button @click="$store.modals.closeModuleModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <form method="POST" action="{{ route('instructor.modules.store') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            {{-- Title --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                    Module Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="e.g., Understanding Your Body"
                       class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
                @error('title')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea name="description" rows="3" required
                          placeholder="Briefly describe what learners will discover in this module..."
                          class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors resize-none">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Thumbnail --}}
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                    Cover Thumbnail
                    <span class="text-gray-400 font-normal normal-case tracking-normal ml-1">(optional, shows on card)</span>
                </label>

                <div class="flex items-start gap-4">
                    {{-- Preview box --}}
                    <div class="flex-shrink-0 w-24 h-16 rounded-xl overflow-hidden border-2 border-dashed border-gray-200 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-800 transition-colors"
                         :class="thumbnailPreview ? 'border-purple-300' : ''">
                        <template x-if="thumbnailPreview">
                            <img :src="thumbnailPreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!thumbnailPreview">
                            <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </template>
                    </div>
                    {{-- Upload input --}}
                    <div class="flex-1">
                        <input type="file" name="thumbnail" accept="image/*"
                               @change="previewImage($event)"
                               class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 dark:file:bg-purple-900/20 dark:file:text-purple-300 hover:file:bg-purple-100 dark:hover:file:bg-purple-900/40 transition-colors cursor-pointer">
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">JPG, PNG, GIF up to 2MB. If none, a gradient background is used.</p>
                    </div>
                </div>
                @error('thumbnail')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Age Group + Enrollment Mode (2-col) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                        Age Group <span class="text-red-500">*</span>
                    </label>
                    <select name="age_bracket" required
                            class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
                        <option value="">— Select —</option>
                        <option value="kids" {{ old('age_bracket') === 'kids' ? 'selected' : '' }}>Kids (5–12)</option>
                        <option value="teens" {{ old('age_bracket') === 'teens' ? 'selected' : '' }}>Teens (13–17)</option>
                        <option value="adults" {{ old('age_bracket') === 'adults' ? 'selected' : '' }}>Adults (18+)</option>
                    </select>
                    @error('age_bracket')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-widest mb-1.5">
                        Enrollment Mode <span class="text-red-500">*</span>
                    </label>
                    <select name="enrollment_mode" required
                            class="block w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
                        <option value="auto" {{ old('enrollment_mode', 'auto') === 'auto' ? 'selected' : '' }}>Auto (open enrollment)</option>
                        <option value="manual" {{ old('enrollment_mode') === 'manual' ? 'selected' : '' }}>Manual (requires approval)</option>
                    </select>
                    @error('enrollment_mode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Status Toggle --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Active module</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">New modules default to active. Turn this off to save as inactive.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                        <input type="checkbox" class="sr-only peer" x-model="isPublished" name="is_published" value="1" checked>
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:ring-2 peer-focus:ring-purple-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r"
                             :style="isPublished ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1)' : ''"></div>
                    </label>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <button type="button" @click="$store.modals.closeModuleModal()"
                        class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create Module
                </button>
            </div>
        </form>
    </div>
</div>
