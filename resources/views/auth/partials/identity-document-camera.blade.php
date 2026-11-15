<div x-data="identityDocumentUpload" @pagehide.window="destroy()" @identity-choice-changed.window="cancel()" class="mt-3 space-y-3">
    <input id="{{ $slot }}" type="file" x-ref="selfieInput" name="{{ $slot }}" accept="image/jpeg,image/png,image/webp" hidden @change="uploadSelected($event)" @if($side === 'back') :disabled="!requiresBack()" @endif>
    <div class="flex flex-wrap gap-3">
        <button type="button" @click="startCamera()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white">Capture {{ strtolower($label) }}</button>
        <button type="button" @click="replacePhoto()" class="min-h-11 rounded-lg border border-purple-700 px-4 py-2 font-semibold text-purple-800">Choose photo</button>
    </div>
    <label class="block text-sm font-semibold">Document orientation
        <select x-model="documentOrientation" class="mt-1 w-full rounded-lg border-gray-300"><option value="landscape">Landscape</option><option value="portrait">Portrait</option></select>
    </label>
    <p class="text-sm text-gray-600">Rotate your document or device to fit. The entire camera frame is saved.</p>
    <label x-show="cameras.length > 1" x-cloak class="block text-sm font-semibold">Camera
        <select x-model="selectedCamera" @change="startCamera()" class="mt-1 w-full rounded-lg border-gray-300">
            <option value="">Default camera</option>
            <template x-for="(camera, index) in cameras" :key="camera.deviceId"><option :value="camera.deviceId" x-text="camera.label || ('Camera ' + (index + 1))"></option></template>
        </select>
    </label>
    <div x-show="cameraActive" x-cloak class="space-y-3 rounded-xl bg-gray-950 p-3">
        <video x-ref="video" autoplay muted playsinline :style="{ aspectRatio: documentGuideAspect() }" class="max-h-[70vh] w-full rounded-lg object-contain" aria-label="Live {{ strtolower($label) }} camera preview"></video>
        <button type="button" @click="capturePhoto()" class="min-h-11 rounded-lg bg-white px-4 py-2 font-semibold text-purple-900">Capture photo</button>
    </div>
    <div x-show="previewUrl" x-cloak class="space-y-3 rounded-xl border border-purple-200 p-3">
        <button type="button" @click="$refs.documentPreview.showModal()" class="block w-full cursor-zoom-in rounded-lg focus:ring-2 focus:ring-purple-700" aria-label="Enlarge {{ strtolower($label) }} preview"><img :src="previewUrl" alt="Selected {{ strtolower($label) }} preview" class="max-h-80 w-full object-contain"></button>
        <button type="button" x-show="candidate && !photoReady" @click="usePhoto()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white">Use photo</button>
        <button type="button" @click="retakePhoto()" class="min-h-11 rounded-lg border border-purple-700 px-4 py-2 font-semibold text-purple-800">Retake with camera</button>
    </div>
    <dialog x-ref="documentPreview" class="w-[min(90vw,48rem)] rounded-xl p-4 shadow-2xl backdrop:bg-black/75" aria-label="{{ $label }} preview">
        <button type="button" @click="$refs.documentPreview.close()" class="mb-3 min-h-11 rounded-lg px-3 font-semibold text-purple-800">Close preview</button>
        <img :src="previewUrl" alt="{{ $label }} full preview" class="max-h-[70vh] w-full object-contain">
    </dialog>
    <button type="button" x-show="cameraActive || previewUrl" x-cloak @click="cancel()" class="min-h-11 font-semibold text-purple-800 underline">Clear selection</button>
    <p x-show="cameraError" x-text="cameraError" role="alert" class="text-sm text-red-700"></p>
    <p x-text="status" aria-live="polite" class="text-sm text-gray-700"></p>
</div>
