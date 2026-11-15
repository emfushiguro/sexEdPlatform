<input type="hidden" name="reference_editor" value="{{ $record?->id ?? 'new' }}">
@foreach(['issuer' => 'Issuer', 'version' => 'Document version', 'published_on' => 'Publication date'] as $field => $fieldLabel)
<label class="text-sm font-semibold text-gray-700">{{ $fieldLabel }}<input name="{{ $field }}" type="{{ $field === 'published_on' ? 'date' : 'text' }}" @if($field !== 'published_on') maxlength="120" @endif value="{{ old('reference_editor') == ($record?->id ?? 'new') ? old($field) : $record?->{$field} }}" class="mt-1 block w-full rounded-lg border-gray-300"></label>
@endforeach
<label class="text-sm font-semibold text-gray-700">Reference image
    <input name="image" type="file" accept="image/jpeg,image/png,image/webp" @if(!$record) required @endif @change="if (thumbnail) URL.revokeObjectURL(thumbnail); thumbnail = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="mt-2 block w-full rounded-lg border border-dashed border-purple-300 bg-purple-50 p-3 text-sm font-normal file:mr-3 file:rounded-md file:border-0 file:bg-purple-800 file:px-3 file:py-2 file:font-semibold file:text-white">
    <span class="mt-1 block text-xs font-normal text-gray-500">JPEG, PNG, or WebP, up to 5 MB. {{ $record ? 'Choose an image to replace the attachment.' : 'Use a public specimen without personal information.' }}</span>
    <template x-if="thumbnail"><img :src="thumbnail" alt="Selected reference attachment" class="mt-3 h-32 w-full rounded-lg border border-gray-200 bg-gray-50 object-contain"></template>
    @if($record)<img x-show="!thumbnail" src="{{ route('admin.identity-references.image', [$type, $record->id]) }}" alt="Current reference attachment" class="mt-3 h-32 w-full rounded-lg border border-gray-200 bg-gray-50 object-contain">@endif
</label>
@foreach(['source_url' => 'Official source URL', 'verification_url' => 'Official verification URL'] as $field => $fieldLabel)
<label class="text-sm font-semibold text-gray-700 sm:col-span-2">{{ $fieldLabel }}<input name="{{ $field }}" type="url" @if($field === 'source_url') required @endif maxlength="2048" value="{{ old('reference_editor') == ($record?->id ?? 'new') ? old($field) : $record?->{$field} }}" placeholder="https://" class="mt-1 block w-full rounded-lg border-gray-300"></label>
@endforeach
