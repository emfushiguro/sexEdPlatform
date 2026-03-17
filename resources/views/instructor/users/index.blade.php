@extends('layouts.instructor-app')

@php
    $usersForTable = $users->map(function ($u) {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role ?? 'learner',
            'status' => $u->status ?? 'active',
            'created_at' => $u->created_at?->format('M d, Y') ?? '',
            'deleted_at' => $u->deleted_at ? $u->deleted_at->format('M d, Y') : null,
            'module_enrollments_count' => $u->module_enrollments_count,
            'enrollments' => $u->moduleEnrollments->map(function ($e) {
                return [
                    'module_title' => $e->module?->title ?? 'Unknown Module',
                    'status' => $e->status ?? 'pending',
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

@push('scripts')
<script>
function userTable() {
    return {
        search: '',
        roleFilter: '',
        statusFilter: '',
        currentPage: 1,
        perPage: 15,
        expandedRow: null,
        deleteModal: false,
        deleteUserId: null,
        deleteUserName: '',
        users: @js($usersForTable),
        toggleRow(id) {
            this.expandedRow = this.expandedRow === id ? null : id;
        },
        confirmDelete(id, name) {
            this.deleteUserId = id;
            this.deleteUserName = name;
            this.deleteModal = true;
        },
        async deleteUser() {
            const res = await fetch(`/instructor/users/${this.deleteUserId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });
            if (res.ok) {
                const user = this.users.find(u => u.id === this.deleteUserId);
                if (user) user.deleted_at = 'Deleted';
                this.deleteModal = false;
                this.deleteUserId = null;
                this.resetPage();
            }
        },
        async restoreUser(id) {
            const res = await fetch(`/instructor/users/${id}/restore`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
            });
            if (res.ok) {
                const user = this.users.find(u => u.id === id);
                if (user) user.deleted_at = null;
            }
        },
        get filtered() {
            const self = this;
            return this.users.filter(u => {
                const matchSearch = !self.search ||
                    u.name.toLowerCase().includes(self.search.toLowerCase()) ||
                    u.email.toLowerCase().includes(self.search.toLowerCase());
                const matchRole = !self.roleFilter || u.role === self.roleFilter;
                const matchStatus = !self.statusFilter ||
                    (self.statusFilter === 'deleted'
                        ? !!u.deleted_at
                        : (u.status === self.statusFilter && !u.deleted_at));
                return matchSearch && matchRole && matchStatus;
            });
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },
        resetPage() { this.currentPage = 1; },
        initials(name) {
            return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('');
        },
        roleBadgeClass(role) {
            const map = {
                'admin':        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                'instructor':   'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                'learner':      'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'counselor':    'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                'clinic':       'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                'organization': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
            };
            return map[role] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
        },
        statusBadgeClass(status) {
            const map = {
                'active':    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'inactive':  'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                'suspended': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            };
            return map[status] ?? 'bg-gray-100 text-gray-500';
        },
    };
}
</script>
@endpush

@section('content')
<div x-data="userTable()" class="space-y-5">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manage Users</h1>
            <p class="text-sm text-gray-400 mt-0.5">All registered users on the platform</p>
        </div>
        <a href="{{ route('instructor.users.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text"
                   x-model.debounce.300ms="search"
                   @input="resetPage()"
                   placeholder="Search by name or email…"
                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
        </div>
        <select x-model="roleFilter" @change="resetPage()"
                class="px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
            <option value="">All Roles</option>
            <option value="learner">Learner</option>
            <option value="counselor">Counselor</option>
            <option value="clinic">Clinic</option>
            <option value="organization">Organization</option>
            <option value="admin">Admin</option>
            <option value="instructor">Instructor</option>
        </select>
        <select x-model="statusFilter" @change="resetPage()"
                class="px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="deleted">Deleted</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                {{-- Header --}}
                <thead>
                    <tr class="bg-gray-50/60 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">User</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Role</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Enrolled</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Joined</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>

                {{-- Empty state --}}
                <template x-if="paginated.length === 0">
                    <tbody>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                </svg>
                                <p class="text-sm text-gray-400">No users found</p>
                            </td>
                        </tr>
                    </tbody>
                </template>

                {{-- One tbody per user allows two <tr> per iteration (main + expandable) --}}
                <template x-for="user in paginated" :key="user.id">
                    <tbody class="border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        {{-- Main row --}}
                        <tr @click="toggleRow(user.id)"
                            :class="user.deleted_at ? 'opacity-60' : ''"
                            class="cursor-pointer hover:bg-purple-50/30 dark:hover:bg-purple-900/10 transition-colors">
                            {{-- Avatar + name --}}
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                                         style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"
                                         x-text="initials(user.name)"></div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="user.name"></p>
                                        <p class="text-xs text-gray-400 truncate" x-text="user.email"></p>
                                    </div>
                                </div>
                            </td>
                            {{-- Role --}}
                            <td class="px-5 py-3.5">
                                <span :class="roleBadgeClass(user.role)"
                                      class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                      x-text="user.role"></span>
                            </td>
                            {{-- Status --}}
                            <td class="px-5 py-3.5">
                                <template x-if="user.deleted_at">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Deleted</span>
                                </template>
                                <template x-if="!user.deleted_at">
                                    <span :class="statusBadgeClass(user.status)"
                                          class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full capitalize"
                                          x-text="user.status"></span>
                                </template>
                            </td>
                            {{-- Enrolled count --}}
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1 text-sm">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="user.module_enrollments_count"></span>
                                </div>
                            </td>
                            {{-- Joined --}}
                            <td class="px-5 py-3.5 text-sm text-gray-400" x-text="user.created_at"></td>
                            {{-- Actions --}}
                            <td class="px-5 py-3.5 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    <template x-if="user.deleted_at">
                                        <button @click="restoreUser(user.id)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Restore
                                        </button>
                                    </template>
                                    <template x-if="!user.deleted_at">
                                        <div class="flex items-center gap-1.5">
                                            <a :href="`{{ url('instructor/users') }}/${user.id}`"
                                               title="View"
                                               class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </a>
                                            <a :href="`{{ url('instructor/users') }}/${user.id}/edit`"
                                               title="Edit"
                                               class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <button @click="confirmDelete(user.id, user.name)"
                                                    title="Delete"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        {{-- Expandable detail row --}}
                        <tr x-show="expandedRow === user.id"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="bg-purple-50/30 dark:bg-purple-900/5">
                            <td colspan="6" class="px-5 pb-4 pt-0">
                                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4 mt-2">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">Enrolled Modules</p>
                                    <template x-if="user.enrollments.length === 0">
                                        <p class="text-xs text-gray-400">No module enrollments yet.</p>
                                    </template>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(enrollment, idx) in user.enrollments" :key="idx">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                                  :class="enrollment.status === 'approved'
                                                      ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/40'
                                                      : enrollment.status === 'rejected'
                                                      ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800/40'
                                                      : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/40'">
                                                <span x-text="enrollment.module_title"></span>
                                                <span class="opacity-60 capitalize" x-text="'· ' + enrollment.status"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-3.5 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/40 dark:bg-gray-800/40">
            <p class="text-xs text-gray-400">
                Showing
                <span class="font-semibold text-gray-600 dark:text-gray-300"
                      x-text="filtered.length === 0 ? 0 : ((currentPage-1)*perPage+1)"></span>–<span
                      class="font-semibold text-gray-600 dark:text-gray-300"
                      x-text="Math.min(currentPage*perPage, filtered.length)"></span>
                of <span class="font-semibold text-gray-600 dark:text-gray-300" x-text="filtered.length"></span> users
            </p>
            <div class="flex items-center gap-1.5">
                <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-for="page in totalPages" :key="page">
                    <button @click="currentPage = page"
                            :class="currentPage === page ? 'text-white border-transparent' : 'text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            :style="currentPage === page ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1);' : ''"
                            class="w-7 h-7 flex items-center justify-center rounded-lg border text-xs font-semibold transition-colors"
                            x-text="page"></button>
                </template>
                <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-gray-900/50 backdrop-blur-sm"
         @click.self="deleteModal = false"
         @keydown.escape.window="deleteModal = false">
        <div x-show="deleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-red-100 dark:bg-red-900/30">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Delete User</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Are you sure you want to delete <strong x-text="deleteUserName" class="text-gray-900 dark:text-white"></strong>?
                        They will be soft-deleted and can be restored later.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="deleteModal = false"
                        class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors">
                    Cancel
                </button>
                <button @click="deleteUser()"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors">
                    Delete User
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
