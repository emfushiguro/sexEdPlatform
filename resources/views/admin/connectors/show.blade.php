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
    $submittedRequirements = $activeRequirements->filter(fn ($requirement) => $requirement->documents->whereNull('superseded_at')->isNotEmpty());
    $requirementsByPurpose = $submittedRequirements->groupBy('evidence_category');
    $reviewExceptions = $activeRequirements->filter(fn ($requirement) =>
        $requirement->documents->whereNull('superseded_at')->isEmpty()
        && ! in_array($requirement->status, ['satisfied', 'not_applicable'], true)
        && ($requirement->is_required || ($requirement->definition_snapshot['manual_not_applicable_allowed'] ?? false))
    );
    $representativeAvatarPath = $connector->primaryRepresentative?->learnerProfile?->avatar_path;
    $representativeAvatarUrl = $representativeAvatarPath ? Storage::disk('public')->url($representativeAvatarPath) : null;
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
<div class="space-y-6" x-data="connectorReviewWorkspace()" data-reopen-dialog="{{ $errors->any() ? old('review_dialog', '') : '' }}">
    <a href="{{ route('admin.connectors.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-900">
        <span aria-hidden="true">←</span>
        Back to connectors
    </a>
    <a href="{{ route('admin.connectors.module-access.index', ['connector_id' => $connector->id]) }}" class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-brand-700">Manage module access</a>

    <header class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Administrator verification workspace</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ $connector->name }}</h1>
                <p class="max-w-3xl mt-2 text-sm leading-6 text-gray-600">
                    Submitted {{ $connector->verification_submitted_at?->format('M d, Y g:i A') ?? $connector->created_at?->format('M d, Y g:i A') ?? 'Unknown date' }}.
                    Round {{ $round }} is the current server-side review round.
                </p>
                <div class="flex flex-wrap gap-2 mt-4" aria-label="Connector status">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusStyles[$connector->status] ?? 'bg-slate-100 text-slate-800' }}">
                        Operational: {{ $statusLabels[$connector->status] ?? str((string) $connector->status)->headline() }}
                    </span>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $verificationStyles[$verificationStatus] ?? 'bg-slate-100 text-slate-800' }}">
                        Evidence: {{ $verificationLabels[$verificationStatus] ?? ($verificationStatus !== '' ? str($verificationStatus)->headline() : 'Not recorded') }}
                    </span>
                </div>
            </div>
            <div class="px-4 py-3 text-sm border rounded-2xl border-brand-100 bg-brand-50/60 text-brand-900">
                <p class="font-semibold">Submission round</p>
                <p class="mt-1 text-2xl font-bold">{{ $round }}</p>
            </div>
        </div>
    </header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <main class="space-y-6">
            <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="organization-heading">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 id="organization-heading" class="text-lg font-bold text-gray-900">Organization identity</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $organizationTypeLabel }}@if($connector->category !== $connector->organization_type) · {{ str($connector->category)->headline() }}@endif</p>
                        @if($connector->organization_type === 'other' && $connector->organization_type_detail)
                            <p class="mt-1 text-sm text-gray-700">Specified type: {{ $connector->organization_type_detail }}</p>
                        @endif
                    </div>
                    @if($logoUrl)
                        <div class="shrink-0">
                            <p class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">Organization logo</p>
                            <img src="{{ $logoUrl }}" alt="{{ $connector->name }} logo" class="object-cover w-20 h-20 border border-gray-200 rounded-2xl">
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No organization logo provided.</p>
                    @endif
                </div>
                <dl class="grid gap-4 mt-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Organization email</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->organization_email ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Contact number</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->contact_number ?: 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Address</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->address_line }} · {{ $connector->barangay_code }}, {{ $connector->city_code }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Description</dt><dd class="mt-1 text-sm leading-6 text-gray-700 whitespace-pre-line">{{ $connector->description ?: 'No description provided.' }}</dd></div>
                    @if($connector->website_url)
                        <div class="sm:col-span-2"><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Website</dt><dd class="mt-1 text-sm"><a href="{{ $connector->website_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:underline">{{ $connector->website_url }}</a></dd></div>
                    @endif
                </dl>
            </section>

            <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="representative-heading">
                <div class="flex items-center gap-3">
                    <div class="relative flex items-center justify-center overflow-hidden font-bold rounded-full h-14 w-14 shrink-0 bg-brand-50 text-brand-700">
                        <span aria-hidden="true">{{ str($connector->primaryRepresentative?->name ?? '?')->substr(0, 1)->upper() }}</span>
                        @if($representativeAvatarUrl)
                            <img src="{{ $representativeAvatarUrl }}" alt="{{ $connector->primaryRepresentative?->name }} profile photo" class="absolute inset-0 object-cover w-full h-full" x-on:error="$event.currentTarget.hidden = true">
                        @endif
                    </div>
                    <h2 id="representative-heading" class="text-lg font-bold text-gray-900">Representative account and identity</h2>
                </div>
                <dl class="grid gap-4 mt-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Primary representative</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $connector->primaryRepresentative?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Account email</dt><dd class="mt-1 text-sm text-gray-900">{{ $connector->primaryRepresentative?->email ?? 'Not provided' }}</dd></div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Learner identity state</dt>
                        <dd class="mt-1 text-sm font-semibold {{ $identityCase?->status === 'approved' ? 'text-emerald-700' : 'text-gray-700' }}">
                            {{ $identityCase?->status === 'approved' ? 'Approved current identity case' : ($identityCase?->status ? str($identityCase->status)->headline() : 'No current identity case linked') }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="requirements-heading">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="requirements-heading" class="text-lg font-bold text-gray-900">Submitted documents</h2>
                        <p class="mt-1 text-sm text-gray-500">Only submitted files appear below. Click a thumbnail to inspect evidence without leaving this page.</p>
                    </div>
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Round {{ $round }}</span>
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
                                            $isExemptable = (bool) ($definition['manual_not_applicable_allowed'] ?? false);
                                            $importance = $requirement->is_required ? 'Required' : (in_array($requirement->rule_mode, ['any', 'minimum'], true) ? 'Required alternative' : 'Optional');
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
                                        <article class="p-4 border border-gray-200 rounded-2xl bg-gray-50/60">
                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h4 class="font-bold text-gray-900">{{ $requirement->label }}</h4>
                                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $importance !== 'Optional' ? 'bg-brand-100 text-brand-800' : 'bg-white text-gray-600 border border-gray-200' }}">{{ $importance }}</span>
                                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ $statusText }}</span>
                                                    </div>

                                                </div>
                                                <span class="text-xs font-semibold text-gray-500 shrink-0">{{ $currentDocuments->count() }} current file(s)</span>
                                            </div>

                                            <div class="mt-4 space-y-3">
                                                @foreach($currentDocuments as $document)
                                                    @include('admin.connectors.partials.evidence-document')
                                                @endforeach
                                            </div>

                                            @if($supersededDocuments->isNotEmpty())
                                                <details class="mt-4 bg-white border border-gray-200 rounded-xl">
                                                    <summary class="px-3 py-2 text-sm font-semibold text-gray-700 cursor-pointer">Superseded evidence history ({{ $supersededDocuments->count() }})</summary>
                                                    <div class="p-3 space-y-2 border-t border-gray-100">
                                                        @foreach($supersededDocuments as $document)
                                                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                                                <span>{{ $document->original_name }} · Round {{ $document->submission_round }}</span>
                                                                <span class="px-2 py-1 font-semibold rounded-full bg-slate-100 text-slate-700">Superseded</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif

                                            @if($canReview && $requirement->status === 'pending' && $isExemptable)
                                                <button type="button" data-dialog="na-requirement-{{ $requirement->id }}" @click="openDialog($event.currentTarget.dataset.dialog)" class="mt-3 text-xs font-semibold min-h-10 text-brand-700 hover:underline">Mark not applicable</button>
                                            @endif

                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach

                    @if($submittedRequirements->isEmpty())
                        <p class="text-sm text-gray-500">No submitted files in the current requirements.</p>
                    @endif
                    @foreach($activeRequirements->whereNotNull('learner_identity_verification_id') as $requirement)
                        <p class="p-3 text-sm rounded-xl bg-emerald-50 text-emerald-800">{{ $requirement->label }}: linked learner identity case {{ $requirement->learnerIdentityVerification?->status === 'approved' && $requirement->learnerIdentityVerification?->superseded_at === null ? 'approved' : 'requires review' }}. No duplicate upload is needed.</p>
                    @endforeach
                    @foreach($activeRequirements->diff($submittedRequirements) as $requirement)
                        @if($requirement->documents->whereNotNull('superseded_at')->isNotEmpty())
                            <details class="p-3 border border-gray-200 rounded-xl">
                                <summary class="text-sm font-semibold text-gray-700 cursor-pointer">Superseded evidence history — {{ $requirement->label }}</summary>
                                <div class="mt-3 space-y-3">
                                    @foreach($requirement->documents->whereNotNull('superseded_at') as $document)
                                        @include('admin.connectors.partials.evidence-document')
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @endforeach

                    @if($supersededRequirements->isNotEmpty())
                        <details class="p-4 border rounded-2xl border-slate-200 bg-slate-50">
                            <summary class="text-sm font-bold cursor-pointer text-slate-800">Superseded requirement history ({{ $supersededRequirements->count() }})</summary>
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

                <div class="mt-5 space-y-3" data-decision-controls>
                    @if($canReview)
                        <button type="button" data-dialog="approve-connector" @click="openDialog($event.currentTarget.dataset.dialog)" class="min-h-11 w-full rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Approve connector</button>
                        @if($activeRequirements->contains(fn ($requirement) => in_array($requirement->status, ['rejected', 'expired'], true) || ($requirement->status === 'pending' && $requirement->requested_by)))
                            <button type="button" data-dialog="resubmit-connector" @click="openDialog($event.currentTarget.dataset.dialog)" class="min-h-11 w-full rounded-xl border border-orange-200 px-4 py-2.5 text-sm font-semibold text-orange-800 hover:bg-orange-50">Request resubmission</button>
                        @endif
                    @endif
                    @if($canDecide)
                        <button type="button" data-dialog="reject-connector" @click="openDialog($event.currentTarget.dataset.dialog)" class="min-h-11 w-full rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Permanently reject</button>
                        @if($connector->status !== 'suspended')
                            <button type="button" data-dialog="suspend-connector" @click="openDialog($event.currentTarget.dataset.dialog)" class="min-h-11 w-full rounded-xl border border-amber-200 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-50">Suspend connector</button>
                        @endif
                    @endif
                </div>
                @if($canReview)
                    <p class="mt-4 text-xs leading-5 text-gray-500">Approval requires accepted evidence for mandatory rules, a valid representative identity, and independent representative authority.</p>
                @endif
                @if($reviewExceptions->isNotEmpty())
                    <div class="pt-4 mt-5 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Unresolved requirements</h3>
                        <p class="mt-1 text-xs text-gray-500">Required evidence must be resolved before approval.</p>
                        <ul class="mt-3 space-y-3">
                            @foreach($reviewExceptions as $requirement)
                                <li class="text-sm text-gray-700">
                                    <span>{{ $requirement->label }}</span>
                                    @if($canReview && $requirement->status === 'pending' && ($requirement->definition_snapshot['manual_not_applicable_allowed'] ?? false))
                                        <button type="button" data-dialog="na-requirement-{{ $requirement->id }}" @click="openDialog($event.currentTarget.dataset.dialog)" class="block mt-1 text-xs font-semibold min-h-10 text-brand-700 hover:underline">Mark not applicable</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </section>

            @if($connector->organization_type === 'other' && $canReview)
                <section class="rounded-[24px] border border-gray-200 bg-white p-5 shadow-theme-xs" aria-labelledby="supplemental-heading">
                    <h2 id="supplemental-heading" class="text-lg font-bold text-gray-900">Supplemental evidence</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">This control is available only for the snapshotted Other organization path.</p>
                    <button type="button" data-dialog="supplemental-evidence" @click="openDialog($event.currentTarget.dataset.dialog)" class="w-full px-4 py-2 mt-4 text-sm font-semibold border min-h-11 rounded-xl border-brand-200 text-brand-700 hover:bg-brand-50">Request supplemental evidence</button>
                    <x-admin.connector-review-dialog id="supplemental-evidence" title="Request supplemental evidence" description="Specify the evidence this organization must provide."
                        :action="route('admin.connectors.verification.request-evidence', $connector)" :round="$round" submit="Request evidence">
                        <label class="block text-xs font-semibold text-gray-700" for="supplemental-label">Requirement label</label>
                        <input value="{{ old('review_dialog') === 'supplemental-evidence' ? old('label') : '' }}" id="supplemental-label" name="label" required maxlength="255" class="w-full mt-1 text-sm border-gray-300 rounded-lg" placeholder="Current activity certification">
                        <label class="block text-xs font-semibold text-gray-700" for="supplemental-guidance">Applicant guidance</label>
                        <textarea id="supplemental-guidance" name="guidance" required rows="3" class="w-full mt-1 text-sm border-gray-300 rounded-lg" placeholder="Explain what evidence is needed">{{ old('review_dialog') === 'supplemental-evidence' ? old('guidance') : '' }}</textarea>
                        <label class="block text-xs font-semibold text-gray-700" for="supplemental-category">Evidence purpose</label>
                        <select id="supplemental-category" name="evidence_category" required class="w-full mt-1 text-sm border-gray-300 rounded-lg">
                            @foreach($purposeLabels as $key => $label)<option value="{{ $key }}" @selected(old('review_dialog') === 'supplemental-evidence' && old('evidence_category') === $key)>{{ $label }}</option>@endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-semibold text-gray-700" for="supplemental-min">Minimum files<input id="supplemental-min" name="minimum_evidence_count" type="number" min="1" max="5" value="{{ old('review_dialog') === 'supplemental-evidence' ? old('minimum_evidence_count', 1) : 1 }}" required class="w-full mt-1 text-sm border-gray-300 rounded-lg"></label>
                            <label class="block text-xs font-semibold text-gray-700" for="supplemental-max">Maximum files<input id="supplemental-max" name="maximum_evidence_count" type="number" min="1" max="5" value="{{ old('review_dialog') === 'supplemental-evidence' ? old('maximum_evidence_count', 1) : 1 }}" required class="w-full mt-1 text-sm border-gray-300 rounded-lg"></label>
                        </div>
                        <fieldset>
                            <legend class="text-xs font-semibold text-gray-700">Accepted file types</legend>
                            <div class="grid gap-2 mt-2 text-xs text-gray-700 sm:grid-cols-2">
                                @foreach(['application/pdf' => 'PDF', 'image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/webp' => 'WebP'] as $mime => $label)
                                    <label class="flex items-center gap-2"><input type="checkbox" name="accepted_mime_types[]" value="{{ $mime }}" @checked(old('review_dialog') !== 'supplemental-evidence' || in_array($mime, (array) old('accepted_mime_types', []), true)) class="border-gray-300 rounded text-brand-700 focus:ring-brand-500"><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <label class="flex items-center gap-2 text-xs text-gray-700"><input type="checkbox" name="expires" value="1" @checked(old('review_dialog') === 'supplemental-evidence' && old('expires')) class="border-gray-300 rounded text-brand-700 focus:ring-brand-500"><span>Evidence expires</span></label>
                    </x-admin.connector-review-dialog>
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
        <div class="grid gap-3 mt-5 lg:grid-cols-2">
            @forelse($connector->verificationAudits as $audit)
                <article class="p-4 border border-gray-100 rounded-2xl bg-gray-50/60">
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
                <article class="p-4 border border-gray-100 rounded-2xl bg-gray-50/60">
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
    <div data-decision-dialog>
        @foreach([
            'approve' => ['title' => 'Approve connector', 'description' => 'Confirm approval of this submission. The server will verify required evidence before activating the connector.', 'route' => 'admin.connectors.approve'],
            'resubmit' => ['title' => 'Request resubmission', 'description' => 'Tell the applicant which rejected, expired, or requested evidence must be replaced.', 'route' => 'admin.connectors.verification.request-resubmission'],
            'reject' => ['title' => 'Permanently reject', 'description' => 'This is a final rejection, not a request for corrections. Explain the decision to the applicant.', 'route' => 'admin.connectors.reject'],
            'suspend' => ['title' => 'Suspend connector', 'description' => 'Suspend operational access without deleting the submitted evidence.', 'route' => 'admin.connectors.suspend'],
        ] as $action => $decision)
            @if((in_array($action, ['approve', 'resubmit'], true) && $canReview) || (in_array($action, ['reject', 'suspend'], true) && $canDecide && ($action !== 'suspend' || $connector->status !== 'suspended')))
                @php($dialogId = $action.'-connector')
                <x-admin.connector-review-dialog :id="$dialogId" :title="$decision['title']" :description="$decision['description']"
                    :action="route($decision['route'], $connector)" :round="$round" :submit="$decision['title']" :destructive="$action === 'reject'">
                    @if($action === 'reject')
                        <label for="{{ $dialogId }}-code" class="block text-sm font-semibold text-gray-700">Reason code</label>
                        <select id="{{ $dialogId }}-code" name="reason_code" required class="w-full text-sm border-gray-300 rounded-xl">
                            <option value="">Select a reason</option>
                            @foreach($rejectionCodes as $code => $label)
                                <option value="{{ $code }}" @selected(old('review_dialog') === $dialogId && old('reason_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if(in_array($action, ['resubmit', 'reject', 'suspend'], true))
                        @php($reasonField = $action === 'suspend' ? 'reason' : 'applicant_reason')
                        <label for="{{ $dialogId }}-reason" class="block text-sm font-semibold text-gray-700">{{ $action === 'suspend' ? 'Reason' : 'Applicant reason' }}</label>
                        <textarea id="{{ $dialogId }}-reason" name="{{ $reasonField }}" required maxlength="2000" rows="3" class="w-full text-sm border-gray-300 rounded-xl">{{ old('review_dialog') === $dialogId ? old($reasonField) : '' }}</textarea>
                    @endif
                    @if($action !== 'suspend')
                        <label for="{{ $dialogId }}-note" class="block text-sm font-semibold text-gray-700">Internal note <span class="font-normal text-gray-500">(optional)</span></label>
                        <textarea id="{{ $dialogId }}-note" name="internal_note" maxlength="2000" rows="3" class="w-full text-sm border-gray-300 rounded-xl">{{ old('review_dialog') === $dialogId ? old('internal_note') : '' }}</textarea>
                    @endif
                </x-admin.connector-review-dialog>
            @endif
        @endforeach
    </div>
    @if($canReview)
        @foreach($activeRequirements as $requirement)
            @if($requirement->status === 'pending' && ($requirement->definition_snapshot['manual_not_applicable_allowed'] ?? false))
                @php($dialogId = 'na-requirement-'.$requirement->id)
                <x-admin.connector-review-dialog :id="$dialogId" title="Mark not applicable" :description="$requirement->label"
                    :action="route('admin.connectors.verification.requirements.resolve', [$connector, $requirement])" :round="$round" submit="Confirm not applicable">
                    <label for="{{ $dialogId }}-reason" class="block text-sm font-semibold text-gray-700">N/A reason</label>
                    <textarea id="{{ $dialogId }}-reason" name="reason" required maxlength="2000" rows="3" class="w-full text-sm border-gray-300 rounded-xl">{{ old('review_dialog') === $dialogId ? old('reason') : '' }}</textarea>
                    <label for="{{ $dialogId }}-note" class="block text-sm font-semibold text-gray-700">Internal note <span class="font-normal text-gray-500">(optional)</span></label>
                    <textarea id="{{ $dialogId }}-note" name="internal_note" maxlength="2000" rows="3" class="w-full text-sm border-gray-300 rounded-xl">{{ old('review_dialog') === $dialogId ? old('internal_note') : '' }}</textarea>
                </x-admin.connector-review-dialog>
            @endif
        @endforeach
    @endif
    <dialog id="evidence-preview" @click.self="$event.currentTarget.close()" @close="clearPreview()"
            aria-labelledby="evidence-preview-title"
            class="m-auto max-h-[94vh] w-[min(94vw,72rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/70">
        <div class="flex items-center justify-between gap-3 p-4 border-b border-gray-200">
            <h3 id="evidence-preview-title" class="min-w-0 text-sm font-bold text-gray-900 break-words" x-text="preview ? preview.name : 'Evidence preview'"></h3>
            <div class="flex items-center gap-2 shrink-0">
                <a :href="preview ? preview.download : '#'" aria-label="Download previewed document" class="flex items-center justify-center h-11 w-11 rounded-xl text-brand-700 hover:bg-brand-50">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m-4-4 4 4 4-4M5 16v4h14v-4"/></svg>
                </a>
                <button type="button" @click="closeDialog($event.currentTarget)" aria-label="Close document preview" class="flex items-center justify-center text-gray-600 h-11 w-11 rounded-xl hover:bg-gray-100">
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m6 6 12 12M6 18 18 6"/></svg>
                </button>
            </div>
        </div>
        <div class="p-4 bg-gray-50">
            <template x-if="preview && preview.type === 'image'">
                <div>
                    <img :src="preview.url" :alt="preview.name" class="mx-auto max-h-[75vh] w-auto max-w-full rounded-lg object-contain" x-on:error="$event.currentTarget.hidden = true; $event.currentTarget.nextElementSibling.hidden = false">
                    <p hidden role="alert" class="p-6 text-sm text-gray-600">The image could not be loaded. Try downloading the document.</p>
                </div>
            </template>
            <template x-if="preview && preview.type === 'pdf'">
                <div>
                    <iframe :src="preview.url" :title="preview.name" class="h-[72vh] w-full rounded-lg border border-gray-200"></iframe>
                    <p class="mt-2 text-xs text-gray-500">If your browser cannot display this PDF, use the download icon.</p>
                </div>
            </template>
        </div>
    </dialog>

</div>
@endsection
@push('scripts')
<script>
function connectorReviewWorkspace() {
    return {
        preview: null,
        pdfObserver: null,
        pdfLibraryPromise: null,
        init() {
            this.$nextTick(() => {
                this.openDialog(this.$root.dataset.reopenDialog);
                if ('IntersectionObserver' in window) {
                    this.pdfObserver = new IntersectionObserver(entries => {
                        entries.filter(entry => entry.isIntersecting).forEach(entry => {
                            this.pdfObserver.unobserve(entry.target);
                            this.renderPdfThumbnail(entry.target.querySelector('[data-pdf-thumbnail]'));
                        });
                    });
                    this.$root.querySelectorAll('[data-pdf-thumbnail]').forEach(canvas => this.pdfObserver.observe(canvas.parentElement));
                }
            });
        },
        destroy() {
            this.pdfObserver?.disconnect();
        },
        async renderPdfThumbnail(canvas) {
            let document = null;
            try {
                if (!window.ensurePdfJsLib) return;
                this.pdfLibraryPromise ??= window.ensurePdfJsLib();
                const library = await this.pdfLibraryPromise;
                document = await library.getDocument({
                    url: canvas.dataset.previewUrl, withCredentials: true, isEvalSupported: false,
                }).promise;
                const page = await document.getPage(1);
                const original = page.getViewport({ scale: 1 });
                const viewport = page.getViewport({ scale: 192 / Math.max(original.width, original.height) });
                canvas.width = Math.ceil(viewport.width);
                canvas.height = Math.ceil(viewport.height);
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                canvas.classList.remove('hidden');
                canvas.nextElementSibling.classList.add('hidden');
            } catch {
                // Keep the PDF tile and download available for unsupported or malformed files.
            } finally {
                if (document) await document.destroy();
            }
        },
        openDialog(id) {
            const dialog = Array.from(this.$root.querySelectorAll('dialog')).find(item => item.id === id);
            if (dialog && !dialog.open) dialog.showModal();
        },
        closeDialog(button) {
            button.closest('dialog').close();
        },
        submitReview(event) {
            if (event.currentTarget.checkValidity()) {
                event.currentTarget.querySelector('button[type=submit]').disabled = true;
            }
        },
        previewDocument(button) {
            this.preview = {
                url: button.dataset.previewUrl, name: button.dataset.previewName,
                type: button.dataset.previewType, download: button.dataset.downloadUrl,
            };
            this.openDialog('evidence-preview');
        },
        clearPreview() {
            this.preview = null;
        },
    };
}
</script>
@endpush
