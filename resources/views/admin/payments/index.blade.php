@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Payment Management</h1>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-green-600">₱{{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="text-sm text-gray-500">Total Revenue</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-green-600">{{ $stats['completed'] }}</div>
            <div class="text-sm text-gray-500">Completed</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-red-600">{{ $stats['failed'] }}</div>
            <div class="text-sm text-gray-500">Failed</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-gray-600">{{ $stats['refunded'] }}</div>
            <div class="text-sm text-gray-500">Refunded</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white p-3 rounded shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user..." class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm">
            <select name="method" class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm">
                <option value="all">All Methods</option>
                <option value="gcash" @selected(request('method')=='gcash')>GCash</option>
                <option value="paymaya" @selected(request('method')=='paymaya')>PayMaya</option>
                <option value="grab_pay" @selected(request('method')=='grab_pay')>GrabPay</option>
                <option value="card" @selected(request('method')=='card')>Card</option>
                <option value="billease" @selected(request('method')=='billease')>BillEase</option>
                <option value="bank_transfer" @selected(request('method')=='bank_transfer')>Bank Transfer</option>
                <option value="paymongo" @selected(request('method')=='paymongo')>PayMongo (Generic)</option>
            </select>
            <select name="status" class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm">
                <option value="all">All Status</option>
                <option value="pending" @selected(request('status')=='pending')>Pending</option>
                <option value="processing" @selected(request('status')=='processing')>Processing</option>
                <option value="completed" @selected(request('status')=='completed')>Completed</option>
                <option value="failed" @selected(request('status')=='failed')>Failed</option>
                <option value="refunded" @selected(request('status')=='refunded')>Refunded</option>
            </select>
            <div class="flex gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From Date" class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm w-1/2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To Date" class="border rounded px-2 py-1 focus:ring focus:border-blue-400 text-sm w-1/2">
            </div>
            <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded shadow text-sm md:col-span-4 w-full md:w-auto mt-2 md:mt-0" type="submit">Filter</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded shadow bg-white">
    <table class="min-w-full border border-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 border font-semibold text-sm">User</th>
                <th class="px-4 py-3 border font-semibold text-sm">Amount</th>
                <th class="px-4 py-3 border font-semibold text-sm">Method</th>
                <th class="px-4 py-3 border font-semibold text-sm">Status</th>
                <th class="px-4 py-3 border font-semibold text-sm">Date</th>
                <th class="px-4 py-3 border font-semibold text-sm">Transaction ID</th>
                <th class="px-4 py-3 border font-semibold text-sm">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $payment)
            <tr class="hover:bg-gray-50 align-middle">
                <td class="border px-4 py-3 align-middle">
                    <div class="font-semibold text-base">{{ $payment->user->name ?? 'N/A' }}</div>
                    <div class="text-xs text-gray-500">{{ $payment->user->email ?? '' }}</div>
                </td>
                <td class="border px-4 py-3 align-middle">₱{{ number_format($payment->amount,2) }}</td>
                <td class="border px-4 py-3 align-middle">
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
                        $methodColors = [
                            'gcash' => 'bg-blue-100 text-blue-800',
                            'paymaya' => 'bg-green-100 text-green-800',
                            'grab_pay' => 'bg-emerald-100 text-emerald-800',
                            'card' => 'bg-purple-100 text-purple-800',
                            'billease' => 'bg-indigo-100 text-indigo-800',
                            'bank_transfer' => 'bg-gray-100 text-gray-800',
                            'paymongo' => 'bg-blue-100 text-blue-800'
                        ];
                        $methodColor = $methodColors[$payment->method] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-block {{ $methodColor }} text-xs px-2 py-1 rounded">{{ $methodLabel }}</span>
                </td>
                <td class="border px-4 py-3 align-middle">
                    @if($payment->status=='completed')
                        <span class="inline-block bg-green-100 text-green-800 text-xs px-3 py-1 rounded font-semibold">Completed</span>
                    @elseif($payment->status=='failed')
                        <span class="inline-block bg-red-100 text-red-800 text-xs px-3 py-1 rounded font-semibold">Failed</span>
                    @elseif($payment->status=='refunded')
                        <span class="inline-block bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded font-semibold">Refunded</span>
                    @else
                        <span class="inline-block bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded font-semibold">{{ ucfirst($payment->status) }}</span>
                    @endif
                </td>
                <td class="border px-4 py-3 align-middle text-sm">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                <td class="border px-4 py-3 align-middle text-xs text-gray-600">{{ $payment->transaction_id ?? 'N/A' }}</td>
                <td class="border px-4 py-3 align-middle">
                    <a href="{{ route('admin.payments.show', $payment) }}" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-1 rounded text-xs font-semibold mr-1">View</a>
                    @if($payment->status == 'pending' || $payment->status == 'processing')
                        <form method="POST" action="{{ route('admin.payments.complete', $payment) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-100 hover:bg-green-200 text-green-800 px-4 py-1 rounded text-xs font-semibold" onclick="return confirm('Mark this payment as completed and activate subscription?')">Mark as Paid</button>
                        </form>
                    @endif
                    @if($payment->status == 'completed')
                        <button onclick="openRefundModal({{ $payment->id }})" class="bg-red-100 hover:bg-red-200 text-red-800 px-4 py-1 rounded text-xs font-semibold">Refund</button>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-4">No payments found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
</div>

<!-- Refund Modal -->
<div id="refundModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900">Process Refund</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Please provide a reason for the refund:</p>
            </div>
            <form id="refundForm" method="POST">
                @csrf
                <div class="mt-4">
                    <textarea name="reason" rows="3" class="w-full border rounded px-3 py-2" placeholder="Refund reason..." required></textarea>
                </div>
                <div class="items-center px-4 py-3">
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 mr-2">Process Refund</button>
                    <button type="button" onclick="closeRefundModal()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRefundModal(paymentId) {
    document.getElementById('refundForm').action = '/admin/payments/' + paymentId + '/refund';
    document.getElementById('refundModal').classList.remove('hidden');
}

function closeRefundModal() {
    document.getElementById('refundModal').classList.add('hidden');
}
</script>
@endsection
