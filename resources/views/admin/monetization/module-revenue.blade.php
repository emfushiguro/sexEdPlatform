@extends('layouts.admin')

@section('title', 'Module Revenue Dashboard')
@section('page-title', 'Module Revenue Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Module Revenue Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Track sales, platform commission, and instructor earnings for paid modules.</p>
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
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Gross</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Earnings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Payout Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $tx->module?->title ?? 'Unknown Module' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $tx->instructor?->name ?? 'Unknown Instructor' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $tx->gross_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $tx->commission_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₱{{ number_format((float) $tx->instructor_earnings_amount, 2) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ ucfirst($tx->payout_status) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">
                                    @if($tx->payout_status === 'pending')
                                        <form method="POST" action="{{ route('admin.monetization.module-revenue.payout.update', $tx) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payout_status" value="payable">
                                            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Mark Payable
                                            </button>
                                        </form>
                                    @elseif($tx->payout_status === 'payable')
                                        <form method="POST" action="{{ route('admin.monetization.module-revenue.payout.update', $tx) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payout_status" value="paid">
                                            <input type="text" name="payout_batch_reference" placeholder="Batch ref" class="w-32 rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                                Mark Paid
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No transactions found for the selected filters.</td>
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
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Net Earnings</th>
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
