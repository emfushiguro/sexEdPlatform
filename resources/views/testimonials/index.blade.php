@extends(auth()->user()->role === 'instructor' ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', 'My Testimonials | Conscious Connections')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] px-4 py-8 text-gray-900 dark:bg-gray-950 dark:text-gray-100 sm:px-6">
    <div class="mx-auto max-w-4xl">
        <nav aria-label="Breadcrumb" class="mb-7 text-sm"><a href="{{ route('help.index') }}" class="font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300">← Back to Help Center</a></nav>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><x-support.page-header icon="testimonial" title="My Testimonials" description="Manage testimonials you have submitted for public consideration." /><a href="{{ route('testimonials.create') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Share Your Experience</a></div>
        @if(session('success'))<div role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
        <section class="mt-7 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @forelse($testimonials as $testimonial)
                <article class="border-b border-gray-100 p-5 last:border-b-0 dark:border-gray-800">
                    <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-semibold text-gray-950 dark:text-white">{{ $testimonial->display_name }}</h2><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $testimonial->status->label() }}</span></div>
                    <p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300">“{{ $testimonial->quotation }}”</p>
                    <p class="mt-2 text-xs text-gray-500">Submitted {{ $testimonial->created_at->format('M j, Y') }}</p>
                    @if(in_array($testimonial->status?->value, ['draft', 'published'], true))<form method="POST" action="{{ route('testimonials.withdraw', $testimonial) }}" data-confirm-submit data-confirm-title="Withdraw testimonial?" data-confirm-text="Remove this testimonial from public display and revoke consent." data-confirm-icon="warning" data-confirm-button="Withdraw" class="mt-4">@csrf @method('DELETE')<button class="min-h-10 rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Withdraw consent</button></form>@endif
                </article>
            @empty
                <div class="px-6 py-14 text-center"><h2 class="font-semibold text-gray-950 dark:text-white">No testimonials yet</h2><p class="mt-2 text-sm text-gray-500">Share your experience to submit the first draft.</p><a href="{{ route('testimonials.create') }}" class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white">Share Your Experience</a></div>
            @endforelse
        </section>
        @if($testimonials->hasPages())<div class="mt-6">{{ $testimonials->links() }}</div>@endif
    </div>
</main>
@endsection
