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
            'id' => (int) $application->id,
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
            'id' => (int) $application->id,
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
        page: 1,
        perPage: 10,
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
            this.page = 1;
            this.syncUrl();
        },
        setStatus(status) {
            this.activeStatus = status;
            this.page = 1;
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
        rowsFor(type) {
            return type === 'parents' ? this.parentSearchRows : this.childSearchRows;
        },
        filteredRowsFor(type, status = null) {
            const query = this.normalizedSearchQuery();

            return this.rowsFor(type).filter((row) => {
                const rowStatus = row.status || 'pending';

                if (status && rowStatus !== status) {
                    return false;
                }

                if (query === '') {
                    return true;
                }

                return String(row.search || '').includes(query);
            });
        },
        filteredCountFor(type, status) {
            return this.filteredRowsFor(type, status).length;
        },
        countFor(type, status) {
            const query = this.normalizedSearchQuery();

            if (query !== '') {
                return this.filteredCountFor(type, status);
            }

            const counts = type === 'parents' ? this.parentCounts : this.childCounts;
            return Number(counts[status] || 0);
        },
        totalCountFor(type) {
            const counts = type === 'parents' ? this.parentCounts : this.childCounts;

            return Number(counts.pending || 0)
                + Number(counts.approved || 0)
                + Number(counts.rejected || 0);
        },
        totalRowsForCurrent() {
            return this.filteredRowsFor(this.activeType, this.activeStatus).length;
        },
        totalPages() {
            const pages = Math.ceil(this.totalRowsForCurrent() / this.perPage);

            return pages > 0 ? pages : 1;
        },
        safePage() {
            return Math.min(Math.max(this.page, 1), this.totalPages());
        },
        setPage(page) {
            const targetPage = Number(page);

            if (!Number.isFinite(targetPage)) {
                return;
            }

            this.page = Math.min(Math.max(Math.floor(targetPage), 1), this.totalPages());
        },
        prevPage() {
            this.setPage(this.safePage() - 1);
        },
        nextPage() {
            this.setPage(this.safePage() + 1);
        },
        formatNumber(value) {
            const numeric = Number(value || 0);

            return Number.isFinite(numeric) ? numeric.toLocaleString() : '0';
        },
        rowOnCurrentPage(type, rowId) {
            const rows = this.filteredRowsFor(type, this.activeStatus);
            const safePage = this.safePage();
            const start = (safePage - 1) * this.perPage;
            const visibleRows = rows.slice(start, start + this.perPage);

            return visibleRows.some((row) => Number(row.id) === Number(rowId));
        },
        rowNumberFor(type, rowId) {
            const rows = this.filteredRowsFor(type, this.activeStatus);
            const index = rows.findIndex((row) => Number(row.id) === Number(rowId));

            return index >= 0 ? index + 1 : '-';
        },
        hasRowsForCurrent() {
            return this.totalRowsForCurrent() > 0;
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
            const rowsKey = detail.type === 'parents' ? 'parentSearchRows' : 'childSearchRows';
            const fromStatus = detail.oldStatus || 'pending';
            const toStatus = detail.newStatus || 'pending';
            const rowId = Number(detail.rowId || 0);

            if (fromStatus !== toStatus) {
                this[countsKey][fromStatus] = Math.max(Number(this[countsKey][fromStatus] || 0) - 1, 0);
                this[countsKey][toStatus] = Number(this[countsKey][toStatus] || 0) + 1;
            }

            if (rowId > 0) {
                const row = this[rowsKey].find((item) => Number(item.id) === rowId);

                if (row) {
                    row.status = toStatus;
                }
            }

            this.page = this.safePage();

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

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
            <article class="min-h-[116px] rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700" x-text="activeType === 'parents' ? 'Parent Applications' : 'Child Applications'">Parent Applications</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 via-brand-700 to-brand-900 text-white shadow-lg shadow-brand-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(totalCountFor(activeType))">{{ number_format($pendingParentCount + $approvedParentCount + $rejectedParentCount) }}</p>
            </article>
            <article class="min-h-[116px] rounded-[28px] border border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Pending</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 via-brand-600 to-brand-800 text-white shadow-lg shadow-brand-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(countFor(activeType, 'pending'))">{{ number_format($pendingParentCount) }}</p>
            </article>
            <article class="min-h-[116px] rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-100/60 via-white to-brand-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-800">Approved</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 text-white shadow-lg shadow-brand-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(countFor(activeType, 'approved'))">{{ number_format($approvedParentCount) }}</p>
            </article>
            <article class="min-h-[116px] rounded-[28px] border border-brand-300 bg-gradient-to-br from-brand-100 via-white to-brand-200/70 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-900">Rejected</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 text-white shadow-lg shadow-brand-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900" x-text="formatNumber(countFor(activeType, 'rejected'))">{{ number_format($rejectedParentCount) }}</p>
            </article>
        </section>

                <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Verifications Table</h2>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6" data-testid="admin-table-filter-bar">
                        <label class="block xl:col-span-2">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input type="text" x-model.debounce.300ms="searchQuery" placeholder="Name, email, ID number..." class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select x-model="activeStatus" @change="setStatus($event.target.value)" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </label>
                        <div class="hidden xl:block"></div>
                        <div class="hidden xl:block"></div>
                        <div class="hidden xl:block"></div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="w-[10%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">No. #</th>
                            <th class="w-[32%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Parent</th>
                            <th class="w-[14%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Status</th>
                            <th class="w-[22%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Submitted</th>
                            <th class="w-[22%] px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Actions</th>
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
                                                rowId: {{ (int) $application->id }},
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
                                                rowId: {{ (int) $application->id }},
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
                                x-show="activeStatus === currentStatus && rowMatchesSearch(@js($parentSearchText)) && rowOnCurrentPage('parents', {{ (int) $application->id }})"
                                x-cloak
                                class="transition hover:bg-brand-50/55">
                                <td class="px-4 py-3 align-top text-sm font-semibold text-gray-500" x-text="rowNumberFor('parents', {{ (int) $application->id }})"></td>
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
                                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700">
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
                                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
                                                    aria-label="Review and confirm approval">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                    title="Reject"
                                                    @click="openRejectModal()"
                                                    :disabled="processingApprove || processingReject || currentStatus !== 'pending'"
                                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700">
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
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No parent verification records found for this status. Try another status tab or switch account type.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-x-auto" x-show="activeType === 'children'" x-cloak>
                <table class="w-full table-fixed divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="w-[10%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">No. #</th>
                            <th class="w-[19%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Parent</th>
                            <th class="w-[19%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500">Child</th>
                            <th class="w-[16%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Verification Document</th>
                            <th class="w-[12%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Status</th>
                            <th class="w-[16%] px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.18em] text-gray-500 whitespace-nowrap">Submitted</th>
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
                                                rowId: {{ (int) $application->id }},
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
                                                rowId: {{ (int) $application->id }},
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
                                x-show="activeStatus === currentStatus && rowMatchesSearch(@js($childSearchText)) && rowOnCurrentPage('children', {{ (int) $application->id }})"
                                x-cloak
                                class="transition hover:bg-brand-50/55">
                                <td class="px-4 py-3 align-top text-sm font-semibold text-gray-500" x-text="rowNumberFor('children', {{ (int) $application->id }})"></td>
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
                                        <span class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-700">
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
                                                    class="shrink-0 inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700">
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
                                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
                                                    aria-label="Review and confirm approval">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                    title="Reject"
                                                    @click="openRejectModal()"
                                                    :disabled="processingApprove || processingReject || currentStatus !== 'pending'"
                                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700">
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
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No child verification records found for this status. Try another status tab or switch account type.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="prevPage()"
                            :disabled="safePage() === 1"
                            class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">
                        Previous
                    </button>
                    <span class="text-sm text-gray-600">Page <span class="font-semibold" x-text="safePage()"></span> of <span class="font-semibold" x-text="totalPages()"></span></span>
                    <button type="button"
                            @click="nextPage()"
                            :disabled="safePage() >= totalPages()"
                            class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">
                        Next
                    </button>
                </div>
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
