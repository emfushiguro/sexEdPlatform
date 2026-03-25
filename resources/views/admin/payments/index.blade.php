@extends('layouts.admin')
@section('title', 'Payment Management')
@section('page-title', 'Payment Management')
@section('content')

@foreach(['success','error','warning'] as $type)
 @if(session($type))
 @php $cfg = ['success'=>['bg'=>'bg-success-50 border-success-200 text-success-700 ','icon'=>'M5 13l4 4L19 7'],'error'=>['bg'=>'bg-error-50 border-error-200 text-error-700 ','icon'=>'M6 18L18 6M6 6l12 12'],'warning'=>['bg'=>'bg-warning-50 border-warning-200 text-warning-700 ','icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z']]; @endphp
 <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl border {{ $cfg[$type]['bg'] }} text-sm">
 <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg[$type]['icon'] }}"/></svg>
 {{ session($type) }}
 </div>
 @endif
@endforeach

{{-- Stat Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
 @php
 $pCards = [
 ['label'=>'Total Revenue', 'value'=>'&#8369;'.number_format($stats['total_revenue'],2), 'bg'=>'bg-success-50 ','color'=>'text-success-600 ','icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
 ['label'=>'Completed', 'value'=>$stats['completed'], 'bg'=>'bg-brand-50 ', 'color'=>'text-brand-600 ', 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
 ];
 @endphp
 @foreach($pCards as $c)
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
 <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
 <svg class="w-5 h-5 {{ $c['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/></svg>
 </div>
 <p class="text-xl font-bold text-gray-900 ">{!! $c['value'] !!}</p>
 <p class="text-xs text-gray-400 mt-0.5">{{ $c['label'] }}</p>
 </div>
 @endforeach
</div>

{{-- Table Card --}}
<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="px-6 py-4 border-b border-gray-100 ">
 <h3 class="text-base font-semibold text-gray-900 ">All Payments</h3>
 </div>
 @include('admin.partials.table-filter-bar', ['label' => 'Payments Filters', 'hint' => 'Filter by method, status, date range, and user'])
 <form method="GET" class="px-6 py-4 border-b border-gray-100 ">
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
 <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user..."
 class="px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 <select name="method" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option value="all">All Methods</option>
 <option value="gcash" @selected(request('method')=='gcash')>GCash</option>
 <option value="paymaya" @selected(request('method')=='paymaya')>PayMaya</option>
 <option value="grab_pay" @selected(request('method')=='grab_pay')>GrabPay</option>
 <option value="card" @selected(request('method')=='card')>Card</option>
 <option value="billease" @selected(request('method')=='billease')>BillEase</option>
 <option value="bank_transfer" @selected(request('method')=='bank_transfer')>Bank Transfer</option>
 </select>
 <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option value="all">All Status</option>
 <option value="pending" @selected(request('status')=='pending')>Pending</option>
 <option value="processing" @selected(request('status')=='processing')>Processing</option>
 <option value="completed" @selected(request('status')=='completed')>Completed</option>
 <option value="failed" @selected(request('status')=='failed')>Failed</option>
 <option value="refunded" @selected(request('status')=='refunded')>Refunded</option>
 </select>
 <input type="date" name="date_from" value="{{ request('date_from') }}"
 class="px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <div class="flex gap-2">
 <input type="date" name="date_to" value="{{ request('date_to') }}"
 class="flex-1 px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors">Filter</button>
 <a href="{{ route('admin.payments.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Clear</a>
 </div>
 </div>
 </form>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ref</th>
 <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @php
 $methodLabels = ['gcash'=>'GCash','paymaya'=>'PayMaya','grab_pay'=>'GrabPay','card'=>'Card','billease'=>'BillEase','bank_transfer'=>'Bank Transfer','paymongo'=>'PayMongo'];
 $methodColors = ['gcash'=>'bg-brand-50 text-brand-700 ','paymaya'=>'bg-success-50 text-success-700 ','grab_pay'=>'bg-teal-50 text-teal-700 ','card'=>'bg-purple-50 text-purple-700 ','billease'=>'bg-indigo-50 text-indigo-700 ','bank_transfer'=>'bg-gray-100 text-gray-600 ','paymongo'=>'bg-brand-50 text-brand-700 '];
 $payStatusMap = ['completed'=>'bg-success-50 text-success-700 ','failed'=>'bg-error-50 text-error-700 ','refunded'=>'bg-gray-100 text-gray-500 ','pending'=>'bg-warning-50 text-warning-700 ','processing'=>'bg-brand-50 text-brand-700 '];
 @endphp
 @forelse($payments as $payment)
 @php $pv = is_object($payment->status) ? $payment->status->value : $payment->status; @endphp
 <tr class="hover:bg-gray-50 transition-colors">
 <td class="px-5 py-3 text-sm font-semibold text-gray-500 ">
 {{ (($payments->currentPage() - 1) * $payments->perPage()) + $loop->iteration }}
 </td>
 <td class="px-5 py-3">
 <div class="flex items-center gap-2.5">
 <div class="w-7 h-7 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-xs font-bold flex-shrink-0">
 {{ strtoupper(substr($payment->user->name ?? 'U', 0, 1)) }}
 </div>
 <div>
 <p class="text-sm font-semibold text-gray-900 leading-none">{{ $payment->user->name ?? 'N/A' }}</p>
 <p class="text-xs text-gray-400 mt-0.5">{{ $payment->user->email ?? '' }}</p>
 </div>
 </div>
 </td>
 <td class="px-5 py-3 text-sm font-semibold text-gray-900 ">&#8369;{{ number_format($payment->amount,2) }}</td>
 <td class="px-5 py-3">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $methodColors[$payment->method] ?? 'bg-gray-100 text-gray-500 ' }}">{{ $methodLabels[$payment->method] ?? ucfirst($payment->method) }}</span>
 </td>
 <td class="px-5 py-3">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payStatusMap[$pv] ?? 'bg-gray-100 text-gray-500 ' }}">{{ ucfirst($pv) }}</span>
 </td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $payment->created_at->format('M d, Y') }}</td>
 <td class="px-5 py-3 text-xs font-mono text-gray-400 ">{{ Str::limit($payment->transaction_id ?? '-', 12) }}</td>
 <td class="px-5 py-3">
 <div class="flex items-center justify-end gap-1">
 <a href="{{ route('admin.payments.show', $payment) }}" title="View" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
 </a>
 @if($payment->isPending() || $pv === 'processing')
 <form method="POST" action="{{ route('admin.payments.complete', $payment) }}" class="inline" onsubmit="return confirm('Mark as completed and activate subscription?')">
 @csrf
 <button type="submit" title="Reconcile" class="p-1.5 rounded-lg text-gray-400 hover:bg-success-50 hover:text-success-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
 </button>
 </form>
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400 ">No payments found.</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 @if($payments->hasPages())
 <div class="px-6 py-4 border-t border-gray-100 ">
 <div class="flex items-center justify-between gap-4">
 <p class="text-xs text-gray-500 ">Showing {{ $payments->firstItem() }}-{{ $payments->lastItem() }} of {{ $payments->total() }}</p>
 {{ $payments->withQueryString()->links() }}
 </div>
 </div>
 @endif
</div>

@endsection
