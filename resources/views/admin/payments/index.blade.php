@extends('layouts.admin')

@section('title', 'Payment Management')
@section('page-title', 'Payment Management')

@php
    $paymentRows = $payments->values()->map(function ($payment) {
        $status = is_object($payment->status) ? $payment->status->value : (string) $payment->status;
        $method = (string) ($payment->method ?? 'unknown');

        return [
            'id' => $payment->id,
            'user' => $payment->user?->name ?? 'Unknown user',
            'email' => $payment->user?->email ?? 'No email',
            'amount' => (float) ($payment->amount ?? 0),
            'method' => $method,
            'status' => $status,
            'created_at' => $payment->created_at?->format('M d, Y') ?? 'Unknown date',
            'created_at_value' => $payment->created_at?->format('Y-m-d') ?? null,
            'reference' => $payment->transaction_id ?? '-',
            'show_url' => route('admin.payments.show', $payment),
            'complete_url' => route('admin.payments.complete', $payment),
            'can_complete' => method_exists($payment, 'isPending')
                ? ($payment->isPending() || $status === 'processing')
                : in_array($status, ['pending', 'processing'], true),
            'search_blob' => strtolower(implode(' ', array_filter([
                $payment->id,
                $payment->user?->name,
                $payment->user?->email,
                $payment->amount,
                $method,
                $status,
                $payment->transaction_id,
                $payment->created_at?->format('Y-m-d'),
            ]))),
        ];
    });
@endphp

