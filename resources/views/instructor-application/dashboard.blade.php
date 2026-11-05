@php
    $status = $case?->status ?? 'identity_required';
    $applicationStatus = $application?->status;
    $stage = $identityMessage || ! $case
        ? 'identity'
        : ($status !== 'approved'
            ? 'review'
            : ($applicationStatus === 'draft' || $applicationStatus === null ? 'application' : 'final'));
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
    @if(session('status'))<p class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</p>@endif
    @if(session('error'))<p class="mt-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-800" role="alert">{{ session('error') }}</p>@endif

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-live="polite">
        @if ($identityMessage)
            <h2 class="text-lg font-semibold text-gray-900">Identity verification required</h2>
            <p class="mt-2 text-sm text-gray-600">{{ $identityMessage }}</p>
            @if ($user->isLearner())
                <a href="{{ route($learnerIdentity ? 'learner.identity.status' : 'learner.identity.create') }}" class="mt-4 inline-flex rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white">Continue learner verification</a>
            @endif
        @elseif ($case)
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">Stage One: {{ str($case->status)->headline() }}</h2>
                @if ($case->status === 'pending_review')
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Pending review</span>
                @endif
            </div>
            @if ($case->status === 'pending_review')
                <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path stroke-linecap="round" d="M12 7v5l3 2"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">Your identity submission is being reviewed</p>
                        <p class="mt-1">Your Government ID and selfie were submitted successfully. An administrator is reviewing them now, so no action is needed.</p>
                        <a href="{{ route('instructor-application.dashboard') }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white transition hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Check status again</a>
                    </div>
                </div>
            @else
                <p class="mt-2 text-sm text-gray-600">
                    @switch($case->status)
                        @case('awaiting_verification') Start your Government ID and selfie verification when ready. @break
                        @case('verification_in_progress') Finish the current verification attempt. @break
                        @case('action_required') Review the requested correction and start a replacement attempt. @break
                        @case('approved') Stage One is approved. The Stage Two application will be available next. @break
                        @default Your identity case is being processed.
                    @endswitch
                </p>
            @endif
            @if ($user->isInstructorApplicant() && $case->status === 'action_required' && $case->resubmission_allowed && in_array('clarify_personal_information', $case->requested_action_codes ?? [], true))
                <a href="{{ route('instructor-application.personal-information.edit') }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl border border-purple-700 px-4 py-2 font-semibold text-purple-700">Correct personal information</a>
            @endif
            @if ($user->isInstructorApplicant() && in_array($case->status, ['awaiting_verification', 'verification_in_progress', 'action_required', 'rejected'], true))
                @php
                    $governmentIdTypes = collect(config('persona.government_id_classes', []))
                        ->mapWithKeys(fn ($personaClass, $value) => [
                            $value => data_get(config('guardian_identity.id_types', []), $value.'.label', str($value)->headline()),
                        ])
                        ->all();
                @endphp
                @if ($case->status === 'awaiting_verification' || ($case->status === 'verification_in_progress' && ! $attempt?->persona_inquiry_id))
                    <form method="POST" action="{{ route('instructor-application.identity.persona.start') }}" class="mt-4 flex flex-wrap items-end gap-3" data-instructor-form x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <label for="government_id_type" class="min-w-56 text-sm font-medium text-gray-700">Government ID type
                            <select id="government_id_type" name="government_id_type" required class="mt-1 block min-h-11 w-full rounded-xl border-gray-300">
                                @foreach ($governmentIdTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <button type="submit" :disabled="loading" class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                            <span x-show="!loading">Start identity verification</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Opening verification…
                            </span>
                        </button>
                        <p data-submit-status class="w-full text-sm text-gray-600" role="status" aria-live="polite"></p>
                    </form>
                @elseif ($case->status === 'verification_in_progress')
                    <form method="POST" action="{{ route('instructor-application.identity.persona.resume') }}" class="mt-4" data-instructor-form x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" :disabled="loading" class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                            <span x-show="!loading">Continue identity verification</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Opening verification…
                            </span>
                        </button>
                        <p data-submit-status class="mt-2 text-sm text-gray-600" role="status" aria-live="polite"></p>
                    </form>
                @else
                    <form method="POST" action="{{ route('instructor-application.identity.persona.replace') }}" class="mt-4 flex flex-wrap items-end gap-3" data-instructor-form x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <label for="replacement_government_id_type" class="min-w-56 text-sm font-medium text-gray-700">Government ID type
                            <select id="replacement_government_id_type" name="government_id_type" required class="mt-1 block min-h-11 w-full rounded-xl border-gray-300">
                                @foreach ($governmentIdTypes as $value => $label)<option value="{{ $value }}" @selected($attempt?->local_government_id_type === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <button type="submit" :disabled="loading" class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                            <span x-show="!loading">Start a replacement attempt</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Opening verification…
                            </span>
                        </button>
                        <p data-submit-status class="w-full text-sm text-gray-600" role="status" aria-live="polite"></p>
                    </form>
                @endif
            @endif
        @endif
    </div>

    <div class="mt-5 rounded-2xl border border-gray-200 bg-white p-5" aria-live="polite">
        <h2 class="text-lg font-semibold text-gray-900">Stage Two: Instructor application</h2>
        @if (! $case || $case->status !== 'approved')
            <p class="mt-2 text-sm text-gray-600">Stage Two opens only after an administrator approves your local Stage One identity case.</p>
        @elseif (! $application)
            <p class="mt-2 text-sm text-gray-600">Stage One is approved. Add your qualifications when you are ready for the current instructor review.</p>
            <a href="{{ route('instructor-application.application.edit') }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white">Start Stage Two</a>
        @else
            <p class="mt-2 text-sm text-gray-600">Current status: <span class="font-semibold text-gray-900">{{ str($application->status)->headline() }}</span></p>
            @switch($application->status)
                @case('draft')
                    <p class="mt-2 text-sm text-gray-600">Your saved draft is not visible for final review yet.</p>
                    <a href="{{ route('instructor-application.application.edit') }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white">Resume Stage Two</a>
                    @break
                @case('pending')
                    @if(!empty($application->application_metadata['document_change_request']['slots']))
                        <p class="mt-2 text-sm text-amber-800">An administrator has requested replacement documents before your review can continue.</p>
                        <a href="{{ route('instructor-application.documents.requested', $application) }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-4 py-2 font-semibold text-white">Review requested documents</a>
                    @else
                        <p class="mt-2 text-sm text-gray-600">An administrator is reviewing your current qualifications. Instructor access remains unchanged until a final decision.</p>
                    @endif
                    @break
                @case('approved')
                    <p class="mt-2 text-sm text-emerald-700">Final application approval is recorded. Your instructor workspace is now available.</p>
                    <a href="{{ route('instructor.dashboard') }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-emerald-600 px-4 py-2 font-semibold text-white">Open instructor workspace</a>
                    @break
                @case('rejected')
                    <p class="mt-2 text-sm text-rose-700">This application was not approved. Review the administrator message in your notifications before starting a new application.</p>
                    @break
            @endswitch
        @endif
    </div>

    <div class="mt-5 text-sm text-gray-600">
        <a href="{{ route('instructor-application.personal-information.edit') }}" class="text-purple-700 underline">Review personal information</a>
    </div>
</x-auth-split-layout>
