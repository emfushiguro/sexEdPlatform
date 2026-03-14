@extends('layouts.admin')
@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('content')

<div class="mb-5">
    <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Payments
    </a>
</div>

@foreach(['success','error','warning'] as $type)
    @if(session($type))
    <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl border text-sm
        {{ $type === 'success' ? 'bg-success-50 border-success-200 text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400' : ($type === 'error' ? 'bg-error-50 border-error-200 text-error-700 dark:bg-error-500/10 dark:border-error-500/20 dark:text-error-400' : 'bg-warning-50 border-warning-200 text-warning-700 dark:bg-warning-500/10 dark:border-warning-500/20 dark:text-warning-400') }}">
        {{ session($type) }}
    </div>
    @endif
@endforeach

@php
    $pv = is_object($payment->status) ? $payment->status->value : $payment->status;
    $payStatusMap = ['completed'=>'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400','failed'=>'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400','refunded'=>'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400','pending'=>'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400','processing'=>'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400'];
    $methodLabels = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','card'=>'Credit/Debit Card','billease'=>'BillEase','bank_transfer'=>'Bank Transfer','paymongo'=>'PayMongo'];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 space-y-5">

        {{-- Payment Info --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Payment Information</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payStatusMap[$pv] ?? 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($pv) }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Amount</p><p class="text-2xl font-bold text-success-600 dark:text-success-400">&#8369;{{ number_format($payment->amount,2) }}</p></div>
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Method</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}</p></div>
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Date</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $payment->created_at->format('M d, Y H:i') }}</p></div>
                <div class="sm:col-span-3"><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Transaction ID</p><p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $payment->transaction_id ?? 'N/A' }}</p></div>
                @if($payment->paid_at)
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Paid At</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $payment->paid_at->format('M d, Y H:i') }}</p></div>
                @endif
                @if($payment->refund_reason)
                <div class="sm:col-span-3"><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Refund Reason</p><p class="text-sm text-gray-700 dark:text-gray-300">{{ $payment->refund_reason }}</p></div>
                @endif
            </div>
        </div>

        {{-- User Info --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">User Information</h3>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 text-lg font-bold flex-shrink-0">
                    {{ strtoupper(substr($payment->user->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $payment->user->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-400">{{ $payment->user->email ?? '' }}</p>
                </div>
                <a href="{{ route('admin.users.show', $payment->user) }}" class="ml-auto inline-flex items-center gap-1.5 text-xs text-brand-500 hover:text-brand-600 dark:text-brand-400 transition-colors">
                    View Profile <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        {{-- Subscription Info --}}
        @if($payment->subscription)
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Subscription</h3>
            @php $sv = is_object($payment->subscription->status) ? $payment->subscription->status->value : $payment->subscription->status; @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Plan</p><p class="text-sm font-semibold text-gray-900 dark:text-white capitalize">{{ $payment->subscription->plan }}</p></div>
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Status</p><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sv === 'active' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($sv) }}</span></div>
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Period</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $payment->subscription->start_date }} &ndash; {{ $payment->subscription->end_date }}</p></div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.subscribers.show', $payment->subscription) }}" class="inline-flex items-center gap-1.5 text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400 transition-colors">
                    View Full Subscription <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Actions Sidebar --}}
    <div>
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Actions</h3>
            <div class="space-y-2">
                @if($payment->isPending() || $pv === 'processing')
                <form method="POST" action="{{ route('admin.payments.complete', $payment) }}" onsubmit="return confirm('Mark as completed and activate subscription?')">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg bg-success-50 dark:bg-success-500/10 border border-success-200 dark:border-success-800 text-sm font-medium text-success-700 dark:text-success-400 hover:bg-success-100 dark:hover:bg-success-500/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Mark as Completed
                    </button>
                </form>
                @endif
                @if($payment->isCompleted())
                <button onclick="openRefundModal({{ $payment->id }})" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg bg-error-50 dark:bg-error-500/10 border border-error-200 dark:border-error-800 text-sm font-medium text-error-700 dark:text-error-400 hover:bg-error-100 dark:hover:bg-error-500/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Process Refund
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Refund Modal --}}
<div id="refundModal" class="fixed inset-0 z-[99999] hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeRefundModal()"></div>
    <div class="relative flex items-center justify-center min-h-full p-4">
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-2xl w-full max-w-md p-6">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Process Refund</h3>
            <form id="refundForm" method="POST">
                @csrf
                <textarea name="reason" rows="3" required placeholder="Refund reason..."
                          class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 resize-none mb-4"></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRefundModal()" class="px-4 py-2 rounded-lg text-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-error-500 hover:bg-error-600 text-white text-sm font-medium">Process Refund</button>
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
