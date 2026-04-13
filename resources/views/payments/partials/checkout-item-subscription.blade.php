<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Plan Name</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $subscription->getPlanLabel() }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Starts</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ optional($subscription->start_date)->format('M d, Y') }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Duration</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">Until {{ optional($subscription->end_date)->format('M d, Y') }}</p>
    </div>
</div>
