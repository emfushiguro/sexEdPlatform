@extends(auth()->user()->role === 'instructor' ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', 'Share Your Experience | Conscious Connections')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] px-4 py-8 text-gray-900 dark:bg-gray-950 dark:text-gray-100 sm:px-6">
    <div class="mx-auto max-w-3xl">
        <nav aria-label="Breadcrumb" class="text-sm"><a href="{{ route('help.index') }}" class="font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300">← Back to Help Center</a></nav>
        <div class="mt-7"><x-support.page-header icon="testimonial" title="Share Your Experience" description="Tell future learners what has helped you. Your testimonial stays in draft until an administrator reviews it." /></div>

        @if($errors->any())
            <div role="alert" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold">Please correct the highlighted fields.</p></div>
        @endif

        <form method="POST" action="{{ route('testimonials.store') }}" class="mt-7 space-y-6 rounded-2xl border border-purple-100 bg-white p-5 shadow-sm sm:p-7 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
            <div class="rounded-xl border border-purple-100 bg-purple-50/60 p-4 dark:border-purple-900 dark:bg-purple-950/30">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700 dark:text-purple-300">Public name</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $publicDisplayName }}</p>
                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">Your account name is used automatically so your public identity stays consistent.</p>
            </div>
            <div>
                <label for="testimonial-quotation" class="text-sm font-semibold text-gray-800 dark:text-gray-200">Testimonial quotation</label>
                <textarea id="testimonial-quotation" name="quotation" required maxlength="1000" rows="7" class="mt-2 w-full rounded-xl border-gray-300 bg-white px-3 py-2.5 text-sm leading-6 dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('quotation') }}</textarea>
                @error('quotation')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="rounded-xl border border-purple-200 bg-purple-50/70 p-4 dark:border-purple-900 dark:bg-purple-950/40">
                <label for="testimonial-consent" class="flex gap-3 text-sm font-semibold text-gray-900 dark:text-white"><input id="testimonial-consent" type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-gray-300 text-brand-700">I consent to this testimonial, my role, and my profile image being displayed publicly after administrator approval.</label>
                <p class="mt-2 pl-7 text-xs leading-5 text-purple-900/75 dark:text-purple-200/80">Your role and profile image are displayed with your quotation by default.</p>
                @error('consent')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end border-t border-gray-100 pt-5 dark:border-gray-800"><button class="min-h-11 rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Submit testimonial</button></div>
        </form>
    </div>
</main>
@endsection
