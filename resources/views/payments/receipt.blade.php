@extends('layouts.learner-app')

@section('title', 'Payment Receipt')

@section('content')
<div class="max-w-3xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- Receipt Header -->
                    <div class="text-center border-b pb-6 mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Payment Receipt</h1>
                        <p class="text-gray-500 mt-1">Transaction ID: {{ $payment->transaction_id }}</p>
                    </div>

                    <!-- Receipt Status Badge -->
                    <div class="text-center mb-6">
                        @if($payment->isCompleted())
                            <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-lg font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Payment Successful
                            </span>
                        @elseif($payment->status->value === 'refunded')
                            <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-lg font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                                Refunded
                            </span>
                        @endif
                    </div>

                    <!-- Receipt Details -->
                    <div class="space-y-4">
                        <div class="flex justify-between py-3 border-b">
                            <span class="text-gray-600">Plan</span>
                            <span class="font-semibold text-gray-900">
                                @if($payment->subscription)
                                    {{ $payment->subscription->getPlanLabel() }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between py-3 border-b">
                            <span class="text-gray-600">Payment Method</span>
                            <span class="font-semibold text-gray-900">{{ ucfirst($payment->method) }}</span>
                        </div>

                        <div class="flex justify-between py-3 border-b">
                            <span class="text-gray-600">Payment Date</span>
                            <span class="font-semibold text-gray-900">
                                {{ $payment->paid_at ? $payment->paid_at->format('F d, Y h:i A') : $payment->created_at->format('F d, Y h:i A') }}
                            </span>
                        </div>

                        @if($payment->subscription)
                            <div class="flex justify-between py-3 border-b">
                                <span class="text-gray-600">Subscription Period</span>
                                <span class="font-semibold text-gray-900">
                                    {{ $payment->subscription->start_date->format('M d, Y') }} - 
                                    {{ $payment->subscription->end_date->format('M d, Y') }}
                                </span>
                            </div>
                        @endif

                        <div class="flex justify-between py-4 bg-gray-50 px-4 rounded-lg mt-4">
                            <span class="text-lg font-bold text-gray-900">Total Amount</span>
                            <span class="text-lg font-bold text-green-600">₱{{ number_format($payment->amount, 2) }}</span>
                        </div>
                    </div>

                    <!-- PayMongo Reference -->
                    @php
                        $paymongoRef = $payment->payment_details['paymongo_payment_id']
                            ?? $payment->payment_details['paymongo_link_id']
                            ?? null;
                    @endphp
                    @if($paymongoRef)
                        <div class="mt-6 p-4 bg-gray-100 rounded-lg">
                            <p class="text-sm text-gray-600">
                                <strong>PayMongo Reference:</strong> {{ $paymongoRef }}
                            </p>
                        </div>
                    @endif

                    <!-- Refund Policy Notice -->
                    @if($payment->isCompleted())
                        @php
                            $refundWindowDays = config('billing.subscription.refund_window_days', 3);
                            $refundDeadline   = ($payment->paid_at ?? $payment->created_at)->copy()->addDays($refundWindowDays);
                            $isRefundable     = now()->lt($refundDeadline);
                        @endphp
                        <div class="mt-6 p-4 {{ $isRefundable ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50 border border-gray-200' }} rounded-lg">
                            <p class="text-sm {{ $isRefundable ? 'text-yellow-800' : 'text-gray-600' }}">
                                @if($isRefundable)
                                    <strong>Refund Policy:</strong> You may request a refund until
                                    {{ $refundDeadline->format('F d, Y h:i A') }}
                                    (within {{ $refundWindowDays }} days of payment).
                                @else
                                    <strong>Note:</strong> The {{ $refundWindowDays }}-day refund period has ended.
                                @endif
                            </p>
                            @if($isRefundable)
                                <div class="mt-3">
                                    <form action="{{ route('subscription.refund') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="reason" value="Customer refund request via receipt">
                                        <button type="submit"
                                                onclick="return confirm('Request a refund? Your subscription will be cancelled immediately.')"
                                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                                            Request Refund
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="mt-8 flex justify-center space-x-4">
                        <button onclick="window.print()" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print Receipt
                        </button>
                        <a href="{{ route('payment.history') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded transition">
                            ← Back to History
                        </a>
                    </div>
                </div>
            </div>
</div>
@endsection
