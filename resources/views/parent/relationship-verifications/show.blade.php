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
    $correctionRequest = $status === 'resubmission_required' ? $relationship->relationship_correction_request : null;
    $correctionRows = $correctionRequest ? $relationship->verificationDocuments
        ->where('submission_round', $relationship->current_evidence_round)
        ->whereIn('id', $correctionRequest['document_ids'])
        ->map(fn ($document) => [
            'documentType' => $document->document_type,
            'side' => $document->document_side,
            'pairingKey' => $document->pairing_key,
            'replacementDocumentId' => $document->id,
        ])->values()->merge(collect($correctionRequest['document_types'])->map(fn ($type) => [
            'documentType' => $type, 'side' => 'not_applicable', 'pairingKey' => null,
            'replacementDocumentId' => null,
        ]))->all() : [];
    $oldDocuments = collect(old('documents', []))->filter('is_array')->map(static fn (array $document): array => [
        'documentType' => $document['document_type'] ?? '',
        'side' => $document['document_side'] ?? 'not_applicable',
        'pairingKey' => $document['pairing_key'] ?? null,
    ])->values()->all();
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <div class="p-6 text-white rounded-2xl" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Relationship Verification</h1>
                <p class="mt-1 text-sm text-white/80">{{ $relationship->child?->name ?? 'Dependent' }} · {{ $relationship->relationshipLabel() }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-white rounded-full bg-white/20">
                {{ $relationship->relationshipVerificationLabel() }}
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 text-sm text-green-800 border border-green-200 rounded-xl bg-green-50">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="px-4 py-3 text-sm border rounded-xl border-rose-200 bg-rose-50 text-rose-800" role="alert">
            Please correct the highlighted evidence details before submitting.
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <h2 class="text-sm font-semibold tracking-wide text-gray-500 uppercase">Relationship</h2>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $relationship->relationshipLabel() }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ $relationship->child?->name ?? 'Dependent' }}</p>
            <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                {{ $relationship->relationshipVerificationLabel() }}
            </span>
        </div>

        <div class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <h2 class="text-sm font-semibold tracking-wide text-gray-500 uppercase">Timeline</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-600">
                <p>Created: {{ $relationship->created_at?->format('M d, Y h:i A') }}</p>
                <p>Submitted: {{ $relationship->relationship_verification_submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</p>
                <p>Reviewed: {{ $relationship->relationship_verification_reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}</p>
            </div>
        </div>
    </div>

    @if($relationship->relationship_verification_rejection_reason)
        <div class="p-5 text-sm border rounded-2xl border-rose-200 bg-rose-50 text-rose-800">
            <p class="font-semibold">Review reason</p>
            <p class="mt-1">{{ config('guardian_relationships.rejection_reasons.' . $relationship->relationship_verification_rejection_reason, $relationship->relationship_verification_rejection_reason) }}</p>
            @if($relationship->relationship_verification_rejection_note)
                <p class="mt-2">{{ $relationship->relationship_verification_rejection_note }}</p>
            @endif
        </div>
    @endif

    @if($requiresVerification)
        <div class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">Submitted Evidence</h2>
                <span class="text-xs text-gray-500">{{ $relationship->verificationDocuments->count() }} file(s)</span>
            </div>
            <div class="mt-3 space-y-4">
                @forelse($submittedRounds as $round => $documents)
                    <div class="p-4 border border-gray-100 rounded-xl bg-gray-50">
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
                                <article class="overflow-hidden bg-white border border-gray-200 rounded-xl" data-testid="submitted-evidence-card">
                                    <div class="flex flex-col gap-2 px-3 py-3 border-b border-gray-100 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $document->original_name }}</p>
                                            @if($document->review_status)
                                                <p class="mt-1 text-xs font-semibold text-gray-700">{{ match ($document->review_status) {
                                                    'accepted' => 'No changes needed',
                                                    'changes_requested' => 'Changes requested',
                                                    'rejected' => 'Rejected',
                                                    default => 'Pending review',
                                                } }}</p>
                                            @endif
                                            <p class="mt-1 text-xs text-gray-500">{{ $documentTypeLabel }} · {{ $documentSideLabel }}</p>
                                        </div>
                                        <a href="{{ $documentUrl }}" download class="text-xs font-semibold text-purple-700 shrink-0 hover:text-purple-900">Download</a>
                                    </div>
                                    <div class="p-3 bg-gray-50">
                                        @if($isImage)
                                            <a href="{{ $inlineDocumentUrl }}" target="_blank" rel="noopener" class="block" aria-label="Preview {{ $documentTypeLabel }} {{ $documentSideLabel }}">
                                                <img src="{{ $inlineDocumentUrl }}" alt="{{ $documentTypeLabel }} {{ $documentSideLabel }} evidence preview" data-testid="submitted-evidence-preview" class="object-contain w-full h-56 bg-white border border-gray-200 rounded-lg">
                                            </a>
                                        @elseif($isPdf)
                                            <iframe src="{{ $inlineDocumentUrl }}#toolbar=0&amp;navpanes=0" title="{{ $documentTypeLabel }} {{ $documentSideLabel }} evidence preview" data-testid="submitted-evidence-preview" class="w-full h-56 bg-white border border-gray-200 rounded-lg"></iframe>
                                        @else
                                            <div class="px-3 py-8 text-sm text-center text-gray-500 bg-white border border-gray-300 border-dashed rounded-lg">
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
                class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl"
                x-data="guardianEvidenceForm({
                    documentTypes: @js($documentTypes),
                    requiredDocumentTypes: @js($requiredDocumentTypes),
                    initialRows: @js($correctionRequest ? $correctionRows : $oldDocuments),
                    savedDrafts: @js($savedDrafts),
                    draftUrl: @js($correctionRequest ? route('parent.relationship-verifications.draft.store', $relationship) : null),
                    evidenceRound: @js($relationship->current_evidence_round),
                    maxRows: 10,
                })"
                x-on:beforeunload.window="destroy()"
            >
                <h2 class="text-lg font-semibold text-gray-900">{{ $correctionRequest ? 'Requested corrections' : 'Administrative verification' }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $correctionRequest ? 'Replace each requested file and add the missing documents listed below. Evidence marked No changes needed is kept automatically.' : $pathwayLabel . '. Upload the documents that support this specific Guardian-Dependent relationship. A reviewer will assess the submission.' }}</p>

                <form method="POST" action="{{ route('parent.relationship-verifications.store', $relationship) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="current_evidence_round" value="{{ $relationship->current_evidence_round }}">
                    @error('current_evidence_round')<p class="text-sm text-red-600" role="alert">{{ $message }}</p>@enderror

                    <div class="space-y-4">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <div class="p-4 bg-white border border-gray-200 rounded-xl sm:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Document <span x-text="index + 1"></span></p>
                                    @if($correctionRequest)
                                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.error ? 'bg-red-50 text-red-700' : (row.savedName && !row.file ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800')" role="status" x-text="row.saving ? 'Saving…' : (row.error ? 'Needs attention' : (row.file ? 'Ready to save' : (row.savedName ? 'Saved' : 'File needed')))"></span>
                                    @endif
                                    @unless($correctionRequest)
                                        <button type="button" class="text-xs font-semibold text-rose-600 disabled:cursor-not-allowed disabled:text-gray-400" :disabled="rows.length === 1" @click="removeRow(index)">Remove</button>
                                    @endunless
                                </div>

                                @if($correctionRequest)
                                    <h3 class="mt-2 text-base font-semibold leading-6 text-gray-900 break-words" x-text="(documentTypes[row.documentType] || row.documentType) + (row.side === 'not_applicable' ? '' : ' - ' + row.side)"></h3>
                                    <p class="mt-1 text-sm text-gray-500" x-text="row.replacementDocumentId ? 'Upload a replacement for the requested document.' : 'Upload the missing document requested by your reviewer.'"></p>
                                @else
                                <div class="grid gap-4 mt-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700" :for="`document-type-${row.id}`">Document type</label>
                                        <select :id="`document-type-${row.id}`" :name="`documents[${index}][document_type]`" x-model="row.documentType" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50">
                                            <option value="">Select document type</option>
                                            <template x-for="(label, value) in documentTypes" :key="value">
                                                <option :value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700" :for="`document-side-${row.id}`">Document side</label>
                                        <select :id="`document-side-${row.id}`" :name="`documents[${index}][document_side]`" x-model="row.side" @change="sideChanged(index)" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50">
                                            <option value="not_applicable">Not applicable</option>
                                            <option value="front">Front</option>
                                            <option value="back">Back</option>
                                        </select>
                                    </div>
                                </div>

                                @endif
                                <input :name="draftUrl ? null : `documents[${index}][pairing_key]`" type="hidden" :value="row.pairingKey">
                                <div class="mt-3">
                                    <div class="p-4 border border-purple-200 border-dashed rounded-xl bg-purple-50/40">
                                        <label class="block mb-2 text-sm font-semibold text-gray-800" :for="`document-file-${row.id}`">{{ $correctionRequest ? 'Choose a replacement file' : 'Choose a file' }}</label>
                                        <input :id="`document-file-${row.id}`" :aria-describedby="`document-file-help-${row.id}`" :name="draftUrl ? null : `documents[${index}][file]`" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" :required="!draftUrl" :disabled="row.saving" @change="fileChanged(index, $event)" class="block w-full min-w-0 rounded-lg text-sm text-gray-600 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-purple-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-purple-900 hover:file:bg-purple-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                                        <p :id="`document-file-help-${row.id}`" class="mt-2 text-xs leading-5 text-gray-500">PDF, JPEG, PNG, or WebP · maximum 5 MB</p>
                                    </div>
                                    @if($correctionRequest)
                                        <div class="flex flex-col gap-3 mt-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0 text-sm" aria-live="polite">
                                                <p class="break-words text-emerald-700" x-show="row.savedName && !row.file" x-text="'Saved: ' + row.savedName"></p>
                                                <p class="font-medium text-gray-800 break-words" x-show="row.file" x-text="row.fileName"></p>
                                                <p class="mt-1 text-xs text-amber-800" x-show="row.file && !row.saving">Review the preview, then save this replacement.</p>
                                                <p class="text-xs text-gray-500" x-show="!row.file && !row.savedName">Choose a file to enable saving.</p>
                                            </div>
                                            <button type="button" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500" :disabled="!row.file || row.saving" :aria-busy="row.saving" @click="saveReplacement(index, $el.closest('form'))">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                <span x-text="row.saving ? 'Saving…' : 'Save replacement'"></span>
                                            </button>
                                        </div>
                                        <p class="px-3 py-2 mt-3 text-sm text-red-700 rounded-lg bg-red-50" role="alert" x-show="row.error" x-text="row.error"></p>
                                    @else
                                        <p class="mt-2 text-xs text-gray-600 break-words" x-show="row.fileName" x-text="row.fileName"></p>
                                    @endif
                                    <template x-if="row.previewUrl && row.previewKind === 'image'">
                                        <img :src="row.previewUrl" alt="Selected evidence preview" class="object-contain mt-3 border border-gray-200 rounded-lg max-h-48">
                                    </template>
                                    <template x-if="row.previewUrl && row.previewKind === 'pdf'">
                                        <iframe :src="row.previewUrl" title="Selected PDF evidence preview" class="w-full h-48 mt-3 border border-gray-200 rounded-lg"></iframe>
                                    </template>
                                </div>

                                @unless($correctionRequest)
                                    <button type="button" class="mt-3 text-sm font-semibold text-purple-700 disabled:cursor-not-allowed disabled:text-gray-400" :disabled="!row.documentType || row.pairingKey || rows.length >= maxRows" @click="addBackSide(index)">Add back side</button>
                                @endunless
                            </div>
                        </template>
                    </div>

                    @unless($correctionRequest)
                        <button type="button" class="px-4 py-2 text-sm font-semibold text-purple-700 border border-purple-200 rounded-xl disabled:cursor-not-allowed disabled:opacity-50" :disabled="rows.length >= maxRows" @click="addRow()">Add another document</button>
                    @endunless

                    <div>
                        <label for="relationship_notes" class="block mb-1 text-sm font-medium text-gray-700">Context {{ $requiresCircumstances ? '(required)' : '(optional)' }}</label>
                        <textarea id="relationship_notes" name="relationship_notes" rows="4" maxlength="1000" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-xl bg-gray-50" placeholder="Share any context that helps the reviewer understand the relationship.">{{ old('relationship_notes', $relationship->relationship_notes) }}</textarea>
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
                        <input type="checkbox" name="confirm_submission" value="1" required class="mt-1 border-gray-300 rounded" @checked(old('confirm_submission'))>
                        I confirm these documents support this specific Guardian-Dependent relationship.
                    </label>
                    @error('confirm_submission')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                    <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl disabled:cursor-not-allowed disabled:opacity-50" :disabled="draftUrl && rows.some(row => !row.savedName || row.file || row.saving)" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        {{ $correctionRequest ? 'Submit all corrections' : 'Submit for Verification' }}
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
            draftUrl: config.draftUrl,
            rows: [],

            init() {
                if (this.initialRows.length > 0) {
                    this.rows = this.initialRows.map(row => ({
                        id: crypto.randomUUID(),
                        documentType: row.documentType || '',
                        side: row.side || 'not_applicable',
                        pairingKey: row.pairingKey || null,
                        replacementDocumentId: row.replacementDocumentId || null,
                        savedName: config.savedDrafts[row.replacementDocumentId ? 'document:' + row.replacementDocumentId : 'type:' + row.documentType]?.name || null,
                        file: null,
                        saving: false,
                        error: null,
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
                if (!row) return;

                this.revoke(row);
                row.file = file || null;
                row.error = null;
                row.fileName = file?.name || null;
                if (!file) return;
                row.previewUrl = URL.createObjectURL(file);
                row.previewKind = file.type === 'application/pdf' ? 'pdf' : 'image';
                row.fileName = file.name;
            },

            async saveReplacement(index, form) {
                const row = this.rows[index];
                if (!row?.file || row.saving) return;
                row.saving = true;
                row.error = null;
                const body = new FormData();
                body.append('_token', form.elements._token.value);
                body.append('current_evidence_round', config.evidenceRound);
                body.append('slot', row.replacementDocumentId ? 'document:' + row.replacementDocumentId : 'type:' + row.documentType);
                body.append('file', row.file);
                try {
                    const response = await fetch(this.draftUrl, { method: 'POST', headers: { Accept: 'application/json' }, body });
                    const result = await response.json();
                    if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || 'Unable to save this replacement. Reload and try again.');
                    row.savedName = result.name;
                    row.file = null;
                    document.getElementById('document-file-' + row.id).value = '';
                } catch (error) {
                    row.error = error.message || 'Unable to save this replacement. Try again.';
                } finally {
                    row.saving = false;
                }
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
