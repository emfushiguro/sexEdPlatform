@extends('layouts.connector-app')

@section('title', 'Members')
@section('page-title', 'Members')

@php
    $roleOptions = $connector->roles->map(fn ($role) => [
        'id' => $role->id,
        'name' => $role->name,
        'is_owner' => (bool) $role->is_owner,
    ])->values();

    $activeOwnerCount = $connector->memberships
        ->where('status', 'active')
        ->filter(fn ($item) => $item->role?->is_owner)
        ->count();

    $memberRows = $connector->memberships->values()->map(function ($membership) use ($connector, $activeOwnerCount) {
        $user = $membership->user;
        $avatarPath = $user?->learnerProfile?->avatar_path;
        $avatarUrl = $avatarPath ? asset('storage/' . ltrim($avatarPath, '/')) : null;

        return [
            'id' => $membership->id,
            'name' => $user?->name ?? 'Unknown user',
            'email' => $user?->email ?? 'No email',
            'avatar' => $avatarUrl,
            'initial' => strtoupper(mb_substr($user?->name ?? 'U', 0, 1)),
            'role_id' => $membership->connector_role_id,
            'role' => $membership->role?->name ?? 'No role',
            'is_owner' => (bool) $membership->role?->is_owner,
            'status' => $membership->status,
            'joined' => $membership->accepted_at?->format('M d, Y') ?? 'Pending',
            'joined_value' => $membership->accepted_at?->format('Y-m-d') ?? null,
            'update_url' => route('connector.members.role', [$connector, $membership]),
            'remove_url' => route('connector.members.destroy', [$connector, $membership]),
            'can_remove' => ! ($membership->role?->is_owner && $activeOwnerCount <= 1),
            'search_blob' => strtolower(implode(' ', [$user?->name, $user?->email, $membership->role?->name, $membership->status])),
        ];
    });

    $invitationRows = $connector->invitations->where('status', 'pending')->values()->map(fn ($invitation) => [
        'id' => $invitation->id,
        'email' => $invitation->email,
        'name' => $invitation->invitedUser?->name ?? $invitation->email,
        'role' => $invitation->role?->name ?? 'No role',
        'status' => $invitation->status,
        'expires' => $invitation->expires_at?->format('M d, Y') ?? 'No expiry',
        'resend_url' => route('connector.invitations.resend', [$connector, $invitation]),
        'search_blob' => strtolower(implode(' ', [$invitation->email, $invitation->invitedUser?->name, $invitation->role?->name, $invitation->status])),
    ]);

    $candidateRows = $inviteCandidates->map(fn ($user) => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'label' => $user->name . ' - ' . $user->email,
        'search_blob' => strtolower($user->name . ' ' . $user->email),
    ])->values();
@endphp

