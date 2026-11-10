@extends('layouts.admin')

@section('title', 'Create Onboarding Guide')
@section('page-title', 'Create Onboarding Guide')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6">
        <a href="{{ route('admin.onboarding-guides.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Back to Onboarding Guides</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">Create onboarding guide</h1>
        <p class="mt-1 text-sm text-gray-500">Save a draft before making the walkthrough available to users.</p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">Please review the highlighted fields before saving.</div>
    @endif

    @include('admin.onboarding-guides.partials.form', [
        'guide' => $guide,
        'version' => $version,
        'action' => route('admin.onboarding-guides.store'),
        'submitLabel' => 'Save draft',
    ])
</div>
@endsection
