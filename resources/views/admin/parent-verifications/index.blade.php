@extends('layouts.admin')

@section('title', 'Parent & Child Verifications')
@section('page-title', 'Parent & Child Verifications')

@section('content')
@php
    $moderationReasons = \App\Enums\ParentChildModerationReason::cases();
    $parentStatusCounts = [
        'pending' => $parentApplications->filter(fn ($application) => ($application->parent_verification_status ?? 'pending') === 'pending')->count(),
        'approved' => $parentApplications->filter(fn ($application) => ($application->parent_verification_status ?? 'pending') === 'approved')->count(),
        'rejected' => $parentApplications->filter(fn ($application) => ($application->parent_verification_status ?? 'pending') === 'rejected')->count(),
    ];
    $childStatusCounts = [
        'pending' => $childApplications->filter(fn ($application) => ($application->verification_status ?? 'pending') === 'pending')->count(),
        'approved' => $childApplications->filter(fn ($application) => ($application->verification_status ?? 'pending') === 'approved')->count(),
        'rejected' => $childApplications->filter(fn ($application) => ($application->verification_status ?? 'pending') === 'rejected')->count(),
    ];
    $parentSearchRows = $parentApplications->map(function ($application) {
        $statusValue = $application->parent_verification_status ?: 'pending';
        $search = strtolower(trim(implode(' ', array_filter([
            (string) ($application->full_name ?? ''),
            (string) ($application->email ?? ''),
            (string) ($application->created_at?->format('M d, Y h:i A') ?? ''),
            (string) $statusValue,
        ]))));

        return [
            'status' => $statusValue,
            'search' => $search,
        ];
    })->values();
    $childSearchRows = $childApplications->map(function ($application) {
        $statusValue = $application->verification_status ?: 'pending';
        $search = strtolower(trim(implode(' ', array_filter([
            (string) ($application->parent?->full_name ?? ''),
            (string) ($application->parent?->email ?? ''),
            (string) ($application->child?->full_name ?? ''),
            (string) ($application->child?->learnerProfile?->username ?? ''),
            (string) ($application->created_at?->format('M d, Y h:i A') ?? ''),
            (string) $statusValue,
        ]))));

        return [
            'status' => $statusValue,
            'search' => $search,
        ];
    })->values();
@endphp

