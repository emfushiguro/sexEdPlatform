@extends('layouts.instructor-app')

@section('title', 'Module Earnings')

@section('content')
    <div x-data="earningsPage()" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h1 class="text-2xl font-bold text-gray-900">Module Earnings</h1>
            <p class="mt-1 text-sm text-gray-600">Track your paid module sales with clearer performance summaries and transaction details.</p>

            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
                <p class="text-sm font-semibold text-indigo-900">Formula: Your Earnings = Sale amount - Platform fee.</p>
                <p class="mt-1 text-xs text-indigo-800">Refund policy: module purchase refunds are currently disabled.</p>
                @if(!empty($effectiveCommissionPolicy))
                    <p class="mt-1 text-xs text-indigo-800">
                        Current commission rate for new sales: {{ number_format((float) $effectiveCommissionPolicy['commission_percent'], 2) }}%.
                    </p>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Sales</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_sales']) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Sales Revenue</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['gross_revenue'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Platform Fee</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['platform_commission'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-700">Your Earnings</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['net_earnings'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Last 7 Days</p>
                <p class="mt-2 text-xl font-bold text-gray-900">{{ number_format((int) $stats['last_7_days_sales']) }} sales</p>
                <p class="mt-1 text-sm font-semibold text-sky-700">P{{ number_format((float) $stats['last_7_days_earnings'], 2) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Sales Transactions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Learner</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Sale Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Platform Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Your Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Payout</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Purchased</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($transactions as $tx)
                            @php
                                $avatarPath = $tx->learner?->learnerProfile?->avatar_path;
                                $avatarUrl = null;
                                if (!empty($avatarPath)) {
                                    if (\Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://', '//'])) {
                                        $avatarUrl = $avatarPath;
                                    } else {
                                        $avatarUrl = asset('storage/' . ltrim(str_replace('storage/', '', (string) $avatarPath), '/'));
                                    }
                                }
                                $rowNumber = (int) ($transactions->firstItem() + $loop->index);
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-500">{{ $rowNumber }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($tx->module?->thumbnail_url)
                                            <img src="{{ $tx->module->thumbnail_url }}" alt="Module thumbnail" class="h-11 w-16 rounded-lg border border-gray-200 object-cover">
                                        @else
                                            <span class="inline-flex h-11 w-16 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 text-[11px] font-semibold text-gray-500">No image</span>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $tx->module?->title ?? 'Unknown Module' }}</p>
                                            <p class="text-xs text-gray-500">{{ ucfirst((string) ($tx->modulePurchase?->status ?? 'completed')) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="Learner avatar" class="h-10 w-10 rounded-full border border-gray-200 object-cover">
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sm font-semibold text-sky-700">
                                                {{ strtoupper(substr($tx->learner_name_snapshot ?: ($tx->learner?->name ?? 'U'), 0, 1)) }}
                                            </span>
                                        @endif
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $tx->learner_name_snapshot ?: ($tx->learner?->name ?? 'Unknown Learner') }}</p>
                                            <p class="text-xs text-gray-500">{{ $tx->learner?->email ?? 'No email available' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">P{{ number_format((float) $tx->gross_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">P{{ number_format((float) $tx->commission_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">P{{ number_format((float) $tx->instructor_earnings_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ ucfirst((string) $tx->payout_status) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ optional($tx->occurred_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('instructor.earnings.show', $tx) }}"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100"
                                           title="View transaction details"
                                           aria-label="View transaction details">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <button type="button"
                                                @click="openConfirm('archive', {{ $tx->id }}, @js($tx->module?->title ?? 'this transaction'))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                                                title="Archive transaction"
                                                aria-label="Archive transaction">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                                            </svg>
                                        </button>

                                        <button type="button"
                                                @click="openConfirm('delete', {{ $tx->id }}, @js($tx->module?->title ?? 'this transaction'))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                title="Delete transaction"
                                                aria-label="Delete transaction">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <form id="archive-form-{{ $tx->id }}" method="POST" action="{{ route('instructor.earnings.archive', $tx) }}" class="hidden">
                                        @csrf
                                        <input type="hidden" name="delete_reason" value="archived_by_instructor">
                                    </form>

                                    <form id="delete-form-{{ $tx->id }}" method="POST" action="{{ route('instructor.earnings.delete', $tx) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="delete_reason" value="deleted_by_instructor">
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">No earnings transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $transactions->links() }}
            </div>
        </div>

        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeConfirm()"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-gray-900" x-text="confirmTitle"></h3>
                <p class="mt-2 text-sm text-gray-600" x-text="confirmMessage"></p>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button type="button" @click="closeConfirm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button"
                            @click="submitConfirm()"
                            :class="confirmKind === 'delete' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-white">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function earningsPage() {
            return {
                confirmOpen: false,
                confirmKind: 'archive',
                confirmTitle: '',
                confirmMessage: '',
                confirmFormId: null,
                openConfirm(kind, ledgerId, moduleTitle) {
                    this.confirmKind = kind;
                    this.confirmFormId = `${kind}-form-${ledgerId}`;

                    if (kind === 'delete') {
                        this.confirmTitle = 'Delete Transaction?';
                        this.confirmMessage = `You are removing ${moduleTitle} from your earnings list. This only changes your personal view and does not remove platform records.`;
                    } else {
                        this.confirmTitle = 'Archive Transaction?';
                        this.confirmMessage = `You are archiving ${moduleTitle} from your earnings list. You can still keep financial transparency in admin records.`;
                    }

                    this.confirmOpen = true;
                },
                closeConfirm() {
                    this.confirmOpen = false;
                    this.confirmFormId = null;
                },
                submitConfirm() {
                    if (!this.confirmFormId) {
                        return;
                    }

                    const form = document.getElementById(this.confirmFormId);
                    if (form) {
                        form.submit();
                    }
                },
            };
        }
    </script>
@endsection
