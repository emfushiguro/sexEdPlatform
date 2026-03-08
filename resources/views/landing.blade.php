<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="LearnPath — The all-in-one platform for health and wellness education. Certified instructors, structured modules, and real-time progress tracking for teens and young adults.">
    <title>{{ config('app.name', 'LearnPath') }} — Learn. Grow. Thrive.</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-outfit antialiased text-gray-800 bg-white overflow-x-hidden">

{{-- ============================================================ --}}
{{-- NAVBAR                                                       --}}
{{-- ============================================================ --}}
<header id="navbar" class="fixed top-0 inset-x-0 z-50 transition-all duration-300 bg-brand-950/95 backdrop-blur-md border-b border-white/5" role="banner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group" aria-label="Go to homepage">
                <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-brand-500 text-white font-bold text-sm leading-none select-none">L</span>
                <span class="text-base font-bold text-white tracking-tight">{{ config('app.name', 'LearnPath') }}</span>
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden md:flex items-center gap-1" aria-label="Primary navigation">
                <a href="#features"    class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">Features</a>
                <a href="#how-it-works" class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">How It Works</a>
                <a href="#pricing"     class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">Pricing</a>
                <a href="#faq"         class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">FAQ</a>
            </nav>

            {{-- Desktop CTA --}}
            <div class="hidden md:flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm font-medium text-white/70 hover:text-white transition-colors">Log in</a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-brand-950 transition-colors">
                    Get Started Free
                </a>
            </div>

            {{-- Mobile hamburger --}}
            <button id="mobile-menu-btn" type="button"
                class="md:hidden inline-flex items-center justify-center rounded-lg p-2 text-white/70 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-400 transition-colors"
                aria-controls="mobile-menu" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg id="icon-open" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                <svg id="icon-close" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile overlay --}}
    <div id="mobile-overlay" class="hidden fixed inset-0 z-40 bg-black/40 backdrop-blur-sm md:hidden" aria-hidden="true"></div>

    {{-- Mobile panel --}}
    <div id="mobile-menu" class="hidden md:hidden fixed inset-x-0 top-16 z-50" aria-label="Mobile navigation">
        <div class="mx-3 mt-1 rounded-2xl bg-brand-950 border border-white/10 shadow-2xl overflow-hidden">
            <div class="px-4 pt-3 pb-3 space-y-1">
                <a href="#features" class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">Features</a>
                <a href="#how-it-works" class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">How It Works</a>
                <a href="#pricing" class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">Pricing</a>
                <a href="#faq" class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">FAQ</a>
            </div>
            <div class="border-t border-white/10 px-4 pt-3 pb-4 flex flex-col gap-2">
                <a href="{{ route('login') }}" class="block text-center rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors">Log in</a>
                <a href="{{ route('register') }}" class="block text-center rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-400 transition-colors">Get Started Free</a>
            </div>
        </div>
    </div>
</header>

