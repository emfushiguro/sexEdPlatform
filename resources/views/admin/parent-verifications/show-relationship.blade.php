@extends('layouts.admin')

@section('title', 'Review Relationship Verification')
@section('page-title', 'Review Relationship Verification')

@section('content')
@php
    $status = (string) ($relationship->relationship_verified_status ?: 'pending');
    $relationshipStatus = (string) ($relationship->relationship_status ?: 'pending');
    $dependentStatus = (string) ($relationship->verification_status ?: 'pending');
    $guardianStatus = (string) ($relationship->parent?->parent_verification_status ?: 'unknown');
    $canApprove = $status === 'under_review';
    $canReject = $status === 'under_review';

    $badge = function (?string $value): string {
        return match ((string) $value) {
            'approved', 'verified', 'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'under_review', 'pending', 'resubmission_required' => 'border-amber-200 bg-amber-50 text-amber-700',
            'rejected', 'revoked', 'inactive' => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-gray-200 bg-gray-50 text-gray-600',
        };
    };

    $humanStatus = fn (?string $value): string => str((string) ($value ?: 'unknown'))->replace('_', ' ')->title()->toString();
    $initial = fn ($user, string $fallback): string => strtoupper(substr((string) ($user?->name ?: $fallback), 0, 1));
    $evidenceRounds = $relationship->verificationDocuments
        ->groupBy('submission_round')
        ->sortKeysDesc();
    $currentDocuments = $relationship->verificationDocuments
        ->where('submission_round', $relationship->current_evidence_round)->whereNull('superseded_at');
    $missingDependentRecord = ! $currentDocuments->contains('document_type', 'dependent_birth_record');
    $canApprove = $canApprove && ! $missingDependentRecord;
    $missingDocumentTypes = array_diff_key(
        \App\Support\GuardianRelationshipTypes::documentTypeOptions($relationship->relationship_type),
        array_flip($currentDocuments->pluck('document_type')->all()),
    );
    $guardianIdentityDocuments = collect([
        'front' => [
            'label' => 'Front of guardian ID',
            'path' => (string) ($relationship->parent?->parent_id_document_path ?? ''),
        ],
        'back' => [
            'label' => 'Back of guardian ID',
            'path' => (string) ($relationship->parent?->parent_id_document_back_path ?? ''),
        ],
    ])->map(function (array $document, string $side) use ($relationship): array {
        $extension = strtolower(pathinfo($document['path'], PATHINFO_EXTENSION));

        return [
            ...$document,
            'url' => $document['path'] === '' ? null : route('admin.parent-verifications.parents.document', [$relationship->parent, $side]),
            'is_pdf' => $extension === 'pdf',
        ];
    });
@endphp

<div
    class="space-y-5"
    x-data="{
        decisionModal: null,
        previewDoc: null,
        zoom: 1,
        fit: true,
        openPreview(doc) {
            this.previewDoc = doc;
            this.zoom = 1;
            this.fit = true;
        },
        closePreview() {
            this.previewDoc = null;
            this.zoom = 1;
            this.fit = true;
        },
        zoomIn() {
            this.fit = false;
            this.zoom = Math.min(this.zoom + 0.25, 3);
        },
        zoomOut() {
            this.fit = false;
            this.zoom = Math.max(this.zoom - 0.25, 0.5);
        },
        resetZoom() {
            this.fit = false;
            this.zoom = 1;
        },
        fitToScreen() {
            this.fit = true;
            this.zoom = 1;
        }
    }"
