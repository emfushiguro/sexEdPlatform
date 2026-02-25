@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8 max-w-4xl">
    <h1 class="text-2xl font-bold mb-6">Subscription Details</h1>

    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Subscription Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <label class="font-medium text-gray-500">Plan Name</label>
                <div class="mt-1">{{ $subscription->getPlanLabel() }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Plan Slug</label>
                <div class="mt-1 font-mono text-xs text-gray-600">{{ $subscription->plan->slug ?? ($subscription->plan_id ? 'n/a' : 'free') }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Plan</label>
                <div class="mt-1">{{ $subscription->getPlanLabel() }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Status</label>
                <div class="mt-1 capitalize">{{ ucfirst($subscription->status) }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Start Date</label>
                <div class="mt-1">{{ $subscription->start_date ? $subscription->start_date->format('M d, Y') : 'N/A' }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">End Date</label>
                <div class="mt-1">{{ $subscription->end_date ? $subscription->end_date->format('M d, Y') : 'N/A' }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Trial Ends</label>
                <div class="mt-1">{{ $subscription->trial_ends_at ? $subscription->trial_ends_at->format('M d, Y') : 'N/A' }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Price Paid</label>
                <div class="mt-1">₱{{ number_format($subscription->price_paid, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">User Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <label class="font-medium text-gray-500">Name</label>
                <div class="mt-1">{{ $subscription->user->name ?? 'N/A' }}</div>
            </div>
            <div>
                <label class="font-medium text-gray-500">Email</label>
                <div class="mt-1">{{ $subscription->user->email ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Payments</h2>
        @if($subscription->payments && $subscription->payments->count())
            <table class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left">Amount</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Method</th>
                        <th class="px-4 py-2 text-left">Transaction ID</th>
                        <th class="px-4 py-2 text-left">Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscription->payments as $payment)
                        <tr>
                            <td class="px-4 py-2">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ ucfirst($payment->status) }}</td>
                            <td class="px-4 py-2">{{ $payment->method ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $payment->transaction_id ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i:s') : 'Not paid' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-gray-500">No payments found for this subscription.</div>
        @endif
    </div>

    @if($subscription->payments && $subscription->payments->count())
        <a href="{{ route('admin.payments.show', $subscription->payments->first()) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded block text-center">Back to Payment Details</a>
    @endif
</div>
@endsection
