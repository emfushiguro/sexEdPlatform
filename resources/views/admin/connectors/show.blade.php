@extends('layouts.admin')

@section('title', 'Connector Verification Workspace')
@section('page-title', 'Connector Verification Workspace')

@php
    $statusLabels = [
        'pending' => 'Pending application',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
        'withdrawn' => 'Withdrawn',
    ];
    $verificationLabels = [
        'under_review' => 'Under review',
        'resubmission_required' => 'Resubmission required',
        'verified' => 'Verified evidence',
        'legacy_verified' => 'Legacy verified',
        'reverification_required' => 'Re-verification required',
        'rejected' => 'Rejected evidence',
    ];
    $statusStyles = [
        'pending' => 'bg-amber-100 text-amber-800',
        'verified' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'suspended' => 'bg-slate-100 text-slate-800',
        'withdrawn' => 'bg-slate-100 text-slate-800',
    ];
    $verificationStyles = [
        'under_review' => 'bg-blue-100 text-blue-800',
        'resubmission_required' => 'bg-orange-100 text-orange-800',
        'verified' => 'bg-emerald-100 text-emerald-800',
        'legacy_verified' => 'bg-violet-100 text-violet-800',
        'reverification_required' => 'bg-amber-100 text-amber-800',
        'rejected' => 'bg-rose-100 text-rose-800',
    ];
    $purposeLabels = [
        'organization_identity' => 'Organization identity',
        'regulatory_eligibility' => 'Regulatory eligibility',
        'representative_identity' => 'Representative identity',
        'representative_authority' => 'Representative authority',
        'supporting_evidence' => 'Supporting evidence',
    ];
    $purposeOrder = array_keys($purposeLabels);
    $activeRequirements = $connector->verificationRequirements->whereNull('superseded_at');
    $supersededRequirements = $connector->verificationRequirements->whereNotNull('superseded_at');
    $requirementsByPurpose = $activeRequirements->groupBy('evidence_category');
    $satisfiedCount = $activeRequirements->filter(
        fn ($requirement) => in_array($requirement->status, ['satisfied', 'not_applicable'], true),
    )->count();
    $totalRequirements = $activeRequirements->count();
    $round = (int) ($connector->current_submission_round ?: 1);
    $verificationStatus = (string) ($connector->verification_status ?: '');
    $canReview = $connector->status === 'pending'
        && $verificationStatus === \App\Models\Connector::VERIFICATION_UNDER_REVIEW;
    $canDecide = ! in_array($connector->status, ['rejected', 'withdrawn'], true)
        && $verificationStatus !== \App\Models\Connector::VERIFICATION_REJECTED;
    $identityCase = $connector->primaryRepresentative?->identityVerifications
        ?->first(fn ($case) => $case->superseded_at === null);
    $organizationTypes = config('connector_verification.organization_types', []);
    $organizationTypeLabel = $organizationTypes[$connector->organization_type]['label']
        ?? str((string) $connector->organization_type)->headline()->toString();
    $approvalChecklist = [
        'organization_identity_established' => 'Organization identity established',
        'regulatory_evidence_resolved' => 'Regulatory evidence resolved',
        'representative_identity_verified' => 'Representative identity verified',
        'representative_authority_established' => 'Representative authority established',
        'required_evidence_complete' => 'Required evidence complete',
        'application_consistent' => 'Application consistent',
    ];
    $rejectionCodes = [
        'ineligible' => 'Ineligible organization',
        'fraudulent' => 'Fraudulent or misleading evidence',
        'policy_violation' => 'Policy violation',
        'duplicate' => 'Duplicate organization',
        'unsupported' => 'Unsupported organization type',
        'other' => 'Other',
    ];
    $logoUrl = $connector->logo_path
        ? Storage::disk(config('connector_verification.logo_disk', 'public'))->url($connector->logo_path)
        : null;
