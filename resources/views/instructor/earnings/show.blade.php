@extends('layouts.instructor-app')

@section('title', 'Transaction Details')

@section('content')
    <div x-data="transactionDetailsPage()" class="space-y-6">
        @php
            $avatarPath = $ledger->learner?->learnerProfile?->avatar_path;
            $avatarUrl = null;
            if (!empty($avatarPath)) {
                if (\Illuminate\Support\Str::startsWith($avatarPath, ['http://', 'https://', '//'])) {
                    $avatarUrl = $avatarPath;
                } else {
                    $avatarUrl = asset('storage/' . ltrim(str_replace('storage/', '', (string) $avatarPath), '/'));
                }
            }
        @endphp

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transaction Details</h1>
                <p class="mt-1 text-sm text-gray-600">Complete breakdown of module, learner, payment, and payout information.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('instructor.earnings.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to Earnings</a>

                <button type="button"
                        @click="openConfirm('archive')"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                        title="Archive transaction"
                        aria-label="Archive transaction">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                    </svg>
                </button>

                <button type="button"
                        @click="openConfirm('delete')"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                        title="Delete transaction"
                        aria-label="Delete transaction">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <form id="archive-form" method="POST" action="{{ route('instructor.earnings.archive', $ledger) }}" class="hidden">
                    @csrf
                    <input type="hidden" name="delete_reason" value="archived_from_details_view">
                </form>

                <form id="delete-form" method="POST" action="{{ route('instructor.earnings.delete', $ledger) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_reason" value="deleted_from_details_view">
                </form>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Sale Amount</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">P{{ number_format((float) $ledger->gross_amount, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Platform Fee</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">P{{ number_format((float) $ledger->commission_amount, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Your Earnings</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">P{{ number_format((float) $ledger->instructor_earnings_amount, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Transaction Status</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ ucfirst((string) $ledger->sale_status) }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Module Information</h2>
                <div class="mt-4 flex items-start gap-4">
                    @if($ledger->module?->thumbnail_url)
                        <img src="{{ $ledger->module->thumbnail_url }}" alt="Module thumbnail" class="h-20 w-28 rounded-lg border border-gray-200 object-cover">
                    @else
                        <span class="inline-flex h-20 w-28 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 text-xs font-semibold text-gray-500">No image</span>
                    @endif
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $ledger->module?->title ?? 'Unknown Module' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Price: P{{ number_format((float) ($ledger->module?->price_amount ?? 0), 2) }}</p>
                        <p class="mt-1 text-xs text-gray-500">Purchase status: {{ ucfirst((string) ($ledger->modulePurchase?->status ?? 'N/A')) }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Learner Information</h2>
                <div class="mt-4 flex items-start gap-4">
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Learner avatar" class="h-14 w-14 rounded-full border border-gray-200 object-cover">
                    @else
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-sky-100 text-lg font-semibold text-sky-700">
                            {{ strtoupper(substr($ledger->learner_name_snapshot ?: ($ledger->learner?->name ?? 'U'), 0, 1)) }}
                        </span>
                    @endif
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $ledger->learner_name_snapshot ?: ($ledger->learner?->name ?? 'Unknown Learner') }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $ledger->learner?->email ?? 'No email available' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Username: {{ $ledger->learner?->learnerProfile?->username ?? 'N/A' }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Payment Information</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500">Reference</dt>
                        <dd class="font-semibold text-gray-900">{{ $ledger->payment?->transaction_id ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500">Method</dt>
                        <dd class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', (string) ($ledger->payment?->method ?? 'unknown'))) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500">Payment Status</dt>
                        <dd class="font-semibold text-gray-900">{{ ucfirst((string) ($ledger->payment?->status?->value ?? $ledger->payment?->status ?? 'N/A')) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500">Payout Status</dt>
                        <dd class="font-semibold text-gray-900">{{ ucfirst((string) $ledger->payout_status) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500">Purchased At</dt>
                        <dd class="font-semibold text-gray-900">{{ optional($ledger->modulePurchase?->purchased_at ?: $ledger->occurred_at)->toDayDateTimeString() ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Commission Snapshot</h2>
            </div>
            <dl class="grid gap-x-8 gap-y-4 px-6 py-5 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Commission Percent</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ number_format((float) $ledger->commission_percent_snapshot, 2) }}%</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Tax Basis</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ strtoupper((string) $ledger->tax_basis_snapshot) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Refund Policy</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ strtoupper((string) $ledger->refund_policy_snapshot) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Recorded At</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ optional($ledger->occurred_at)->toDayDateTimeString() ?? 'N/A' }}</dd>
                </div>
            </dl>
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
        function transactionDetailsPage() {
            return {
                confirmOpen: false,
                confirmKind: 'archive',
                confirmTitle: '',
                confirmMessage: '',
                confirmFormId: null,
                openConfirm(kind) {
                    this.confirmKind = kind;
                    this.confirmFormId = kind === 'delete' ? 'delete-form' : 'archive-form';

                    if (kind === 'delete') {
                        this.confirmTitle = 'Delete Transaction?';
                        this.confirmMessage = 'This removes this transaction from your earnings page only. Platform records stay intact for transparency.';
                    } else {
                        this.confirmTitle = 'Archive Transaction?';
                        this.confirmMessage = 'This archives this transaction from your earnings page. You can keep your view clean while preserving platform records.';
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
