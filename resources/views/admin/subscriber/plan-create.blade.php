@extends('layouts.admin')

@section('title', 'Create Plan')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Subscription Plan</h1>
        <a href="{{ route('admin.subscribers.index') }}" class="text-sm text-brand-600 hover:text-brand-700">Back to Subscribers</a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.subscribers.store-plan') }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('description') }}</textarea>
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Prices</h2>
            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-6">
                    <input type="hidden" name="prices[0][duration_mode]" value="preset">
                    <input type="hidden" name="prices[0][duration_unit]" value="month">
                    <input type="hidden" name="prices[0][duration_count]" value="1">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Label</label>
                        <input name="prices[0][duration_label]" value="{{ old('prices.0.duration_label', 'Monthly') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Amount Minor</label>
                        <input name="prices[0][amount_minor]" type="number" value="{{ old('prices.0.amount_minor', 0) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Currency</label>
                        <input name="prices[0][currency]" value="{{ old('prices.0.currency', 'PHP') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="prices[0][is_default]" value="1" checked>
                        Default
                    </label>
                </div>

                <div class="grid gap-3 sm:grid-cols-6">
                    <input type="hidden" name="prices[1][duration_mode]" value="preset">
                    <input type="hidden" name="prices[1][duration_unit]" value="year">
                    <input type="hidden" name="prices[1][duration_count]" value="1">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Label</label>
                        <input name="prices[1][duration_label]" value="{{ old('prices.1.duration_label', 'Yearly') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Amount Minor</label>
                        <input name="prices[1][amount_minor]" type="number" value="{{ old('prices.1.amount_minor', 0) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Currency</label>
                        <input name="prices[1][currency]" value="{{ old('prices.1.currency', 'PHP') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="prices[1][is_default]" value="1">
                        Default
                    </label>
                </div>
            </div>
        </div>

        <div>
            <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Entitlements</h2>
            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-5">
                    <input type="hidden" name="entitlements[0][value_type]" value="boolean">
                    <input type="hidden" name="entitlements[0][is_enabled]" value="1">
                    <input type="hidden" name="entitlements[0][is_unlimited]" value="1">
                    <input name="entitlements[0][feature_key]" value="unlimited_shields" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <input name="entitlements[0][feature_name]" value="Unlimited Shields" class="sm:col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <input name="entitlements[0][category]" value="core" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div class="grid gap-3 sm:grid-cols-5">
                    <input type="hidden" name="entitlements[1][value_type]" value="quota">
                    <input type="hidden" name="entitlements[1][is_enabled]" value="1">
                    <input type="hidden" name="entitlements[1][is_unlimited]" value="0">
                    <input name="entitlements[1][feature_key]" value="monthly_streak_savers_quota" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <input name="entitlements[1][feature_name]" value="Monthly Streak Savers" class="sm:col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <input name="entitlements[1][quota_value]" type="number" value="3" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <input name="entitlements[1][category]" value="limits" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.subscribers.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Cancel</a>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Create Plan</button>
        </div>
    </form>
</div>
@endsection
