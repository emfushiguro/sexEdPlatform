<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Complete Your Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Order Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Plan</span>
                            <span class="font-medium text-gray-900">{{ $subscription->getPlanLabel() }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Start Date</span>
                            <span class="font-medium text-gray-900">{{ $subscription->start_date->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">End Date</span>
                            <span class="font-medium text-gray-900">{{ $subscription->end_date->format('M d, Y') }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-lg">
                        <span class="font-semibold text-gray-900">Total Amount</span>
                        <span class="font-bold text-blue-600">₱{{ number_format($amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Method Selection -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Payment Method</h3>

                    <form action="{{ route('payment.process', $subscription) }}" method="POST" id="payment-form">
                        @csrf

                        <div class="space-y-4">
                            <!-- GCash -->
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-option">
                                <input type="radio" name="payment_method" value="gcash" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <div class="ml-4 flex items-center">
                                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold text-sm mr-3">
                                        GC
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">GCash</p>
                                        <p class="text-sm text-gray-500">Pay using your GCash wallet</p>
                                    </div>
                                </div>
                            </label>

                            <!-- PayMaya -->
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-option">
                                <input type="radio" name="payment_method" value="paymaya" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <div class="ml-4 flex items-center">
                                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold text-sm mr-3">
                                        PM
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">PayMaya / Maya</p>
                                        <p class="text-sm text-gray-500">Pay using your Maya wallet</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Credit/Debit Card -->
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition payment-option">
                                <input type="radio" name="payment_method" value="card" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <div class="ml-4 flex items-center">
                                    <div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center text-white font-bold text-sm mr-3">
                                        💳
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Credit/Debit Card</p>
                                        <p class="text-sm text-gray-500">Visa, Mastercard, JCB</p>
                                    </div>
                                </div>
                            </label>
                        </div>

                        @error('payment_method')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <!-- Terms & Conditions -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <label class="flex items-start">
                                <input type="checkbox" name="accept_terms" id="accept_terms" required
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 mt-1">
                                <span class="ml-3 text-sm text-gray-600">
                                    I have read and agree to the
                                    <a href="{{ route('terms') }}" target="_blank" class="text-blue-600 hover:underline">Terms & Conditions</a>
                                    and
                                    <a href="{{ route('privacy') }}" target="_blank" class="text-blue-600 hover:underline">Privacy Policy</a>.
                                    I understand that refunds are only available within
                                    <strong>{{ config('billing.subscription.refund_window_days', 3) }} days</strong> of payment.
                                </span>
                            </label>
                            @error('accept_terms')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6">
                            <button type="submit" id="submit-btn"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <span id="btn-text">Proceed to Payment</span>
                                <span id="btn-loading" class="hidden">
                                    <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <div class="flex items-center justify-center text-gray-500 text-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span>Secured by PayMongo - Your payment information is encrypted and secure</span>
                </div>
            </div>

            <!-- Back Link -->
            <div class="mt-6 text-center">
                <a href="{{ route('subscription.upgrade') }}" class="text-blue-600 hover:text-blue-800">
                    ← Back to Plans
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const acceptTerms = document.getElementById('accept_terms');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }

            if (!acceptTerms.checked) {
                e.preventDefault();
                alert('Please accept the Terms & Conditions');
                return;
            }

            // Show loading state
            document.getElementById('btn-text').classList.add('hidden');
            document.getElementById('btn-loading').classList.remove('hidden');
            document.getElementById('submit-btn').disabled = true;
        });

        // Highlight selected payment option
        document.querySelectorAll('.payment-option input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('border-blue-500', 'bg-blue-50');
                    opt.classList.add('border-gray-200');
                });
                if (this.checked) {
                    this.closest('.payment-option').classList.add('border-blue-500', 'bg-blue-50');
                    this.closest('.payment-option').classList.remove('border-gray-200');
                }
            });
        });
    </script>
</x-app-layout>
