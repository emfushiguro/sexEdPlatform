@extends('layouts.admin')

@section('title', 'Testimonials')
@section('page-title', 'Testimonials')

@section('content')
<main class="mx-auto max-w-6xl space-y-6 px-4 py-8">
    <header><p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Support management</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Testimonials</h1><p class="mt-2 text-sm text-gray-500">Review consented public experiences separately from private support tickets.</p></header>
    <form method="GET" class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-5" aria-label="Filter testimonials">
        <div><label for="testimonial-search" class="text-xs font-semibold text-gray-700">Search</label><input id="testimonial-search" name="search" value="{{ request('search') }}" placeholder="Name or quotation" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm"></div>
        <div><label for="testimonial-status" class="text-xs font-semibold text-gray-700">Status</label><select id="testimonial-status" name="status" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm"><option value="">All statuses</option>@foreach(\App\Enums\TestimonialStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
        <div><label for="testimonial-role" class="text-xs font-semibold text-gray-700">Role</label><select id="testimonial-role" name="role" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm"><option value="">All roles</option><option value="learner" @selected(request('role') === 'learner')>Learner</option><option value="instructor" @selected(request('role') === 'instructor')>Instructor</option></select></div>
        <div><label for="testimonial-from" class="text-xs font-semibold text-gray-700">From date</label><input id="testimonial-from" type="date" name="from" value="{{ request('from') }}" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm"></div>
        <div><label for="testimonial-to" class="text-xs font-semibold text-gray-700">To date</label><input id="testimonial-to" type="date" name="to" value="{{ request('to') }}" class="mt-1.5 w-full rounded-xl border-gray-300 text-sm"></div>
        <div class="md:col-span-2 xl:col-span-5 flex justify-end"><button class="min-h-10 rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Apply filters</button></div>
    </form>
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @forelse($testimonials as $testimonial)
            @php
                $eligibility = $publicationEligibility->get($testimonial->id, ['eligible' => false, 'code' => 'unknown', 'message' => 'Publication eligibility is unavailable.']);
                $canPublish = $eligibility['eligible'];
                $consentActive = $testimonial->consent_given && ! $testimonial->consent_withdrawn_at;
            @endphp
            <article class="border-b border-gray-100 p-5 last:border-b-0 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <x-support.testimonial-avatar :testimonial="$testimonial" :respect-consent="false" />
                        <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-gray-950">{{ $testimonial->display_name }}</h2><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $testimonial->status->label() }}</span><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $consentActive, 'bg-gray-100 text-gray-600' => ! $consentActive])>{{ $consentActive ? 'Consent active' : 'Consent withdrawn' }}</span>@if($testimonial->status !== \App\Enums\TestimonialStatus::Published && ! $canPublish)<span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Not publishable</span>@endif</div>
                        <p class="mt-2 text-sm leading-6 text-gray-700">“{{ str($testimonial->quotation)->limit(220) }}”</p>
                        <p class="mt-2 text-xs text-gray-500">{{ $testimonial->display_role ?: 'No public role' }} · Submitted {{ $testimonial->created_at->format('M j, Y') }} · Order {{ $testimonial->sort_order }}</p>
                        @if($testimonial->status !== \App\Enums\TestimonialStatus::Published && ! $canPublish)<p class="mt-2 text-xs font-medium text-gray-600">{{ $eligibility['message'] }}</p>@endif
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center justify-end" aria-label="Testimonial actions">
                        <a data-testimonial-action="preview" href="{{ route('admin.testimonials.preview', $testimonial) }}" title="Preview testimonial" aria-label="Preview testimonial" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-purple-200 text-purple-700 transition hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg><span class="sr-only">Preview testimonial</span></a>
                    </div>
                </div>
            </article>
        @empty
            <div class="px-6 py-14 text-center"><h2 class="font-semibold text-gray-900">No testimonials match these filters</h2><p class="mt-1 text-sm text-gray-500">Independent testimonial submissions will appear here after consent.</p></div>
        @endforelse
    </section>
    {{ $testimonials->links() }}
</main>
@endsection
