@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8 max-w-2xl">
    <h1 class="text-2xl font-bold mb-6">Subscription Details</h1>
    <div class="bg-white rounded shadow p-6 mb-4">
        <div class="mb-2"><b>User:</b> {{ $subscription->user->name ?? 'N/A' }} ({{ $subscription->user->email ?? '' }})</div>
        <div class="mb-2"><b>Plan:</b> <span class="capitalize">{{ $subscription->plan }}</span></div>
        <div class="mb-2"><b>Status:</b> <span class="capitalize">{{ $subscription->status }}</span></div>
        <div class="mb-2"><b>Start Date:</b> {{ $subscription->start_date }}</div>
        <div class="mb-2"><b>End Date:</b> {{ $subscription->end_date }}</div>
        <div class="mb-2"><b>Payments:</b>
            <ul class="list-disc ml-6">
                @foreach($subscription->payments as $payment)
                    <li>
                        <b>Amount:</b> ₱{{ number_format($payment->amount,2) }} | <b>Method:</b> {{ $payment->method }} | <b>Status:</b> {{ $payment->status }} | <b>Date:</b> {{ $payment->created_at }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <a href="{{ route('admin.subscriptions.index') }}" class="text-blue-600 underline">&larr; Back to list</a>
</div>
@endsection
