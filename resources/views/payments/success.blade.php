<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Successful!') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <!-- Success Icon -->
                    <div class="mb-6">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                        Payment Received!
                    </h3>
                    
                    <p class="text-gray-600 mb-6">
                        Thank you for subscribing to our premium plan. Your payment has been received and is being processed.
                    </p>

                    <!-- Important Notice -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-yellow-800">
                                    Subscription Activation Pending
                                </h4>
                                <p class="mt-2 text-sm text-yellow-700">
                                    Your subscription will be activated within a few minutes. Our admin team will verify your payment and activate your premium access shortly.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- What's Next -->
                    <div class="bg-blue-50 rounded-lg p-6 mb-6 text-left">
                        <h4 class="font-semibold text-blue-900 mb-3">What happens next?</h4>
                        <ul class="space-y-2 text-sm text-blue-800">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Your payment is being verified by our admin team</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>You'll receive an email confirmation once activated</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Premium features will be unlocked automatically</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Activation typically takes 5-15 minutes</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('dashboard') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Go to Dashboard
                        </a>
                        <a href="{{ route('subscription.index') }}" 
                           class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            View Subscription
                        </a>
                    </div>

                    <!-- Contact Support -->
                    <div class="mt-8 text-sm text-gray-500">
                        <p>Having issues? <a href="mailto:support@sexedplatform.com" class="text-blue-600 hover:text-blue-800">Contact Support</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
