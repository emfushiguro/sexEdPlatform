<div class="rounded-xl border border-gray-200 bg-white p-4">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Connector module package</p>
            <h2 class="mt-1 text-lg font-bold text-gray-900">{{ $module->title }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ $connector->name }}</p>
        </div>
        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ $seatCapacity }} seats</span>
    </div>
    <p class="mt-4 text-xs leading-5 text-gray-600">This package is non-refundable and does not automatically assign seats to members. Seat assignment happens after successful payment.</p>
</div>
