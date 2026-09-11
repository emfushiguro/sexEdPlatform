@extends('layouts.learner-app')

@section('title', 'Relationship Verification')

@section('content')
@php
    $status = $relationship->relationship_verified_status ?? 'pending';
    $statusClass = match ($status) {
        'verified', 'not_required' => 'bg-emerald-100 text-emerald-700',
        'rejected', 'revoked' => 'bg-rose-100 text-rose-700',
        'resubmission_required' => 'bg-orange-100 text-orange-700',
        default => 'bg-amber-100 text-amber-700',
    };
    $canSubmit = $requiresVerification && in_array($status, ['pending', 'resubmission_required'], true);
    $submittedRounds = $relationship->verificationDocuments->groupBy('submission_round');
    $oldDocuments = collect(old('documents', []))->filter('is_array')->map(static fn (array $document): array => [
        'documentType' => $document['document_type'] ?? '',
        'side' => $document['document_side'] ?? 'not_applicable',
        'pairingKey' => $document['pairing_key'] ?? null,
    ])->values()->all();
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-2xl p-6 text-white" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Relationship Verification</h1>
                <p class="text-white/80 text-sm mt-1">{{ $relationship->child?->name ?? 'Dependent' }} · {{ $relationship->relationshipLabel() }}</p>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-white/20 text-white">
                {{ $relationship->relationshipVerificationLabel() }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
            Please correct the highlighted evidence details before submitting.
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Relationship</h2>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $relationship->relationshipLabel() }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ $relationship->child?->name ?? 'Dependent' }}</p>
            <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                {{ $relationship->relationshipVerificationLabel() }}
            </span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Timeline</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-600">
                <p>Created: {{ $relationship->created_at?->format('M d, Y h:i A') }}</p>
                <p>Submitted: {{ $relationship->relationship_verification_submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</p>
                <p>Reviewed: {{ $relationship->relationship_verification_reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}</p>
            </div>
        </div>
    </div>

    @if($relationship->relationship_verification_rejection_reason)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800">
            <p class="font-semibold">Review reason</p>
            <p class="mt-1">{{ config('guardian_relationships.rejection_reasons.' . $relationship->relationship_verification_rejection_reason, $relationship->relationship_verification_rejection_reason) }}</p>
            @if($relationship->relationship_verification_rejection_note)
                <p class="mt-2">{{ $relationship->relationship_verification_rejection_note }}</p>
            @endif
        </div>
    @endif

    @if($requiresVerification)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">Submitted Evidence</h2>
                <span class="text-xs text-gray-500">{{ $relationship->verificationDocuments->count() }} file(s)</span>
            </div>
            <div class="mt-3 space-y-4">
                @forelse($submittedRounds as $round => $documents)
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Submission round {{ $round }}</p>
                        <div class="mt-2 space-y-2">
                            @foreach($documents as $document)
                                @php
                                    $documentUrl = route('parent.relationship-verifications.documents.show', [$relationship, $document]);
                                    $inlineDocumentUrl = $documentUrl.'?inline=1';
                                    $documentTypeLabel = (string) config('guardian_relationships.document_types.' . $document->document_type, $document->document_type);
                                    $documentSideLabel = $document->document_side === 'not_applicable'
                                        ? 'Not applicable'
                                        : ucfirst(str_replace('_', ' ', (string) $document->document_side));
                                    $isImage = str_starts_with((string) $document->mime_type, 'image/');
                                    $isPdf = (string) $document->mime_type === 'application/pdf';
                                @endphp
                                <article class="overflow-hidden rounded-xl border border-gray-200 bg-white" data-testid="submitted-evidence-card">
                                    <div class="flex flex-col gap-2 border-b border-gray-100 px-3 py-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-900">{{ $document->original_name }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $documentTypeLabel }} · {{ $documentSideLabel }}</p>
                                        </div>
                                        <a href="{{ $documentUrl }}" download class="shrink-0 text-xs font-semibold text-purple-700 hover:text-purple-900">Download</a>
                                    </div>
                                    <div class="bg-gray-50 p-3">
                                        @if($isImage)
                                            <a href="{{ $inlineDocumentUrl }}" target="_blank" rel="noopener" class="block" aria-label="Preview {{ $documentTypeLabel }} {{ $documentSideLabel }}">
                                                <img src="{{ $inlineDocumentUrl }}" alt="{{ $documentTypeLabel }} {{ $documentSideLabel }} evidence preview" data-testid="submitted-evidence-preview" class="h-56 w-full rounded-lg border border-gray-200 bg-white object-contain">
                                            </a>
                                        @elseif($isPdf)
                                            <iframe src="{{ $inlineDocumentUrl }}#toolbar=0&amp;navpanes=0" title="{{ $documentTypeLabel }} {{ $documentSideLabel }} evidence preview" data-testid="submitted-evidence-preview" class="h-56 w-full rounded-lg border border-gray-200 bg-white"></iframe>
                                        @else
                                            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-8 text-center text-sm text-gray-500">
                                                Inline preview is not available for this file type.
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No evidence submitted yet.</p>
                @endforelse
            </div>
        </div>

        @if($canSubmit)
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                x-data="guardianEvidenceForm({
                    documentTypes: @js($documentTypes),
                    requiredDocumentTypes: @js($requiredDocumentTypes),
                    initialRows: @js($oldDocuments),
                    maxRows: 10,
                })"
                x-init="init()"
                x-on:beforeunload.window="destroy()"
            >
                <h2 class="text-lg font-semibold text-gray-900">Administrative verification</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $pathwayLabel }}. Upload the documents that support this specific Guardian-Dependent relationship. A reviewer will assess the submission.</p>

                <div class="mt-4 rounded-xl border border-purple-100 bg-purple-50 p-4 text-sm text-purple-900">
                    <p class="font-semibold">Evidence guidance</p>
                    <p class="mt-1">Required core document: <span x-text="requiredDocumentTypes.map(type => documentTypes[type] || type).join(', ')"></span>.</p>
                    <p class="mt-1">You may add up to 10 PDF, JPEG, PNG, or WebP files. Additional context is {{ $requiresCircumstances ? 'required' : 'optional' }} for this pathway.</p>
                </div>

                <form method="POST" action="{{ route('parent.relationship-verifications.store', $relationship) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <div class="space-y-4">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <div class="rounded-xl border border-gray-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-900">Document <span x-text="index + 1"></span></p>
                                    <button type="button" class="text-xs font-semibold text-rose-600 disabled:cursor-not-allowed disabled:text-gray-400" :disabled="rows.length === 1" @click="removeRow(index)">Remove</button>
                                </div>

                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" :for="`document-type-${row.id}`">Document type</label>
                                        <select :id="`document-type-${row.id}`" :name="`documents[${index}][document_type]`" x-model="row.documentType" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                            <option value="">Select document type</option>
                                            <template x-for="(label, value) in documentTypes" :key="value">
                                                <option :value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" :for="`document-side-${row.id}`">Document side</label>
                                        <select :id="`document-side-${row.id}`" :name="`documents[${index}][document_side]`" x-model="row.side" @change="sideChanged(index)" required class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                            <option value="not_applicable">Not applicable</option>
                                            <option value="front">Front</option>
                                            <option value="back">Back</option>
                                        </select>
                                    </div>
                                </div>

                                <input :name="`documents[${index}][pairing_key]`" type="hidden" :value="row.pairingKey">
                                <div class="mt-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1" :for="`document-file-${row.id}`">File</label>
                                    <input :id="`document-file-${row.id}`" :name="`documents[${index}][file]`" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required @change="fileChanged(index, $event)" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                    <p class="mt-1 text-xs text-gray-500" x-text="row.fileName || 'PDF, JPEG, PNG, or WebP · maximum 5 MB'"></p>
                                    <template x-if="row.previewUrl && row.previewKind === 'image'">
                                        <img :src="row.previewUrl" alt="Selected evidence preview" class="mt-3 max-h-48 rounded-lg border border-gray-200 object-contain">
                                    </template>
                                    <template x-if="row.previewUrl && row.previewKind === 'pdf'">
                                        <iframe :src="row.previewUrl" title="Selected PDF evidence preview" class="mt-3 h-48 w-full rounded-lg border border-gray-200"></iframe>
                                    </template>
                                </div>

                                <button type="button" class="mt-3 text-sm font-semibold text-purple-700 disabled:cursor-not-allowed disabled:text-gray-400" :disabled="!row.documentType || row.pairingKey || rows.length >= maxRows" @click="addBackSide(index)">Add back side</button>
                            </div>
                        </template>
                    </div>

                    <button type="button" class="rounded-xl border border-purple-200 px-4 py-2 text-sm font-semibold text-purple-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="rows.length >= maxRows" @click="addRow()">Add another document</button>

                    <div>
                        <label for="relationship_notes" class="block text-sm font-medium text-gray-700 mb-1">Context {{ $requiresCircumstances ? '(required)' : '(optional)' }}</label>
                        <textarea id="relationship_notes" name="relationship_notes" rows="4" maxlength="1000" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm" placeholder="Share any context that helps the reviewer understand the relationship.">{{ old('relationship_notes') }}</textarea>
                        @error('relationship_notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    @error('documents')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @foreach($errors->getMessages() as $field => $messages)
                        @if(str_starts_with($field, 'documents.') && is_array($messages))
                            @foreach($messages as $message)
                                <p class="text-xs text-red-600">{{ str($field)->replace('.', ' · ')->toString() }}: {{ $message }}</p>
                            @endforeach
                        @endif
                    @endforeach

                    <label class="flex gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="confirm_submission" value="1" required class="mt-1 rounded border-gray-300" @checked(old('confirm_submission'))>
                        I confirm these documents support this specific Guardian-Dependent relationship.
                    </label>
                    @error('confirm_submission')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                    <button class="rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Submit for Verification
                    </button>
                </form>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    function guardianEvidenceForm(config) {
        return {
            documentTypes: config.documentTypes,
            requiredDocumentTypes: config.requiredDocumentTypes,
            initialRows: config.initialRows || [],
            maxRows: config.maxRows,
            rows: [],

            init() {
                if (this.initialRows.length > 0) {
                    this.rows = this.initialRows.map(row => ({
                        id: crypto.randomUUID(),
                        documentType: row.documentType || '',
                        side: row.side || 'not_applicable',
                        pairingKey: row.pairingKey || null,
                        previewUrl: null,
                        previewKind: null,
                        fileName: null,
                    }));
                } else {
                    this.addRow();
                }
            },

            addRow() {
                if (this.rows.length >= this.maxRows) return;
                this.rows.push({
                    id: crypto.randomUUID(),
                    documentType: '',
                    side: 'not_applicable',
                    pairingKey: null,
                    previewUrl: null,
                    previewKind: null,
                    fileName: null,
                });
            },

            addBackSide(index) {
                const row = this.rows[index];
                if (!row || row.pairingKey || !row.documentType || this.rows.length >= this.maxRows) return;

                const pairingKey = crypto.randomUUID();
                row.side = 'front';
                row.pairingKey = pairingKey;
                this.rows.splice(index + 1, 0, {
                    id: crypto.randomUUID(),
                    documentType: row.documentType,
                    side: 'back',
                    pairingKey,
                    previewUrl: null,
                    previewKind: null,
                    fileName: null,
                });
            },

            sideChanged(index) {
                const row = this.rows[index];
                if (!row || row.side === 'not_applicable') {
                    if (row) row.pairingKey = null;
                    return;
                }

                const duplicate = this.rows.some((other, otherIndex) => otherIndex !== index && other.pairingKey === row.pairingKey && other.side === row.side);
                if (row.pairingKey && duplicate) row.side = 'not_applicable';
            },

            fileChanged(index, event) {
                const row = this.rows[index];
                const file = event.target.files?.[0];
                if (!row || !file) return;

                this.revoke(row);
                row.previewUrl = URL.createObjectURL(file);
                row.previewKind = file.type === 'application/pdf' ? 'pdf' : 'image';
                row.fileName = file.name;
            },

            removeRow(index) {
                if (this.rows.length === 1) return;
                const [row] = this.rows.splice(index, 1);
                this.revoke(row);
            },

            revoke(row) {
                if (row?.previewUrl) URL.revokeObjectURL(row.previewUrl);
                if (row) row.previewUrl = null;
            },

            destroy() {
                this.rows.forEach(row => this.revoke(row));
            },
        };
    }
</script>
@endpush
@endsection
