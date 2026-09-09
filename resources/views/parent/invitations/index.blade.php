@extends('layouts.learner-app')

@section('title', 'Guardian Invitations')

@section('content')
@php
    $totalOutgoingInvitations = $totalOutgoingInvitations ?? $outgoingInvitations->count();
    $relationshipOptions = \App\Support\GuardianRelationshipTypes::options();
    $verificationRequiredTypes = array_values(array_filter(array_keys($relationshipOptions), fn ($type) => \App\Support\GuardianRelationshipTypes::requiresVerification($type)));
    $relationshipDocumentTypeMap = collect(array_keys($relationshipOptions))
        ->mapWithKeys(fn ($type) => [$type => \App\Support\GuardianRelationshipTypes::documentTypeOptions($type)])
        ->all();
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    <div class="rounded-2xl p-6 text-white"
         style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Guardian Invitation Center</h1>
                <p class="text-white/80 text-sm mt-1">Send invitations and track recent guardian-link activity.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('parent.invitations.history') }}"
                   class="inline-flex items-center rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/25 border border-white/20">
                    Full History
                </a>
                <a href="{{ route('parent.children.index') }}"
                   class="inline-flex items-center rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/30">
                    Back to My Dependents
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-semibold text-gray-900">Send New Invitation</h2>
        <p class="text-sm text-gray-500 mt-1">Enter the learner's username or email address and define your guardian relationship.</p>

        <form method="POST" action="{{ route('parent.invitations.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3"
              x-data="guardianInvitationEvidence({ oldRows: @js(old('documents', [])), documentTypeMap: @js($relationshipDocumentTypeMap), maxFiles: 10, verificationRequiredTypes: @js($verificationRequiredTypes) })">
            @csrf
            <div>
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">Learner Username or Email</label>
                <input id="identifier"
                       name="identifier"
                       type="text"
                       required
                       value="{{ old('identifier') }}"
                       placeholder="e.g. learnerusername or learner@email.com"
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                @error('identifier')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="relationship_type" class="block text-sm font-medium text-gray-700 mb-1">Guardian Relationship</label>
                    <select id="relationship_type"
                            name="relationship_type"
                            required
                            x-model="relationshipType"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                        <option value="">Select relationship</option>
                        @foreach($relationshipOptions as $value => $label)
                            @continue($value === \App\Support\GuardianRelationshipTypes::LEGACY_PARENT)
                            <option value="{{ $value }}" @selected(old('relationship_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('relationship_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="verificationRequiredTypes.includes(relationshipType)" x-cloak class="mt-1 text-xs text-amber-700">
                        Supporting documentation is required before this relationship can be reviewed.
                    </p>
                </div>

                <div x-show="relationshipType === 'other'" x-cloak>
                    <label for="relationship_custom" class="block text-sm font-medium text-gray-700 mb-1">Custom Relationship</label>
                    <input id="relationship_custom"
                           name="relationship_custom"
                           type="text"
                           maxlength="120"
                           value="{{ old('relationship_custom') }}"
                           placeholder="Specify relationship"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                    @error('relationship_custom')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div x-show="verificationRequiredTypes.includes(relationshipType)" x-cloak class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm">
                <div class="border-b border-amber-100 bg-gradient-to-r from-amber-50 to-purple-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Required verification</p>
                    <h3 class="mt-1 text-base font-semibold text-purple-950">Relationship documents</h3>
                    <p class="mt-1 text-xs text-gray-600">Upload one to ten files. Admins review these private documents before approval.</p>
                </div>

                <div class="space-y-4 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-600">Use a front/back pair when one document has two sides. Maximum 10 files.</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    @click="addDocument"
                                    :disabled="rows.length >= maxFiles"
                                    class="inline-flex min-h-10 items-center rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-800 hover:bg-purple-100 disabled:cursor-not-allowed disabled:opacity-50">
                                Add document
                            </button>
                            <button type="button"
                                    @click="addPair"
                                    :disabled="rows.length > maxFiles - 2"
                                    class="inline-flex min-h-10 items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50">
                                Add front/back pair
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3" aria-live="polite">
                        <template x-for="(row, index) in rows" :key="row.key">
                            <fieldset class="rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                                <legend class="sr-only">Evidence document <span x-text="index + 1"></span></legend>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-900">
                                        Document <span x-text="index + 1"></span>
                                        <span class="text-xs font-normal text-gray-500" x-text="row.document_side === 'not_applicable' ? '· single-sided' : '${row.document_side}'"></span>
                                    </p>
                                    <button type="button"
                                            @click="removeDocument(index)"
                                            class="inline-flex min-h-10 items-center rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                            :aria-label="`Remove document ${index + 1}`">
                                        Remove document
                                    </button>
                                </div>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <label class="block text-xs font-semibold text-gray-700" :for="`document-type-${row.key}`">
                                        Document category
                                        <select :id="`document-type-${row.key}`"
                                                :name="`documents[${index}][document_type]`"
                                                x-model="row.document_type"
                                                required
                                                class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-normal text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                                            <option value="">Select document</option>
                                            <template x-for="(label, value) in (documentTypeMap[relationshipType] || {})" :key="value">
                                                <option :value="value" x-text="label"></option>
                                            </template>
                                        </select>
                                    </label>

                                    <label class="block text-xs font-semibold text-gray-700" :for="`document-side-${row.key}`">
                                        Document side
                                        <select :id="`document-side-${row.key}`"
                                                :name="`documents[${index}][document_side]`"
                                                x-model="row.document_side"
                                                required
                                                class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-normal text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                                            <option value="not_applicable">Not applicable</option>
                                            <option value="front">Front</option>
                                            <option value="back">Back</option>
                                        </select>
                                    </label>
                                </div>

                                <input type="hidden"
                                       :name="`documents[${index}][pairing_key]`"
                                       :value="row.pairing_key">

                                <div class="mt-3">
                                    <label class="block text-xs font-semibold text-gray-700" :for="`document-file-${row.key}`">
                                        Evidence file
                                        <span class="font-normal text-gray-500">(PDF, JPG, JPEG, PNG, or WebP; max 5 MB)</span>
                                    </label>
                                    <label :for="`document-file-${row.key}`"
                                           class="mt-1 flex min-h-11 cursor-pointer items-center gap-2 rounded-xl border border-purple-200 bg-purple-50/50 px-3 py-2 transition hover:border-purple-400 hover:bg-purple-50 focus-within:border-purple-500 focus-within:ring-2 focus-within:ring-purple-100">
                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-purple-700 ring-1 ring-purple-100" aria-hidden="true">↥</span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-purple-950">Replace file</span>
                                        <span class="max-w-[45%] truncate text-xs font-medium text-gray-600" x-text="row.fileName"></span>
                                    </label>
                                    <input :id="`document-file-${row.key}`"
                                           :name="`documents[${index}][file]`"
                                           type="file"
                                           accept=".pdf,.jpg,.jpeg,.png,.webp"
                                           required
                                           class="sr-only"
                                           @change="chooseFile(index, $event)">
                                    <p class="mt-1 text-xs text-gray-600" role="status" x-text="row.fileName"></p>
                                    <p class="mt-1 text-xs text-amber-800" role="status" aria-live="polite" x-show="duplicateWarning(index)" x-text="duplicateWarning(index)"></p>

                                    <template x-if="row.previewKind === 'image' && row.previewUrl">
                                        <img :src="row.previewUrl" :alt="`Preview of document ${index + 1}`" class="mt-2 max-h-40 rounded-lg border border-gray-200 object-contain">
                                    </template>
                                    <template x-if="row.previewKind === 'pdf' && row.previewUrl">
                                        <iframe :src="row.previewUrl" :title="`PDF preview of document ${index + 1}`" class="mt-2 h-48 w-full rounded-lg border border-gray-200"></iframe>
                                    </template>
                                </div>
                            </fieldset>
                        </template>
                    </div>

                    @foreach($errors->messages() as $field => $messages)
                        @if(str_starts_with($field, 'documents'))
                            @foreach($messages as $message)
                                <p class="text-xs text-red-600" role="alert">{{ $message }}</p>
                            @endforeach
                        @endif
                    @endforeach

                    @if($errors->has('documents') || $errors->has('documents.*'))
                        <p class="text-xs text-amber-800" role="status">
                            Your document categories were restored. For your security, please reselect the files before submitting again.
                        </p>
                    @endif

                    <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                        <input type="checkbox" name="confirm_relationship_verification" value="1" required class="mt-0.5 rounded border-amber-300 text-purple-700 focus:ring-purple-500" @checked(old('confirm_relationship_verification'))>
                        <span>I confirm these documents support the requested relationship and should be submitted for administrative review.</span>
                    </label>
                    @error('confirm_relationship_verification')
                        <p class="text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message (optional)</label>
                <textarea id="message"
                          name="message"
                          rows="3"
                          maxlength="500"
                          placeholder="Add a short context for the learner"
                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                    Send Invitation
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">Recent Invitations</h2>
            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                {{ $totalOutgoingInvitations }} total
            </span>
        </div>

        @if($outgoingInvitations->isEmpty())
            <p class="text-sm text-gray-500 mt-3">No invitations sent yet.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach($outgoingInvitations as $invitation)
                    @php
                        $statusValue = $invitation->status instanceof \App\Enums\ParentChildInvitationStatus
                            ? $invitation->status->value
                            : (string) $invitation->status;
                        $statusClass = match ($statusValue) {
                            'accepted' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                            'cancelled' => 'bg-gray-100 text-gray-700',
                            'expired' => 'bg-orange-100 text-orange-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                        $childAvatarPath = $invitation->child?->learnerProfile?->avatar_path;
                        $childAvatarUrl = $childAvatarPath
                            ? asset('storage/' . ltrim((string) $childAvatarPath, '/'))
                            : null;
                    @endphp

                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex items-center gap-3">
                                @if($childAvatarUrl)
                                    <img src="{{ $childAvatarUrl }}" alt="Invited learner avatar" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                                @else
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                        {{ strtoupper(substr((string) ($invitation->child?->name ?? 'L'), 0, 1)) }}
                                    </span>
                                @endif
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $invitation->child?->name ?? 'Learner' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $invitation->child?->email ?? 'No email' }}
                                        @if($invitation->child?->learnerProfile?->username)
                                            · {{ $invitation->child->learnerProfile->username }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">Sent {{ $invitation->created_at?->diffForHumans() }}</p>
                                    <span class="mt-2 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        {{ $invitation->relationshipLabel() }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($statusValue) }}
                                </span>
                                <a href="{{ route('parent.invitations.show', $invitation) }}"
                                   class="inline-flex items-center rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-50">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($totalOutgoingInvitations > $outgoingInvitations->count())
                <div class="mt-4 flex justify-end">
                    <a href="{{ route('parent.invitations.history') }}"
                       class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-100">
                        View Full History
                    </a>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.guardianInvitationEvidence = ({ oldRows, documentTypeMap, maxFiles, verificationRequiredTypes }) => ({
        relationshipType: @js(old('relationship_type', '')),
        documentTypeMap,
        maxFiles,
        verificationRequiredTypes,
        rows: [],
        init() {
            const restored = Array.isArray(oldRows) ? oldRows : [];
            this.rows = restored.length > 0
                ? restored.map((row) => this.makeRow(row))
                : [this.makeRow()];
        },
        makeRow(row = {}) {
            return {
                key: crypto.randomUUID(),
                document_type: String(row.document_type || ''),
                document_side: String(row.document_side || 'not_applicable'),
                pairing_key: row.pairing_key ? String(row.pairing_key) : '',
                file: null,
                fileName: 'No file selected',
                previewUrl: null,
                previewKind: null,
            };
        },
        addDocument() {
            if (this.rows.length < this.maxFiles) {
                this.rows.push(this.makeRow());
            }
        },
        addPair() {
            if (this.rows.length <= this.maxFiles - 2) {
                const pairingKey = crypto.randomUUID();
                this.rows.push(this.makeRow({ document_side: 'front', pairing_key: pairingKey }));
                this.rows.push(this.makeRow({ document_side: 'back', pairing_key: pairingKey }));
            }
        },
        chooseFile(index, event) {
            const file = event.target.files?.[0] || null;
            this.revokePreview(this.rows[index]);
            this.rows[index].file = file;
            this.rows[index].fileName = file?.name || 'No file selected';
            this.rows[index].previewKind = file?.type === 'application/pdf'
                ? 'pdf'
                : (file?.type || '').startsWith('image/') ? 'image' : null;
            this.rows[index].previewUrl = file ? URL.createObjectURL(file) : null;
        },
        removeDocument(index) {
            this.revokePreview(this.rows[index]);
            this.rows.splice(index, 1);
            if (this.rows.length === 0) {
                this.rows.push(this.makeRow());
            }
        },
        revokePreview(row) {
            if (row?.previewUrl) {
                URL.revokeObjectURL(row.previewUrl);
            }
        },
        duplicateWarning(index) {
            const row = this.rows[index];
            if (!row?.file) {
                return '';
            }

            return this.rows.some((candidate, candidateIndex) => candidateIndex !== index
                && candidate.file
                && candidate.file.name === row.file.name
                && candidate.file.size === row.file.size
                && candidate.file.lastModified === row.file.lastModified)
                ? 'This appears to duplicate another selected file.'
                : '';
        },
        destroy() {
            this.rows.forEach((row) => this.revokePreview(row));
        },
    });
</script>
@endpush
