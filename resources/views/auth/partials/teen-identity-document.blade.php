@php
    $selection = old('id_selection', $draft['id_selection'] ?? ($case->document_type ?: 'school_id'));
    if ($selection === 'government_id') $selection .= ':'.$case->government_id_type;
    $keepId = $case->status === 'rejected' && $case->requested_corrections !== null && ! $case->requestsCorrection('identity_front');
@endphp
<form method="POST" action="{{ route('learner.identity.document.store') }}" enctype="multipart/form-data"
      x-data="{ selection: @js($selection), government() { return this.selection.startsWith('government_id:'); } }"
      :action="government() ? @js(route('persona.identity.start')) : @js(route('learner.identity.document.store'))" class="space-y-5">
    @csrf
    <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
    <input type="hidden" name="document_type" :value="government() ? 'government_id' : selection">
    <input type="hidden" name="government_id_type" :disabled="!government()" :value="government() ? selection.slice(14) : ''">
    <div>
        <label for="id_selection" class="block font-semibold text-gray-900">ID type</label>
        <select id="id_selection" name="id_selection" x-model="selection" @change="$dispatch('identity-choice-changed')" required class="mt-2 w-full rounded-xl border-gray-300 p-3 focus:ring-purple-700">
            <option value="school_id">School ID</option>
            <option value="institution_id">Institution ID</option>
            <optgroup label="Philippine government ID">
                @foreach(config('persona.government_id_classes', []) as $key => $class)
                    <option value="government_id:{{ $key }}">{{ config('guardian_identity.id_types.'.$key.'.label', $key) }}</option>
                @endforeach
            </optgroup>
        </select>
        @error('id_selection')<p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <p x-show="government()" x-cloak class="text-sm text-gray-700">You will capture your government ID and selfie securely through Persona.</p>
    <section x-show="!government()" x-data="identityDocument" @identity-choice-changed.window="cancel()" @pagehide.window="destroy()" class="space-y-4" data-testid="id-camera-capture">
        <p class="text-sm text-gray-700">Place the front of your ID inside the guide. Make sure your photo and details are readable.</p>
        @if($keepId)
            <p class="rounded-xl bg-purple-50 p-3 text-sm text-purple-900">Your ID needs no changes. Continue to update your selfie, or capture a replacement ID below.</p>
        @endif
        @if($draft)
            <p class="rounded-xl bg-purple-50 p-3 text-sm text-purple-900">Your captured ID is saved. Capture again only if you want to replace it.</p>
        @endif
        <input type="file" name="identity_front" x-ref="selfieInput" accept="image/jpeg,image/png,image/webp" hidden :disabled="government()">
        <button type="button" @click="startCamera()" class="min-h-11 w-full rounded-xl bg-purple-800 px-4 py-3 font-semibold text-white focus:ring-2 focus:ring-purple-700">Open ID camera</button>
        <div x-show="cameras.length > 1" x-cloak>
            <label class="block text-sm font-semibold" for="id-camera-device">Camera</label>
            <select id="id-camera-device" x-model="selectedCamera" @change="startCamera()" class="mt-1 w-full rounded-lg border-gray-300">
                <option value="">Default camera</option>
                <template x-for="(camera, index) in cameras" :key="camera.deviceId"><option :value="camera.deviceId" x-text="camera.label || ('Camera ' + (index + 1))"></option></template>
            </select>
        </div>
        <div x-show="cameraActive" x-cloak class="space-y-3 rounded-xl bg-gray-950 p-3">
            <div class="relative"><video x-ref="video" autoplay muted playsinline aria-label="Live ID camera preview" class="aspect-[4/3] w-full rounded-lg object-contain"></video><div class="pointer-events-none absolute inset-x-[5%] inset-y-[15%] rounded-lg border-2 border-white" aria-hidden="true"></div></div>
            <button type="button" @click="capturePhoto()" class="min-h-11 w-full rounded-lg bg-white px-4 py-2 font-semibold text-purple-900">Capture ID</button>
        </div>
        <div x-show="previewUrl" x-cloak class="space-y-3 rounded-xl border border-purple-200 p-3">
            <img :src="previewUrl" alt="Captured ID preview" class="max-h-60 w-full object-contain">
            <button type="button" x-show="!photoReady" @click="usePhoto()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white">Use photo</button>
            <button type="button" @click="retakePhoto()" class="min-h-11 rounded-lg border border-purple-700 px-4 py-2 font-semibold text-purple-800">Retake</button>
        </div>
        <button x-show="cameraActive || previewUrl" x-cloak type="button" @click="cancel()" class="min-h-11 font-semibold text-purple-800 underline">Cancel capture</button>
        <p x-show="cameraError" x-text="cameraError" role="alert" class="text-sm text-red-700"></p>
        <p x-text="status" aria-live="polite" class="text-sm text-gray-700"></p>
        @error('identity_front')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
    </section>
    <button type="submit" class="min-h-11 w-full rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white focus:ring-2 focus:ring-purple-700" x-text="government() ? 'Start verification' : 'Save ID and continue'">Save ID and continue</button>
</form>
