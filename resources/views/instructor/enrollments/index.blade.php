@extends('layouts.instructor-app')

@php
    $allEnrollments = $pendingEnrollments ?? collect();
    $statuses = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $statusCounts = [
        'all' => $allEnrollments->count(),
        'pending' => $allEnrollments->where('status', 'pending')->count(),
        'approved' => $allEnrollments->where('status', 'approved')->count(),
        'rejected' => $allEnrollments->where('status', 'rejected')->count(),
    ];
@endphp

@section('content')
<div x-data="{ currentStatus: 'pending' }" class="space-y-5" data-enrollment-list>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Enrollments</h1>
            <p class="text-xs text-gray-400 dark:text-gray-500">Manage learner enrollment requests</p>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-xl p-1 flex-shrink-0 flex-wrap">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $tab => $label)
        <button
            @click="currentStatus = '{{ $tab }}'"
            :class="currentStatus === '{{ $tab }}'
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium'"
            class="px-3 py-1.5 rounded-lg text-sm transition-all">
            {{ $label }}
            <span class="ml-1 text-xs font-bold">{{ $statusCounts[$tab] ?? 0 }}</span>
        </button>
        @endforeach
    </div>

    {{-- Enrollments Container --}}
    @if($allEnrollments->isNotEmpty())
        <div class="grid grid-cols-1 gap-3">
            @foreach($allEnrollments as $enrollment)
                @php
                    $profile = $enrollment->user->learnerProfile;
                    $age = $profile?->birthday ? $profile->getAge() : null;
                    $ageBracket = $profile?->birthday ? $profile->getAgeBracket() : null;
                    $reviewPayload = [
                        'id' => $enrollment->id,
                        'learner' => [
                            'name' => $enrollment->user->name,
                            'email' => $enrollment->user->email,
                            'username' => $profile?->username ?? 'N/A',
                            'age' => $age,
                            'ageBracket' => $ageBracket ?? 'N/A',
                            'gender' => $profile?->gender ?? 'N/A',
                            'city' => $profile?->city?->name ?? 'N/A',
                            'barangay' => $profile?->barangay?->name ?? 'N/A',
                            'isChild' => (bool) ($profile?->requires_parental_consent ?? false),
                            'parentName' => $enrollment->user->parent()?->name,
                            'profileComplete' => (bool) ($profile?->isCompleted() ?? false),
                        ],
                        'module' => [
                            'id' => $enrollment->module->id,
                            'title' => $enrollment->module->title,
                            'description' => \Illuminate\Support\Str::limit((string) $enrollment->module->description, 150),
                            'minAge' => $enrollment->module->min_age ?? 0,
                            'maxAge' => $enrollment->module->max_age ?? 100,
                            'lessonsCount' => $enrollment->module->lessons_count ?? $enrollment->module->lessons()->count(),
                            'isPremium' => (bool) ($enrollment->module->is_premium ?? false),
                            'thumbnailUrl' => $enrollment->module->thumbnail
                                ? asset('storage/' . $enrollment->module->thumbnail)
                                : asset('images/default-module.jpg'),
                        ],
                        'status' => $enrollment->status,
                        'appliedAt' => $enrollment->created_at->format('M d, Y h:i A'),
                    ];
                @endphp
                <div
                    x-show="currentStatus === 'all' || currentStatus === '{{ $enrollment->status }}'"
                    x-transition:enter="transition duration-150 ease-in"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-150 ease-out"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 flex items-center justify-between gap-4 hover:border-purple-200 dark:hover:border-purple-900/40 transition-colors">

                    {{-- Learner Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ $enrollment->user->first_name ?? $enrollment->user->name }}
                            @if($enrollment->user->last_name)
                            <span class="font-normal">{{ $enrollment->user->last_name }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $enrollment->user->email }}</p>
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-medium mt-1 truncate">
                            {{ $enrollment->module->title ?? 'Unknown Module' }}
                        </p>
                    </div>

                    {{-- Status Badge + Request Time --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        {{-- Status Badge --}}
                        @if($enrollment->status === 'pending')
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-900/60">Pending</span>
                        @elseif($enrollment->status === 'approved')
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-900/60">Approved</span>
                        @else
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-red-100 text-red-600 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-900/60">Rejected</span>
                        @endif

                        {{-- Requested Date --}}
                        <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 whitespace-nowrap">{{ $enrollment->created_at->format('M d') }}</span>
                    </div>

                    {{-- Actions --}}
                    @if($enrollment->status === 'pending')
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button"
                               @click='$store.modals.openEnrollmentReview(@js($reviewPayload))'
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-900/60 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Review Information
                            </button>
                            <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}" class="inline" onsubmit="return confirm('Approve this enrollment?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-900/60 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Approve
                                </button>
                            </form>
                            <button type="button"
                                    @click="$store.modals.openRejectModal({{ $enrollment->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-900/60 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reject
                            </button>
                        </div>
                    @else
                        <div class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">No actions</div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="flex justify-center mb-4">
                <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">No enrollment requests</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Enrollments will appear here as learners request access to your modules.</p>
        </div>
    @endif

</div>

{{-- ENROLLMENT REVIEW MODAL --}}
<div x-show="$store.modals.enrollmentReview"
     x-cloak
     @keydown.escape.window="$store.modals.closeEnrollmentReview()"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div x-show="$store.modals.enrollmentReview"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$store.modals.closeEnrollmentReview()"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="$store.modals.enrollmentReview"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                 style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                <div>
                    <h3 class="text-lg font-bold text-white">Enrollment Review</h3>
                    <p class="text-xs text-white/80 mt-0.5">Review learner information and module details</p>
                </div>
                <button type="button"
                        @click="$store.modals.closeEnrollmentReview()"
                        class="p-2 rounded-lg hover:bg-white/10 transition text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <div x-show="$store.modals.enrollmentReviewData?.learner?.age && ($store.modals.enrollmentReviewData.learner.age < $store.modals.enrollmentReviewData.module.minAge || $store.modals.enrollmentReviewData.learner.age > $store.modals.enrollmentReviewData.module.maxAge)"
                     class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
                    Learner age appears outside this module's recommended range.
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 space-y-3">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Learner Information</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Name:</span> <span x-text="$store.modals.enrollmentReviewData?.learner?.name"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Email:</span> <span x-text="$store.modals.enrollmentReviewData?.learner?.email"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Username:</span> <span x-text="$store.modals.enrollmentReviewData?.learner?.username"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Age:</span> <span x-text="$store.modals.enrollmentReviewData?.learner?.age ?? 'N/A'"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Gender:</span> <span x-text="$store.modals.enrollmentReviewData?.learner?.gender"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Location:</span> <span x-text="$store.modals.enrollmentReviewData?.learner?.barangay"></span>, <span x-text="$store.modals.enrollmentReviewData?.learner?.city"></span></p>
                        <p class="text-sm" :class="$store.modals.enrollmentReviewData?.learner?.profileComplete ? 'text-green-700' : 'text-red-700'">
                            <span class="font-semibold">Profile:</span>
                            <span x-text="$store.modals.enrollmentReviewData?.learner?.profileComplete ? 'Complete' : 'Incomplete'"></span>
                        </p>
                        <p x-show="$store.modals.enrollmentReviewData?.learner?.isChild" class="text-xs text-purple-700">
                            Parent: <span x-text="$store.modals.enrollmentReviewData?.learner?.parentName || 'Not linked'"></span>
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900/40 rounded-xl p-4 space-y-3">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Module Requested</h4>
                        <img :src="$store.modals.enrollmentReviewData?.module?.thumbnailUrl"
                             :alt="$store.modals.enrollmentReviewData?.module?.title"
                             class="w-16 h-16 rounded-lg object-cover">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="$store.modals.enrollmentReviewData?.module?.title"></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300" x-text="$store.modals.enrollmentReviewData?.module?.description"></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Age Range:</span> <span x-text="$store.modals.enrollmentReviewData?.module?.minAge"></span>-<span x-text="$store.modals.enrollmentReviewData?.module?.maxAge"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Lessons:</span> <span x-text="$store.modals.enrollmentReviewData?.module?.lessonsCount"></span></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300"><span class="font-semibold">Applied:</span> <span x-text="$store.modals.enrollmentReviewData?.appliedAt"></span></p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between">
                <button type="button"
                        @click="$store.modals.closeEnrollmentReview()"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                    Close
                </button>

                <div class="flex items-center gap-3">
                    <button type="button"
                            @click="$store.modals.openRejectModal($store.modals.enrollmentReviewData?.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition">
                        Reject
                    </button>

                    <form method="POST" :action="`/instructor/enrollments/${$store.modals.enrollmentReviewData?.id}/approve`">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-[0.98] shadow-sm"
                                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            Approve Enrollment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- REJECTION REASON MODAL --}}
