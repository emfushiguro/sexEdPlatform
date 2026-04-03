@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
 <h1 class="text-2xl font-bold text-gray-900 mb-8">Subscription Details</h1>
 @php
 $currentPlan = $subscription->relationLoaded('plan')
 ? $subscription->getRelation('plan')
 : $subscription->plan()->first();
 @endphp

 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-8 mb-8">
 <h2 class="text-lg font-bold text-gray-900 mb-6">Subscription Information</h2>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Plan Name</label>
 <div class="font-bold text-gray-900 ">{{ $subscription->getPlanLabel() }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Plan Slug</label>
 <div class="font-mono text-xs text-gray-600 ">{{ $currentPlan?->slug ?? ($subscription->plan_id ? 'n/a' : 'free') }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Plan</label>
 <div class="font-bold text-gray-900 ">{{ $subscription->getPlanLabel() }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
 <div class="capitalize font-bold text-gray-900 ">{{ ucfirst($subscription->status->value) }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
 <div class="text-gray-900 ">{{ $subscription->start_date ? $subscription->start_date->format('M d, Y') : 'N/A' }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
 <div class="text-gray-900 ">{{ $subscription->end_date ? $subscription->end_date->format('M d, Y') : 'N/A' }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Trial Ends</label>
 <div class="text-gray-900 ">{{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('M d, Y') : 'N/A' }}</div>
 @if($subscription->start_date && $subscription->trial_ends_at)
 @php
 $trialDays = $subscription->start_date->diffInDays($subscription->trial_ends_at);
 @endphp
 <div class="text-xs text-gray-500 ">({{ $trialDays }} day{{ $trialDays == 1 ? '' : 's' }} trial)</div>
 @endif
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Price Paid</label>
 <div class="font-bold text-gray-900 ">₱{{ number_format($subscription->price_paid, 2) }}</div>
 </div>
 </div>
 </div>

 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-8 mb-8">
 <h2 class="text-lg font-bold text-gray-900 mb-6">User Information</h2>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Name</label>
 <div class="font-bold text-gray-900 ">{{ $subscription->user->name ?? 'N/A' }}</div>
 </div>
 <div>
 <label class="block text-xs font-semibold text-gray-500 mb-1">Email</label>
 <div class="text-gray-900 ">{{ $subscription->user->email ?? 'N/A' }}</div>
 </div>
 </div>
 </div>

 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-8 mb-8">
 <h2 class="text-lg font-bold text-gray-900 mb-6">Payments</h2>
 @if($subscription->payments && $subscription->payments->count())
 <table class="min-w-full text-sm">
 <thead>
 <tr class="bg-gray-100 text-gray-700 ">
 <th class="px-6 py-3 text-left font-bold uppercase tracking-wider">Amount</th>
 <th class="px-6 py-3 text-left font-bold uppercase tracking-wider">Status</th>
 <th class="px-6 py-3 text-left font-bold uppercase tracking-wider">Method</th>
 <th class="px-6 py-3 text-left font-bold uppercase tracking-wider">Transaction ID</th>
 <th class="px-6 py-3 text-left font-bold uppercase tracking-wider">Paid Date</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-200 ">
 <tbody class="bg-white divide-y divide-gray-200 ">
 @foreach($subscription->payments as $payment)
 <tr>
 <td class="px-6 py-4">₱{{ number_format($payment->amount, 2) }}</td>
 <td class="px-6 py-4">{{ ucfirst($payment->status->value) }}</td>
 <td class="px-6 py-4">{{ $payment->method ?? 'N/A' }}</td>
 <td class="px-6 py-4">{{ $payment->transaction_id ?? 'N/A' }}</td>
 <td class="px-6 py-4">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i:s') : 'Not paid' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 @else
 <div class="text-gray-500 ">No payments found for this subscription.</div>
 @endif
 </div>

 @if($subscription->payments && $subscription->payments->count())
 <a href="{{ route('admin.payments.show', $subscription->payments->first()) }}" class="w-full block text-center px-6 py-3 rounded-lg bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold mt-2">Back to Payment Details</a>
 @endif
</div>
@endsection
