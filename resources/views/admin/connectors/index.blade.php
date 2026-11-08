@extends('layouts.admin')

@section('title', 'Connectors')
@section('page-title', 'Connectors')

@php
    $categories = config('connector_permissions.categories', []);
    $verificationLabels = [
        'under_review' => 'Under review',
        'resubmission_required' => 'Resubmission required',
        'verified' => 'Verified evidence',
        'legacy_verified' => 'Legacy verified',
        'reverification_required' => 'Re-verification required',
        'rejected' => 'Rejected evidence',
    ];
    $connectorRows = $connectors->values()->map(function ($connector) use ($categories, $verificationLabels) {
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
            'verification_status' => (string) ($connector->verification_status ?: ''),
            'verification_status_label' => $verificationLabels[$connector->verification_status] ?? 'Not recorded',
            'members' => (int) $connector->memberships_count,
            'invitations' => (int) $connector->invitations_count,
            'submitted_at' => $connector->created_at?->format('M d, Y') ?? 'Unknown date',
            'submitted_at_value' => $connector->created_at?->format('Y-m-d') ?? null,
            'show_url' => route('admin.connectors.show', $connector),
            'logo_url' => $connector->logo_path
                ? Storage::disk(config('connector_verification.logo_disk', 'public'))->url($connector->logo_path) : null,
            'representative_avatar_url' => $representative?->learnerProfile?->avatar_path
                ? Storage::disk('public')->url($representative->learnerProfile->avatar_path) : null,
            'search_blob' => strtolower(implode(' ', array_filter([
                $connector->name,
                $connector->organization_email,
                $representative?->name,
                $representative?->email,
                $category,
                $categories[$category] ?? null,
                $status,
                $verificationLabels[$connector->verification_status] ?? null,
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
<div class="mb-4 flex justify-end"><a href="{{ route('admin.connectors.module-access.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-brand-700">Manage module access</a></div>
<div x-data="connectorManagementPage({
        connectors: @js($connectorRows),
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
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @elseif($card['icon'] === 'check')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @elseif($card['icon'] === 'pause')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 9v6m4-6v6m7-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Zm9 0a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2V6ZM4 15a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3Zm9 0a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2v-3Z"/></svg>
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
                            class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border shadow-sm outline-none rounded-2xl border-brand-100 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                        <select x-model="filters.status" @change="page = 1" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border shadow-sm outline-none rounded-2xl border-brand-100 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span>
                        <select x-model="filters.category" @change="page = 1" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border shadow-sm outline-none rounded-2xl border-brand-100 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Members</span>
                        <select x-model="filters.memberBand" @change="page = 1" class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border shadow-sm outline-none rounded-2xl border-brand-100 focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">Any count</option>
                            <option value="0">No members</option>
                            <option value="1-5">1-5</option>
                            <option value="6+">6+</option>
                        </select>
                    </label>
                </div>
            </div>
            <form method="GET" class="grid gap-3 p-4 mt-5 border rounded-2xl border-brand-100 bg-white/80 sm:grid-cols-2 xl:grid-cols-4" data-testid="verification-filter-form">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Operational status</span>
                    <select name="status" class="w-full rounded-xl border-brand-100 bg-white px-3 py-2.5 text-sm text-gray-900">
                        <option value="">All operational statuses</option>
                        @foreach(['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'withdrawn' => 'Withdrawn'] as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Verification state</span>
                    <select name="verification_status" class="w-full rounded-xl border-brand-100 bg-white px-3 py-2.5 text-sm text-gray-900">
                        <option value="">All verification states</option>
                        @foreach($verificationLabels as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['verification_status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Organization type</span>
                    <select name="organization_type" class="w-full rounded-xl border-brand-100 bg-white px-3 py-2.5 text-sm text-gray-900">
                        <option value="">All organization types</option>
                        @foreach($organizationTypes as $key => $definition)
                            <option value="{{ $key }}" @selected(($filters['organization_type'] ?? '') === $key)>{{ $definition['label'] ?? str($key)->headline() }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end">
                    <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Apply verification filters</button>
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Server results: {{ $connectors->count() }}</p>
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
                <tbody class="bg-white divide-y divide-gray-100">
                    <template x-for="(connector, index) in paginatedConnectors" :key="connector.id">
                        <tr class="transition hover:bg-brand-50/55">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="rowNumber(index)"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-sm font-bold text-brand-700">
                                        <span aria-hidden="true" x-text="initials(connector.name)"></span>
                                        <template x-if="connector.logo_url">
                                            <img :src="connector.logo_url" :alt="connector.name + ' photo'" loading="lazy" class="absolute inset-0 h-full w-full object-cover" x-on:error="connector.logo_url = null">
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900" x-text="connector.name"></p>
                                        <p class="text-xs text-gray-500" x-text="connector.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-50 text-sm font-bold text-gray-700">
                                        <span aria-hidden="true" x-text="initials(connector.representative)"></span>
                                        <template x-if="connector.representative_avatar_url">
                                            <img :src="connector.representative_avatar_url" :alt="connector.representative + ' photo'" loading="lazy" class="absolute inset-0 h-full w-full object-cover" x-on:error="connector.representative_avatar_url = null">
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900" x-text="connector.representative"></p>
                                        <p class="text-xs text-gray-500" x-text="connector.representative_email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="connector.category_label"></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="font-semibold text-gray-900" x-text="connector.members"></span>
                                <span class="text-gray-400"> / </span>
                                <span x-text="connector.invitations"></span>
                                <span class="text-xs text-gray-400">invites</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="inline-flex px-3 py-1 text-xs font-bold capitalize rounded-full" :class="statusClass(connector.status)" x-text="connector.status"></span>
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold text-blue-700 rounded-full bg-blue-50" x-text="connector.verification_status_label"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="connector.submitted_at"></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="connector.show_url" class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-brand-50/60 text-brand-700 hover:bg-brand-100" title="View connector" :aria-label="'View connector: ' + connector.name">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12Z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredConnectors.length === 0" x-cloak>
                        <td colspan="8" class="px-6 text-sm text-center text-gray-500 py-14">No connectors match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100">
            <button type="button" @click="prevPage()" :disabled="page === 1" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
            <span class="text-sm text-gray-600">Page <span class="font-semibold" x-text="safePage"></span> of <span class="font-semibold" x-text="totalPages"></span></span>
            <button type="button" @click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
        </div>
    </section>


</div>

<script>
    function connectorManagementPage(config) {
        return {
            connectors: config.connectors || [],
            filters: { search: '', status: '', category: '', memberBand: '' },
            page: 1,
            perPage: 10,
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
            initials(name) {
                return String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('');
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
