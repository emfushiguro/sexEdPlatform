@extends('layouts.admin')

@section('title', 'Learner Identity Review')
@section('page-title', 'Learner Identity Review')

@section('content')
@php
    $images = $case->evidence->keyBy('slot');
    $dateOfBirth = $case->learner?->birthdate;
    $learnerAge = $case->learner?->calculateAge();
    $documentLabel = match ($case->document_type) {
        'school_id' => 'School ID',
        'institution_id' => 'Institution ID',
        'government_id' => data_get(config('guardian_identity.id_types', []), $case->government_id_type.'.label', 'Government ID'),
        'assisted_review' => 'Alternative evidence — assisted review',
        default => 'Not submitted',
    };
    $guidance = $case->pathway === 'adult'
        ? ['Account name, ID details, and date of birth are consistent.', 'The document is readable and appears valid.', 'The selfie is clear and recent.', 'The selfie reasonably corresponds to the ID photo.']
        : ['The document is readable.', 'Submitted information and age are consistent.', 'The selfie is clear and recent.', 'Check linked guardian requirements when present.'];
    $correctionItems = $case->evidence_source === 'persona'
        ? ['persona_identity' => 'ID and selfie']
        : array_filter([
            'identity_front' => $case->document_type === 'assisted_review' ? 'Alternative evidence' : 'ID front',
            'identity_back' => $images->has('identity_back') ? 'ID back' : null,
            'selfie' => 'Selfie',
        ]);
@endphp

