@extends('layouts.learner-app')

@section('title', 'Payment History')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-8 relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500">Payment History</h1>
            <p class="text-sm text-gray-500 mt-2 font-medium">Track your subscription transactions and review receipts.</p>
        </div>
        <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 bg-white shadow-sm hover:shadow-md hover:border-purple-300 hover:text-purple-700 hover:-translate-y-0.5 transition-all duration-300 group">
            <svg class="w-4 h-4 mr-2 text-gray-400 group-hover:text-purple-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Back to Subscription
        </a>
    </div>

    <div class="bg-white/80 backdrop-blur-xl border border-purple-100 rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 relative z-10">
        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-purple-50/80 to-pink-50/80 border-b border-purple-100">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Transaction ID</th>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Plan</th>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Amount</th>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Method</th>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Status</th>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Date</th>
                            <th class="px-6 py-4 text-left font-bold text-purple-900 tracking-wide uppercase text-xs">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-purple-50 bg-white/50">
                        @foreach($payments as $payment)
                            <tr class="group hover:bg-purple-50/40 transition-colors duration-200">
                                <td class="px-6 py-5 whitespace-nowrap text-sm font-bold text-gray-900">
                                    {{ $payment->transaction_id }}
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    @if($payment->subscription)
                                        {{ $payment->subscription->getPlanLabel() }}
                                    @else
                                        <span class="text-gray-400 italic">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-600">
                                    ₱{{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600 font-medium whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-100 text-gray-700 shadow-sm">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        {{ ucfirst($payment->method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full border shadow-sm
                                        {{ $payment->status->value === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                           ($payment->status->value === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                           ($payment->status->value === 'refunded' ? 'bg-blue-50 text-blue-700 border-blue-200' : 
                                           'bg-red-50 text-red-700 border-red-200')) }}">
                                        {{ ucfirst($payment->status->value) }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-500">
                                    <span class="font-bold text-gray-700">{{ $payment->created_at->format('M d, Y') }}</span>
                                    @if($payment->paid_at)
                                        <div class="text-xs text-emerald-600 font-bold mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            {{ $payment->paid_at->format('h:i A') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-sm font-bold">
                                    @if($payment->isCompleted())
                                        <a href="{{ route('payment.receipt', $payment) }}" class="inline-flex items-center gap-1.5 text-purple-600 hover:text-pink-600 transition-colors group/link bg-purple-50 px-3 py-1.5 rounded-lg hover:bg-purple-100">
                                            <span>Receipt</span>
                                            <svg class="w-4 h-4 transform group-hover/link:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    @elseif($payment->isPending())
                                        <a href="{{ route('payment.pending', $payment) }}" class="inline-flex items-center gap-1.5 text-amber-600 hover:text-amber-800 transition-colors group/link bg-amber-50 px-3 py-1.5 rounded-lg hover:bg-amber-100">
                                            <span>Status</span>
                                            <svg class="w-4 h-4 transform group-hover/link:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </a>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="px-6 py-4 border-t border-purple-100 bg-purple-50/30">
                    {{ $payments->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-24 text-center flex flex-col items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-purple-100/50 via-transparent to-transparent -z-10"></div>
                <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-50 rounded-full flex items-center justify-center mb-6 ring-8 ring-purple-50 shadow-inner">
                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-2xl font-extrabold tracking-tight text-gray-900 mb-3">No payment history yet</h3>
                <p class="text-gray-500 mb-8 max-w-sm font-medium">When you subscribe to a plan, your secure payment records and downloadable receipts will appear here.</p>
                <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-8 py-3.5 rounded-xl text-sm font-bold text-white bg-brand-500 hover:bg-brand-600 hover:shadow-lg shadow-purple-200/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
                    Explore Subscription Plans
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