{{-- ============================================================ --}}
{{-- HERO                                                         --}}
{{-- ============================================================ --}}
<main>
<section id="home" class="relative min-h-screen flex items-center bg-brand-950 overflow-hidden pt-16">

    {{-- Background grid + glows --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0" style="background-image:linear-gradient(rgba(70,95,255,0.06) 1px,transparent 1px),linear-gradient(90deg,rgba(70,95,255,0.06) 1px,transparent 1px);background-size:64px 64px;"></div>
        <div class="absolute -top-32 left-1/4 h-[600px] w-[600px] rounded-full bg-brand-600 opacity-10 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-[500px] w-[500px] rounded-full bg-brand-400 opacity-8 blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 w-full">
        <div class="grid lg:grid-cols-2 gap-6 sm:gap-8 items-center">

            {{-- Text --}}
            <div class="flex flex-col justify-center lg:items-start items-center text-center lg:text-left">
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-100 px-3.5 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200 mb-4">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    Trusted by 2,000+ learners
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight tracking-tight">
                    Learn Smarter.<br>
                    <span class="text-brand-600">Grow Faster.</span>
                </h1>
                <p class="mt-4 text-lg text-gray-600 leading-relaxed max-w-lg lg:mx-0">
                    An interactive learning platform with certified instructors, structured modules, and real-time progress tracking — designed to help you thrive.
                </p>
                <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start w-full max-w-xs lg:max-w-none">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors">
                        Get Started — It's Free
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#pricing" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors shadow-sm">
                        View Plans
                    </a>
                </div>
                <p class="mt-4 text-xs text-gray-400">No credit card required &middot; Free plan available</p>
            </div>

            {{-- Illustration placeholder --}}
            <div class="flex justify-center lg:justify-end items-center" aria-hidden="true">
                <div class="relative w-full max-w-md mt-8 lg:mt-0">
                    {{-- Main card --}}
                    <div class="rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden p-1">
                        <div class="rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 p-6 text-white">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Health &amp; Wellness 101</p>
                                    <p class="text-xs text-white/70">Module 3 of 8</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs text-white/80 mb-1">
                                    <span>Progress</span><span>62%</span>
                                </div>
                                <div class="h-2 rounded-full bg-white/20"><div class="h-2 rounded-full bg-white" style="width:62%"></div></div>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            @foreach(['Introduction to Nutrition ✓', 'Understanding Emotions ✓', 'Healthy Relationships', 'Personal Safety'] as $lesson)
                            <div class="flex items-center gap-3 text-sm {{ str_contains($lesson, '✓') ? 'text-success-600 line-through' : 'text-gray-700 font-medium' }}">
                                <span class="h-5 w-5 flex-shrink-0 rounded-full {{ str_contains($lesson, '✓') ? 'bg-success-100 text-success-600' : 'bg-brand-100 text-brand-600' }} flex items-center justify-center text-xs">
                                    {{ str_contains($lesson, '✓') ? '✓' : '–' }}
                                </span>
                                {{ str_replace(' ✓', '', $lesson) }}
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Floating badge --}}
                    <div class="absolute -top-4 -right-4 rounded-2xl bg-white shadow-lg border border-gray-100 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-lg">🎓</span>
                        <div>
                            <p class="text-xs font-semibold text-gray-800">Certificate</p>
                            <p class="text-xs text-gray-400">Awarded on completion</p>
                        </div>
                    </div>

                    {{-- Floating stat --}}
                    <div class="absolute -bottom-4 -left-4 rounded-2xl bg-white shadow-lg border border-gray-100 px-4 py-2.5 flex items-center gap-2">
                        <span class="text-lg">⚡</span>
                        <div>
                            <p class="text-xs font-semibold text-gray-800">Live Sessions</p>
                            <p class="text-xs text-gray-400">Every week</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- SOCIAL PROOF STRIP --}}
{{-- ============================================================ --}}
<section class="bg-gray-50 border-y border-gray-100 py-6 sm:py-8" aria-label="Statistics">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <dl class="grid grid-cols-2 gap-6 sm:grid-cols-4">
            @foreach([
                ['2,000+', 'Active Learners'],
                ['50+', 'Learning Modules'],
                ['12', 'Certified Instructors'],
                ['98%', 'Satisfaction Rate'],
            ] as [$value, $label])
            <div class="text-center">
                <dt class="text-3xl font-extrabold text-brand-600">{{ $value }}</dt>
                <dd class="mt-1 text-sm text-gray-500">{{ $label }}</dd>
            </div>
            @endforeach
        </dl>
    </div>
</section>

