@extends('layouts.learner-app')

@section('title', 'Payment Cancelled')

@section('content')
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

                    <!-- Info Box -->
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 text-left">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Don't worry! You can try again anytime. Your subscription details have not been affected.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Why Subscribe? -->
                    <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                        <h4 class="font-semibold text-gray-900 mb-3">Why upgrade to Premium?</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span><strong>Unlimited quiz attempts</strong> - Practice as much as you need</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span><strong>Access all modules</strong> - Complete learning path</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span><strong>Priority support</strong> - Get help when you need it</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span><strong>Certificates of completion</strong> - Prove your knowledge</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span><strong>Offline content</strong> - Learn anywhere, anytime</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Pricing Reminder -->
                    @if(isset($premiumPlan))
                    <div class="mb-6">
                        <div class="inline-flex items-center">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900">Premium Plan</div>
                                <div class="text-2xl font-bold text-blue-600">₱{{ number_format($premiumPlan->price, 0) }}</div>
                                <div class="text-xs text-gray-500">per month</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('subscription.upgrade') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Try Again
                        </a>
                        <a href="{{ route('learner.dashboard') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Back to Dashboard
                        </a>
                    </div>

                    <!-- Contact Support -->
                    <div class="mt-8 text-sm text-gray-500">
                        <p>Need help? <a href="mailto:support@sexedplatform.com" class="text-blue-600 hover:text-blue-800">Contact Support</a></p>
                    </div>
                </div>
            </div>
</div>
@endsection
