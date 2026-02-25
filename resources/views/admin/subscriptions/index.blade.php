@extends('layouts.admin')

@section('title', 'Subscription Management Center')

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Header with Quick Actions -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">Subscription Management Center</h1>
            <p class="text-gray-600 mt-1">Unified subscription and plan management</p>
        </div>
        <div class="flex space-x-3">
            <!-- Create Plan -->
            <button onclick="openModal('createPlanModal')" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-medium transition">
                📋 New Plan
            </button>
        </div>
    </div>

    <!-- Combined Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Subscription Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Subscribers</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $subscriptionStats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $subscriptionStats['active'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Plan Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-indigo-100 rounded-full">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Plans</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $planStats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active Plans</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $planStats['active'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        @if(isset($subscriptionStats['expiring_soon']))
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Expiring Soon</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $subscriptionStats['expiring_soon'] }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex">
                <a href="{{ route('admin.subscriptions.index') }}" 
                   class="py-4 px-6 text-sm font-medium text-center border-b-2 
                   {{ !request('tab') || request('tab') === 'subscriptions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    🔑 Active Subscriptions
                </a>
                <a href="{{ route('admin.subscriptions.index', ['tab' => 'plans']) }}" 
                   class="py-4 px-6 text-sm font-medium text-center border-b-2 
                   {{ request('tab') === 'plans' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    📋 Subscription Plans
                </a>
            </nav>
        </div>
    </div>

    @if(!request('tab') || request('tab') === 'subscriptions')
        @include('admin.subscriptions.partials.subscriptions-tab')
    @elseif(request('tab') === 'plans')
        @include('admin.subscriptions.partials.plans-tab')
    @endif
</div>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show success notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.textContent = @json(session('success'));
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show error notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.textContent = @json(session('error'));
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    });
</script>
@endif


<!-- Create Plan Modal -->
<div id="createPlanModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900">Create New Plan</h3>
            <button onclick="closeModal('createPlanModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
            <p class="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.subscriptions.store-plan') }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name *</label>
                <input type="text" name="name" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Premium Plan" value="{{ old('name') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Brief description of the plan">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price (₱) *</label>
                    <input type="number" name="price" step="0.01" min="0" required class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="299.00" value="{{ old('price') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trial Days</label>
                    <input type="number" name="trial_days" min="0" max="365" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0" value="{{ old('trial_days') }}">
                </div>
            </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" min="0" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Auto" value="{{ old('sort_order') }}">
                </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Features</label>
                @php
                    $availableFeatures = [
                        'unlimited_quizzes'         => 'Unlimited quiz attempts',
                        'certificates'              => 'Completion certificates',
                        'priority_support'          => 'Priority support',
                        'downloadable_content'      => 'Downloadable resources',
                        'consultations'             => 'Live consultations',
                        'offline_access'            => 'Offline access',
                        'progress_analytics'        => 'Progress analytics',
                        'all_modules'               => 'Access to all modules',
                        'admin_dashboard'           => 'Admin dashboard',
                        'progress_tracking'         => 'Progress tracking',
                        'bulk_enrollment'           => 'Bulk enrollment',
                        'custom_branding'           => 'Custom branding',
                        'api_access'                => 'API access',
                        'dedicated_account_manager' => 'Dedicated account manager',
                        'custom_reporting'          => 'Custom reporting',
                    ];
                    $oldFeatures = old('feature_keys', []);
                @endphp
                <div class="grid grid-cols-1 gap-2">
                    @foreach($availableFeatures as $key => $label)
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="feature_keys[]" value="{{ $key }}"
                                   {{ in_array($key, $oldFeatures) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active (available for subscription)</label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="closeModal('createPlanModal')" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded font-medium transition">
                    Create Plan
                </button>
            </div>
        </form>
    </div>
</div>
</div>


<script>
// Auto-open modal if session flag is set
@if(session('openModal'))
document.addEventListener('DOMContentLoaded', function() {
    openModal('{{ session('openModal') }}Modal');
});
@endif

// Auto-open modal if there are validation errors
@if($errors->any() && old('_token'))
document.addEventListener('DOMContentLoaded', function() {
    @if(old('name') !== null || old('price') !== null)
        openModal('createPlanModal');
    @endif
});
@endif

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on backdrop click
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id$="Modal"]').forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                closeModal(modal.id);
            }
        });
    }
});
</script>
@endsection