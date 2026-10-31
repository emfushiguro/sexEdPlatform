@extends('layouts.landing')

@section('title', 'Be Our Partner | Conscious Connections')
@section('meta_description', 'Learn how educators can apply to partner with Conscious Connections through a two-stage identity and instructor review.')

@section('content')
<a href="#partner-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-purple-900 focus:shadow-lg">Skip to content</a>

<header class="border-b border-purple-100 bg-white">
    <nav aria-label="Primary navigation" class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3 text-purple-950 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
            <img src="{{ asset('landing/sign.png') }}" alt="Conscious Connections" class="h-10 w-auto">
            <span class="text-lg font-black tracking-wide">Conscious Connections</span>
        </a>
        <div class="flex items-center gap-3 text-sm font-semibold">
            <a href="{{ route('login') }}" class="rounded-full border border-purple-200 px-4 py-2 text-purple-800 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Sign in</a>
            <a href="{{ route('home') }}" class="hidden rounded-full bg-purple-700 px-4 py-2 text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 sm:inline-flex">Back to home</a>
        </div>
    </nav>
</header>

<main id="partner-main" tabindex="-1">
    <section class="bg-purple-50 px-6 py-16 lg:px-8 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1.1fr_.9fr] lg:items-end">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-purple-700">Educator partnership</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-black tracking-tight text-purple-950 sm:text-5xl">Be Our Partner</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-700">Bring thoughtful, age-appropriate sexual health education to Filipino learners through Conscious Connections.</p>
                <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600">The process has two separate decisions: administrator identity review first, then a current instructor application and final administrator review. Neither step guarantees approval.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($enabled)
                        <a href="{{ route('instructor-application.register.personal') }}" class="inline-flex min-h-11 items-center rounded-full bg-purple-700 px-6 py-3 text-sm font-bold text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Start your application</a>
                    @else
                        <span class="inline-flex min-h-11 items-center rounded-full bg-slate-300 px-6 py-3 text-sm font-bold text-slate-700" role="status">Start is currently unavailable</span>
                    @endif
                    <a href="{{ $resumeUrl }}" class="inline-flex min-h-11 items-center rounded-full border border-purple-300 px-6 py-3 text-sm font-bold text-purple-800 hover:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">{{ $resumeUrl === route('login') ? 'Sign in to resume' : 'Resume your application' }}</a>
                </div>
            </div>
            <aside class="rounded-3xl border border-purple-200 bg-white p-6 shadow-sm" aria-labelledby="prepare-heading">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-purple-700">Before you start</p>
                <h2 id="prepare-heading" class="mt-3 text-2xl font-bold text-purple-950">Prepare for a private review</h2>
                <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-700">
                    <li>Use your own accurate personal information and a current Cavite address.</li>
                    <li>Have a Government ID and selfie ready for Stage One evidence.</li>
                    <li>Keep your qualifications, clearance, and CV or resume ready for Stage Two.</li>
                </ul>
                <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">Evidence is handled for review through protected application routes. Do not send identity documents by email or chat.</p>
            </aside>
        </div>
    </section>

    <section class="bg-white px-6 py-16 lg:px-8 lg:py-20" aria-labelledby="sequence-heading">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-purple-700">The sequence</p>
                <h2 id="sequence-heading" class="mt-3 text-3xl font-black tracking-tight text-purple-950">One clear path, two review stages</h2>
            </div>
            <ol class="mt-10 grid gap-5 md:grid-cols-2">
                @foreach ([
                    ['01', 'Personal information', 'Create an applicant-only account and confirm the personal information used for review.'],
                    ['02', 'Government ID plus Selfie', 'Complete the Persona evidence flow. Provider completion is evidence only; it does not approve your local identity case.'],
                    ['03', 'Administrator identity review', 'An administrator reviews the local identity case and may approve, request action, or reject it.'],
                    ['04', 'Instructor application', 'After Stage One access approval, submit your current qualifications for final administrator review.'],
                ] as [$number, $title, $copy])
                    <li class="rounded-2xl border border-slate-200 p-6">
                        <span class="text-sm font-black text-purple-600">{{ $number }}</span>
                        <h3 class="mt-3 text-xl font-bold text-slate-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="bg-slate-950 px-6 py-14 text-white lg:px-8" aria-labelledby="privacy-heading">
        <div class="mx-auto flex max-w-7xl flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-purple-200">Important to know</p>
                <h2 id="privacy-heading" class="mt-3 text-2xl font-bold">Verification is not automatic acceptance</h2>
                <p class="mt-3 text-sm leading-6 text-slate-300">A successful provider flow does not write a local approval, unlock Stage Two by itself, or grant instructor access. Sandbox is simulated where applicable, and administrator review remains authoritative.</p>
            </div>
            <a href="{{ route('login') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-bold text-purple-900 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-950">Sign in to resume</a>
        </div>
    </section>
</main>
@endsection
