@php
    $personaAttempts = $personaAttempts ?? collect();
@endphp

<section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="identity-evidence-title">
    <h2 id="identity-evidence-title" class="text-lg font-bold text-gray-900">Submitted identity evidence</h2>

    @if($personaAttempts->isEmpty())
        <p class="mt-4 text-sm text-gray-600">No identity evidence is available for this submission.</p>
    @else
        <div class="mt-4 space-y-6">
            @foreach($personaAttempts as $attempt)
                <article class="rounded-xl border border-gray-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">{{ $attempt->isCurrent() ? 'Current submission' : 'Previous submission' }}</h3>
                        @if($attempt->local_government_id_type)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                {{ config('guardian_identity.id_types.'.$attempt->local_government_id_type.'.label', str($attempt->local_government_id_type)->headline()) }}
                            </span>
                        @endif
                    </div>

                    @if($attempt->isCurrent() && $attempt->is_sandbox && in_array($attempt->verification_outcome, [
                        \App\Models\PersonaInquiry::OUTCOME_ELIGIBLE,
                        \App\Models\PersonaInquiry::OUTCOME_SANDBOX_REVIEW,
                    ], true))
                        <div class="mt-5 space-y-6">
                            <div>
                                <h4 class="text-base font-semibold text-gray-900">Selfie photos</h4>
                                @if($attempt->selfie_verification_id)
                                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                                        @foreach(['selfie' => 'center', 'selfie-left' => 'left', 'selfie-right' => 'right'] as $artifact => $pose)
                                            @include('admin.parent-verifications.partials.persona-image', [
                                                'attempt' => $attempt,
                                                'artifact' => $artifact,
                                                'label' => 'Selfie - '.$pose,
                                                'compactPersonaSummary' => true,
                                            ])
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 text-sm text-amber-900" role="status">Selfie images are unavailable. Request a new submission if required evidence cannot be reviewed.</p>
                                @endif
                            </div>

                            @if($attempt->flow_type !== \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL && $attempt->government_id_verification_id)
                                <div class="border-t border-gray-200 pt-6">
                                    <h4 class="text-base font-semibold text-gray-900">ID document</h4>
                                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                                        @include('admin.parent-verifications.partials.persona-image', [
                                            'attempt' => $attempt,
                                            'artifact' => 'government-id-front',
                                            'label' => 'ID front',
                                            'compactPersonaSummary' => true,
                                        ])
                                        @if(data_get(config('guardian_identity.id_types'), $attempt->local_government_id_type.'.requires_back', false))
                                            @include('admin.parent-verifications.partials.persona-image', [
                                                'attempt' => $attempt,
                                                'artifact' => 'government-id-back',
                                                'label' => 'ID back',
                                                'compactPersonaSummary' => true,
                                            ])
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-600">The submitted images are not available for display.</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
