@extends('layouts.admin')

@section('title', 'Connectors')
@section('page-title', 'Connectors')

@php
    $categories = config('connector_permissions.categories', []);
    $connectorRows = $connectors->values()->map(function ($connector) use ($categories) {
        $representative = $connector->primaryRepresentative;
        $category = (string) $connector->category;
        $status = (string) $connector->status;

        return [
            'id' => $connector->id,
            'name' => $connector->name,
            'email' => $connector->organization_email ?: 'No organization email',
            'representative' => $representative?->name ?? 'Unassigned',
            'representative_email' => $representative?->email ?? 'No email',
            'category' => $category,
            'category_label' => $categories[$category] ?? str($category)->headline()->toString(),
            'status' => $status,
            'members' => (int) $connector->memberships_count,
            'invitations' => (int) $connector->invitations_count,
            'submitted_at' => $connector->created_at?->format('M d, Y') ?? 'Unknown date',
            'submitted_at_value' => $connector->created_at?->format('Y-m-d') ?? null,
            'show_url' => route('admin.connectors.show', $connector),
            'approve_url' => route('admin.connectors.approve', $connector),
            'reject_url' => route('admin.connectors.reject', $connector),
            'suspend_url' => route('admin.connectors.suspend', $connector),
            'search_blob' => strtolower(implode(' ', array_filter([
                $connector->name,
                $connector->organization_email,
                $representative?->name,
                $representative?->email,
                $category,
                $categories[$category] ?? null,
                $status,
                $connector->city_code,
                $connector->barangay_code,
            ]))),
        ];
    });

    $statCards = [
        ['label' => 'Total Connectors', 'value' => $counts['total'] ?? 0, 'icon' => 'grid', 'tone' => 'brand'],
        ['label' => 'Pending Applications', 'value' => $counts['pending'] ?? 0, 'icon' => 'clock', 'tone' => 'amber'],
        ['label' => 'Verified Connectors', 'value' => $counts['verified'] ?? 0, 'icon' => 'check', 'tone' => 'emerald'],
        ['label' => 'Suspended Connectors', 'value' => $counts['suspended'] ?? 0, 'icon' => 'pause', 'tone' => 'rose'],
    ];
@endphp

