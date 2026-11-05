<div x-data="{ documentType: @js($documentType ?: ($allowSchoolDocuments ? 'school_id' : 'government_id')) }" class="space-y-3">
    <label for="persona_document_type" class="block text-sm font-semibold text-gray-900">Document path</label>
    <select id="persona_document_type" name="document_type" x-model="documentType" required class="w-full rounded-xl border border-purple-200 bg-white p-3">
        @if($allowSchoolDocuments)
            <option value="school_id">School ID</option>
            <option value="institution_id">Institution ID</option>
        @endif
        <option value="government_id">Philippine government ID</option>
    </select>
    <div x-show="documentType === 'government_id'">
        <label for="persona_government_id_type" class="block text-sm font-semibold text-gray-900">Government ID type</label>
        <select id="persona_government_id_type" name="government_id_type" :disabled="documentType !== 'government_id'" :required="documentType === 'government_id'" class="mt-2 w-full rounded-xl border border-purple-200 bg-white p-3">
            <option value="">Choose a supported Philippine ID</option>
            @foreach($governmentTypes as $type)
                <option value="{{ $type }}" @selected($governmentIdType === $type)>{{ config('guardian_identity.id_types.'.$type.'.label', $type) }}</option>
            @endforeach
        </select>
    </div>
</div>
