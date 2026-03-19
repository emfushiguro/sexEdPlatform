@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Subscription Plan</h1>
        <a href="{{ route('admin.subscribers.show-plan', $subscriptionPlan) }}" class="text-sm text-brand-600 hover:text-brand-700">Back to Plan</a>
    </div>

    <form method="POST" action="{{ route('admin.subscribers.update-plan', $subscriptionPlan) }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input name="name" value="{{ old('name', $subscriptionPlan->name) }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('description', $subscriptionPlan->description) }}</textarea>
            </div>
        </div>

        @php $prices = $subscriptionPlan->planPrices()->orderBy('id')->get(); @endphp
        @foreach($prices as $index => $price)
            <div class="grid gap-3 sm:grid-cols-6">
                <input type="hidden" name="prices[{{ $index }}][duration_mode]" value="{{ $price->duration_mode }}">
                <input type="hidden" name="prices[{{ $index }}][duration_unit]" value="{{ $price->duration_unit }}">
                <input type="hidden" name="prices[{{ $index }}][duration_count]" value="{{ $price->duration_count }}">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-gray-500">Label</label>
                    <input name="prices[{{ $index }}][duration_label]" value="{{ old('prices.' . $index . '.duration_label', $price->duration_label) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-gray-500">Amount Minor</label>
                    <input name="prices[{{ $index }}][amount_minor]" type="number" value="{{ old('prices.' . $index . '.amount_minor', $price->amount_minor) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-500">Currency</label>
                    <input name="prices[{{ $index }}][currency]" value="{{ old('prices.' . $index . '.currency', $price->currency) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="prices[{{ $index }}][is_default]" value="1" {{ $price->is_default ? 'checked' : '' }}>
                    Default
                </label>
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.subscribers.show-plan', $subscriptionPlan) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Cancel</a>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Save Plan</button>
        </div>
    </form>
</div>
@endsection
