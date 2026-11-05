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
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.parent-verifications.index', ['type' => 'relationships']) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
            Back to Guardian & Dependent Verification
        </a>
        <div class="max-w-xl">
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
    </div>

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
        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-gray-500">Verification Requirements</h2>
        <dl class="grid gap-3 mt-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
            <div class="p-3 border border-gray-100 rounded-lg bg-gray-50">
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Guardian verification</dt>
                <dd class="mt-1 font-semibold text-gray-900">{{ $humanStatus($guardianStatus) }}</dd>
            </div>
            <div class="p-3 border border-gray-100 rounded-lg bg-gray-50">
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Dependent validation</dt>
                <dd class="mt-1 font-semibold text-gray-900">{{ $humanStatus($dependentStatus) }}</dd>
            </div>
            <div class="p-3 border border-gray-100 rounded-lg bg-gray-50">
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Relationship verification</dt>
                <dd class="mt-1 font-semibold text-gray-900">{{ $humanStatus($status) }}</dd>
            </div>
            <div class="p-3 border border-gray-100 rounded-lg bg-gray-50">
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Overall relationship</dt>
                <dd class="mt-1 font-semibold text-gray-900">{{ $humanStatus($relationshipStatus) }}</dd>
            </div>
        </dl>
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
                <a href="{{ route('admin.parent-verifications.parents.show', $relationship->parent) }}" class="inline-flex min-h-11 items-center rounded-lg border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500">View guardian information</a>
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
                    @if(data_get(config('guardian_identity.id_types'), $guardianPersonaAttempt->local_government_id_type.'.requires_back', false))
                        @include('admin.parent-verifications.partials.persona-image', [
                            'attempt' => $guardianPersonaAttempt,
                            'artifact' => 'government-id-back',
                            'label' => 'Back of guardian ID',
                            'compactPersonaSummary' => true,
                        ])
                    @endif
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
                                <a href="{{ $document['url'] }}" download class="text-xs font-semibold text-brand-700 hover:text-brand-900">Download</a>
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
                                class="px-3 py-2 text-sm font-semibold text-white rounded-lg bg-brand-600 hover:bg-brand-700"
                            >
                                Preview
                            </button>
                            <a href="{{ $documentUrl }}" download class="px-3 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Download
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

    <div x-show="previewDoc" x-cloak @keydown.escape.window="closePreview()" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70">
        <div class="absolute inset-0" @click="closePreview()"></div>
        <div class="relative z-10 flex h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="flex flex-col gap-3 px-4 py-3 border-b border-gray-200 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="font-semibold text-gray-900" x-text="previewDoc?.label"></h3>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="zoomIn()" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Zoom in</button>
                    <button type="button" @click="zoomOut()" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Zoom out</button>
                    <button type="button" @click="resetZoom()" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Reset zoom</button>
                    <button type="button" @click="fitToScreen()" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Fit to screen</button>
                    <a :href="previewDoc?.url" download class="px-3 py-2 text-sm font-semibold text-white rounded-lg bg-brand-600 hover:bg-brand-700">Download</a>
                    <button type="button" @click="closePreview()" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
                </div>
            </div>
            <div class="flex-1 p-4 overflow-auto bg-gray-100">
                <template x-if="previewDoc?.isImage">
                    <img :src="previewDoc.url" :alt="previewDoc.label" class="object-contain max-w-full max-h-full mx-auto origin-top" :style="fit ? '' : `transform: scale(${zoom});`">
                </template>
                <template x-if="previewDoc && !previewDoc.isImage">
                    <iframe :src="previewDoc.url" :title="previewDoc.label" class="mx-auto h-full min-h-[640px] w-full origin-top rounded-lg bg-white" :style="fit ? '' : `transform: scale(${zoom}); width: ${100 / zoom}%; height: ${100 / zoom}%;`"></iframe>
                </template>
            </div>
        </div>
    </div>

    <div x-show="decisionModal === 'approve'" x-cloak @keydown.escape.window="decisionModal = null" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50" @click="decisionModal = null"></div>
        <form method="POST" action="{{ route('admin.parent-verifications.relationships.approve', $relationship) }}" class="relative z-10 w-full max-w-lg overflow-hidden bg-white shadow-2xl rounded-xl">
            @csrf
            <input type="hidden" name="current_evidence_round" value="{{ $relationship->current_evidence_round }}">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Confirm approval</h3>
                <p class="mt-2 text-sm text-gray-600">Approve this guardian-dependent relationship verification as a single administrative decision.</p>
            </div>
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