{{-- ============================================================ --}}
{{-- FEATURES --}}
{{-- ============================================================ --}}
<section id="features" class="py-10 sm:py-16 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Platform Features</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Everything you need to learn effectively</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Our platform combines modern pedagogy with powerful tools to make your learning journey seamless and rewarding.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @php
            $features = [
                ['icon' => '🧩', 'title' => 'Interactive Modules', 'desc' => 'Engaging lessons built with quizzes, activities, and multimedia to reinforce understanding at every step.', 'color' => 'bg-brand-50 text-brand-700'],
                ['icon' => '👩‍🏫', 'title' => 'Certified Instructors', 'desc' => 'All content is created and reviewed by verified, credentialed educators with real-world expertise.', 'color' => 'bg-success-50 text-success-700'],
                ['icon' => '📊', 'title' => 'Progress Tracking', 'desc' => 'Stay motivated with a visual dashboard that shows exactly how far you\'ve come and what\'s next.', 'color' => 'bg-warning-50 text-warning-700'],
                ['icon' => '🔒', 'title' => 'Secure Subscription', 'desc' => 'Industry-standard encryption and secure payment processing ensures your data and transactions are always safe.', 'color' => 'bg-brand-50 text-brand-700'],
                ['icon' => '🤝', 'title' => 'Community Learning', 'desc' => 'Connect with fellow learners, share insights, ask questions, and grow together in a supportive environment.', 'color' => 'bg-success-50 text-success-700'],
                ['icon' => '🎓', 'title' => 'Completion Certificates', 'desc' => 'Earn verifiable digital certificates for every course you complete and share them with confidence.', 'color' => 'bg-warning-50 text-warning-700'],
            ];
            @endphp

            @foreach($features as $feature)
            <article class="group rounded-2xl border border-gray-100 bg-white p-7 hover:border-brand-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $feature['color'] }} text-2xl mb-5 group-hover:scale-110 transition-transform duration-200" aria-hidden="true">{{ $feature['icon'] }}</span>
                <h3 class="text-base font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $feature['desc'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- HOW IT WORKS --}}
{{-- ============================================================ --}}
<section id="how-it-works" class="py-10 sm:py-16 bg-gradient-to-br from-brand-25 to-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">How It Works</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Start learning in four simple steps</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Getting started is quick and easy. From sign-up to first lesson takes less than 2 minutes.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 relative">
            {{-- Connector line (desktop) --}}
            <div class="hidden lg:block absolute top-10 left-[12.5%] right-[12.5%] h-px bg-brand-100" aria-hidden="true"></div>

            @php
            $steps = [
                ['step' => '01', 'icon' => '✉️', 'title' => 'Create an Account', 'desc' => 'Sign up in seconds with just your email. No credit card needed to get started.'],
                ['step' => '02', 'icon' => '📋', 'title' => 'Choose a Plan', 'desc' => 'Pick the plan that fits your goals — start free or unlock premium content.'],
                ['step' => '03', 'icon' => '▶️', 'title' => 'Start Learning', 'desc' => 'Dive into structured modules guided by certified instructors at your own pace.'],
                ['step' => '04', 'icon' => '📈', 'title' => 'Track Your Progress', 'desc' => 'Monitor milestones, earn certificates, and celebrate every achievement.'],
            ];
            @endphp

            @foreach($steps as $step)
            <div class="flex flex-col items-center text-center">
                <div class="relative z-10 mb-5">
                    <span class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-white border-2 border-brand-200 shadow-sm text-3xl" aria-hidden="true">{{ $step['icon'] }}</span>
                    <span class="absolute -top-1 -right-1 h-6 w-6 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center leading-none">{{ $step['step'] }}</span>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors">
                Begin Your Journey
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- PRICING --}}
{{-- ============================================================ --}}
<section id="pricing" class="py-10 sm:py-16 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Pricing</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Simple, transparent pricing</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Start for free and upgrade whenever you're ready. No hidden fees, no surprises.</p>
        </div>

        <div class="grid sm:grid-cols-3 gap-4 lg:gap-6 max-w-5xl mx-auto">

            {{-- Free Plan --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-7 flex flex-col">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Free</p>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-gray-900">₱0</span>
                        <span class="text-sm text-gray-400 pb-1">/forever</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">Perfect for exploring the platform before committing.</p>
                </div>
                <ul class="space-y-3 mb-8 flex-1" role="list">
                    @foreach(['Access 3 free modules', 'Basic progress tracking', 'Community forum access', 'Email support'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <svg class="h-5 w-5 flex-shrink-0 text-success-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                    @foreach(['Live instructor sessions', 'Certificates of completion'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-gray-400">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-300 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Get Started Free
                </a>
            </div>

            {{-- Premium Monthly — Highlighted --}}
            <div class="relative rounded-2xl border-2 border-brand-500 bg-white p-7 flex flex-col shadow-xl">
                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 inline-flex rounded-full bg-brand-600 px-4 py-1 text-xs font-bold text-white tracking-wide">MOST POPULAR</span>
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-600 mb-2">Premium Monthly</p>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-gray-900">₱129</span>
                        <span class="text-sm text-gray-400 pb-1">/month</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">Full access to all modules and features, billed monthly.</p>
                </div>
                <ul class="space-y-3 mb-8 flex-1" role="list">
                    @foreach(['All modules unlocked', 'Advanced progress tracking', 'Live instructor sessions', 'Certificates of completion', 'Community forum access', 'Priority email support'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <svg class="h-5 w-5 flex-shrink-0 text-brand-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Start Premium
                </a>
            </div>

            {{-- Annual --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-7 flex flex-col">
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Premium Annual</p>
                        <span class="rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">Save 16%</span>
                    </div>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-gray-900">₱1,299</span>
                        <span class="text-sm text-gray-400 pb-1">/year</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">Everything in Premium Monthly, billed annually at a discount.</p>
                </div>
                <ul class="space-y-3 mb-8 flex-1" role="list">
                    @foreach(['All modules unlocked', 'Advanced progress tracking', 'Live instructor sessions', 'Certificates of completion', 'Community forum access', 'Priority email support', 'Early access to new modules'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <svg class="h-5 w-5 flex-shrink-0 text-success-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Get Annual Plan
                </a>
            </div>

        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- TESTIMONIALS --}}
{{-- ============================================================ --}}
<section id="about" class="py-10 sm:py-16 bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Testimonials</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">What our learners say</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Real feedback from real students who've transformed their understanding through our platform.</p>
        </div>

        <div class="grid sm:grid-cols-3 gap-4 sm:gap-6">
            @php
            $testimonials = [
                [
                    'quote' => 'The modules are clear, well-organized, and actually engaging. I\'ve learned so much in just a few weeks. The certified instructors make all the difference.',
                    'name' => 'Maria Santos',
                    'role' => 'High School Student',
                    'avatar' => 'MS',
                    'color' => 'bg-brand-100 text-brand-700',
                    'stars' => 5,
                ],
                [
                    'quote' => 'As a parent, I appreciate how safe and educational this platform is. My child\'s progress tracking gives me confidence that they\'re learning the right things.',
                    'name' => 'Ricardo dela Cruz',
                    'role' => 'Parent',
                    'avatar' => 'RC',
                    'color' => 'bg-success-100 text-success-700',
                    'stars' => 5,
                ],
                [
                    'quote' => 'The premium plan is totally worth it. Live sessions with instructors and instant certificates helped me build confidence I never thought I\'d have.',
                    'name' => 'Ana Reyes',
                    'role' => 'College Freshman',
                    'avatar' => 'AR',
                    'color' => 'bg-warning-100 text-warning-700',
                    'stars' => 5,
                ],
            ];
            @endphp

            @foreach($testimonials as $review)
            <blockquote class="rounded-2xl bg-white border border-gray-100 p-7 flex flex-col shadow-sm hover:shadow-md transition-shadow duration-200">
                {{-- Stars --}}
                <div class="flex gap-0.5 mb-4" aria-label="{{ $review['stars'] }} out of 5 stars">
                    @for($i = 0; $i < $review['stars']; $i++)
                    <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>

                <p class="text-sm text-gray-600 leading-relaxed flex-1">"{{ $review['quote'] }}"</p>

                <footer class="mt-6 flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full {{ $review['color'] }} text-sm font-bold flex-shrink-0" aria-hidden="true">{{ $review['avatar'] }}</span>
                    <div>
                        <cite class="not-italic text-sm font-semibold text-gray-900">{{ $review['name'] }}</cite>
                        <p class="text-xs text-gray-400">{{ $review['role'] }}</p>
                    </div>
                </footer>
            </blockquote>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- CTA BANNER --}}
{{-- ============================================================ --}}
<section class="py-10 sm:py-14 bg-gradient-to-br from-brand-600 to-brand-800 border-b border-brand-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
            Ready to start your learning journey?
        </h2>
        <p class="mt-4 text-brand-200 text-lg leading-relaxed max-w-2xl mx-auto">
            Join over 2,000 learners who have already taken the first step. Create your free account today and unlock your potential.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-brand-700 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-600 transition-colors shadow-sm">
                Start Learning Today
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 px-7 py-3.5 text-sm font-semibold text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-600 transition-colors">
                I already have an account
            </a>
        </div>
        <p class="mt-5 text-xs text-brand-300">No credit card required &middot; Free plan always available</p>
    </div>
</section>
</main>

{{-- ============================================================ --}}
{{-- FOOTER --}}
{{-- ============================================================ --}}
<footer class="bg-gray-900 text-gray-400 border-t border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">

            {{-- Brand --}}
            <div class="lg:col-span-1">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 mb-4" aria-label="Home">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-lg leading-none" aria-hidden="true">L</span>
                    <span class="text-lg font-bold text-white">{{ config('app.name', 'LearnPath') }}</span>
                </a>
                <p class="text-sm leading-relaxed">An interactive learning platform empowering teens and young adults with accessible, accurate education.</p>
                {{-- Social --}}
                <div class="mt-6 flex gap-3" aria-label="Social media links">
                    <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-800 hover:bg-brand-600 text-gray-400 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500" aria-label="Facebook">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-800 hover:bg-brand-600 text-gray-400 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500" aria-label="Twitter / X">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-800 hover:bg-brand-600 text-gray-400 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500" aria-label="Instagram">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/></svg>
                    </a>
                </div>
            </div>

            {{-- Quick Links --}}
            <nav aria-labelledby="footer-platform">
                <h3 id="footer-platform" class="text-sm font-semibold text-white uppercase tracking-widest mb-4">Platform</h3>
                <ul class="space-y-3 text-sm" role="list">
                    <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                    <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Get Started</a></li>
                </ul>
            </nav>

            {{-- Legal --}}
            <nav aria-labelledby="footer-legal">
                <h3 id="footer-legal" class="text-sm font-semibold text-white uppercase tracking-widest mb-4">Legal</h3>
                <ul class="space-y-3 text-sm" role="list">
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('certificates.verify-form') }}" class="hover:text-white transition-colors">Verify Certificate</a></li>
                </ul>
            </nav>

            {{-- Contact --}}
            <div aria-labelledby="footer-contact">
                <h3 id="footer-contact" class="text-sm font-semibold text-white uppercase tracking-widest mb-4">Contact</h3>
                <ul class="space-y-3 text-sm" role="list">
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 mt-0.5 flex-shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        <a href="mailto:support@learnpath.ph" class="hover:text-white transition-colors">support@learnpath.ph</a>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 mt-0.5 flex-shrink-0 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span>Philippines</span>
                    </li>
                </ul>
            </div>

        </div>

        <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'LearnPath') }}. All rights reserved.</p>
            <p class="text-gray-600 text-xs">Built with ❤️ in the Philippines</p>
        </div>
    </div>
</footer>

{{-- ============================================================ --}}
{{-- Minimal JS: mobile menu toggle + sticky nav shadow --}}
{{-- ============================================================ --}}
<script>
(function () {
    var btn = document.getElementById('mobile-menu-btn');
    var menu = document.getElementById('mobile-menu');
    var overlay = document.getElementById('mobile-overlay');
    var iconOpen = document.getElementById('icon-open');
    var iconClose = document.getElementById('icon-close');

    function openMenu() {
        btn.setAttribute('aria-expanded', 'true');
        menu.classList.remove('hidden');
        overlay.classList.remove('hidden');
        iconOpen.classList.add('hidden');
        iconClose.classList.remove('hidden');
    }

    function closeMenu() {
        btn.setAttribute('aria-expanded', 'false');
        menu.classList.add('hidden');
        overlay.classList.add('hidden');
        iconOpen.classList.remove('hidden');
        iconClose.classList.add('hidden');
    }

    btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        expanded ? closeMenu() : openMenu();
    });

    overlay.addEventListener('click', closeMenu);

    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeMenu);
    });

    var navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function () {
        navbar.classList.toggle('shadow-md', window.scrollY > 8);
    }, { passive: true });
})();
</script>

</body>
</html>