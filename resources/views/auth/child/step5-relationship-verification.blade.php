<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex flex-col items-center justify-center h-full p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="w-auto h-20 mx-auto mb-3">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Relationship review</h2>
            <p class="max-w-xs text-lg text-white/80">Submit relationship evidence for administrative review.</p>
        </div>
    </x-slot>

    <x-wizard-stepper flow="dependent" />

    <div class="p-5 mb-6 border border-purple-100 rounded-2xl bg-purple-50/60">
        <p class="text-xs font-semibold tracking-wide text-purple-600 uppercase">Dependent setup</p>
        <h1 class="mt-1 text-2xl font-bold text-purple-950">Guardian relationship review</h1>
        <p class="mt-2 text-sm text-gray-700">{{ $pathwayLabel }}</p>
        <details class="mt-3 text-sm text-gray-700"><summary class="cursor-pointer font-semibold text-purple-800">What does the reviewer check?</summary><p class="mt-2">Include the dependent's PSA birth certificate or equivalent civil registry record. The reviewer compares their name and birthdate with the registration details, and the named parent or guardian with your verified identity and relationship evidence. A birth record naming you as a biological parent can also support that relationship; other relationship types require their own authority documents.</p></details>
    </div>

    <x-guardian-relationship-guidance :relationship-type="$step1['relationship_type']" class="mb-5" />

    @if ($errors->any())
        <div class="p-4 mb-6 border-l-4 border-red-500 rounded-lg bg-red-50" role="alert">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('parent.create-child.relationship-verification.store') }}" enctype="multipart/form-data" class="space-y-5"
          x-data="guardianEvidenceForm({
              documentTypes: @js(array_diff_key($relationshipDocumentTypes, ['dependent_birth_record' => true])),
              requiredDocumentTypes: @js($requiredDocumentTypes),
              maxRows: 9,
          })" x-on:beforeunload.window="destroy()">
        @csrf

        <section class="rounded-2xl border border-purple-200 bg-purple-50/50 p-5">
            <h2 class="text-lg font-bold text-purple-950">Dependent's birth record</h2>
            <details class="mt-2 text-sm text-gray-700"><summary class="cursor-pointer font-semibold text-purple-800">Why is a birth record needed?</summary><p class="mt-2">Upload the dependent's PSA birth certificate or equivalent civil registry record. An administrator uses it for the initial relationship review, comparing the dependent's details with the other evidence. It is stored privately.</p></details>
            <input type="hidden" name="documents[0][document_type]" value="dependent_birth_record">
            <input type="hidden" name="documents[0][document_side]" value="not_applicable">
            <label for="dependent-birth-record" class="mt-4 block text-sm font-semibold text-gray-800">PSA or equivalent record</label>
            <input id="dependent-birth-record" @change="birthRecordChanged($event)" name="documents[0][file]" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="mt-2 block w-full rounded-xl border border-dashed border-purple-300 bg-white p-4 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-purple-700 file:px-4 file:py-2 file:font-semibold file:text-white">
            <p class="mt-2 text-xs text-gray-600">PDF, JPEG, PNG or WebP, up to 5 MB.</p>
            <div x-show="birthRecord.fileName" x-cloak class="mt-3 flex items-center gap-3 rounded-xl border border-purple-100 bg-white p-3">
                <template x-if="birthRecord.previewUrl"><img :src="birthRecord.previewUrl" alt="Selected birth record preview" class="h-16 w-16 shrink-0 rounded-lg object-contain"></template>
                <span class="min-w-0 truncate text-sm text-gray-700" x-text="birthRecord.fileName"></span>
            </div>
        </section>
        <h2 class="text-lg font-bold text-purple-950">Additional relationship evidence</h2>
        <p class="text-sm text-gray-600">Add the documents required for your relationship. If the birth record itself establishes parentage, no duplicate upload is needed.</p>
        <div class="overflow-hidden bg-white border border-purple-200 shadow-sm rounded-2xl">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                </div>

            <div class="p-5 space-y-4 sm:p-6" data-testid="relationship-evidence-upload-cards">
                <template x-for="(row, index) in rows" :key="row.id">
                    <div class="p-4 transition border border-gray-200 rounded-2xl bg-gray-50/55 hover:border-purple-200 hover:shadow-sm sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start min-w-0 gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold text-purple-700 bg-purple-100 shrink-0 rounded-xl" x-text="index + 1"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">Document <span x-text="index + 1"></span></p>
                                    <p class="mt-0.5 text-xs leading-relaxed text-gray-500" x-text="row.pairingKey ? 'Front and back sides are paired for this document.' : 'Upload one file, or add a back side when the document has two sides.'"></p>
                                </div>
                            </div>
                            <button type="button" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:text-gray-400 disabled:hover:bg-transparent" :disabled="false" @click="removeRow(index)" :aria-label="`Remove document ${index + 1}`">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h12" />
                                </svg>
                                <span>Remove</span>
                            </button>
                        </div>

                        <div class="grid gap-4 mt-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600" :for="`document-type-${row.id}`">Document type</label>
                                <select :id="`document-type-${row.id}`" :name="`documents[${index + 1}][document_type]`" x-model="row.documentType" required class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-100">
                                    <option value="">Select document type</option>
                                    <template x-for="(label, value) in documentTypes" :key="value">
                                        <option :value="value" x-text="label"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600" :for="`document-side-${row.id}`">Document side</label>
                                <select :id="`document-side-${row.id}`" :name="`documents[${index + 1}][document_side]`" x-model="row.side" @change="sideChanged(index)" required class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-100">
                                    <option value="not_applicable">Not applicable</option>
                                    <option value="front">Front</option>
                                    <option value="back">Back</option>
                                </select>
                            </div>
                        </div>

                        <input :name="`documents[${index + 1}][pairing_key]`" type="hidden" :value="row.pairingKey">
                        <div class="mt-4">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600" :for="`document-file-${row.id}`">Attachment</label>
                                <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-rose-500">Required</span>
                            </div>

                            <div data-testid="relationship-evidence-dropzone"
                                 class="p-3 transition border-2 border-purple-200 border-dashed rounded-2xl bg-purple-50/35 focus-within:ring-4 focus-within:ring-purple-100"
                                 :class="row.isDragging ? 'border-purple-500 bg-purple-100/70 ring-4 ring-purple-100' : 'hover:border-purple-400 hover:bg-purple-50/70'"
                                 @dragover.prevent="row.isDragging = true"
                                 @dragleave.prevent="row.isDragging = false"
                                 @drop.prevent="handleDrop(index, $event)">
                                <label class="block outline-none cursor-pointer group rounded-xl" :for="`document-file-${row.id}`">
                                    <input :id="`document-file-${row.id}`" :name="`documents[${index + 1}][file]`" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required @change="fileChanged(index, $event)" class="sr-only">

                                    <div x-show="!row.fileName" class="flex flex-col items-center justify-center px-4 py-6 text-center">
                                        <span class="inline-flex items-center justify-center text-purple-600 transition bg-white shadow-sm h-11 w-11 rounded-2xl ring-1 ring-purple-100 group-hover:scale-105 group-hover:bg-purple-100">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0L8 8m4-4l4 4M5 16.5v1.25A2.25 2.25 0 007.25 20h9.5A2.25 2.25 0 0019 17.75V16.5" />
                                            </svg>
                                        </span>
                                        <p class="mt-3 text-sm font-semibold text-gray-900">Drop a file here or <span class="text-purple-700 underline decoration-purple-300 underline-offset-2">browse</span></p>
                                        <p class="mt-1 text-xs text-gray-500">Upload a clear PDF, JPEG, PNG, or WebP file</p>
                                    </div>

                                    <div x-show="row.fileName" x-cloak class="flex items-center gap-3 rounded-xl bg-white px-3.5 py-3 text-left shadow-sm ring-1 ring-purple-100">
                                        <span class="inline-flex items-center justify-center w-10 h-10 text-purple-700 bg-purple-100 shrink-0 rounded-xl">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </span>
                                        <span class="flex-1 min-w-0">
                                            <span class="block text-sm font-semibold text-gray-900 truncate" x-text="row.fileName"></span>
                                            <span class="mt-0.5 block text-xs text-gray-500"><span x-text="row.fileTypeLabel"></span><span aria-hidden="true"> · </span><span x-text="row.fileSizeLabel"></span></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 shrink-0">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Replace
                                        </span>
                                    </div>
                                </label>
                            </div>

                            <div x-show="row.fileName" x-cloak class="mt-3 overflow-hidden bg-white border border-gray-200 rounded-2xl" data-testid="relationship-evidence-preview">
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-3.5 py-2.5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500">Selected preview</p>
                                    <button type="button" data-testid="relationship-evidence-clear" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold transition rounded-lg text-rose-600 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-200" @click.stop="clearFile(index)">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Clear file
                                    </button>
                                </div>
                                <template x-if="row.previewUrl && row.previewKind === 'image'"><img :src="row.previewUrl" alt="Selected evidence preview" class="object-contain w-16 h-16 m-3 rounded-lg bg-gray-50"></template>
                                <template x-if="row.previewKind === 'pdf'"><p class="p-3 text-xs text-gray-600">PDF selected. Replace the attachment above to change it.</p></template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/60 sm:px-6">
                <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-purple-200 bg-white px-4 py-2.5 text-sm font-semibold text-purple-700 shadow-sm transition hover:border-purple-300 hover:bg-purple-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" :disabled="rows.length >= maxRows" @click="addRow()">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14" />
                    </svg>
                    Add relationship document
                </button>
                <p class="mt-2 text-xs text-gray-500">You can submit up to <span x-text="maxRows"></span> attachments.</p>
            </div>

            <div class="mx-0.5 mt-6 px-5 py-4 border-t border-gray-100 bg-gray-50/60 sm:px-6">
                <label for="relationship_notes" class="block text-sm font-medium text-gray-700">Context (optional)</label>
                <textarea id="relationship_notes" name="relationship_notes" rows="4" maxlength="1000" class="w-full px-3 py-2 mt-1 text-sm border border-gray-200 rounded-xl bg-gray-50" placeholder="Share context for the reviewer.">{{ old('relationship_notes') }}</textarea>
            </div>

            <label class="flex items-start gap-2 p-3 text-xs border rounded-xl border-amber-200 bg-amber-50 text-amber-900 mx-0.5 sm:mx-6">
                <input type="checkbox" name="confirm_submission" value="1" required class="mt-0.5 rounded border-amber-300" @checked(old('confirm_submission'))>
                <span>I confirm these documents support the requested Guardian–Dependent relationship.</span>
            </label>

            <p class="p-3 text-xs text-purple-900">After relationship approval, a minor's Guardian authorizes registration. An adult may consent personally or use separately reviewed representative authority.</p>
        </div>

        <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-200">
            <a href="{{ route('parent.create-child.credentials') }}" class="text-sm text-gray-500 hover:text-gray-700">Back</a>
            <button type="submit" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" class="inline-flex items-center justify-center px-8 py-3.5 font-semibold text-white rounded-xl shadow-md hover:opacity-90">Submit Request</button>
        </div>
    </form>
