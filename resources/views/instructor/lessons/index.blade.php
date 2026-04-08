@extends('layouts.instructor-app')

@section('content')
@php
    $prefillLesson = null;
    if (request()->filled('edit_lesson')) {
        $editLessonId = (int) request('edit_lesson');
        $prefillLesson = $moduleGroups->flatMap(fn($group) => $group->lessons)->firstWhere('id', $editLessonId);
    }
@endphp
<div x-data="{
    q: '',
    deleteModalOpen: false,
    deleteForm: null,
    openDeleteConfirm(form) {
        this.deleteForm = form;
        this.deleteModalOpen = true;
    },
    closeDeleteConfirm() {
        this.deleteModalOpen = false;
        this.deleteForm = null;
    },
    confirmDelete() {
        if (this.deleteForm) {
            this.deleteForm.submit();
        }
    },
    matchesSearch(text) {
        return !this.q || text.toLowerCase().includes(this.q.toLowerCase());
    },
    groupHasMatch(lessons) {
        if (!this.q) return true;
        return lessons.some(l => l.title.toLowerCase().includes(this.q.toLowerCase()) || (l.description && l.description.toLowerCase().includes(this.q.toLowerCase())));
    }
}"
@if($prefillLesson)
    x-init="$store.modals.openLessonSlideout({{ $prefillLesson->module_id }}, {{ Js::from([
        'id' => $prefillLesson->id,
        'module_id' => $prefillLesson->module_id,
        'title' => $prefillLesson->title,
        'description' => $prefillLesson->description,
        'is_published' => (bool) $prefillLesson->is_published,
    ]) }})"
@endif
 class="space-y-5">
    <span class="sr-only table-standard-numbering">Table numbering standard enabled</span>

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manage Lessons</h1>
            <p class="text-sm text-gray-400 mt-0.5">All lessons grouped by module</p>
        </div>
        <button @click="$store.modals.openLessonSlideout(null)"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Lesson
        </button>
    </div>

    {{-- Search --}}
    <div class="relative">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
        <input type="text"
               x-model.debounce.300ms="q"
               placeholder="Search lessons…"
               class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
    </div>

    {{-- Accordion Groups --}}
    @forelse($moduleGroups as $moduleGroup)
    @php
        $lessonsJson = $moduleGroup->lessons->map(fn($l) => [
            'id'          => $l->id,
            'title'       => $l->title,
            'description' => $l->description ?? '',
        ])->values()->toJson();
    @endphp
    <div x-data="{
            open: true,
            lessons: {{ $lessonsJson }},
            get showGroup() { return {{ $loop->first ? 'true' : 'false' }} || !$root.q || $root.groupHasMatch(this.lessons); }
         }"
         x-show="showGroup"
         class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

        {{-- Accordion Header --}}
        <div @click="open = !open"
             class="flex items-center justify-between px-5 py-4 cursor-pointer select-none hover:bg-gray-50/60 dark:hover:bg-gray-700/40 transition-colors">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $moduleGroup->title }}</p>
                    <p class="text-xs text-gray-400">{{ $moduleGroup->lessons_count }} {{ Str::plural('lesson', $moduleGroup->lessons_count) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                <button @click.stop="$store.modals.openLessonSlideout({{ $moduleGroup->id }})"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white rounded-lg hover:opacity-90 transition-all"
                        style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add
                </button>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>

        {{-- Accordion Body --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1">
            <div class="border-t border-gray-100 dark:border-gray-700">
                @forelse($moduleGroup->lessons->sortBy('order') as $lesson)
                @php
                    $lessonSearchText = strtolower($lesson->title . ' ' . ($lesson->description ?? ''));
                @endphp
                <div x-show="!q || {{ json_encode($lessonSearchText) }}.includes(q.toLowerCase())"
                     class="flex items-center gap-4 px-5 py-3.5 border-b border-gray-50 dark:border-gray-700/50 last:border-0 hover:bg-purple-50/30 dark:hover:bg-purple-900/10 transition-colors">

                    {{-- Order Badge --}}
                    <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                         style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                        {{ $lesson->order ?? $loop->iteration }}
                    </div>

                    {{-- Lesson Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $lesson->title }}</p>
                        @if($lesson->description)
                        <p class="text-xs text-gray-400 truncate mt-0.5">{{ Str::limit($lesson->description, 80) }}</p>
                        @endif
                    </div>

                    {{-- Duration --}}
                    <div class="hidden sm:flex items-center gap-1 text-xs text-gray-400 flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        {{ $lesson->topics()->sum('duration') ?: ($lesson->duration ?? 0) }} min
                    </div>

                    {{-- Topics count --}}
                    <div class="hidden md:flex items-center gap-1 text-xs text-gray-400 flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                        </svg>
                        {{ $lesson->topics()->count() }} topics
                    </div>

                    {{-- Published badge --}}
                    <span class="hidden sm:inline-flex px-2 py-0.5 text-xs font-semibold rounded-full flex-shrink-0
                        {{ $lesson->is_published ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                        {{ $lesson->is_published ? 'Published' : 'Draft' }}
                    </span>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <a href="{{ route('instructor.lessons.show', $lesson) }}"
                           title="View"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors action-icon-standard">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                                <button type="button"
                                    data-edit-lesson-trigger
                                    @click="$store.modals.openLessonSlideout({{ $lesson->module_id }}, {{ Js::from([
                                         'id' => $lesson->id,
                                         'module_id' => $lesson->module_id,
                                         'title' => $lesson->title,
                                         'description' => $lesson->description,
                                         'is_published' => (bool) $lesson->is_published,
                                    ]) }})"
                           title="Edit"
                           class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors action-icon-standard">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <form action="{{ route('instructor.lessons.destroy', $lesson) }}" method="POST" class="inline"
                            @submit.prevent="openDeleteConfirm($event.target)">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    title="Delete"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors action-icon-standard">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-gray-400">
                    <svg class="mx-auto w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>
                    No lessons yet.
                    <button @click.stop="$store.modals.openLessonSlideout({{ $moduleGroup->id }})"
                            class="font-semibold hover:underline" style="color: #730DB1;">Add the first one</button>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @empty
    {{-- Empty state --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
        <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center"
             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">No modules yet</h3>
        <p class="text-sm text-gray-400 mb-5">Create a module first, then you can add lessons to it.</p>
        <a href="{{ route('instructor.modules.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            Go to Modules
        </a>
    </div>
    @endforelse

        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50" @click="closeDeleteConfirm()"></div>
        <div x-show="deleteModalOpen" x-cloak id="lessons-delete-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Lesson Deletion</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">This action permanently removes the selected lesson and all of its topics.</p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" data-delete-confirm-cancel @click="closeDeleteConfirm()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                    <button type="button" data-delete-confirm-submit @click="confirmDelete()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">Delete</button>
                </div>
            </div>
        </div>

</div>

{{-- Lesson Slide-Over --}}
@include('instructor.lessons.partials.lesson-slideout')
@endsection
