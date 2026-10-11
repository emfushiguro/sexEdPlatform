@extends('layouts.admin')

@section('title', 'Edit Testimonial')
@section('page-title', 'Edit Testimonial')

@section('content')
<main class="mx-auto max-w-3xl space-y-6 px-4 py-8">
    <header><p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Testimonials</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950">Edit testimonial</h1><p class="mt-2 text-sm text-gray-500">Edit the quotation and layout order. The submitter’s public name is fixed to their account username.</p></header>
    <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf @method('PUT')
        <div class="rounded-xl border border-purple-100 bg-purple-50/60 p-4"><p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Public name</p><p class="mt-1 font-semibold text-gray-900">{{ $testimonial->display_name }}</p><p class="mt-1 text-xs text-gray-500">This identity snapshot cannot be edited by administrators.</p></div>
        <div><label for="testimonial-display-role" class="text-sm font-semibold text-gray-800">Display role</label><p id="testimonial-role-help" class="mt-1 text-xs text-gray-500">Shown only when the submitter allowed their role.</p><input id="testimonial-display-role" name="display_role" value="{{ old('display_role', $testimonial->display_role) }}" aria-describedby="testimonial-role-help" class="mt-2 w-full rounded-xl border-gray-300"></div>
        <div><label for="testimonial-quotation" class="text-sm font-semibold text-gray-800">Quotation</label><p id="testimonial-quotation-help" class="mt-1 text-xs text-gray-500">Maximum 1,000 characters.</p><textarea id="testimonial-quotation" name="quotation" maxlength="1000" rows="7" aria-describedby="testimonial-quotation-help" class="mt-2 w-full rounded-xl border-gray-300" required>{{ old('quotation', $testimonial->quotation) }}</textarea></div>
        <div><label for="testimonial-sort-order" class="text-sm font-semibold text-gray-800">Display order</label><input id="testimonial-sort-order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $testimonial->sort_order) }}" class="mt-2 w-32 rounded-xl border-gray-300"></div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4"><p class="text-sm font-semibold text-gray-900">Profile image consent</p><p class="mt-1 text-xs leading-5 text-gray-600">{{ $testimonial->show_profile_image ? 'The submitter consented to showing their profile image.' : 'The submitter did not consent to showing a profile image.' }} This setting is controlled by the submitter.</p></div>
        <div class="flex justify-end gap-3 border-t border-gray-100 pt-6"><a href="{{ route('admin.testimonials.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700">Cancel</a><button class="min-h-11 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Save testimonial</button></div>
    </form>
</main>
@endsection
