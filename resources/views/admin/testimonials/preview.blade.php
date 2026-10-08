@extends('layouts.admin')

@section('title', 'Preview Testimonial')
@section('page-title', 'Preview Testimonial')

@section('content')
<main class="mx-auto max-w-3xl space-y-6 px-4 py-8">
    <nav aria-label="Breadcrumb"><a href="{{ route('admin.testimonials.index') }}" class="text-sm font-semibold text-purple-700">← Back to Testimonials</a></nav>
    <section class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Public preview</p>
        <blockquote class="mt-4 text-2xl leading-9 text-gray-800">“{{ $testimonial->quotation }}”</blockquote>
        <div class="mt-6 flex items-center gap-3">
            <x-support.testimonial-avatar :testimonial="$testimonial" />
            <p class="font-semibold text-gray-900">{{ $testimonial->display_name }}@if($testimonial->display_role) · {{ $testimonial->display_role }}@endif</p>
        </div>
        <p class="mt-1 text-sm text-gray-500">{{ $testimonial->status->label() }}</p>
    </section>

    <section aria-labelledby="testimonial-review-actions" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 id="testimonial-review-actions" class="text-lg font-bold text-gray-950">Review actions</h2>
                <p class="mt-1 text-sm text-gray-500">Update this testimonial from its preview. The list stays focused on one eye action.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $testimonial->status->label() }}</span>
        </div>

        @if($testimonial->status !== \App\Enums\TestimonialStatus::Published && ! ($publicationEligibility['eligible'] ?? false))
            <p class="mt-4 text-sm text-gray-600" role="status">{{ $publicationEligibility['message'] ?? 'This testimonial is not currently eligible for publication.' }}</p>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-5" aria-label="Testimonial review actions">
            @can('update', $testimonial)
                <a data-testimonial-action="edit" href="{{ route('admin.testimonials.edit', $testimonial) }}" title="Edit testimonial" aria-label="Edit testimonial" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-gray-300 px-3.5 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m4 16.5-.75 3.75L7 19.5 18.25 8.25a2.65 2.65 0 0 0-3.75-3.75L3.25 15.75"/><path stroke-linecap="round" d="m13 6 3.75 3.75"/></svg>
                    <span>Edit</span>
                </a>
            @endcan

            @can('publish', $testimonial)
                @if($testimonial->status !== \App\Enums\TestimonialStatus::Published && ($publicationEligibility['eligible'] ?? false))
                    <form method="POST" action="{{ route('admin.testimonials.publish', $testimonial) }}" data-confirm-submit data-confirm-title="Confirm publish testimonial?" data-confirm-text="This testimonial will become visible on the public website." data-confirm-icon="question" data-confirm-button="Publish">
                        @csrf
                        <button data-testimonial-action="publish" type="submit" title="Publish testimonial" aria-label="Publish testimonial" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.25 4.25L19 7"/></svg>
                            <span>Publish</span>
                        </button>
                    </form>
                @endif
            @endcan

            @can('reject', $testimonial)
                @if(! in_array($testimonial->status, [\App\Enums\TestimonialStatus::Published, \App\Enums\TestimonialStatus::Rejected, \App\Enums\TestimonialStatus::Withdrawn], true))
                    <form method="POST" action="{{ route('admin.testimonials.reject', $testimonial) }}" data-confirm-submit data-confirm-title="Confirm reject testimonial?" data-confirm-text="Keep this testimonial private and mark it as rejected." data-confirm-icon="warning" data-confirm-button="Reject">
                        @csrf
                        <button data-testimonial-action="reject" type="submit" title="Reject testimonial" aria-label="Reject testimonial" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-amber-200 px-3.5 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="m7 7 10 10M17 7 7 17"/></svg>
                            <span>Reject</span>
                        </button>
                    </form>
                @endif
            @endcan

            @can('withdraw', $testimonial)
                @if($testimonial->status !== \App\Enums\TestimonialStatus::Withdrawn)
                    <form method="POST" action="{{ route('admin.testimonials.withdraw', $testimonial) }}" data-confirm-submit data-confirm-title="Confirm withdraw testimonial?" data-confirm-text="Remove this testimonial from the public website." data-confirm-icon="warning" data-confirm-button="Withdraw">
                        @csrf
                        <button data-testimonial-action="withdraw" type="submit" title="Withdraw testimonial" aria-label="Withdraw testimonial" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-rose-200 px-3.5 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-400">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M10 11v5m4-5v5M7 7l.75 12h8.5L17 7m-8 0V4.75A1.75 1.75 0 0 1 10.75 3h2.5A1.75 1.75 0 0 1 15 4.75V7"/></svg>
                            <span>Withdraw</span>
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </section>
</main>
@endsection
