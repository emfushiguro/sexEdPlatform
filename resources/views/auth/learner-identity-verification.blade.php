<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Identity Verification</h2>
            <p class="mt-3 max-w-sm">Upload clear images so we can review your account safely.</p>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl space-y-6">
        <x-wizard-stepper :is-parent-flow="false" />
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-purple-700">Account setup</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Verify your identity</h1>
            <p class="mt-2 text-gray-700">Choose an ID and upload a clear front image and a selfie. Some IDs also need a back image.</p>
        </header>
        @if($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">
                Please review the fields below and try again.
            </div>
        @endif
        <form method="POST" action="{{ route('learner.identity.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label for="document_type" class="block font-semibold text-gray-900">ID type</label>
                <select id="document_type" name="document_type" required aria-invalid="{{ $errors->has('document_type') ? 'true' : 'false' }}" class="mt-2 w-full rounded-lg border border-gray-400 p-3 focus:ring-2 focus:ring-purple-700">
                    <option value="">Choose an ID type</option>
                    @if($case->pathway === 'teen')
                        <option value="school_id" @selected(old('document_type') === 'school_id')>School ID</option>
                        <option value="institution_id" @selected(old('document_type') === 'institution_id')>Institution ID</option>
                    @endif
                    <option value="government_id" @selected(old('document_type') === 'government_id')>Government ID</option>
                </select>
                @error('document_type')<p class="mt-1 text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="government_id_type" class="block font-semibold text-gray-900">Government ID kind (for government ID)</label>
                <select id="government_id_type" name="government_id_type" class="mt-2 w-full rounded-lg border border-gray-400 p-3 focus:ring-2 focus:ring-purple-700">
                    <option value="">Choose a kind</option>
                    @foreach(config('guardian_identity.id_types', []) as $value => $type)
                        <option value="{{ $value }}" @selected(old('government_id_type') === $value)>{{ $type['label'] }}</option>
                    @endforeach
                </select>
                @error('government_id_type')<p class="mt-1 text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="government_id_type_other" class="block font-semibold text-gray-900">Other government ID kind (if selected)</label>
                <input id="government_id_type_other" name="government_id_type_other" value="{{ old('government_id_type_other') }}" class="mt-2 w-full rounded-lg border border-gray-400 p-3 focus:ring-2 focus:ring-purple-700">
                @error('government_id_type_other')<p class="mt-1 text-red-700">{{ $message }}</p>@enderror
            </div>
            @foreach(['identity_front' => 'Front of ID', 'identity_back' => 'Back of ID (if required)'] as $slot => $label)
                <div>
                    <label for="{{ $slot }}" class="block font-semibold text-gray-900">{{ $label }}</label>
                    <input id="{{ $slot }}" name="{{ $slot }}" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-gray-400 p-3 text-gray-900 focus:ring-2 focus:ring-purple-700">
                    @error($slot)<p class="mt-1 text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <section x-data="identitySelfie" data-testid="selfie-capture" class="space-y-4 rounded-xl border border-purple-200 bg-purple-50/50 p-4 sm:p-5" aria-labelledby="selfie-heading">
                <div>
                    <h2 id="selfie-heading" class="text-lg font-bold text-gray-900">Your selfie</h2>
                    <p class="mt-1 text-sm text-gray-700">Take a selfie with your camera or upload a selfie from your device.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" data-testid="selfie-start-camera" @click="startCamera()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Take a selfie</button>
                    <label for="selfie" class="inline-flex min-h-11 items-center rounded-lg border border-purple-700 bg-white px-4 py-2 font-semibold text-purple-900">Upload a selfie</label>
                </div>
                <input id="selfie" name="selfie" x-ref="selfieInput" @change="uploadSelected($event)" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="selfie-guide{{ $errors->has('selfie') ? ' selfie-error' : '' }}" aria-invalid="{{ $errors->has('selfie') ? 'true' : 'false' }}" class="block w-full min-w-0 rounded-lg border border-gray-400 bg-white p-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-700" data-testid="selfie-upload">
                <div x-show="cameraActive" x-cloak class="space-y-3" data-testid="selfie-camera">
                    <video x-ref="video" autoplay muted playsinline class="aspect-[4/3] w-full rounded-lg bg-gray-900 object-cover" aria-label="Live selfie camera preview"></video>
                    <button type="button" @click="capturePhoto()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Capture photo</button>
                </div>
                <div x-show="previewUrl" x-cloak class="space-y-3" data-testid="selfie-preview">
                    <img :src="previewUrl" alt="Preview of selected selfie" class="aspect-[4/3] w-full rounded-lg bg-gray-100 object-contain">
                    <div class="flex flex-wrap gap-3">
                        <button x-show="candidate && !photoReady" type="button" @click="usePhoto()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Use Photo</button>
                        <button x-show="candidate" type="button" @click="retakePhoto()" class="min-h-11 rounded-lg border border-purple-700 bg-white px-4 py-2 font-semibold text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Retake</button>
                        <button type="button" @click="replacePhoto()" class="min-h-11 rounded-lg border border-purple-700 bg-white px-4 py-2 font-semibold text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Replace</button>
                    </div>
                </div>
                <button x-show="cameraActive || previewUrl" x-cloak type="button" @click="cancel()" class="min-h-11 rounded-lg px-4 py-2 font-semibold text-purple-900 underline focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Cancel</button>
                <p x-show="cameraError" x-text="cameraError" role="alert" class="text-sm font-medium text-red-700"></p>
                <p x-text="status" aria-live="polite" class="text-sm text-gray-700"></p>
                @error('selfie')<p id="selfie-error" class="text-sm text-red-700">{{ $message }}</p>@enderror
                <div id="selfie-guide" class="rounded-lg bg-white p-4 text-sm text-gray-800">
                    <p class="font-semibold">For a clear photo</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>Show only one person in a clear, recent photo.</li>
                        <li>Use good lighting and keep your whole face visible.</li>
                        <li>Avoid sunglasses, face coverings, and heavy filters.</li>
                    </ul>
                </div>
                <p class="text-sm text-gray-700">Your images are used for private manual review. Read our <a href="{{ route('privacy') }}" class="font-semibold text-purple-800 underline focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">privacy policy</a> for details.</p>
            </section>
            <div class="flex items-start gap-3">
                <input id="confirm_submission" name="confirm_submission" type="checkbox" value="1" required class="mt-1 h-5 w-5 accent-purple-700">
                <label for="confirm_submission" class="text-gray-800">I confirm these images are mine and agree to their review under the <a class="font-semibold text-purple-800 underline" href="{{ route('privacy') }}">privacy policy</a>.</label>
            </div>
            @error('confirm_submission')<p class="text-red-700">{{ $message }}</p>@enderror
            <button type="submit" class="min-h-11 rounded-lg bg-purple-800 px-6 py-3 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Submit for review</button>
        </form>
    </main>
</x-auth-split-layout>
