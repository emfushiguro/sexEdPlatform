@extends('layouts.learner-app')

@section('title', 'Payment Cancelled')

@section('content')
@php
    $scope = $scope ?? 'subscription';
    $isModule = $scope === 'module_purchase';
@endphp
<div class="max-w-3xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <!-- Cancel Icon -->
                    <div class="mb-6">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100">
                            <svg class="h-10 w-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Cancel Message -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                        Payment Cancelled
                    </h3>
                    
                    <p class="text-gray-600 mb-6">
                        You have cancelled the payment process. No charges have been made to your account.
                    </p>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ $isModule && $module ? route('learner.modules.show', $module) : route('subscription.upgrade') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Return to {{ $isModule ? 'Module' : 'Subscription' }}
                        </a>
                        <a href="{{ $isModule && $module ? route('learner.modules.purchase.form', $module) : route('subscription.upgrade') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Retry Payment
                        </a>
                    </div>
                </div>
            </div>
</div>
@endsection
