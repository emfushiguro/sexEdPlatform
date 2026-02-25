@extends('layouts.admin')

@section('title', 'Create Subscription Plan')

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">Create Subscription Plan</h1>
            <p class="text-gray-600 mt-1">Design a new subscription plan for your users</p>
        </div>
        <a href="{{ route('admin.subscription-plans.index') }}" 
           class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded font-medium transition">
            ← Back to Plans
        </a>
    </div>

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('admin.subscription-plans.store') }}">
            @csrf
            
            <div class="bg-white rounded-lg shadow-lg">
                <!-- Basic Information -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Plan Name *</label>
                            <input type="text" 
                                   name="name" 
                                   id="name"
                                   value="{{ old('name') }}" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="e.g., VIP Student Plan"
                                   required>
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                            <input type="number" 
                                   name="sort_order" 
                                   id="sort_order"
                                   value="{{ old('sort_order') }}" 
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="10"
                                   min="0">
                            <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" 
                                  id="description"
                                  rows="3"
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Describe what this plan offers...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing</h3>
                    
                    <div class="max-w-xs">
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price (₱) *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-gray-500">₱</span>
                            <input type="number" 
                                   name="price" 
                                   id="price"
                                   value="{{ old('price') }}" 
                                   step="0.01"
                                   min="0"
                                   class="w-full p-3 pl-8 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('price') border-red-500 @enderror"
                                   placeholder="299.00"
                                   required>
                        </div>
                        @error('price')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">Set to 0 for a free plan.</p>
                    </div>
                </div>

                <!-- Limits & Trial -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Limits & Trial</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="trial_days" class="block text-sm font-medium text-gray-700 mb-2">Trial Days</label>
                            <input type="number" 
                                   name="trial_days" 
                                   id="trial_days"
                                   value="{{ old('trial_days', 0) }}" 
                                   min="0"
                                   max="365"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0">
                            <p class="text-xs text-gray-500 mt-1">0 = No trial period</p>
                        </div>

                        <div>
                            <label for="max_users" class="block text-sm font-medium text-gray-700 mb-2">Max Users</label>
                            <input type="number" 
                                   name="max_users" 
                                   id="max_users"
                                   value="{{ old('max_users', 1) }}" 
                                   min="1"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="1">
                        </div>

                        <div>
                            <label for="max_modules" class="block text-sm font-medium text-gray-700 mb-2">Max Modules</label>
                            <input type="number" 
                                   name="max_modules" 
                                   id="max_modules"
                                   value="{{ old('max_modules') }}" 
                                   min="0"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="999">
                            <p class="text-xs text-gray-500 mt-1">999 = Unlimited modules</p>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Features *</h3>
                    <p class="text-sm text-gray-600 mb-4">Add features for this plan. At least one feature is required.</p>
                    
                    <div id="features-container" class="space-y-3 mb-3">
                        <!-- Initial feature row -->
                        <div class="grid grid-cols-12 gap-3 feature-row">
                            <input type="text" 
                                   name="feature_keys[]" 
                                   class="col-span-11 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   placeholder="Feature name (e.g., unlimited_quizzes)"
                                   required>
                            <button type="button" 
                                    onclick="removeFeature(this)" 
                                    class="col-span-1 bg-red-500 hover:bg-red-600 text-white px-3 py-3 rounded-lg transition">
                                ✕
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" 
                            onclick="addFeature()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-medium transition">
                        + Add Feature
                    </button>
                </div>

                <!-- Status -->
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status</h3>
                    
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active"
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                            Plan is active and available for subscription
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('admin.subscription-plans.index') }}" 
                       class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded font-medium transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded font-medium transition">
                        Create Plan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const newFeatureRow = document.createElement('div');
    newFeatureRow.className = 'grid grid-cols-12 gap-3 feature-row';
    newFeatureRow.innerHTML = `
        <input type="text" 
               name="feature_keys[]" 
               class="col-span-11 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
               placeholder="Feature name (e.g., certificates)"
               required>
        <button type="button" 
                onclick="removeFeature(this)" 
                class="col-span-1 bg-red-500 hover:bg-red-600 text-white px-3 py-3 rounded-lg transition">
            ✕
        </button>
    `;
    container.appendChild(newFeatureRow);
}

function removeFeature(button) {
    const container = document.getElementById('features-container');
    const rows = container.querySelectorAll('.feature-row');
    
    // Keep at least one feature row
    if (rows.length > 1) {
        button.closest('.feature-row').remove();
    } else {
        alert('At least one feature is required.');
    }
}

// No additional JS needed - single price field
document.addEventListener('DOMContentLoaded', function() {
    // Feature row management is handled above
});
</script>
@endsection