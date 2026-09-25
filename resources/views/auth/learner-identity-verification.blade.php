@php
    $selectedId = old('id_selection', $draft['id_selection'] ?? ($case->document_type === 'government_id' ? 'government_id:'.$case->government_id_type : $case->document_type));
    $idRequirements = collect(config('guardian_identity.id_types', []))->mapWithKeys(fn ($type, $key) => ['government_id:'.$key => (bool) $type['requires_back']])->all();
@endphp
<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Identity Verification</h2>
            <p class="mt-3 max-w-sm">Start with a clear image of your ID.</p>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl space-y-6">
        <x-wizard-stepper :is-parent-flow="false" />
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-purple-700">Identity verification · Step 1 of 2</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Upload your ID</h1>
            <p class="mt-2 text-gray-700">Choose one ID type, then add a clear photo of its front. Some IDs also need the back.</p>
        </header>
        @if($draft)
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-gray-800" role="status">
                Your ID is saved for this session. You can <a href="{{ route('learner.identity.selfie.create') }}" class="font-semibold text-purple-800 underline">continue to your selfie</a> or replace an image below.
            </div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">Please review the fields below and try again.</div>
        @endif

        <form method="POST" action="{{ route('learner.identity.document.store') }}" enctype="multipart/form-data" class="space-y-5"
              x-data="{
                  idSelection: @js($selectedId),
                  requirements: @js($idRequirements),
                  previewUrl: '', previewTitle: '',
                  documents: { front: { name: '', url: '' }, back: { name: '', url: '' } },
                  requiresBack() { return Boolean(this.requirements[this.idSelection]); },
                  setDocument(side, event) {
                      this.clearDocument(side, false);
                      const file = event.target.files?.[0];
                      if (file) { this.documents[side].name = file.name; this.documents[side].url = URL.createObjectURL(file); }
                  },
                  clearDocument(side, clearInput = true) {
                      if (this.documents[side].url) URL.revokeObjectURL(this.documents[side].url);
                      this.documents[side] = { name: '', url: '' };
                      if (clearInput) this.$refs[side + 'Input'].value = '';
                  },
                  showPreview(side) {
                      this.previewUrl = this.documents[side].url;
                      this.previewTitle = side === 'front' ? 'Front of ID' : 'Back of ID';
                      this.$refs.previewDialog.showModal();
                  },
                  closePreview() { this.$refs.previewDialog.close(); this.previewUrl = ''; }
              }">
            @csrf
            <div>
                <label for="id_selection" class="block font-semibold text-gray-900">ID type</label>
                <select id="id_selection" name="id_selection" x-model="idSelection" @change="if (!requiresBack()) clearDocument('back')" required
                        aria-invalid="{{ $errors->has('id_selection') ? 'true' : 'false' }}"
                        class="mt-2 w-full rounded-xl border border-gray-300 bg-white p-3 focus:outline-none focus:ring-2 focus:ring-purple-700">
                    <option value="">Choose an ID type</option>
                    @if($case->pathway === 'teen')
                        <optgroup label="School or institution">
                            <option value="school_id">School ID</option>
                            <option value="institution_id">Institution ID</option>
                        </optgroup>
                    @endif
                    <optgroup label="Government ID">
                        @foreach(config('guardian_identity.id_types', []) as $value => $type)
                            <option value="government_id:{{ $value }}">{{ $type['label'] }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="mt-2 text-sm text-gray-600" x-text="!idSelection ? 'Select an ID type to see what you need.' : (requiresBack() ? 'Front and back images required.' : 'Front image only required.')"></p>
                @error('id_selection')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div x-show="idSelection === 'government_id:other'" x-cloak>
                <label for="government_id_type_other" class="block font-semibold text-gray-900">Specify your ID type</label>
                <input id="government_id_type_other" name="government_id_type_other" value="{{ old('government_id_type_other', $draft['government_id_type_other'] ?? $case->government_id_type_other) }}"
                       :disabled="idSelection !== 'government_id:other'" class="mt-2 w-full rounded-xl border border-gray-300 p-3 focus:outline-none focus:ring-2 focus:ring-purple-700">
                @error('government_id_type_other')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-5">
                @foreach(['front' => 'Front of ID', 'back' => 'Back of ID'] as $side => $label)
                    @php $slot = 'identity_'.$side; @endphp
                    <div @if($side === 'back') x-show="requiresBack()" x-cloak @endif>
                        <label for="{{ $slot }}" class="block font-semibold text-gray-900">{{ $label }}</label>
                        <input id="{{ $slot }}" name="{{ $slot }}" type="file" accept="image/jpeg,image/png,image/webp" capture="environment"
                               x-ref="{{ $side }}Input" @change="setDocument('{{ $side }}', $event)" @if($side === 'back') :disabled="!requiresBack()" @endif
                               class="mt-2 block w-full min-w-0 rounded-xl border border-gray-300 bg-white p-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-2 file:font-semibold file:text-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-700">
                        @if(!empty($draft[$slot.'_path']))<p class="mt-2 text-sm font-medium text-purple-800">Saved image available. Choose a file to replace it.</p>@endif
                        @if($case->status === 'rejected' && $case->evidence()->where('slot', $slot)->exists())<p class="mt-2 text-sm text-gray-600">You can keep your previous image if it is still valid.</p>@endif
                        @error($slot)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        <div x-show="documents.{{ $side }}.url" x-cloak data-testid="learner-id-{{ $side }}-preview" class="mt-3 rounded-xl border border-purple-200 bg-purple-50 p-3">
                            <button type="button" @click="showPreview('{{ $side }}')" class="block w-full cursor-zoom-in rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-700" aria-label="Enlarge {{ strtolower($label) }} preview">
                                <img :src="documents.{{ $side }}.url" alt="Selected {{ strtolower($label) }} preview" class="h-36 w-full rounded-lg bg-white object-contain">
                            </button>
                            <div class="mt-2 flex items-center justify-between gap-2 text-sm">
                                <span x-text="documents.{{ $side }}.name" class="min-w-0 truncate text-gray-700"></span>
                                <button type="button" @click="clearDocument('{{ $side }}')" class="font-semibold text-purple-800 underline">Remove</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <dialog x-ref="previewDialog" @close="previewUrl = ''" class="w-[min(90vw,48rem)] rounded-xl p-4 shadow-2xl backdrop:bg-black/75" aria-label="ID image preview">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-gray-900" x-text="previewTitle"></h2>
                    <button type="button" @click="closePreview()" class="rounded-lg px-3 py-2 font-semibold text-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-700">Close</button>
                </div>
                <img :src="previewUrl" :alt="previewTitle" class="max-h-[70vh] w-full object-contain">
            </dialog>
            <button type="submit" class="min-h-11 w-full rounded-xl bg-purple-800 px-6 py-3 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Continue to selfie</button>
        </form>
    </main>
</x-auth-split-layout>
