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
<body class="font-sans antialiased text-gray-800 bg-white overflow-x-hidden">

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
                <a href="#how-it-works"class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">How It Works</a>
                <a href="#pricing"     class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">Pricing</a>
                <a href="#faq"         class="px-3.5 py-2 text-sm font-medium text-white/70 hover:text-white rounded-lg hover:bg-white/8 transition-colors">FAQ</a>
            </nav>

            {{-- Desktop CTA --}}
            <div class="hidden md:flex items-center gap-3">
                <a href="{{ route('login') }}"    class="text-sm font-medium text-white/70 hover:text-white transition-colors">Log in</a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-brand-950 transition-colors">
                    Get Started Free
                </a>
            </div>

            {{-- Mobile hamburger --}}
            <button id="mobile-menu-btn" type="button"
                class="md:hidden inline-flex items-center justify-center rounded-lg p-2 text-white/70 hover:text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-brand-400 transition-colors"
                aria-controls="mobile-menu" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg id="icon-open"  class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
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
                <a href="#features"     class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">Features</a>
                <a href="#how-it-works" class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">How It Works</a>
                <a href="#pricing"      class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">Pricing</a>
                <a href="#faq"          class="block px-3 py-2.5 text-sm font-medium text-white/70 hover:text-white hover:bg-white/8 rounded-xl transition-colors">FAQ</a>
            </div>
            <div class="border-t border-white/10 px-4 pt-3 pb-4 flex flex-col gap-2">
                <a href="{{ route('login') }}"    class="block text-center rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10 transition-colors">Log in</a>
                <a href="{{ route('register') }}" class="block text-center rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-400 transition-colors">Get Started Free</a>
            </div>
        </div>
    </div>
</header>

{{-- ============================================================ --}}
{{-- HERO                                                         --}}
{{-- ============================================================ --}}
<main>

