<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    @if(!empty($giftPayment))
        <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70 sm:col-span-3">
            <p class="text-xs text-gray-500">Gift Recipient</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ data_get($giftPayment->payment_details, 'gift.beneficiary_name') }}</p>
        </div>
    @endif
    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Plan Name</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ !empty($giftPayment) ? data_get($giftPayment->payment_details, 'gift.plan_name') : $subscription->getPlanLabel() }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Starts</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ optional($subscription->start_date)->format('M d, Y') }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Duration</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ !empty($giftPayment) ? data_get($giftPayment->payment_details, 'gift.duration_label') : 'Until '.optional($subscription->end_date)->format('M d, Y') }}</p>
    </div>
</div>
