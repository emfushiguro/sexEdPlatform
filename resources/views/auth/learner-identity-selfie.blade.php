<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Identity Verification</h2>
            <p class="mt-3 max-w-sm">Take a clear selfie to finish your verification.</p>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl space-y-6">
        <x-wizard-stepper :is-parent-flow="false" />
        @include('auth.partials.dependent-page-help', ['step' => 'identity'])
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-purple-700">Identity verification · Step 2 of 2</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Take your selfie</h1>
            <p class="mt-2 text-gray-700">Capture your selfie through Persona using your camera, then return here to submit for review. Selfie uploads are not accepted.</p>
        </header>
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-3 text-sm text-purple-900" role="status">Your identity evidence is saved. <a href="{{ route('learner.identity.create') }}" class="font-semibold underline">Change it</a></div>
        @if($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">Please review your selfie and confirmation below.</div>
        @endif
        @if($case->status === 'rejected' && $case->correction_note)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold">Reviewer message for round {{ $case->submission_round }}</p><p class="mt-2 whitespace-pre-wrap">{{ $case->correction_note }}</p></div>
        @endif

        @if(!config('persona.enabled') || blank(config('persona.templates.selfie')))
            <p role="alert" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">Camera verification is currently unavailable. Contact support or try again when Persona is available. Your saved identity evidence is kept for this session.</p>
        @elseif(! $personaAttempt?->isPolicyEligible() || $personaAttempt->local_document_type !== $draft['document_type'])
            @include('auth.partials.persona-identity-step', ['attempt' => $personaAttempt?->local_document_type === $draft['document_type'] ? $personaAttempt : null, 'documentType' => $draft['document_type'], 'allowSchoolDocuments' => true, 'selfieOnly' => true])
        @else
        <form method="POST" action="{{ route('persona.identity.replace') }}">
            @csrf
            <input type="hidden" name="document_type" value="{{ $draft['document_type'] }}">
            <button type="submit" class="min-h-11 rounded-xl border border-purple-300 px-4 py-2 font-semibold text-purple-900">Retake selfie with Persona camera</button>
        </form>
        <form method="POST" action="{{ route('learner.identity.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-purple-900" role="status">
                <p class="font-semibold">Your Persona selfie is ready.</p>
                <p class="mt-1">Submit your identity evidence and camera selfie for administrator review.</p>
            </div>
            @if($case->status === 'rejected' && $case->resubmission_allowed && $case->requested_corrections !== null)
            <div>
                <label for="learner_note" class="block font-semibold text-gray-900">Note to the reviewer (optional)</label>
                <p id="learner-note-help" class="mt-1 text-sm text-gray-600">Explain your evidence or the changes you made. This note stays with this submission round. Do not include medical information.</p>
                <textarea id="learner_note" name="learner_note" rows="3" maxlength="2000" aria-describedby="learner-note-help" class="mt-2 w-full rounded-xl border-gray-300 p-3">{{ old('learner_note') }}</textarea>
                @error('learner_note')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            @endif
            <div class="flex items-start gap-3">
                <input id="confirm_submission" name="confirm_submission" type="checkbox" value="1" required class="mt-1 h-5 w-5 accent-purple-700">
                <label for="confirm_submission" class="text-gray-800">I confirm this selfie is mine and agree to private review of the evidence I submitted under the <a class="font-semibold text-purple-800 underline" href="{{ route('privacy') }}">privacy policy</a>.</label>
            </div>
            @error('confirm_submission')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            <button type="submit" class="min-h-11 w-full rounded-xl bg-purple-800 px-6 py-3 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Submit for review</button>
        </form>
        @endif
    </main>
</x-auth-split-layout>
