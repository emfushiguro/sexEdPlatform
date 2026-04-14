@extends($contentPanelLayout ?? 'layouts.instructor-app')

@section('content')
@php
    $moduleId = isset($moduleId) ? (int) $moduleId : 0;
    $lessonStatus = $lessonStatus ?? 'all';
    $search = $search ?? '';
    $availableModules = $availableModules ?? collect();

    $prefillLesson = null;
    if (request()->filled('edit_lesson')) {
        $editLessonId = (int) request('edit_lesson');
        $prefillLesson = $moduleGroups->flatMap(fn($group) => $group->lessons)->firstWhere('id', $editLessonId);
    }
@endphp
<div x-data="{
    q: @js($search),
    moduleFilter: @js($moduleId > 0 ? (string) $moduleId : ''),
    statusFilter: @js($lessonStatus),
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
    matchesLesson(lesson) {
        const query = this.q.trim().toLowerCase();
        const title = (lesson.title || '').toLowerCase();
        const description = (lesson.description || '').toLowerCase();
        const searchMatch = query === '' || title.includes(query) || description.includes(query);

        const statusMatch = this.statusFilter === 'all'
            || (this.statusFilter === 'active' && lesson.is_published)
            || (this.statusFilter === 'inactive' && !lesson.is_published);

        return searchMatch && statusMatch;
    },
    groupHasMatch(moduleId, lessons) {
        const moduleMatch = !this.moduleFilter || String(moduleId) === this.moduleFilter;
        if (!moduleMatch) {
            return false;
        }

        return lessons.some((lesson) => this.matchesLesson(lesson));
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
            <h1 class="text-xl font-bold text-gray-900">Manage Lessons</h1>
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

    {{-- Filters --}}
    <form method="GET" action="{{ route($contentRoutePrefix . '.lessons.index') }}" @submit.prevent class="rounded-2xl border border-gray-200 bg-white p-4" data-testid="lesson-filter-bar">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
            <div class="md:col-span-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Module</label>
                <select name="module_id" x-model="moduleFilter" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-200">
                    <option value="">All Modules</option>
                    @foreach($availableModules as $availableModule)
                        <option value="{{ $availableModule->id }}" @selected($moduleId === (int) $availableModule->id)>{{ $availableModule->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Lesson Status</label>
                <select name="lesson_status" x-model="statusFilter" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-200">
                    <option value="all" @selected($lessonStatus === 'all')>All</option>
                    <option value="active" @selected($lessonStatus === 'active')>Active</option>
                    <option value="inactive" @selected($lessonStatus === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Keyword</label>
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input type="text"
                           name="search"
                              x-model.debounce.200ms="q"
                           value="{{ $search }}"
                           placeholder="Search lesson title or description..."
                           class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
                </div>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2">
            <button type="button" @click="q = ''; moduleFilter = ''; statusFilter = 'all'" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-200">Reset</button>
        </div>

        <noscript>
            <div class="mt-3 flex items-center justify-end">
                <button type="submit" class="rounded-xl bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">Apply Filters</button>
            </div>
        </noscript>
    </form>

    {{-- Accordion Groups --}}
    @forelse($moduleGroups as $moduleGroup)
    @php
        $isInstructorOwnedGroup = in_array((string) ($moduleGroup->content_owner_type ?? ''), ['admin', 'instructor'], true)
            ? (string) $moduleGroup->content_owner_type === 'instructor'
            : ((string) optional($moduleGroup->creator)->role !== 'admin');

        $lessonsJson = $moduleGroup->lessons->map(fn($l) => [
            'id'          => $l->id,
            'title'       => $l->title,
            'description' => $l->description ?? '',
            'is_published' => (bool) $l->is_published,
        ])->values()->toJson();
    @endphp
    <div x-data="{
            open: true,
            lessons: {{ $lessonsJson }},
            get showGroup() { return groupHasMatch({{ (int) $moduleGroup->id }}, this.lessons); }
         }"
         x-show="showGroup"
         class="rounded-2xl bg-white shadow-sm border border-gray-100 overflow-hidden">

        {{-- Accordion Header --}}
        <div @click="open = !open"
             class="flex items-center justify-between px-5 py-4 cursor-pointer select-none hover:bg-gray-50/60 transition-colors">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2m14 0V9a2 2 0 0 0-2-2M5 11V9a2 2 0 0 1 2-2m0 0V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $moduleGroup->title }}</p>
                    <p class="text-xs text-gray-400">{{ $moduleGroup->lessons_count }} {{ Str::plural('lesson', $moduleGroup->lessons_count) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                @if(!(($isContentAdminPanel ?? false) && $isInstructorOwnedGroup))
                    <button @click.stop="$store.modals.openLessonSlideout({{ $moduleGroup->id }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white rounded-lg hover:opacity-90 transition-all"
                            style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add
                    </button>
                @endif
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
            <div class="border-t border-gray-100">
                @forelse($moduleGroup->lessons->sortBy('order') as $lesson)
                @php
                    $moduleReviewStatus = (string) ($moduleGroup->current_review_status ?? 'draft');
                    $lessonStatusLabel = $lesson->is_published ? 'Published' : 'Draft';
                    $lessonStatusClass = $lesson->is_published
                        ? 'bg-green-100 text-green-700'
                        : 'bg-gray-100 text-gray-500';

                    if ($moduleGroup->trashed()) {
                        $lessonStatusLabel = 'Module Archived';
                        $lessonStatusClass = 'bg-red-100 text-red-700';
                    } elseif ($moduleReviewStatus === 'in_review') {
                        $lessonStatusLabel = 'Module Under Review';
                        $lessonStatusClass = 'bg-amber-100 text-amber-700';
                    } elseif ($moduleReviewStatus === 'submitted') {
                        $lessonStatusLabel = 'Submission Pending';
                        $lessonStatusClass = 'bg-orange-100 text-orange-700';
                    } elseif ($moduleReviewStatus === 'needs_revision') {
                        $lessonStatusLabel = 'Needs Revision';
                        $lessonStatusClass = 'bg-rose-100 text-rose-700';
                    } elseif (!$moduleGroup->is_published) {
                        $lessonStatusLabel = 'Module Inactive';
                        $lessonStatusClass = 'bg-gray-100 text-gray-600';
                    }
                @endphp
                <div x-show="matchesLesson({
                        title: @js((string) $lesson->title),
                        description: @js((string) ($lesson->description ?? '')),
                        is_published: @js((bool) $lesson->is_published)
                    })"
                     data-lesson-id="{{ $lesson->id }}"
                     data-owner-type="{{ $isInstructorOwnedGroup ? 'instructor' : 'admin' }}"
                     class="flex items-center gap-4 px-5 py-3.5 border-b border-gray-50 last:border-0 hover:bg-purple-50/30 transition-colors">

                    {{-- Order Badge --}}
                    <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                         style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                        {{ $lesson->order ?? $loop->iteration }}
                    </div>

                    {{-- Lesson Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $lesson->title }}</p>
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
                    <span class="hidden sm:inline-flex px-2 py-0.5 text-xs font-semibold rounded-full flex-shrink-0 {{ $lessonStatusClass }}">
                        {{ $lessonStatusLabel }}
                    </span>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <a href="{{ route($contentRoutePrefix . '.lessons.show', $lesson) }}"
                           title="View"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 transition-colors action-icon-standard">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                        @if(!(($isContentAdminPanel ?? false) && $isInstructorOwnedGroup))
                            <button type="button"
                                data-edit-lesson-trigger
                                data-testid="lesson-edit-{{ $lesson->id }}"
                                @click="$store.modals.openLessonSlideout({{ $lesson->module_id }}, {{ Js::from([
                                     'id' => $lesson->id,
                                     'module_id' => $lesson->module_id,
                                     'title' => $lesson->title,
                                     'description' => $lesson->description,
                                     'is_published' => (bool) $lesson->is_published,
                                ]) }})"
                                title="Edit"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors action-icon-standard">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <form action="{{ route($contentRoutePrefix . '.lessons.destroy', $lesson) }}" method="POST" class="inline"
                                @submit.prevent="openDeleteConfirm($event.target)">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    data-testid="lesson-delete-{{ $lesson->id }}"
                                        title="Delete"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors action-icon-standard">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-gray-400">
                    <svg class="mx-auto w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                    </svg>
                    No lessons yet.
                        @if(!(($isContentAdminPanel ?? false) && $isInstructorOwnedGroup))
                        <button @click.stop="$store.modals.openLessonSlideout({{ $moduleGroup->id }})"
                            class="font-semibold hover:underline" style="color: #730DB1;">Add the first one</button>
                        @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @empty
    {{-- Empty state --}}
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center"
             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
            </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 mb-1">No modules yet</h3>
        <p class="text-sm text-gray-400 mb-5">Create a module first, then you can add lessons to it.</p>
        <a href="{{ route($contentRoutePrefix . '.modules.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            Go to Modules
        </a>
    </div>
    @endforelse

        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50" @click="closeDeleteConfirm()"></div>
        <div x-show="deleteModalOpen" x-cloak id="lessons-delete-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-gray-100" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900">Confirm Lesson Deletion</h3>
                <p class="mt-2 text-sm text-gray-600">This action permanently removes the selected lesson and all of its topics.</p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" data-delete-confirm-cancel @click="closeDeleteConfirm()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">Cancel</button>
                    <button type="button" data-delete-confirm-submit @click="confirmDelete()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">Delete</button>
                </div>
            </div>
        </div>

</div>

{{-- Lesson Slide-Over --}}
@include('instructor.lessons.partials.lesson-slideout')
@endsection


