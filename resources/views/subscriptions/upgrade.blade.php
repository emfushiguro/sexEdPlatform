<x-app-layout>
    @php
        $featureLabelMap = config('subscription_features.labels', []);
        $hiddenFeatures = config('subscription_features.hidden', ['test_mode', 'duration_minutes', 'age_based_content_filtering']);

        $flattenFeatureLabels = function (array $features) use ($featureLabelMap, $hiddenFeatures): array {
            $output = [];

            foreach ($features as $group => $value) {
                if (is_array($value)) {
                    foreach ($value as $key => $featureValue) {
                        if (in_array($key, $hiddenFeatures, true)) {
                            continue;
                        }

                        if ($featureValue === true || (is_numeric($featureValue) && $featureValue > 0) || (is_string($featureValue) && in_array($featureValue, ['unlimited', 'full', 'advanced', 'priority'], true))) {
                            $output[$key] = $featureLabelMap[$key] ?? ucwords(str_replace('_', ' ', $key));
                        }
                    }

                    continue;
                }

                if (is_string($group) && ($value === true || $value === 1) && !in_array($group, $hiddenFeatures, true)) {
                    $output[$group] = $featureLabelMap[$group] ?? ucwords(str_replace('_', ' ', $group));
                    continue;
                }

                if (is_int($group) && is_string($value) && !in_array($value, $hiddenFeatures, true)) {
                    $output[$value] = $featureLabelMap[$value] ?? ucwords(str_replace('_', ' ', $value));
                }
            }

            return $output;
        };

        $visiblePlans = collect($availablePlans ?? [])->filter(fn ($plan) => is_object($plan))->values();
        $currentUser = auth()->user();
        $freeQuizLimit = \App\Models\QuizDailyLimit::MAX_FREE_ATTEMPTS;

        $comparisonGroups = [
            'Learning Access' => [
                ['label' => 'Standard Module Access', 'key' => 'full_course_access', 'free' => 'Limited'],
                ['label' => 'Offline Access', 'key' => 'offline_access', 'free' => false],
                ['label' => 'Expert Video Sessions', 'key' => 'expert_video_sessions', 'free' => false],
                ['label' => 'Exclusive Bonus Content', 'key' => 'exclusive_content', 'free' => false],
            ],
            'Assessment & Rewards' => [
                ['label' => 'Unlimited Quiz Attempts', 'key' => 'unlimited_quizzes', 'free' => false],
                ['label' => 'Completion Certificates', 'key' => 'certificates', 'free' => false],
                ['label' => 'Advanced Progress Analytics', 'key' => 'advanced_analytics', 'free' => false],
            ],
            'Community & Support' => [
                ['label' => 'Downloadable Materials', 'key' => 'downloadable_materials', 'free' => false],
                ['label' => 'Anonymous Q&A with Educators', 'key' => 'anonymous_qa', 'free' => false],
                ['label' => 'Private Community Discussion', 'key' => 'private_community', 'free' => false],
                ['label' => 'Priority Support', 'key' => 'priority_support', 'free' => false],
                ['label' => 'Ad-Free Experience', 'key' => 'ad_free', 'free' => false],
            ],
            'Premium Plus' => [
                ['label' => 'Appointment Booking', 'key' => 'appointment_booking', 'free' => false],
                ['label' => 'AI Chatbot Assistant', 'key' => 'ai_chatbot', 'free' => false],
            ],
        ];

        $planFeatureKeys = [];
        foreach ($visiblePlans as $plan) {
            $planFeatureKeys[$plan->id] = array_keys($flattenFeatureLabels(is_array($plan->features) ? $plan->features : []));
        }

        $freePlan = $visiblePlans->first(fn ($plan) => $plan->isFree());
        $premiumPlan = $visiblePlans
            ->filter(fn ($plan) => !$plan->isFree() && !str_contains(strtolower($plan->name), 'plus') && !str_contains(strtolower($plan->name), 'max'))
            ->sortByDesc(fn ($plan) => str_contains(strtolower($plan->name), 'annual') || str_contains(strtolower($plan->name), 'year'))
            ->first();
        $plusPlan = $visiblePlans->first(fn ($plan) => str_contains(strtolower($plan->name), 'plus') || str_contains(strtolower($plan->name), 'max'));

        $benefitCards = [
            [
                'title' => 'Finish more lessons',
                'copy' => 'Unlock full modules, saved progress, and a cleaner path through every topic.',
                'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                'iconWrap' => 'bg-brand-50 text-brand-600 ring-brand-200',
            ],
            [
                'title' => 'Earn certificates',
                'copy' => 'Get rewarded for completing premium learning paths and milestone achievements.',
                'icon' => 'M9 12.75 11.25 15 15 9.75M12 3l7.5 4.5v4.25c0 4.026-2.867 7.438-6.75 8.282-3.883-.844-6.75-4.256-6.75-8.282V7.5L12 3z',
                'iconWrap' => 'bg-success-50 text-success-600 ring-success-200',
            ],
            [
                'title' => 'Practice without limits',
                'copy' => 'Remove the free quiz cap and keep learning with unlimited attempts and deeper analytics.',
                'icon' => 'M11.25 6.75h-2.25m2.25 4.5h-2.25m-2.25 4.5h6.75M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12A2.25 2.25 0 0117.25 20.25H6.75A2.25 2.25 0 014.5 18V6A2.25 2.25 0 016.75 3.75z',
                'iconWrap' => 'bg-warning-50 text-warning-600 ring-warning-200',
            ],
            [
                'title' => 'Get premium support',
                'copy' => 'Access private community features, educator touchpoints, and premium-only tools.',
                'icon' => 'M18 10.5a6 6 0 01-8.414 5.478L6 19.5l1.022-3.586A6 6 0 1118 10.5z',
                'iconWrap' => 'bg-brand-purple-50 text-brand-purple-600 ring-brand-purple-200',
            ],
        ];
    @endphp

    <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-brand-950 to-slate-900">
        <div class="absolute inset-0 opacity-40">
            <div class="absolute -top-24 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-brand-500 blur-3xl"></div>
            <div class="absolute right-0 top-48 h-72 w-72 rounded-full bg-brand-purple-600 blur-3xl"></div>
            <div class="absolute left-0 bottom-0 h-80 w-80 rounded-full bg-warning-500/40 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 lg:px-8 lg:pb-24 lg:pt-10">
            <div class="mb-6 space-y-3">
                @if(session('error'))
                    <div class="flex items-center gap-3 rounded-2xl border border-error-500/30 bg-error-500/10 px-4 py-3 text-sm text-error-100 backdrop-blur">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="flex items-center gap-3 rounded-2xl border border-success-500/30 bg-success-500/10 px-4 py-3 text-sm text-success-100 backdrop-blur">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif
            </div>

            <section class="grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)] lg:items-center">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-brand-200 backdrop-blur">
                        Subscription Upgrade
                    </span>
                    <h1 class="mt-5 text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Bring your landing page energy into the full subscription experience.
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                        Clear pricing, stronger premium benefits, and a learner flow that matches the polished cards and spacing used across your admin UI.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <div class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200 backdrop-blur">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-warning-400 text-slate-950">⚡</span>
                            <span>Free plan includes {{ $freeQuizLimit }} quiz attempts per day</span>
                        </div>
                        @if($currentUser && method_exists($currentUser, 'isPremium') && $currentUser->isPremium())
                            <div class="inline-flex items-center gap-2 rounded-2xl border border-success-400/30 bg-success-500/10 px-4 py-3 text-sm text-success-100 backdrop-blur">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-success-400 text-slate-950">✓</span>
                                <span>Your premium access is active</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#plans" class="inline-flex items-center justify-center rounded-2xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white shadow-theme-lg transition hover:bg-brand-600">
                            View Plans
                        </a>
                        <a href="#comparison" class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            Compare Benefits
                        </a>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Plans</p>
                            <p class="mt-2 text-3xl font-black text-white">{{ $visiblePlans->count() }}</p>
                            <p class="mt-1 text-sm text-slate-400">Flexible premium options</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Certificates</p>
                            <p class="mt-2 text-3xl font-black text-white">Yes</p>
                            <p class="mt-1 text-sm text-slate-400">Awarded on completion</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Support</p>
                            <p class="mt-2 text-3xl font-black text-white">Priority</p>
                            <p class="mt-1 text-sm text-slate-400">For premium learners</p>
                        </div>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-lg">
                    <div class="relative overflow-hidden rounded-[32px] border border-white/10 bg-gradient-to-br from-brand-950/95 via-brand-900/90 to-brand-purple-950/95 p-5 shadow-theme-xl">
                        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.06)_1px,transparent_1px)] bg-[size:28px_28px] opacity-20"></div>
                        <div class="relative space-y-5">
                            <div class="max-w-sm rounded-[28px] border border-white/10 bg-white/95 p-4 shadow-theme-lg">
                                <div class="rounded-[22px] bg-gradient-to-r from-brand-500 to-brand-purple-600 p-5 text-white shadow-theme-md">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                </svg>
                                            </div>
                                            <p class="mt-4 text-lg font-bold">Health &amp; Wellness 101</p>
                                            <p class="mt-1 text-sm text-white/75">Module 3 of 8</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/15 px-3 py-2 text-right">
                                            <p class="text-xs uppercase tracking-[0.18em] text-white/70">Progress</p>
                                            <p class="text-sm font-bold">62%</p>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <div class="h-2 rounded-full bg-white/20">
                                            <div class="h-2 w-[62%] rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach(['Introduction to Nutrition', 'Understanding Emotions', 'Healthy Relationships', 'Personal Safety'] as $lesson)
                                        <div class="flex items-center gap-3 text-sm text-slate-700">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full {{ $loop->first || $loop->iteration === 2 ? 'bg-success-100 text-success-600' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $loop->first || $loop->iteration === 2 ? '✓' : '•' }}
                                            </span>
                                            <span class="{{ $loop->first || $loop->iteration === 2 ? 'line-through text-slate-400' : '' }}">{{ $lesson }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="absolute right-4 top-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-theme-lg sm:right-6 sm:top-5">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-warning-50 text-warning-600">🎓</span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Certificate</p>
                                        <p class="text-xs text-slate-500">Awarded on completion</p>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute -bottom-2 left-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-theme-lg sm:left-6">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">⚡</span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Live Sessions</p>
                                        <p class="text-xs text-slate-500">Every week</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <section class="mb-12">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">Subscription Benefits</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">Why upgrade inside the product</h2>
                        <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">Your landing visuals sell the value. This page now carries the same promise into the actual learner flow with clearer feature explanations and cleaner layout behavior.</p>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-white px-4 py-3 text-sm text-slate-600 shadow-theme-xs">
                        Built with the same rounded cards, spacing, and color system used in admin.
                    </div>
                </div>

                <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($benefitCards as $benefit)
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-theme-sm transition hover:-translate-y-1 hover:shadow-theme-lg">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl ring-1 {{ $benefit['iconWrap'] }}">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $benefit['icon'] }}" />
                                </svg>
                            </div>
                            <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $benefit['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $benefit['copy'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="plans" class="scroll-mt-24">
                <div class="rounded-[32px] border border-gray-200 bg-white p-6 shadow-theme-sm sm:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">Pricing</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">Choose the plan that fits your learners</h2>
                            <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">Pricing cards now use a responsive admin-style grid, more breathing room, and clearer hierarchy for descriptions, price, features, and actions.</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            All plans include core sexual health education content.
                        </div>
                    </div>

                    <div class="mt-8 grid gap-6 grid-cols-1 lg:grid-cols-2 xl:grid-cols-3">
                        @forelse($visiblePlans as $plan)
                            @php
                                $isCurrentPlan = isset($currentPlanId) && $plan->id === $currentPlanId;
                                $hasActivePlan = $hasActiveSubscription ?? false;
                                $lowerName = strtolower($plan->name);
                                $isFree = $plan->isFree();
                                $isPlus = str_contains($lowerName, 'plus') || str_contains($lowerName, 'max');
                                $isAnnual = str_contains($lowerName, 'annual') || str_contains($lowerName, 'yearly') || str_contains($lowerName, 'year');
                                $isMonthly = str_contains($lowerName, 'month');
                                $isFeatured = $premiumPlan && $plan->id === $premiumPlan->id;
                                $billingNote = $isFree ? 'Forever free' : ($isAnnual ? 'Billed annually' : ($isMonthly ? 'Billed monthly' : 'Flexible billing'));
                                $trialLabel = $plan->trial_days > 0 ? $plan->trial_days.'-day access included' : null;
                                $flatFeatures = array_values($flattenFeatureLabels(is_array($plan->features) ? $plan->features : []));

                                $cardClasses = 'border-gray-200 bg-white';
                                $iconWrap = 'bg-gray-100 text-gray-500 ring-gray-200';
                                $badgeClasses = 'bg-gray-100 text-gray-600';
                                $buttonClasses = 'bg-gray-900 text-white hover:bg-gray-800';
                                $highlightBar = 'bg-gray-200';

                                if ($isPlus) {
                                    $cardClasses = 'border-brand-purple-200 bg-gradient-to-b from-brand-purple-950 via-slate-950 to-slate-950 text-white';
                                    $iconWrap = 'bg-brand-purple-500/15 text-brand-purple-200 ring-brand-purple-400/30';
                                    $badgeClasses = 'bg-brand-purple-500 text-white';
                                    $buttonClasses = 'bg-brand-purple-500 text-white hover:bg-brand-purple-600';
                                    $highlightBar = 'bg-brand-purple-500';
                                } elseif ($isFeatured) {
                                    $cardClasses = 'border-brand-200 bg-brand-950 text-white';
                                    $iconWrap = 'bg-brand-500/15 text-brand-100 ring-brand-400/30';
                                    $badgeClasses = 'bg-warning-400 text-warning-950';
                                    $buttonClasses = 'bg-brand-500 text-white hover:bg-brand-600';
                                    $highlightBar = 'bg-brand-500';
                                } elseif ($isFree) {
                                    $cardClasses = 'border-gray-200 bg-slate-50';
                                    $iconWrap = 'bg-gray-200 text-gray-600 ring-gray-300';
                                    $badgeClasses = 'bg-gray-200 text-gray-700';
                                    $buttonClasses = 'bg-gray-300 text-gray-500';
                                    $highlightBar = 'bg-gray-300';
                                }
                            @endphp

                            <div class="relative flex h-full min-w-0 flex-col overflow-hidden rounded-[28px] border p-6 shadow-theme-sm transition hover:-translate-y-1 hover:shadow-theme-lg {{ $cardClasses }}">
                                <div class="absolute inset-x-0 top-0 h-1.5 {{ $highlightBar }}"></div>

                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl ring-1 {{ $iconWrap }}">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7.5A2.25 2.25 0 015.25 5.25h13.5A2.25 2.25 0 0121 7.5v9A2.25 2.25 0 0118.75 18.75H5.25A2.25 2.25 0 013 16.5v-9zm3.75 2.25a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5zm0 3.75a.75.75 0 000 1.5h10.5a.75.75 0 000-1.5H6.75z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-5 text-2xl font-black tracking-tight {{ $isPlus || $isFeatured ? 'text-white' : 'text-slate-900' }}">{{ $plan->name }}</h3>
                                        <p class="mt-2 text-sm leading-6 {{ $isPlus || $isFeatured ? 'text-slate-300' : 'text-slate-600' }}">
                                            {{ $plan->description ?: 'Premium learning access with feature-rich progress tools and better support.' }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col items-end gap-2">
                                        @if($isCurrentPlan)
                                            <span class="inline-flex items-center rounded-full bg-success-500 px-3 py-1 text-xs font-semibold text-white">Current</span>
                                        @elseif($isPlus)
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">MAX</span>
                                        @elseif($isFeatured)
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">Popular</span>
                                        @elseif(!$isFree)
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">Pro</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-8 rounded-3xl border {{ $isPlus || $isFeatured ? 'border-white/10 bg-white/5' : 'border-gray-200 bg-slate-50' }} p-5">
                                    <div class="flex items-end gap-2">
                                        <span class="text-4xl font-black tracking-tight {{ $isPlus || $isFeatured ? 'text-white' : 'text-slate-900' }}">₱{{ number_format($plan->price, 0) }}</span>
                                        @if(!$isFree)
                                            <span class="pb-1 text-sm font-medium {{ $isPlus || $isFeatured ? 'text-slate-300' : 'text-slate-500' }}">{{ $isAnnual ? '/year' : ($isMonthly ? '/month' : '') }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm {{ $isPlus || $isFeatured ? 'text-slate-300' : 'text-slate-500' }}">{{ $billingNote }}</p>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @if($trialLabel)
                                            <span class="inline-flex items-center rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning-700">{{ $trialLabel }}</span>
                                        @endif
                                        @if($isFree)
                                            <span class="inline-flex items-center rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700">No card required</span>
                                        @endif
                                        @if($isPlus)
                                            <span class="inline-flex items-center rounded-full bg-brand-purple-500/15 px-3 py-1 text-xs font-semibold text-brand-purple-100">All features included</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6 space-y-3">
                                    @forelse(array_slice($flatFeatures, 0, 6) as $feature)
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full {{ $isPlus ? 'bg-brand-purple-500/15 text-brand-purple-200' : ($isFeatured ? 'bg-brand-500/15 text-brand-200' : 'bg-brand-50 text-brand-600') }}">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                            <p class="min-w-0 text-sm leading-6 {{ $isPlus || $isFeatured ? 'text-slate-200' : 'text-slate-700' }}">{{ $feature }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm {{ $isPlus || $isFeatured ? 'text-slate-300' : 'text-slate-500' }}">Core premium access included.</p>
                                    @endforelse
                                </div>

                                <div class="mt-8 border-t {{ $isPlus || $isFeatured ? 'border-white/10' : 'border-gray-200' }} pt-6">
                                    @if($isFree)
                                        <button disabled class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold {{ $buttonClasses }}">
                                            Free Forever
                                        </button>
                                    @elseif($isCurrentPlan)
                                        <button disabled class="inline-flex w-full items-center justify-center rounded-2xl border border-success-400/40 bg-success-500/10 px-4 py-3 text-sm font-semibold text-success-200">
                                            You are on this plan
                                        </button>
                                    @elseif($hasActivePlan)
                                        <button disabled class="inline-flex w-full items-center justify-center rounded-2xl bg-gray-200 px-4 py-3 text-sm font-semibold text-gray-500">
                                            Cancel current plan to switch
                                        </button>
                                        <a href="{{ route('subscription.index') }}" class="mt-3 block text-center text-sm font-medium {{ $isPlus || $isFeatured ? 'text-slate-300 hover:text-white' : 'text-brand-600 hover:text-brand-700' }}">
                                            Manage existing subscription →
                                        </a>
                                    @else
                                        <form action="{{ route('subscription.subscribe') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold transition {{ $buttonClasses }}">
                                                {{ $isPlus ? 'Choose MAX' : ($isFeatured ? 'Choose PRO' : 'Choose Plan') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl border border-dashed border-gray-300 bg-slate-50 p-10 text-center text-slate-500 lg:col-span-2 xl:col-span-3">
                                No plans available right now. Contact the administrator.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            @if($visiblePlans->count() > 1)
                <section id="comparison" class="mt-12 scroll-mt-24">
                    <div class="rounded-[32px] border border-gray-200 bg-white p-6 shadow-theme-sm sm:p-8">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-600">Feature Comparison</p>
                            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">Compare plans without the layout breaking on smaller screens</h2>
                            <p class="mt-3 text-base leading-7 text-slate-600">Mobile uses stacked comparison cards. Larger screens get a clean table with the same admin-style borders, spacing, and status treatment.</p>
                        </div>

                        <div class="mt-8 space-y-6 lg:hidden">
                            @foreach($comparisonGroups as $groupLabel => $rows)
                                <div class="overflow-hidden rounded-3xl border border-gray-200 bg-slate-50">
                                    <div class="border-b border-gray-200 bg-white px-5 py-4">
                                        <h3 class="text-base font-bold text-slate-900">{{ $groupLabel }}</h3>
                                    </div>
                                    <div class="space-y-4 p-5">
                                        @foreach($rows as $row)
                                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs">
                                                <p class="text-sm font-semibold text-slate-900">{{ $row['label'] }}</p>
                                                <div class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                                    @if($freePlan)
                                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-slate-600">
                                                            <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Free</span>
                                                            <span class="mt-1 block">{{ $row['free'] ?: 'Not included' }}</span>
                                                        </div>
                                                    @endif
                                                    @if($premiumPlan)
                                                        <div class="rounded-xl bg-brand-50 px-3 py-2 text-brand-700">
                                                            <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-brand-500">Premium</span>
                                                            <span class="mt-1 block">{{ in_array($row['key'], $planFeatureKeys[$premiumPlan->id] ?? [], true) ? 'Included' : 'Not included' }}</span>
                                                        </div>
                                                    @endif
                                                    @if($plusPlan)
                                                        <div class="rounded-xl bg-brand-purple-50 px-3 py-2 text-brand-purple-700">
                                                            <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-brand-purple-500">Premium Plus</span>
                                                            <span class="mt-1 block">{{ in_array($row['key'], $planFeatureKeys[$plusPlan->id] ?? [], true) ? 'Included' : 'Not included' }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8 hidden overflow-hidden rounded-3xl border border-gray-200 lg:block">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-brand-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Feature</th>
                                            @if($freePlan)
                                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Free</th>
                                            @endif
                                            @if($premiumPlan)
                                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Premium</th>
                                            @endif
                                            @if($plusPlan)
                                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Premium Plus</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($comparisonGroups as $groupLabel => $rows)
                                            <tr class="bg-slate-50">
                                                <td colspan="{{ 1 + ($freePlan ? 1 : 0) + ($premiumPlan ? 1 : 0) + ($plusPlan ? 1 : 0) }}" class="px-6 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                    {{ $groupLabel }}
                                                </td>
                                            </tr>
                                            @foreach($rows as $row)
                                                <tr class="hover:bg-slate-50/80">
                                                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $row['label'] }}</td>
                                                    @if($freePlan)
                                                        <td class="px-6 py-4 text-center text-sm text-slate-500">
                                                            @if($row['free'])
                                                                <span class="inline-flex items-center rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning-700">{{ $row['free'] }}</span>
                                                            @else
                                                                <span>—</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                    @if($premiumPlan)
                                                        <td class="px-6 py-4 text-center text-sm text-slate-700">
                                                            @if(in_array($row['key'], $planFeatureKeys[$premiumPlan->id] ?? [], true))
                                                                <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Included</span>
                                                            @else
                                                                <span>—</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                    @if($plusPlan)
                                                        <td class="px-6 py-4 text-center text-sm text-slate-700">
                                                            @if(in_array($row['key'], $planFeatureKeys[$plusPlan->id] ?? [], true))
                                                                <span class="inline-flex items-center rounded-full bg-brand-purple-50 px-3 py-1 text-xs font-semibold text-brand-purple-700">Included</span>
                                                            @else
                                                                <span>—</span>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <div class="mt-10 text-center">
                <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-brand-600 shadow-theme-xs transition hover:bg-brand-50 hover:text-brand-700">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

