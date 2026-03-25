@extends('layouts.admin')

@section('title', 'Subscription Details')
@section('page-title', 'Subscription Details')

@section('content')
 <div class="mb-5">
 <a href="{{ route('admin.subscribers.index') }}"
 class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
 Back to Subscribers
 </a>
 </div>

 @php
 $statusMap = ['active'=>'bg-success-50 text-success-700 ','cancelled'=>'bg-error-50 text-error-700 ','expired'=>'bg-gray-100 text-gray-500 ','pending'=>'bg-warning-50 text-warning-700 ','past_due'=>'bg-error-50 text-error-700 '];
 $statusVal = is_object($subscription->status) ? $subscription->status->value : $subscription->status;
 $statusClass = $statusMap[$statusVal] ?? 'bg-gray-100 text-gray-500 ';
 @endphp

 <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
 <div class="xl:col-span-2 space-y-5">
 {{-- Main Info --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center justify-between mb-6">
 <div class="flex items-center gap-4">
 <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-lg font-bold">
 {{ strtoupper(substr($subscription->user->name ?? '?', 0, 1)) }}
 </div>
 <div>
 <h2 class="text-lg font-bold text-gray-900 ">{{ $subscription->user->name ?? 'N/A' }}</h2>
 <p class="text-sm text-gray-400">{{ $subscription->user->email ?? '' }}</p>
 </div>
 </div>
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ ucfirst($statusVal) }}</span>
 </div>
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 py-4 border-t border-gray-100 ">
 <div><p class="text-xs text-gray-400 mb-0.5">Plan</p><p class="text-sm font-semibold text-gray-900 capitalize">{{ $subscription->plan }}</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Start Date</p><p class="text-sm font-semibold text-gray-900 ">{{ $subscription->start_date }}</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">End Date</p><p class="text-sm font-semibold text-gray-900 ">{{ $subscription->end_date }}</p></div>
 </div>
 </div>

 {{-- Payment History --}}
 <div id="payment-timeline" class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="px-6 py-4 border-b border-gray-100 ">
 <h3 class="text-base font-semibold text-gray-900 ">Payment History</h3>
 </div>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @forelse($subscription->payments as $payment)
 <tr class="hover:bg-gray-50 ">
 <td class="px-5 py-3 text-sm font-semibold text-gray-900 ">{{ number_format($payment->amount,2) }}</td>
 <td class="px-5 py-3 text-sm text-gray-500 capitalize">{{ $payment->method }}</td>
 <td class="px-5 py-3">
 @php $pv = is_object($payment->status) ? $payment->status->value : $payment->status; @endphp
 <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pv === 'completed' ? 'bg-success-50 text-success-700 ' : 'bg-gray-100 text-gray-500 ' }}">{{ ucfirst($pv) }}</span>
 </td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $payment->created_at->format('M d, Y H:i') }}</td>
 </tr>
 @empty
 <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400 ">No payments yet.</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>

 {{-- Sidebar --}}
 <div>
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
 <h3 class="text-sm font-semibold text-gray-700 mb-4">Quick Actions</h3>
 <div class="space-y-2">
 <a href="{{ route('admin.subscribers.show', $subscription) }}#payment-timeline"
 class="flex items-center gap-2 w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
 <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 Timeline
 </a>
 <a href="{{ route('admin.payments.index') }}?search={{ $subscription->user->email ?? '' }}"
 class="flex items-center gap-2 w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
 <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
 View Payments
 </a>
 </div>
 </div>
 </div>
 </div>
@endsection
