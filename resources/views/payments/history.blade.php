<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Transaction ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Plan
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Method
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $payment->transaction_id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($payment->subscription)
                                                    {{ $payment->subscription->getPlanLabel() }}
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₱{{ number_format($payment->amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ ucfirst($payment->method) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                                       ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                        ($payment->status === 'refunded' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $payment->created_at->format('M d, Y') }}
                                                @if($payment->paid_at)
                                                    <br><span class="text-xs text-green-600">Paid: {{ $payment->paid_at->format('M d, Y h:i A') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @if($payment->status === 'completed')
                                                    <a href="{{ route('payment.receipt', $payment) }}" 
                                                       class="text-blue-600 hover:text-blue-900">
                                                        View Receipt
                                                    </a>
                                                @elseif($payment->status === 'pending')
                                                    <a href="{{ route('payment.pending', $payment) }}" 
                                                       class="text-yellow-600 hover:text-yellow-900">
                                                        View Status
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No payment history</h3>
                            <p class="text-gray-500 mb-4">You haven't made any payments yet.</p>
                            <a href="{{ route('subscription.upgrade') }}" 
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition">
                                View Subscription Plans
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-6 text-center">
                <a href="{{ route('subscription.index') }}" class="text-blue-600 hover:text-blue-800">
                    ← Back to Subscription
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