@section('content')
    <div x-data="paymentManagementPage({
            payments: @js($paymentRows),
            stats: @js($stats),
        })"
         class="space-y-8">

        @foreach(['success','error','warning'] as $type)
            @if(session($type))
                @php
                    $cfg = [
                        'success' => ['bg' => 'bg-success-50 border-success-200 text-success-700', 'icon' => 'M5 13l4 4L19 7'],
                        'error' => ['bg' => 'bg-error-50 border-error-200 text-error-700', 'icon' => 'M6 18L18 6M6 6l12 12'],
                        'warning' => ['bg' => 'bg-warning-50 border-warning-200 text-warning-700', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    ];
                @endphp
                <div class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm {{ $cfg[$type]['bg'] }}">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg[$type]['icon'] }}"/>
                    </svg>
                    {{ session($type) }}
                </div>
            @endif
        @endforeach

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Total Revenue', 'value' => null, 'valueKey' => 'total_revenue', 'type' => 'currency', 'accent' => 'emerald', 'description' => 'Completed payments currently recorded in admin.'],
                    ['label' => 'Completed', 'value' => null, 'valueKey' => 'completed', 'type' => 'number', 'accent' => 'sky', 'description' => 'Transactions that already activated successfully.'],
                    ['label' => 'Needs Review', 'value' => null, 'valueKey' => null, 'type' => 'computed-review', 'accent' => 'amber', 'description' => 'Pending and processing records waiting for reconciliation.'],
                    ['label' => 'Failed', 'value' => null, 'valueKey' => 'failed', 'type' => 'number', 'accent' => 'rose', 'description' => 'Payments that require follow-up or retry support.'],
                ];
            @endphp

            @foreach($cards as $card)
                <div class="rounded-[28px] border p-5 shadow-theme-xs {{ $card['accent'] === 'emerald' ? 'border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50' : ($card['accent'] === 'sky' ? 'border-sky-100 bg-gradient-to-br from-sky-50 via-white to-cyan-50' : ($card['accent'] === 'amber' ? 'border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50' : 'border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50')) }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $card['accent'] === 'emerald' ? 'text-emerald-600' : ($card['accent'] === 'sky' ? 'text-sky-600' : ($card['accent'] === 'amber' ? 'text-amber-600' : 'text-rose-600')) }}">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-bold text-gray-900">
                                @if($card['type'] === 'currency')
                                    <span x-text="formatCurrency(stats.{{ $card['valueKey'] }})"></span>
                                @elseif($card['type'] === 'computed-review')
                                    <span x-text="formatNumber((stats.pending || 0) + (stats.processing || 0))"></span>
                                @else
                                    <span x-text="formatNumber(stats.{{ $card['valueKey'] }})"></span>
                                @endif
                            </p>
                            <p class="mt-2 text-sm text-gray-500">{{ $card['description'] }}</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-lg {{ $card['accent'] === 'emerald' ? 'bg-emerald-500 shadow-emerald-200' : ($card['accent'] === 'sky' ? 'bg-sky-500 shadow-sky-200' : ($card['accent'] === 'amber' ? 'bg-amber-500 shadow-amber-200' : 'bg-rose-500 shadow-rose-200')) }}">
                            @if($card['accent'] === 'emerald')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($card['accent'] === 'sky')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @elseif($card['accent'] === 'amber')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @else
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.10),_transparent_30%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        @include('admin.partials.table-filter-bar', ['label' => 'Payments Filters', 'hint' => 'Search every visible column and narrow results with live column filters'])
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Live Filters</p>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Payments Table</h2>
                        <p class="mt-1 text-sm text-gray-500">Search user names, emails, references, methods, and statuses while filtering the full admin payments dataset in real time.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                        <label class="block xl:col-span-2">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input x-model.debounce.150ms="filters.search"
                                   type="text"
                                   placeholder="User, email, method, status, ref..."
                                   class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Method</span>
                            <select x-model="filters.method"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">All methods</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                                <option value="grab_pay">GrabPay</option>
                                <option value="card">Card</option>
                                <option value="billease">BillEase</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="paymongo">PayMongo</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
                            <select x-model="filters.status"
                                    class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date From</span>
                                <input x-model="filters.dateFrom"
                                       type="date"
                                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</span>
                                <input x-model="filters.dateTo"
                                       type="date"
                                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900"><span x-text="filteredPayments.length"></span> matching payments</p>
                    <p class="text-xs text-gray-500">Results update instantly as you type or change the filters.</p>
                </div>
                <button type="button"
                        @click="resetFilters()"
                        class="inline-flex items-center rounded-2xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
                    Reset Filters
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Method</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Ref</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="(payment, index) in filteredPayments" :key="payment.id">
                            <tr class="transition hover:bg-sky-50/40">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="index + 1"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-bold text-sky-700"
                                              x-text="payment.user.charAt(0).toUpperCase()"></span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900" x-text="payment.user"></p>
                                            <p class="text-xs text-gray-500" x-text="payment.email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatCurrency(payment.amount)"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                          :class="methodClass(payment.method)"
                                          x-text="methodLabel(payment.method)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                          :class="statusClass(payment.status)"
                                          x-text="formatLabel(payment.status)"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="payment.created_at"></td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-500" x-text="truncate(payment.reference, 16)"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="payment.show_url"
                                           class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                                           title="View payment">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <template x-if="payment.can_complete">
                                            <form :action="payment.complete_url" method="POST" @submit="return confirm('Mark as completed and activate subscription?')">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                                                        title="Complete payment">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredPayments.length === 0" x-cloak>
                            <td colspan="8" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-sm">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h3 class="mt-4 text-sm font-semibold text-gray-900">No payments match these filters</h3>
                                    <p class="mt-1 text-sm text-gray-500">Try broadening the search or resetting one of the filter fields.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function paymentManagementPage(config) {
            return {
                payments: config.payments || [],
                stats: config.stats || {},
                filters: {
                    search: '',
                    method: '',
                    status: '',
                    dateFrom: '',
                    dateTo: '',
                },
                get filteredPayments() {
                    return this.payments.filter((payment) => {
                        const search = this.filters.search.trim().toLowerCase();
                        const matchesSearch = !search || payment.search_blob.includes(search);
                        const matchesMethod = !this.filters.method || payment.method === this.filters.method;
                        const matchesStatus = !this.filters.status || payment.status === this.filters.status;
                        const matchesDateFrom = !this.filters.dateFrom || (payment.created_at_value && payment.created_at_value >= this.filters.dateFrom);
                        const matchesDateTo = !this.filters.dateTo || (payment.created_at_value && payment.created_at_value <= this.filters.dateTo);

                        return matchesSearch && matchesMethod && matchesStatus && matchesDateFrom && matchesDateTo;
                    });
                },
                resetFilters() {
                    this.filters.search = '';
                    this.filters.method = '';
                    this.filters.status = '';
                    this.filters.dateFrom = '';
                    this.filters.dateTo = '';
                },
                formatNumber(value) {
                    return new Intl.NumberFormat('en-US').format(Number(value || 0));
                },
                formatCurrency(value) {
                    return new Intl.NumberFormat('en-PH', {
                        style: 'currency',
                        currency: 'PHP',
                        minimumFractionDigits: 2,
                    }).format(Number(value || 0));
                },
                formatLabel(value) {
                    return String(value || '')
                        .replace(/_/g, ' ')
                        .replace(/\b\w/g, (char) => char.toUpperCase());
                },
                methodLabel(method) {
                    return {
                        gcash: 'GCash',
                        paymaya: 'PayMaya',
                        grab_pay: 'GrabPay',
                        card: 'Card',
                        billease: 'BillEase',
                        bank_transfer: 'Bank Transfer',
                        paymongo: 'PayMongo',
                    }[method] || this.formatLabel(method);
                },
                methodClass(method) {
                    return {
                        gcash: 'bg-sky-100 text-sky-700',
                        paymaya: 'bg-emerald-100 text-emerald-700',
                        grab_pay: 'bg-teal-100 text-teal-700',
                        card: 'bg-violet-100 text-violet-700',
                        billease: 'bg-indigo-100 text-indigo-700',
                        bank_transfer: 'bg-gray-100 text-gray-600',
                        paymongo: 'bg-sky-100 text-sky-700',
                    }[method] || 'bg-gray-100 text-gray-600';
                },
                statusClass(status) {
                    return {
                        completed: 'bg-emerald-100 text-emerald-700',
                        failed: 'bg-rose-100 text-rose-700',
                        refunded: 'bg-gray-100 text-gray-600',
                        pending: 'bg-amber-100 text-amber-700',
                        processing: 'bg-sky-100 text-sky-700',
                    }[status] || 'bg-gray-100 text-gray-600';
                },
                truncate(value, length = 16) {
                    const text = String(value || '-');
                    return text.length > length ? `${text.slice(0, length)}...` : text;
                },
            };
        }
    </script>
@endsection
