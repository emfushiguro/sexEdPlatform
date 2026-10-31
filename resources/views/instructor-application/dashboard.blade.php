@php
    $status = $case?->status ?? 'identity_required';
    $stage = $status === 'approved' ? 'application' : ($status === 'pending_review' ? 'review' : 'identity');
@endphp
<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Instructor application</h2>
            <p class="mt-4 max-w-sm text-white/80">This workspace keeps identity review separate from final instructor approval.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-purple-900">Your instructor application</h1>
        <p class="mt-1 text-sm text-gray-600">Identity evidence does not automatically approve an instructor application.</p>
    </div>

    @include('instructor-application.partials.stage-tracker', ['stage' => $stage])

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-live="polite">
        @if ($identityMessage)
            <h2 class="text-lg font-semibold text-gray-900">Identity verification required</h2>
            <p class="mt-2 text-sm text-gray-600">{{ $identityMessage }}</p>
            @if ($user->isLearner())
                <a href="{{ route($learnerIdentity ? 'learner.identity.status' : 'learner.identity.create') }}" class="mt-4 inline-flex rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white">Continue learner verification</a>
            @endif
        @elseif ($case)
            <h2 class="text-lg font-semibold text-gray-900">Stage One: {{ str($case->status)->headline() }}</h2>
            <p class="mt-2 text-sm text-gray-600">
                @switch($case->status)
                    @case('awaiting_verification') Start your Government ID and Selfie verification when ready. @break
                    @case('verification_in_progress') Finish the current verification attempt. @break
                    @case('pending_review') An administrator is reviewing your identity evidence. @break
                    @case('action_required') Review the requested correction and start a replacement attempt. @break
                    @case('approved') Stage One is approved. The Stage Two application will be available next. @break
                    @default Your identity case is being processed.
                @endswitch
            </p>
        @endif
    </div>

    <div class="mt-5 text-sm text-gray-600">
        <a href="{{ route('instructor-application.personal-information.edit') }}" class="text-purple-700 underline">Review personal information</a>
    </div>
</x-auth-split-layout>
