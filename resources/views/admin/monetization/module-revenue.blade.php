@extends('layouts.admin')

@section('title', 'Module Revenue Dashboard')
@section('page-title', 'Module Revenue Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Module Revenue Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Track module sales, instructor earnings, learner purchases, and payment references with full transparency.</p>
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

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Total Module Sales</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_module_sales']) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Total Gross Revenue</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format($stats['total_gross_revenue'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">Total Platform Commission</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format($stats['total_platform_commission'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-600">Total Instructor Earnings</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">₱{{ number_format($stats['total_instructor_earnings'], 2) }}</p>
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
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Learner</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Sale Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Platform Fee</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Payment</th>
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
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
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
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Instructor Rollup</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Sales</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Gross</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor Earnings</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($rollups as $rollup)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $rollup->instructor?->name ?? 'Unknown Instructor' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ number_format((int) $rollup->sales_count) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $rollup->gross_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $rollup->commission_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $rollup->earnings_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No instructor rollup data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