<div class="mx-auto max-w-7xl px-4 py-8"
     x-data="{
        activeType: @js($type),
        activeStatus: @js($status),
        parentCounts: @js($parentStatusCounts),
        childCounts: @js($childStatusCounts),
        parentSearchRows: @js($parentSearchRows),
        childSearchRows: @js($childSearchRows),
        searchQuery: '',
        flashMessage: '',
        flashType: 'success',
        previewOpen: false,
        previewUrl: '',
        previewTitle: '',
        previewType: 'file',
        previewCompareUrl: '',
        previewCompareTitle: '',
        previewCompareType: 'file',
        previewDetails: {},
        setType(type) {
            this.activeType = type;
            this.syncUrl();
        },
        setStatus(status) {
            this.activeStatus = status;
            this.syncUrl();
        },
        syncUrl() {
            const params = new URLSearchParams(window.location.search);
            params.set('type', this.activeType);
            params.set('status', this.activeStatus);
            window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());
        },
        normalizedSearchQuery() {
            return String(this.searchQuery || '').trim().toLowerCase();
        },
        rowMatchesSearch(searchableText) {
            const query = this.normalizedSearchQuery();

            if (query === '') {
                return true;
            }

            return String(searchableText || '').toLowerCase().includes(query);
        },
        filteredCountFor(type, status) {
            const rows = type === 'parents' ? this.parentSearchRows : this.childSearchRows;
            const query = this.normalizedSearchQuery();

            return rows.filter((row) => {
                const rowStatus = row.status || 'pending';

                if (rowStatus !== status) {
                    return false;
                }

                if (query === '') {
                    return true;
                }

                return String(row.search || '').includes(query);
            }).length;
        },
        countFor(type, status) {
            const query = this.normalizedSearchQuery();

            if (query !== '') {
                return this.filteredCountFor(type, status);
            }

            const counts = type === 'parents' ? this.parentCounts : this.childCounts;
            return Number(counts[status] || 0);
        },
        hasRowsForCurrent() {
            return this.countFor(this.activeType, this.activeStatus) > 0;
        },
        notify(message, type = 'success') {
            if (window.toast && typeof window.toast[type] === 'function') {
                window.toast[type](message);
                return;
            }

            this.flashMessage = message;
            this.flashType = type;
            setTimeout(() => {
                this.flashMessage = '';
            }, 3500);
        },
        handleModerationUpdated(detail) {
            const countsKey = detail.type === 'parents' ? 'parentCounts' : 'childCounts';
            const fromStatus = detail.oldStatus || 'pending';
            const toStatus = detail.newStatus || 'pending';

            if (fromStatus !== toStatus) {
                this[countsKey][fromStatus] = Math.max(Number(this[countsKey][fromStatus] || 0) - 1, 0);
                this[countsKey][toStatus] = Number(this[countsKey][toStatus] || 0) + 1;
            }

            this.notify(detail.message || 'Moderation decision saved successfully.', 'success');
        },
        handleModerationError(detail) {
            this.notify(detail.message || 'Failed to save moderation decision.', 'error');
        },
        openPreview(url, title, type, details = {}, compare = null) {
            this.previewUrl = url;
            this.previewTitle = title;
            this.previewType = type;
            this.previewCompareUrl = compare?.url || '';
            this.previewCompareTitle = compare?.title || '';
            this.previewCompareType = compare?.type || 'file';
            this.previewDetails = details || {};
            this.previewOpen = true;
        },
        closePreview() {
            this.previewOpen = false;
            this.previewUrl = '';
            this.previewTitle = '';
            this.previewType = 'file';
            this.previewCompareUrl = '';
            this.previewCompareTitle = '';
            this.previewCompareType = 'file';
            this.previewDetails = {};
        }
     }"
     @moderation-updated.window="handleModerationUpdated($event.detail)"
     @moderation-error.window="handleModerationError($event.detail)">
    <div :class="previewOpen ? 'blur-[2px] scale-[0.995] pointer-events-none select-none' : ''" class="space-y-6 transition duration-300 ease-out">
        <template x-if="flashMessage !== ''">
            <div class="rounded-2xl border px-4 py-3 text-sm"
                 :class="flashType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'"
                 x-text="flashMessage"></div>
        </template>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">Pending Parents</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2 2m6-2a8 8 0 11-16 0 8 8 0 0116 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900" x-text="Number(parentCounts.pending || 0).toLocaleString()">{{ number_format($pendingParentCount) }}</p>
                <p class="mt-1 text-xs text-amber-800">Parent applications waiting for admin moderation.</p>
            </article>
            <article class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Approved Parents</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900" x-text="Number(parentCounts.approved || 0).toLocaleString()">{{ number_format($approvedParentCount) }}</p>
                <p class="mt-1 text-xs text-emerald-800">Parent accounts approved and allowed to create child profiles.</p>
            </article>
            <article class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-700">Rejected Parents</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900" x-text="Number(parentCounts.rejected || 0).toLocaleString()">{{ number_format($rejectedParentCount) }}</p>
                <p class="mt-1 text-xs text-rose-800">Submissions returned with required fixes or invalid documents.</p>
            </article>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">Pending Children</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2 2m6-2a8 8 0 11-16 0 8 8 0 0116 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900" x-text="Number(childCounts.pending || 0).toLocaleString()">{{ number_format($pendingChildCount) }}</p>
                <p class="mt-1 text-xs text-amber-800">Child accounts pending relationship and document verification.</p>
            </article>
            <article class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Approved Children</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900" x-text="Number(childCounts.approved || 0).toLocaleString()">{{ number_format($approvedChildCount) }}</p>
                <p class="mt-1 text-xs text-emerald-800">Children approved and unlocked for monitored learning access.</p>
            </article>
            <article class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-700">Rejected Children</p>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900" x-text="Number(childCounts.rejected || 0).toLocaleString()">{{ number_format($rejectedChildCount) }}</p>
                <p class="mt-1 text-xs text-rose-800">Applications rejected until parent submits corrected details.</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-6 space-y-5">
                @include('admin.partials.table-filter-bar', ['label' => 'Parent and Child Verification Filters', 'hint' => 'Switch between parent and child queues and moderate records instantly without reloading the page.'])

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Moderation Queue</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Parent and Child Verification Table</h2>
                    <p class="mt-1 text-sm text-gray-500">Use the controls below to review parent IDs and child verification documents, then approve or reject each application.</p>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4">
                    <div class="flex flex-wrap gap-2 lg:flex-none">
                        <button type="button"
                                @click="setType('parents')"
                                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                :class="activeType === 'parents' ? 'bg-brand-500 text-white shadow-theme-xs' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-brand-50 hover:text-brand-700 hover:ring-brand-200'">
                            <span>Parent Accounts</span>
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                  :class="activeType === 'parents' ? 'bg-white/20 text-white' : 'bg-brand-100 text-brand-700'"
                                  x-text="countFor('parents', activeStatus)"></span>
                        </button>
                        <button type="button"
                                @click="setType('children')"
                                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                :class="activeType === 'children' ? 'bg-brand-500 text-white shadow-theme-xs' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-brand-50 hover:text-brand-700 hover:ring-brand-200'">
                            <span>Child Accounts</span>
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                  :class="activeType === 'children' ? 'bg-white/20 text-white' : 'bg-brand-100 text-brand-700'"
                                  x-text="countFor('children', activeStatus)"></span>
                        </button>
                    </div>

                    <div class="w-full lg:flex-1 lg:min-w-0">
                        <div class="relative">
                            <input type="text"
                                   x-model.debounce.250ms="searchQuery"
                                   placeholder="Search parent, child, email, username..."
                                   class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 pr-16 text-sm text-gray-900 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <button type="button"
                                    x-show="searchQuery"
                                    x-cloak
                                    @click="searchQuery = ''"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2.5 py-1 text-xs font-semibold text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $statusKey => $statusLabel)
                        <button type="button"
                                @click="setStatus('{{ $statusKey }}')"
                                class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-gray-300/50"
                                :class="activeStatus === '{{ $statusKey }}'
                                    ? '{{ $statusKey === 'pending' ? 'bg-amber-500 text-white' : ($statusKey === 'approved' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white') }} shadow-theme-xs'
                                    : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50'">
                            {{ $statusLabel }}
                            <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                  :class="activeStatus === '{{ $statusKey }}' ? 'bg-white/20 text-white' : '{{ $statusKey === 'pending' ? 'bg-amber-100 text-amber-700' : ($statusKey === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700') }}'"
                                  x-text="countFor(activeType, '{{ $statusKey }}')"></span>
                        </button>
                    @endforeach
                </div>

                @if($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto" x-show="activeType === 'parents'" x-cloak>
                <table class="w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-[38%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Parent</th>
                            <th class="w-[14%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Status</th>
                            <th class="w-[24%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Submitted</th>
                            <th class="w-[24%] px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($parentApplications as $application)
                            @php
                                $statusValue = $application->parent_verification_status ?: 'pending';
                                $parentDocumentPath = (string) ($application->parent_id_document_path ?? '');
                                $hasParentDocument = $parentDocumentPath !== '';
                                $parentDocumentUrl = $hasParentDocument ? asset('storage/' . $parentDocumentPath) : null;
                                $parentDocumentExtension = $hasParentDocument ? strtolower(pathinfo($parentDocumentPath, PATHINFO_EXTENSION)) : null;
                                $parentPreviewType = $hasParentDocument
                                    ? (in_array($parentDocumentExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
                                        ? 'image'
                                        : ($parentDocumentExtension === 'pdf' ? 'pdf' : 'file'))
                                    : 'file';
                                $parentRejectionReason = trim((string) preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags((string) ($application->parent_verification_rejection_reason ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
                                $parentPreviewDetails = [
                                    'Queue' => 'Parent Verification',
                                    'Parent Name' => $application->full_name,
                                    'Email' => $application->email,
                                    'Status' => ucfirst($statusValue),
                                    'Submitted At' => $application->created_at?->format('M d, Y h:i A') ?? 'N/A',
                                    'Rejection Reason' => $parentRejectionReason !== '' ? $parentRejectionReason : 'N/A',
                                ];
                                $parentSearchText = strtolower(trim(implode(' ', array_filter([
                                    (string) ($application->full_name ?? ''),
                                    (string) ($application->email ?? ''),
                                    (string) ($application->created_at?->format('M d, Y h:i A') ?? ''),
                                    (string) $statusValue,
                                ]))));
                            @endphp
                                <tr x-data="{
                                    currentStatus: @js($statusValue),
                                    rejectionReason: @js($parentRejectionReason),
                                    processingApprove: false,
                                    processingReject: false,
                                    approveConfirmOpen: false,
                                    rejectModalOpen: false,
                                    modalReasonCode: '',
                                    modalCustomReason: '',
                                    openApproveConfirm() {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        this.approveConfirmOpen = true;
                                    },
                                    closeApproveConfirm() {
                                        this.approveConfirmOpen = false;
                                    },
                                    openRejectModal() {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        this.modalReasonCode = '';
                                        this.modalCustomReason = '';
                                        this.rejectModalOpen = true;
                                    },
                                    closeRejectModal() {
                                        this.rejectModalOpen = false;
                                        this.modalReasonCode = '';
                                        this.modalCustomReason = '';
                                        if (typeof window.destroyParentChildModerationEditors === 'function') {
                                            window.destroyParentChildModerationEditors();
                                        }
                                    },
                                    async submitRejectModal(actionUrl) {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        if (!this.modalReasonCode) {
                                            this.$dispatch('moderation-error', {
                                                message: 'Please select a rejection reason.',
                                            });
                                            return;
                                        }

                                        if (this.modalReasonCode === 'others' && this.$refs.customReasonEditor && typeof tinymce !== 'undefined') {
                                            const editorId = this.$refs.customReasonEditor.id || '';
                                            const editorInstance = editorId ? tinymce.get(editorId) : null;

                                            if (editorInstance) {
                                                this.modalCustomReason = editorInstance.getContent({ format: 'html' }).trim();
                                            }
                                        }

                                        if (this.modalReasonCode === 'others' && !this.modalCustomReason.trim()) {
                                            this.$dispatch('moderation-error', {
                                                message: 'Please provide a custom rejection reason.',
                                            });
                                            return;
                                        }

                                        this.processingReject = true;

                                        try {
                                            const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                                            const formData = new FormData();
                                            formData.append('reason_code', this.modalReasonCode);

                                            if (this.modalReasonCode === 'others') {
                                                formData.append('custom_reason', this.modalCustomReason.trim());
                                            }

                                            const response = await fetch(actionUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'X-CSRF-TOKEN': csrfToken,
                                                },
                                                body: formData,
                                            });

                                            const payload = await response.json().catch(() => ({}));

                                            if (!response.ok) {
                                                throw new Error(payload.message || 'Unable to save moderation decision.');
                                            }

                                            const previousStatus = this.currentStatus;
                                            this.currentStatus = payload.status || 'rejected';
                                            this.rejectionReason = payload.rejection_reason || null;

                                            this.closeRejectModal();

                                            this.$dispatch('moderation-updated', {
                                                type: 'parents',
                                                oldStatus: previousStatus,
                                                newStatus: this.currentStatus,
                                                message: payload.message,
                                            });
                                        } catch (error) {
                                            this.$dispatch('moderation-error', {
                                                message: error.message,
                                            });
                                        } finally {
                                            this.processingReject = false;
                                        }
                                    },
                                    async submitApprove(actionUrl) {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        this.processingApprove = true;

                                        try {
                                            const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                                            const response = await fetch(actionUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'X-CSRF-TOKEN': csrfToken,
                                                },
                                            });

                                            const payload = await response.json().catch(() => ({}));

                                            if (!response.ok) {
                                                throw new Error(payload.message || 'Unable to save moderation decision.');
                                            }

                                            const previousStatus = this.currentStatus;
                                            this.currentStatus = payload.status || 'approved';
                                            this.rejectionReason = payload.rejection_reason || null;
                                            this.closeApproveConfirm();

                                            this.$dispatch('moderation-updated', {
                                                type: 'parents',
                                                oldStatus: previousStatus,
                                                newStatus: this.currentStatus,
                                                message: payload.message,
                                            });
                                        } catch (error) {
                                            this.$dispatch('moderation-error', {
                                                message: error.message,
                                            });
                                        } finally {
                                            this.processingApprove = false;
                                        }
                                    }
                                }"
                                x-show="activeStatus === currentStatus && rowMatchesSearch(@js($parentSearchText))"
                                x-cloak
                                class="transition hover:bg-sky-50/50">
                                <td class="px-4 py-3 align-top">
                                    <p class="text-sm font-semibold text-gray-900 break-words">{{ $application->full_name }}</p>
                                    <p class="text-xs text-gray-500 break-words">{{ $application->email }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                          :class="currentStatus === 'approved' ? 'bg-emerald-100 text-emerald-700' : (currentStatus === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')">
                                        <span x-text="currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700 whitespace-nowrap">
                                    {{ $application->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-4 py-3 align-top whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($hasParentDocument)
                                            <button type="button"
                                                    title="View document"
                                                    @click="openPreview(@js($parentDocumentUrl), @js('Parent Verification - '.$application->full_name), @js($parentPreviewType), @js($parentPreviewDetails))"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-200">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        @endif

                                        <div class="flex items-center gap-2" x-show="currentStatus === 'pending'" x-cloak>
                                            <button type="button"
                                                    title="Review and confirm approval"
                                                    data-testid="open-approval-confirm-modal"
                                                    @click="openApproveConfirm()"
                                                    :disabled="processingApprove || processingReject || currentStatus !== 'pending'"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
                                                    aria-label="Review and confirm approval">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                    title="Reject"
                                                    @click="openRejectModal()"
                                                    :disabled="processingApprove || processingReject || currentStatus !== 'pending'"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-200 disabled:cursor-not-allowed disabled:opacity-60">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    @include('admin.parent-verifications.partials.moderation-modal-shell', [
                                        'title' => 'Reject Parent Verification',
                                        'submitUrl' => route('admin.parent-verifications.parents.reject', $application),
                                        'moderationReasons' => $moderationReasons,
                                    ])

                                    <div x-show="approveConfirmOpen"
                                         x-cloak
                                         @keydown.escape.window="closeApproveConfirm()"
                                         class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 lg:p-8"
                                         data-testid="approval-confirm-modal">
                                        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeApproveConfirm()"></div>

                                        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl">
                                            <div class="border-b border-gray-100 px-5 py-4">
                                                <h3 class="text-sm font-semibold text-gray-900">Confirm Parent Approval</h3>
                                            </div>
                                            <div class="px-5 py-5">
                                                <p class="text-sm text-gray-700">Are you sure you want to approve this verification?</p>
                                            </div>
                                            <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
                                                <button type="button"
                                                        @click="closeApproveConfirm()"
                                                        :disabled="processingApprove"
                                                        class="inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60">
                                                    Cancel
                                                </button>
                                                <button type="button"
                                                        @click="submitApprove(@js(route('admin.parent-verifications.parents.approve', $application)))"
                                                        :disabled="processingApprove"
                                                        class="inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <span x-text="processingApprove ? 'Confirming...' : 'Confirm'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @empty
                        @endforelse
                        <tr x-show="!hasRowsForCurrent()">
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">No parent verification records found for this status. Try another status tab or switch account type.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-x-auto" x-show="activeType === 'children'" x-cloak>
                <table class="w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-[22%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Parent</th>
                            <th class="w-[22%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Child</th>
                            <th class="w-[16%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Verification Document</th>
                            <th class="w-[12%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Status</th>
                            <th class="w-[18%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Submitted</th>
                            <th class="w-[170px] px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($childApplications as $application)
                            @php
                                $statusValue = $application->verification_status ?: 'pending';
                                $verificationDocumentPath = (string) ($application->verification_document_path ?? '');
                                $hasVerificationDocument = $verificationDocumentPath !== '';
                                $verificationDocumentUrl = $hasVerificationDocument ? asset('storage/' . $verificationDocumentPath) : null;
                                $verificationDocumentExtension = $hasVerificationDocument ? strtolower(pathinfo($verificationDocumentPath, PATHINFO_EXTENSION)) : null;
                                $verificationPreviewType = $hasVerificationDocument
                                    ? (in_array($verificationDocumentExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
                                        ? 'image'
                                        : ($verificationDocumentExtension === 'pdf' ? 'pdf' : 'file'))
                                    : 'file';
                                $parentComparisonPath = (string) ($application->parent?->parent_id_document_path ?? '');
                                $hasParentComparisonDocument = $parentComparisonPath !== '';
                                $parentComparisonUrl = $hasParentComparisonDocument ? asset('storage/' . $parentComparisonPath) : null;
                                $parentComparisonExtension = $hasParentComparisonDocument ? strtolower(pathinfo($parentComparisonPath, PATHINFO_EXTENSION)) : null;
                                $parentComparisonType = $hasParentComparisonDocument
                                    ? (in_array($parentComparisonExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
                                        ? 'image'
                                        : ($parentComparisonExtension === 'pdf' ? 'pdf' : 'file'))
                                    : 'file';
                                $comparisonPreviewPayload = $hasParentComparisonDocument
                                    ? [
                                        'url' => $parentComparisonUrl,
                                        'type' => $parentComparisonType,
                                        'title' => 'Parent Government ID - ' . ($application->parent?->full_name ?? 'Parent'),
                                    ]
                                    : null;
                                $childRejectionReason = trim((string) preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags((string) ($application->verification_rejection_reason ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
                                $childPreviewDetails = [
                                    'Queue' => 'Child Verification',
                                    'Parent Name' => $application->parent?->full_name ?? 'Unknown parent',
                                    'Parent Email' => $application->parent?->email ?? 'N/A',
                                    'Child Name' => $application->child?->full_name ?? 'Unknown child',
                                    'Child Username' => $application->child?->learnerProfile?->username ?? 'N/A',
                                    'Status' => ucfirst($statusValue),
                                    'Submitted At' => $application->created_at?->format('M d, Y h:i A') ?? 'N/A',
                                    'Rejection Reason' => $childRejectionReason !== '' ? $childRejectionReason : 'N/A',
                                ];
                                $childSearchText = strtolower(trim(implode(' ', array_filter([
                                    (string) ($application->parent?->full_name ?? ''),
                                    (string) ($application->parent?->email ?? ''),
                                    (string) ($application->child?->full_name ?? ''),
                                    (string) ($application->child?->learnerProfile?->username ?? ''),
                                    (string) ($application->created_at?->format('M d, Y h:i A') ?? ''),
                                    (string) $statusValue,
                                ]))));
                            @endphp
                                <tr x-data="{
                                    currentStatus: @js($statusValue),
                                    rejectionReason: @js($childRejectionReason),
                                    processingApprove: false,
                                    processingReject: false,
                                    approveConfirmOpen: false,
                                    rejectModalOpen: false,
                                    modalReasonCode: '',
                                    modalCustomReason: '',
                                    openApproveConfirm() {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        this.approveConfirmOpen = true;
                                    },
                                    closeApproveConfirm() {
                                        this.approveConfirmOpen = false;
                                    },
                                    openRejectModal() {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        this.modalReasonCode = '';
                                        this.modalCustomReason = '';
                                        this.rejectModalOpen = true;
                                    },
                                    closeRejectModal() {
                                        this.rejectModalOpen = false;
                                        this.modalReasonCode = '';
                                        this.modalCustomReason = '';
                                        if (typeof window.destroyParentChildModerationEditors === 'function') {
                                            window.destroyParentChildModerationEditors();
                                        }
                                    },
                                    async submitRejectModal(actionUrl) {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        if (!this.modalReasonCode) {
                                            this.$dispatch('moderation-error', {
                                                message: 'Please select a rejection reason.',
                                            });
                                            return;
                                        }

                                        if (this.modalReasonCode === 'others' && this.$refs.customReasonEditor && typeof tinymce !== 'undefined') {
                                            const editorId = this.$refs.customReasonEditor.id || '';
                                            const editorInstance = editorId ? tinymce.get(editorId) : null;

                                            if (editorInstance) {
                                                this.modalCustomReason = editorInstance.getContent({ format: 'html' }).trim();
                                            }
                                        }

                                        if (this.modalReasonCode === 'others' && !this.modalCustomReason.trim()) {
                                            this.$dispatch('moderation-error', {
                                                message: 'Please provide a custom rejection reason.',
                                            });
                                            return;
                                        }

                                        this.processingReject = true;

                                        try {
                                            const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                                            const formData = new FormData();
                                            formData.append('reason_code', this.modalReasonCode);

                                            if (this.modalReasonCode === 'others') {
                                                formData.append('custom_reason', this.modalCustomReason.trim());
                                            }

                                            const response = await fetch(actionUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'X-CSRF-TOKEN': csrfToken,
                                                },
                                                body: formData,
                                            });

                                            const payload = await response.json().catch(() => ({}));

                                            if (!response.ok) {
                                                throw new Error(payload.message || 'Unable to save moderation decision.');
                                            }

                                            const previousStatus = this.currentStatus;
                                            this.currentStatus = payload.status || 'rejected';
                                            this.rejectionReason = payload.rejection_reason || null;

                                            this.closeRejectModal();

                                            this.$dispatch('moderation-updated', {
                                                type: 'children',
                                                oldStatus: previousStatus,
                                                newStatus: this.currentStatus,
                                                message: payload.message,
                                            });
                                        } catch (error) {
                                            this.$dispatch('moderation-error', {
                                                message: error.message,
                                            });
                                        } finally {
                                            this.processingReject = false;
                                        }
                                    },
                                    async submitApprove(actionUrl) {
                                        if (this.currentStatus !== 'pending') {
                                            return;
                                        }

                                        this.processingApprove = true;

                                        try {
                                            const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                                            const response = await fetch(actionUrl, {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'X-CSRF-TOKEN': csrfToken,
                                                },
                                            });

                                            const payload = await response.json().catch(() => ({}));

                                            if (!response.ok) {
                                                throw new Error(payload.message || 'Unable to save moderation decision.');
                                            }

                                            const previousStatus = this.currentStatus;
                                            this.currentStatus = payload.status || 'approved';
                                            this.rejectionReason = payload.rejection_reason || null;
                                            this.closeApproveConfirm();

                                            this.$dispatch('moderation-updated', {
                                                type: 'children',
                                                oldStatus: previousStatus,
                                                newStatus: this.currentStatus,
                                                message: payload.message,
                                            });
                                        } catch (error) {
                                            this.$dispatch('moderation-error', {
                                                message: error.message,
                                            });
                                        } finally {
                                            this.processingApprove = false;
                                        }
                                    }
                                }"
                                x-show="activeStatus === currentStatus && rowMatchesSearch(@js($childSearchText))"
                                x-cloak
                                class="transition hover:bg-sky-50/50">
                                <td class="px-4 py-3 align-top">
                                    <p class="text-sm font-semibold text-gray-900 break-words">{{ $application->parent?->full_name ?? 'Unknown parent' }}</p>
                                    <p class="text-xs text-gray-500 break-words">{{ $application->parent?->email }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <p class="text-sm font-semibold text-gray-900 break-words">{{ $application->child?->full_name ?? 'Unknown child' }}</p>
                                    <p class="text-xs text-gray-500 break-words">{{ $application->child?->learnerProfile?->username ?: 'No username' }}</p>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if($hasVerificationDocument)
                                        <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                            Uploaded
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-500">No document</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                          :class="currentStatus === 'approved' ? 'bg-emerald-100 text-emerald-700' : (currentStatus === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')">
                                        <span x-text="currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-gray-700 whitespace-nowrap">
                                    {{ $application->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 align-top whitespace-nowrap text-right">
                                    <div class="flex flex-nowrap items-center justify-end gap-2">
                                        @if($hasVerificationDocument)
                                            <button type="button"
                                                    title="View document"
                                                    @click="openPreview(@js($verificationDocumentUrl), @js('Child Verification - '.($application->child?->full_name ?? 'Child')), @js($verificationPreviewType), @js($childPreviewDetails), @js($comparisonPreviewPayload))"
                                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-200">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        @endif

                                        <div class="flex shrink-0 items-center gap-2" x-show="currentStatus === 'pending'" x-cloak>
                                            <button type="button"
                                                    title="Review and confirm approval"
                                                    data-testid="open-approval-confirm-modal"
                                                    @click="openApproveConfirm()"
                                                    :disabled="processingApprove || processingReject || currentStatus !== 'pending'"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
                                                    aria-label="Review and confirm approval">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                    title="Reject"
                                                    @click="openRejectModal()"
                                                    :disabled="processingApprove || processingReject || currentStatus !== 'pending'"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-200 disabled:cursor-not-allowed disabled:opacity-60">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    @include('admin.parent-verifications.partials.moderation-modal-shell', [
                                        'title' => 'Reject Child Verification',
                                        'submitUrl' => route('admin.parent-verifications.children.reject', $application),
                                        'moderationReasons' => $moderationReasons,
                                    ])

                                    <div x-show="approveConfirmOpen"
                                         x-cloak
                                         @keydown.escape.window="closeApproveConfirm()"
                                         class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 lg:p-8"
                                         data-testid="approval-confirm-modal">
                                        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeApproveConfirm()"></div>

                                        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl">
                                            <div class="border-b border-gray-100 px-5 py-4">
                                                <h3 class="text-sm font-semibold text-gray-900">Confirm Child Approval</h3>
                                            </div>
                                            <div class="px-5 py-5">
                                                <p class="text-sm text-gray-700">Are you sure you want to approve this verification?</p>
                                            </div>
                                            <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
                                                <button type="button"
                                                        @click="closeApproveConfirm()"
                                                        :disabled="processingApprove"
                                                        class="inline-flex rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60">
                                                    Cancel
                                                </button>
                                                <button type="button"
                                                        @click="submitApprove(@js(route('admin.parent-verifications.children.approve', $application)))"
                                                        :disabled="processingApprove"
                                                        class="inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                                    <span x-text="processingApprove ? 'Confirming...' : 'Confirm'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @empty
                        @endforelse
                        <tr x-show="!hasRowsForCurrent()">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">No child verification records found for this status. Try another status tab or switch account type.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div x-show="previewOpen"
         x-cloak
         @keydown.escape.window="closePreview()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div x-show="previewOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closePreview()"></div>

        <div x-show="previewOpen"
             x-transition:enter="ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-3 sm:scale-95"
             class="relative z-10 w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-900" x-text="previewTitle"></h2>
                <div class="flex items-center gap-2">
                    <button type="button" @click="closePreview()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="max-h-[78vh] overflow-auto bg-gray-50 p-5">
                <div x-show="Object.keys(previewDetails).length > 0" x-cloak class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Verification Details</p>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <template x-for="([label, value], index) in Object.entries(previewDetails)" :key="`${label}-${index}`">
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500" x-text="label"></dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 break-words" x-text="value || 'N/A'"></dd>
                            </div>
                        </template>
                    </dl>
                </div>

                <div class="grid gap-4" :class="previewCompareUrl ? 'lg:grid-cols-2' : 'grid-cols-1'">
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500" x-text="previewTitle"></p>

                        <template x-if="previewType === 'image'">
                            <img :src="previewUrl" alt="Primary document preview" class="mx-auto max-h-[55vh] w-auto max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                        </template>

                        <template x-if="previewType === 'pdf'">
                            <iframe :src="previewUrl + '#toolbar=0&navpanes=0'" class="h-[55vh] w-full rounded-lg border border-gray-200 bg-white" title="Primary document preview"></iframe>
                        </template>

                        <template x-if="previewType === 'file'">
                            <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
                                <p class="text-sm text-gray-600">Inline preview is not available for this file type.</p>
                                <a :href="previewUrl" download class="mt-4 inline-flex rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Download file</a>
                            </div>
                        </template>
                    </div>

                    <div x-show="previewCompareUrl" x-cloak class="rounded-xl border border-gray-200 bg-white p-3">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500" x-text="previewCompareTitle || 'Parent Comparison Document'"></p>

                        <template x-if="previewCompareType === 'image'">
                            <img :src="previewCompareUrl" alt="Comparison document preview" class="mx-auto max-h-[55vh] w-auto max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                        </template>

                        <template x-if="previewCompareType === 'pdf'">
                            <iframe :src="previewCompareUrl + '#toolbar=0&navpanes=0'" class="h-[55vh] w-full rounded-lg border border-gray-200 bg-white" title="Comparison document preview"></iframe>
                        </template>

                        <template x-if="previewCompareType === 'file'">
                            <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
                                <p class="text-sm text-gray-600">Inline preview is not available for this file type.</p>
                                <a :href="previewCompareUrl" download class="mt-4 inline-flex rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Download file</a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
    <script>
        window.initParentChildModerationEditor = function (textarea, retries) {
            const attemptCount = Number(retries || 0);

            if (typeof tinymce === 'undefined' || !textarea) {
                if (attemptCount >= 20) {
                    return;
                }

                window.setTimeout(function () {
                    window.initParentChildModerationEditor(textarea, attemptCount + 1);
                }, 50);
                return;
            }

            if (textarea.offsetParent === null) {
                if (attemptCount >= 20) {
                    return;
                }

                window.setTimeout(function () {
                    window.initParentChildModerationEditor(textarea, attemptCount + 1);
                }, 50);
                return;
            }

            if (!textarea.id) {
                textarea.id = 'parent-child-moderation-editor-' + Math.random().toString(36).slice(2, 10);
            }

            const existingInstance = tinymce.get(textarea.id);
            if (existingInstance) {
                existingInstance.remove();
            }

            tinymce.init({
                selector: '#' + textarea.id,
                license_key: 'gpl',
                menubar: false,
                branding: false,
                height: 180,
                plugins: 'lists link code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
                content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }',
                setup: function (editor) {
                    const sync = function () {
                        editor.save();
                        const element = editor.getElement();

                        if (element) {
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    };

                    editor.on('init', sync);
                    editor.on('change keyup SetContent', sync);
                },
            });
        };

        window.destroyParentChildModerationEditor = function (textarea) {
            if (typeof tinymce === 'undefined' || !textarea || !textarea.id) {
                return;
            }

            const editor = tinymce.get(textarea.id);

            if (!editor) {
                return;
            }

            editor.save();
            editor.remove();
        };

        window.destroyParentChildModerationEditors = function () {
            if (typeof tinymce === 'undefined') {
                return;
            }

            tinymce.remove('textarea.js-parent-child-moderation-editor');
        };

        window.initParentChildModerationEditors = function () {
            if (typeof tinymce === 'undefined') {
                return;
            }

            window.destroyParentChildModerationEditors();

            tinymce.init({
                selector: 'textarea.js-parent-child-moderation-editor',
                license_key: 'gpl',
                menubar: false,
                branding: false,
                height: 180,
                plugins: 'lists link code',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
                content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }',
                setup: function (editor) {
                    const sync = function () {
                        editor.save();
                        const element = editor.getElement();

                        if (element) {
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    };

                    editor.on('init', sync);
                    editor.on('change keyup SetContent', sync);
                },
            });
        };
    </script>
@endpush
