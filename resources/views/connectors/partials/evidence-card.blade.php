<article
    x-show="isRequirementVisible('{{ $key }}')"
    x-cloak
    data-requirement-key="{{ $key }}"
    class="rounded-xl border border-gray-200 bg-white p-4"
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h4 class="font-semibold text-gray-900">{{ $requirement['label'] }}</h4>
            <p class="mt-1 text-sm leading-6 text-gray-600">{{ $requirement['guidance'] }}</p>
        </div>
        <span class="shrink-0 text-xs font-semibold {{ $requirement['required'] ? 'text-rose-700' : 'text-gray-500' }}">
            {{ $requirement['required'] ? 'Required' : 'Optional' }}
        </span>
    </div>

    @if($requirement['issuing_authority'])
        <p class="mt-3 text-xs text-gray-500">Typical issuer: {{ $requirement['issuing_authority'] }}</p>
    @endif

    <div class="mt-4">
        <label for="evidence-file-{{ $key }}" class="text-sm font-semibold text-gray-800">Upload file{{ ($requirement['maximum_evidence_count'] ?? 1) > 1 ? 's' : '' }}</label>
        <div
            data-evidence-dropzone="{{ $key }}"
            class="mt-2 rounded-2xl border-2 border-dashed border-brand-200 bg-brand-50/40 p-3 transition hover:border-brand-300 hover:bg-brand-50 focus-within:ring-4 focus-within:ring-brand-100"
            :class="draggingEvidenceKey === '{{ $key }}' ? 'border-brand-500 bg-brand-100/60' : ''"
            @dragover.prevent="draggingEvidenceKey = '{{ $key }}'"
            @dragleave.prevent="draggingEvidenceKey = null"
            @drop.prevent="dropEvidenceFiles('{{ $key }}', $event)"
        >
            <div class="px-4 py-5 text-center">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-brand-100" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M5 16.5v1.25A2.25 2.25 0 007.25 20h9.5A2.25 2.25 0 0019 17.75V16.5" /></svg>
                </span>
                <span class="mt-3 block text-sm font-semibold text-gray-900">Choose {{ $requirement['maximum_evidence_count'] === 1 ? 'a file' : 'files' }} below or drop {{ $requirement['maximum_evidence_count'] === 1 ? 'it' : 'them' }} here</span>
                <input
                    id="evidence-file-{{ $key }}"
                    type="file"
                    name="evidence[{{ $key }}][files][]"
                    data-requirement-file="{{ $key }}"
                    @if(($requirement['maximum_evidence_count'] ?? 1) > 1) multiple @endif
                    accept="{{ implode(',', $requirement['accepted_mime_types']) }}"
                    x-bind:disabled="isRequirementDisabled('{{ $key }}')"
                    @change="evidenceFileChanged('{{ $key }}', $event)"
                    aria-describedby="hint-evidence-{{ $key }} error-evidence-{{ $key }}-files"
                    class="mt-3 block w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                <span id="hint-evidence-{{ $key }}" class="mt-1 block text-xs text-gray-600">PDF, JPEG, PNG, or WebP; {{ number_format($requirement['max_kilobytes'] / 1024, 1) }} MB maximum per file. Up to {{ $requirement['maximum_evidence_count'] }} file{{ $requirement['maximum_evidence_count'] === 1 ? '' : 's' }}.</span>
            </div>
        </div>
        <div x-show="(selectedEvidenceFiles['{{ $key }}'] || []).length > 0" x-cloak class="mt-3 rounded-xl border border-brand-100 bg-white p-3">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-600">Selected files</p>
                <button type="button" @click="clearEvidenceFiles('{{ $key }}', true)" class="min-h-11 rounded-lg px-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500">Clear files</button>
            </div>
            <ul class="mt-1 space-y-1 text-sm text-gray-700">
                <template x-for="(fileName, index) in (selectedEvidenceFiles['{{ $key }}'] || [])" :key="index">
                    <li class="break-all" x-text="fileName"></li>
                </template>
            </ul>
        </div>
        <p id="error-evidence-{{ $key }}-files" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error($key.'.files'){{ $message }}@enderror</p>
    </div>

    @foreach($requirement['metadata_fields'] as $metadataField)
        <label class="mt-3 block">
            <span class="text-sm font-semibold text-gray-800">{{ str($metadataField)->replace('_', ' ')->title() }}</span>
            <input
                type="{{ in_array($metadataField, ['issue_date', 'expiry_date'], true) ? 'date' : 'text' }}"
                name="evidence[{{ $key }}][{{ $metadataField }}]"
                value="{{ old('evidence.'.$key.'.'.$metadataField) }}"
                x-bind:disabled="isRequirementDisabled('{{ $key }}')"
                class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500"
            >
        </label>
    @endforeach
</article>
