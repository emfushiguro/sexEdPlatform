@php
    $attempt = $attempt ?? null;
    $isGuardian = $isGuardian ?? false;
    $selfieOnly = $selfieOnly ?? false;
    $allowSchoolDocuments = $allowSchoolDocuments ?? false;
    $documentType = $documentType ?? $attempt?->local_document_type;
    $governmentIdType = $governmentIdType ?? $attempt?->local_government_id_type;
    $governmentTypes = collect(config('persona.government_id_classes', []))
        ->keys()
        ->filter(fn (string $key): bool => array_key_exists($key, config('guardian_identity.id_types', [])))
        ->all();
@endphp

<section class="space-y-4 rounded-2xl border border-purple-200 bg-purple-50 p-5" aria-labelledby="persona-identity-title">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-800">Persona</p>
        <h2 id="persona-identity-title" class="mt-1 text-lg font-semibold text-gray-900">{{ $selfieOnly ? 'Selfie verification' : 'Identity evidence step' }}</h2>
        <p class="mt-2 text-sm leading-6 text-gray-700">
            {{ $selfieOnly ? 'Complete your selfie verification, then return to submit your ID and selfie for review.' : 'Submit your identity evidence once. An authorized administrator reviews and decides the application.' }}
        </p>
        @error('persona')
            <p class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</p>
        @enderror
    </div>

    @if(!$attempt)
        <form method="POST" action="{{ route('persona.identity.start') }}" class="space-y-3">
            @csrf
            @if($selfieOnly)
                <input type="hidden" name="document_type" value="{{ $documentType }}">
            @else
                @include('auth.partials.persona-document-choice')
            @endif
            <button type="submit" class="min-h-11 rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white">{{ $selfieOnly ? 'Start selfie verification' : 'Begin Persona verification' }}</button>
        </form>
    @elseif($attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_SANDBOX_REVIEW)
        <div class="space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950" role="status">
            @if($attempt->isSandboxReviewable())
                <p class="font-semibold">Pending review</p>
                <p>Your ID and selfie are ready for administrator review. No additional upload is required.</p>
            @else
                <p>Contact support to continue.</p>
            @endif
        </div>
        @if($attempt->isSandboxReviewable())
            <form method="POST" action="{{ route('persona.identity.refresh') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl border border-purple-300 bg-white px-5 py-3 text-sm font-semibold text-purple-900">Check review submission</button>
            </form>
        @endif
    @elseif($attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_ELIGIBLE)
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900" role="status">Persona evidence is eligible. Administrator approval is still required.</p>
        @if($attempt->flow_type !== \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL)
            <p class="text-sm text-gray-700">No additional ID or selfie upload is required.</p>
            <form method="POST" action="{{ route('persona.identity.refresh') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl border border-purple-300 bg-white px-5 py-3 text-sm font-semibold text-purple-900">Check review submission</button>
            </form>
        @endif
    @elseif($attempt->expired_at || in_array($attempt->persona_status, ['failed', 'expired', 'declined'], true) || in_array($attempt->verification_outcome, [\App\Models\PersonaInquiry::OUTCOME_RETRYABLE, \App\Models\PersonaInquiry::OUTCOME_INELIGIBLE], true) || (in_array($attempt->persona_status, ['completed', 'approved', 'needs-review'], true) && $attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_INCONCLUSIVE))
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="status">
            @if($attempt->country_policy_status === 'ineligible')
                A Philippine-issued government ID is required. The returned issuing country did not match.
            @elseif($attempt->document_policy_status === 'ineligible')
                The document returned by Persona did not match the supported ID type you selected.
            @elseif($attempt->persona_status === 'needs-review')
                Persona could not finalize this attempt. Start a new attempt or contact support.
            @elseif($attempt->verification_outcome === 'inconclusive')
                Persona did not return enough information to verify this document. Start a new attempt or contact support.
            @else
                This Persona attempt needs a new attempt.
            @endif
        </p>
        @if(\App\Models\PersonaInquiry::localManualReviewEnabled() && $attempt->is_sandbox
            && in_array($attempt->persona_status, ['completed', 'approved'], true)
            && $attempt->government_id_verification_id && $attempt->selfie_verification_id)
            <form method="POST" action="{{ route('persona.identity.refresh') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white">Recheck existing Persona evidence</button>
            </form>
        @endif
        <form method="POST" action="{{ route('persona.identity.replace') }}" class="space-y-3">
            @csrf
            @if($selfieOnly)
                <input type="hidden" name="document_type" value="{{ $documentType }}">
            @else
                @include('auth.partials.persona-document-choice')
            @endif
            <button type="submit" class="min-h-11 rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white">{{ $selfieOnly ? 'Start selfie verification' : 'Start a new Persona attempt' }}</button>
        </form>
    @else
        <p class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900" role="status">Complete Persona verification, then return here. If you already finished, refresh the status while the result is processed.</p>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('persona.identity.resume') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white">Continue Persona verification</button>
            </form>
            <form method="POST" action="{{ route('persona.identity.refresh') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl border border-purple-300 bg-white px-5 py-3 text-sm font-semibold text-purple-900">Refresh Persona status</button>
            </form>
        </div>
    @endif
</section>
