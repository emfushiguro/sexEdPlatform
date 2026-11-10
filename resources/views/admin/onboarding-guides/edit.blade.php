@extends('layouts.admin')

@section('title', 'Edit Onboarding Guide')
@section('page-title', 'Edit Onboarding Guide')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">Please review the highlighted fields before saving.</div>
    @endif

    @php
        $statusClass = match($guide->status) {
            \App\Models\OnboardingGuide::STATUS_PUBLISHED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\OnboardingGuide::STATUS_ARCHIVED => 'border-slate-200 bg-slate-100 text-slate-700',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
        $isRevision = $draft && $guide->current_version_id !== null && (int) $draft->id !== (int) $guide->current_version_id;
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <a href="{{ route('admin.onboarding-guides.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Back to Onboarding Guides</a>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ $version?->title ?? 'Onboarding guide' }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ str($guide->target_role)->headline() }} · {{ $version ? 'Version '.$version->version_number : 'No version' }}</p>
        </div>
        <span class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold {{ $statusClass }}">{{ str($guide->status)->headline() }}</span>
    </div>

    @include('admin.onboarding-guides.partials.form', [
        'guide' => $guide,
        'version' => $version,
        'action' => route('admin.onboarding-guides.update', $guide),
        'submitLabel' => $draft ? 'Save draft' : 'Start revision',
    ])

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Publication</p>
                <h2 class="mt-1 text-lg font-bold text-gray-900">Publish this guide</h2>
            </div>
            <a href="{{ route('admin.onboarding-guides.history', $guide) }}" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">View history</a>
        </div>

        @if($guide->status === \App\Models\OnboardingGuide::STATUS_ARCHIVED)
            <p class="mt-4 text-sm text-slate-600">Archived guides cannot be published or edited.</p>
        @else
            <form method="POST" action="{{ route('admin.onboarding-guides.publish', $guide) }}" class="mt-5 space-y-4">
                @csrf
                @if($guide->current_version_id === null)
                    <p class="rounded-xl bg-purple-50 px-4 py-3 text-sm text-purple-900">This is the initial publication. Initial onboarding is required for eligible users.</p>
                @elseif($isRevision)
                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-800">How should this revision affect users?</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-gray-200 p-4 hover:border-purple-300">
                                <input type="radio" name="requires_recompletion" value="0" @checked(old('requires_recompletion') === '0') required>
                                <span><strong class="block text-sm text-gray-900">Informational update</strong><span class="mt-1 block text-xs text-gray-500">Keep existing completion and dismissal state effective.</span></span>
                            </label>
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-gray-200 p-4 hover:border-purple-300">
                                <input type="radio" name="requires_recompletion" value="1" @checked(old('requires_recompletion', '1') === '1') required>
                                <span><strong class="block text-sm text-gray-900">Require users to complete this new version</strong><span class="mt-1 block text-xs text-gray-500">Start a new completion boundary for this revision.</span></span>
                            </label>
                        </div>
                    </fieldset>
                @else
                    <p class="text-sm text-gray-600">Republishing keeps the current version, files, and user state unchanged.</p>
                @endif

                @if($otherPublished)
                    <label class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <input type="checkbox" name="confirm_supersede" value="1" @checked(old('confirm_supersede')) class="mt-0.5" required>
                        <span>Confirm that publishing this guide should supersede the other published guide for this role.</span>
                    </label>
                @endif

                <button type="submit" class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">{{ $guide->status === \App\Models\OnboardingGuide::STATUS_PUBLISHED ? 'Publish revision' : 'Publish guide' }}</button>
            </form>
        @endif
    </section>

    @if($guide->status === \App\Models\OnboardingGuide::STATUS_PUBLISHED)
        <form method="POST" action="{{ route('admin.onboarding-guides.unpublish', $guide) }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            @csrf
            <button type="submit" class="text-sm font-semibold text-amber-800 hover:underline">Unpublish this guide</button>
        </form>
    @endif
</div>
@endsection
