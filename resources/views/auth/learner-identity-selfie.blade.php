<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Identity Verification</h2>
            <p class="mt-3 max-w-sm">Take a clear selfie to finish your verification.</p>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl space-y-6">
        <x-wizard-stepper :is-parent-flow="false" />
        <header>
            <p class="text-sm font-semibold uppercase tracking-wider text-purple-700">Identity verification · Step 2 of 2</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Take your selfie</h1>
            <p class="mt-2 text-gray-700">Show your whole face in good light. Use your camera or choose a photo from your device.</p>
        </header>
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-3 text-sm text-purple-900" role="status">Your ID is saved. <a href="{{ route('learner.identity.create') }}" class="font-semibold underline">Change your ID</a></div>
        @if($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">Please review your selfie and confirmation below.</div>
        @endif

        <form method="POST" action="{{ route('learner.identity.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <section x-data="identitySelfie" data-testid="selfie-capture" class="space-y-4" aria-labelledby="selfie-heading">
                <h2 id="selfie-heading" class="sr-only">Selfie photo</h2>
                <button type="button" data-testid="selfie-start-camera" @click="startCamera()"
                        class="min-h-11 w-full rounded-xl bg-purple-800 px-4 py-3 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Take a selfie</button>
                <div class="relative text-center text-sm text-gray-500" aria-hidden="true">or</div>
                <div>
                    <label for="selfie" class="block font-semibold text-gray-900">Upload a selfie</label>
                    <input id="selfie" name="selfie" x-ref="selfieInput" @change="uploadSelected($event)" type="file" accept="image/jpeg,image/png,image/webp"
                           aria-describedby="selfie-guide{{ $errors->has('selfie') ? ' selfie-error' : '' }}" aria-invalid="{{ $errors->has('selfie') ? 'true' : 'false' }}"
                           class="mt-2 block w-full min-w-0 rounded-xl border border-gray-300 bg-white p-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-2 file:font-semibold file:text-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-700" data-testid="selfie-upload">
                </div>
                <div x-show="cameraActive" x-cloak class="space-y-3 rounded-xl bg-gray-950 p-3" data-testid="selfie-camera">
                    <video x-ref="video" autoplay muted playsinline class="aspect-[4/3] w-full rounded-lg object-cover" aria-label="Live selfie camera preview"></video>
                    <button type="button" @click="capturePhoto()" class="min-h-11 w-full rounded-lg bg-white px-4 py-2 font-semibold text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700">Capture photo</button>
                </div>
                <div x-show="previewUrl" x-cloak class="space-y-3 rounded-xl border border-purple-200 bg-purple-50 p-3" data-testid="selfie-preview">
                    <img :src="previewUrl" alt="Preview of selected selfie" class="aspect-[4/3] w-full rounded-lg bg-white object-contain">
                    <div class="flex flex-wrap gap-3">
                        <button x-show="candidate && !photoReady" type="button" @click="usePhoto()" class="min-h-11 rounded-lg bg-purple-800 px-4 py-2 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700">Use Photo</button>
                        <button x-show="candidate" type="button" @click="retakePhoto()" class="min-h-11 rounded-lg border border-purple-700 bg-white px-4 py-2 font-semibold text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700">Retake</button>
                        <button type="button" @click="replacePhoto()" class="min-h-11 rounded-lg border border-purple-700 bg-white px-4 py-2 font-semibold text-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700">Replace</button>
                    </div>
                </div>
                <button x-show="cameraActive || previewUrl" x-cloak type="button" @click="cancel()" class="min-h-11 rounded-lg px-2 py-2 font-semibold text-purple-900 underline focus:outline-none focus:ring-2 focus:ring-purple-700">Cancel</button>
                <p x-show="cameraError" x-text="cameraError" role="alert" class="text-sm font-medium text-red-700"></p>
                <p x-text="status" aria-live="polite" class="text-sm text-gray-700"></p>
                @error('selfie')<p id="selfie-error" class="text-sm text-red-700">{{ $message }}</p>@enderror
                @if($case->status === 'rejected' && $case->evidence()->where('slot', 'selfie')->exists())
                    <p class="text-sm text-gray-600">You can keep your previous selfie if it is still valid.</p>
                @endif
                <div id="selfie-guide" class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-800">
                    <p class="font-semibold">For a clear photo</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>Only one person, with your whole face visible.</li>
                        <li>Use good light and avoid sunglasses or filters.</li>
                    </ul>
                </div>
                <p class="text-sm text-gray-700">We collect your ID and selfie to confirm this account belongs to you. Only authorized reviewers use them for manual review. Our <a href="{{ route('privacy') }}" class="font-semibold text-purple-800 underline">privacy policy</a> explains how long they are kept and when they may be deleted.</p>
            </section>
            <div class="flex items-start gap-3">
                <input id="confirm_submission" name="confirm_submission" type="checkbox" value="1" required class="mt-1 h-5 w-5 accent-purple-700">
                <label for="confirm_submission" class="text-gray-800">I confirm these images are mine and agree to their review under the <a class="font-semibold text-purple-800 underline" href="{{ route('privacy') }}">privacy policy</a>.</label>
            </div>
            @error('confirm_submission')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            <button type="submit" class="min-h-11 w-full rounded-xl bg-purple-800 px-6 py-3 font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Submit for review</button>
        </form>
    </main>
</x-auth-split-layout>
