<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Choose Your Plan') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Page Heading --}}
            <div class="text-center mb-10">
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Choose a plan</h1>
                <p class="text-gray-500 text-lg">Start for free. Upgrade anytime.</p>
                @if(!auth()->user()->isPremium())
                    <div class="inline-flex items-center gap-2 mt-4 bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-2 rounded-full">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        Free plan: limited to <strong class="mx-1">{{ \App\Models\QuizDailyLimit::MAX_FREE_ATTEMPTS }} quiz attempts</strong> per day
                    </div>
                @endif
            </div>

            {{-- Plans Grid --}}
            @php
                $visiblePlans = collect($availablePlans ?? [])->filter(fn($p) => is_object($p));
                $planCount = $visiblePlans->count();
                $featuredUsed = false;
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-{{ min($planCount, 3) }} gap-6 items-stretch">

                @forelse($visiblePlans as $plan)
                    @php
                        $isCurrentPlan = isset($currentPlanId) && $plan->id === $currentPlanId;
                        $isActive      = $hasActiveSubscription ?? false;
                        $isFeatured    = !$plan->isFree() && !$plan->hasFeature('test_mode');
                        $showFeatured  = $isFeatured && !$featuredUsed;
                        if ($showFeatured) $featuredUsed = true;
                    @endphp

                    <div class="relative rounded-2xl flex flex-col
                        {{ $isCurrentPlan
                            ? 'bg-white border-2 border-blue-500 shadow-lg'
                            : ($showFeatured
                                ? 'bg-gray-900 text-white shadow-2xl'
                                : 'bg-white border border-gray-200 shadow-sm') }}">

                        {{-- Top badge --}}
                        @if($isCurrentPlan)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow">Your plan</span>
                            </div>
                        @elseif($showFeatured)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="bg-white text-gray-900 text-xs font-bold px-3 py-1 rounded-full shadow border border-gray-200">Most popular</span>
                            </div>
                        @elseif($plan->hasFeature('test_mode'))
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full shadow">Test mode</span>
                            </div>
                        @endif

                        <div class="p-8 flex flex-col flex-1">

                            {{-- Plan name & description --}}
                            <div class="mb-6">
                                <h2 class="text-xl font-bold {{ $showFeatured ? 'text-white' : 'text-gray-900' }} mb-1">{{ $plan->name }}</h2>
                                @if($plan->description)
                                    <p class="text-sm {{ $showFeatured ? 'text-gray-400' : 'text-gray-500' }}">{{ $plan->description }}</p>
                                @endif
                            </div>

                            {{-- Pricing --}}
                            <div class="mb-6">
                                @if($plan->isFree())
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-5xl font-bold text-gray-900">₱0</span>
                                    </div>
                                @else
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-5xl font-bold {{ $showFeatured ? 'text-white' : 'text-gray-900' }}">
                                            ₱{{ number_format($plan->price, 0) }}
                                        </span>
                                        <span class="{{ $showFeatured ? 'text-gray-400' : 'text-gray-500' }} text-sm">/ month</span>
                                    </div>
                                @endif
                            </div>

                            {{-- CTA Button --}}
                            <div class="mb-8">
                                @if($plan->isFree())
                                    <button disabled class="w-full py-3 px-4 rounded-xl text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed">
                                        Your current plan
                                    </button>

                                @elseif($isCurrentPlan)
                                    <button disabled class="w-full py-3 px-4 rounded-xl text-sm font-semibold bg-blue-50 text-blue-700 border border-blue-300 cursor-default">
                                        ✓ Current plan
                                    </button>

                                @elseif($isActive)
                                    <button disabled class="w-full py-3 px-4 rounded-xl text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed"
                                        title="Cancel your current plan to switch.">
                                        Cancel plan to switch
                                    </button>
                                    <p class="text-center text-xs text-gray-400 mt-2">
                                        <a href="{{ route('subscription.index') }}" class="underline hover:opacity-80">Manage subscription →</a>
                                    </p>

                                @elseif($plan->hasFeature('test_mode'))
                                    <form action="{{ route('subscription.subscribe') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold bg-yellow-400 hover:bg-yellow-500 text-yellow-900 transition">
                                            🧪 Start test ({{ $plan->getFeatureValue('duration_minutes', 10) }} min)
                                        </button>
                                    </form>

                                @else
                                    <form action="{{ route('subscription.subscribe') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold transition
                                            {{ $showFeatured ? 'bg-white text-gray-900 hover:bg-gray-100' : 'bg-gray-900 text-white hover:bg-gray-800' }}">
                                            Subscribe
                                        </button>
                                    </form>
                                @endif
                            </div>

                            {{-- Divider --}}
                            <div class="border-t {{ $showFeatured ? 'border-gray-700' : 'border-gray-100' }} mb-6"></div>

                            {{-- Features --}}
                            @php
                                $featureLabels = [
                                    'unlimited_quizzes'          => 'Unlimited quiz attempts',
                                    'certificates'               => 'Completion certificates',
                                    'priority_support'           => 'Priority support',
                                    'downloadable_content'       => 'Downloadable resources',
                                    'downloadable_resources'     => 'Downloadable resources',
                                    'consultations'              => 'Live consultations',
                                    'offline_access'             => 'Offline access',
                                    'progress_analytics'         => 'Progress analytics',
                                    'all_modules'                => 'Access to all modules',
                                    'admin_dashboard'            => 'Admin dashboard',
                                    'progress_tracking'          => 'Progress tracking',
                                    'bulk_enrollment'            => 'Bulk enrollment',
                                    'custom_branding'            => 'Custom branding',
                                    'api_access'                 => 'API access',
                                    'dedicated_account_manager'  => 'Dedicated account manager',
                                    'custom_reporting'           => 'Custom reporting',
                                ];
                                $planFeatures = is_array($plan->features) ? $plan->features : [];
                                $hidden = ['test_mode', 'duration_minutes'];
                                $displayFeatures = array_filter($planFeatures, fn($f) => !in_array($f, $hidden));
                            @endphp
                            <ul class="space-y-2.5 flex-1 text-sm">
                                @if($plan->isFree())
                                    <li class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 flex-shrink-0 {{ $showFeatured ? 'text-green-400' : 'text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="{{ $showFeatured ? 'text-gray-300' : 'text-gray-700' }}">3 quiz attempts per day</span>
                                    </li>
                                    <li class="flex items-center gap-2.5">
                                        <svg class="w-4 h-4 flex-shrink-0 {{ $showFeatured ? 'text-green-400' : 'text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="{{ $showFeatured ? 'text-gray-300' : 'text-gray-700' }}">Limited module access</span>
                                    </li>
                                @else
                                    @foreach($displayFeatures as $feat)
                                        <li class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 flex-shrink-0 {{ $showFeatured ? 'text-green-400' : 'text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="{{ $showFeatured ? 'text-gray-300' : 'text-gray-700' }}">
                                                {{ $featureLabels[$feat] ?? ucwords(str_replace('_', ' ', $feat)) }}
                                            </span>
                                        </li>
                                    @endforeach
                                    @if($plan->trial_days > 0)
                                        <li class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 flex-shrink-0 {{ $showFeatured ? 'text-blue-400' : 'text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="{{ $showFeatured ? 'text-blue-300' : 'text-blue-600' }}">
                                                {{ $plan->trial_days }}-day free trial
                                            </span>
                                        </li>
                                    @endif
                                    @if(empty($displayFeatures) && $plan->trial_days == 0)
                                        <li class="flex items-center gap-2.5 text-gray-400 italic">
                                            Basic access included
                                        </li>
                                    @endif
                                @endif
                            </ul>

                        </div>
                    </div>

                @empty
                    <div class="col-span-3 text-center py-16">
                        <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <p class="text-gray-500 text-lg font-medium mb-1">No plans available</p>
                        <p class="text-gray-400 text-sm">Contact the administrator to set up subscription plans.</p>
                    </div>
                @endforelse
            </div>

            <p class="text-center text-xs text-gray-400 mt-10">
                All plans include access to our core sexual health education content. Cancel anytime.
                <a href="{{ route('dashboard') }}" class="ml-3 text-gray-500 underline hover:text-gray-700">← Back to dashboard</a>
            </p>

        </div>
    </div>
</x-app-layout>
