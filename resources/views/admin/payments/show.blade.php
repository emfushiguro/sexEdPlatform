@extends('layouts.admin')
@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('content')

<div class="mb-5">
 <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
 Back to Payments
 </a>
</div>

@foreach(['success','error','warning'] as $type)
 @if(session($type))
 <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl border text-sm
 {{ $type === 'success' ? 'bg-success-50 border-success-200 text-success-700 ' : ($type === 'error' ? 'bg-error-50 border-error-200 text-error-700 ' : 'bg-warning-50 border-warning-200 text-warning-700 ') }}">
 {{ session($type) }}
 </div>
 @endif
@endforeach

@php
 $pv = is_object($payment->status) ? $payment->status->value : $payment->status;
 $payStatusMap = ['completed'=>'bg-success-50 text-success-700 ','failed'=>'bg-error-50 text-error-700 ','refunded'=>'bg-gray-100 text-gray-500 ','pending'=>'bg-warning-50 text-warning-700 ','processing'=>'bg-brand-50 text-brand-700 '];
 $methodLabels = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','card'=>'Credit/Debit Card','billease'=>'BillEase','bank_transfer'=>'Bank Transfer','paymongo'=>'PayMongo'];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
 <div class="xl:col-span-2 space-y-5">

 {{-- Payment Info --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center justify-between mb-5">
 <h3 class="text-base font-semibold text-gray-900 ">Payment Information</h3>
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payStatusMap[$pv] ?? 'bg-gray-100 text-gray-500 ' }}">{{ ucfirst($pv) }}</span>
 </div>
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
 <div><p class="text-xs text-gray-400 mb-0.5">Amount</p><p class="text-2xl font-bold text-success-600 ">&#8369;{{ number_format($payment->amount,2) }}</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Method</p><p class="text-sm font-semibold text-gray-900 ">{{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Date</p><p class="text-sm font-semibold text-gray-900 ">{{ $payment->created_at->format('M d, Y H:i') }}</p></div>
 <div class="sm:col-span-3"><p class="text-xs text-gray-400 mb-0.5">Transaction ID</p><p class="text-sm font-mono text-gray-700 ">{{ $payment->transaction_id ?? 'N/A' }}</p></div>
 @if($payment->paid_at)
 <div><p class="text-xs text-gray-400 mb-0.5">Paid At</p><p class="text-sm font-semibold text-gray-900 ">{{ $payment->paid_at->format('M d, Y H:i') }}</p></div>
 @endif
 @if($payment->refund_reason)
 <div class="sm:col-span-3"><p class="text-xs text-gray-400 mb-0.5">Refund Reason</p><p class="text-sm text-gray-700 ">{{ $payment->refund_reason }}</p></div>
 @endif
 </div>
 </div>

 {{-- User Info --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <h3 class="text-base font-semibold text-gray-900 mb-4">User Information</h3>
 <div class="flex items-center gap-4">
 <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-lg font-bold flex-shrink-0">
 {{ strtoupper(substr($payment->user->name ?? 'U', 0, 1)) }}
 </div>
 <div>
 <p class="text-sm font-bold text-gray-900 ">{{ $payment->user->name ?? 'N/A' }}</p>
 <p class="text-xs text-gray-400">{{ $payment->user->email ?? '' }}</p>
 </div>
 </div>
 </div>

 {{-- Internal Notes --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center justify-between mb-4">
 <h3 class="text-base font-semibold text-gray-900 ">Internal Notes</h3>
 </div>

 @php $internalNotes = $payment->payment_details['internal_notes'] ?? []; @endphp
 @if(!empty($internalNotes))
 <div class="space-y-3">
 @foreach(array_reverse($internalNotes) as $note)
 <div class="rounded-lg border border-gray-200 p-3">
 <p class="text-sm text-gray-700 ">{{ $note['note'] ?? '' }}</p>
 <p class="mt-2 text-xs text-gray-400 ">
 {{ $note['author_name'] ?? 'Admin' }} • {{ isset($note['created_at']) ? \Carbon\Carbon::parse($note['created_at'])->format('M d, Y h:i A') : 'Unknown date' }}
 </p>
 </div>
 @endforeach
 </div>
 @else
 <p class="text-sm text-gray-500 ">No internal notes yet.</p>
 @endif
 </div>

 {{-- Subscription Info --}}
 @if($payment->subscription)
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <h3 class="text-base font-semibold text-gray-900 mb-4">Subscription</h3>
 @php $sv = is_object($payment->subscription->status) ? $payment->subscription->status->value : $payment->subscription->status; @endphp
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
 <div><p class="text-xs text-gray-400 mb-0.5">Plan</p><p class="text-sm font-semibold text-gray-900 capitalize">{{ $payment->subscription->plan }}</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Status</p><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sv === 'active' ? 'bg-success-50 text-success-700 ' : 'bg-gray-100 text-gray-500 ' }}">{{ ucfirst($sv) }}</span></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Period</p><p class="text-sm font-semibold text-gray-900 ">{{ $payment->subscription->start_date }} &ndash; {{ $payment->subscription->end_date }}</p></div>
 </div>
 <div class="mt-4">
 <a href="{{ route('admin.subscribers.show', $payment->subscription) }}" class="inline-flex items-center gap-1.5 text-sm text-brand-500 hover:text-brand-600 transition-colors">
 View Full Subscription <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 </a>
 </div>
 </div>
 @endif
 </div>

 {{-- Actions Sidebar --}}
 <div>
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
 <h3 class="text-sm font-semibold text-gray-700 mb-4">Actions</h3>
 <div class="space-y-2">
 @if($payment->isPending() || $pv === 'processing')
 <form method="POST" action="{{ route('admin.payments.complete', $payment) }}" onsubmit="return confirm('Mark as completed and activate subscription?')">
 @csrf
 <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg bg-success-50 border border-success-200 text-sm font-medium text-success-700 hover:bg-success-100 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
 Mark as Completed
 </button>
 </form>
 @endif
 </div>
 </div>
 </div>
</div>
@endsection
