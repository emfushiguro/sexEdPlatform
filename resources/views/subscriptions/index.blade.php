<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Subscription') }}
        </h2>
    </x-slot>

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Flash messages --}}
            @foreach(['success' => 'green', 'error' => 'red', 'info' => 'blue'] as $type => $color)
                @if(session($type))
                    <div class="mb-5 bg-{{ $color }}-50 border border-{{ $color }}-300 text-{{ $color }}-700 px-4 py-3 rounded-lg text-sm">
                        {{ session($type) }}
                    </div>
                @endif
            @endforeach

                    {{-- Pending payment warning --}}
            @if($subscription && $subscription->status === 'pending')
                <div class="mb-5 bg-amber-50 border border-amber-300 rounded-xl p-4 flex gap-3">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="font-semibold text-amber-800 text-sm">Payment pending</p>
                        <p class="text-amber-700 text-sm mt-0.5">If you already paid, click "Return to merchant" on PayMongo or refresh this page. Contact support if it doesn't update after 5 minutes.</p>
                    </div>
                </div>
            @endif

            @if($subscription && in_array($subscription->status, ['active', 'trialing', 'cancelled', 'past_due', 'pending']))
                @php
                    $planModel = $subscription->relationLoaded('plan') ? $subscription->getRelation('plan') : $subscription->plan;
                    $daysLeft  = $subscription->end_date ? max(0, (int) ceil(now()->floatDiffInDays($subscription->end_date))) : null;
                    $totalDays = ($subscription->start_date && $subscription->end_date)
                        ? max(1, $subscription->start_date->diffInDays($subscription->end_date))
                        : null;
                    $progress  = ($daysLeft !== null && $totalDays) ? min(100, round(($daysLeft / $totalDays) * 100)) : null;
                    $statusColor = match($subscription->status) {
                        'active'    => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'dot' => 'bg-green-500'],
                        'trialing'  => ['bg' => 'bg-blue-100',  'text' => 'text-blue-700',  'dot' => 'bg-blue-500'],
                        'cancelled' => ['bg' => 'bg-gray-100',  'text' => 'text-gray-600',  'dot' => 'bg-gray-400'],
                        'past_due'  => ['bg' => 'bg-red-100',   'text' => 'text-red-700',   'dot' => 'bg-red-500'],
                        default     => ['bg' => 'bg-yellow-100','text' => 'text-yellow-700','dot' => 'bg-yellow-500'],
                    };
                @endphp

                {{-- Hero Plan Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 mb-6">
                    <div class="flex items-start justify-between flex-wrap gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Current plan</p>
                            <h2 class="text-3xl font-bold text-gray-900">{{ $subscription->getPlanLabel() }}</h2>
                            @if($planModel && $planModel->description)
                                <p class="text-gray-500 text-sm mt-1">{{ $planModel->description }}</p>
                            @endif
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold {{ $statusColor['bg'] }} {{ $statusColor['text'] }}">
                            <span class="w-2 h-2 rounded-full {{ $statusColor['dot'] }}"></span>
                            {{ $subscription->getStatusLabel() }}
                        </span>
                    </div>

                    {{-- Dates row --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
                        @if($subscription->start_date)
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Started</p>
                                <p class="text-sm font-medium text-gray-800">{{ $subscription->start_date->format('M d, Y') }}</p>
                            </div>
                        @endif
                        @if($subscription->end_date)
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">{{ $subscription->status === 'cancelled' ? 'Access until' : 'Renews' }}</p>
                                <p class="text-sm font-medium text-gray-800">{{ $subscription->end_date->format('M d, Y') }}</p>
                            </div>
                        @endif
                        @if($daysLeft !== null && $subscription->isActive())
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Days remaining</p>
                                <p class="text-sm font-bold text-gray-800">{{ $daysLeft }} days</p>
                            </div>
                        @endif
                    </div>

                    {{-- Progress bar (active subscriptions only) --}}
                    @if($progress !== null && $subscription->isActive())
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>Subscription period</span>
                                <span>{{ $progress }}% remaining</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Two-column: Features + Actions --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                    {{-- Plan features --}}
                    @if($planModel && is_object($planModel))
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-4">What's included</h3>
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
                                $features = is_array($planModel->features) ? $planModel->features : [];
                                // Filter out internal/technical flags
                                $hidden = ['test_mode', 'duration_minutes'];
                                $displayFeatures = array_filter($features, fn($f) => !in_array($f, $hidden));
                            @endphp
                            <ul class="space-y-2.5 text-sm">
                                @if($planModel->isFree())
                                    <li class="flex items-center gap-2.5">
                                        <input type="checkbox" checked disabled class="w-4 h-4 rounded border-gray-300 accent-green-500 cursor-default">
                                        <span class="text-gray-700">3 quiz attempts per day</span>
                                    </li>
                                    <li class="flex items-center gap-2.5">
                                        <input type="checkbox" checked disabled class="w-4 h-4 rounded border-gray-300 accent-green-500 cursor-default">
                                        <span class="text-gray-700">Limited module access</span>
                                    </li>
                                @else
                                    @foreach($displayFeatures as $feature)
                                        <li class="flex items-center gap-2.5">
                                            <input type="checkbox" checked disabled
                                                   class="w-4 h-4 rounded border-gray-300 text-green-500 accent-green-500 cursor-default">
                                            <span class="text-gray-700">
                                                {{ $featureLabels[$feature] ?? ucwords(str_replace('_', ' ', $feature)) }}
                                            </span>
                                        </li>
                                    @endforeach
                                    @if($planModel->trial_days > 0)
                                        <li class="flex items-center gap-2.5">
                                            <input type="checkbox" checked disabled
                                                   class="w-4 h-4 rounded border-gray-300 accent-blue-500 cursor-default">
                                            <span class="text-blue-600">{{ $planModel->trial_days }}-day free trial</span>
                                        </li>
                                    @endif
                                @endif
                            </ul>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col gap-3">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-widest mb-1">Manage</h3>

                        @if($subscription->canRenew())
                            <form action="{{ route('subscription.renew') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold bg-green-600 hover:bg-green-700 text-white transition">
                                    Renew subscription
                                </button>
                            </form>
                        @endif

                        @if(!$subscription->isPremium())
                            <a href="{{ route('subscription.upgrade') }}"
                               class="block text-center w-full py-3 px-4 rounded-xl text-sm font-semibold bg-gray-900 hover:bg-gray-800 text-white transition">
                                Upgrade plan
                            </a>
                        @endif

                        <a href="{{ route('payment.history') }}"
                           class="block text-center w-full py-3 px-4 rounded-xl text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                            Payment history
                        </a>

                        <a href="{{ route('subscription.upgrade') }}"
                           class="block text-center w-full py-3 px-4 rounded-xl text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition">
                            View all plans
                        </a>

                        {{-- Refund --}}
                        @if($canRequestRefund ?? false)
                            @php $refundWindowDays = config('billing.subscription.refund_window_days', 3); @endphp
                            <div class="border-t border-gray-100 pt-3 mt-1">
                                <p class="text-xs text-gray-400 mb-2">
                                    Refund window closes {{ $latestPaidPayment->paid_at->copy()->addDays($refundWindowDays)->format('M d, Y h:i A') }}
                                </p>
                                <form action="{{ route('subscription.refund') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="reason" value="Customer refund request">
                                    <button type="submit"
                                        onclick="return confirm('Request a refund? Your subscription will be cancelled immediately.')"
                                        class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold border border-red-300 text-red-600 hover:bg-red-50 transition">
                                        Request refund
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

            @else
                {{-- No subscription --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No active subscription</h3>
                    <p class="text-gray-500 mb-6">You're currently on the free plan with limited access to modules.</p>
                    <a href="{{ route('subscription.upgrade') }}"
                       class="inline-block py-3 px-8 rounded-xl text-sm font-semibold bg-gray-900 hover:bg-gray-800 text-white transition">
                        Choose a plan
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>