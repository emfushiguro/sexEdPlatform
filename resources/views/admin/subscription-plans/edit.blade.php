@extends('layouts.admin')

@section('title', 'Edit: ' . $subscriptionPlan->name)
@section('page-title', 'Edit Plan')

@section('content')
    {{-- Back link --}}
    <div class="mb-5">
        <a href="{{ route('admin.subscription-plans.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Plans
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Plan Details</h2>

            <form method="POST" action="{{ route('admin.subscription-plans.update', $subscriptionPlan) }}">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="mb-5 rounded-xl bg-error-50 dark:bg-error-500/10 border border-error-200 dark:border-error-500/20 p-4">
                        <p class="text-sm font-medium text-error-700 dark:text-error-400 mb-1">Please fix the following errors:</p>
                        <ul class="list-disc list-inside text-sm text-error-600 dark:text-error-400 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Name --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Plan Name <span class="text-error-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $subscriptionPlan->name) }}" required
                           class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 @error('name') border-error-400 @enderror"/>
                    @error('name')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                </div>

                {{-- Description --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 resize-none">{{ old('description', $subscriptionPlan->description) }}</textarea>
                </div>

                {{-- Price & Trial --}}
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Price (₱/month) <span class="text-error-500">*</span></label>
                        <input type="number" name="price" value="{{ old('price', $subscriptionPlan->price) }}" min="0" step="0.01" required
                               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 @error('price') border-error-400 @enderror"/>
                        @error('price')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Trial Days</label>
                        <input type="number" name="trial_days" value="{{ old('trial_days', $subscriptionPlan->trial_days ?? 0) }}" min="0" max="365"
                               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"/>
                    </div>
                </div>

                {{-- Sort Order --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $subscriptionPlan->sort_order) }}" min="0"
                           class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"/>
                </div>

                {{-- Features (grouped checkboxes) --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Features</label>
                    @php
                        $existingFeatures = old('feature_keys', $subscriptionPlan->features ?? []);
                        if (!is_array($existingFeatures)) {
                            $existingFeatures = [];
                        }
                        $limitedQuizChecked = (bool) old('limited_quiz_attempts', in_array('limited_quiz_attempts', $existingFeatures, true));
                        $limitedModulesChecked = (bool) old('limited_modules_access', in_array('limited_modules_access', $existingFeatures, true));
                    @endphp
                    @foreach(config('subscription_features.groups', []) as $groupKey => $group)
                        <p class="text-xs font-bold text-gray-500 uppercase mb-1 {{ $loop->first ? 'mt-2' : 'mt-3' }}">
                            {{ $group['label'] }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 mb-2 {{ ($group['dimmed'] ?? false) ? 'opacity-60' : '' }}">
                            @foreach($group['features'] as $val => $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="feature_keys[]" value="{{ $val }}"
                                           {{ in_array($val, $existingFeatures, true) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded border-gray-300 {{ ($group['dimmed'] ?? false) ? 'text-purple-600 focus:ring-purple-500' : 'text-brand-500 focus:ring-brand-500' }}">
                                    <span class="text-sm {{ ($group['dimmed'] ?? false) ? 'text-gray-500 italic' : 'text-gray-700 dark:text-gray-300' }}">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- Additional toggles for common limitations --}}
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="limited_quiz_attempts" value="0" />
                        <input type="checkbox" name="limited_quiz_attempts" id="limited_quiz_attempts" value="1" {{ $limitedQuizChecked ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        <label for="limited_quiz_attempts" class="text-sm font-medium text-gray-700 dark:text-gray-300">Limited Quiz Attempts</label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="limited_modules_access" value="0" />
                        <input type="checkbox" name="limited_modules_access" id="limited_modules_access" value="1" {{ $limitedModulesChecked ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        <label for="limited_modules_access" class="text-sm font-medium text-gray-700 dark:text-gray-300">Limited Modules Access</label>
                    </div>
                </div>

                {{-- Active toggle --}}
                <div class="flex items-center gap-3 mb-6">
                    <input type="hidden" name="is_active" value="0"/>
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $subscriptionPlan->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"/>
                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active (visible to learners)</label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition-colors shadow-theme-xs">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.subscription-plans.index') }}"
                       class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