{{-- ============================================================ --}}
{{-- HERO                                                         --}}
{{-- ============================================================ --}}
<section id="home" class="relative min-h-screen flex items-center bg-brand-950 overflow-hidden pt-16">

    {{-- Background grid + glows --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0" style="background-image:linear-gradient(rgba(70,95,255,0.07) 1px,transparent 1px),linear-gradient(90deg,rgba(70,95,255,0.07) 1px,transparent 1px);background-size:64px 64px;"></div>
        <div class="absolute -top-32 left-1/4 h-[600px] w-[600px] rounded-full bg-brand-600 opacity-10 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-[500px] w-[500px] rounded-full bg-brand-400 opacity-8 blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 w-full">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">

            {{-- Copy --}}
            <div class="flex flex-col justify-center lg:items-start items-center text-center lg:text-left">
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-500/15 border border-brand-500/30 px-3.5 py-1.5 text-xs font-semibold text-brand-300 mb-6">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400 animate-pulse"></span>
                    Trusted by 2,000+ learners in the Philippines
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight">
                    <span class="text-white">Learn Smarter.</span><br>
                    <span class="bg-gradient-to-r from-brand-400 to-blue-400 bg-clip-text text-transparent">Grow Faster.</span>
                </h1>
                <p class="mt-5 text-lg text-white/55 leading-relaxed max-w-lg lg:mx-0">
                    An interactive learning platform with certified instructors, structured modules, and real-time progress tracking — designed to help teens and young adults thrive.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start w-full max-w-xs lg:max-w-none">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-brand-950 transition-colors">
                        Get Started Free
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#pricing" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white/80 hover:bg-white/10 hover:border-white/25 focus:outline-none focus:ring-2 focus:ring-white/30 transition-colors">
                        View Plans
                    </a>
                </div>
                <p class="mt-4 text-xs text-white/30">No credit card required &middot; Free plan always available</p>
            </div>

            {{-- Dashboard mockup --}}
            <div class="flex justify-center lg:justify-end items-center" aria-hidden="true">
                <div class="relative w-full max-w-md">
                    {{-- Main card --}}
                    <div class="rounded-2xl bg-brand-900/80 border border-white/10 overflow-hidden shadow-2xl backdrop-blur">
                        {{-- Browser chrome --}}
                        <div class="flex items-center gap-1.5 px-4 py-3 border-b border-white/8 bg-brand-950/60">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-400/70"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-400/70"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-green-400/70"></span>
                            <div class="ml-3 flex-1 h-5 rounded-md bg-white/8 flex items-center px-3">
                                <span class="text-[10px] text-white/30">learnpath.ph/dashboard</span>
                            </div>
                        </div>
                        {{-- Card body --}}
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-xs text-white/40 mb-0.5">Current Course</p>
                                    <p class="text-sm font-semibold text-white">Health &amp; Wellness 101</p>
                                </div>
                                <span class="rounded-full bg-brand-500/20 border border-brand-500/30 px-2.5 py-1 text-xs font-semibold text-brand-300">Module 3/8</span>
                            </div>
                            <div class="mb-1 flex justify-between text-xs text-white/40">
                                <span>Progress</span><span class="text-brand-400 font-semibold">62%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/8 mb-5">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-brand-500 to-blue-500" style="width:62%"></div>
                            </div>
                            <div class="space-y-2.5">
                                @foreach([
                                    ['Introduction to Nutrition', true],
                                    ['Understanding Emotions', true],
                                    ['Healthy Relationships', false],
                                    ['Personal Safety', false],
                                ] as [$lesson, $done])
                                <div class="flex items-center gap-3">
                                    <span class="flex-shrink-0 h-5 w-5 rounded-full flex items-center justify-center {{ $done ? 'bg-success-500/20' : 'bg-white/8' }}">
                                        @if($done)
                                        <svg class="h-3 w-3 text-success-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        @else
                                        <svg class="h-3 w-3 text-white/30" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/></svg>
                                        @endif
                                    </span>
                                    <span class="text-sm {{ $done ? 'text-white/35 line-through' : 'text-white/80' }}">{{ $lesson }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Floating certificate badge --}}
                    <div class="absolute -top-5 -right-5 rounded-2xl bg-brand-900 border border-white/10 shadow-xl px-4 py-2.5 flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-yellow-400/15 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white">Certificate</p>
                            <p class="text-xs text-white/40">Awarded on completion</p>
                        </div>
                    </div>

                    {{-- Floating live sessions badge --}}
                    <div class="absolute -bottom-5 -left-5 rounded-2xl bg-brand-900 border border-white/10 shadow-xl px-4 py-2.5 flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-brand-500/15 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white">Live Sessions</p>
                            <p class="text-xs text-white/40">Every week</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Stats strip --}}
        <div class="mt-16 sm:mt-20 pt-8 border-t border-white/8 grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-0 sm:divide-x sm:divide-white/8">
            @foreach([
                ['2,000+', 'Active Learners'],
                ['50+',    'Learning Modules'],
                ['12',     'Certified Instructors'],
                ['98%',    'Satisfaction Rate'],
            ] as [$value, $label])
            <div class="text-center sm:px-6 first:pl-0 last:pr-0">
                <p class="text-3xl font-extrabold text-white">{{ $value }}</p>
                <p class="mt-1 text-sm text-white/45">{{ $label }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- FEATURES                                                     --}}
{{-- ============================================================ --}}
<section id="features" class="py-20 sm:py-28 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Platform Features</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Everything you need to learn effectively</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Modern pedagogy meets powerful tools to make your learning journey seamless and rewarding.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
            $features = [
                [
                    'title' => 'Interactive Modules',
                    'desc'  => 'Engaging lessons built with quizzes, activities, and multimedia to reinforce understanding at every step.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.401.604-.401.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.959.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z"/>',
                    'bg' => 'bg-brand-50', 'stroke' => 'text-brand-600',
                ],
                [
                    'title' => 'Certified Instructors',
                    'desc'  => 'All content is created and reviewed by verified, credentialed educators with real-world expertise.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>',
                    'bg' => 'bg-success-50', 'stroke' => 'text-success-600',
                ],
                [
                    'title' => 'Progress Tracking',
                    'desc'  => 'Stay motivated with a visual dashboard that shows exactly how far you\'ve come and what\'s next.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
                    'bg' => 'bg-warning-50', 'stroke' => 'text-warning-600',
                ],
                [
                    'title' => 'Secure Subscription',
                    'desc'  => 'Industry-standard encryption and secure PayMongo processing ensures your data and transactions are safe.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
                    'bg' => 'bg-brand-50', 'stroke' => 'text-brand-600',
                ],
                [
                    'title' => 'Community Learning',
                    'desc'  => 'Connect with fellow learners, share insights, ask questions, and grow together in a supportive environment.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>',
                    'bg' => 'bg-success-50', 'stroke' => 'text-success-600',
                ],
                [
                    'title' => 'Completion Certificates',
                    'desc'  => 'Earn verifiable digital certificates for every course you complete and share them with confidence.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>',
                    'bg' => 'bg-warning-50', 'stroke' => 'text-warning-600',
                ],
            ];
            @endphp

            @foreach($features as $feature)
            <article class="group rounded-2xl border border-gray-100 bg-white p-7 hover:border-brand-200 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $feature['bg'] }} mb-5 group-hover:scale-110 transition-transform duration-200" aria-hidden="true">
                    <svg class="h-6 w-6 {{ $feature['stroke'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $feature['icon'] !!}</svg>
                </span>
                <h3 class="text-base font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $feature['desc'] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- FEATURE HIGHLIGHT SPLIT                                      --}}
{{-- ============================================================ --}}
<section class="py-20 sm:py-28 bg-gray-50 border-b border-gray-100 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            {{-- Mockup panel --}}
            <div class="flex justify-center" aria-hidden="true">
                <div class="w-full max-w-sm rounded-2xl bg-brand-950 border border-white/10 p-6 shadow-2xl">
                    <p class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-5">Your Learning Dashboard</p>
                    <div class="space-y-4">
                        @foreach([
                            ['Health &amp; Wellness 101', 62, 'text-brand-400', 'from-brand-500 to-blue-500'],
                            ['Understanding Emotions',    88, 'text-success-400', 'from-success-500 to-teal-500'],
                            ['Nutrition Fundamentals',    41, 'text-warning-400', 'from-warning-500 to-orange-400'],
                            ['Personal Safety',           15, 'text-brand-400', 'from-brand-600 to-purple-500'],
                        ] as [$title, $pct, $textColor, $gradFrom])
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-sm text-white/80">{{ $title }}</span>
                                <span class="text-xs font-semibold {{ $textColor }}">{{ $pct }}%</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-white/8">
                                <div class="h-1.5 rounded-full bg-gradient-to-r {{ $gradFrom }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-6 pt-5 border-t border-white/8 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-yellow-400/15 flex items-center justify-center flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">3 Certificates Earned</p>
                            <p class="text-xs text-white/40">Keep going — 2 more to unlock</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Copy --}}
            <div>
                <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Real-Time Insights</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    Real-time insight into every learning milestone
                </h2>
                <p class="mt-4 text-gray-500 text-lg leading-relaxed">Your personalized dashboard keeps you on track, motivated, and aware of exactly where to focus next.</p>
                <ul class="mt-8 space-y-4" role="list">
                    @foreach([
                        ['Visual progress bars for every enrolled course', 'text-brand-600', 'bg-brand-50'],
                        ['Instant certificate issuance on course completion', 'text-success-600', 'bg-success-50'],
                        ['Instructor feedback and milestone notifications', 'text-warning-600', 'bg-warning-50'],
                        ['Parent/guardian visibility for under-18 learners', 'text-brand-600', 'bg-brand-50'],
                    ] as [$item, $iconColor, $iconBg])
                    <li class="flex items-start gap-3">
                        <span class="flex-shrink-0 h-6 w-6 rounded-full {{ $iconBg }} flex items-center justify-center mt-0.5">
                            <svg class="h-3.5 w-3.5 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        </span>
                        <span class="text-gray-600 text-sm leading-relaxed">{{ $item }}</span>
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    See Your Dashboard
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- HOW IT WORKS                                                 --}}
{{-- ============================================================ --}}
<section id="how-it-works" class="py-20 sm:py-28 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">How It Works</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Start learning in four simple steps</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">From sign-up to first lesson in under two minutes.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 relative">
            <div class="hidden lg:block absolute top-10 left-[12.5%] right-[12.5%] h-px bg-brand-100" aria-hidden="true"></div>

            @php
            $steps = [
                ['step'=>'01','title'=>'Create an Account',  'desc'=>'Sign up with just your email. No credit card needed to get started.',
                 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>'],
                ['step'=>'02','title'=>'Choose a Plan',      'desc'=>'Start free or unlock premium content — pick the plan that fits your goals.',
                 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>'],
                ['step'=>'03','title'=>'Start Learning',     'desc'=>'Dive into structured modules guided by certified instructors at your own pace.',
                 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>'],
                ['step'=>'04','title'=>'Track Your Progress','desc'=>'Monitor milestones, earn certificates, and celebrate every achievement.',
                 'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>'],
            ];
            @endphp

            @foreach($steps as $step)
            <div class="flex flex-col items-center text-center">
                <div class="relative z-10 mb-5">
                    <span class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-brand-50 border-2 border-brand-200 shadow-sm" aria-hidden="true">
                        <svg class="h-8 w-8 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $step['icon'] !!}</svg>
                    </span>
                    <span class="absolute -top-1 -right-1 h-6 w-6 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center leading-none">{{ $step['step'] }}</span>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-14 text-center">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors">
                Begin Your Journey
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- PRICING                                                      --}}
{{-- ============================================================ --}}
<section id="pricing" class="py-20 sm:py-28 bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Pricing</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Simple, transparent pricing</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Start for free and upgrade whenever you're ready. No hidden fees.</p>
        </div>

        {{-- Billing toggle --}}
        <div class="flex items-center justify-center gap-3 mb-10">
            <button id="btn-monthly" onclick="setPeriod('monthly')" type="button"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 bg-brand-600 text-white">
                Monthly
            </button>
            <button id="btn-annual" onclick="setPeriod('annual')" type="button"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 bg-white border border-gray-200 text-gray-600 hover:border-brand-300 hover:text-brand-600">
                Annual
                <span class="ml-1.5 rounded-full bg-success-100 px-2 py-0.5 text-xs font-bold text-success-700">Save 17%</span>
            </button>
        </div>

        <div class="grid sm:grid-cols-3 gap-5 lg:gap-6 max-w-5xl mx-auto">

            {{-- Free Plan --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-7 flex flex-col">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Free</p>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-gray-900">₱0</span>
                        <span class="text-sm text-gray-400 pb-1">/forever</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">Explore the platform before committing.</p>
                </div>
                <ul class="space-y-3 mb-8 flex-1" role="list">
                    @foreach(['Access 3 free modules','Basic progress tracking','Community forum access','Email support'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <svg class="h-5 w-5 flex-shrink-0 text-success-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                    @foreach(['Live instructor sessions','Certificates of completion'] as $feat)
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

            {{-- Premium — Highlighted --}}
            <div class="relative rounded-2xl border-2 border-brand-500 bg-white p-7 flex flex-col shadow-xl">
                <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 inline-flex rounded-full bg-brand-600 px-4 py-1 text-xs font-bold text-white tracking-wide whitespace-nowrap">MOST POPULAR</span>
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-600 mb-2">Premium</p>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-gray-900 price-display" data-monthly="₱299" data-annual="₱249">₱299</span>
                        <span class="text-sm text-gray-400 pb-1">/month</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400 price-note" data-monthly="" data-annual="billed annually (₱2,988/yr)"></p>
                    <p class="mt-3 text-sm text-gray-500">Full access to all modules and features.</p>
                </div>
                <ul class="space-y-3 mb-8 flex-1" role="list">
                    @foreach(['All modules unlocked','Advanced progress tracking','Live instructor sessions','Certificates of completion','Community forum access','Priority email support'] as $feat)
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

            {{-- Organization --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-7 flex flex-col">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2">Organization</p>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-gray-900 price-display" data-monthly="₱999" data-annual="₱829">₱999</span>
                        <span class="text-sm text-gray-400 pb-1">/month</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400 price-note" data-monthly="" data-annual="billed annually (₱9,948/yr)"></p>
                    <p class="mt-3 text-sm text-gray-500">For schools, teams, and organizations.</p>
                </div>
                <ul class="space-y-3 mb-8 flex-1" role="list">
                    @foreach(['Everything in Premium','Bulk seat management','Admin analytics dashboard','Dedicated account manager','Custom onboarding','Invoice billing available'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-gray-600">
                        <svg class="h-5 w-5 flex-shrink-0 text-success-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="block text-center rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Get Organization
                </a>
            </div>

        </div>

        <p class="mt-8 text-center text-sm text-gray-500">
            Payments processed securely via PayMongo &middot; GCash, PayMaya, GrabPay &amp; Card accepted &middot; 7-day free trial on paid plans
        </p>
    </div>
</section>

{{-- ============================================================ --}}
{{-- TESTIMONIALS                                                 --}}
{{-- ============================================================ --}}
<section id="about" class="py-20 sm:py-28 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">Testimonials</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">What our learners say</h2>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">Real feedback from students who've transformed their understanding through our platform.</p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
            @php
            $testimonials = [
                ['quote'=>'The modules are clear, well-organized, and actually engaging. I\'ve learned so much in just a few weeks. The certified instructors make all the difference.','name'=>'Maria Santos','role'=>'High School Student','avatar'=>'MS','color'=>'bg-brand-100 text-brand-700','stars'=>5],
                ['quote'=>'As a parent, I appreciate how safe and educational this platform is. My child\'s progress tracking gives me confidence they\'re learning the right things.','name'=>'Ricardo dela Cruz','role'=>'Parent','avatar'=>'RC','color'=>'bg-success-100 text-success-700','stars'=>5],
                ['quote'=>'The premium plan is totally worth it. Live sessions with instructors and instant certificates helped me build confidence I never thought I\'d have.','name'=>'Ana Reyes','role'=>'College Freshman','avatar'=>'AR','color'=>'bg-warning-100 text-warning-700','stars'=>5],
            ];
            @endphp
            @foreach($testimonials as $review)
            <blockquote class="rounded-2xl bg-white border border-gray-100 p-7 flex flex-col shadow-sm hover:shadow-md transition-shadow duration-200">
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
{{-- FAQ                                                          --}}
{{-- ============================================================ --}}
<section id="faq" class="py-20 sm:py-28 bg-gray-50 border-b border-gray-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="text-sm font-semibold text-brand-600 uppercase tracking-widest">FAQ</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">Frequently asked questions</h2>
            <p class="mt-4 text-gray-500 text-lg">Everything you need to know before getting started.</p>
        </div>

        <div class="space-y-3" id="faq-list">
            @php
            $faqs = [
                ['q'=>'Is the free plan really free?',
                 'a'=>'Yes! The free plan gives you permanent access to 3 curated modules, basic progress tracking, and community forum access — no credit card required and no time limit.'],
                ['q'=>'Who creates the course content?',
                 'a'=>'All content is created and reviewed by certified educators with real-world expertise in health, wellness, and related fields. Every module goes through a quality review process before publishing.'],
                ['q'=>'Can parents monitor their child\'s progress?',
                 'a'=>'Yes. LearnPath offers parent/guardian visibility features for learners under 18. Parents can view course progress, module completion status, and earned certificates from a dedicated overview panel.'],
                ['q'=>'What payment methods are accepted?',
                 'a'=>'We accept GCash, PayMaya, GrabPay, and major credit/debit cards. All transactions are processed securely via PayMongo, a PCI-DSS compliant payment gateway.'],
                ['q'=>'Can I cancel my subscription anytime?',
                 'a'=>'Absolutely. You can cancel at any time from your account settings. Your access continues until the end of your current billing period with no penalty fees.'],
                ['q'=>'Do you offer refunds?',
                 'a'=>'Yes. We offer a 3-day refund window from the date of payment for paid plans. To request a refund, contact our support team at support@learnpath.ph within 3 days of your purchase.'],
                ['q'=>'Are the certificates verifiable?',
                 'a'=>'Yes. Every certificate issued by LearnPath includes a unique verification code. Anyone can verify a certificate\'s authenticity using our public certificate verification page.'],
            ];
            @endphp

            @foreach($faqs as $i => $faq)
            <div class="faq-item rounded-2xl border border-gray-200 bg-white overflow-hidden">
                <button type="button"
                    class="faq-trigger w-full flex items-center justify-between px-6 py-5 text-left text-sm font-semibold text-gray-900 hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500 transition-colors"
                    aria-expanded="false">
                    <span>{{ $faq['q'] }}</span>
                    <svg class="faq-icon h-5 w-5 text-gray-400 flex-shrink-0 ml-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
                <div class="faq-answer hidden px-6 pb-5">
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $faq['a'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- CTA BANNER                                                   --}}
{{-- ============================================================ --}}
<section class="py-20 sm:py-24 bg-brand-950 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute inset-0" style="background-image:linear-gradient(rgba(70,95,255,0.07) 1px,transparent 1px),linear-gradient(90deg,rgba(70,95,255,0.07) 1px,transparent 1px);background-size:64px 64px;"></div>
        <div class="absolute -top-20 left-1/3 h-80 w-80 rounded-full bg-brand-600 opacity-15 blur-3xl"></div>
    </div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
            Ready to start your learning journey?
        </h2>
        <p class="mt-4 text-white/55 text-lg leading-relaxed max-w-2xl mx-auto">
            Join over 2,000 learners who have already taken the first step. Create your free account today — no credit card required.
        </p>
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-7 py-3.5 text-sm font-bold text-white hover:bg-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 focus:ring-offset-brand-950 transition-colors shadow-sm">
                Start Learning Today
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-7 py-3.5 text-sm font-semibold text-white/80 hover:bg-white/8 hover:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/30 transition-colors">
                I already have an account
            </a>
        </div>
    </div>
</section>

</main>

{{-- ============================================================ --}}
{{-- FOOTER                                                       --}}
{{-- ============================================================ --}}
<footer class="bg-brand-950 text-white/40 border-t border-white/8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">

            {{-- Brand --}}
            <div class="lg:col-span-1">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 mb-4" aria-label="Home">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white font-bold text-lg leading-none" aria-hidden="true">L</span>
                    <span class="text-lg font-bold text-white">{{ config('app.name', 'LearnPath') }}</span>
                </a>
                <p class="text-sm leading-relaxed">An interactive learning platform empowering teens and young adults with accessible, accurate health and wellness education.</p>
                <div class="mt-6 flex gap-3" aria-label="Social media links">
                    <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/8 hover:bg-brand-500 text-white/40 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-400" aria-label="Facebook">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                    </a>
                    <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/8 hover:bg-brand-500 text-white/40 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-400" aria-label="Twitter / X">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/8 hover:bg-brand-500 text-white/40 hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-400" aria-label="Instagram">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/></svg>
                    </a>
                </div>
            </div>

            {{-- Platform --}}
            <nav aria-labelledby="footer-platform">
                <h3 id="footer-platform" class="text-sm font-semibold text-white uppercase tracking-widest mb-4">Platform</h3>
                <ul class="space-y-3 text-sm" role="list">
                    <li><a href="#features"     class="hover:text-white transition-colors">Features</a></li>
                    <li><a href="#pricing"       class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="#how-it-works"  class="hover:text-white transition-colors">How It Works</a></li>
                    <li><a href="#faq"           class="hover:text-white transition-colors">FAQ</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Get Started</a></li>
                </ul>
            </nav>

            {{-- Legal --}}
            <nav aria-labelledby="footer-legal">
                <h3 id="footer-legal" class="text-sm font-semibold text-white uppercase tracking-widest mb-4">Legal</h3>
                <ul class="space-y-3 text-sm" role="list">
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}"   class="hover:text-white transition-colors">Terms of Service</a></li>
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

        <div class="mt-12 pt-8 border-t border-white/8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'LearnPath') }}. All rights reserved.</p>
            <p class="text-white/20 text-xs">Built with ❤️ in the Philippines</p>
        </div>
    </div>
</footer>

{{-- ============================================================ --}}
{{-- JS: mobile menu + pricing toggle + FAQ accordion             --}}
{{-- ============================================================ --}}
<script>
(function () {
    // ── Mobile menu ───────────────────────────────────────────────────
    var btn     = document.getElementById('mobile-menu-btn');
    var menu    = document.getElementById('mobile-menu');
    var overlay = document.getElementById('mobile-overlay');
    var iconOpen  = document.getElementById('icon-open');
    var iconClose = document.getElementById('icon-close');

    function openMenu() {
        btn.setAttribute('aria-expanded', 'true');
        menu.classList.remove('hidden');
        overlay.classList.remove('hidden');
        iconOpen.classList.add('hidden');
        iconClose.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        btn.setAttribute('aria-expanded', 'false');
        menu.classList.add('hidden');
        overlay.classList.add('hidden');
        iconOpen.classList.remove('hidden');
        iconClose.classList.add('hidden');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', function () {
        btn.getAttribute('aria-expanded') === 'true' ? closeMenu() : openMenu();
    });
    overlay.addEventListener('click', closeMenu);
    menu.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', closeMenu); });

    // ── Sticky nav shadow ────────────────────────────────────────────
    var navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function () {
        navbar.classList.toggle('shadow-lg', window.scrollY > 8);
    }, { passive: true });

    // ── Pricing toggle ───────────────────────────────────────────────
    var btnMonthly = document.getElementById('btn-monthly');
    var btnAnnual  = document.getElementById('btn-annual');
    var activeClass   = ['bg-brand-600', 'text-white'];
    var inactiveClass = ['bg-white', 'border', 'border-gray-200', 'text-gray-600', 'hover:border-brand-300', 'hover:text-brand-600'];

    window.setPeriod = function (period) {
        document.querySelectorAll('.price-display').forEach(function (el) {
            el.textContent = el.dataset[period];
        });
        document.querySelectorAll('.price-note').forEach(function (el) {
            el.textContent = el.dataset[period];
        });
        if (period === 'monthly') {
            activeClass.forEach(function(c){ btnMonthly.classList.add(c); });
            inactiveClass.forEach(function(c){ btnMonthly.classList.remove(c); });
            inactiveClass.forEach(function(c){ btnAnnual.classList.add(c); });
            activeClass.forEach(function(c){ btnAnnual.classList.remove(c); });
        } else {
            activeClass.forEach(function(c){ btnAnnual.classList.add(c); });
            inactiveClass.forEach(function(c){ btnAnnual.classList.remove(c); });
            inactiveClass.forEach(function(c){ btnMonthly.classList.add(c); });
            activeClass.forEach(function(c){ btnMonthly.classList.remove(c); });
        }
    };

    // ── FAQ accordion ────────────────────────────────────────────────
    document.querySelectorAll('.faq-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var item   = trigger.closest('.faq-item');
            var answer = item.querySelector('.faq-answer');
            var icon   = item.querySelector('.faq-icon');
            var isOpen = trigger.getAttribute('aria-expanded') === 'true';

            // Close all others
            document.querySelectorAll('.faq-item').forEach(function (other) {
                if (other !== item) {
                    other.querySelector('.faq-trigger').setAttribute('aria-expanded', 'false');
                    other.querySelector('.faq-answer').classList.add('hidden');
                    other.querySelector('.faq-icon').style.transform = 'rotate(0deg)';
                }
            });

            trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            answer.classList.toggle('hidden', isOpen);
            icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        });
    });
})();
</script>

</body>
</html>