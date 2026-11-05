@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
    <a href="{{ route('admin.instructor-applications.identities.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">← Back to Instructor Applications</a>

    @if(session('success'))<p class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Instructor application</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $application->user?->full_name ?? $application->user?->name }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $application->user?->email }}</p>
        @if($identityCase)
            <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">A current Stage One identity case is linked. Review it from the centralized Instructor Applications queue when you have identity-review permission.</p>
        @endif
    </section>

    @include('admin.instructor-applications.partials.stage-two-review', [
        'application' => $application,
        'identityCase' => $identityCase,
        'canReviewApplications' => $canReviewApplications,
    ])
</div>
@endsection
