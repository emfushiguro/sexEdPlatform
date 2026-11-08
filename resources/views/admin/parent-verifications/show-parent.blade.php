@extends('layouts.admin')

@section('title', 'Guardian Identity Review')

@section('content')
@php
    $approvalReasons = [
        'identity_matches_submission' => 'Identity matches submitted account details',
        'government_id_valid' => 'Government ID is valid and readable',
        'guardian_eligibility_confirmed' => 'Guardian eligibility is confirmed',
    ];
    $rejectionReasons = \App\Enums\ParentChildModerationReason::cases();
    $currentPersonaAttempt = $guardian->personaInquiries->sortByDesc('attempt_number')
        ->first(fn ($attempt) => $attempt->isCurrent() && $attempt->learner_identity_verification_id === null);
    $guardianCorrectionItems = $guardian->parent_identity_evidence_source === 'persona'
        ? ['persona_identity' => 'ID and selfie']
        : ['id' => 'ID document'];
@endphp

<div class="space-y-5" x-data="{ approvalModalOpen: @js($errors->hasAny(['sandbox_philippine_id_confirmed', 'sandbox_id_type_confirmed', 'persona_portrait_compared'])), rejectionModalOpen: @js($errors->hasAny(['reason_code', 'custom_reason', 'allow_resubmission', 'requested_corrections', 'requested_corrections.*', 'persona_attempt_id'])) }">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">Guardian Verification</p>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Guardian Identity Review</h1>
            <p class="mt-1 text-sm text-gray-500">Review identity documents before unlocking Guardian features.</p>
        </div>
        <a href="{{ route('admin.parent-verifications.index', ['type' => 'parents']) }}"
           class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Back
        </a>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm lg:col-span-1">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Identity</h2>
            <div class="mt-4 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                    {{ strtoupper(substr((string) ($guardian->name ?? 'G'), 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900">{{ $guardian->full_name ?: $guardian->name }}</p>
                    <p class="text-sm text-gray-500">{{ $guardian->email }}</p>
                </div>
            </div>
            <dl class="mt-5 space-y-3 text-sm">
                <div><dt class="text-gray-500">Username</dt><dd class="font-medium text-gray-900">{{ $guardian->learnerProfile?->username ?? 'N/A' }}</dd></div>
                <div><dt class="text-gray-500">Birthdate</dt><dd class="font-medium text-gray-900">{{ optional($guardian->birthdate)->format('M d, Y') ?? 'N/A' }}</dd></div>
                <div><dt class="text-gray-500">Status</dt><dd class="font-medium text-gray-900">{{ ucfirst((string) ($guardian->parent_verification_status ?? 'not submitted')) }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Government ID</h2>
            <p class="mt-3 text-sm font-semibold text-gray-900">{{ $idTypeLabel }}</p>
            @if($guardian->parent_identity_evidence_source === 'persona')
                <p class="mt-3 text-sm text-gray-700">The government ID and selfie were submitted through Persona. Review the Persona images below; no local copy is stored.</p>
            @else
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm font-semibold text-gray-800">Front Image</p>
                    @if($guardian->parent_id_document_path)
                        <img src="{{ route('admin.parent-verifications.parents.document', [$guardian, 'front']) }}" alt="Guardian ID front" class="mt-3 h-48 w-full rounded-lg border border-gray-100 bg-gray-50 object-contain">
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('admin.parent-verifications.parents.document', [$guardian, 'front']) }}" target="_blank" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">Preview</a>
                            <a href="{{ route('admin.parent-verifications.parents.document', [$guardian, 'front']) }}" download class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Download</a>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-500">Not uploaded</p>
                    @endif
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm font-semibold text-gray-800">Back Image</p>
                    @if($guardian->parent_id_document_back_path)
                        <img src="{{ route('admin.parent-verifications.parents.document', [$guardian, 'back']) }}" alt="Guardian ID back" class="mt-3 h-48 w-full rounded-lg border border-gray-100 bg-gray-50 object-contain">
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('admin.parent-verifications.parents.document', [$guardian, 'back']) }}" target="_blank" class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">Preview</a>
                            <a href="{{ route('admin.parent-verifications.parents.document', [$guardian, 'back']) }}" download class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Download</a>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-500">Not uploaded</p>
                    @endif
                </div>
            </div>
            @endif
        </section>
    </div>

    @include('admin.parent-verifications.partials.persona-summary', [
        'compactPersonaSummary' => true,
        'personaAttempts' => $guardian->personaInquiries,
        'sandboxManualReview' => $guardian->parent_identity_sandbox_manual_review,
    ])

    <section class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Verification Timeline</h2>
        <dl class="mt-4 grid gap-4 text-sm md:grid-cols-3">
            <div><dt class="text-gray-500">Registration Date</dt><dd class="font-medium text-gray-900">{{ optional($guardian->created_at)->format('M d, Y g:i A') }}</dd></div>
            <div><dt class="text-gray-500">Email Verified Date</dt><dd class="font-medium text-gray-900">{{ optional($guardian->email_verified_at)->format('M d, Y g:i A') ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">Verification Submitted Date</dt><dd class="font-medium text-gray-900">{{ optional($guardian->parent_verification_submitted_at)->format('M d, Y g:i A') ?? 'N/A' }}</dd></div>
        </dl>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <caption class="pb-3 text-left font-semibold text-gray-900">Audit history</caption>
                <thead><tr class="border-b border-gray-200 text-gray-500"><th class="py-2">When</th><th>Action</th><th>Actor</th><th>Change</th><th>Reason</th></tr></thead>
                <tbody>
                    @foreach($guardian->guardianIdentityAudits->sortByDesc('id') as $audit)
                        @php
                            $auditReason = match (true) {
                                preg_match('/^(?:(?:Local|Persona) ){1,2}Sandbox manual review(?::|$)/i', (string) $audit->reason) === 1 => 'Submitted for administrator review',
                                preg_match('/^(?:(?:Local|Persona) ){1,2}Sandbox test-only approval(?::|$)/i', (string) $audit->reason) === 1 => 'Approved after manual review',
                                default => $audit->reason ?? '—',
                            };
                        @endphp
                        <tr class="border-b border-gray-100"><td class="py-2">{{ $audit->created_at?->format('M d, Y h:i A') }}</td><td class="capitalize">{{ $audit->action }}</td><td>{{ $audit->actor?->full_name ?? 'System' }}</td><td>{{ $audit->from_status ?? '—' }} → {{ $audit->to_status ?? '—' }}</td><td>{{ $auditReason }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
        @php
            $onboardingStatus = $guardian->guardian_onboarding_status
                ? str($guardian->guardian_onboarding_status)->replace('_', ' ')->title()->toString()
                : ($guardian->hasCompletedProfile() ? 'Completed' : 'Not Started');
        @endphp
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Guardian Onboarding</h2>
                <dl class="mt-4 grid gap-4 text-sm md:grid-cols-3">
                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium text-gray-900">{{ $onboardingStatus }}</dd></div>
                    <div><dt class="text-gray-500">Started Date</dt><dd class="font-medium text-gray-900">{{ optional($guardian->guardian_onboarding_started_at)->format('M d, Y g:i A') ?? 'N/A' }}</dd></div>
                    <div><dt class="text-gray-500">Completed Date</dt><dd class="font-medium text-gray-900">{{ optional($guardian->guardian_onboarding_completed_at)->format('M d, Y g:i A') ?? 'N/A' }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-gray-500">Last updated: {{ optional($guardian->updated_at)->format('M d, Y g:i A') ?? 'N/A' }}</p>
            </div>
            <form method="POST" action="{{ route('admin.parent-verifications.parents.reset-onboarding', $guardian) }}" onsubmit="return confirm('Reset Guardian onboarding for this account?')">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Reset Onboarding
                </button>
            </form>
        </div>
    </section>

    @if(($guardian->parent_verification_status ?? 'pending') === 'pending')
        <section class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Moderation Actions</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button"
                        @click="approvalModalOpen = true"
                        @disabled($guardian->parent_identity_sandbox_manual_review && ! \App\Models\PersonaInquiry::localManualReviewEnabled())
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Approve
                </button>
                <button type="button"
                        @click="rejectionModalOpen = true"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Request changes
                </button>
            </div>
        </section>

        <div x-show="approvalModalOpen"
             x-cloak
             @keydown.escape.window="approvalModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="approvalModalOpen = false"></div>

            <form method="POST"
                  action="{{ route('admin.parent-verifications.parents.approve', $guardian) }}"
                  data-testid="guardian-approval-confirm-modal"
                  role="dialog" aria-modal="true" aria-labelledby="guardian-approval-title"
                  class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
                @csrf
                @if($guardian->parent_identity_sandbox_manual_review)
                    <input type="hidden" name="persona_attempt_id" value="{{ $guardian->personaInquiries->first(fn ($attempt) => $attempt->isCurrent())?->id }}">
                @endif
                <div class="border-b border-gray-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 id="guardian-approval-title" class="text-base font-semibold text-gray-900">Confirm Guardian Approval</h3>
                            <p class="mt-2 text-sm text-gray-600">Select the reason that best supports approving this guardian application.</p>
                        </div>
                        <button type="button" @click="approvalModalOpen = false" aria-label="Close approval dialog" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <label for="approval_reason" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Approval Reason</label>
                    <select id="approval_reason" name="approval_reason" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        <option value="">Select approval reason</option>
                        @foreach($approvalReasons as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($guardian->parent_identity_sandbox_manual_review)
                        <fieldset class="mt-4 space-y-3">
                            <legend class="text-sm font-semibold text-gray-900">Required confirmations</legend>
                            @foreach([
                                'sandbox_philippine_id_confirmed' => 'I confirm the ID was issued in the Philippines.',
                                'sandbox_id_type_confirmed' => 'I confirm the ID matches the selected ID type.',
                                'persona_portrait_compared' => 'I compared the ID and selfie portraits.',
                            ] as $field => $label)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm text-gray-700">
                                    <input type="checkbox" name="{{ $field }}" value="1" required @checked(old($field)) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700">
                                    <span>{{ $label }} (required)</span>
                                </label>
                                @error($field)<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                            @endforeach
                        </fieldset>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" @click="approvalModalOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirm Approval</button>
                </div>
            </form>
        </div>

        <div x-show="rejectionModalOpen"
             x-cloak
             @keydown.escape.window="rejectionModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="rejectionModalOpen = false"></div>

            <form method="POST"
                  action="{{ route('admin.parent-verifications.parents.reject', $guardian) }}"
                  data-testid="guardian-rejection-confirm-modal"
                  role="dialog" aria-modal="true" aria-labelledby="guardian-rejection-title"
                  class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
                @csrf
                <input type="hidden" name="persona_attempt_id" value="{{ $currentPersonaAttempt?->id }}">
                <div class="border-b border-gray-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 id="guardian-rejection-title" class="text-base font-semibold text-gray-900">Record guardian review decision</h3>
                            <p class="mt-2 text-sm text-gray-600">Request changes or record a final rejection. ID and selfie must be submitted again together.</p>
                        </div>
                        <button type="button" @click="rejectionModalOpen = false" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-4 px-6 py-5" x-data="{ reasonCode: @js(old('reason_code', '')), allowResubmission: @js((string) old('allow_resubmission', '1')) }">
                    <div>
                        <label for="guardian_allow_resubmission" class="mb-1 block text-sm font-semibold text-gray-800">Decision</label>
                        <select id="guardian_allow_resubmission" name="allow_resubmission" x-model="allowResubmission" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="1" @selected((string) old('allow_resubmission', '1') === '1')>Request changes</option>
                            <option value="0" @selected((string) old('allow_resubmission', '1') === '0')>Final rejection</option>
                        </select>
                        @error('allow_resubmission')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        @error('persona_attempt_id')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <fieldset x-show="allowResubmission === '1'" :disabled="allowResubmission !== '1'" class="space-y-3">
                        <legend class="text-sm font-semibold text-gray-800">Requested updates</legend>
                        @if($guardian->parent_identity_evidence_source === 'persona')
                            <p class="text-sm text-gray-600">The ID and selfie are verified and resubmitted together.</p>
                        @endif
                        @foreach($guardianCorrectionItems as $correction => $label)
                            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm text-gray-700">
                                <input type="checkbox" name="requested_corrections[]" value="{{ $correction }}" @checked(in_array($correction, (array) old('requested_corrections', $guardian->parent_identity_requested_corrections ?? []), true)) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-rose-700 focus:ring-rose-700">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                        @error('requested_corrections')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        @error('requested_corrections.*')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    </fieldset>
                    <div>
                        <label for="reason_code" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Rejection Reason</label>
                        <select id="reason_code" name="reason_code" x-model="reasonCode" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                            <option value="">Select rejection reason</option>
                            @foreach($rejectionReasons as $reason)
                                <option value="{{ $reason->value }}" @selected(old('reason_code') === $reason->value)>{{ $reason->label() }}</option>
                            @endforeach
                        </select>
                        @error('reason_code')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="reasonCode === 'others'" x-cloak>
                        <label for="custom_reason" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Custom Reason</label>
                        <textarea id="custom_reason" name="custom_reason" rows="3" maxlength="1000" :required="reasonCode === 'others'" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" placeholder="Enter custom rejection reason">{{ old('custom_reason') }}</textarea>
                        @error('custom_reason')<p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" @click="rejectionModalOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Record decision</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
