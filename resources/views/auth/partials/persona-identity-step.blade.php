@php
    $attempt = $attempt ?? null;
    $isGuardian = $isGuardian ?? false;
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
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-800">Persona Sandbox</p>
        <h2 id="persona-identity-title" class="mt-1 text-lg font-semibold text-gray-900">Identity evidence step</h2>
        <p class="mt-2 text-sm leading-6 text-gray-700">
            Persona is one evidence step only. An authorized administrator still reviews and decides the application.
            Every Persona result is a Simulated Sandbox result.
        </p>
        @error('persona')
            <p class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</p>
        @enderror
    </div>

    @if(!$attempt)
        <form method="POST" action="{{ route('persona.identity.start') }}" class="space-y-3">
            @csrf
            <label for="persona_document_type" class="block text-sm font-semibold text-gray-900">Document path</label>
            <select id="persona_document_type" name="document_type" required class="w-full rounded-xl border border-purple-200 bg-white p-3">
                @if($allowSchoolDocuments)
                    <option value="school_id" @selected($documentType === 'school_id')>School ID</option>
                    <option value="institution_id" @selected($documentType === 'institution_id')>Institution ID</option>
                @endif
                <option value="government_id" @selected($documentType === 'government_id' || !$allowSchoolDocuments)>Government ID</option>
            </select>
            <label for="persona_government_id_type" class="block text-sm font-semibold text-gray-900">Government ID type, if selected</label>
            <select id="persona_government_id_type" name="government_id_type" class="w-full rounded-xl border border-purple-200 bg-white p-3">
                <option value="">Choose a supported ID</option>
                @foreach($governmentTypes as $type)
                    <option value="{{ $type }}" @selected($governmentIdType === $type)>{{ config('guardian_identity.id_types.'.$type.'.label', $type) }}</option>
                @endforeach
            </select>
            <button type="submit" class="min-h-11 rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white">Begin Persona verification</button>
        </form>
    @elseif($attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_ELIGIBLE)
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900" role="status">Persona evidence is eligible. Administrator approval is still required.</p>
    @elseif($attempt->expired_at || in_array($attempt->persona_status, ['failed', 'expired'], true) || $attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_RETRYABLE)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="status">This Persona attempt needs a new attempt.</p>
        <form method="POST" action="{{ route('persona.identity.replace') }}">
            @csrf
            <button type="submit" class="min-h-11 rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white">Start a new Persona attempt</button>
        </form>
    @else
        <p class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900" role="status">Continue the Persona evidence step, then return here for the application review.</p>
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