@endphp

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.connectors.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-900">
        <span aria-hidden="true">←</span>
        Back to connectors
    </a>

    <header class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Administrator verification workspace</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ $connector->name }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                    Submitted {{ $connector->verification_submitted_at?->format('M d, Y g:i A') ?? $connector->created_at?->format('M d, Y g:i A') ?? 'Unknown date' }}.
                    Round {{ $round }} is the current server-side review round.
                </p>
                <div class="mt-4 flex flex-wrap gap-2" aria-label="Connector status">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusStyles[$connector->status] ?? 'bg-slate-100 text-slate-800' }}">
                        Operational: {{ $statusLabels[$connector->status] ?? str((string) $connector->status)->headline() }}
                    </span>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $verificationStyles[$verificationStatus] ?? 'bg-slate-100 text-slate-800' }}">
                        Evidence: {{ $verificationLabels[$verificationStatus] ?? ($verificationStatus !== '' ? str($verificationStatus)->headline() : 'Not recorded') }}
                    </span>
                </div>
            </div>
            <div class="rounded-2xl border border-brand-100 bg-brand-50/60 px-4 py-3 text-sm text-brand-900">
                <p class="font-semibold">Submission round</p>
                <p class="mt-1 text-2xl font-bold">{{ $round }}</p>
            </div>
        </div>
    </header>

    <section aria-labelledby="verification-summary-heading">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 id="verification-summary-heading" class="text-lg font-bold text-gray-900">Verification summary</h2>
            <span class="text-sm text-gray-500">{{ $satisfiedCount }} of {{ $totalRequirements }} current requirements resolved</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'Organization identity', 'value' => $activeRequirements->where('evidence_category', 'organization_identity')->filter(fn ($item) => in_array($item->status, ['satisfied', 'not_applicable'], true))->count().' resolved', 'tone' => 'brand'],
                ['label' => 'Regulatory eligibility', 'value' => $activeRequirements->where('evidence_category', 'regulatory_eligibility')->filter(fn ($item) => in_array($item->status, ['satisfied', 'not_applicable'], true))->count().' resolved', 'tone' => 'blue'],
                ['label' => 'Representative authority', 'value' => $activeRequirements->where('evidence_category', 'representative_authority')->filter(fn ($item) => in_array($item->status, ['satisfied', 'not_applicable'], true))->count().' resolved', 'tone' => 'emerald'],
                ['label' => 'Completion', 'value' => $satisfiedCount.'/'.$totalRequirements, 'tone' => 'violet'],
            ] as $card)
                <article class="rounded-[22px] border border-gray-200 bg-white p-5 shadow-theme-xs">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <main class="space-y-6">
            <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="organization-heading">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 id="organization-heading" class="text-lg font-bold text-gray-900">Organization identity</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $organizationTypeLabel }} · {{ str($connector->category)->headline() }}</p>
                    </div>
                    @if($logoUrl)
                        <div class="shrink-0">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Organization logo</p>
                            <img src="{{ $logoUrl }}" alt="{{ $connector->name }} logo" class="h-20 w-20 rounded-2xl border border-gray-200 object-cover">
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No organization logo provided.</p>
                    @endif
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Organization email</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->organization_email ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Contact number</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->contact_number ?: 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Address</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->address_line }} · {{ $connector->barangay_code }}, {{ $connector->city_code }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Description</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $connector->description ?: 'No description provided.' }}</dd></div>
                    @if($connector->website_url)
                        <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Website</dt><dd class="mt-1 text-sm"><a href="{{ $connector->website_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:underline">{{ $connector->website_url }}</a></dd></div>
                    @endif
                </dl>
            </section>

            <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="representative-heading">
                <h2 id="representative-heading" class="text-lg font-bold text-gray-900">Representative account and identity</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Primary representative</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $connector->primaryRepresentative?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Account email</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->primaryRepresentative?->email ?? 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Learner identity state</dt>
                        <dd class="mt-1 text-sm font-semibold {{ $identityCase?->status === 'approved' ? 'text-emerald-700' : 'text-gray-700' }}">
                            {{ $identityCase?->status === 'approved' ? 'Approved current identity case' : ($identityCase?->status ? str($identityCase->status)->headline() : 'No current identity case linked') }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="requirements-heading">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="requirements-heading" class="text-lg font-bold text-gray-900">Evidence requirements</h2>
                        <p class="mt-1 text-sm text-gray-500">Current requirements are grouped by verification purpose. Superseded evidence remains visible only as history.</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Round {{ $round }}</span>
                </div>

                <div class="mt-6 space-y-8">
                    @foreach($purposeOrder as $purpose)
                        @if($requirementsByPurpose->has($purpose))
                            <section aria-labelledby="purpose-{{ $purpose }}">
                                <h3 id="purpose-{{ $purpose }}" class="text-sm font-bold uppercase tracking-[0.16em] text-brand-700">{{ $purposeLabels[$purpose] }}</h3>
                                <div class="mt-3 space-y-4">
                                    @foreach($requirementsByPurpose->get($purpose) as $requirement)
                                        @php
                                            $definition = is_array($requirement->definition_snapshot) ? $requirement->definition_snapshot : [];
                                            $source = is_array($requirement->source_snapshot) ? $requirement->source_snapshot : [];
                                            $source = array_replace($definition, $source);
                                            $isExemptable = (bool) ($definition['manual_not_applicable_allowed'] ?? false);
                                            $documents = $requirement->documents;
                                            $currentDocuments = $documents->whereNull('superseded_at');
                                            $supersededDocuments = $documents->whereNotNull('superseded_at');
                                            $statusText = $requirement->status === 'not_applicable' ? 'Not applicable (N/A)' : str((string) $requirement->status)->headline();
                                            $statusClass = match ($requirement->status) {
                                                'satisfied' => 'bg-emerald-100 text-emerald-800',
                                                'rejected', 'expired' => 'bg-rose-100 text-rose-800',
                                                'not_applicable' => 'bg-slate-100 text-slate-800',
                                                default => 'bg-amber-100 text-amber-800',
                                            };
                                        @endphp
                                        <article class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h4 class="font-bold text-gray-900">{{ $requirement->label }}</h4>
                                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $requirement->is_required ? 'bg-brand-100 text-brand-800' : 'bg-white text-gray-600 border border-gray-200' }}">{{ $requirement->is_required ? 'Required' : 'Optional' }}</span>
                                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ $statusText }}</span>
                                                    </div>
                                                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $requirement->guidance }}</p>
                                                    <dl class="mt-3 grid gap-2 text-xs text-gray-600 sm:grid-cols-2">
                                                        @if($source['issuing_authority'] ?? null)<div><dt class="font-semibold text-gray-500">Issuer</dt><dd>{{ $source['issuing_authority'] }}</dd></div>@endif
                                                        @if($source['source_reviewed_on'] ?? null)<div><dt class="font-semibold text-gray-500">Source reviewed</dt><dd>{{ $source['source_reviewed_on'] }}</dd></div>@endif
                                                        @if($source['source_url'] ?? null)<div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Official source</dt><dd><a href="{{ $source['source_url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:underline">Open official source</a></dd></div>@endif
                                                    </dl>
                                                </div>
                                                <span class="shrink-0 text-xs font-semibold text-gray-500">{{ $currentDocuments->count() }} current file(s)</span>
                                            </div>

                                            @if($currentDocuments->isNotEmpty())
                                                <div class="mt-4 space-y-3">
                                                    @foreach($currentDocuments as $document)
                                                        @php
                                                            $documentStatusClass = match ($document->status) {
                                                                'accepted' => 'bg-emerald-100 text-emerald-800',
                                                                'rejected', 'expired' => 'bg-rose-100 text-rose-800',
                                                                'superseded' => 'bg-slate-100 text-slate-700',
                                                                default => 'bg-amber-100 text-amber-800',
                                                            };
                                                        @endphp
                                                        <div class="rounded-xl border border-gray-200 bg-white p-3">
                                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                                <div class="min-w-0">
                                                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $document->original_name }}</p>
                                                                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                                                                        <span>Round {{ $document->submission_round }}</span>
                                                                        <span>{{ $document->mime_type }}</span>
                                                                        @if($document->issuer)<span>Issuer: {{ $document->issuer }}</span>@endif
                                                                        @if($document->issue_date)<span>Issued {{ $document->issue_date->format('M d, Y') }}</span>@endif
                                                                        @if($document->expiry_date)<span>Expires {{ $document->expiry_date->format('M d, Y') }}</span>@endif
                                                                    </div>
                                                                    @if($document->applicant_rejection_reason)
                                                                        <p class="mt-2 text-xs leading-5 text-rose-700"><span class="font-semibold">Applicant reason:</span> {{ $document->applicant_rejection_reason }}</p>
                                                                    @endif
                                                                </div>
                                                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $documentStatusClass }}">{{ str((string) $document->status)->headline() }}</span>
                                                            </div>
                                                            <div class="mt-3 flex flex-wrap gap-2">
                                                                <a href="{{ route('admin.connectors.verification.documents.show', [$connector, $document, 'disposition' => 'inline']) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-800 hover:bg-brand-100">Preview</a>
                                                                <a href="{{ route('admin.connectors.verification.documents.show', [$connector, $document, 'disposition' => 'attachment']) }}" class="inline-flex min-h-10 items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Download</a>
                                                            </div>
                                                            @if($canReview && $document->status === 'pending' && (int) $document->submission_round === $round)
                                                                <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 lg:grid-cols-2">
                                                                    <form method="POST" action="{{ route('admin.connectors.verification.documents.review', [$connector, $document]) }}" class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3">
                                                                        @csrf
                                                                        <input type="hidden" name="submission_round" value="{{ $round }}">
                                                                        <input type="hidden" name="decision" value="accept">
                                                                        <label class="block text-xs font-semibold text-gray-700" for="accept-note-{{ $document->id }}">Internal note</label>
                                                                        <textarea id="accept-note-{{ $document->id }}" name="internal_note" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Optional internal note"></textarea>
                                                                        <button type="submit" class="mt-2 inline-flex min-h-10 items-center rounded-xl bg-emerald-700 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-800">Accept evidence</button>
                                                                    </form>
                                                                    <form method="POST" action="{{ route('admin.connectors.verification.documents.review', [$connector, $document]) }}" class="rounded-xl border border-rose-100 bg-rose-50/50 p-3">
                                                                        @csrf
                                                                        <input type="hidden" name="submission_round" value="{{ $round }}">
                                                                        <input type="hidden" name="decision" value="reject">
                                                                        <label class="block text-xs font-semibold text-gray-700" for="reject-reason-{{ $document->id }}">Applicant reason</label>
                                                                        <textarea id="reject-reason-{{ $document->id }}" name="applicant_reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Explain what the applicant must correct"></textarea>
                                                                        <label class="mt-2 block text-xs font-semibold text-gray-700" for="reject-note-{{ $document->id }}">Internal note</label>
                                                                        <textarea id="reject-note-{{ $document->id }}" name="internal_note" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Optional internal note"></textarea>
                                                                        <button type="submit" class="mt-2 inline-flex min-h-10 items-center rounded-xl bg-rose-700 px-3 py-2 text-xs font-bold text-white hover:bg-rose-800">Reject evidence</button>
                                                                    </form>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="mt-4 rounded-xl border border-dashed border-gray-300 bg-white px-3 py-3 text-sm text-gray-500">No current evidence file submitted.</p>
                                            @endif

                                            @if($supersededDocuments->isNotEmpty())
                                                <details class="mt-4 rounded-xl border border-gray-200 bg-white">
                                                    <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-gray-700">Superseded evidence history ({{ $supersededDocuments->count() }})</summary>
                                                    <div class="space-y-2 border-t border-gray-100 p-3">
                                                        @foreach($supersededDocuments as $document)
                                                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                                                <span>{{ $document->original_name }} · Round {{ $document->submission_round }}</span>
                                                                <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-700">Superseded</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif

                                            @if($canReview && $requirement->status === 'pending' && $isExemptable)
                                                <form method="POST" action="{{ route('admin.connectors.verification.requirements.resolve', [$connector, $requirement]) }}" class="mt-4 rounded-xl border border-slate-200 bg-white p-3">
                                                    @csrf
                                                    <input type="hidden" name="submission_round" value="{{ $round }}">
                                                    <label class="block text-xs font-semibold text-gray-700" for="na-reason-{{ $requirement->id }}">N/A reason</label>
                                                    <textarea id="na-reason-{{ $requirement->id }}" name="reason" rows="2" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Explain why this requirement does not apply"></textarea>
                                                    <label class="mt-2 block text-xs font-semibold text-gray-700" for="na-note-{{ $requirement->id }}">Internal note</label>
                                                    <textarea id="na-note-{{ $requirement->id }}" name="internal_note" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Optional internal note"></textarea>
                                                    <button type="submit" class="mt-2 inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800 hover:bg-slate-200">Mark not applicable</button>
                                                </form>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach

                    @if($supersededRequirements->isNotEmpty())
                        <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <summary class="cursor-pointer text-sm font-bold text-slate-800">Superseded requirement history ({{ $supersededRequirements->count() }})</summary>
                            <div class="mt-3 space-y-2 text-sm text-slate-600">
                                @foreach($supersededRequirements as $requirement)
                                    <p>{{ $requirement->label }} · Round {{ $requirement->created_at?->format('M d, Y') ?? 'prior' }}</p>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </section>
        </main>

        <aside class="space-y-6">
            <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs" aria-labelledby="decision-heading">
                <h2 id="decision-heading" class="text-lg font-bold text-gray-900">Decision controls</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Every decision includes the current submission round and is checked again by the service before it persists.</p>

                @if($canReview)
                    <form method="POST" action="{{ route('admin.connectors.approve', $connector) }}" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $round }}">
                        <p class="text-sm font-bold text-emerald-900">Approval checklist</p>
                        <p class="mt-1 text-xs text-emerald-900/80">Complete every checklist item before approving.</p>
                        <div class="mt-3 space-y-2">
                            @foreach($approvalChecklist as $key => $label)
                                <label class="flex items-start gap-2 text-sm text-emerald-950">
                                    <input type="checkbox" name="checklist[{{ $key }}]" value="1" required class="mt-1 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-500">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-4 block text-xs font-semibold text-gray-700" for="approval-note">Internal note</label>
                        <textarea id="approval-note" name="internal_note" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Optional internal note"></textarea>
                        <button type="submit" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Approve connector</button>
                    </form>

                    @if($activeRequirements->contains(fn ($requirement) => in_array($requirement->status, ['rejected', 'expired'], true)))
                        <form method="POST" action="{{ route('admin.connectors.verification.request-resubmission', $connector) }}" class="mt-4 rounded-2xl border border-orange-200 bg-orange-50/60 p-4">
                            @csrf
                            <input type="hidden" name="submission_round" value="{{ $round }}">
                            <p class="text-sm font-bold text-orange-900">Request replacement evidence</p>
                            <label class="mt-3 block text-xs font-semibold text-gray-700" for="resubmission-reason">Applicant reason</label>
                            <textarea id="resubmission-reason" name="applicant_reason" rows="3" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Tell the applicant what must be replaced"></textarea>
                            <label class="mt-3 block text-xs font-semibold text-gray-700" for="resubmission-note">Internal note</label>
                            <textarea id="resubmission-note" name="internal_note" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Optional internal note"></textarea>
                            <button type="submit" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-orange-300 bg-white px-4 py-2.5 text-sm font-bold text-orange-900 hover:bg-orange-100">Request resubmission</button>
                        </form>
                    @endif
                @endif

                @if($canDecide)
                    <form method="POST" action="{{ route('admin.connectors.reject', $connector) }}" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50/60 p-4">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $round }}">
                        <p class="text-sm font-bold text-rose-900">Permanently reject</p>
                        <label class="mt-3 block text-xs font-semibold text-gray-700" for="rejection-code">Reason code</label>
                        <select id="rejection-code" name="reason_code" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            @foreach($rejectionCodes as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
                        </select>
                        <label class="mt-3 block text-xs font-semibold text-gray-700" for="rejection-reason">Applicant reason</label>
                        <textarea id="rejection-reason" name="applicant_reason" rows="3" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Explain the final decision to the applicant"></textarea>
                        <label class="mt-3 block text-xs font-semibold text-gray-700" for="rejection-note">Internal note</label>
                        <textarea id="rejection-note" name="internal_note" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Optional internal note"></textarea>
                        <button type="submit" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-rose-800">Permanently reject</button>
                    </form>

                    @if($connector->status !== 'suspended')
                        <form method="POST" action="{{ route('admin.connectors.suspend', $connector) }}" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
                            @csrf
                            <input type="hidden" name="submission_round" value="{{ $round }}">
                            <p class="text-sm font-bold text-amber-900">Suspend operational access</p>
                            <label class="mt-3 block text-xs font-semibold text-gray-700" for="suspend-reason">Reason</label>
                            <textarea id="suspend-reason" name="reason" rows="3" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Explain the operational suspension"></textarea>
                            <button type="submit" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-800">Suspend connector</button>
                        </form>
                    @endif
                @endif
            </section>

            @if($connector->organization_type === 'other' && $canReview)
                <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs" aria-labelledby="supplemental-heading">
                    <h2 id="supplemental-heading" class="text-lg font-bold text-gray-900">Supplemental evidence</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">This control is available only for the snapshotted Other organization path.</p>
                    <form method="POST" action="{{ route('admin.connectors.verification.request-evidence', $connector) }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $round }}">
                        <label class="block text-xs font-semibold text-gray-700" for="supplemental-label">Requirement label</label>
                        <input id="supplemental-label" name="label" required maxlength="255" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Current activity certification">
                        <label class="block text-xs font-semibold text-gray-700" for="supplemental-guidance">Applicant guidance</label>
                        <textarea id="supplemental-guidance" name="guidance" required rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Explain what evidence is needed"></textarea>
                        <label class="block text-xs font-semibold text-gray-700" for="supplemental-category">Evidence purpose</label>
                        <select id="supplemental-category" name="evidence_category" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            @foreach($purposeLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-semibold text-gray-700" for="supplemental-min">Minimum files<input id="supplemental-min" name="minimum_evidence_count" type="number" min="1" max="5" value="1" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                            <label class="block text-xs font-semibold text-gray-700" for="supplemental-max">Maximum files<input id="supplemental-max" name="maximum_evidence_count" type="number" min="1" max="5" value="1" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"></label>
                        </div>
                        <fieldset>
                            <legend class="text-xs font-semibold text-gray-700">Accepted file types</legend>
                            <div class="mt-2 grid gap-2 text-xs text-gray-700 sm:grid-cols-2">
                                @foreach(['application/pdf' => 'PDF', 'image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/webp' => 'WebP'] as $mime => $label)
                                    <label class="flex items-center gap-2"><input type="checkbox" name="accepted_mime_types[]" value="{{ $mime }}" checked class="rounded border-gray-300 text-brand-700 focus:ring-brand-500"><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <label class="flex items-center gap-2 text-xs text-gray-700"><input type="checkbox" name="expires" value="1" class="rounded border-gray-300 text-brand-700 focus:ring-brand-500"><span>Evidence expires</span></label>
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-800 hover:bg-brand-100">Request supplemental evidence</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>

    <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="history-heading">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 id="history-heading" class="text-lg font-bold text-gray-900">Audit and review history</h2>
                <p class="mt-1 text-sm text-gray-500">Append-only history of decision and evidence events. Private storage metadata is intentionally omitted.</p>
            </div>
        </div>
        <div class="mt-5 grid gap-3 lg:grid-cols-2">
            @forelse($connector->verificationAudits as $audit)
                <article class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-gray-900">{{ str((string) $audit->action)->headline() }}</p>
                        <time class="text-xs text-gray-500">{{ $audit->created_at?->format('M d, Y g:i A') }}</time>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $audit->actor?->name ?? 'System' }} · Round {{ (int) $audit->submission_round }}
                        @if($audit->requirement) · {{ $audit->requirement->label }} @endif
                    </p>
                </article>
            @empty
                <p class="text-sm text-gray-500">No verification audit history yet.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="review-history-heading">
        <h2 id="review-history-heading" class="text-lg font-bold text-gray-900">Review decisions</h2>
        <div class="mt-4 space-y-3">
            @forelse($connector->reviews as $review)
                <article class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-gray-900">{{ $review->from_status ?: 'New' }} → {{ $review->to_status }}</p>
                        <time class="text-xs text-gray-500">{{ $review->reviewed_at?->format('M d, Y g:i A') }}</time>
                    </div>
                    <p class="mt-1 text-sm leading-6 text-gray-700">{{ $review->reason }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $review->reviewer?->name ?? 'System' }} · Round {{ (int) $review->submission_round }}</p>
                </article>
            @empty
                <p class="text-sm text-gray-500">No review decisions yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
