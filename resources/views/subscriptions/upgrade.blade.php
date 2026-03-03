<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upgrade to Premium') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <!-- Current Subscription Status -->
            @if($currentSubscription)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">Current Subscription</h3>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-800">
                                <strong>Plan:</strong> {{ ucfirst($currentSubscription->plan_type) }}
                            </p>
                            <p class="text-blue-800">
                                <strong>Status:</strong> 
                                <span class="px-2 py-1 rounded-full text-xs 
                                    {{ $currentSubscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($currentSubscription->status) }}
                                </span>
                            </p>
                            @if($currentSubscription->end_date)
                                <p class="text-blue-800">
                                    <strong>Expires:</strong> {{ $currentSubscription->end_date->format('F d, Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quiz Limit Notice (for free users) -->
            @if(!auth()->user()->isPremium())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-yellow-800">Daily Quiz Limit Reached</h3>
                            <p class="mt-2 text-yellow-700">
                                Free users are limited to <strong>3 quiz attempts per day</strong>. 
                                Upgrade to Premium for unlimited quiz attempts and access to exclusive features!
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Premium Plans -->
            <div class="grid md:grid-cols-2 gap-8 mb-8">
                <!-- Monthly Plan -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-200 hover:border-blue-500 transition">
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Monthly Premium</h3>
                        <div class="flex items-baseline mb-4">
                            <span class="text-4xl font-bold">₱299</span>
                            <span class="text-gray-600 ml-2">/month</span>
                        </div>
                        
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Unlimited quiz attempts</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Access to all modules and lessons</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Priority support</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Certificates of completion</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Offline downloadable content</span>
                            </li>
                        </ul>

                        <form action="{{ route('subscription.subscribe') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_type" value="monthly">
                            <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                                Subscribe Monthly
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Annual Plan (Best Value) -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-blue-500 relative">
                    <div class="absolute top-0 right-0 bg-blue-500 text-white px-4 py-1 text-sm font-semibold rounded-bl-lg">
                        BEST VALUE
                    </div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Annual Premium</h3>
                        <div class="flex items-baseline mb-1">
                            <span class="text-4xl font-bold">₱2,999</span>
                            <span class="text-gray-600 ml-2">/year</span>
                        </div>
                        <p class="text-green-600 font-semibold mb-4">Save ₱589 (16% off)</p>
                        
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Unlimited quiz attempts</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Access to all modules and lessons</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Priority support</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Certificates of completion</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700">Offline downloadable content</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-gray-700"><strong>Bonus:</strong> Extended analytics dashboard</span>
                            </li>
                        </ul>

                        <form action="{{ route('subscription.subscribe') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_type" value="annual">
                            <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                                Subscribe Annually
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Back to Dashboard -->
            <div class="text-center">
                <a href="{{ route('learner.modules.index') }}" class="text-blue-600 hover:text-blue-800">
                    ← Back to Modules
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
