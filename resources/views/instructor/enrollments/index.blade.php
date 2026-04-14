@extends($contentPanelLayout ?? 'layouts.instructor-app')

@php
    $isAdminPanel = ($isContentAdminPanel ?? false) === true;
    $ownershipRestrictionTooltip = 'Instructor-owned content is read-only in the admin panel.';
    $statusFilter = $statusFilter ?? 'all';
    $search = $search ?? '';
    $moduleFilter = $moduleFilter ?? 0;
    $statusCounts = $statusCounts ?? ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    $modulesForFilter = $modulesForFilter ?? collect();
    $rowOffset = ($enrollments->currentPage() - 1) * $enrollments->perPage();

    $statusOptions = [
        'all' => 'All statuses',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'archived' => 'Archived',
    ];
@endphp

@section('content')
<div x-data="{
    viewModalOpen: false,
    confirmModalOpen: false,
    viewPayload: null,
    confirmForm: null,
    confirmMessage: '',
    confirmButtonLabel: 'Confirm',
    openView(payload) {
        this.viewPayload = payload;
        this.viewModalOpen = true;
    },
    closeView() {
        this.viewPayload = null;
        this.viewModalOpen = false;
    },
    openConfirm(form, message, label) {
        this.confirmForm = form;
        this.confirmMessage = message;
        this.confirmButtonLabel = label;
        this.confirmModalOpen = true;
    },
    closeConfirm() {
        this.confirmForm = null;
        this.confirmMessage = '';
        this.confirmButtonLabel = 'Confirm';
        this.confirmModalOpen = false;
    },
    confirmAction() {
        if (this.confirmForm) {
            this.confirmForm.submit();
        }
    }
}" class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Enrollments</h1>
            <p class="text-sm text-gray-500">Track learner requests and enrollment records.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <a href="{{ route($contentRoutePrefix . '.enrollments.index', array_filter(array_merge(request()->query(), ['status' => 'all', 'page' => null]))) }}"
           class="rounded-2xl border bg-white px-5 py-4 shadow-sm transition hover:border-purple-200 {{ $statusFilter === 'all' ? 'border-purple-300 ring-1 ring-purple-200' : 'border-gray-200' }}">
            <p class="text-2xl font-bold text-gray-900">{{ $statusCounts['all'] ?? 0 }}</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-gray-500">All Enrollees</p>
        </a>

        <a href="{{ route($contentRoutePrefix . '.enrollments.index', array_filter(array_merge(request()->query(), ['status' => 'pending', 'page' => null]))) }}"
           class="rounded-2xl border bg-white px-5 py-4 shadow-sm transition hover:border-amber-200 {{ $statusFilter === 'pending' ? 'border-amber-300 ring-1 ring-amber-200' : 'border-gray-200' }}">
            <p class="text-2xl font-bold text-amber-700">{{ $statusCounts['pending'] ?? 0 }}</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Pending</p>
        </a>

        <a href="{{ route($contentRoutePrefix . '.enrollments.index', array_filter(array_merge(request()->query(), ['status' => 'approved', 'page' => null]))) }}"
           class="rounded-2xl border bg-white px-5 py-4 shadow-sm transition hover:border-emerald-200 {{ $statusFilter === 'approved' ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-gray-200' }}">
            <p class="text-2xl font-bold text-emerald-700">{{ $statusCounts['approved'] ?? 0 }}</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Approved</p>
        </a>

        <a href="{{ route($contentRoutePrefix . '.enrollments.index', array_filter(array_merge(request()->query(), ['status' => 'rejected', 'page' => null]))) }}"
           class="rounded-2xl border bg-white px-5 py-4 shadow-sm transition hover:border-rose-200 {{ $statusFilter === 'rejected' ? 'border-rose-300 ring-1 ring-rose-200' : 'border-gray-200' }}">
            <p class="text-2xl font-bold text-rose-700">{{ $statusCounts['rejected'] ?? 0 }}</p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-gray-500">Rejected</p>
        </a>
    </div>

    <form method="GET" action="{{ route($contentRoutePrefix . '.enrollments.index') }}" class="rounded-2xl border border-gray-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Search</label>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Learner name, email, or module..."
                       class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-200">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select name="status" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-200">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Module</label>
                <select name="module_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-200">
                    <option value="0">All modules</option>
                    @foreach($modulesForFilter as $moduleOption)
                        <option value="{{ $moduleOption->id }}" @selected((int) $moduleFilter === (int) $moduleOption->id)>{{ $moduleOption->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2">
            <a href="{{ route($contentRoutePrefix . '.enrollments.index') }}" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-200">Reset</a>
            <button type="submit" class="rounded-xl bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">Apply Filters</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white" data-enrollment-list>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Learner Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Module Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Enrollment Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Enrollment Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($enrollments as $index => $enrollment)
                        @php
                            $learner = $enrollment->user;
                            $profile = $learner?->learnerProfile;
                            $module = $enrollment->module;
                            $isArchived = (string) $enrollment->rejection_reason_code === 'archived_enrollment';
                            $isPendingDecision = in_array((string) $enrollment->status->value, ['pending', 'pending_parent_approval'], true);

                            $statusLabel = $isArchived
                                ? 'Archived'
                                : match((string) $enrollment->status->value) {
                                    'pending_parent_approval' => 'Pending Parent Approval',
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                    default => ucfirst((string) $enrollment->status->value),
                                };

                            $statusClasses = $isArchived
                                ? 'bg-slate-100 text-slate-700 border-slate-200'
                                : match((string) $enrollment->status->value) {
                                    'pending', 'pending_parent_approval' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    default => 'bg-rose-100 text-rose-700 border-rose-200',
                                };

                            $payload = [
                                'name' => (string) ($learner?->full_name ?: $learner?->name ?: 'Learner'),
                                'email' => (string) ($learner?->email ?: 'N/A'),
                                'username' => (string) ($profile?->username ?: 'N/A'),
                                'age_bracket' => (string) ($profile?->age_range ?: 'N/A'),
                                'module' => (string) ($module?->title ?: 'Unknown module'),
                                'status' => $statusLabel,
                                'enrolled_at' => optional($enrollment->enrolled_at ?: $enrollment->created_at)->format('M d, Y h:i A'),
                            ];

                            $moduleOwnerType = strtolower(trim((string) ($module?->content_owner_type ?? '')));
                            if (!in_array($moduleOwnerType, ['admin', 'platform', 'instructor'], true)) {
                                $moduleCreator = $module?->creator;
                                $moduleOwnerType = (($moduleCreator?->isAdmin() ?? false) || strtolower((string) ($moduleCreator?->role ?? '')) === 'admin')
                                    ? 'admin'
                                    : 'instructor';
                            }
                            $isRestrictedAdminMutation = $isAdminPanel && !in_array($moduleOwnerType, ['admin', 'platform'], true);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $rowOffset + $index + 1 }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $learner?->full_name ?: $learner?->name ?: 'Learner' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $learner?->email ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $module?->title ?: 'Unknown module' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusClasses }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ optional($enrollment->enrolled_at ?: $enrollment->created_at)->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route($contentRoutePrefix . '.enrollments.show', $enrollment) }}"
                                       title="Details"
                                       class="h-8 w-8 rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600">
                                        <svg class="mx-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z"/>
                                        </svg>
                                    </a>

                                    <button type="button"
                                            @click='openView(@json($payload))'
                                            title="View"
                                            class="h-8 w-8 rounded-lg text-gray-400 transition-colors hover:bg-purple-50 hover:text-purple-600">
                                        <svg class="mx-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    @if($isPendingDecision)
                                        <form method="POST" action="{{ route($contentRoutePrefix . '.enrollments.approve', $enrollment) }}" class="inline"
                                              @submit.prevent="@if($isRestrictedAdminMutation) false @else openConfirm($event.target, 'Approve this enrollment request?', 'Approve') @endif">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    @if($isRestrictedAdminMutation) disabled @endif
                                                    title="{{ $isRestrictedAdminMutation ? $ownershipRestrictionTooltip : 'Approve' }}"
                                                    class="h-8 w-8 rounded-lg text-gray-400 transition-colors {{ $isRestrictedAdminMutation ? 'cursor-not-allowed opacity-50' : 'hover:bg-emerald-50 hover:text-emerald-700' }}">
                                                <svg class="mx-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route($contentRoutePrefix . '.enrollments.reject', $enrollment) }}" class="inline"
                                              @submit.prevent="@if($isRestrictedAdminMutation) false @else openConfirm($event.target, 'Reject this enrollment request?', 'Reject') @endif">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="rejection_reason_code" value="did_not_meet_requirements">
                                            <input type="hidden" name="rejection_reason_note" value="Rejected via enrollment management quick action.">
                                            <button type="submit"
                                                    @if($isRestrictedAdminMutation) disabled @endif
                                                    title="{{ $isRestrictedAdminMutation ? $ownershipRestrictionTooltip : 'Reject' }}"
                                                    class="h-8 w-8 rounded-lg text-gray-400 transition-colors {{ $isRestrictedAdminMutation ? 'cursor-not-allowed opacity-50' : 'hover:bg-rose-50 hover:text-rose-700' }}">
                                                <svg class="mx-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if(!$isArchived)
                                        <form method="POST" action="{{ route($contentRoutePrefix . '.enrollments.archive', $enrollment) }}" class="inline"
                                              @submit.prevent="@if($isRestrictedAdminMutation) false @else openConfirm($event.target, 'Archive this enrollment record?', 'Archive') @endif">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    @if($isRestrictedAdminMutation) disabled @endif
                                                    title="{{ $isRestrictedAdminMutation ? $ownershipRestrictionTooltip : 'Archive' }}"
                                                    class="h-8 w-8 rounded-lg text-gray-400 transition-colors {{ $isRestrictedAdminMutation ? 'cursor-not-allowed opacity-50' : 'hover:bg-amber-50 hover:text-amber-700' }}">
                                                <svg class="mx-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 7l1 12h12l1-12M9 7V4h6v3"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route($contentRoutePrefix . '.enrollments.destroy', $enrollment) }}" class="inline"
                                          @submit.prevent="@if($isRestrictedAdminMutation) false @else openConfirm($event.target, 'Permanently delete this enrollment record?', 'Delete') @endif">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                @if($isRestrictedAdminMutation) disabled @endif
                                                title="{{ $isRestrictedAdminMutation ? $ownershipRestrictionTooltip : 'Delete' }}"
                                                class="h-8 w-8 rounded-lg text-gray-400 transition-colors {{ $isRestrictedAdminMutation ? 'cursor-not-allowed opacity-50' : 'hover:bg-rose-50 hover:text-rose-700' }}">
                                            <svg class="mx-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No enrollments found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($enrollments->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>

    <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/60" @click="closeView()"></div>
    <div x-show="viewModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Learner Enrollment Details</h3>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="closeView()">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 text-sm">
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Learner</p>
                    <p class="mt-1 font-medium text-gray-900" x-text="viewPayload?.name"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Email</p>
                    <p class="mt-1 text-gray-900" x-text="viewPayload?.email"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Username</p>
                    <p class="mt-1 text-gray-900" x-text="viewPayload?.username"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Age Bracket</p>
                    <p class="mt-1 text-gray-900" x-text="viewPayload?.age_bracket"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Module</p>
                    <p class="mt-1 text-gray-900" x-text="viewPayload?.module"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Enrollment Status</p>
                    <p class="mt-1 text-gray-900" x-text="viewPayload?.status"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Enrollment Date</p>
                    <p class="mt-1 text-gray-900" x-text="viewPayload?.enrolled_at"></p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="closeView()" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Close</button>
            </div>
        </div>
    </div>

    <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/60" @click="closeConfirm()"></div>
    <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Confirm Action</h3>
            <p class="mt-2 text-sm text-gray-600" x-text="confirmMessage"></p>
            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="closeConfirm()" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="button" @click="confirmAction()" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" x-text="confirmButtonLabel"></button>
            </div>
        </div>
    </div>

</div>
@endsection
