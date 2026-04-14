@extends($contentPanelLayout ?? 'layouts.instructor-app')

@php
    $isAdminPanel = ($isContentAdminPanel ?? false) === true;
    $ownershipRestrictionTooltip = 'Instructor-owned content is read-only in the admin panel.';
    $quizzesForTable = $quizzes->map(function ($q) use ($isAdminPanel) {
        $timeLimit = (int) ($q->time_limit ?? 0);
        $ownerType = strtolower((string) ($q->owner_type ?? $q->module?->content_owner_type ?? 'instructor'));

        if (!in_array($ownerType, ['admin', 'platform', 'instructor'], true)) {
            $ownerType = 'instructor';
        }

        $ownerType = in_array($ownerType, ['admin', 'platform'], true) ? 'admin' : 'instructor';

        return [
            'id' => $q->id,
            'title' => $q->title,
            'description' => $q->description ?? '',
            'module_id' => $q->module_id,
            'lesson_id' => $q->lesson_id,
            'module_title' => $q->module?->title ?? '',
            'lesson_title' => $q->lesson?->title ?? '',
            'questions_count' => $q->questions_count,
            'passing_score' => $q->passing_score,
            'time_limit' => $q->time_limit,
            'time_limit_hours' => $timeLimit > 0 ? intdiv($timeLimit, 3600) : 0,
            'time_limit_minutes' => $timeLimit > 0 ? intdiv($timeLimit % 3600, 60) : 0,
            'time_limit_seconds' => $timeLimit > 0 ? $timeLimit % 60 : 0,
            'attempt_limit' => $q->attempt_limit,
            'is_active' => $q->is_active,
            'type' => $q->module_id ? 'module' : 'lesson',
            'owner_type' => $ownerType,
            'is_mutation_restricted' => $isAdminPanel && $ownerType !== 'admin',
        ];
    })->values()->all();

    $modulesForFilter = $modules->map(function ($mod) {
        return [
            'id' => $mod->id,
            'title' => $mod->title,
        ];
    })->values()->all();

    $prefillQuiz = null;
    $prefillQuizRestricted = false;
    if (request()->filled('edit_quiz')) {
        $prefillQuiz = $quizzes->firstWhere('id', (int) request('edit_quiz'));
        if ($prefillQuiz) {
            $prefillOwnerType = strtolower((string) ($prefillQuiz->owner_type ?? $prefillQuiz->module?->content_owner_type ?? 'instructor'));
            $prefillOwnerType = in_array($prefillOwnerType, ['admin', 'platform'], true) ? 'admin' : 'instructor';
            $prefillQuizRestricted = $isAdminPanel && $prefillOwnerType !== 'admin';
        }
    }

    $openCreateQuiz = request()->boolean('create_quiz');
@endphp

@push('scripts')
<script>
function quizTable() {
    return {
        search: '',
        moduleFilter: '',
        typeFilter: '',
        deleteModalOpen: false,
        deleteForm: null,
        currentPage: 1,
        perPage: 10,
        quizzes: @js($quizzesForTable),
        modules: @js($modulesForFilter),
        get filtered() {
            const self = this;
            return this.quizzes.filter(q => {
                const matchSearch = !self.search ||
                    q.title.toLowerCase().includes(self.search.toLowerCase()) ||
                    q.description.toLowerCase().includes(self.search.toLowerCase());
                const matchModule = !self.moduleFilter ||
                    String(q.module_id) === self.moduleFilter ||
                    (q.lesson_id && self.modules.some(m => String(m.id) === self.moduleFilter && q.module_title === m.title));
                const matchType = !self.typeFilter || q.type === self.typeFilter;
                return matchSearch && matchModule && matchType;
            });
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },
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
        resetPage() { this.currentPage = 1; },
    };
}
</script>
@endpush

@section('content')
<div x-data="quizTable()"
@if($openCreateQuiz)
    x-init="$store.modals.openQuizModal()"
@elseif($prefillQuiz && !$prefillQuizRestricted)
    x-init='$store.modals.openQuizModal({ id: {{ $prefillQuiz->id }}, title: @js($prefillQuiz->title), description: @js($prefillQuiz->description), module_id: {{ $prefillQuiz->module_id ?? 'null' }}, lesson_id: {{ $prefillQuiz->lesson_id ?? 'null' }}, passing_score: {{ $prefillQuiz->passing_score }}, time_limit: {{ $prefillQuiz->time_limit ?? 'null' }}, time_limit_hours: {{ $prefillQuiz->time_limit ? intdiv($prefillQuiz->time_limit, 3600) : 0 }}, time_limit_minutes: {{ $prefillQuiz->time_limit ? intdiv($prefillQuiz->time_limit % 3600, 60) : 0 }}, time_limit_seconds: {{ $prefillQuiz->time_limit ? $prefillQuiz->time_limit % 60 : 0 }}, attempt_limit: {{ $prefillQuiz->attempt_limit ?? 'null' }}, is_active: {{ $prefillQuiz->is_active ? 'true' : 'false' }} })'
