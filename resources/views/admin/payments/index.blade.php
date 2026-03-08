@extends('layouts.admin')
@section('title', 'Payment Management')
@section('page-title', 'Payment Management')
@section('content')

{{-- Stat Cards --}}
@php
    $actionStyles = [
        'view' => 'text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400',
        'complete' => 'text-gray-400 hover:bg-success-50 hover:text-success-600 dark:hover:bg-success-500/10 dark:hover:text-success-400',
        'refund' => 'text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400',
    ];

    $methodLabels = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','card'=>'Card','billease'=>'BillEase','bank_transfer'=>'Bank Transfer','paymongo'=>'PayMongo'];
    $methodColors = ['gcash'=>'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400','paymaya'=>'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400','grab_pay'=>'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400','card'=>'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400','billease'=>'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400','bank_transfer'=>'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400','paymongo'=>'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400'];
    $payStatusMap = ['completed'=>'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400','failed'=>'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400','refunded'=>'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400','pending'=>'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400','processing'=>'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400'];

    $pCards = [
        ['label'=>'Total Revenue', 'value'=>'&#8369;'.number_format($stats['total_revenue'],2), 'bg'=>'bg-success-50 dark:bg-success-500/10','color'=>'text-success-600 dark:text-success-400','ring'=>'ring-success-200 dark:ring-success-800','icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'Completed', 'value'=>$stats['completed'], 'bg'=>'bg-brand-50 dark:bg-brand-500/10','color'=>'text-brand-600 dark:text-brand-400','ring'=>'ring-brand-200 dark:ring-brand-800','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'Failed', 'value'=>$stats['failed'], 'bg'=>'bg-error-50 dark:bg-error-500/10','color'=>'text-error-600 dark:text-error-400','ring'=>'ring-error-200 dark:ring-error-800','icon'=>'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'Refunded', 'value'=>$stats['refunded'], 'bg'=>'bg-gray-100 dark:bg-white/5','color'=>'text-gray-500 dark:text-gray-400','ring'=>'ring-gray-200 dark:ring-gray-800','icon'=>'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach($pCards as $c)
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5 ring-1 {{ $c['ring'] }}">
        <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
            <svg class="w-5 h-5 {{ $c['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/></svg>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{!! $c['value'] !!}</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $c['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Table Card --}}
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">All Payments</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Track transactions, payment methods, and refund actions in one place.</p>
        </div>
        <div class="text-xs text-gray-400 dark:text-gray-500">
            {{ $payments->total() }} {{ \Illuminate\Support\Str::plural('payment', $payments->total()) }} listed
        </div>
    </div>
    <form method="GET" class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user..."
                   class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            <select name="method" class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                <option value="">All Methods</option>
                <option value="gcash" @selected(request('method')=='gcash')>GCash</option>
                <option value="paymaya" @selected(request('method')=='paymaya')>PayMaya</option>
                <option value="grab_pay" @selected(request('method')=='grab_pay')>GrabPay</option>
                <option value="card" @selected(request('method')=='card')>Card</option>
                <option value="billease" @selected(request('method')=='billease')>BillEase</option>
                <option value="bank_transfer" @selected(request('method')=='bank_transfer')>Bank Transfer</option>
            </select>
            <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                <option value="">All Status</option>
                <option value="pending" @selected(request('status')=='pending')>Pending</option>
                <option value="processing" @selected(request('status')=='processing')>Processing</option>
                <option value="completed" @selected(request('status')=='completed')>Completed</option>
                <option value="failed" @selected(request('status')=='failed')>Failed</option>
                <option value="refunded" @selected(request('status')=='refunded')>Refunded</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors shadow-theme-xs">Filter</button>
                <a href="{{ route('admin.payments.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Clear</a>
            </div>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-brand-50 dark:bg-brand-500/10">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">User</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Amount</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Method</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Ref</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-brand-700 dark:text-brand-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($payments as $payment)
                @php $pv = is_object($payment->status) ? $payment->status->value : $payment->status; @endphp
                <tr class="hover:bg-brand-50 dark:hover:bg-brand-500/10 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($payment->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-none">{{ $payment->user->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $payment->user->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white">&#8369;{{ number_format($payment->amount,2) }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $methodColors[$payment->method] ?? 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payStatusMap[$pv] ?? 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($pv) }}</span>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $payment->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-3 text-xs font-mono text-gray-400 dark:text-gray-500">{{ Str::limit($payment->transaction_id ?? '-', 12) }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.payments.show', $payment) }}" title="View" class="p-1.5 rounded-lg transition-colors {{ $actionStyles['view'] }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if($payment->isPending() || $pv === 'processing')
                            <form method="POST" action="{{ route('admin.payments.complete', $payment) }}" class="inline" onsubmit="return confirm('Mark as completed and activate subscription?')">
                                @csrf
                                <button type="submit" title="Mark Paid" class="p-1.5 rounded-lg transition-colors {{ $actionStyles['complete'] }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            @endif
                            @if($payment->isCompleted())
                            <button onclick="openRefundModal({{ $payment->id }})" title="Refund" class="p-1.5 rounded-lg transition-colors {{ $actionStyles['refund'] }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>

{{-- Refund Modal --}}
<div id="refundModal" class="fixed inset-0 z-[99999] hidden" x-data>
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeRefundModal()"></div>
    <div class="relative flex items-center justify-center min-h-full p-4">
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-2xl w-full max-w-md p-6">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Process Refund</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Provide a reason for the refund. This cannot be undone.</p>
            <form id="refundForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reason</label>
                    <textarea name="reason" rows="3" required placeholder="Refund reason..."
                              class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 resize-none transition"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRefundModal()" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-error-500 hover:bg-error-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openRefundModal(id) { document.getElementById('refundForm').action = '/admin/payments/' + id + '/refund'; document.getElementById('refundModal').classList.remove('hidden'); }
function closeRefundModal() { document.getElementById('refundModal').classList.add('hidden'); }
</script>
@endsection
