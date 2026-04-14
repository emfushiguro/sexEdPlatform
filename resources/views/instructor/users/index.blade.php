@extends('layouts.instructor-app')

@section('title', 'Manage Learners')

@php
    $learnersTable = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->full_name ?: $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'category' => $user->learner_category_label,
            'status' => ucfirst((string) ($user->status ?? 'active')),
            'modules_enrolled' => (int) $user->instructor_modules_enrolled_count,
            'show_url' => route('instructor.users.show', $user),
            'chat_url' => route('chat.page', [
                'target_user_id' => $user->id,
                'conversation_type' => 'direct',
            ]),
            'archive_url' => route('instructor.users.archive', $user),
            'remove_url' => route('instructor.users.remove', $user),
        ];
    })->values();

    $categoryOptions = collect($learnersTable)->pluck('category')->filter()->unique()->values();
@endphp

@section('content')
<div class="space-y-5"
     x-data="{
        rows: @js($learnersTable),
        categoryOptions: @js($categoryOptions),
        q: '',
        categoryFilter: '',
        statusFilter: '',
        page: 1,
        perPage: 10,
        confirmModalOpen: false,
        confirmMode: 'archive',
        confirmRow: null,
        get filteredRows() {
            return this.rows.filter((row) => {
                const matchesSearch = !this.q
                    || row.name.toLowerCase().includes(this.q.toLowerCase())
                    || row.email.toLowerCase().includes(this.q.toLowerCase());
                const matchesCategory = !this.categoryFilter || row.category === this.categoryFilter;
                const matchesStatus = !this.statusFilter || row.status.toLowerCase() === this.statusFilter.toLowerCase();
                return matchesSearch && matchesCategory && matchesStatus;
            });
        },
        get paginatedRows() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredRows.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
        },
        resetPage() {
            this.page = 1;
        },
        openConfirm(mode, row) {
            this.confirmMode = mode;
            this.confirmRow = row;
            this.confirmModalOpen = true;
        },
        closeConfirm() {
            this.confirmModalOpen = false;
            this.confirmRow = null;
        },
        submitConfirm() {
            if (!this.confirmRow) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.confirmMode === 'archive' ? this.confirmRow.archive_url : this.confirmRow.remove_url;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name=csrf-token]').content;

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = this.confirmMode === 'archive' ? 'PATCH' : 'DELETE';

            form.appendChild(csrf);
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
     }">
    <div class="sr-only">
        @foreach($learnersTable as $row)
            <span>Learner Category {{ $row['category'] }}</span>
        @endforeach
    </div>

    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manage Learners</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Monitor learners enrolled in your modules with quick filters and actions.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
        <div class="relative lg:col-span-2">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text"
                   x-model.debounce.250ms="q"
                   @input="resetPage()"
                   placeholder="Search learner name or email..."
                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
        </div>

        <select x-model="categoryFilter" @change="resetPage()"
                class="px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
            <option value="">All Categories</option>
            <template x-for="category in categoryOptions" :key="category">
                <option :value="category" x-text="category"></option>
            </template>
        </select>

        <select x-model="statusFilter" @change="resetPage()"
                class="px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-400/50 focus:border-purple-400 transition-colors">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm table-standard-numbering">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Learner</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Learner Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Account Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Modules Enrolled</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-if="paginatedRows.length === 0">
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                No enrolled learners matched your filters.
                            </td>
                        </tr>
                    </template>

                    <template x-for="row in paginatedRows" :key="row.id">
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <template x-if="row.avatar_url">
                                        <img :src="row.avatar_url" alt="Learner avatar" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                    </template>
                                    <template x-if="!row.avatar_url">
                                        <div class="w-9 h-9 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold" x-text="row.name.charAt(0)"></div>
                                    </template>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="row.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="row.email"></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300" x-text="row.category"></span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-green-100 text-green-700': row.status.toLowerCase() === 'active',
                                          'bg-gray-100 text-gray-700': row.status.toLowerCase() === 'inactive',
                                          'bg-red-100 text-red-700': row.status.toLowerCase() === 'suspended',
                                          'bg-amber-100 text-amber-700': row.status.toLowerCase() === 'archived'
                                      }"
                                      x-text="row.status"></span>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium" x-text="row.modules_enrolled"></td>

                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a :href="row.chat_url"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-brand-700 hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors action-icon-standard"
                                       title="Message learner">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-8 8 3.7-3H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </a>

                                    <a :href="row.show_url"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors action-icon-standard"
                                       title="View learner details">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    <button type="button"
                                            @click="openConfirm('archive', row)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors action-icon-standard"
                                            title="Archive learner from active roster">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-1 12a2 2 0 01-2 2H7a2 2 0 01-2-2L4 7m3-3h10l1 3H6l1-3z"/>
                                        </svg>
                                    </button>

                                    <button type="button"
                                            @click="openConfirm('remove', row)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors action-icon-standard"
                                            title="Remove learner from your modules">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3">
            <p class="text-xs text-gray-500" x-text="`Showing ${filteredRows.length === 0 ? 0 : ((page - 1) * perPage + 1)}-${Math.min(page * perPage, filteredRows.length)} of ${filteredRows.length}`"></p>
            <div class="flex items-center gap-1.5">
                <button @click="page = Math.max(1, page - 1)"
                        :disabled="page === 1"
                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-for="n in totalPages" :key="n">
                    <button @click="page = n"
                            class="w-7 h-7 text-xs font-semibold rounded-lg border transition-colors"
                            :class="page === n ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-100'"
                            :style="page === n ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1);' : ''"
                            x-text="n"></button>
                </template>
                <button @click="page = Math.min(totalPages, page + 1)"
                        :disabled="page === totalPages"
                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50" @click="closeConfirm()"></div>
    <div id="users-delete-confirm-modal" x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-100 dark:border-gray-700" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="confirmMode === 'archive' ? 'Archive Learner' : 'Remove Learner'"></h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="confirmMode === 'archive'
                ? 'This will archive active/pending enrollments for this learner in your modules.'
                : 'This will remove the learner from your module roster and delete related progress for your modules.'"></p>
            <p class="mt-2 text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="confirmRow ? confirmRow.name : ''"></p>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" data-delete-confirm-cancel @click="closeConfirm()" class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                <button type="button" data-delete-confirm-submit @click="submitConfirm()" class="px-4 py-2 text-sm font-semibold rounded-lg text-white" :class="confirmMode === 'archive' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-red-600 hover:bg-red-700'" x-text="confirmMode === 'archive' ? 'Archive' : 'Remove'"></button>
            </div>
        </div>
    </div>
</div>
@endsection