@endif
 class="space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Manage Quizzes</h1>
            <p class="text-sm text-gray-400 mt-0.5">All quizzes for your modules and lessons</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route($contentRoutePrefix . '.image-library.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm4 10 2.5-3 2 2.5L15 10l3 5H7z"/>
                </svg>
                Quiz Image Library
            </a>
            <button @click="$store.modals.openQuizModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Create Quiz
            </button>
        </div>
    </div>

    {{-- Filters row --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text"
                   x-model.debounce.300ms="search"
                   @input="resetPage()"
                   placeholder="Search quizzesâ€¦"
                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
        </div>
        <select x-model="moduleFilter" @change="resetPage()"
                class="px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
            <option value="">All Modules</option>
            @foreach($modules as $mod)
            <option value="{{ $mod->id }}">{{ $mod->title }}</option>
            @endforeach
        </select>
        <select x-model="typeFilter" @change="resetPage()"
                class="px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
            <option value="">All Types</option>
            <option value="module">Module Quiz</option>
            <option value="lesson">Lesson Quiz</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 table-standard-numbering">
                <thead>
                    <tr class="bg-gray-50/60">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-widest">Quiz</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-widest">Belongs To</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-widest">Questions</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-widest">Passing</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-widest">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="paginated.length === 0">
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                                </svg>
                                <p class="text-sm text-gray-400">No quizzes found</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="quiz in paginated" :key="quiz.id">
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <p class="text-sm font-semibold text-gray-900" x-text="quiz.title"></p>
                                <p class="text-xs text-gray-400 truncate max-w-xs mt-0.5" x-text="quiz.description || 'â€”'"></p>
                            </td>
                            <td class="px-5 py-3.5">
                                <template x-if="quiz.type === 'module'">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-brand-50 text-brand-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                        <span x-text="quiz.module_title"></span>
                                    </span>
                                </template>
                                <template x-if="quiz.type === 'lesson'">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-purple-50 text-purple-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span x-text="quiz.lesson_title"></span>
                                    </span>
                                </template>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-sm font-semibold text-gray-900" x-text="quiz.questions_count"></span>
                                <span class="text-xs text-gray-400 ml-0.5">Qs</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="text-sm font-semibold text-gray-900" x-text="quiz.passing_score + '%'"></span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span :class="quiz.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full"
                                    x-text="quiz.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a :href="`{{ url($contentRoutePrefix . '/quizzes') }}/${quiz.id}`"
                                       title="View"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 transition-colors action-icon-standard">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    <button type="button"
                                       data-edit-quiz-trigger
                                       @click="if (!quiz.is_mutation_restricted) { $store.modals.openQuizModal(quiz); }"
                                       :disabled="quiz.is_mutation_restricted"
                                       :title="quiz.is_mutation_restricted ? @js($ownershipRestrictionTooltip) : 'Edit'"
                                       :class="quiz.is_mutation_restricted ? 'cursor-not-allowed opacity-50' : ''"
                                       class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors action-icon-standard">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <form :action="`{{ url($contentRoutePrefix . '/quizzes') }}/${quiz.id}`" method="POST" class="inline"
                                          @submit.prevent="if (!quiz.is_mutation_restricted) { openDeleteConfirm($event.target); }">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                :disabled="quiz.is_mutation_restricted"
                                                :title="quiz.is_mutation_restricted ? @js($ownershipRestrictionTooltip) : 'Delete'"
                                                :class="quiz.is_mutation_restricted ? 'cursor-not-allowed opacity-50' : ''"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors action-icon-standard">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-3.5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/40">
            <p class="text-xs text-gray-400">
                Showing
                <span class="font-semibold text-gray-600"
                      x-text="filtered.length === 0 ? 0 : ((currentPage-1)*perPage+1)"></span>â€“<span
                      class="font-semibold text-gray-600"
                      x-text="Math.min(currentPage*perPage, filtered.length)"></span>
                of <span class="font-semibold text-gray-600" x-text="filtered.length"></span> quizzes
            </p>
            <div class="flex items-center gap-1.5">
                <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-for="page in totalPages" :key="page">
                    <button @click="currentPage = page"
                            :class="currentPage === page ? 'text-white border-transparent' : 'text-gray-500 border-gray-200 hover:bg-gray-100'"
                            :style="currentPage === page ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1);' : ''"
                            class="w-7 h-7 flex items-center justify-center rounded-lg border text-xs font-semibold transition-colors"
                            x-text="page"></button>
                </template>
                <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50" @click="closeDeleteConfirm()"></div>
    <div x-show="deleteModalOpen" x-cloak id="quizzes-delete-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-gray-100" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Confirm Quiz Deletion</h3>
            <p class="mt-2 text-sm text-gray-600">This action permanently removes the selected quiz and all of its questions.</p>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" data-delete-confirm-cancel @click="closeDeleteConfirm()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">Cancel</button>
                <button type="button" data-delete-confirm-submit @click="confirmDelete()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">Delete</button>
            </div>
        </div>
    </div>
</div>

{{-- Quiz Creation Modal --}}
@include('instructor.quizzes.partials.quiz-modal')
@endsection