@section('content')
<div x-data="connectorMembersPage({
        members: @js($memberRows),
        invitations: @js($invitationRows),
        roles: @js($roleOptions),
        candidates: @js($candidateRows),
    })"
    class="space-y-6">
    <section class="overflow-hidden rounded-[24px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.14),_transparent_34%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Access</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Members Management</h2>
                </div>
                <button type="button" @click="openInvite()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                    Invite Member
                </button>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
                <label class="block md:col-span-2">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                    <input x-model.debounce.150ms="filters.search" @input="page = 1" placeholder="Name, email, role..." class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm shadow-sm">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Role</span>
                    <select x-model="filters.role" @change="page = 1" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm shadow-sm">
                        <option value="">All roles</option>
                        <template x-for="role in roles" :key="role.id">
                            <option :value="String(role.id)" x-text="role.name"></option>
                        </template>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                    <select x-model="filters.status" @change="page = 1" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm shadow-sm">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="removed">Removed</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-brand-50/45">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Profile</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Role</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Joined</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="member in paginatedMembers" :key="member.id">
                        <tr class="transition hover:bg-brand-50/55">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <template x-if="member.avatar"><img :src="member.avatar" alt="" class="h-10 w-10 rounded-full object-cover"></template>
                                    <template x-if="!member.avatar"><span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700" x-text="member.initial"></span></template>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900" x-text="member.name"></p>
                                        <p class="text-xs text-gray-500" x-text="member.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="member.role"></td>
                            <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold capitalize" :class="statusClass(member.status)" x-text="member.status"></span></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="member.joined"></td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="openView(member)" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100" title="View member">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12Z"/></svg>
                                    </button>
                                    <button type="button" @click="openRole(member)" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Edit role">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    </button>
                                    <button type="button" @click="openRemove(member)" :disabled="!member.can_remove" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-40" title="Remove member">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredMembers.length === 0" x-cloak><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No members match these filters.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page === 1" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 disabled:opacity-50">Previous</button>
            <span class="text-sm text-gray-600">Page <span x-text="safePage"></span> of <span x-text="totalPages"></span></span>
            <button type="button" @click="page = Math.min(totalPages, page + 1)" :disabled="page >= totalPages" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 disabled:opacity-50">Next</button>
        </div>
    </section>

    <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs">
        <h2 class="font-bold text-gray-900">Pending Invitations</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <template x-for="invitation in invitations" :key="invitation.id">
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-gray-100 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900" x-text="invitation.name"></p>
                        <p class="text-xs text-gray-500"><span x-text="invitation.email"></span> as <span x-text="invitation.role"></span></p>
                    </div>
                    <form method="POST" :action="invitation.resend_url">
                        @csrf
                        <button class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100" title="Resend invitation">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 15a7 7 0 0 0 12 3M19 9A7 7 0 0 0 7 6"/></svg>
                        </button>
                    </form>
                </div>
            </template>
            <p x-show="invitations.length === 0" class="text-sm text-gray-500">No pending invitations.</p>
        </div>
    </section>

    <div x-show="inviteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-gray-900/50" @click="inviteOpen = false"></div>
        <form method="POST" action="{{ route('connector.invitations.store', $connector) }}" class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            @csrf
            <h3 class="text-lg font-bold text-gray-900">Invite Member</h3>
            <p class="mt-1 text-sm text-gray-500">Search an existing user, assign a connector role, and send an invitation notification.</p>
            <div class="mt-5 space-y-4">
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Search user</span>
                    <input type="text" x-model.debounce.150ms="inviteSearch" placeholder="Type a name or email" class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                </label>
                <div class="max-h-48 overflow-y-auto rounded-xl border border-gray-100">
                    <template x-for="candidate in filteredCandidates" :key="candidate.id">
                        <button type="button" @click="selectCandidate(candidate)" class="flex w-full items-center justify-between px-4 py-3 text-left text-sm hover:bg-purple-50">
                            <span><span class="font-semibold text-gray-900" x-text="candidate.name"></span><span class="ml-2 text-gray-500" x-text="candidate.email"></span></span>
                            <span x-show="selectedEmail === candidate.email" class="text-xs font-bold text-purple-700">Selected</span>
                        </button>
                    </template>
                    <p x-show="filteredCandidates.length === 0" class="px-4 py-4 text-sm text-gray-500">No matching users in the current candidate list.</p>
                </div>
                <input type="hidden" name="email" :value="selectedEmail" required>
                <label class="block">
                    <span class="text-sm font-semibold text-gray-700">Role</span>
                    <select name="connector_role_id" class="mt-1 w-full rounded-xl border-gray-300 text-sm" required>
                        @foreach($connector->roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="inviteOpen = false" class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                <button type="submit" :disabled="!selectedEmail" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Send Invitation</button>
            </div>
        </form>
    </div>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-gray-900/50" @click="modalOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <template x-if="modalMode === 'view' && selectedMember">
                <div>
                    <h3 class="text-lg font-bold text-gray-900" x-text="selectedMember.name"></h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="selectedMember.email"></p>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Role</dt><dd class="font-semibold" x-text="selectedMember.role"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd class="font-semibold capitalize" x-text="selectedMember.status"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Joined</dt><dd class="font-semibold" x-text="selectedMember.joined"></dd></div>
                    </dl>
                </div>
            </template>
            <template x-if="modalMode === 'role' && selectedMember">
                <form method="POST" :action="selectedMember.update_url">
                    @csrf
                    @method('PATCH')
                    <h3 class="text-lg font-bold text-gray-900">Edit Role</h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="selectedMember.name"></p>
                    <select name="connector_role_id" class="mt-4 w-full rounded-xl border-gray-300 text-sm" required>
                        <template x-for="role in roles" :key="role.id">
                            <option :value="role.id" :selected="role.id === selectedMember.role_id" x-text="role.name"></option>
                        </template>
                    </select>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                        <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Save Role</button>
                    </div>
                </form>
            </template>
            <template x-if="modalMode === 'remove' && selectedMember">
                <form method="POST" :action="selectedMember.remove_url">
                    @csrf
                    @method('DELETE')
                    <h3 class="text-lg font-bold text-gray-900">Remove member?</h3>
                    <p class="mt-2 text-sm text-gray-600">This removes <span class="font-semibold" x-text="selectedMember.name"></span> from the connector workspace.</p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                        <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Remove</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>

<script>
    function connectorMembersPage(config) {
        return {
            members: config.members || [],
            invitations: config.invitations || [],
            roles: config.roles || [],
            candidates: config.candidates || [],
            filters: { search: '', role: '', status: '' },
            page: 1,
            perPage: 10,
            inviteOpen: false,
            inviteSearch: '',
            selectedEmail: '',
            modalOpen: false,
            modalMode: 'view',
            selectedMember: null,
            get filteredMembers() {
                return this.members.filter((member) => {
                    const search = this.filters.search.trim().toLowerCase();
                    return (!search || member.search_blob.includes(search))
                        && (!this.filters.role || String(member.role_id) === this.filters.role)
                        && (!this.filters.status || member.status === this.filters.status);
                });
            },
            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredMembers.length / this.perPage));
            },
            get safePage() {
                return Math.min(this.page, this.totalPages);
            },
            get paginatedMembers() {
                return this.filteredMembers.slice((this.safePage - 1) * this.perPage, this.safePage * this.perPage);
            },
            get filteredCandidates() {
                const search = this.inviteSearch.trim().toLowerCase();
                return this.candidates.filter((candidate) => !search || candidate.search_blob.includes(search)).slice(0, 12);
            },
            openInvite() {
                this.inviteOpen = true;
                this.inviteSearch = '';
                this.selectedEmail = '';
            },
            selectCandidate(candidate) {
                this.selectedEmail = candidate.email;
                this.inviteSearch = candidate.label;
            },
            openView(member) { this.selectedMember = member; this.modalMode = 'view'; this.modalOpen = true; },
            openRole(member) { this.selectedMember = member; this.modalMode = 'role'; this.modalOpen = true; },
            openRemove(member) { if (!member.can_remove) return; this.selectedMember = member; this.modalMode = 'remove'; this.modalOpen = true; },
            statusClass(status) {
                return { active: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700', removed: 'bg-gray-100 text-gray-600' }[status] || 'bg-gray-100 text-gray-600';
            },
        };
    }
</script>
@endsection
