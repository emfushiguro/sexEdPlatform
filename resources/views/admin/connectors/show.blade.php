@extends('layouts.admin')

@section('title', 'Connector Review')
@section('page-title', 'Connector Review')

@php
    $statusClass = [
        'pending' => 'bg-amber-100 text-amber-700',
        'verified' => 'bg-emerald-100 text-emerald-700',
        'rejected' => 'bg-rose-100 text-rose-700',
        'suspended' => 'bg-gray-100 text-gray-700',
    ][$connector->status] ?? 'bg-gray-100 text-gray-700';
@endphp

@section('content')
<div x-data="connectorReviewPage()" class="grid gap-6 lg:grid-cols-3">
    <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs lg:col-span-2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Connector</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h1>
                <p class="mt-2 text-sm leading-6 text-gray-500">{{ $connector->description ?: 'No description provided.' }}</p>
            </div>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $connector->status }}</span>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4"><dt class="text-xs font-semibold uppercase text-gray-500">Representative</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $connector->primaryRepresentative?->name }} <span class="font-normal text-gray-500">({{ $connector->primaryRepresentative?->email }})</span></dd></div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4"><dt class="text-xs font-semibold uppercase text-gray-500">Category</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', $connector->category) }}</dd></div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4"><dt class="text-xs font-semibold uppercase text-gray-500">Contact</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $connector->contact_number }}</dd></div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4"><dt class="text-xs font-semibold uppercase text-gray-500">Email</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $connector->organization_email ?: 'None' }}</dd></div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Address</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $connector->address_line }} ({{ $connector->barangay_code }}, {{ $connector->city_code }})</dd></div>
            <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 sm:col-span-2"><dt class="text-xs font-semibold uppercase text-gray-500">Verification Notes</dt><dd class="mt-1 text-sm leading-6 text-gray-700">{{ $connector->verification_notes ?: 'None' }}</dd></div>
        </dl>
    </section>

    <aside class="space-y-4">
        <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h2 class="font-bold text-gray-900">Moderation Actions</h2>
            <p class="mt-1 text-sm text-gray-500">Use a confirmation flow before changing connector access.</p>
            <div class="mt-4 grid gap-2">
                <button type="button" @click="openModal('approve')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                    Approve
                </button>
                <button type="button" @click="openModal('reject')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    Reject
                </button>
                <button type="button" @click="openModal('suspend')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Suspend
                </button>
            </div>
        </section>

        <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs">
            <h2 class="font-bold text-gray-900">Review History</h2>
            <div class="mt-3 space-y-3">
                @forelse($connector->reviews as $review)
                    <div class="rounded-2xl border border-gray-100 px-3 py-2 text-sm">
                        <p class="font-semibold text-gray-900">{{ $review->from_status ?: 'new' }} to {{ $review->to_status }}</p>
                        <p class="mt-1 text-xs leading-5 text-gray-500">{{ $review->reason }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No review history yet.</p>
                @endforelse
            </div>
        </section>
    </aside>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" @keydown.escape.window="closeModal()">
        <div class="absolute inset-0 bg-gray-900/55" @click="closeModal()"></div>
        <form method="POST" :action="actionUrl" class="relative w-full max-w-xl rounded-2xl border border-brand-100 bg-white p-6 shadow-2xl">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em]" :class="toneText" x-text="actionLabel"></p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900" x-text="modalTitle"></h3>
                </div>
                <button type="button" @click="closeModal()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="mt-3 text-sm leading-6 text-gray-600" x-text="modalCopy"></p>

            <div class="mt-5 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">{{ $connector->name }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $connector->organization_email ?: $connector->primaryRepresentative?->email }}</p>
            </div>

            <div class="mt-5" x-show="action !== 'approve'">
                <label class="block text-sm font-semibold text-gray-700">Reason</label>
                <select x-model="reason" class="mt-1 w-full rounded-xl border-gray-300 text-sm" :required="action !== 'approve'">
                    <template x-for="item in currentReasons" :key="item">
                        <option :value="item" x-text="item"></option>
                    </template>
                </select>
                <textarea x-show="reason === 'Other'" name="reason" rows="4" class="mt-3 w-full rounded-xl border-gray-300 text-sm" placeholder="Write a specific reason..." :required="reason === 'Other'"></textarea>
                <input x-show="reason !== 'Other'" type="hidden" name="reason" :value="reason">
            </div>

            <div class="mt-5" x-show="action === 'approve'">
                <label class="block text-sm font-semibold text-gray-700">Approval note</label>
                <select name="reason" class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                    <option value="Organization information verified.">Organization information verified.</option>
                    <option value="Representative and connector details approved.">Representative and connector details approved.</option>
                    <option value="Verification requirements met.">Verification requirements met.</option>
                </select>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="closeModal()" class="rounded-lg border border-brand-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-brand-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white" :class="submitClass" x-text="confirmLabel"></button>
            </div>
        </form>
    </div>
</div>

<script>
    function connectorReviewPage() {
        return {
            modalOpen: false,
            action: 'approve',
            reason: '',
            urls: {
                approve: @js(route('admin.connectors.approve', $connector)),
                reject: @js(route('admin.connectors.reject', $connector)),
                suspend: @js(route('admin.connectors.suspend', $connector)),
            },
            rejectionReasons: ['Incomplete organization information', 'Invalid organization details', 'Duplicate organization', 'Verification requirements not met', 'Policy concerns', 'Other'],
            suspensionReasons: ['Policy concerns', 'Misuse of connector privileges', 'Verification requirements no longer met', 'Reported safety concern', 'Other'],
            openModal(action) {
                this.action = action;
                this.reason = action === 'suspend' ? this.suspensionReasons[0] : this.rejectionReasons[0];
                this.modalOpen = true;
            },
            closeModal() {
                this.modalOpen = false;
            },
            get actionUrl() {
                return this.urls[this.action];
            },
            get currentReasons() {
                return this.action === 'suspend' ? this.suspensionReasons : this.rejectionReasons;
            },
            get actionLabel() {
                return { approve: 'Approval', reject: 'Rejection', suspend: 'Suspension' }[this.action];
            },
            get modalTitle() {
                return { approve: 'Approve connector?', reject: 'Reject connector application?', suspend: 'Suspend connector?' }[this.action];
            },
            get modalCopy() {
                return {
                    approve: 'This verifies the connector and activates the primary representative membership. Existing learner access remains unchanged.',
                    reject: 'This closes the current application and notifies the representative with the selected reason.',
                    suspend: 'This keeps the connector record but removes active dashboard access until reviewed again.',
                }[this.action];
            },
            get confirmLabel() {
                return { approve: 'Approve Connector', reject: 'Reject Connector', suspend: 'Suspend Connector' }[this.action];
            },
            get toneText() {
                return this.action === 'approve' ? 'text-emerald-700' : (this.action === 'reject' ? 'text-rose-700' : 'text-amber-700');
            },
            get submitClass() {
                return this.action === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : (this.action === 'reject' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700');
            },
        };
    }
</script>
@endsection