>
    @if(session('success'))
        <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</p>
    @endif
    @foreach($relationship->adultAuthorityRequests as $authority)
        <section id="adult-authority-{{ $authority->id }}" class="rounded-2xl border border-gray-200 bg-white p-5 text-sm text-gray-800 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Adult representative authority: {{ ucfirst($authority->status) }}</h2>
            <p class="mt-2">Submitted {{ $authority->authorized_at?->format('M d, Y H:i') }} under consent version {{ $authority->policy_version }}. The Guardian requested authority to authorize account creation and identity, selfie, and learning-record processing.</p>
            @php $authorityUrl = route('admin.parent-verifications.relationships.adult-authority.document', [$relationship, $authority]); @endphp
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <div><p class="font-semibold text-gray-800">Private authority evidence</p><p class="mt-1 text-xs text-gray-500">{{ $authority->document_mime === 'application/pdf' ? 'PDF document' : 'Image attachment' }}</p></div>
                <button type="button" @click="openPreview({ label: 'Representative authority evidence', url: @js($authorityUrl), isImage: @js(str_starts_with((string) $authority->document_mime, 'image/')) })" aria-label="Preview private authority evidence" title="Preview private authority evidence" class="rounded-lg border border-purple-200 bg-white p-2.5 text-purple-800 hover:bg-purple-50"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
            @if($authority->status === 'pending')
                <form method="POST" action="{{ route('admin.parent-verifications.relationships.adult-authority.decide', [$relationship, $authority]) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="decision" value="approved">
                    <label class="flex items-start gap-2"><input type="checkbox" name="authority_scope_confirmed" value="1" required class="mt-1"><span>I reviewed the document and verified that this person has authority for this dependent and the specific registration and data-processing purposes stated above. Relationship or disability alone is not proof of authority.</span></label>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white" type="submit">Approve authority</button>
                </form>
                <form method="POST" action="{{ route('admin.parent-verifications.relationships.adult-authority.decide', [$relationship, $authority]) }}" class="mt-3">
                    @csrf<input type="hidden" name="decision" value="rejected">
                    <button class="rounded-lg border border-rose-700 px-4 py-2 font-semibold text-rose-700" type="submit">Reject authority evidence</button>
                </form>
            @elseif($authority->status === 'approved')
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                    <p class="font-semibold">Authority review complete</p><p class="mt-1">The Guardian has been notified to create an activation code. The dependent chooses their own password and completes their separate identity review.</p>
                    <a href="{{ route('admin.parent-verifications.index', ['type' => 'relationships']) }}" class="mt-3 inline-flex rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800">Back to verification queue</a>
                </div>
            @endif
        </section>
    @endforeach
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @if($status === 'under_review' && $missingDependentRecord)
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Dependent birth record missing. Request this document before approval. Compare the dependent's name and birthdate with their registration and the parent or guardian named in the evidence with the guardian's verified ID.</p>
        @endif
        <a href="{{ route('admin.parent-verifications.index', ['type' => 'relationships']) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
            Back to Guardian & Dependent Verification
        </a>
    </div>

    <x-guardian-relationship-guidance :relationship-type="$relationship->relationship_type" />

    @if($errors->any())
        <div role="alert" class="p-4 text-sm text-rose-800 border border-rose-200 rounded-xl bg-rose-50">
            @foreach($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Verification Status</p>
                <div class="flex flex-wrap items-center gap-3 mt-2">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $relationship->relationshipVerificationLabel() }}</h1>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Submitted {{ $relationship->relationship_verification_submitted_at?->format('M d, Y h:i A') ?? 'not submitted' }}
                    @if($relationship->relationship_verification_reviewed_at)
                        <span class="mx-1 text-gray-300">|</span>
                        Reviewed {{ $relationship->relationship_verification_reviewed_at->format('M d, Y h:i A') }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl">
        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500">Guardian + Dependent Identity</h2>
        <div class="grid gap-4 mt-4 lg:grid-cols-2">
            <article class="p-4 border border-gray-100 rounded-xl bg-gray-50/70">
                <div class="flex items-start gap-4">
                    <div class="flex items-center justify-center text-lg font-bold rounded-full h-14 w-14 shrink-0 bg-brand-50 text-brand-700">
                        {{ $initial($relationship->parent, 'G') }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $relationship->parent?->name ?? 'Guardian' }}</h3>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badge($guardianStatus) }}">{{ $humanStatus($guardianStatus) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 truncate">{{ $relationship->parent?->email }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Guardian</p>
                    </div>
                </div>
            </article>

            <article class="p-4 border border-gray-100 rounded-xl bg-gray-50/70">
                <div class="flex items-start gap-4">
                    <div class="flex items-center justify-center text-lg font-bold rounded-full h-14 w-14 shrink-0 bg-sky-50 text-sky-700">
                        {{ $initial($relationship->child, 'D') }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $relationship->child?->name ?? 'Dependent' }}</h3>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badge($dependentStatus) }}">{{ $humanStatus($dependentStatus) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 truncate">{{ $relationship->child?->email }}</p>
                        <p class="mt-2 text-sm text-gray-700">Registered birthdate: {{ ($relationship->child?->birthdate ?? $relationship->child?->learnerProfile?->birthdate)?->format('M d, Y') ?? 'Not recorded' }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Dependent</p>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Guardian identity documents</h2>
                <p class="mt-1 text-sm text-gray-500">Compare the guardian’s ID with the relationship evidence below.</p>
            </div>
            @if($relationship->parent)
                <a href="{{ route('admin.parent-verifications.parents.show', ['user' => $relationship->parent, 'from_relationship' => $relationship->id]) }}" class="inline-flex min-h-11 items-center rounded-lg border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500">View guardian information</a>
            @endif
        </div>

        @if($relationship->parent?->parent_identity_evidence_source === 'persona')
            @if(($guardianPersonaAttempt ?? null)?->government_id_verification_id)
                <div class="grid gap-4 mt-4 lg:grid-cols-2">
                    @include('admin.parent-verifications.partials.persona-image', [
                        'attempt' => $guardianPersonaAttempt,
                        'artifact' => 'government-id-front',
                        'label' => 'Front of guardian ID',
                        'compactPersonaSummary' => true,
                    ])
                    @include('admin.parent-verifications.partials.persona-image', [
                        'attempt' => $guardianPersonaAttempt,
                        'artifact' => 'government-id-back',
                        'label' => 'Back of guardian ID',
                        'compactPersonaSummary' => true,
                    ])
                </div>
            @else
                <p class="mt-4 text-sm text-gray-500" role="status">Guardian ID images are unavailable. Open the guardian information to review the identity submission.</p>
            @endif
        @else
        <div class="grid gap-4 mt-4 lg:grid-cols-2">
            @foreach($guardianIdentityDocuments as $document)
                <article class="overflow-hidden border border-gray-200 rounded-xl bg-gray-50">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 bg-white border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $document['label'] }}</h3>
                        @if($document['url'])
                            <div class="flex items-center gap-2">
                                <a href="{{ $document['url'] }}" target="_blank" rel="noopener" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Preview</a>
                                <a href="{{ $document['url'] }}" download class="text-xs font-semibold text-brand-700 hover:text-brand-900" aria-label="Download document" title="Download document"><span aria-hidden="true">&#8681;</span></a>
                            </div>
                        @endif
                    </div>

                    @if($document['url'])
                        <div class="p-3 bg-gray-100">
                            @if($document['is_pdf'])
                                <iframe src="{{ $document['url'] }}#toolbar=0&amp;navpanes=0" title="{{ $document['label'] }}" class="w-full bg-white border border-gray-200 rounded-lg h-72"></iframe>
                            @else
                                <img src="{{ $document['url'] }}" alt="{{ $document['label'] }}" class="object-contain w-full bg-white border border-gray-200 rounded-lg h-72">
                            @endif
                        </div>
                    @else
                        <p class="p-6 text-sm text-center text-gray-500">Not submitted</p>
                    @endif
                </article>
            @endforeach
        </div>
        @endif
    </section>

    <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Submitted Documents</h2>
            </div>
            <span class="text-sm font-semibold text-gray-500">{{ $relationship->verificationDocuments->count() }} submitted</span>
            <a href="{{ route('admin.identity-references.show', 'dependent_birth_record') }}" target="_blank" rel="noopener" class="rounded-lg border border-purple-200 px-3 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50">Birth record references<span class="sr-only"> (opens in a new tab)</span></a>
        </div>

        @forelse($evidenceRounds as $round => $roundDocuments)
            <div class="mt-5" data-testid="evidence-round-{{ $round }}">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Evidence round {{ $round }}</h3>
                </div>
                <div class="grid gap-3 xl:grid-cols-2">
                    @foreach($roundDocuments as $document)
                        @php
                            $documentTypeLabel = (string) config('guardian_relationships.document_types.' . $document->document_type, $humanStatus($document->document_type));
                            $sideLabel = $document->document_side === 'not_applicable'
                                ? 'Not applicable'
                                : (filled($document->document_side) ? $humanStatus($document->document_side) : 'Front');
                            $documentUrl = route('admin.parent-verifications.relationships.documents.show', [$relationship, $document]);
                            $isImage = str_starts_with((string) $document->mime_type, 'image/');
                        @endphp
                <article class="p-4 border border-gray-200 rounded-xl bg-gray-50" data-testid="relationship-document-card">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-gray-900">{{ $documentTypeLabel }}</h3>
                                <span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-600">{{ $sideLabel }}</span>
                                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badge($document->review_status) }}">{{ $document->review_status ? $humanStatus($document->review_status) : ((int) $round === (int) $relationship->current_evidence_round ? 'Pending review' : 'Review state not recorded') }}</span>
                            </div>
                            <dl class="grid gap-2 mt-3 text-sm text-gray-600 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Submitted by</dt>
                                    <dd class="mt-1 font-medium text-gray-800">{{ $document->uploadedBy?->name ?? $relationship->parent?->name ?? 'Guardian' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Submitted</dt>
                                    <dd class="mt-1 font-medium text-gray-800">{{ $document->submitted_at?->format('M d, Y h:i A') ?? $document->created_at?->format('M d, Y h:i A') }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button
                                type="button"
                                @click="openPreview({
                                    label: @js($documentTypeLabel . ' - ' . $sideLabel),
                                    url: @js($documentUrl),
                                    mime: @js((string) $document->mime_type),
                                    isImage: @js($isImage)
                                })"
                                aria-label="Preview {{ $documentTypeLabel }} {{ $sideLabel }}"
                                title="Preview document"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600 text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                            >
                                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                <span class="sr-only">Preview document</span>
                            </button>
                            <a href="{{ $documentUrl }}" download aria-label="Download {{ $documentTypeLabel }} {{ $sideLabel }}" title="Download document" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></svg>
                                <span class="sr-only">Download document</span>
                            </a>
                        </div>
                    </div>
                    @if($isImage)
                        <div class="mt-4 overflow-hidden border border-gray-200 rounded-xl bg-white" data-testid="relationship-document-image-preview">
                            <div class="flex items-center justify-between gap-3 px-3 py-2 border-b border-gray-200">
                                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Image preview</span>
                                <span class="text-xs font-medium text-gray-400">{{ $sideLabel }}</span>
                            </div>
                            <div class="p-3 bg-gray-100">
                                <img src="{{ $documentUrl }}" alt="{{ $documentTypeLabel }} - {{ $sideLabel }}" class="object-contain w-full h-64 bg-white border border-gray-200 rounded-lg">
                            </div>
                        </div>
                    @endif
                </article>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="px-4 py-8 mt-4 text-sm text-center text-gray-500 border border-gray-200 border-dashed rounded-xl bg-gray-50">No documents submitted.</p>
        @endforelse
    </section>

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl lg:col-span-2">
            <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500">Relationship</h2>
            <dl class="grid gap-4 mt-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500">Relationship type</dt>
                    <dd class="mt-1 font-semibold text-gray-900">{{ $relationship->relationshipLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Relationship status</dt>
                    <dd class="mt-1"><span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badge($relationshipStatus) }}">{{ $humanStatus($relationshipStatus) }}</span></dd>
                </div>
            </dl>
        </section>
    </div>
    <section class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl">
        <h2 class="text-lg font-semibold text-gray-900">Audit Trail</h2>
        <div class="mt-3 space-y-2">
            @forelse($relationship->verificationAudits as $audit)
                <div class="px-4 py-3 text-sm text-gray-700 border border-gray-100 rounded-xl bg-gray-50">
                    <span class="font-semibold">{{ $humanStatus($audit->action) }}</span>
                    <span class="mx-1 text-gray-300">|</span>
                    {{ $audit->previous_status ?? 'none' }} to {{ $audit->new_status ?? 'none' }}
                    <span class="mx-1 text-gray-300">|</span>
                    {{ $audit->actor?->name ?? 'System' }}
                    <span class="mx-1 text-gray-300">|</span>
                    {{ $audit->created_at?->format('M d, Y h:i A') }}
                </div>
            @empty
                <p class="text-sm text-gray-500">No audit entries yet.</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5" data-testid="relationship-review-actions">
        <div class="ml-auto max-w-xl">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 sm:text-right">Administrative Decision</p>
            <div class="flex flex-wrap gap-2 sm:justify-end">
                <button type="button" @disabled(! $canApprove) @click="decisionModal = 'approve'" class="px-4 py-2 text-sm font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Approve
                </button>
                <button type="button" @disabled(! $canReject) @click="decisionModal = 'changes'" class="px-4 py-2 text-sm font-semibold text-amber-800 border border-amber-300 rounded-lg bg-amber-50 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50">
                    Request changes
                </button>
                <button type="button" @disabled(! $canReject) @click="decisionModal = 'reject'" class="px-4 py-2 text-sm font-semibold text-white rounded-lg bg-rose-600 hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Final reject
                </button>
            </div>
            @unless($canApprove || $canReject)
                <p class="mt-2 text-sm text-gray-500 sm:text-right" role="status">{{ match ($status) {
                    'pending' => 'Awaiting the guardian’s document submission before review.',
                    'resubmission_required' => 'Awaiting the guardian’s updated documents before another review.',
                    'verified' => 'This relationship has already been approved.',
                    'rejected' => 'This relationship has received a final rejection.',
                    'revoked' => 'This relationship verification has been revoked.',
                    default => 'Moderation is available when a submission is under review.',
                } }}</p>
            @endunless
        </div>
    </section>

    <dialog x-effect="if (previewDoc) { if (!$el.open) $el.showModal(); } else if ($el.open) { $el.close(); }" @close="closePreview()" @click="if ($event.target === $el) $el.close()" aria-labelledby="relationship-preview-title" class="m-auto h-[85dvh] max-h-[850px] w-[calc(100%-2rem)] max-w-5xl overflow-hidden rounded-xl border-0 bg-white p-0 shadow-2xl backdrop:bg-gray-950/70">
        <div class="flex h-full min-h-0 flex-col">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <h3 id="relationship-preview-title" class="font-semibold text-gray-900" x-text="previewDoc?.label"></h3>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="zoomIn()" aria-label="Zoom in" title="Zoom in" class="rounded-lg border border-gray-300 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50"><span aria-hidden="true">&#43;</span></button>
                    <button type="button" @click="zoomOut()" aria-label="Zoom out" title="Zoom out" class="rounded-lg border border-gray-300 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50"><span aria-hidden="true">&#8722;</span></button>
                    <button type="button" @click="resetZoom()" aria-label="Reset zoom" title="Reset zoom" class="rounded-lg border border-gray-300 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50"><span aria-hidden="true">&#8634;</span></button>
                    <button type="button" @click="fitToScreen()" aria-label="Fit to screen" title="Fit to screen" class="rounded-lg border border-gray-300 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50"><span aria-hidden="true">&#9974;</span></button>
                    <a :href="previewDoc?.url" download aria-label="Download document" title="Download document" class="rounded-lg bg-brand-600 px-3 py-2 font-semibold text-white hover:bg-brand-700"><span aria-hidden="true">&#8681;</span></a>
                    <button type="button" @click="closePreview()" aria-label="Close preview" title="Close preview" class="rounded-lg border border-gray-300 px-3 py-2 font-semibold text-gray-700 hover:bg-gray-50"><span aria-hidden="true">&#10005;</span></button>
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-auto bg-gray-100 p-4">
                <template x-if="previewDoc?.isImage">
                    <img :src="previewDoc.url" :alt="previewDoc.label" class="mx-auto object-contain" :style="fit ? 'width: 100%; height: 100%;' : 'width: ' + (zoom * 100) + '%; max-width: none;'">
                </template>
                <template x-if="previewDoc && !previewDoc.isImage">
                    <iframe :src="previewDoc.url" :title="previewDoc.label" class="h-full w-full rounded-lg bg-white" :style="fit ? '' : 'width: ' + (zoom * 100) + '%; height: ' + (zoom * 100) + '%;'"></iframe>
                </template>
            </div>
        </div>
    </dialog>
    <div x-show="decisionModal === 'approve'" x-cloak @keydown.escape.window="decisionModal = null" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50" @click="decisionModal = null"></div>
        <form method="POST" action="{{ route('admin.parent-verifications.relationships.approve', $relationship) }}" class="relative z-10 max-h-[90dvh] w-full max-w-lg overflow-y-auto bg-white shadow-2xl rounded-xl">
            @csrf
            <input type="hidden" name="current_evidence_round" value="{{ $relationship->current_evidence_round }}">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Confirm approval</h3>
                <p class="mt-2 text-sm text-gray-600">Approve this guardian-dependent relationship verification as a single administrative decision.</p>
            </div>
            <fieldset class="space-y-3 border-b border-gray-100 px-6 py-5 text-sm text-gray-700">
                <legend class="sr-only">Approval review checklist</legend>
                <label class="flex items-start gap-3"><input type="checkbox" name="dependent_identity_confirmed" value="1" required class="mt-1 rounded border-gray-300"><span>I compared the dependent's name and birthdate on the birth or civil registry record with their registration.</span></label>
                <label class="flex items-start gap-3"><input type="checkbox" name="guardian_identity_confirmed" value="1" required class="mt-1 rounded border-gray-300"><span>I compared the named parent or guardian with the guardian's verified identity and checked discrepancies.</span></label>
                <label class="flex items-start gap-3"><input type="checkbox" name="relationship_evidence_confirmed" value="1" required class="mt-1 rounded border-gray-300"><span>I reviewed the current evidence and confirmed it supports the claimed relationship. Appearance alone is not proof of authenticity.</span></label>
            </fieldset>
            <div class="flex justify-end gap-2 px-6 py-4">
                <button type="button" @click="decisionModal = null" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">Approve</button>
            </div>
        </form>
    </div>

    <div x-show="decisionModal === 'reject' || decisionModal === 'changes'" x-cloak @keydown.escape.window="decisionModal = null" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/50" @click="decisionModal = null"></div>
        <form method="POST" action="{{ route('admin.parent-verifications.relationships.reject', $relationship) }}" class="relative z-10 w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white shadow-2xl rounded-xl">
            @csrf
            <input type="hidden" name="current_evidence_round" value="{{ $relationship->current_evidence_round }}">
            <input type="hidden" name="allow_resubmission" :value="decisionModal === 'changes' ? '1' : '0'">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900" x-text="decisionModal === 'changes' ? 'Request targeted changes' : 'Final rejection'"></h3>
                <p class="mt-2 text-sm text-gray-600" x-text="decisionModal === 'changes' ? 'Select the current evidence that needs replacement, any missing document types, and explain what to correct. Other evidence is kept.' : 'Reject this relationship claim without allowing resubmission.'"></p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label for="relationship-reject-reason" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Reason</label>
                    <select id="relationship-reject-reason" name="reason_code" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        <option value="">Select reason</option>
                        @foreach($rejectionReasons as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason_code') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <fieldset x-show="decisionModal === 'changes'" :disabled="decisionModal !== 'changes'" class="p-4 border border-gray-200 rounded-xl">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Current evidence to replace</legend>
                    <p class="text-xs text-gray-500">Selecting either side of a front/back pair requests both sides.</p>
                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        @foreach($currentDocuments as $document)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" name="requested_document_ids[]" value="{{ $document->id }}" @checked(in_array($document->id, (array) old('requested_document_ids', []))) class="mt-1 border-gray-300 rounded">
                                <span>{{ config('guardian_relationships.document_types.' . $document->document_type, $document->document_type) }} - {{ $document->original_name }} ({{ $humanStatus($document->document_side) }})</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset x-show="decisionModal === 'changes'" :disabled="decisionModal !== 'changes'" class="p-4 border border-gray-200 rounded-xl">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-[0.14em] text-gray-600">Missing document types to add (optional)</legend>
                    <div class="mt-2 space-y-2 text-sm text-gray-700">
                        @forelse($missingDocumentTypes as $value => $label)
                            <label class="flex items-start gap-2">
                                <input type="checkbox" name="requested_document_types[]" value="{{ $value }}" @checked(in_array($value, (array) old('requested_document_types', []))) class="mt-1 border-gray-300 rounded">
                                <span>{{ $label }}</span>
                            </label>
                        @empty
                            <p class="text-gray-500">All accepted document types are already present.</p>
                        @endforelse
                    </div>
                </fieldset>
                <div>
                    <label for="relationship-reject-note" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-gray-600" x-text="decisionModal === 'changes' ? 'Correction instructions (required)' : 'Explanation (required for Other)'"></label>
                    <textarea id="relationship-reject-note" name="note" rows="4" maxlength="1000" :required="decisionModal === 'changes'" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100" placeholder="Explain the decision and any required corrections.">{{ old('note') }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100">
                <button type="button" @click="decisionModal = null" class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white rounded-lg bg-rose-600 hover:bg-rose-700" x-text="decisionModal === 'changes' ? 'Request changes' : 'Confirm final rejection'"></button>
            </div>
        </form>
    </div>

</div>
@endsection
