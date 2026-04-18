@extends('layouts.instructor-app')

@section('title', 'Instructor Payment History')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
	<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-2xl font-bold text-gray-900">Subscription Payment History</h1>
			<p class="mt-1 text-sm text-gray-500">Track all instructor subscription payments, statuses, and receipts.</p>
		</div>
		<a href="{{ route('instructor.subscriptions.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
			Back to subscriptions
		</a>
	</div>

	<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
		@if($payments->count() > 0)
			<div class="overflow-x-auto">
				<table class="min-w-full text-sm">
					<thead class="bg-gray-50 border-b border-gray-200">
						<tr>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Transaction</th>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Plan</th>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Amount</th>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Method</th>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Status</th>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Date</th>
							<th class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-[0.14em] text-xs">Actions</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100">
						@foreach($payments as $payment)
							@php
								$statusValue = (string) ($payment->status->value ?? $payment->status);
								$statusTone = match ($statusValue) {
									'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
									'pending', 'processing' => 'bg-amber-50 text-amber-700 border-amber-200',
									'refunded' => 'bg-blue-50 text-blue-700 border-blue-200',
									default => 'bg-rose-50 text-rose-700 border-rose-200',
								};
								$planName = $payment->subscription?->plan?->name
									?? data_get($payment->payment_details, 'plan_name')
									?? $payment->subscription?->getPlanLabel()
									?? 'Instructor Plan';
							@endphp
							<tr class="hover:bg-gray-50/70 transition-colors">
								<td class="px-5 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $payment->transaction_id }}</td>
								<td class="px-5 py-4 whitespace-nowrap text-gray-700">{{ $planName }}</td>
								<td class="px-5 py-4 whitespace-nowrap font-semibold text-gray-900">PHP {{ number_format((float) $payment->amount, 2) }}</td>
								<td class="px-5 py-4 whitespace-nowrap text-gray-600">{{ ucfirst((string) ($payment->method ?? 'n/a')) }}</td>
								<td class="px-5 py-4 whitespace-nowrap">
									<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusTone }}">
										{{ ucfirst(str_replace('_', ' ', $statusValue)) }}
									</span>
								</td>
								<td class="px-5 py-4 whitespace-nowrap text-gray-600">
									<span class="block text-gray-800 font-medium">{{ $payment->created_at->format('M d, Y') }}</span>
									@if($payment->paid_at)
										<span class="block text-xs text-emerald-600">{{ $payment->paid_at->format('h:i A') }}</span>
									@endif
								</td>
								<td class="px-5 py-4 whitespace-nowrap">
									@if($payment->isCompleted())
										<a href="{{ route('instructor.payments.receipt', $payment) }}" class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 transition hover:bg-purple-100">
											Receipt
										</a>
									@elseif($payment->isPending())
										<a href="{{ route('instructor.payments.pending', $payment) }}" class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
											Resume
										</a>
									@else
										<span class="text-xs text-gray-400">-</span>
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			@if($payments->hasPages())
				<div class="border-t border-gray-100 px-5 py-4 bg-gray-50/70">
					{{ $payments->links() }}
				</div>
			@endif
		@else
			<div class="px-6 py-20 text-center">
				<h2 class="text-xl font-semibold text-gray-900">No instructor payments yet</h2>
				<p class="mt-2 text-sm text-gray-500">When you subscribe to a paid instructor plan, transactions will appear here.</p>
				<a href="{{ route('instructor.subscriptions.index') }}" class="mt-5 inline-flex items-center rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
					View plans
				</a>
			</div>
		@endif
	</div>
</div>
@endsection
