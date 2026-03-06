<x-app-layout>
    {{-- Dark Hero Banner --}}
    <div style="background:linear-gradient(160deg,#0d1117 0%,#1a1f3c 50%,#0d1117 100%);" class="text-white py-14 px-4 text-center border-b border-gray-800">
        <p class="text-indigo-400 text-sm font-semibold uppercase tracking-widest mb-3">Choose your plan</p>
        <h1 class="text-4xl font-extrabold tracking-tight mb-3">Unlock more lessons, practice &amp; Hearts</h1>
        <p class="text-gray-400 text-lg max-w-xl mx-auto">Start for free. Upgrade any time to unlock the full learning experience.</p>
        @if(!auth()->user()->isPremium())
            <div class="inline-flex items-center gap-2 mt-5 bg-white/5 border border-white/10 text-gray-300 text-sm px-4 py-2 rounded-full">
                <svg class="w-4 h-4 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Free plan: limited to <strong class="mx-1 text-white">{{ \App\Models\QuizDailyLimit::MAX_FREE_ATTEMPTS }} quiz attempts</strong> per day
            </div>
        @endif
    </div>

    <div style="background:#0d1117;min-height:100vh;" class="pb-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            <div class="pt-6">
                @if(session('error'))
                    <div class="mb-4 rounded-xl px-4 py-3 text-sm text-red-300 border border-red-800" style="background:rgba(127,29,29,0.3);">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="mb-4 rounded-xl px-4 py-3 text-sm text-green-300 border border-green-800" style="background:rgba(20,83,45,0.3);">{{ session('success') }}</div>
                @endif
            </div>

            @php
                $featureLabelMap = config('subscription_features.labels', []);
                $hidden = config('subscription_features.hidden', ['test_mode','duration_minutes','age_based_content_filtering']);

                if (!function_exists('flattenFeatureLabels')) {
                    function flattenFeatureLabels(array $features, array $labelMap, array $hidden = []): array {
                        $out = [];
                        foreach ($features as $group => $value) {
                            if (is_array($value)) {
                                foreach ($value as $key => $v) {
                                    if (in_array($key, $hidden)) continue;
                                    if ($v === true || (is_numeric($v) && $v > 0) || (is_string($v) && in_array($v, ['unlimited','full','advanced','priority']))) {
                                        $out[$key] = $labelMap[$key] ?? ucwords(str_replace('_', ' ', $key));
                                    }
                                }
                            } elseif (is_string($group) && ($value === true || $value === 1)) {
                                if (!in_array($group, $hidden)) {
                                    $out[$group] = $labelMap[$group] ?? ucwords(str_replace('_', ' ', $group));
                                }
                            } elseif (is_int($group) && is_string($value)) {
                                if (!in_array($value, $hidden)) {
                                    $out[$value] = $labelMap[$value] ?? ucwords(str_replace('_', ' ', $value));
                                }
                            }
                        }
                        return $out;
                    }
                }

                $visiblePlans = collect($availablePlans ?? [])->filter(fn($p) => is_object($p));
                $planCount    = $visiblePlans->count();
                $gridCols     = $planCount >= 4 ? 'sm:grid-cols-2 lg:grid-cols-4' : ($planCount === 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : ($planCount === 2 ? 'sm:grid-cols-2' : ''));
                $featuredUsed = false;
            @endphp

            {{-- ─── Plan Cards ── --}}
            <div class="grid grid-cols-1 {{ $gridCols }} gap-x-7 gap-y-7 items-stretch justify-items-center pt-12">
                @forelse($visiblePlans as $plan)
                    @php
                        $isCurrentPlan = isset($currentPlanId) && $plan->id === $currentPlanId;
                        $isActive      = $hasActiveSubscription ?? false;
                        $lowerName     = strtolower($plan->name);
                        $isFree        = $plan->isFree();
                        $isPlus        = str_contains($lowerName, 'plus') || str_contains($lowerName, 'max');
                        $isAnnual      = str_contains($lowerName, 'annual') || str_contains($lowerName, 'yearly');
                        $isMonthly     = str_contains($lowerName, 'month');

                        // First non-free non-plus annual plan gets MOST POPULAR
                        $isFeatured = !$isFree && !$isPlus && $isAnnual && !$featuredUsed;
                        if ($isFeatured) $featuredUsed = true;

                        $billingNote  = $isAnnual ? 'Billed annually' : ($isMonthly ? 'Billed monthly' : null);
                        $rawFeatures  = is_array($plan->features) ? $plan->features : [];
                        $flatFeatures = flattenFeatureLabels($rawFeatures, $featureLabelMap, $hidden);

                        $trialLabel = null;
                        if ($plan->trial_days > 0) {
                            if ($plan->trial_days >= 365)      $trialLabel = $plan->trial_days . '-Day Access (Annual)';
                            elseif ($plan->trial_days >= 28)   $trialLabel = $plan->trial_days . '-Day Access (Monthly)';
                            else                               $trialLabel = $plan->trial_days . '-Day Access';
                        }

                        // Card appearance
                        if ($isFree) {
                            $cardBg    = 'background:#161b2e;border:1px solid #2d3748;';
                            $accentClr = '#4b5563';
                            $tierBadge = '';
                            $checkClr  = '#9ca3af';
                            $btnStyle  = 'background:#1f2937;color:#6b7280;cursor:not-allowed;';
                            $btnText   = 'Free — always';
                        } elseif ($isPlus) {
                            $cardBg    = 'background:#12082a;border:2px solid #7c3aed;';
                            $accentClr = '#7c3aed';
                            $tierBadge = '<span style="background:#7c3aed;color:#fff;font-size:0.6rem;font-weight:800;padding:2px 10px;border-radius:20px;letter-spacing:.06em;">MAX</span>';
                            $checkClr  = '#c4b5fd';
                            $btnStyle  = 'background:linear-gradient(90deg,#7c3aed,#6d28d9);color:#fff;';
                            $btnText   = 'Choose Plan';
                        } elseif ($isFeatured) {
                            $cardBg    = 'background:#0e1f45;border:2px solid #3b82f6;';
                            $accentClr = '#3b82f6';
                            $tierBadge = '<span style="background:#f59e0b;color:#78350f;font-size:0.6rem;font-weight:800;padding:2px 10px;border-radius:20px;letter-spacing:.06em;">POPULAR</span>';
                            $checkClr  = '#60a5fa';
                            $btnStyle  = 'background:linear-gradient(90deg,#2563eb,#1d4ed8);color:#fff;';
                            $btnText   = 'Choose Plan';
                        } else {
                            $cardBg    = 'background:#0e1a36;border:1px solid #1e40af;';
                            $accentClr = '#22d3ee';
                            $tierBadge = '<span style="background:#1d4ed8;color:#bfdbfe;font-size:0.6rem;font-weight:800;padding:2px 10px;border-radius:20px;letter-spacing:.06em;">PRO</span>';
                            $checkClr  = '#22d3ee';
                            $btnStyle  = 'background:linear-gradient(90deg,#06b6d4,#0891b2);color:#fff;';
                            $btnText   = 'Choose Plan';
                        }
                    @endphp

                    {{-- ── Card ── --}}
                    <div class="relative w-full max-w-[250px] rounded-xl flex flex-col overflow-hidden shadow-2xl" style="{{ $cardBg }}">

                        {{-- Colored top accent bar --}}
                        <div style="height:4px;background:{{ $accentClr }};"></div>

                        {{-- Popular / current badge --}}
                        @if($isFeatured)
                            <div class="absolute top-3 right-3 z-10">
                                <span class="text-xs font-black px-3 py-1 rounded-full" style="background:#f59e0b;color:#78350f;">★ POPULAR</span>
                            </div>
                        @elseif($isPlus)
                            <div class="absolute top-3 right-3 z-10">
                                <span class="text-xs font-black px-3 py-1 rounded-full" style="background:#7c3aed;color:#fff;">⭐ ALL ACCESS</span>
                            </div>
                        @elseif($isCurrentPlan)
                            <div class="absolute top-3 right-3 z-10">
                                <span class="text-xs font-black px-3 py-1 rounded-full bg-indigo-600 text-white">✓ Your plan</span>
                            </div>
                        @endif

                        <div class="p-3 flex flex-col flex-1">

                            {{-- Plan name + badge --}}
                            <div class="flex items-center gap-1 mb-1 flex-wrap">
                                <h2 class="text-lg font-black text-white">{{ $plan->name }}</h2>
                                @if(!$isFree) {!! $tierBadge !!} @endif
                            </div>

                            @if($plan->description)
                                <p class="text-[11px] leading-relaxed mb-3" style="color:#94a3b8;">{{ $plan->description }}</p>
                            @else
                                <div class="mb-5"></div>
                            @endif

                            {{-- Price block --}}
                            @if($isFree)
                                <div class="flex items-end gap-1 mb-1">
                                    <span class="text-3xl font-black text-white leading-none">0</span>
                                    <span class="text-lg font-bold mb-1" style="color:{{ $accentClr }};">₱</span>
                                </div>
                                <p class="text-[11px] mb-4" style="color:#6b7280;">Always free · No card needed</p>
                            @else
                                <div class="flex items-end gap-1 mb-1">
                                    <span class="text-3xl font-black text-white leading-none">{{ number_format($plan->price, 0) }}</span>
                                    <span class="text-lg font-bold mb-1" style="color:{{ $accentClr }};">₱</span>
                                </div>
                                <p class="text-[11px] mb-4" style="color:#6b7280;">{{ $billingNote ?? 'One-time' }}</p>
                            @endif

                            {{-- Divider --}}
                            <div class="border-t mb-5" style="border-color:rgba(255,255,255,0.07);"></div>

                            {{-- Feature list — checkmarks on LEFT --}}
                            <ul class="flex-1 space-y-1.5 mb-4">
                                @if($isFree)
                                    @foreach(['3 quiz attempts / day','Limited module access'] as $feat)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="{{ $checkClr }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="text-xs" style="color:#94a3b8;">{{ $feat }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    @if($trialLabel)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="#fcd34d" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="text-xs font-semibold" style="color:#fcd34d;">{{ $trialLabel }}</span>
                                        </li>
                                    @endif
                                    @forelse($flatFeatures as $key => $label)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="{{ $checkClr }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="text-xs text-gray-200">{{ $label }}</span>
                                        </li>
                                    @empty
                                        @if(!$trialLabel)
                                            <li class="text-[11px] italic" style="color:#6b7280;">Full premium access</li>
                                        @endif
                                    @endforelse
                                @endif
                            </ul>

                            {{-- CTA pinned to bottom --}}
                            <div class="mt-auto">
                                @if($isFree)
                                    <button disabled class="w-full py-2 rounded-lg text-xs font-bold tracking-wide" style="{{ $btnStyle }}">{{ $btnText }}</button>
                                @elseif($isCurrentPlan)
                                    <button disabled class="w-full py-2 rounded-lg text-xs font-bold border" style="background:transparent;color:#818cf8;border-color:#4f46e5;cursor:default;">✓ Current Plan</button>
                                @elseif($isActive)
                                    <button disabled class="w-full py-2 rounded-lg text-xs font-bold" style="background:#1f2937;color:#6b7280;cursor:not-allowed;">Cancel plan to switch</button>
                                    <p class="text-center text-[11px] mt-1" style="color:#6b7280;">
                                        <a href="{{ route('subscription.index') }}" class="underline hover:text-gray-300">Manage subscription →</a>
                                    </p>
                                @else
                                    <form action="{{ route('subscription.subscribe') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="w-full py-2 rounded-lg text-xs font-bold tracking-wide transition hover:opacity-90 shadow-lg" style="{{ $btnStyle }}">
                                            {{ $btnText }}
                                        </button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-16">
                        <p class="text-gray-500 text-lg">No plans available. Contact administrator.</p>
                    </div>
                @endforelse
            </div>

            {{-- ─── Feature Comparison Table ── --}}
            @if($visiblePlans->count() > 1)
            @php
                // ── Rows definition ──────────────────────────────────────────
                $comparisonGroups = [
                    '📚 Learning' => [
                        ['label' => 'Standard Module Access',    'key' => 'full_course_access',    'free' => 'limited'],
                        ['label' => 'Offline Access',            'key' => 'offline_access',        'free' => false],
                        ['label' => 'Expert Video Sessions',     'key' => 'expert_video_sessions', 'free' => false],
                        ['label' => 'Exclusive / Bonus Content', 'key' => 'exclusive_content',     'free' => false],
                    ],
                    '📝 Assessment' => [
                        ['label' => 'Unlimited Quiz Attempts',      'key' => 'unlimited_quizzes',           'free' => false],
                        ['label' => 'Completion Certificates',      'key' => 'certificates',                'free' => false],
                        ['label' => 'Advanced Progress Analytics',  'key' => 'advanced_analytics',          'free' => false],
                    ],
                    '💬 Content & Community' => [
                        ['label' => 'Downloadable Materials',       'key' => 'downloadable_materials',      'free' => false],
                        ['label' => 'Anonymous Q&A with Educators', 'key' => 'anonymous_qa',                'free' => false],
                        ['label' => 'Private Community Discussion', 'key' => 'private_community',           'free' => false],
                    ],
                    '🎧 Support & Experience' => [
                        ['label' => 'Priority Support',             'key' => 'priority_support',            'free' => false],
                        ['label' => 'Ad-Free Experience',           'key' => 'ad_free',                     'free' => false],
                    ],
                    '🚀 Premium Plus Exclusive' => [
                        ['label' => 'Appointment Booking',          'key' => 'appointment_booking',         'free' => false],
                        ['label' => 'AI Chatbot Assistant',         'key' => 'ai_chatbot',                  'free' => false],
                    ],
                ];

                // ── Build feature key sets per plan ──────────────────────────
                $planFeatureKeys = [];
                foreach ($visiblePlans as $plan) {
                    $flat = [];
                    if (is_array($plan->features)) {
                        foreach ($plan->features as $group => $value) {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    if ($v === true || (is_numeric($v) && $v > 0) || (is_string($v) && in_array($v, ['unlimited','full','advanced','priority']))) {
                                        $flat[] = $k;
                                    }
                                }
                            } elseif ($value === true) {
                                $flat[] = $group;
                            } elseif (is_int($group) && is_string($value)) {
                                $flat[] = $value;
                            }
                        }
                    }
                    $planFeatureKeys[$plan->id] = $flat;
                }

                // ── Build 3 comparison columns: Free | Premium | Premium Plus ─
                // Free plan
                $freePlan     = $visiblePlans->first(fn($p) => $p->isFree());
                // Premium = pick the annual plan (or monthly if no annual)
                $premiumPlan  = $visiblePlans
                    ->filter(fn($p) => !$p->isFree() && !str_contains(strtolower($p->name),'plus') && !str_contains(strtolower($p->name),'max'))
                    ->sortByDesc(fn($p) => str_contains(strtolower($p->name),'annual') ? 1 : 0)
                    ->first();
                // Find monthly plan price for the subtext
                $premiumMonthly = $visiblePlans
                    ->filter(fn($p) => !$p->isFree() && str_contains(strtolower($p->name),'month'))
                    ->first();
                $premiumAnnual  = $visiblePlans
                    ->filter(fn($p) => !$p->isFree() && (str_contains(strtolower($p->name),'annual')||str_contains(strtolower($p->name),'year')))
                    ->first();
                // Premium Plus
                $plusPlan     = $visiblePlans
                    ->filter(fn($p) => str_contains(strtolower($p->name),'plus') || str_contains(strtolower($p->name),'max'))
                    ->first();

                $tableCols = array_filter([
                    'free'    => $freePlan,
                    'premium' => $premiumPlan,
                    'plus'    => $plusPlan,
                ]);
            @endphp

            @if(!empty($tableCols))
            <div class="mt-20">
                <h2 class="text-3xl font-black text-center text-white mb-2">Compare all features</h2>
                <p class="text-center text-gray-500 text-sm mb-10">Premium Monthly &amp; Annual share the same features — only the billing changes.</p>

                <div class="rounded-2xl overflow-hidden border border-gray-800" style="background:#161b2e;">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                {{-- Column headers --}}
                                <tr style="background:#0d1117;border-bottom:2px solid #1e2a45;">
                                    <th class="text-left py-6 px-6 font-semibold text-gray-500 w-5/12"></th>

                                    {{-- Free --}}
                                    @if($freePlan)
                                    <th class="py-6 px-6 text-center">
                                        <div class="text-lg font-black text-gray-400">Free</div>
                                        <div class="text-2xl font-black text-white mt-1">₱0</div>
                                        <div class="text-xs text-gray-600 mt-0.5">Forever</div>
                                    </th>
                                    @endif

                                    {{-- Premium --}}
                                    @if($premiumPlan)
                                    <th class="py-6 px-6 text-center relative">
                                        <div class="inline-flex items-center gap-1.5 mb-2">
                                            <span class="text-lg font-black" style="color:#22d3ee;">Premium</span>
                                            <span class="text-xs font-bold px-2 py-0.5 rounded" style="background:#1d4ed8;color:#bfdbfe;">PRO</span>
                                        </div>
                                        {{-- Pricing options --}}
                                        <div class="flex flex-col gap-1 items-center mt-1">
                                            @if($premiumMonthly)
                                                <div class="text-xs px-3 py-1.5 rounded-lg w-full text-center" style="background:#1a2744;border:1px solid #1e40af;">
                                                    <span class="font-black text-white">₱{{ number_format($premiumMonthly->price, 0) }}</span>
                                                    <span class="text-gray-400">/mo</span>
                                                    <div class="text-gray-600" style="font-size:0.65rem;">Billed monthly</div>
                                                </div>
                                            @endif
                                            @if($premiumAnnual)
                                                <div class="text-xs px-3 py-1.5 rounded-lg w-full text-center" style="background:#1a2744;border:1px solid #f59e0b;">
                                                    <span class="font-black" style="color:#fbbf24;">₱{{ number_format($premiumAnnual->price, 0) }}</span>
                                                    <span class="text-gray-400">/yr</span>
                                                    <div style="color:#f59e0b;font-size:0.65rem;">★ Best value</div>
                                                </div>
                                            @endif
                                        </div>
                                    </th>
                                    @endif

                                    {{-- Premium Plus --}}
                                    @if($plusPlan)
                                    <th class="py-6 px-6 text-center">
                                        <div class="inline-flex items-center gap-1.5 mb-2">
                                            <span class="text-lg font-black" style="color:#a78bfa;">Premium Plus</span>
                                            <span class="text-xs font-bold px-2 py-0.5 rounded" style="background:#7c3aed;color:#fff;">MAX</span>
                                        </div>
                                        <div class="text-2xl font-black text-white mt-1">₱{{ number_format($plusPlan->price, 0) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Billed annually</div>
                                        <div class="inline-block mt-2 text-xs font-bold px-3 py-1 rounded-full" style="background:rgba(124,58,237,0.25);color:#c4b5fd;border:1px solid #7c3aed;">All features included</div>
                                    </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comparisonGroups as $groupLabel => $rows)
                                    {{-- Group header row --}}
                                    <tr style="background:#0d1117;">
                                        <td colspan="{{ count($tableCols) + 1 }}" class="px-6 pt-5 pb-2">
                                            <span class="text-xs font-bold uppercase tracking-widest" style="color:#4b5563;">{{ $groupLabel }}</span>
                                        </td>
                                    </tr>
                                    {{-- Feature rows --}}
                                    @foreach($rows as $i => $row)
                                        @php
                                            $isPlusExclusive = $groupLabel === '🚀 Premium Plus Exclusive';
                                        @endphp
                                        <tr class="border-b transition" style="border-color:#1e2a45;{{ $isPlusExclusive ? 'background:rgba(124,58,237,0.05);' : ($i % 2 === 0 ? 'background:#161b2e;' : 'background:#1a2035;') }}">
                                            <td class="py-3 px-6 {{ $isPlusExclusive ? 'text-purple-300' : 'text-gray-300' }}">
                                                {{ $row['label'] }}
                                                @if($isPlusExclusive)<span class="ml-1 text-xs" style="color:#7c3aed;">★</span>@endif
                                            </td>

                                            {{-- Free --}}
                                            @if($freePlan)
                                            <td class="py-3 px-6 text-center">
                                                @if($row['free'] === 'limited')
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="color:#f59e0b;background:rgba(245,158,11,0.15);">Limited</span>
                                                @else
                                                    <span style="color:#2d3748;font-size:1.2rem;">—</span>
                                                @endif
                                            </td>
                                            @endif

                                            {{-- Premium --}}
                                            @if($premiumPlan)
                                            @php $hasPremium = in_array($row['key'], $planFeatureKeys[$premiumPlan->id] ?? []); @endphp
                                            <td class="py-3 px-6 text-center">
                                                @if($hasPremium)
                                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="#22d3ee" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <span style="color:#2d3748;font-size:1.2rem;">—</span>
                                                @endif
                                            </td>
                                            @endif

                                            {{-- Premium Plus --}}
                                            @if($plusPlan)
                                            @php $hasPlus = in_array($row['key'], $planFeatureKeys[$plusPlan->id] ?? []); @endphp
                                            <td class="py-3 px-6 text-center" style="{{ $isPlusExclusive ? 'background:rgba(124,58,237,0.1);' : '' }}">
                                                @if($hasPlus)
                                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <span style="color:#2d3748;font-size:1.2rem;">—</span>
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach

                                {{-- CTA row --}}
                                <tr style="background:#0d1117;border-top:2px solid #1e2a45;">
                                    <td class="py-6 px-6 text-gray-600 text-xs">All plans include core sexual health education content.</td>

                                    @if($freePlan)
                                    <td class="py-6 px-6 text-center">
                                        <span class="text-xs text-gray-600">Always free</span>
                                    </td>
                                    @endif

                                    @if($premiumPlan)
                                    <td class="py-6 px-6 text-center">
                                        @php $isActivePremium = isset($currentPlanId) && ($premiumMonthly && $premiumMonthly->id === $currentPlanId || $premiumAnnual && $premiumAnnual->id === $currentPlanId); @endphp
                                        @if(!($hasActiveSubscription ?? false) || $isActivePremium)
                                            <a href="{{ route('subscription.upgrade') }}#plans" class="inline-block py-2.5 px-6 rounded-xl text-sm font-bold transition" style="background:#22d3ee;color:#083344;">
                                                Get PRO now
                                            </a>
                                        @endif
                                    </td>
                                    @endif

                                    @if($plusPlan)
                                    <td class="py-6 px-6 text-center" style="background:rgba(124,58,237,0.05);">
                                        @if(!($hasActiveSubscription ?? false))
                                            <a href="{{ route('subscription.upgrade') }}#plans" class="inline-block py-2.5 px-6 rounded-xl text-sm font-bold transition" style="background:#7c3aed;color:#fff;">
                                                Get MAX now
                                            </a>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
            @endif

            <p class="text-center text-xs text-gray-600 mt-10">
                All plans include access to our core sexual health education content. Cancel anytime.
                <a href="{{ route('dashboard') }}" class="ml-3 text-indigo-400 underline hover:text-indigo-300">← Back to dashboard</a>
            </p>
        </div>
    </div>
</x-app-layout>

