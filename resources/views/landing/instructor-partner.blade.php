@extends('layouts.landing')

@section('title', 'Be Our Partner | Conscious Connections')
@section('meta_description', 'Learn how educators can apply to partner with Conscious Connections through a two-stage identity and instructor review.')

@section('content')
<a href="#partner-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-purple-900 focus:shadow-lg">Skip to content</a>

<header class="bg-white border-b border-purple-100">
    <nav aria-label="Primary navigation" class="flex items-center justify-between px-6 py-5 mx-auto max-w-7xl lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3 text-purple-950 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
            <img src="{{ asset('landing/sign.png') }}" alt="Conscious Connections" class="w-auto h-10">
            <span class="text-lg font-black tracking-wide">Conscious Connections</span>
        </a>
        <div class="flex items-center gap-3 text-sm font-semibold">
            <a href="{{ route('login') }}" class="px-4 py-2 text-purple-800 border border-purple-200 rounded-full hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Sign in</a>
            <a href="{{ route('home') }}" class="hidden px-4 py-2 text-white bg-purple-700 rounded-full hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 sm:inline-flex">Back to home</a>
        </div>
    </nav>
</header>

<main id="partner-main" tabindex="-1">
    <section class="px-6 py-16 bg-purple-50 lg:px-8 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1.1fr_.9fr] lg:items-end">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-purple-700">Educator partnership</p>
                <h1 class="max-w-3xl mt-4 text-4xl font-black tracking-tight text-purple-950 sm:text-5xl">Be Our Partner</h1>
                <p class="max-w-2xl mt-5 text-lg leading-8 text-slate-700">Bring thoughtful, age-appropriate sexual health education to Filipino learners through Conscious Connections.</p>
                <p class="max-w-2xl mt-4 text-sm leading-6 text-slate-600">The process has two separate decisions: administrator identity review first, then a current instructor application and final administrator review. Neither step guarantees approval.</p>
                <div class="flex flex-wrap gap-3 mt-8">
                    @if ($showStart)
                        <a href="{{ route('instructor-application.register.personal') }}" class="inline-flex items-center px-6 py-3 text-sm font-bold text-white bg-purple-700 rounded-full min-h-11 hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Start your application</a>
                    @elseif (! $enabled)
                        <span class="inline-flex items-center px-6 py-3 text-sm font-bold rounded-full min-h-11 bg-slate-300 text-slate-700" role="status">Start is currently unavailable</span>
                    @endif
                    <a href="{{ $resumeUrl }}" class="inline-flex items-center px-6 py-3 text-sm font-bold text-purple-800 border border-purple-300 rounded-full min-h-11 hover:bg-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">{{ $canResume ? 'Resume your application' : 'Sign in to resume' }}</a>
                </div>
            </div>
            <aside class="p-6 bg-white border border-purple-200 shadow-sm rounded-3xl" aria-labelledby="prepare-heading">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-purple-700">Before you start</p>
                <h2 id="prepare-heading" class="mt-3 text-2xl font-bold text-purple-950">Prepare for a private review</h2>
                <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-700">
                    <li>Use your own accurate personal information and a current address.</li>
                    <li>Have a Government ID and selfie ready for Stage One evidence.</li>
                    <li>Keep your qualifications, clearance, and CV or resume ready for Stage Two.</li>
                </ul>
                <p class="pt-4 mt-5 text-xs leading-5 border-t border-slate-100 text-slate-500">Evidence is handled for review through protected application routes. Do not send identity documents by email or chat.</p>
            </aside>
        </div>
    </section>

    <section class="px-6 py-16 bg-white lg:px-8 lg:py-20" aria-labelledby="sequence-heading">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-purple-700">The sequence</p>
                <h2 id="sequence-heading" class="mt-3 text-3xl font-black tracking-tight text-purple-950">One clear path, two review stages</h2>
            </div>
            <ol class="grid gap-5 mt-10 md:grid-cols-2">
                @foreach ([
                    ['01', 'Personal information', 'Create an applicant-only account and confirm the personal information used for review.'],
                    ['02', 'Government ID plus Selfie', 'Complete the Persona evidence flow. Provider completion is evidence only; it does not approve your local identity case.'],
                    ['03', 'Administrator identity review', 'An administrator reviews the local identity case and may approve, request action, or reject it.'],
                    ['04', 'Instructor application', 'After Stage One access approval, submit your current qualifications for final administrator review.'],
                ] as [$number, $title, $copy])
                    <li class="p-6 border rounded-2xl border-slate-200">
                        <span class="text-sm font-black text-purple-600">{{ $number }}</span>
                        <h3 class="mt-3 text-xl font-bold text-slate-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
</main>
@endsection
