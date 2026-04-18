@extends('layouts.instructor-app')

@section('title', 'Module Earnings')

@section('content')
    @php
        $commissionRate = number_format((float) ($effectiveCommissionPolicy['commission_percent'] ?? 0), 2);
        $refundPolicy = (string) ($effectiveCommissionPolicy['refund_policy'] ?? 'disabled');
        $refundPolicyLabel = str_replace('_', ' ', $refundPolicy);
    @endphp

    <div x-data="earningsPage()" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Module Earnings</h1>
                    <p class="mt-1 text-sm text-gray-600">Track your paid module sales with clearer performance summaries and transaction details.</p>
                </div>
                <button type="button"
                        @click="policyModalOpen = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                        aria-label="View earnings policy">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    View Earnings Policy
                </button>
            </div>

            <form method="GET" action="{{ route('instructor.earnings.index') }}" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Report Type</span>
                    <select name="report_type" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        <option value="weekly" @selected(($reportFilter->reportType ?? 'monthly') === 'weekly')>Weekly</option>
                        <option value="monthly" @selected(($reportFilter->reportType ?? 'monthly') === 'monthly')>Monthly</option>
                        <option value="yearly" @selected(($reportFilter->reportType ?? 'monthly') === 'yearly')>Yearly</option>
                        <option value="custom" @selected(($reportFilter->reportType ?? 'monthly') === 'custom')>Custom Range</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date From</span>
                    <input type="date" name="date_from" value="{{ request('date_from', $reportFilter->localStart?->toDateString()) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</span>
                    <input type="date" name="date_to" value="{{ request('date_to', $reportFilter->localEnd?->toDateString()) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Module</span>
                    <select name="module_id" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        <option value="">All modules</option>
                        @foreach(($moduleFilterOptions ?? collect()) as $moduleOption)
                            <option value="{{ $moduleOption->id }}" @selected((string) request('module_id') === (string) $moduleOption->id)>
                                {{ Illuminate\Support\Str::limit($moduleOption->title, 35) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Payment Method</span>
                    <select name="payment_method" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        <option value="">All methods</option>
                        @foreach(['gcash' => 'GCash', 'paymaya' => 'PayMaya', 'grab_pay' => 'GrabPay', 'card' => 'Card', 'billease' => 'BillEase', 'bank_transfer' => 'Bank Transfer', 'paymongo' => 'PayMongo'] as $methodValue => $methodLabel)
                            <option value="{{ $methodValue }}" @selected(request('payment_method') === $methodValue)>{{ $methodLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Payout Status</span>
                    <select name="payout_status" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                        <option value="">All payouts</option>
                        @foreach(['pending' => 'Pending', 'payable' => 'Payable', 'paid' => 'Paid', 'reversed' => 'Reversed'] as $payoutValue => $payoutLabel)
                            <option value="{{ $payoutValue }}" @selected(request('payout_status') === $payoutValue)>{{ $payoutLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block sm:col-span-2 xl:col-span-2">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Module, learner, or reference" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 focus:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-100">
                </label>

                <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-3">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Apply Filters</button>
                    <a href="{{ route('instructor.earnings.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
                    <a href="{{ route('instructor.earnings.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">PDF</a>
                    <a href="{{ route('instructor.earnings.export', ['format' => 'csv'] + request()->query()) }}" class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">CSV</a>
                    <a href="{{ route('instructor.earnings.export', ['format' => 'xlsx'] + request()->query()) }}" class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">XLSX</a>
                </div>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Sales</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_sales']) }}</p>
            </div>
            <div class="rounded-2xl border border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Sales Revenue</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['gross_revenue'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-100/60 via-white to-brand-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Platform Fee</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['platform_commission'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-brand-300 bg-gradient-to-br from-brand-100 via-white to-brand-200/70 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-700">Your Earnings</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">P{{ number_format((float) $stats['net_earnings'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Selected Range</p>
                <p class="mt-2 text-xl font-bold text-gray-900">{{ number_format((int) $stats['total_sales']) }} sales</p>
                <p class="mt-1 text-sm font-semibold text-sky-700">{{ $stats['range_label'] ?? 'Monthly' }} report</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Earnings Per Module</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Sales</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Commission</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Earnings</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($moduleBreakdown as $item)
                        <tr>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-900">{{ $item->module?->title ?? 'Unknown Module' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ number_format((int) $item->sales_count) }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">P{{ number_format((float) $item->gross_amount, 2) }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">P{{ number_format((float) $item->commission_amount, 2) }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">P{{ number_format((float) $item->instructor_earnings_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No module breakdown data for selected range.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
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
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Method</th>
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
                                <td class="px-6 py-3 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', (string) ($tx->payment?->method ?? 'unknown'))) }}</td>
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
                                <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500">No earnings transactions yet.</td>
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

        <div x-show="policyModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-gray-900/50" @click="policyModalOpen = false"></div>

            <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Earnings Policy</h3>
                        <p class="mt-1 text-sm text-gray-600">Understand how sale revenue, commission, and refund rules affect your instructor earnings.</p>
                    </div>
                    <button type="button"
                            @click="policyModalOpen = false"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-700"
                            aria-label="Close earnings policy modal">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <section class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.12em] text-indigo-800">Commission Rate</h4>
                        <p class="mt-2 text-sm text-indigo-900">
                            The platform commission for your new paid module sales is
                            <span class="font-bold">{{ $commissionRate }}%</span>.
                        </p>
                        <p class="mt-2 text-xs text-indigo-800">Formula: Your Earnings = Sale amount - Platform fee.</p>
                        <p class="mt-1 text-xs text-indigo-800">Estimated net earnings per sale: Price - (Price x {{ $commissionRate }}%).</p>
                    </section>

                    <section class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.12em] text-amber-800">Refund Policy</h4>
                        <p class="mt-2 text-sm text-amber-900">
                            Current policy status: <span class="font-bold">{{ ucfirst($refundPolicyLabel) }}</span>.
                        </p>
                        <p class="mt-2 text-xs text-amber-800">
                            Refund policy: module purchase refunds are currently {{ strtolower($refundPolicyLabel) }}.
                        </p>
                        <p class="mt-1 text-xs text-amber-800">
                            If refund rules are activated in the future, refunded transactions can reduce reported earnings and payout availability.
                        </p>
                    </section>
                </div>

                <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-600">
                        Note: Policy values shown here apply to new qualifying transactions. Historical records keep their own commission and policy snapshots for transparency.
                    </p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button"
                            @click="policyModalOpen = false"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function earningsPage() {
            return {
                policyModalOpen: false,
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