<div class="px-4 py-8 mx-auto space-y-6 max-w-7xl">
    <a href="{{ route('admin.parent-verifications.index', ['type' => 'learners', 'status' => $case->status]) }}" class="text-sm font-semibold text-brand-700 hover:underline">← Back to learner cases</a>
    @if(session('success')) <p class="p-4 rounded-xl bg-emerald-50 text-emerald-800">{{ session('success') }}</p> @endif
    @if($errors->any()) <div class="p-4 rounded-xl bg-rose-50 text-rose-800">{{ $errors->first() }}</div> @endif

    <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
        <h1 class="text-2xl font-bold text-gray-900">{{ $case->learner?->full_name }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $case->learner?->email }}</p>
        <dl class="grid gap-4 mt-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="font-semibold text-gray-500">Date of birth</dt>
                <dd>{{ $dateOfBirth?->format('M j, Y') ?? 'Missing' }}@if($learnerAge !== null) ({{ $learnerAge }})@endif</dd>
            </div>
            <div><dt class="font-semibold text-gray-500">Pathway</dt><dd class="capitalize">{{ $case->pathway }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Status</dt><dd class="capitalize">{{ $case->status }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Document</dt><dd>{{ $documentLabel }}@if($case->government_id_type === 'other') — {{ $case->government_id_type_other }} @endif</dd></div>
            <div><dt class="font-semibold text-gray-500">Submitted</dt><dd>{{ $case->submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewer</dt><dd>{{ $reviewer?->full_name ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewed</dt><dd>{{ $case->reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Approved</dt><dd>{{ $case->approved_at?->format('M d, Y h:i A') ?? '—' }}</dd></div>
        </dl>
        @if($case->rejection_reason)<p class="p-4 mt-5 text-sm rounded-xl bg-rose-50 text-rose-800"><strong>Rejection reason:</strong> {{ $case->rejection_reason }}</p>@endif
    </section>

    @if($guardianRelationships->isNotEmpty())
        <section class="rounded-2xl border border-purple-200 bg-purple-50 p-5 text-sm text-purple-950">
            <h2 class="font-semibold">Guardian and relationship evidence</h2>
            <p class="mt-2">Review the linked Guardian and current relationship evidence alongside this dependent's identity evidence.</p>
            @foreach($guardianRelationships as $relationship)
                <div class="mt-3 rounded-xl border border-purple-100 bg-white p-3">
                    <p class="font-semibold">{{ $relationship->parent?->full_name ?? 'Guardian unavailable' }}</p>
                    <p>Guardian identity: {{ $relationship->parent?->parent_verification_status ?? 'Not verified' }} · Relationship: {{ $relationship->relationship_verified_status }} · Status: {{ $relationship->relationship_status }}</p>
                    <a class="mt-2 inline-block font-semibold underline" href="{{ route('admin.parent-verifications.relationships.show', $relationship) }}">Open relationship and birth-record evidence</a>
                </div>
            @endforeach
        </section>
    @endif

    <section class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5">
    <h2 class="text-lg font-semibold text-gray-900">ID document</h2>

    @if($case->document_type === 'assisted_review')
        <section class="p-5 text-sm text-amber-900 border border-amber-200 rounded-2xl bg-amber-50">
            <h2 class="font-semibold">Assisted identity review</h2>
            <p class="mt-2">Compare the independent photo record or verified identity statement and selfie with the current approved birth and relationship evidence. Verify its issuer using an independently obtained official channel and record the result. If independent evidence or verification is unavailable, request changes; do not approve based on a selfie or relationship alone.</p>
            @foreach($verifiedRelationships as $relationship)
                <a class="block mt-2 font-semibold underline" href="{{ route('admin.parent-verifications.relationships.show', $relationship) }}">Review {{ $relationship->parent?->name }}'s relationship evidence</a>
            @endforeach
        </section>
    @endif

    @if($case->evidence_source === 'persona')
    @else
    <h2 class="text-lg font-semibold text-gray-900">Submitted to Conscious Connections</h2>
    <section class="grid gap-6 lg:grid-cols-3">
        @foreach(['identity_front' => $case->document_type === 'assisted_review' ? 'Alternative evidence' : 'ID front', 'identity_back' => 'ID back'] as $slot => $label)
            @if($images->has($slot) && ($slot !== 'selfie' || ! $case->usesPersonaSelfie()))
                <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-2xl">
                    <h2 class="mb-3 font-semibold text-gray-900">{{ $label }}</h2>
                    @if($slot !== 'selfie')<p class="mb-2 text-sm text-gray-600">Submitted ID: {{ $documentLabel }}@if($case->government_id_type === 'other') — {{ $case->government_id_type_other }}@endif</p>@endif
                    <a href="{{ route('admin.parent-verifications.learners.evidence', [$case, $slot]) }}" target="_blank" rel="noopener" aria-label="Open {{ $label }} full size">
                        <img src="{{ route('admin.parent-verifications.learners.evidence', [$case, $slot]) }}" alt="{{ $label }} evidence" class="object-contain w-full bg-gray-100 max-h-96 rounded-xl">
                    </a>
                    <p class="mt-2 text-xs text-gray-500">Open image to zoom.</p>
                </div>
            @endif
        @endforeach
    </section>
    @endif
    </section>

    @include('admin.parent-verifications.partials.persona-summary', [
        'compactPersonaSummary' => true,
        'personaAttempts' => $case->personaInquiries,
        'sandboxManualReview' => $case->sandbox_manual_review,
    ])

    @if($images->has('selfie') && ! $case->usesPersonaSelfie() && $case->evidence_source !== 'persona')
        <section class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="mb-3 text-lg font-semibold text-gray-900">Selfie</h2>
            <a href="{{ route('admin.parent-verifications.learners.evidence', [$case, 'selfie']) }}" target="_blank" rel="noopener" aria-label="Open selfie full size">
                <img src="{{ route('admin.parent-verifications.learners.evidence', [$case, 'selfie']) }}" alt="Selfie evidence" class="max-h-96 w-full rounded-xl bg-gray-100 object-contain">
            </a>
        </section>
    @endif

    <section x-data="{}" class="rounded-2xl border border-gray-200 bg-white p-5">

        @if($case->status === 'pending' && $case->superseded_at === null)
            <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-gray-100">
                <button type="button" data-testid="approve-identity-trigger" @click="$refs.approveDialog.showModal()"
                        @disabled($case->sandbox_manual_review && ! \App\Models\PersonaInquiry::localManualReviewEnabled())
                        class="px-5 py-3 text-sm font-semibold text-white transition min-h-11 rounded-xl bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Approve identity
                </button>
                <button type="button" data-testid="reject-identity-trigger" @click="$refs.rejectDialog.showModal()"
                        class="px-5 py-3 text-sm font-semibold text-white transition min-h-11 rounded-xl bg-rose-700 hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">
                    Request changes
                </button>
            </div>

            <dialog x-ref="approveDialog" role="dialog" aria-modal="true" data-testid="approve-identity-dialog" @click.self="$refs.approveDialog.close()"
                    @if($errors->hasAny(['confirm_government_issued', 'sandbox_philippine_id_confirmed', 'sandbox_id_type_confirmed', 'persona_portrait_compared', 'assisted_evidence_reviewed', 'assisted_review_basis', 'assisted_issuer_verified', 'assisted_issuer_verification'])) x-init="$nextTick(() => $refs.approveDialog.showModal())" @endif
                    aria-labelledby="approve-identity-title" aria-describedby="approve-identity-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Confirm decision</p>
                            <h3 id="approve-identity-title" class="mt-2 text-xl font-bold text-gray-900">Approve this identity?</h3>
                            <p id="approve-identity-description" class="mt-2 text-sm leading-6 text-gray-600">
                                This will approve {{ $case->learner?->full_name }}’s current evidence and notify the learner.
                            </p>
                        </div>
                        <button type="button" @click="$refs.approveDialog.close()" aria-label="Close approval dialog"
                                class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-700">×</button>
                    </div>
                        <form method="POST" action="{{ route('admin.parent-verifications.learners.approve', $case) }}" class="mt-6 space-y-5">
                            @csrf
                            <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                            @if($case->document_type === 'assisted_review')
                                <label class="block text-sm font-semibold text-gray-800" for="assisted_review_basis">How was identity independently corroborated?</label>
                                <select id="assisted_review_basis" name="assisted_review_basis" required class="w-full rounded-xl border border-gray-300 p-3 text-sm">
                                    <option value="">Select the review basis</option>
                                    <option value="independent_photo_record" @selected(old('assisted_review_basis') === 'independent_photo_record')>Compared a photo on an independent official or school record</option>
                                    <option value="trusted_verifier_attestation" @selected(old('assisted_review_basis') === 'trusted_verifier_attestation')>Checked a signed attestation from an independent school, agency, or local authority</option>
                                </select>
                                @error('assisted_review_basis')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                                <label class="flex items-start gap-3 p-4 text-sm text-gray-700 border border-amber-200 rounded-xl bg-amber-50">
                                    <input type="checkbox" name="assisted_evidence_reviewed" value="1" required @checked(old('assisted_evidence_reviewed')) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700">
                                    <span>I reviewed the alternative supporting evidence and selfie. I did not treat a selfie or Guardian relationship alone as proof of identity.</span>
                                </label>
                                @error('assisted_evidence_reviewed')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                                <label class="block text-sm font-semibold text-gray-800" for="assisted_issuer_verification">Independent issuer verification</label>
                                <p id="issuer-verification-help" class="text-sm text-gray-600">Record the issuing school, agency or authority; the official contact channel you obtained independently; how and when you checked; and what the issuer confirmed about this person's identity and record. Contact details supplied only by the applicant are insufficient.</p>
                                <textarea id="assisted_issuer_verification" name="assisted_issuer_verification" required minlength="20" maxlength="2000" rows="4" aria-describedby="issuer-verification-help" class="w-full rounded-xl border-gray-300 p-3">{{ old('assisted_issuer_verification') }}</textarea>
                                @error('assisted_issuer_verification')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                                <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="assisted_issuer_verified" value="1" required @checked(old('assisted_issuer_verified')) class="mt-1 rounded border-gray-300 text-emerald-700"><span>I independently verified the issuer and corroborated the learner's identity. I compared the current selfie with the independent photo or verified identity statement.</span></label>
                                @error('assisted_issuer_verified')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                            @endif
                            @if($case->pathway === 'adult' && $case->government_id_type === 'other')
                            <label class="flex items-start gap-3 p-4 text-sm text-gray-700 border border-gray-200 rounded-xl">
                                <input type="checkbox" name="confirm_government_issued" value="1" required @checked(old('confirm_government_issued'))
                                       class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700">
                                <span>I confirm I determined this Other ID is government-issued.</span>
                            </label>
                            @error('confirm_government_issued')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                            @endif
                            @if($case->sandbox_manual_review)
                                <fieldset class="space-y-3">
                                    <legend class="text-sm font-semibold text-gray-900">Required confirmations</legend>
                                    @foreach([
                                        'sandbox_philippine_id_confirmed' => 'I confirm the ID was issued in the Philippines.',
                                        'sandbox_id_type_confirmed' => 'I confirm the ID matches the selected ID type.',
                                        'persona_portrait_compared' => 'I compared the ID and selfie portraits.',
                                    ] as $field => $label)
                                        <label class="flex items-start gap-3 p-3 text-sm text-gray-700 border border-gray-200 rounded-xl">
                                            <input type="checkbox" name="{{ $field }}" value="1" required @checked(old($field)) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700">
                                            <span>{{ $label }} (required)</span>
                                        </label>
                                        @error($field)<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                                    @endforeach
                                </fieldset>
                            @elseif((config('persona.enabled', false) || $case->usesPersonaSelfie()) && $case->pathway === 'teen' && in_array($case->document_type, ['school_id', 'institution_id'], true))
                                <label class="flex items-start gap-3 p-4 mb-4 text-sm text-gray-700 border border-gray-200 rounded-xl">
                                    <input type="checkbox" name="persona_portrait_compared" value="1" required @checked(old('persona_portrait_compared')) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700">
                                    <span>I viewed the submitted school/institution ID and the Persona selfie and manually compared the portraits.</span>
                                </label>
                                @error('persona_portrait_compared')<p class="mb-3 text-sm text-rose-700">{{ $message }}</p>@enderror
                            @endif
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" @click="$refs.approveDialog.close()" class="px-5 py-3 text-sm font-semibold text-gray-700 border border-gray-300 min-h-11 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                                <button type="submit" class="px-5 py-3 text-sm font-semibold text-white min-h-11 rounded-xl bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Confirm approval</button>
                            </div>
                        </form>
                </div>
            </dialog>

            <dialog x-ref="rejectDialog" role="dialog" aria-modal="true" data-testid="reject-identity-dialog" @click.self="$refs.rejectDialog.close()" @if($errors->hasAny(['reason', 'allow_resubmission', 'requested_corrections', 'requested_corrections.*', 'correction_note'])) x-init="$nextTick(() => $refs.rejectDialog.showModal())" @endif
                    aria-labelledby="reject-identity-title" aria-describedby="reject-identity-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Confirm decision</p>
                            <h3 id="reject-identity-title" class="mt-2 text-xl font-bold text-gray-900">Record review decision</h3>
                            <p id="reject-identity-description" class="mt-2 text-sm leading-6 text-gray-600">
                                Request specific changes or record a final rejection.
                            </p>
                        </div>
                        <button type="button" @click="$refs.rejectDialog.close()" aria-label="Close rejection dialog"
                                class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-rose-700">×</button>
                    </div>
                    <form method="POST" action="{{ route('admin.parent-verifications.learners.reject', $case) }}" class="mt-6 space-y-5" x-data="{ allowResubmission: @js((string) old('allow_resubmission', '1')) }">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                        <div>
                            <label for="allow_resubmission" class="block text-sm font-semibold text-gray-800">Decision</label>
                            <select id="allow_resubmission" name="allow_resubmission" x-model="allowResubmission" required class="w-full p-3 mt-2 text-sm bg-white border border-gray-300 rounded-xl">
                                <option value="1" @selected((string) old('allow_resubmission', '1') === '1')>Request changes</option>
                                <option value="0" @selected((string) old('allow_resubmission', '1') === '0')>Final rejection</option>
                            </select>
                            @error('allow_resubmission')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="reason" class="block text-sm font-semibold text-gray-800">Rejection reason</label>
                            <select id="reason" name="reason" required aria-invalid="{{ $errors->has('reason') ? 'true' : 'false' }}"
                                    class="w-full p-3 mt-2 text-sm bg-white border border-gray-300 rounded-xl focus:border-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-600/20">
                                <option value="">Select a reason</option>
                                @foreach(\App\Enums\LearnerIdentityRejectionReason::cases() as $reason)
                                    <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                                @endforeach
                            </select>
                            @error('reason')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <fieldset x-show="allowResubmission === '1'" :disabled="allowResubmission !== '1'" class="space-y-3">
                            <legend class="text-sm font-semibold text-gray-800">Requested items</legend>
                            @foreach($correctionItems as $slot => $label)
                                <label class="flex items-start gap-3 p-3 text-sm text-gray-700 border border-gray-200 rounded-xl">
                                    <input type="checkbox" name="requested_corrections[]" value="{{ $slot }}" @checked(in_array($slot, (array) old('requested_corrections', []), true)) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-rose-700 focus:ring-rose-700">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                            @error('requested_corrections')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                            @error('requested_corrections.*')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        </fieldset>
                        <div>
                            <label for="correction_note" class="block text-sm font-semibold text-gray-800">Note for the learner</label>
                            <textarea id="correction_note" name="correction_note" rows="3" maxlength="2000" class="w-full p-3 mt-2 text-sm border border-gray-300 rounded-xl">{{ old('correction_note') }}</textarea>
                            @error('correction_note')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="$refs.rejectDialog.close()" class="px-5 py-3 text-sm font-semibold text-gray-700 border border-gray-300 min-h-11 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                            <button type="submit" class="px-5 py-3 text-sm font-semibold text-white min-h-11 rounded-xl bg-rose-700 hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">Record decision</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endif
    </section>

    <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
        <h2 class="text-lg font-bold text-gray-900">Audit history</h2>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm text-left">
            <thead><tr class="text-gray-500 border-b border-gray-200"><th class="py-2">When</th><th>Action</th><th>Actor</th><th>Change</th><th>Reason</th></tr></thead>
            <tbody>@foreach($case->audits->sortByDesc('id') as $audit)
                @php
                    $auditReason = match (true) {
                        preg_match('/^(?:(?:Local|Persona) ){1,2}Sandbox manual review(?::|$)/i', (string) $audit->reason) === 1 => 'Submitted for administrator review',
                        preg_match('/^(?:(?:Local|Persona) ){1,2}Sandbox test-only approval(?::|$)/i', (string) $audit->reason) === 1 => 'Approved after manual review',
                        default => $audit->reason ?? '—',
                    };
                @endphp
                <tr class="border-b border-gray-100 align-top"><td class="py-2">{{ $audit->created_at?->format('M d, Y h:i A') }}<span class="block text-xs text-gray-500">Round {{ $audit->submission_round }}</span></td><td class="capitalize">{{ str_replace('_', ' ', $audit->action) }}</td><td>{{ $audit->actor?->full_name ?? 'System' }}</td><td>{{ $audit->from_status ?? '—' }} → {{ $audit->to_status ?? '—' }}</td><td class="space-y-2 py-2">
                    <p>{{ $auditReason }}</p>
                    @if(data_get($audit->correction_request, 'learner_note'))<p class="whitespace-pre-wrap"><strong>Learner note:</strong> {{ $audit->correction_request['learner_note'] }}</p>@endif
                    @if(data_get($audit->correction_request, 'note'))<p class="whitespace-pre-wrap"><strong>Reviewer message:</strong> {{ $audit->correction_request['note'] }}</p>@endif
                    @if(data_get($audit->correction_request, 'assisted_review.issuer_verification'))<p class="whitespace-pre-wrap"><strong>Issuer verification:</strong> {{ data_get($audit->correction_request, 'assisted_review.issuer_verification') }}</p>@endif
                    @foreach(($audit->correction_request['evidence'] ?? []) as $snapshot)
                        <a class="mr-3 inline-block font-semibold text-purple-800 underline" target="_blank" rel="noopener" href="{{ route('admin.parent-verifications.learners.evidence', [$case, $snapshot['slot'], 'round' => $audit->submission_round]) }}">{{ str_replace('_', ' ', $snapshot['slot']) }} (round {{ $audit->submission_round }})</a>
                    @endforeach
                </td></tr>
            @endforeach</tbody>
        </table></div>
    </section>
</div>
@endsection
