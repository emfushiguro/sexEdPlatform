@extends('layouts.instructor-app')

@section('title', $module->title)

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('instructor.modules.index') }}"
           class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">{{ $module->title }}</h1>
            <p class="text-xs text-gray-400 dark:text-gray-500">Module Details</p>
        </div>
    </div>
    <a href="{{ route('instructor.modules.index', ['edit_module' => $module->id]) }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
       style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
        </svg>
        Edit Module
    </a>
</div>

{{-- ══  Section 1: Module Info Card  ══ --}}
<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-5">
    <div class="flex flex-col sm:flex-row gap-5">

        {{-- Thumbnail --}}
        <div class="flex-shrink-0 w-full sm:w-48 h-32 sm:h-36 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700">
            @if($module->thumbnail)
                <img src="{{ asset('storage/' . $module->thumbnail) }}"
                     alt="{{ $module->title }}"
                     class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center"
                     style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);">
                    <svg style="width:28px;height:28px;" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="flex-1 flex flex-col gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white leading-snug">{{ $module->title }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{{ strip_tags($module->description ?? 'No description.') }}</p>
            </div>

            {{-- Stat Chips --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-auto">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-0.5">Duration</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $module->duration_minutes ?: 0 }} <span class="font-normal text-gray-400 text-xs">min</span></p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-0.5">Lessons</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $module->lessons->count() }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-0.5">Enrolled</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $module->enrollments->where('status','approved')->count() }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-0.5">Status</p>
                    @if($module->is_published)
                        <span class="inline-flex items-center text-[11px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">Published</span>
                    @else
                        <span class="inline-flex items-center text-[11px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">Draft</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-5">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-purple-500">Review Status</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $module->current_review_status ?? 'approved' }}</p>
            @if(optional($module->reviewRequests->sortByDesc('id')->first())->feedback)
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Review feedback</p>
                    <p class="mt-1 text-sm text-amber-900">{{ $module->reviewRequests->sortByDesc('id')->first()->feedback }}</p>
                </div>
            @endif
        </div>
        <div class="flex gap-3">
            @if($module->current_review_status === 'needs_revision')
                <form method="POST" action="{{ route('instructor.modules.review.resubmit', $module) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition-all shadow-sm" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Resubmit for Review
                    </button>
                </form>
            @elseif(!$module->is_published)
                <form method="POST" action="{{ route('instructor.modules.review.submit', $module) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition-all shadow-sm" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Submit for Review
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- ══  Section 2: Enrolled Learners  ══ --}}
<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-5"
     x-data="{
         tab: 'all',
         rejectModalOpen: false,
         rejectEnrollmentId: null,
         rejectReasonCode: '',
         rejectReasonNote: '',
         rejectErrors: {},
         rejectReasons: [
             { value: 'prerequisite_missing', label: 'Prerequisite module not completed' },
             { value: 'age_requirement_not_met', label: 'Age requirement not met' },
             { value: 'profile_incomplete', label: 'Learner profile is incomplete' },
             { value: 'capacity_limit', label: 'Module capacity reached' },
             { value: 'other', label: 'Other' },
         ],
         enrollments: {{ json_encode($module->enrollments->map(fn($e) => [
             'id'        => $e->id,
             'name'      => trim(($e->user->first_name ?? '').( ' ').($e->user->last_name ?? '')) ?: ($e->user->name ?? 'Learner'),
             'email'     => $e->user->email ?? '',
             'status'    => $e->status,
             'enrolled'  => optional($e->created_at)->format('M d, Y') ?? '',
             'initials'  => strtoupper(substr($e->user->first_name ?? $e->user->name ?? 'L', 0, 1) . substr($e->user->last_name ?? '', 0, 1)),
         ])) }},
         get filtered() {
             if (this.tab === 'all') return this.enrollments.slice(0, 5);
             return this.enrollments.filter(e => e.status === this.tab).slice(0, 5);
         },
         get pendingCount() { return this.enrollments.filter(e => e.status === 'pending').length; },
         async approveEnrollment(id) {
             const res = await fetch('/instructor/enrollments/' + id + '/approve', {
                 method: 'PATCH',
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
             });
             if (res.ok) {
                 const i = this.enrollments.findIndex(e => e.id === id);
                 if (i > -1) this.enrollments[i].status = 'approved';
             }
         },
         openRejectModal(id) {
             this.rejectEnrollmentId = id;
             this.rejectReasonCode = '';
             this.rejectReasonNote = '';
             this.rejectErrors = {};
             this.rejectModalOpen = true;
         },
         async submitRejectEnrollment() {
             if (!this.rejectEnrollmentId) {
                 return;
             }

             this.rejectErrors = {};

             const res = await fetch('/instructor/enrollments/' + this.rejectEnrollmentId + '/reject', {
                 method: 'PATCH',
                 headers: {
                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     'Accept': 'application/json',
                     'Content-Type': 'application/json',
                 },
                 body: JSON.stringify({
                     rejection_reason_code: this.rejectReasonCode,
                     rejection_reason_note: this.rejectReasonNote,
                 }),
             });

             if (res.status === 422) {
                 const payload = await res.json();
                 this.rejectErrors = payload.errors || {};
                 return;
             }

             if (res.ok) {
                 const i = this.enrollments.findIndex(e => e.id === this.rejectEnrollmentId);
                 if (i > -1) this.enrollments[i].status = 'rejected';
                 this.rejectModalOpen = false;
                 this.rejectEnrollmentId = null;
             }
         },
         async unenroll(id) {
             if (!confirm('Remove this learner from the module?')) return;
             const res = await fetch('/instructor/enrollments/' + id, {
                 method: 'DELETE',
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
             });
             if (res.ok) {
                 this.enrollments = this.enrollments.filter(e => e.id !== id);
             }
         }
     }">

    {{-- Section Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Enrolled Learners</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500">Showing up to 5 — <a href="{{ route('instructor.modules.enrollments', $module) }}" class="text-purple-500 hover:text-purple-700">View all →</a></p>
        </div>
        <a href="{{ route('instructor.modules.enrollments', $module) }}"
           class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-200 transition-colors">
            Open full list →
        </a>
    </div>

    {{-- Tab Filter --}}
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700/50 rounded-xl p-1 mb-4 w-fit">
        @foreach([['all','All'], ['pending','Pending'], ['approved','Approved'], ['rejected','Rejected']] as [$val, $label])
        <button @click="tab = '{{ $val }}'"
                :class="tab === '{{ $val }}' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium'"
                class="relative px-3 py-1.5 rounded-lg text-xs transition-all">
            {{ $label }}
            @if($val === 'pending')
            <span x-show="pendingCount > 0" x-text="pendingCount"
                  class="ml-1 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"></span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Enrollments Table --}}
    <template x-if="filtered.length > 0">
        <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Learner</th>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest hidden md:table-cell">Email</th>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest hidden sm:table-cell">Enrolled</th>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                        <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="e in filtered" :key="e.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold text-purple-700 dark:text-purple-300"
                                         style="background: linear-gradient(135deg, rgba(163,14,178,0.12), rgba(59,12,177,0.12));"
                                         x-text="e.initials"></div>
                                    <span class="font-medium text-gray-900 dark:text-white text-sm" x-text="e.name"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs hidden md:table-cell" x-text="e.email"></td>
                            <td class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs hidden sm:table-cell" x-text="e.enrolled"></td>
                            <td class="px-4 py-3">
                                <span :class="{
                                    'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300': e.status === 'pending',
                                    'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300': e.status === 'approved',
                                    'bg-red-100 text-red-600 border-red-200 dark:bg-red-900/20 dark:text-red-400': e.status === 'rejected'
                                }" class="inline-flex items-center text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full border" x-text="e.status"></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Approve (pending only) --}}
                                    <button x-show="e.status === 'pending'"
                                            @click="approveEnrollment(e.id)"
                                            title="Approve enrollment"
                                            class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    {{-- Reject (pending only) --}}
                                    <button x-show="e.status === 'pending'"
                                            @click="openRejectModal(e.id)"
                                            title="Reject enrollment"
                                            class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    {{-- Remove (approved only) --}}
                                    <button x-show="e.status === 'approved'"
                                            @click="unenroll(e.id)"
                                            title="Remove learner"
                                            class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    <template x-if="filtered.length === 0">
        <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-600 py-8 text-center">
            <p class="text-sm text-gray-400 dark:text-gray-500">No <span x-text="tab === 'all' ? '' : tab"></span> enrollments found.</p>
        </div>
    </template>

    <div x-show="rejectModalOpen"
         x-cloak
         class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50" @click="rejectModalOpen = false"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-xl p-5 space-y-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Reject Enrollment</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choose a reason and optionally add a note for the learner.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Reason</label>
                <select x-model="rejectReasonCode"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-300">
                    <option value="">Select a reason</option>
                    <template x-for="reason in rejectReasons" :key="reason.value">
                        <option :value="reason.value" x-text="reason.label"></option>
                    </template>
                </select>
                <p x-show="rejectErrors.rejection_reason_code" class="mt-1 text-xs text-red-600" x-text="rejectErrors.rejection_reason_code?.[0]"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Note (optional)</label>
                <textarea x-model="rejectReasonNote"
                          rows="3"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-300"
                          placeholder="Add additional context for the learner"></textarea>
                <p x-show="rejectErrors.rejection_reason_note" class="mt-1 text-xs text-red-600" x-text="rejectErrors.rejection_reason_note?.[0]"></p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        @click="rejectModalOpen = false"
                        class="px-3 py-2 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button type="button"
                        @click="submitRejectEnrollment()"
                        class="px-3 py-2 rounded-xl text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors">
                    Reject Enrollment
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══  Section 3: Lessons List  ══ --}}
<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 p-6" x-data="{}">

    {{-- Section Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Lessons <span class="text-gray-400 font-normal text-sm">({{ $module->lessons->count() }})</span></h2>
            <p class="text-xs text-gray-400 dark:text-gray-500">Drag rows to reorder</p>
        </div>
        <button @click="$store.modals.openLessonSlideout({{ $module->id }})"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Lesson
        </button>
    </div>

    @if($module->lessons->count() > 0)
    <div id="lessons-sortable" class="space-y-2">
        @foreach($module->lessons as $lesson)
        @php
            $typeBadge = match($lesson->type ?? 'mixed') {
                'video'       => 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400',
                'text'        => 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
                'worksheet'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400',
                'interactive' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
                default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            };
        @endphp
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 hover:bg-purple-50/30 dark:hover:bg-purple-900/10 transition-colors group"
             data-lesson-id="{{ $lesson->id }}">

            {{-- Drag Handle --}}
            <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 dark:text-gray-600 hover:text-gray-400 dark:hover:text-gray-400 transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
                </svg>
            </div>

            {{-- Order Badge --}}
            <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                {{ $lesson->order }}
            </div>

            {{-- Lesson Info --}}
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm text-gray-900 dark:text-white leading-tight truncate">{{ $lesson->title }}</p>
                @if($lesson->description)
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ Str::limit(strip_tags($lesson->description), 80) }}</p>
                @endif
            </div>

            {{-- Type Badge --}}
            <span class="flex-shrink-0 inline-flex items-center text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full {{ $typeBadge }}">
                {{ ucfirst($lesson->type ?? 'mixed') }}
            </span>

            {{-- Duration --}}
            @if($lesson->duration)
            <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500 font-medium whitespace-nowrap hidden sm:block">{{ $lesson->duration }} min</span>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-0.5 flex-shrink-0 opacity-60 group-hover:opacity-100 transition-opacity">
                <a href="{{ route('instructor.lessons.show', $lesson) }}"
                   title="View lesson"
                   class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
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
                   title="Edit lesson"
                   class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
                <form action="{{ route('instructor.lessons.destroy', $lesson) }}" method="POST" class="inline-flex"
                      x-data @submit.prevent="if(confirm('Delete this lesson and all its topics?')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit" title="Delete lesson"
                            class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    @else
    {{-- Empty State --}}
    <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-600 py-12 text-center">
        <div class="mx-auto w-12 h-12 rounded-xl flex items-center justify-center mb-3"
             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">No lessons yet</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">Add your first lesson to start building this module's curriculum.</p>
        <button @click="$store.modals.openLessonSlideout({{ $module->id }})"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition-opacity shadow-sm"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add First Lesson
        </button>
    </div>
    @endif
</div>

{{-- Lesson Slide-Over Modal --}}
@include('instructor.lessons.partials.lesson-slideout')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('lessons-sortable');
        if (el) {
            Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'opacity-40',
                onEnd: function () {
                    const order = [...el.querySelectorAll('[data-lesson-id]')]
                        .map(el => el.dataset.lessonId);

                    fetch('{{ route("instructor.lessons.reorder") }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ order })
                    });
                }
            });
        }
    });
</script>
@endpush