<div x-show="$store.modals.rejectModal"
     x-cloak
     @keydown.escape.window="$store.modals.closeRejectModal()"
     class="fixed inset-0 z-[60] overflow-y-auto"
     style="display: none;">
    <div x-show="$store.modals.rejectModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$store.modals.closeRejectModal()"
         class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm"></div>

    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="$store.modals.rejectModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">

            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reject Enrollment</h3>
                <p class="text-xs text-gray-500 mt-1">Please provide a reason for rejection</p>
            </div>

            <form method="POST" :action="`/instructor/enrollments/${$store.modals.rejectEnrollmentId}/reject`">
                @csrf
                @method('PATCH')

                <div class="p-6 space-y-4">
                    <div>
                        <label for="rejection_reason_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <select id="rejection_reason_code"
                                name="rejection_reason_code"
                                x-model="$store.modals.rejectReason"
                                required
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm">
                            <option value="">Select a reason...</option>
                            <template x-for="reason in $store.modals.rejectReasons" :key="reason.value">
                                <option :value="reason.value" x-text="reason.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="rejection_reason_note" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Additional Notes <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea id="rejection_reason_note"
                                  name="rejection_reason_note"
                                  x-model="$store.modals.rejectNote"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Provide additional context or guidance for the learner..."
                                  class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="$store.modals.closeRejectModal()"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition active:scale-[0.98] shadow-sm">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