@section('content')
<div x-data="connectorManagementPage({
        connectors: @js($connectorRows),
        csrf: @js(csrf_token()),
    })"
    class="space-y-8">

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($statCards as $card)
            @php
                $tone = $card['tone'];
                $cardClass = match($tone) {
                    'amber' => 'border-amber-200/80 from-amber-50 to-white text-amber-700',
                    'emerald' => 'border-emerald-200/80 from-emerald-50 to-white text-emerald-700',
                    'rose' => 'border-rose-200/80 from-rose-50 to-white text-rose-700',
                    default => 'border-brand-200/80 from-brand-50 to-white text-brand-700',
                };
                $iconClass = match($tone) {
                    'amber' => 'bg-amber-100 text-amber-700',
                    'emerald' => 'bg-emerald-100 text-emerald-700',
                    'rose' => 'bg-rose-100 text-rose-700',
                    default => 'bg-brand-100 text-brand-700',
                };
            @endphp
            <article class="rounded-[24px] border bg-gradient-to-br p-6 shadow-theme-xs {{ $cardClass }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em]">{{ $card['label'] }}</p>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full {{ $iconClass }}">
                        @if($card['icon'] === 'clock')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @elseif($card['icon'] === 'check')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @elseif($card['icon'] === 'pause')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 9v6m4-6v6m7-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Zm9 0a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2V6ZM4 15a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3Zm9 0a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2v-3Z"/></svg>
                        @endif
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">{{ number_format((int) $card['value']) }}</p>
            </article>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.16),_transparent_34%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Moderation</p>
                    <h2 class="mt-2 text-xl font-bold text-gray-900">Connector Registrations</h2>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" data-testid="admin-table-filter-bar">
                    <label class="block xl:col-span-2">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                        <input x-model.debounce.150ms="filters.search" @input="page = 1" type="text" placeholder="Name, representative, email..."
                            class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                        <select x-model="filters.status" @change="page = 1" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span>
                        <select x-model="filters.category" @change="page = 1" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Members</span>
                        <select x-model="filters.memberBand" @change="page = 1" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">Any count</option>
                            <option value="0">No members</option>
                            <option value="1-5">1-5</option>
                            <option value="6+">6+</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
            <p class="text-sm text-gray-500"><span class="font-semibold text-gray-800" x-text="filteredConnectors.length"></span> connectors shown</p>
            <button type="button" @click="resetFilters()" class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                Reset Filters
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-brand-50/45">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No. #</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Connector</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Representative</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Category</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Members</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Submitted</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="(connector, index) in paginatedConnectors" :key="connector.id">
                        <tr class="transition hover:bg-brand-50/55">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="rowNumber(index)"></td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900" x-text="connector.name"></p>
                                <p class="text-xs text-gray-500" x-text="connector.email"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-800" x-text="connector.representative"></p>
                                <p class="text-xs text-gray-500" x-text="connector.representative_email"></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="connector.category_label"></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="font-semibold text-gray-900" x-text="connector.members"></span>
                                <span class="text-gray-400"> / </span>
                                <span x-text="connector.invitations"></span>
                                <span class="text-xs text-gray-400">invites</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize" :class="statusClass(connector.status)" x-text="connector.status"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="connector.submitted_at"></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="connector.show_url" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-brand-50/60 text-brand-700 transition hover:bg-brand-100" title="View connector">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12Z"/></svg>
                                    </a>
                                    <button type="button" x-show="connector.status !== 'verified'" @click="openAction('approve', connector)" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Approve connector">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                    </button>
                                    <button type="button" x-show="connector.status === 'pending'" @click="openAction('reject', connector)" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Reject connector">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                    <button type="button" x-show="connector.status !== 'suspended'" @click="openAction('suspend', connector)" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100" title="Suspend connector">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredConnectors.length === 0" x-cloak>
                        <td colspan="8" class="px-6 py-14 text-center text-sm text-gray-500">No connectors match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button type="button" @click="prevPage()" :disabled="page === 1" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
            <span class="text-sm text-gray-600">Page <span class="font-semibold" x-text="safePage"></span> of <span class="font-semibold" x-text="totalPages"></span></span>
            <button type="button" @click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
        </div>
    </section>

    <div x-show="actionOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" @keydown.escape.window="closeAction()">
        <div class="absolute inset-0 bg-gray-900/50" @click="closeAction()"></div>
        <form method="POST" :action="selectedActionUrl" class="relative w-full max-w-lg rounded-2xl border border-brand-100 bg-white p-6 shadow-2xl">
            @csrf
            <h3 class="text-lg font-bold text-gray-900" x-text="actionTitle"></h3>
            <p class="mt-2 text-sm text-gray-600">
                <span x-text="actionDescription"></span>
                <span class="font-semibold" x-text="selectedConnector ? selectedConnector.name : ''"></span>.
            </p>
            <template x-if="selectedAction === 'approve'">
                <input type="hidden" name="reason" value="Connector approved from the management table.">
            </template>
            <div x-show="selectedAction !== 'approve'" class="mt-4">
                <label class="block text-sm font-semibold text-gray-700">Reason</label>
                <select x-model="selectedReason" class="mt-1 w-full rounded-xl border-gray-300 text-sm" required>
                    <template x-for="reason in currentReasons" :key="reason">
                        <option :value="reason" x-text="reason"></option>
                    </template>
                </select>
                <textarea x-show="selectedReason === 'Other'" name="reason" rows="3" class="mt-3 w-full rounded-xl border-gray-300 text-sm" placeholder="Write the reason..." :required="selectedReason === 'Other'"></textarea>
                <input x-show="selectedReason !== 'Other'" type="hidden" name="reason" :value="selectedReason">
            </div>
            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="closeAction()" class="rounded-lg border border-brand-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-brand-50">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white" :class="selectedAction === 'reject' ? 'bg-rose-600 hover:bg-rose-700' : (selectedAction === 'suspend' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700')">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    function connectorManagementPage(config) {
        return {
            connectors: config.connectors || [],
            filters: { search: '', status: '', category: '', memberBand: '' },
            page: 1,
            perPage: 10,
            actionOpen: false,
            selectedAction: 'approve',
            selectedConnector: null,
            selectedReason: '',
            rejectionReasons: ['Incomplete organization information', 'Invalid organization details', 'Duplicate organization', 'Verification requirements not met', 'Policy concerns', 'Other'],
            suspensionReasons: ['Policy concerns', 'Misuse of connector privileges', 'Verification requirements no longer met', 'Reported safety concern', 'Other'],
            get filteredConnectors() {
                return this.connectors.filter((connector) => {
                    const search = this.filters.search.trim().toLowerCase();
                    const matchesSearch = !search || connector.search_blob.includes(search);
                    const matchesStatus = !this.filters.status || connector.status === this.filters.status;
                    const matchesCategory = !this.filters.category || connector.category === this.filters.category;
                    const matchesMembers = !this.filters.memberBand
                        || (this.filters.memberBand === '0' && connector.members === 0)
                        || (this.filters.memberBand === '1-5' && connector.members >= 1 && connector.members <= 5)
                        || (this.filters.memberBand === '6+' && connector.members >= 6);

                    return matchesSearch && matchesStatus && matchesCategory && matchesMembers;
                });
            },
            get totalPages() {
                const pages = Math.ceil(this.filteredConnectors.length / this.perPage);
                return pages > 0 ? pages : 1;
            },
            get safePage() {
                return Math.min(this.page, this.totalPages);
            },
            get paginatedConnectors() {
                return this.filteredConnectors.slice((this.safePage - 1) * this.perPage, this.safePage * this.perPage);
            },
            get selectedActionUrl() {
                if (!this.selectedConnector) return '#';
                return this.selectedConnector[`${this.selectedAction}_url`];
            },
            get actionTitle() {
                return { approve: 'Approve Connector?', reject: 'Reject Connector?', suspend: 'Suspend Connector?' }[this.selectedAction];
            },
            get actionDescription() {
                return {
                    approve: 'This will verify and activate ',
                    reject: 'This will reject the current application for ',
                    suspend: 'This will suspend dashboard access for ',
                }[this.selectedAction];
            },
            get currentReasons() {
                return this.selectedAction === 'suspend' ? this.suspensionReasons : this.rejectionReasons;
            },
            resetFilters() {
                this.filters = { search: '', status: '', category: '', memberBand: '' };
                this.page = 1;
            },
            rowNumber(index) {
                return ((this.safePage - 1) * this.perPage) + index + 1;
            },
            prevPage() {
                if (this.page > 1) this.page -= 1;
            },
            nextPage() {
                if (this.page < this.totalPages) this.page += 1;
            },
            openAction(action, connector) {
                this.selectedAction = action;
                this.selectedConnector = connector;
                this.selectedReason = action === 'suspend' ? this.suspensionReasons[0] : this.rejectionReasons[0];
                this.actionOpen = true;
            },
            closeAction() {
                this.actionOpen = false;
                this.selectedConnector = null;
            },
            statusClass(status) {
                return {
                    pending: 'bg-amber-100 text-amber-700',
                    verified: 'bg-emerald-100 text-emerald-700',
                    rejected: 'bg-rose-100 text-rose-700',
                    suspended: 'bg-gray-100 text-gray-700',
                }[status] || 'bg-gray-100 text-gray-700';
            },
        };
    }
</script>
@endsection
