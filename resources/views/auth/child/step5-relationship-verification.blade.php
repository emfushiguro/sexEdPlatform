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
        <h1 class="mt-1 text-2xl font-bold text-purple-950">Administrative verification</h1>
        <p class="mt-2 text-sm text-gray-700">{{ $pathwayLabel }}</p>
    </div>

    @if ($errors->any())
        <div class="p-4 mb-6 border-l-4 border-red-500 rounded-lg bg-red-50" role="alert">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="p-4 mb-6 text-sm border border-amber-200 rounded-xl bg-amber-50 text-amber-950">
        <p class="font-semibold">Relationship evidence only</p>
        <p class="mt-1">These files are reviewed as evidence for this Guardian–Dependent relationship. Submission does not create a legal determination and does not activate guardian access.</p>
    </div>

    <form method="POST" action="{{ route('parent.create-child.relationship-verification.store') }}" enctype="multipart/form-data" class="space-y-5"
          x-data="guardianEvidenceForm({
              documentTypes: @js($relationshipDocumentTypes),
              requiredDocumentTypes: @js($requiredDocumentTypes),
              maxRows: 10,
          })" x-on:beforeunload.window="destroy()">
        @csrf

        <div class="p-5 overflow-hidden bg-white border border-purple-200 shadow-sm rounded-2xl">
            <div class="mb-4">
                <p class="text-sm text-gray-600">Upload at least one core category: <span class="font-semibold text-purple-900" x-text="requiredDocumentTypes.map(type => documentTypes[type] || type).join(', ')"></span>.</p>
                <p class="mt-1 text-xs text-gray-500">You may submit up to 10 PDF, JPEG, PNG, or WebP files. Context is {{ $requiresCircumstances ? 'required' : 'optional' }} for this pathway.</p>
            </div>

            <div class="space-y-4">
                <template x-for="(row, index) in rows" :key="row.id">
                    <div class="p-4 border border-gray-200 rounded-xl">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900">Document <span x-text="index + 1"></span></p>
                            <button type="button" class="text-xs font-semibold text-rose-600 disabled:text-gray-400" :disabled="rows.length === 1" @click="removeRow(index)">Remove</button>
                        </div>

                        <div class="grid gap-4 mt-3 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-gray-700">Document type
                                <select :name="`documents[${index}][document_type]`" x-model="row.documentType" required class="w-full px-3 py-2 mt-1 text-sm border border-gray-200 rounded-xl bg-gray-50">
                                    <option value="">Select document</option>
                                    <template x-for="(label, value) in documentTypes" :key="value">
                                        <option :value="value" x-text="label"></option>
                                    </template>
                                </select>
                            </label>

                            <label class="block text-sm font-medium text-gray-700">Document side
                                <select :name="`documents[${index}][document_side]`" x-model="row.side" @change="sideChanged(index)" required class="w-full px-3 py-2 mt-1 text-sm border border-gray-200 rounded-xl bg-gray-50">
                                    <option value="not_applicable">Not applicable</option>
                                    <option value="front">Front</option>
                                    <option value="back">Back</option>
                                </select>
                            </label>
                        </div>

                        <input :name="`documents[${index}][pairing_key]`" type="hidden" :value="row.pairingKey">
                        <label class="block mt-3 text-sm font-medium text-gray-700">File
                            <input :name="`documents[${index}][file]`" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required @change="fileChanged(index, $event)" class="w-full px-3 py-2 mt-1 text-sm border border-gray-200 rounded-xl bg-gray-50">
                        </label>
                        <p class="mt-1 text-xs text-gray-500" x-text="row.fileName || 'PDF, JPEG, PNG, or WebP · maximum 5 MB'"></p>
                        <template x-if="row.previewUrl && row.previewKind === 'image'"><img :src="row.previewUrl" alt="Selected evidence preview" class="object-contain max-h-40 mt-2 rounded-lg border border-gray-200"></template>
                        <template x-if="row.previewUrl && row.previewKind === 'pdf'"><iframe :src="row.previewUrl" title="Selected PDF evidence preview" class="w-full h-40 mt-2 rounded-lg border border-gray-200"></iframe></template>
                        <button type="button" class="mt-3 text-sm font-semibold text-purple-700 disabled:text-gray-400" :disabled="!row.documentType || row.pairingKey || rows.length >= maxRows" @click="addBackSide(index)">Add back side</button>
                    </div>
                </template>
            </div>

            <button type="button" class="px-4 py-2 mt-4 text-sm font-semibold text-purple-700 border border-purple-200 rounded-xl disabled:opacity-50" :disabled="rows.length >= maxRows" @click="addRow()">Add another document</button>

            <div class="mt-4">
                <label for="relationship_notes" class="block text-sm font-medium text-gray-700">Context {{ $requiresCircumstances ? '(required)' : '(optional)' }}</label>
                <textarea id="relationship_notes" name="relationship_notes" rows="4" maxlength="1000" class="w-full px-3 py-2 mt-1 text-sm border border-gray-200 rounded-xl bg-gray-50" placeholder="Share context for the reviewer.">{{ old('relationship_notes') }}</textarea>
            </div>

            <label class="flex items-start gap-2 p-3 mt-4 text-xs border rounded-xl border-amber-200 bg-amber-50 text-amber-900">
                <input type="checkbox" name="confirm_submission" value="1" required class="mt-0.5 rounded border-amber-300" @checked(old('confirm_submission'))>
                <span>I confirm these documents support the requested Guardian–Dependent relationship.</span>
            </label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('parent.create-child.validation') }}" class="text-sm text-gray-500 hover:text-gray-700">Back</a>
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
            init() { this.addRow(); },
            addRow() {
                if (this.rows.length >= this.maxRows) return;
                this.rows.push({ id: crypto.randomUUID(), documentType: '', side: 'not_applicable', pairingKey: null, previewUrl: null, previewKind: null, fileName: null });
            },
            addBackSide(index) {
                const row = this.rows[index];
                if (!row || row.pairingKey || !row.documentType || this.rows.length >= this.maxRows) return;
                const pairingKey = crypto.randomUUID();
                row.side = 'front';
                row.pairingKey = pairingKey;
                this.rows.splice(index + 1, 0, { id: crypto.randomUUID(), documentType: row.documentType, side: 'back', pairingKey, previewUrl: null, previewKind: null, fileName: null });
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
            },
            removeRow(index) { if (this.rows.length === 1) return; const [row] = this.rows.splice(index, 1); this.revoke(row); },
            revoke(row) { if (row?.previewUrl) URL.revokeObjectURL(row.previewUrl); if (row) row.previewUrl = null; },
            destroy() { this.rows.forEach(row => this.revoke(row)); },
        };
    }
</script>
