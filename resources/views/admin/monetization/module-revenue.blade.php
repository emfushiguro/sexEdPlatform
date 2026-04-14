@extends('layouts.admin')

@section('title', 'Module Revenue Dashboard')
@section('page-title', 'Module Revenue Dashboard')

@section('content')
    <div x-data="moduleRevenueDashboard()" x-init="init()" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Module Revenue Dashboard</h1>
                </div>
                <a href="{{ route('admin.monetization.commission-settings.index') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                    Manage Commission Settings
                </a>
            </div>

            <form method="GET" action="{{ route('admin.monetization.module-revenue.index') }}" class="mt-5 grid gap-3 md:grid-cols-5">
                <select name="instructor_id" class="rounded-xl border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Instructors</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}" @selected((string) request('instructor_id') === (string) $instructor->id)>
                            {{ $instructor->name }}
                        </option>
                    @endforeach
                </select>

                <select name="module_id" class="rounded-xl border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->id }}" @selected((string) request('module_id') === (string) $module->id)>
                            {{ $module->title }}
                        </option>
                    @endforeach
                </select>

                <select name="payout_status" class="rounded-xl border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All Payout Statuses</option>
                    @foreach(['pending', 'payable', 'paid', 'reversed'] as $status)
                        <option value="{{ $status }}" @selected(request('payout_status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm">

                <div class="md:col-span-5 flex gap-2">
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply Filters</button>
                    <a href="{{ route('admin.monetization.module-revenue.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Total Module Revenue</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format((float) $stats['total_module_revenue'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Total Instructor Earnings</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format((float) $stats['total_instructor_earnings'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Platform Commission Earnings</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format((float) $stats['total_platform_commission'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700">Total Modules Sold</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_modules_sold']) }}</p>
            </div>
            <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-700">Total Transactions</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_transactions']) }}</p>
            </div>
        </div>

        <div id="sales-transactions" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Sales Transactions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Learner</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Platform Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Context</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Payout</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Purchased Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($transactions as $tx)
                            @php
                                $avatarPath = $tx->learner?->learnerProfile?->avatar_path;
                                $avatarUrl = null;
                                $rowNumber = ($transactions->firstItem() ?? 1) + $loop->index;
                                if (!empty($avatarPath)) {
                                    if (\Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://', '//'])) {
                                        $avatarUrl = $avatarPath;
                                    } else {
                                        $avatarUrl = asset('storage/' . ltrim(str_replace('storage/', '', (string) $avatarPath), '/'));
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-500">{{ $rowNumber }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($tx->module?->thumbnail_url)
                                            <img src="{{ $tx->module->thumbnail_url }}" alt="Module thumbnail" class="h-10 w-16 rounded-lg border border-gray-200 object-cover">
                                        @else
                                            <span class="inline-flex h-10 w-16 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 text-[11px] font-semibold text-gray-500">No image</span>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $tx->module?->title ?? 'Unknown Module' }}</p>
                                            <p class="text-xs text-gray-500">{{ $tx->payment?->transaction_id ?? 'No reference' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <p class="text-sm font-semibold text-gray-900">{{ $tx->instructor?->name ?? 'Unknown Instructor' }}</p>
                                    <p class="text-xs text-gray-500">{{ $tx->instructor?->email ?? 'No email' }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="Learner avatar" class="h-9 w-9 rounded-full border border-gray-200 object-cover">
                                        @else
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-sky-100 text-sm font-semibold text-sky-700">
                                                {{ strtoupper(substr($tx->learner_name_snapshot ?: ($tx->learner?->name ?? 'U'), 0, 1)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $tx->learner_name_snapshot ?: ($tx->learner?->name ?? 'Unknown Learner') }}</p>
                                            <p class="text-xs text-gray-500">{{ $tx->learner?->email ?? 'No email' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">₱{{ number_format((float) $tx->gross_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">₱{{ number_format((float) $tx->commission_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">₱{{ number_format((float) $tx->instructor_earnings_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">
                                    <p class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', (string) ($tx->payment?->method ?? 'unknown'))) }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst((string) ($tx->payment?->status?->value ?? $tx->payment?->status ?? 'N/A')) }}</p>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ ucfirst((string) $tx->payout_status) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ optional($tx->modulePurchase?->purchased_at ?: $tx->occurred_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">
                                    @if($tx->payout_status === 'pending')
                                        <form method="POST" action="{{ route('admin.monetization.module-revenue.payout.update', $tx) }}" onsubmit="return confirm('Mark this transaction as payable?');">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payout_status" value="payable">
                                            <button type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100"
                                                    title="Mark as payable"
                                                    aria-label="Mark as payable">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9z" />
                                                </svg>
                                            </button>
                                        </form>
                                    @elseif($tx->payout_status === 'payable')
                                        <form method="POST" action="{{ route('admin.monetization.module-revenue.payout.update', $tx) }}" onsubmit="return confirm('Mark this transaction as paid?');">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payout_status" value="paid">
                                            <button type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                                                    title="Mark as paid"
                                                    aria-label="Mark as paid">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-10 text-center text-sm text-gray-500">No transactions found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $transactions->links() }}
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Instructor Roll-up</h2>
                    </div>
                    <button type="button"
                            x-show="archivedInstructorIds.length || deletedInstructorIds.length"
                            @click="resetHiddenInstructors()"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        Reset Hidden Rows
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Sales</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Platform Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($rollups as $rollup)
                            @php
                                $instructorPhotoPath = $rollup->instructor?->instructorProfile?->profile_photo_path;
                                $instructorPhotoUrl = null;

                                if (!empty($instructorPhotoPath)) {
                                    if (\Illuminate\Support\Str::startsWith($instructorPhotoPath, ['http://', 'https://', '//'])) {
                                        $instructorPhotoUrl = $instructorPhotoPath;
                                    } else {
                                        $instructorPhotoUrl = asset('storage/' . ltrim(str_replace('storage/', '', (string) $instructorPhotoPath), '/'));
                                    }
                                }
                            @endphp
                            <tr x-show="isInstructorVisible({{ (int) $rollup->instructor_id }})">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($instructorPhotoUrl)
                                            <img src="{{ $instructorPhotoUrl }}" alt="Instructor avatar" class="h-10 w-10 rounded-full border border-gray-200 object-cover">
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                                {{ strtoupper(substr((string) ($rollup->instructor?->name ?? 'U'), 0, 1)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $rollup->instructor?->name ?? 'Unknown Instructor' }}</p>
                                            <p class="text-xs text-gray-500">{{ $rollup->instructor?->email ?? 'No email' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ number_format((int) $rollup->sales_count) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $rollup->gross_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $rollup->commission_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $rollup->earnings_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.monetization.module-revenue.index', ['instructor_id' => $rollup->instructor_id]) }}#sales-transactions"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100"
                                           title="View instructor transactions"
                                           aria-label="View instructor transactions">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <button type="button"
                                                @click="openRollupAction('archive', {{ (int) $rollup->instructor_id }}, @js($rollup->instructor?->name ?? 'this instructor'))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                                                title="Archive roll-up row"
                                                aria-label="Archive roll-up row">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                                @click="openRollupAction('delete', {{ (int) $rollup->instructor_id }}, @js($rollup->instructor?->name ?? 'this instructor'))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                title="Delete roll-up row"
                                                aria-label="Delete roll-up row">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No instructor roll-up data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="rollupConfirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeRollupAction()"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900" x-text="rollupConfirmTitle"></h3>
                <p class="mt-2 text-sm text-gray-600" x-text="rollupConfirmMessage"></p>

                <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                    <p class="text-xs text-gray-600">These actions only affect row visibility on this dashboard and do not modify stored transaction records.</p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" @click="closeRollupAction()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button"
                            @click="confirmRollupAction()"
                            :class="rollupActionKind === 'delete' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-white">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function moduleRevenueDashboard() {
            return {
                archivedInstructorIds: [],
                deletedInstructorIds: [],
                rollupConfirmOpen: false,
                rollupActionKind: 'archive',
                rollupTargetInstructorId: null,
                rollupTargetInstructorName: '',
                rollupConfirmTitle: '',
                rollupConfirmMessage: '',
                init() {
                    this.archivedInstructorIds = this.readLocal('admin_module_revenue_archived_instructors');
                    this.deletedInstructorIds = this.readLocal('admin_module_revenue_deleted_instructors');
                },
                readLocal(key) {
                    try {
                        const parsed = JSON.parse(window.localStorage.getItem(key) || '[]');
                        return Array.isArray(parsed) ? parsed.map((id) => Number(id)).filter((id) => Number.isInteger(id)) : [];
                    } catch (error) {
                        return [];
                    }
                },
                writeLocal() {
                    window.localStorage.setItem('admin_module_revenue_archived_instructors', JSON.stringify(this.archivedInstructorIds));
                    window.localStorage.setItem('admin_module_revenue_deleted_instructors', JSON.stringify(this.deletedInstructorIds));
                },
                isInstructorVisible(instructorId) {
                    const normalizedId = Number(instructorId);
                    return !this.archivedInstructorIds.includes(normalizedId)
                        && !this.deletedInstructorIds.includes(normalizedId);
                },
                openRollupAction(kind, instructorId, instructorName) {
                    this.rollupActionKind = kind;
                    this.rollupTargetInstructorId = Number(instructorId);
                    this.rollupTargetInstructorName = instructorName;

                    if (kind === 'delete') {
                        this.rollupConfirmTitle = 'Delete Instructor Roll-up Row?';
                        this.rollupConfirmMessage = `You are removing ${instructorName} from this roll-up view.`;
                    } else {
                        this.rollupConfirmTitle = 'Archive Instructor Roll-up Row?';
                        this.rollupConfirmMessage = `You are archiving ${instructorName} from this roll-up view.`;
                    }

                    this.rollupConfirmOpen = true;
                },
                closeRollupAction() {
                    this.rollupConfirmOpen = false;
                    this.rollupTargetInstructorId = null;
                    this.rollupTargetInstructorName = '';
                },
                confirmRollupAction() {
                    if (!Number.isInteger(this.rollupTargetInstructorId)) {
                        return;
                    }

                    if (this.rollupActionKind === 'delete') {
                        if (!this.deletedInstructorIds.includes(this.rollupTargetInstructorId)) {
                            this.deletedInstructorIds.push(this.rollupTargetInstructorId);
                        }
                        this.archivedInstructorIds = this.archivedInstructorIds.filter((id) => id !== this.rollupTargetInstructorId);
                    } else {
                        if (!this.archivedInstructorIds.includes(this.rollupTargetInstructorId)) {
                            this.archivedInstructorIds.push(this.rollupTargetInstructorId);
                        }
                        this.deletedInstructorIds = this.deletedInstructorIds.filter((id) => id !== this.rollupTargetInstructorId);
                    }

                    this.writeLocal();
                    this.closeRollupAction();
                },
                resetHiddenInstructors() {
                    this.archivedInstructorIds = [];
                    this.deletedInstructorIds = [];
                    this.writeLocal();
                },
            };
        }
    </script>
@endsection