</x-auth-split-layout>

<script>
    function guardianEvidenceForm(config) {
        return {
            documentTypes: config.documentTypes,
            requiredDocumentTypes: config.requiredDocumentTypes,
            maxRows: config.maxRows,
            rows: [],
            birthRecord: { fileName: null, previewUrl: null },
            init() {},
            birthRecordChanged(event) {
                this.revoke(this.birthRecord);
                const file = event.target.files?.[0];
                this.birthRecord.fileName = file?.name || null;
                if (file?.type.startsWith('image/')) this.birthRecord.previewUrl = URL.createObjectURL(file);
            },
            addRow() {
                if (this.rows.length >= this.maxRows) return;
                this.rows.push({ id: crypto.randomUUID(), documentType: '', side: 'not_applicable', pairingKey: null, previewUrl: null, previewKind: null, fileName: null, fileSizeLabel: null, fileTypeLabel: null, isDragging: false });
            },
            addBackSide(index) {
                const row = this.rows[index];
                if (!row || row.pairingKey || !row.documentType || this.rows.length >= this.maxRows) return;
                const pairingKey = crypto.randomUUID();
                row.side = 'front';
                row.pairingKey = pairingKey;
                this.rows.splice(index + 1, 0, { id: crypto.randomUUID(), documentType: row.documentType, side: 'back', pairingKey, previewUrl: null, previewKind: null, fileName: null, fileSizeLabel: null, fileTypeLabel: null, isDragging: false });
            },
            sideChanged(index) {
                const row = this.rows[index];
                if (!row || row.side === 'not_applicable') { if (row) row.pairingKey = null; return; }
                if (row.pairingKey && this.rows.some((other, otherIndex) => otherIndex !== index && other.pairingKey === row.pairingKey && other.side === row.side)) row.side = 'not_applicable';
            },
            fileChanged(index, event) {
                const row = this.rows[index];
                const file = event.target.files?.[0];
                if (!row || !file) return;
                this.revoke(row);
                row.previewUrl = URL.createObjectURL(file);
                row.previewKind = file.type === 'application/pdf' ? 'pdf' : 'image';
                row.fileName = file.name;
                row.fileSizeLabel = this.formatFileSize(file.size);
                row.fileTypeLabel = file.type === 'application/pdf' ? 'PDF document' : 'Image document';
                row.isDragging = false;
            },
            handleDrop(index, event) {
                const row = this.rows[index];
                if (row) row.isDragging = false;
                const file = event.dataTransfer?.files?.[0];
                const input = event.currentTarget?.querySelector('input[type="file"]');
                if (!row || !file || !input || !window.DataTransfer) return;

                try {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                    this.fileChanged(index, { target: input });
                } catch {
                    return;
                }
            },
            clearFile(index) {
                const row = this.rows[index];
                if (!row) return;
                const input = document.getElementById(`document-file-${row.id}`);
                if (input) input.value = '';
                this.revoke(row);
                row.previewKind = null;
                row.fileName = null;
                row.fileSizeLabel = null;
                row.fileTypeLabel = null;
                row.isDragging = false;
            },
            removeRow(index) { const [row] = this.rows.splice(index, 1); this.revoke(row); },
            formatFileSize(bytes) {
                if (!bytes) return '0 KB';
                if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
                return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
            },
            revoke(row) { if (row?.previewUrl) URL.revokeObjectURL(row.previewUrl); if (row) row.previewUrl = null; },
            destroy() { this.rows.forEach(row => this.revoke(row)); this.revoke(this.birthRecord); },
        };
    }
</script>
