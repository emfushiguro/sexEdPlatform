@php
    $personaRequired = $personaRequired ?? false;
    $personaAttempt = $personaAttempt ?? null;
    $selectedId = $personaAttempt?->local_document_type === 'government_id'
        ? 'government_id:'.$personaAttempt->local_government_id_type
        : ($personaAttempt?->local_document_type ?? old('id_selection', $draft['id_selection'] ?? ($case->document_type === 'government_id' ? 'government_id:'.$case->government_id_type : $case->document_type)));
    $idRequirements = collect(config('guardian_identity.id_types', []))->mapWithKeys(fn ($type, $key) => ['government_id:'.$key => (bool) $type['requires_back']])->all();
@endphp
<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Identity Verification</h2>
            <p class="mt-3 max-w-sm">Submit an ID or request an assisted document review.</p>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl space-y-6">
        <x-wizard-stepper :is-parent-flow="false" />
        @include('auth.partials.dependent-page-help', ['step' => 'identity'])
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-purple-700">Identity verification · Step 1 of 2</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $personaRequired ? 'Verify your identity' : 'Upload your ID' }}</h1>
            <p class="mt-2 text-gray-700">{{ $personaRequired && $case->pathway === 'teen' ? 'Choose your ID, capture it, then complete your selfie.' : ($personaRequired ? 'Submit your government ID and selfie once through Persona. An administrator will review your submission.' : 'Choose one ID type, then add a clear photo of its front. Some IDs also need the back.') }}</p>
        </header>
        @if($draft)
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-gray-800" role="status">
                Your ID is saved for this session. You can <a href="{{ route('learner.identity.selfie.create') }}" class="font-semibold text-purple-800 underline">continue to your selfie</a> or replace an image below.
            </div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">Please review the fields below and try again.</div>
        @endif
        @if($case->status === 'rejected' && $case->correction_note)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold">Reviewer message for round {{ $case->submission_round }}</p><p class="mt-2 whitespace-pre-wrap">{{ $case->correction_note }}</p></div>
        @endif

        @if($personaRequired && $case->pathway === 'teen')
            @if($personaAttempt && $personaAttempt->local_document_type === 'government_id')
                @include('auth.partials.persona-identity-step', ['attempt' => $personaAttempt, 'allowSchoolDocuments' => false])
            @endif
            @include('auth.partials.teen-identity-document')
        @elseif($personaRequired)
            @include('auth.partials.persona-identity-step', [
                'attempt' => $personaAttempt,
                'allowSchoolDocuments' => $case->pathway === 'teen',
                'documentType' => $personaAttempt?->local_document_type,
                'governmentIdType' => $personaAttempt?->local_government_id_type,
            ])
        @endif

        @if($personaRequired && $canRequestAssistedReview)
            <p class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-purple-900">Don't have an ID? <a class="font-semibold underline" href="{{ route('learner.identity.create', ['assisted_review' => 1]) }}">Request an assisted document review</a>.</p>
        @endif

        @if(!$personaRequired)
        <form method="POST" action="{{ route('learner.identity.document.store') }}" enctype="multipart/form-data" class="space-y-5"
              x-data="{
                  idSelection: @js($selectedId),
                  requirements: @js($idRequirements),
                  requiresBack() { return Boolean(this.requirements[this.idSelection]); }
              }">
            @csrf
            <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
            <div>
                <label for="id_selection" class="block font-semibold text-gray-900">ID type</label>
                @if($personaAttempt?->isPolicyEligible())
                    <input type="hidden" name="id_selection" value="{{ $selectedId }}">
                    <p class="mt-2 rounded-xl border border-purple-200 bg-purple-50 p-3 text-sm text-purple-900">Persona locked this choice to <strong>{{ $selectedId }}</strong>.</p>
                @else
                <select id="id_selection" name="id_selection" x-model="idSelection" @change="$dispatch('identity-choice-changed')" required
                        aria-invalid="{{ $errors->has('id_selection') ? 'true' : 'false' }}"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-white p-3 focus:outline-none focus:ring-2 focus:ring-purple-700">
                    <option value="">Choose an identity evidence option</option>
                    @if($canRequestAssistedReview)<option value="assisted_review">I don't have an ID — request assisted review</option>@endif
                    @if($case->pathway === 'teen')
                        <optgroup label="School or institution">
                            <option value="school_id">School ID</option>
                            <option value="institution_id">Institution ID</option>
                        </optgroup>
                    @endif
                    <optgroup label="Government ID">
                        @foreach(array_intersect_key(config('guardian_identity.id_types', []), array_flip(config('guardian_identity.accepted_id_types', []))) as $value => $type)
                            <option value="government_id:{{ $value }}">{{ $type['label'] }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @endif
                <p class="mt-2 text-sm text-gray-600" x-text="idSelection === 'assisted_review' ? 'Upload an independent official or school record with your photo, or a signed statement from a school, agency, or local authority confirming your identity. A reviewer will also check your selfie and approved Guardian relationship. A selfie or Guardian photo alone is not sufficient. Do not upload medical records.' : (!idSelection ? 'Select an option to see what you need.' : (requiresBack() ? 'Front and back images required.' : 'Front image only required.'))"></p>
                @error('id_selection')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div x-show="idSelection === 'government_id:other'" x-cloak>
                <label for="government_id_type_other" class="block font-semibold text-gray-900">Specify your ID type</label>
                <input id="government_id_type_other" name="government_id_type_other" value="{{ old('government_id_type_other', $draft['government_id_type_other'] ?? $case->government_id_type_other) }}"
                       :disabled="idSelection !== 'government_id:other'" class="mt-2 w-full rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-purple-700">
                @error('government_id_type_other')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5">
                @foreach(['front' => 'Front of document', 'back' => 'Back of document'] as $side => $label)
                    @php $slot = 'identity_'.$side; @endphp
                    <div @if($side === 'back') x-show="requiresBack()" x-cloak @endif>
                        <label for="{{ $slot }}" class="block font-semibold text-gray-900">{{ $label }}</label>
                        @include('auth.partials.identity-document-camera')
                        @if(!empty($draft[$slot.'_path']))<p class="mt-2 text-sm font-medium text-purple-800">Saved image available. Choose a file to replace it.</p>@endif
                        @if($case->status === 'rejected' && $case->evidence()->where('slot', $slot)->exists())<p class="mt-2 text-sm text-gray-600">You can keep your previous image if it is still valid.</p>@endif
                        @error($slot)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <button type="submit" class="min-h-11 w-full rounded-xl bg-purple-800 px-6 py-3 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Continue to selfie</button>
        </form>
        @endif
    </main>
</x-auth-split-layout>
