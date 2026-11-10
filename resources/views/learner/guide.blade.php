@extends('layouts.learner-app')

@section('title', 'Guide')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-6">
    @if($guideVersion)
        <x-onboarding.guide-player
            :version="$guideVersion"
            :status="$guideStatus"
            :show-completion-action="($guideStatus['status'] ?? null) !== 'completed'"
        />
    @else
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">No onboarding guide is available right now.</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600">When a guide is available for your learner dashboard, it will appear here.</p>
        </section>
    @endif
</div>
@endsection
