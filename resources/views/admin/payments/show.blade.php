@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8 max-w-4xl">
    <h1 class="text-2xl font-bold mb-6">Payment Details</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Payment Details -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Payment Information</h2>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Amount</label>
                        <div class="text-xl font-bold text-green-600">₱{{ number_format($payment->amount,2) }}</div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <div class="mt-1">
                            @if($payment->status=='completed')
                                <span class="inline-block bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">Completed</span>
                            @elseif($payment->status=='failed')
                                <span class="inline-block bg-red-100 text-red-800 text-sm px-3 py-1 rounded-full">Failed</span>
                            @elseif($payment->status=='refunded')
                                <span class="inline-block bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded-full">Refunded</span>
                            @else
                                <span class="inline-block bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded-full">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="font-medium text-gray-500">Payment Method</label>
                        <div class="mt-1">
                            @php
                                $methodLabels = [
                                    'gcash' => 'GCash',
                                    'paymaya' => 'PayMaya',
                                    'grab_pay' => 'GrabPay',
                                    'card' => 'Credit/Debit Card',
                                    'billease' => 'BillEase',
                                    'bank_transfer' => 'Bank Transfer',
                                    'paymongo' => 'PayMongo'
                                ];
                                $methodLabel = $methodLabels[$payment->method] ?? ucfirst($payment->method);
                            @endphp
                            <span class="font-semibold">{{ $methodLabel }}</span>
                        </div>
                    </div>
                    <div>
                        <label class="font-medium text-gray-500">Transaction ID</label>
                        <div class="mt-1 font-mono text-xs">{{ $payment->transaction_id ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <label class="font-medium text-gray-500">Created Date</label>
                        <div class="mt-1">{{ $payment->created_at->format('M d, Y H:i:s') }}</div>
                    </div>
                    <div>
                        <label class="font-medium text-gray-500">Paid Date</label>
                        <div class="mt-1">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i:s') : 'Not paid' }}</div>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">User Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="font-medium text-gray-500">Name</label>
                        <div class="mt-1">{{ $payment->user->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <label class="font-medium text-gray-500">Email</label>
                        <div class="mt-1">{{ $payment->user->email ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Subscription Information -->
            @if($payment->subscription)
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Subscription Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="font-medium text-gray-500">Plan</label>
                        <div class="mt-1">{{ $payment->subscription->plan->name ?? $payment->subscription->getPlanLabel() }}</div>
                    </div>
                    <div>
                        <label class="font-medium text-gray-500">Start Date</label>
                        <div class="mt-1">{{ $payment->subscription->start_date->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <label class="font-medium text-gray-500">End Date</label>
                        <div class="mt-1">{{ $payment->subscription->end_date ? $payment->subscription->end_date->format('M d, Y') : 'N/A' }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.subscriptions.show-subscription', $payment->subscription) }}" class="text-blue-600 hover:text-blue-800 underline">View Full Subscription Details</a>
                </div>
            </div>
            @endif

            <!-- Payment Details -->
            @if($payment->payment_details)
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Technical Details</h2>
                <pre class="bg-gray-100 rounded p-3 text-xs overflow-x-auto">{{ json_encode($payment->payment_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif
        </div>

        <!-- Actions Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Actions</h2>
                
                @if($payment->status == 'completed')
                    <button onclick="openRefundModal()" class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded mb-2">
                        Process Refund
                    </button>
                @endif
                
                <a href="{{ route('admin.payments.index') }}" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded block text-center">
                    Back to Payments
                </a>
            </div>

            <!-- Notes -->
            @if($payment->notes)
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Notes</h2>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $payment->notes }}</div>
            </div>
            @endif

            <!-- Refund Information -->
            @if($payment->status === 'refunded' || $payment->refunds->isNotEmpty())
            <div class="bg-red-50 border border-red-200 rounded shadow p-6">
                <h2 class="text-lg font-semibold text-red-700 mb-4">🔄 Refund Information</h2>

                @php
                    $details      = $payment->payment_details ?? [];
                    $adminReason  = $details['refund_reason'] ?? null;
                    $refundedAt   = $details['refunded_at'] ?? null;
                    $refundedBy   = $details['refunded_by_admin'] ?? null;
                    $refundRecord = $payment->refunds->first();
                @endphp

                {{-- Reason --}}
                <div class="mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Reason</p>
                    @php $reason = $refundRecord?->reason ?? $adminReason; @endphp
                    @if($reason)
                        <p class="text-sm text-gray-800 bg-white border border-red-100 rounded px-3 py-2">
                            {{ $reason }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400 italic">No reason provided.</p>
                    @endif
                </div>

                {{-- Refund amount --}}
                @if($refundRecord)
                <div class="mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Amount Refunded</p>
                    <p class="text-sm font-bold text-red-600">₱{{ number_format($refundRecord->amount, 2) }}</p>
                </div>
                @endif

                {{-- Status --}}
                @if($refundRecord)
                <div class="mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Refund Status</p>
                    @php
                        $statusClasses = [
                            'completed'         => 'bg-green-100 text-green-800',
                            'pending'           => 'bg-yellow-100 text-yellow-800',
                            'failed'            => 'bg-red-100 text-red-800',
                            'manual_processing' => 'bg-blue-100 text-blue-800',
                        ];
                        $cls = $statusClasses[$refundRecord->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full {{ $cls }}">
                        {{ ucwords(str_replace('_', ' ', $refundRecord->status)) }}
                    </span>
                </div>
                @endif

                {{-- Refund ID --}}
                @if($refundRecord?->refund_id)
                <div class="mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Refund ID</p>
                    <p class="text-xs font-mono text-gray-600">{{ $refundRecord->refund_id }}</p>
                </div>
                @endif

                {{-- Date --}}
                @if($refundRecord?->processed_at || $refundedAt)
                <div class="mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Processed At</p>
                    <p class="text-sm text-gray-700">
                        {{ $refundRecord?->processed_at?->format('M d, Y H:i:s') ?? $refundedAt }}
                    </p>
                </div>
                @endif

                {{-- Admin notes if any --}}
                @if($refundRecord?->admin_notes)
                <div class="mb-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Admin Notes</p>
                    <p class="text-sm text-gray-700 bg-white border border-red-100 rounded px-3 py-2">
                        {{ $refundRecord->admin_notes }}
                    </p>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Refund Modal -->
@if($payment->status == 'completed')
<div id="refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900">Process Refund</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Refund amount: <strong>₱{{ number_format($payment->amount, 2) }}</strong><br>
                    Please provide a reason for the refund:
                </p>
            </div>
            <form method="POST" action="{{ route('admin.payments.refund', $payment) }}">
                @csrf
                <div class="mt-4">
                    <textarea name="reason" rows="3" class="w-full border rounded px-3 py-2" placeholder="Refund reason..." required></textarea>
                </div>
                <div class="items-center px-4 py-3">
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 mr-2">
                        Process Refund
                    </button>
                    <button type="button" onclick="closeRefundModal()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRefundModal() {
    document.getElementById('refundModal').classList.remove('hidden');
}

function closeRefundModal() {
    document.getElementById('refundModal').classList.add('hidden');
}
</script>
@endif
@endsection
