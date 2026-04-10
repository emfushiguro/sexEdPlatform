@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@section('content')
@php
    $initialReviewApplicationId = old('review_application_id', $focusApplicationId ?? null);
    $initialReviewApplicationId = $initialReviewApplicationId ? (int) $initialReviewApplicationId : null;
@endphp

<div class="mx-auto max-w-7xl px-4 py-8"
     x-data="{
        activeReview: @js($initialReviewApplicationId),
        expandedModules: {},
        confirmOpen: false,
        confirmActionType: 'archive',
        confirmApplicationId: null,
        confirmApplicationName: '',
        openReview(id) {
            this.activeReview = id;
        },
        closeReview() {
            this.activeReview = null;
        },
        toggleFinishedModules(id) {
            this.expandedModules[id] = !this.expandedModules[id];
        },
        syncModerationEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
        },
        openActionModal(action, id, name) {
            this.confirmActionType = action;
            this.confirmApplicationId = id;
            this.confirmApplicationName = name;
            this.confirmOpen = true;
        },
        closeActionModal() {
            this.confirmOpen = false;
            this.confirmApplicationId = null;
            this.confirmApplicationName = '';
        },
        submitAction() {
            if (!this.confirmApplicationId) {
                return;
            }

            const archiveUrl = @js(route('admin.instructor-applications.archive', ['application' => '__ID__']));
            const deleteUrl = @js(route('admin.instructor-applications.destroy', ['application' => '__ID__']));
            const actionUrl = this.confirmActionType === 'delete' ? deleteUrl : archiveUrl;

            this.$refs.actionForm.action = actionUrl.replace('__ID__', this.confirmApplicationId);
            this.$refs.actionMethod.value = this.confirmActionType === 'delete' ? 'DELETE' : 'POST';
            this.$refs.actionForm.submit();
        }
     }">
    <div :class="activeReview !== null ? 'blur-[2px] scale-[0.995] pointer-events-none select-none' : ''" class="space-y-8 transition duration-300 ease-out">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Pending</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($pendingCount) }}</p>
                <p class="mt-2 text-sm text-gray-500">Applications currently waiting for review.</p>
            </article>
            <article class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Approved</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($approvedCount) }}</p>
                <p class="mt-2 text-sm text-gray-500">Learners upgraded to instructor status.</p>
            </article>
            <article class="rounded-[28px] border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-5 shadow-theme-xs sm:col-span-2 xl:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-600">Rejected</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($rejectedCount) }}</p>
                <p class="mt-2 text-sm text-gray-500">Applications needing revision before re-apply.</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-6">
                @include('admin.partials.table-filter-bar', ['label' => 'Instructor Applications Filters', 'hint' => 'Search applicant names, usernames, education, and professional background.'])

                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Review Queue</p>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Instructor Applications Table</h2>
                        <p class="mt-1 text-sm text-gray-500">Review instructor applications and moderation history from this queue.</p>
                    </div>

                    <form method="GET" action="{{ route('admin.instructor-applications.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-12 xl:items-end">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <label class="block sm:col-span-2 xl:col-span-5">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input type="text"
                                   name="search"
                                   value="{{ $search ?? '' }}"
                                   placeholder="Name, email, username, education"
                                   class="h-[46px] w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <div class="sm:col-span-2 xl:col-span-4">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <div class="grid grid-cols-3 gap-2 sm:max-w-md xl:max-w-none">
                                <a href="{{ route('admin.instructor-applications.index', ['status' => 'pending', 'search' => $search]) }}" class="inline-flex h-[46px] items-center justify-center rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending</a>
                                <a href="{{ route('admin.instructor-applications.index', ['status' => 'approved', 'search' => $search]) }}" class="inline-flex h-[46px] items-center justify-center rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Approved</a>
                                <a href="{{ route('admin.instructor-applications.index', ['status' => 'rejected', 'search' => $search]) }}" class="inline-flex h-[46px] items-center justify-center rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Rejected</a>
                            </div>
                        </div>
                        <div class="sm:col-span-2 xl:col-span-3 flex flex-col gap-2 sm:flex-row xl:justify-end">
                            <button type="submit" class="inline-flex h-[46px] items-center justify-center rounded-2xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">Apply Filters</button>
                            <a href="{{ route('admin.instructor-applications.index', ['status' => $status]) }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th data-testid="applications-col-applicant" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Applicant Name</th>
                            <th data-testid="applications-col-email" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Email</th>
                            <th data-testid="applications-col-date-applied" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Application Date</th>
                            <th data-testid="applications-col-status" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th data-testid="applications-col-reviewed-by" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Reviewed By</th>
                            <th data-testid="applications-col-decision-date" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Decision Date</th>
                            <th data-testid="applications-col-actions" class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($applications as $application)
                            @php
                                $latestReview = $application->latestReview;
                                $reviewedByName = $latestReview?->reviewedBy?->name ?? $application->approvedBy?->name;
                                $decisionAt = $latestReview?->reviewed_at ?? $application->approved_at;
                                $rowNumber = ($applications->firstItem() ?? 1) + $loop->index;
                            @endphp
                            <tr class="transition hover:bg-sky-50/40">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $rowNumber }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $application->user->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $application->user->email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $application->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($application->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $reviewedByName ?? 'Not reviewed yet' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $decisionAt ? $decisionAt->format('M d, Y h:i A') : 'Pending' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                data-testid="review-application-button-{{ $application->id }}"
                                                @click="openReview({{ $application->id }})"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                                                title="Review Application"
                                                aria-label="Review Application">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <button type="button"
                                                @click="openActionModal('archive', {{ $application->id }}, @js($application->user->name))"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                                                title="Archive Application"
                                                aria-label="Archive Application">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                                            </svg>
                                        </button>

                                        <button type="button"
                                                @click="openActionModal('delete', {{ $application->id }}, @js($application->user->name))"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                title="Delete Application"
                                                aria-label="Delete Application">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-sm">
                                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-4 text-sm font-semibold text-gray-900">No applications found for this status</h3>
                                        <p class="mt-1 text-sm text-gray-500">Try switching tabs or widening your search query.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                {{ $applications->links() }}
            </div>
        </section>
    </div>

    <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center px-4" @keydown.escape.window="closeActionModal()">
        <div class="absolute inset-0 bg-gray-900/50" @click="closeActionModal()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-gray-900" x-text="confirmActionType === 'delete' ? 'Delete Application?' : 'Archive Application?'"></h3>
            <p class="mt-2 text-sm text-gray-600">
                <span x-show="confirmActionType === 'archive'">Archive this application for </span>
                <span x-show="confirmActionType === 'delete'">Permanently delete this application for </span>
                <span class="font-semibold" x-text="confirmApplicationName || 'this applicant'"></span>?
            </p>

            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="closeActionModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button"
                        @click="submitAction()"
                        :class="confirmActionType === 'delete' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <form method="POST" x-ref="actionForm" class="hidden">
        @csrf
        <input type="hidden" name="_method" x-ref="actionMethod" value="POST">
    </form>

    @foreach($applications as $application)
        @php
            $profile = $application->user->learnerProfile;
            $location = collect([
                $profile?->city?->name,
                $profile?->barangayLocation?->name ?? $profile?->barangay,
            ])->filter()->implode(', ');
            $avatarUrl = $profile?->avatar_path ? asset('storage/' . $profile->avatar_path) : null;
            $completedModules = $application->user->moduleEnrollments;
            $tierOneDocuments = [
                ['label' => 'Government ID', 'path' => $application->government_id_path],
                ['label' => 'Verification Document (NBI/Police Clearance)', 'path' => $application->clearance_path],
                ['label' => 'CV or Resume', 'path' => $application->cv_resume_path],
            ];
            $tierTwoDocuments = [
                ['label' => 'Teaching Credential', 'path' => $application->teaching_credential_path],
                ['label' => 'Sex Education Certificate', 'path' => $application->sexed_certificate_path],
                ['label' => 'Professional License', 'path' => $application->professional_license_path],
            ];
            $defaultReasonCode = old('review_application_id') == $application->id ? old('rejection_reason_code') : '';
            $defaultReasonNote = old('review_application_id') == $application->id ? old('rejection_reason_note') : '';
            $defaultAdminMessage = old('review_application_id') == $application->id ? old('admin_message') : null;
            $approveMessage = $defaultAdminMessage ?? $defaultApprovalMessage;
            $rejectMessage = $defaultAdminMessage ?? $defaultRejectionMessage;
            $approveModalDefaultOpen = old('review_application_id') == $application->id && old('modal_action') === 'approve' && $errors->has('admin_message');
            $rejectModalDefaultOpen = old('review_application_id') == $application->id && old('modal_action') === 'reject' && ($errors->has('rejection_reason_code') || $errors->has('rejection_reason_note') || $errors->has('admin_message'));
            $latestReview = $application->latestReview;
            $latestReviewerName = $latestReview?->reviewedBy?->name ?? $application->approvedBy?->name;
            $latestDecisionAt = $latestReview?->reviewed_at ?? $application->approved_at;
        @endphp

        <div x-show="activeReview === {{ $application->id }}"
             x-cloak
             data-testid="application-review-modal-{{ $application->id }}"
             @keydown.escape.window="if (activeReview === {{ $application->id }}) closeReview()"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8">
            <div x-show="activeReview === {{ $application->id }}"
                 x-transition.opacity
                 class="fixed inset-0 bg-gray-900/45 backdrop-blur-lg transition-opacity"
                 @click="closeReview()"></div>

            <div x-show="activeReview === {{ $application->id }}"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative z-50 w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                 x-data="{ approveModalOpen: @js($approveModalDefaultOpen), rejectModalOpen: @js($rejectModalDefaultOpen), selectedCode: @js($defaultReasonCode), chars: {{ strlen((string) $defaultReasonNote) }} }">

                <div class="border-b border-gray-100 bg-gray-50/80 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Application Review</p>
                            <h2 class="mt-1 text-xl font-bold text-gray-900">{{ $application->user->name }}</h2>
                            <p class="text-sm text-gray-500">Application #{{ $application->id }}</p>
                        </div>
                        <button type="button" @click="closeReview()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="max-h-[80vh] space-y-6 overflow-y-auto bg-white px-6 py-6">
                    <section class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ open: true }">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Section 1 - Application Information</h3>
                            <button type="button" @click="open = !open" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" x-text="open ? 'Hide' : 'Show'"></button>
                        </div>

                        <div x-show="open" x-cloak class="mt-4 space-y-4">
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="{{ $application->user->name }}" class="h-12 w-12 rounded-full border border-gray-200 object-cover">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 text-sm font-bold text-sky-700">
                                        {{ strtoupper(substr($application->user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $application->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $application->user->email }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-2">
                                <p><span class="font-semibold">Application Status:</span> <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($application->status) }}</span></p>
                                <p><span class="font-semibold">Date Applied:</span> {{ $application->created_at->format('M d, Y h:i A') }}</p>
                                <p><span class="font-semibold">Username:</span> {{ $profile?->username ?? 'N/A' }}</p>
                                <p><span class="font-semibold">Location:</span> {{ $location !== '' ? $location : 'Not set' }}</p>
                                <p><span class="font-semibold">Educational Background:</span> {{ $application->educational_background_label ?: 'Not provided' }}</p>
                                <p><span class="font-semibold">Professional Background:</span> {{ $application->bio ?: 'Not provided' }}</p>
                                <p><span class="font-semibold">Reviewed By:</span> {{ $latestReviewerName ?? 'Not reviewed yet' }}</p>
                                <p><span class="font-semibold">Decision Date:</span> {{ $latestDecisionAt ? $latestDecisionAt->format('M d, Y h:i A') : 'Pending' }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ open: true }">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Section 2 - Submitted Documents</h3>
                            <button type="button" @click="open = !open" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" x-text="open ? 'Hide' : 'Show'"></button>
                        </div>

                        <div x-show="open" x-cloak class="mt-4 space-y-6">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Tier 1 Documents</p>
                                <div class="mt-3 grid gap-4 md:grid-cols-2">
                                    @foreach($tierOneDocuments as $document)
                                        @if(! empty($document['path']))
                                            @php
                                                $documentPath = (string) $document['path'];
                                                $documentUrl = asset('storage/' . $documentPath);
                                                $extension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
                                                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                                $isPdf = $extension === 'pdf';
                                            @endphp
                                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-sm font-semibold text-gray-900">{{ $document['label'] }}</p>
                                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-bold uppercase text-gray-600">{{ $extension ?: 'file' }}</span>
                                                </div>

                                                @if($isImage)
                                                    <img src="{{ $documentUrl }}" alt="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200 object-cover">
                                                @elseif($isPdf)
                                                    <iframe src="{{ $documentUrl }}#toolbar=0&navpanes=0" title="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200"></iframe>
                                                @else
                                                    <p class="mt-3 rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-500">Inline preview is not available for this file type.</p>
                                                @endif

                                                <div class="mt-3 flex gap-2">
                                                    <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Preview Document" aria-label="Preview Document">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </a>
                                                    <a href="{{ $documentUrl }}" download class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100" title="Download Document" aria-label="Download Document">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-gray-800">Tier 2 Documents</p>
                                <div class="mt-3 grid gap-4 md:grid-cols-2">
                                    @foreach($tierTwoDocuments as $document)
                                        @if(! empty($document['path']))
                                            @php
                                                $documentPath = (string) $document['path'];
                                                $documentUrl = asset('storage/' . $documentPath);
                                                $extension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
                                                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                                $isPdf = $extension === 'pdf';
                                            @endphp
                                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-sm font-semibold text-gray-900">{{ $document['label'] }}</p>
                                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-bold uppercase text-gray-600">{{ $extension ?: 'file' }}</span>
                                                </div>

                                                @if($isImage)
                                                    <img src="{{ $documentUrl }}" alt="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200 object-cover">
                                                @elseif($isPdf)
                                                    <iframe src="{{ $documentUrl }}#toolbar=0&navpanes=0" title="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200"></iframe>
                                                @else
                                                    <p class="mt-3 rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-500">Inline preview is not available for this file type.</p>
                                                @endif

                                                <div class="mt-3 flex gap-2">
                                                    <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Preview Document" aria-label="Preview Document">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </a>
                                                    <a href="{{ $documentUrl }}" download class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100" title="Download Document" aria-label="Download Document">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach

                                    @if(collect($tierTwoDocuments)->every(fn ($doc) => empty($doc['path'])))
                                        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-500 md:col-span-2">
                                            No Tier 2 documents were submitted.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ open: true }">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Section 3 - Learner Data Snapshot</h3>
                            <button type="button" @click="open = !open" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" x-text="open ? 'Hide' : 'Show'"></button>
                        </div>

                        <div x-show="open" x-cloak class="mt-4 space-y-4">
                            <div class="grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-3">
                                <div class="rounded-xl border border-sky-100 bg-sky-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-sky-600">Total Enrolled Modules</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($application->user->enrolled_modules_count ?? 0) }}</p>
                                </div>
                                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Total Finished Modules</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($application->user->finished_modules_count ?? 0) }}</p>
                                </div>
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Total Certificates Earned</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($application->user->certificates_earned_count ?? 0) }}</p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                <button type="button"
                                        @click="toggleFinishedModules({{ $application->id }})"
                                        class="flex w-full items-center justify-between text-left text-sm font-semibold text-gray-800">
                                    <span>Finished Modules Breakdown</span>
                                    <svg class="h-4 w-4 transition-transform" :class="expandedModules[{{ $application->id }}] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-show="expandedModules[{{ $application->id }}]" x-cloak class="mt-3">
                                    @if($completedModules->isEmpty())
                                        <p class="text-sm text-gray-500">No completed modules yet.</p>
                                    @else
                                        <ul class="space-y-2">
                                            @foreach($completedModules as $enrollment)
                                                <li class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
                                                    <div class="font-medium text-gray-900">{{ $enrollment->module?->title ?? 'Untitled Module' }}</div>
                                                    <div class="text-xs text-gray-500">Finished {{ optional($enrollment->completed_at)->format('M d, Y h:i A') }}</div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ open: true }">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Section 4 - Moderation History</h3>
                            <button type="button" @click="open = !open" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" x-text="open ? 'Hide' : 'Show'"></button>
                        </div>

                        <div x-show="open" x-cloak class="mt-4">
                            @if($application->reviews->isEmpty())
                                <p class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">No moderation actions have been recorded yet.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach($application->reviews as $review)
                                        <article class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $review->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ ucfirst($review->status) }}</span>
                                                <span class="text-xs text-gray-500">{{ $review->reviewed_at?->format('M d, Y h:i A') ?? 'N/A' }}</span>
                                            </div>
                                            <p class="mt-2 text-sm text-gray-700"><span class="font-semibold">Reviewed By:</span> {{ $review->reviewedBy?->name ?? 'N/A' }}</p>
                                            @if($review->reason_label)
                                                <p class="mt-1 text-sm text-gray-700"><span class="font-semibold">Reason Category:</span> {{ $review->reason_label }}</p>
                                            @endif
                                            @if($review->reason_note)
                                                <p class="mt-1 text-sm text-gray-700"><span class="font-semibold">Reason Note:</span> {{ $review->reason_note }}</p>
                                            @endif
                                            @if($review->admin_message)
                                                <div class="mt-2 rounded-lg border border-gray-200 bg-white p-3 text-sm leading-6 text-gray-700">{!! $review->admin_message !!}</div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ open: true }">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Section 5 - Moderation Actions</h3>
                            <button type="button" @click="open = !open" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50" x-text="open ? 'Hide' : 'Show'"></button>
                        </div>

                        <div x-show="open" x-cloak class="mt-4">
                            @if($application->status === 'pending')
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" @click="approveModalOpen = true; rejectModalOpen = false" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve Application</button>
                                    <button type="button" @click="rejectModalOpen = true; approveModalOpen = false" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Reject Application</button>
                                </div>
                            @elseif($application->status === 'approved')
                                <p class="text-sm text-emerald-700">Approved by {{ $latestReviewerName ?? 'N/A' }} on {{ $latestDecisionAt ? $latestDecisionAt->format('M d, Y h:i A') : 'N/A' }}.</p>
                                @if($application->review_message)
                                    <div class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-900">{!! $application->review_message !!}</div>
                                @endif
                            @else
                                <p class="text-sm text-rose-700">Rejected by {{ $latestReviewerName ?? 'N/A' }} on {{ $latestDecisionAt ? $latestDecisionAt->format('M d, Y h:i A') : 'N/A' }}.</p>
                                @if($application->review_message)
                                    <div class="mt-3 rounded-lg border border-rose-100 bg-rose-50 p-3 text-sm text-rose-900">{!! $application->review_message !!}</div>
                                @endif
                            @endif
                        </div>
                    </section>
                </div>

                <div x-show="approveModalOpen"
                     x-cloak
                     class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 px-4 backdrop-blur-sm"
                     @click.self="approveModalOpen = false">
                    <div class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white shadow-2xl overflow-hidden">
                        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900">Approve Instructor Application</h3>
                            <p class="mt-1 text-sm text-gray-500">Confirm approval and customize the message that will be sent to the applicant.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.instructor-applications.approve', $application) }}" @submit="syncModerationEditors()">
                            @csrf
                            <input type="hidden" name="review_application_id" value="{{ $application->id }}">
                            <input type="hidden" name="modal_action" value="approve">

                            <div class="px-6 py-5 space-y-4">
                                <div>
                                    <label for="approve_admin_message_{{ $application->id }}" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Built-in Default Message (Editable)</label>
                                    <textarea id="approve_admin_message_{{ $application->id }}"
                                              name="admin_message"
                                              rows="6"
                                              class="js-instructor-moderation-editor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                              placeholder="Write your approval message here...">{{ $approveMessage }}</textarea>
                                    @if(old('review_application_id') == $application->id && old('modal_action') === 'approve')
                                        @error('admin_message')
                                            <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                                <button type="button"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                                        @click="approveModalOpen = false">
                                    Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors shadow-sm">
                                    Approve Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="rejectModalOpen"
                     x-cloak
                     class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 px-4 backdrop-blur-sm"
                     @click.self="rejectModalOpen = false">
                    <div class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white shadow-2xl overflow-hidden">
                        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900">Reject Instructor Application</h3>
                            <p class="mt-1 text-sm text-gray-500">Confirm rejection and provide a clear message to guide the applicant.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.instructor-applications.reject', $application) }}" @submit="syncModerationEditors()" class="space-y-4 px-6 py-5">
                            @csrf
                            <input type="hidden" name="review_application_id" value="{{ $application->id }}">
                            <input type="hidden" name="modal_action" value="reject">

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="rejection_reason_code_{{ $application->id }}">Reason category</label>
                                <select id="rejection_reason_code_{{ $application->id }}" name="rejection_reason_code" x-model="selectedCode" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                    <option value="" disabled>Select a reason</option>
                                    @foreach(\App\Enums\InstructorApplicationRejectionReason::cases() as $reason)
                                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                                @if(old('review_application_id') == $application->id && old('modal_action') === 'reject')
                                    @error('rejection_reason_code')
                                        <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="rejection_reason_note_{{ $application->id }}">Custom note <span class="text-xs text-gray-500">(required when reason is Other)</span></label>
                                <textarea id="rejection_reason_note_{{ $application->id }}"
                                          name="rejection_reason_note"
                                          rows="3"
                                          x-bind:required="selectedCode === 'other'"
                                          x-on:input="chars = $event.target.value.length"
                                          class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                          placeholder="Add actionable guidance for the applicant.">{{ $defaultReasonNote }}</textarea>
                                <div class="mt-1 flex justify-between text-xs text-gray-500">
                                    <span x-show="selectedCode === 'other'" x-cloak>Required for Other reason</span>
                                    <span x-show="selectedCode !== 'other'" x-cloak>Optional but recommended for clarity</span>
                                    <span x-text="chars + ' characters'"></span>
                                </div>
                                @if(old('review_application_id') == $application->id && old('modal_action') === 'reject')
                                    @error('rejection_reason_note')
                                        <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label for="reject_admin_message_{{ $application->id }}" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Default Rejection Message (Editable)</label>
                                <textarea id="reject_admin_message_{{ $application->id }}"
                                          name="admin_message"
                                          rows="6"
                                          class="js-instructor-moderation-editor w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                          placeholder="Write your rejection message here...">{{ $rejectMessage }}</textarea>
                                @if(old('review_application_id') == $application->id && old('modal_action') === 'reject')
                                    @error('admin_message')
                                        <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800">Applicants receive this message in their decision notification. Keep wording respectful, specific, and actionable.</div>

                            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                                <button type="button"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                        @click="rejectModalOpen = false">
                                    Cancel
                                </button>

                                <button type="submit" class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                                    Reject Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@if($hasPendingOnPage)
    @push('scripts')
        <script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof tinymce === 'undefined') {
                    return;
                }

                tinymce.remove('textarea.js-instructor-moderation-editor');
                tinymce.init({
                    selector: 'textarea.js-instructor-moderation-editor',
                    license_key: 'gpl',
                    menubar: false,
                    branding: false,
                    height: 220,
                    plugins: 'lists link table code',
                    toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link table | removeformat | code',
                    content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }'
                });
            });
        </script>
    @endpush
@endif
