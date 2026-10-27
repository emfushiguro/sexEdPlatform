@php
    $personaAttempts = $personaAttempts ?? collect();
@endphp

<section class="rounded-xl border border-purple-200 bg-purple-50 p-5 shadow-sm" aria-labelledby="persona-summary-title">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-800">Persona Sandbox</p>
            <h2 id="persona-summary-title" class="mt-1 text-lg font-bold text-gray-900">Persona evidence summary</h2>
        </div>
        <span class="rounded-full bg-purple-800 px-3 py-1 text-xs font-semibold text-white">Simulated Sandbox result</span>
    </div>

    @if($personaAttempts->isEmpty())
        <p class="mt-4 text-sm text-purple-950">No Persona attempt is recorded for this application. Review the grandfathered local evidence rules before deciding.</p>
    @else
        <p class="mt-4 text-sm leading-6 text-purple-950">
            Review the submitted evidence and account details before making the final decision. Persona images stay with Persona and are displayed securely here when requested.
        </p>
        @if($personaAttempts->first()?->flow_type === \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL)
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Persona did not authenticate the school/institution ID or perform an automated ID-to-selfie match. Compare the local ID portrait with the Persona selfie yourself.
            </p>
        @else
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
                        <div><dt class="text-gray-500">Outcome</dt><dd class="font-medium text-gray-900">{{ $attempt->verification_outcome ?: 'Not ready' }}</dd></div>
                        <div><dt class="text-gray-500">Last sync</dt><dd class="font-medium text-gray-900">{{ $attempt->last_synced_at?->format('M d, Y g:i A') ?? 'Not synchronized' }}</dd></div>
                    </dl>
                    <p class="mt-3 text-xs text-gray-600">
                        Country policy: {{ $attempt->country_policy_status }} · Document policy: {{ $attempt->document_policy_status }}
                    </p>
                    @if($attempt->isCurrent() && $attempt->is_sandbox && $attempt->verification_outcome === \App\Models\PersonaInquiry::OUTCOME_ELIGIBLE)
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            @if($attempt->selfie_verification_id)
                                @include('admin.parent-verifications.partials.persona-image', ['attempt' => $attempt, 'artifact' => 'selfie', 'label' => 'Persona selfie'])
                            @endif
                            @if($attempt->flow_type !== \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL && $attempt->government_id_verification_id)
                                @include('admin.parent-verifications.partials.persona-image', ['attempt' => $attempt, 'artifact' => 'government-id-front', 'label' => 'Persona ID front'])
                                @if(data_get(config('guardian_identity.id_types'), $attempt->local_government_id_type.'.requires_back', false))
                                    @include('admin.parent-verifications.partials.persona-image', ['attempt' => $attempt, 'artifact' => 'government-id-back', 'label' => 'Persona ID back'])
                                @endif
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
