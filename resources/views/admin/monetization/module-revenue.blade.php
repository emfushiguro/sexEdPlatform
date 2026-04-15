@extends('layouts.admin')

@section('title', 'Module Revenue Dashboard')
@section('page-title', 'Module Revenue Dashboard')

@section('content')
    <div class="space-y-8">
        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Monetization</p>
                        <h1 class="mt-2 text-2xl font-bold text-gray-900">Module Revenue Dashboard</h1>
                    </div>
                    <a href="{{ route('admin.monetization.commission-settings.index') }}"
                       class="inline-flex items-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">
                        Manage Commission Settings
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.monetization.module-revenue.index') }}" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</span>
                        <select name="instructor_id" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All instructors</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected((string) request('instructor_id') === (string) $instructor->id)>{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Module</span>
                        <select name="module_id" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module->id }}" @selected((string) request('module_id') === (string) $module->id)>{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Payout Status</span>
                        <select name="payout_status" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                            <option value="">All statuses</option>
                            <option value="paid" @selected(request('payout_status') === 'paid')>Paid</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date From</span>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Date To</span>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-gray-100">
                    </label>

                    <div class="sm:col-span-2 xl:col-span-5 flex items-center justify-end gap-2">
                        <button type="submit" class="inline-flex h-[46px] items-center justify-center rounded-2xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Apply Filters</button>
                        <a href="{{ route('admin.monetization.module-revenue.index') }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-brand-200 bg-brand-50/60 px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100/70">Reset</a>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 px-6 py-6 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Module Revenue</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format((float) $stats['total_module_revenue'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Instructor Earnings</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format((float) $stats['total_instructor_earnings'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Platform Commission</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format((float) $stats['total_platform_commission'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Modules Sold</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_modules_sold']) }}</p>
                </div>
                <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Total Transactions</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format((int) $stats['total_transactions']) }}</p>
                </div>
            </div>
        </section>

        <section id="sales-transactions" class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Sales Transactions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Module</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Learner</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Module Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Platform Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Instructor Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Payment Context</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Payout</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Purchased Date</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
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
                            <tr class="transition hover:bg-brand-50/55">
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
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
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
                                <td class="px-6 py-3">
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Paid</span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ optional($tx->modulePurchase?->purchased_at ?: $tx->occurred_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.monetization.module-revenue.transactions.show', $tx) }}"
                                           class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-white transition hover:bg-brand-50"
                                           title="View transaction details"
                                           aria-label="View transaction details">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <form method="POST" action="{{ route('admin.monetization.module-revenue.archive', $tx) }}" onsubmit="return confirm('Archive this transaction?');">
                                            @csrf
                                            <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100" title="Archive transaction" aria-label="Archive transaction">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M6 8l1 10h10l1-10M9 8V6a1 1 0 011-1h4a1 1 0 011 1v2" />
                                                </svg>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.monetization.module-revenue.destroy', $tx) }}" onsubmit="return confirm('Delete this transaction permanently?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Delete transaction" aria-label="Delete transaction">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
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
        </section>

        <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Instructor Roll-up</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Sales</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Module Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Platform Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Instructor Earnings</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
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
                            <tr class="transition hover:bg-brand-50/55">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($instructorPhotoUrl)
                                            <img src="{{ $instructorPhotoUrl }}" alt="Instructor avatar" class="h-10 w-10 rounded-full border border-gray-200 object-cover">
                                        @else
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
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
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.monetization.module-revenue.instructors.show', $rollup->instructor_id) }}"
                                       class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-brand-200 bg-white transition hover:bg-brand-50"
                                       title="View instructor revenue details"
                                       aria-label="View instructor revenue details">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
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
        </section>
    </div>
@endsection
