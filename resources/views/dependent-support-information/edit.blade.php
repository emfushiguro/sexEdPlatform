@extends('layouts.learner-app')

@section('title', 'Health & Support Information')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-col justify-between gap-3 rounded-2xl bg-purple-700 p-6 text-white sm:flex-row sm:items-center">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-purple-200">{{ $viewerContext === 'guardian' ? 'Authorized guardian view' : 'Your information' }}</p>
            <h1 class="mt-1 text-2xl font-bold">Health &amp; Support Information</h1>
            <p class="mt-2 text-sm text-purple-100">Optional details that may help with learning, accessibility, participation, or safety.</p>
        </div>
        <a href="{{ $backRoute }}" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-semibold hover:bg-white/10">Back</a>
    </div>

    <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5 text-sm text-gray-700">
        <p>This information is optional and is not used for diagnosis, treatment, or Guardian-Dependent approval.</p>
        <p class="mt-2 text-xs text-gray-600">Do not use this page for emergencies or upload medical documents.</p>
    </div>

    @if (session('success') || session('info') || $errors->any())
        <div role="alert" class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-700">
            @if (session('success'))<p class="text-emerald-700">{{ session('success') }}</p>@endif
            @if (session('info'))<p>{{ session('info') }}</p>@endif
            @if ($errors->any())
                <ul class="list-inside list-disc text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ $saveRoute }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="has_relevant_support_information" value="1">
        @if($profile)
            <input type="hidden" name="expected_updated_at" value="{{ $profile->getRawOriginal('updated_at') }}">
        @endif

        <label class="block text-sm font-medium text-gray-800">Relevant health considerations
            <textarea name="relevant_health_considerations" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300">{{ $profile?->relevant_health_considerations }}</textarea>
        </label>
        <label class="block text-sm font-medium text-gray-800">Accessibility or learning-support needs
            <textarea name="accessibility_support_needs" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300">{{ $profile?->accessibility_support_needs }}</textarea>
        </label>
        <label class="block text-sm font-medium text-gray-800">Additional relevant participation or safety information
            <textarea name="additional_relevant_information" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300">{{ $profile?->additional_relevant_information }}</textarea>
        </label>
        <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950">
            <input type="checkbox" name="purpose_acknowledged" value="1" required class="mt-0.5 rounded border-amber-300">
            <span>I understand the stated purpose and choose to provide only information relevant to platform support.</span>
        </label>
        <button type="submit" class="w-full rounded-xl bg-purple-700 px-6 py-3 font-semibold text-white">Save information</button>
    </form>

    @if($profile)
        <div class="text-xs text-gray-500">
            Last updated {{ $profile->updated_at?->format('M d, Y g:i A') }}@if($profile->updatedBy) by {{ $profile->updatedBy->name }}@endif
        </div>
        <form method="POST" action="{{ $deleteRoute }}" x-data="{ confirming: false }" class="rounded-2xl border border-red-100 bg-red-50 p-5">
            @csrf
            @method('DELETE')
            <input type="hidden" name="expected_updated_at" value="{{ $profile->getRawOriginal('updated_at') }}">
            <button type="button" @click="confirming = true" class="text-sm font-semibold text-red-700">Remove all information</button>
            <div x-cloak x-show="confirming" role="alertdialog" aria-modal="true" aria-labelledby="remove-support-title" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4">
                <h2 id="remove-support-title" class="font-semibold text-red-900">Remove all Health &amp; Support Information?</h2>
                <p class="mt-1 text-sm text-red-800">This removes the active encrypted record. Account backups follow the platform retention policy.</p>
                <input type="hidden" name="confirm_removal" value="1">
                <button type="submit" class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Confirm removal</button>
                <button type="button" @click="confirming = false" class="ml-2 text-sm font-semibold text-gray-700">Cancel</button>
            </div>
        </form>
    @endif
</div>
@endsection
