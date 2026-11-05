@php
    $personaAttempts = $personaAttempts ?? collect();
    $sandboxManualReview = $sandboxManualReview ?? false;
    $compactPersonaSummary = $compactPersonaSummary ?? false;
@endphp

<section class="rounded-xl border border-purple-200 bg-purple-50 p-5 shadow-sm" aria-labelledby="persona-summary-title">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-800">Persona</p>
            <h2 id="persona-summary-title" class="mt-1 text-lg font-bold text-gray-900">Persona evidence summary</h2>
        </div>
    </div>

    @if($sandboxManualReview)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950" role="note">
            <p class="font-semibold">Review the submitted ID and selfie</p>
            <p class="mt-1">Confirm Philippine issuance, the selected ID type, and the ID/selfie portraits before approval.</p>
            @unless(\App\Models\PersonaInquiry::localManualReviewEnabled())
                <p class="mt-2 font-semibold">Contact support to continue.</p>
            @endunless
        </div>
    @endif

    @if($personaAttempts->isEmpty())
        <p class="mt-4 text-sm text-purple-950">No Persona attempt is recorded for this application. Review the grandfathered local evidence rules before deciding.</p>
    @else
        @unless($compactPersonaSummary)
        <p class="mt-4 text-sm leading-6 text-purple-950">
            Review the submitted evidence and account details before making the final decision. Persona images stay with Persona and are displayed securely here when requested.
        </p>
        @endunless
        @if($personaAttempts->first()?->flow_type === \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL)
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Persona did not authenticate the school/institution ID or perform an automated ID-to-selfie match. Compare the local ID portrait with the Persona selfie yourself.
            </p>
        @elseif(! $compactPersonaSummary)
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Persona classification is not proof of document authenticity or ownership.
            </p>
        @endif

        <div class="mt-4 space-y-3">
            @foreach($personaAttempts as $attempt)
                <article class="rounded-lg border border-purple-100 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">Attempt {{ $attempt->attempt_number }}</h3>
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $attempt->isCurrent() ? 'Current' : 'Superseded' }}
                        </span>
                    </div>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-gray-500">Purpose</dt><dd class="font-medium text-gray-900">{{ str_replace('_', ' ', $attempt->flow_type) }}</dd></div>
                        <div><dt class="text-gray-500">Status</dt><dd class="font-medium text-gray-900">{{ $attempt->persona_status ?: 'Not started' }}</dd></div>
                        <div><dt class="text-gray-500">Outcome</dt><dd class="font-medium text-gray-900">{{ $attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_SANDBOX_REVIEW ? 'Pending review' : str_replace('_', ' ', $attempt->verification_outcome ?: 'Not ready') }}</dd></div>
                        <div><dt class="text-gray-500">Last sync</dt><dd class="font-medium text-gray-900">{{ $attempt->last_synced_at?->format('M d, Y g:i A') ?? 'Not synchronized' }}</dd></div>
                    </dl>
                    @if($attempt->flow_type !== \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL)
                        @php
                            $expectedLabel = config('guardian_identity.id_types.'.$attempt->local_government_id_type.'.label', 'Unknown or unavailable');
                            $reportedType = array_search($attempt->id_class, config('persona.government_id_classes', []), true);
                            $reportedLabel = $reportedType === false
                                ? 'Unknown or unavailable'
                                : config('guardian_identity.id_types.'.$reportedType.'.label', 'Unknown or unavailable');
                            $reportedCountry = strtoupper((string) $attempt->country_code);
                            $reportedCountry = preg_match('/^[A-Z]{2}$/', $reportedCountry) ? $reportedCountry : 'Unavailable';
                        @endphp
                        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="text-gray-500">Selected expected Philippine ID</dt><dd class="font-medium text-gray-900">Philippines (PH), {{ $expectedLabel }}</dd></div>
                            @unless($compactPersonaSummary)
                                <div><dt class="text-gray-500">Original Persona-reported country / ID class</dt><dd class="font-medium text-gray-900">{{ $reportedCountry }} / {{ $reportedLabel }}@if($reportedType !== false) ({{ $attempt->id_class }})@endif</dd></div>
                            @endunless
                        </dl>
                        @if(! $compactPersonaSummary && $attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_SANDBOX_REVIEW)
                            <p class="mt-3 text-sm text-amber-900">Country and ID type were not automatically verified. The original reported values above are retained for manual review.</p>
                        @endif
                    @endif
                    @unless($compactPersonaSummary)
                    <p class="mt-3 text-xs text-gray-600">
                        Country policy: {{ $attempt->country_policy_status }} · Document policy: {{ $attempt->document_policy_status }}
                    </p>
                    @endunless
                    @if($attempt->isCurrent() && $attempt->is_sandbox && in_array($attempt->verification_outcome, [\App\Models\PersonaInquiry::OUTCOME_ELIGIBLE, \App\Models\PersonaInquiry::OUTCOME_SANDBOX_REVIEW], true))
                        <div @class(['mt-4', 'space-y-6' => $compactPersonaSummary, 'grid gap-4 md:grid-cols-3' => ! $compactPersonaSummary])>
                            <div @class(['space-y-3' => $compactPersonaSummary, 'contents' => ! $compactPersonaSummary])>
                                @if($compactPersonaSummary)
                                    <h4 class="text-base font-semibold text-gray-900">Selfie photos</h4>
                                @endif
                                <div @class(['grid gap-4 md:grid-cols-3' => $compactPersonaSummary, 'contents' => ! $compactPersonaSummary])>
                                    @if($attempt->selfie_verification_id)
                                        @foreach(['selfie' => 'center', 'selfie-left' => 'left', 'selfie-right' => 'right'] as $artifact => $pose)
                                            @include('admin.parent-verifications.partials.persona-image', ['attempt' => $attempt, 'artifact' => $artifact, 'label' => 'Persona selfie — '.$pose])
                                        @endforeach
                                    @else
                                        <p class="text-sm text-amber-900" role="status">Persona selfie images are unavailable. Request a new submission if required evidence cannot be reviewed.</p>
                                    @endif
                                </div>
                            </div>
                            @if($attempt->flow_type !== \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL && $attempt->government_id_verification_id)
                                <div @class(['space-y-3 border-t border-gray-200 pt-6' => $compactPersonaSummary, 'contents' => ! $compactPersonaSummary])>
                                    @if($compactPersonaSummary)
                                        <h4 class="text-base font-semibold text-gray-900">ID document</h4>
                                    @endif
                                    <div @class(['grid gap-4 md:grid-cols-2' => $compactPersonaSummary, 'contents' => ! $compactPersonaSummary])>
                                        @include('admin.parent-verifications.partials.persona-image', ['attempt' => $attempt, 'artifact' => 'government-id-front', 'label' => 'Persona ID front'])
                                        @if(data_get(config('guardian_identity.id_types'), $attempt->local_government_id_type.'.requires_back', false))
                                            @include('admin.parent-verifications.partials.persona-image', ['attempt' => $attempt, 'artifact' => 'government-id-back', 'label' => 'Persona ID back'])
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
