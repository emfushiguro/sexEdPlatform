@extends(auth()->user()->role === 'instructor' ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', 'Testimonial | Conscious Connections')

@section('content')
<main class="min-h-screen bg-[#F9F7FF] px-4 py-8 text-gray-900 dark:bg-gray-950 dark:text-gray-100 sm:px-6">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('testimonials.index') }}" class="text-sm font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300">← Back to My Testimonials</a>
        @if(session('success'))<div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
        <div class="mt-7"><x-support.page-header icon="testimonial" eyebrow="Testimonial submission" :title="$testimonial->display_name" :description="$testimonial->status->label()" /></div>
        <article class="mt-7 rounded-2xl border border-purple-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"><p class="text-lg leading-8 text-gray-800 dark:text-gray-200">“{{ $testimonial->quotation }}”</p><dl class="mt-6 grid gap-4 border-t border-gray-100 pt-5 text-sm sm:grid-cols-2 dark:border-gray-800"><div><dt class="text-gray-500">Role visibility</dt><dd class="mt-1 font-semibold">{{ $testimonial->display_role ? 'Shown' : 'Hidden' }}</dd></div><div><dt class="text-gray-500">Profile image</dt><dd class="mt-1 font-semibold">{{ $testimonial->show_profile_image ? 'Shown' : 'Hidden' }}</dd></div></dl>@if(in_array($testimonial->status?->value, ['draft', 'published'], true))<form method="POST" action="{{ route('testimonials.withdraw', $testimonial) }}" data-confirm-submit data-confirm-title="Withdraw testimonial?" data-confirm-text="Remove this testimonial from public display and revoke consent." data-confirm-icon="warning" data-confirm-button="Withdraw" class="mt-6">@csrf @method('DELETE')<button class="min-h-11 rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Withdraw consent</button></form>@endif</article>
    </div>
</main>
@endsection
