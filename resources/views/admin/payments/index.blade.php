@extends('layouts.admin')

@section('title', 'Payment Management')
@section('page-title', 'Payment Management')

@php
    $paymentRows = $payments->values()->map(function ($payment) {
        $status = is_object($payment->status) ? $payment->status->value : (string) $payment->status;
        $method = (string) ($payment->method ?? 'unknown');
        $scope = (string) data_get($payment->payment_details, 'payment_scope');

        $isModulePurchase = $scope === 'module_purchase'
            || $payment->modulePurchase !== null
            || $payment->moduleSaleLedger !== null;

        $module = $payment->modulePurchase?->module ?? $payment->moduleSaleLedger?->module;
        $instructor = $module?->creator ?? $payment->moduleSaleLedger?->instructor;
        $learner = $payment->user ?? $payment->moduleSaleLedger?->learner;

        $learnerAvatarPath = $learner?->learnerProfile?->avatar_path;
        $learnerAvatar = null;
        if (!empty($learnerAvatarPath)) {
            if (\Illuminate\Support\Str::startsWith($learnerAvatarPath, ['http://', 'https://', '//'])) {
                $learnerAvatar = $learnerAvatarPath;
            } else {
                $learnerAvatar = asset('storage/' . ltrim(str_replace('storage/', '', (string) $learnerAvatarPath), '/'));
            }
        }

        return [
            'id' => $payment->id,
            'type_key' => $isModulePurchase ? 'module_purchase' : 'subscription',
            'type_label' => $isModulePurchase ? 'Module Purchase' : 'Subscription Payment',
            'module_title' => $module?->title ?? '-',
            'module_thumb' => $module?->thumbnail_url,
            'instructor' => $instructor?->name ?? '-',
            'learner' => $learner?->name ?? 'Unknown learner',
            'learner_email' => $learner?->email ?? 'No email',
            'learner_avatar' => $learnerAvatar,
            'user' => $learner?->name ?? 'Unknown user',
            'email' => $learner?->email ?? 'No email',
            'amount' => (float) ($payment->amount ?? 0),
            'method' => $method,
            'status' => $status,
            'created_at' => $payment->created_at?->format('M d, Y') ?? 'Unknown date',
            'created_at_value' => $payment->created_at?->format('Y-m-d') ?? null,
            'reference' => $payment->transaction_id ?? '-',
            'show_url' => route('admin.payments.show', $payment),
            'search_blob' => strtolower(implode(' ', array_filter([
                $payment->id,
                $isModulePurchase ? 'module purchase' : 'subscription payment',
                $module?->title,
                $instructor?->name,
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
            actionRoutes: {
                archive: @js(route('admin.payments.archive', ['payment' => '__ID__'])),
                destroy: @js(route('admin.payments.destroy', ['payment' => '__ID__'])),
            },
        })"
         class="space-y-8">

        @foreach(['success','error','warning'] as $type)
            @if(session($type))
                @php
                    $cfg = [
                        'success' => ['bg' => 'bg-emerald-50 border-emerald-200 text-emerald-700', 'icon' => 'M5 13l4 4L19 7'],
                        'error' => ['bg' => 'bg-rose-50 border-rose-200 text-rose-700', 'icon' => 'M6 18L18 6M6 6l12 12'],
                        'warning' => ['bg' => 'bg-amber-50 border-amber-200 text-amber-700', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    ];
                @endphp
                <div class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm {{ $cfg[$type]['bg'] }}">
                    <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg[$type]['icon'] }}"/>
                    </svg>
                    {{ session($type) }}
                </div>
            @endif
        @endforeach

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $cards = [
                    [
                        'label' => 'Total Revenue',
                        'value' => null,
                        'valueKey' => 'total_revenue',
                        'type' => 'currency',
                        'icon' => 'currency',
                        'cardClass' => 'border-brand-200 bg-gradient-to-br from-brand-50 via-white to-brand-100/70',
                        'labelClass' => 'text-brand-700',
                    ],
                    [
                        'label' => 'Completed',
                        'value' => null,
                        'valueKey' => 'completed',
                        'type' => 'number',
                        'icon' => 'check',
                        'cardClass' => 'border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60',
                        'labelClass' => 'text-brand-600',
                    ],
                    [
                        'label' => 'Needs Review',
                        'value' => null,
                        'valueKey' => null,
                        'type' => 'computed-review',
                        'icon' => 'clock',
                        'cardClass' => 'border-brand-200 bg-gradient-to-br from-brand-100/60 via-white to-brand-50',
                        'labelClass' => 'text-brand-800',
                    ],
                    [
                        'label' => 'Failed',
                        'value' => null,
                        'valueKey' => 'failed',
                        'type' => 'number',
                        'icon' => 'warning',
                        'cardClass' => 'border-brand-300 bg-gradient-to-br from-brand-100 via-white to-brand-200/70',
                        'labelClass' => 'text-brand-900',
                    ],
                ];
            @endphp

            @foreach($cards as $card)
                <div class="rounded-[28px] border p-5 shadow-theme-xs {{ $card['cardClass'] }} min-h-[116px]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $card['labelClass'] }}">{{ $card['label'] }}</p>
                            <p class="mt-2 text-4xl leading-none font-bold text-gray-900">
                                @if($card['type'] === 'currency')
                                    <span x-text="formatCurrency(stats.{{ $card['valueKey'] }})"></span>
                                @elseif($card['type'] === 'computed-review')
                                    <span x-text="formatNumber((stats.pending || 0) + (stats.processing || 0))"></span>
                                @else
                                    <span x-text="formatNumber(stats.{{ $card['valueKey'] }})"></span>
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg {{ $card['icon'] === 'currency' ? 'from-brand-500 via-brand-700 to-brand-900 shadow-brand-200' : ($card['icon'] === 'check' ? 'from-brand-400 via-brand-600 to-brand-800 shadow-brand-200' : ($card['icon'] === 'clock' ? 'from-brand-600 via-brand-700 to-brand-900 shadow-brand-300' : 'from-brand-700 via-brand-800 to-brand-900 shadow-brand-300')) }}">
                            @if($card['icon'] === 'currency')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($card['icon'] === 'check')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @elseif($card['icon'] === 'clock')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @else
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h2 class="mt-2 text-xl font-bold text-gray-900">Payments Table</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6" data-testid="admin-table-filter-bar">
                        <label class="block xl:col-span-2">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
                            <input x-model.debounce.150ms="filters.search"
                                @input="page = 1"
                                   type="text"
                                   placeholder="Type, module, name, ref..."
                                   class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Type</span>
                                <select x-model="filters.type" @change="page = 1"
                                    class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                                <option value="">All types</option>
                                <option value="module_purchase">Module Purchase</option>
                                <option value="subscription">Subscription Payment</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Method</span>
                                <select x-model="filters.method" @change="page = 1"
                                    class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
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
                                <select x-model="filters.status" @change="page = 1"
                                    class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
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
                                        @change="page = 1"
                                       type="date"
                                       class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            </label>
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</span>
                                <input x-model="filters.dateTo"
                                        @change="page = 1"
                                       type="date"
                                       class="w-full px-4 py-3 text-sm text-gray-900 transition bg-white border border-brand-100 shadow-sm outline-none rounded-2xl focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 px-6 py-4">
                <button type="button"
                        @click="resetFilters()"
                        class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                    Reset Filters
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No. #</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Method</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Ref</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <template x-for="(payment, index) in paginatedPayments" :key="payment.id">
                            <tr class="transition hover:bg-brand-50/55">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500" x-text="rowNumber(index)"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full"
                                          :class="typeClass(payment.type_key)"
                                          x-text="payment.type_label"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <template x-if="payment.learner_avatar">
                                            <img :src="payment.learner_avatar" alt="Learner avatar" class="object-cover w-10 h-10 border border-gray-200 rounded-full">
                                        </template>
                                        <template x-if="!payment.learner_avatar">
                                            <span class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold rounded-2xl bg-brand-100 text-brand-700"
                                                  x-text="initialFromName(payment.learner)"></span>
                                        </template>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900" x-text="payment.learner"></p>
                                            <p class="text-xs text-gray-500" x-text="payment.learner_email"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="formatCurrency(payment.amount)"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full"
                                          :class="methodClass(payment.method)"
                                          x-text="methodLabel(payment.method)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full"
                                          :class="statusClass(payment.status)"
                                          x-text="formatLabel(payment.status)"></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="payment.created_at"></td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500" x-text="truncate(payment.reference, 16)"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="payment.show_url"
                                                         class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50"
                                           title="View payment">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        <button type="button"
                                                @click="openActionModal('archive', payment.id, payment.reference)"
                                                class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50"
                                                title="Archive payment">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                                            </svg>
                                        </button>

                                        <button type="button"
                                                @click="openActionModal('delete', payment.id, payment.reference)"
                                                class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50"
                                                title="Delete payment">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredPayments.length === 0" x-cloak>
                            <td colspan="9" class="px-6 text-center py-14">
                                <div class="max-w-sm mx-auto">
                                    <div class="flex items-center justify-center w-16 h-16 mx-auto text-gray-400 bg-gray-100 rounded-full">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                    <button type="button" @click="prevPage()" :disabled="page === 1" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
                    <span class="text-sm text-gray-600">Page <span class="font-semibold" x-text="safePage"></span> of <span class="font-semibold" x-text="totalPages"></span></span>
                    <button type="button" @click="nextPage()" :disabled="page >= totalPages" class="rounded-lg border border-brand-200 px-3 py-1.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                </div>
            </div>
        </section>

        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" @keydown.escape.window="closeActionModal()">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeActionModal()"></div>
            <div class="relative w-full max-w-md p-6 bg-white border border-brand-100 shadow-2xl rounded-2xl">
                <h3 class="text-lg font-bold text-gray-900" x-text="confirmAction === 'delete' ? 'Delete Payment?' : 'Archive Payment?'"></h3>
                <p class="mt-2 text-sm text-gray-600">
                    <span x-show="confirmAction === 'archive'">Archive payment </span>
                    <span x-show="confirmAction === 'delete'">Permanently delete payment </span>
                    <span class="font-semibold" x-text="confirmReference || '#'+confirmTargetId"></span>?
                </p>
                <div class="flex items-center justify-end gap-2 mt-6">
                    <button type="button" @click="closeActionModal()" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-brand-200 rounded-lg hover:bg-brand-50">Cancel</button>
                    <button type="button" @click="submitAction()" :class="confirmAction === 'delete' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700'" class="px-4 py-2 text-sm font-semibold text-white rounded-lg">Confirm</button>
                </div>
            </div>
        </div>

        <form method="POST" x-ref="actionForm" class="hidden">
            @csrf
            <input type="hidden" name="_method" value="POST" x-ref="actionMethod">
        </form>
    </div>

    <script>
        function paymentManagementPage(config) {
            return {
                payments: config.payments || [],
                stats: config.stats || {},
                actionRoutes: config.actionRoutes || {},
                filters: {
                    search: '',
                    type: '',
                    method: '',
                    status: '',
                    dateFrom: '',
                    dateTo: '',
                },
                page: 1,
                perPage: 10,
                confirmOpen: false,
                confirmAction: 'archive',
                confirmTargetId: null,
                confirmReference: '',
                get filteredPayments() {
                    return this.payments.filter((payment) => {
                        const search = this.filters.search.trim().toLowerCase();
                        const matchesSearch = !search || payment.search_blob.includes(search);
                        const matchesType = !this.filters.type || payment.type_key === this.filters.type;
                        const matchesMethod = !this.filters.method || payment.method === this.filters.method;
                        const matchesStatus = !this.filters.status || payment.status === this.filters.status;
                        const matchesDateFrom = !this.filters.dateFrom || (payment.created_at_value && payment.created_at_value >= this.filters.dateFrom);
                        const matchesDateTo = !this.filters.dateTo || (payment.created_at_value && payment.created_at_value <= this.filters.dateTo);

                        return matchesSearch && matchesType && matchesMethod && matchesStatus && matchesDateFrom && matchesDateTo;
                    });
                },
                get totalPages() {
                    const pages = Math.ceil(this.filteredPayments.length / this.perPage);
                    return pages > 0 ? pages : 1;
                },
                get safePage() {
                    return Math.min(this.page, this.totalPages);
                },
                get paginatedPayments() {
                    const start = (this.safePage - 1) * this.perPage;
                    return this.filteredPayments.slice(start, start + this.perPage);
                },
                resetFilters() {
                    this.filters.search = '';
                    this.filters.type = '';
                    this.filters.method = '';
                    this.filters.status = '';
                    this.filters.dateFrom = '';
                    this.filters.dateTo = '';
                    this.page = 1;
                },
                rowNumber(index) {
                    return ((this.safePage - 1) * this.perPage) + index + 1;
                },
                prevPage() {
                    if (this.page > 1) {
                        this.page -= 1;
                    }
                },
                nextPage() {
                    if (this.page < this.totalPages) {
                        this.page += 1;
                    }
                },
                openActionModal(action, id, reference) {
                    this.confirmAction = action;
                    this.confirmTargetId = id;
                    this.confirmReference = reference || ('#' + id);
                    this.confirmOpen = true;
                },
                closeActionModal() {
                    this.confirmOpen = false;
                    this.confirmTargetId = null;
                    this.confirmReference = '';
                },
                submitAction() {
                    if (!this.confirmTargetId) {
                        return;
                    }

                    const routeTemplate = this.confirmAction === 'delete'
                        ? this.actionRoutes.destroy
                        : this.actionRoutes.archive;

                    this.$refs.actionForm.action = routeTemplate.replace('__ID__', this.confirmTargetId);
                    this.$refs.actionMethod.value = this.confirmAction === 'delete' ? 'DELETE' : 'POST';
                    this.$refs.actionForm.submit();
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
                initialFromName(name) {
                    const value = String(name || 'U').trim();
                    return value.length > 0 ? value.charAt(0).toUpperCase() : 'U';
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
                        gcash: 'bg-brand-50 text-brand-700',
                        paymaya: 'bg-brand-100 text-brand-800',
                        grab_pay: 'bg-brand-50 text-brand-800',
                        card: 'bg-brand-100 text-brand-700',
                        billease: 'bg-brand-200/70 text-brand-800',
                        bank_transfer: 'bg-gray-100 text-gray-600',
                        paymongo: 'bg-brand-100 text-brand-800',
                    }[method] || 'bg-gray-100 text-gray-600';
                },
                typeClass(typeKey) {
                    return {
                        module_purchase: 'bg-brand-100 text-brand-800',
                        subscription: 'bg-brand-50 text-brand-700',
                    }[typeKey] || 'bg-gray-100 text-gray-600';
                },
                statusClass(status) {
                    return {
                        completed: 'bg-emerald-100 text-emerald-700',
                        failed: 'bg-rose-100 text-rose-700',
                        refunded: 'bg-gray-100 text-gray-600',
                        pending: 'bg-amber-100 text-amber-700',
                        processing: 'bg-brand-100 text-brand-700',
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
