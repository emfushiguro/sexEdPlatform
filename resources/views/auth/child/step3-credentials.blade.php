<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="h-20 w-auto mx-auto mb-3">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Login details</h2>
            <p class="text-white/80 text-lg max-w-xs">Create a username and password your child can use to sign in.</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Set Up Info',    'active' => false, 'done' => true],
        ['label' => 'Where Are You?', 'active' => false, 'done' => true],
        ['label' => 'Login Details',  'active' => true,  'done' => false],
        ['label' => 'All Set!',       'active' => false, 'done' => false],
    ]" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-purple-900">Create Login Credentials</h1>
        <p class="mt-1 text-sm text-gray-600">
            Setting up login for <strong>{{ $step1['first_name'] ?? 'your child' }}</strong>.
        </p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
            <h3 class="text-sm font-medium text-red-800 mb-1">Please correct the following errors:</h3>
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

        <form method="POST" action="{{ route('parent.create-child.credentials.store') }}" enctype="multipart/form-data"
            x-data="{
                showPass: false,
                showConfirm: false,
                password: '',
                passwordConfirmation: '',
                uploadBusy: false,
                uploadError: '',
                checks() {
                    return {
                        length: this.password.length >= 8,
                        lower: /[a-z]/.test(this.password),
                        upper: /[A-Z]/.test(this.password),
                        number: /\d/.test(this.password),
                        symbol: /[^A-Za-z0-9]/.test(this.password),
                    };
                },
                score() {
                    const checks = this.checks();
                    return Object.values(checks).filter(Boolean).length;
                },
                strengthLabel() {
                    const score = this.score();
                    if (score <= 2) return 'Weak';
                    if (score <= 4) return 'Good';
                    return 'Strong';
                },
                upload: {
                    hasStored: @js($hasChildVerificationUpload ?? false),
                    path: @js($tempChildVerificationUpload['path'] ?? ''),
                    originalName: @js($tempChildVerificationUpload['original_name'] ?? ''),
                    previewUrl: @js($tempChildVerificationUpload['preview_url'] ?? ''),
                },
                previewModalOpen: false,
                previewModalUrl: '',
                previewModalType: 'file',
                previewModalTitle: '',
                resolvePreviewType(name, url) {
                    const source = ((name || '') + ' ' + (url || '')).toLowerCase();

                    if (/\.(jpg|jpeg|png|gif|webp)(\?|$)/.test(source)) {
                        return 'image';
                    }

                    if (/\.pdf(\?|$)/.test(source)) {
                        return 'pdf';
                    }

                    return 'file';
                },
                openUploadPreview(url, name) {
                    if (!url) {
                        return;
                    }

                    this.previewModalUrl = url;
                    this.previewModalTitle = name || 'PSA Birth Certificate Preview';
                    this.previewModalType = this.resolvePreviewType(name, url);
                    this.previewModalOpen = true;
                },
                closeUploadPreview() {
                    this.previewModalOpen = false;
                    this.previewModalUrl = '';
                    this.previewModalType = 'file';
                    this.previewModalTitle = '';
                },
                csrfToken() {
                    return document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                },
                async uploadVerificationDocument(event) {
                    const file = event.target.files?.[0];
                    this.uploadError = '';

                    if (!file) {
                        return;
                    }

                    this.uploadBusy = true;

                    try {
                        const formData = new FormData();
                        formData.append('verification_document', file);

                        const response = await fetch(@js(route('parent.create-child.credentials.temp-upload')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken(),
                            },
                            body: formData,
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const firstError = payload?.errors ? Object.values(payload.errors)[0]?.[0] : null;
                            throw new Error(firstError || payload.message || 'Unable to upload PSA birth certificate.');
                        }

                        this.upload.hasStored = true;
                        this.upload.path = payload?.upload?.path || '';
                        this.upload.originalName = payload?.upload?.original_name || file.name;
                        this.upload.previewUrl = payload?.upload?.preview_url || '';
                    } catch (error) {
                        this.uploadError = error.message || 'Unable to upload PSA birth certificate.';
                    } finally {
                        this.uploadBusy = false;
                        event.target.value = '';
                    }
                },
                async removeVerificationDocumentUpload() {
                    this.uploadError = '';
                    this.uploadBusy = true;

                    try {
                        const response = await fetch(@js(route('parent.create-child.credentials.temp-upload.remove')), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken(),
                            },
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to remove uploaded PSA birth certificate.');
                        }

                        this.upload.hasStored = false;
                        this.upload.path = '';
                        this.upload.originalName = '';
                        this.upload.previewUrl = '';
                        this.closeUploadPreview();

                        if (this.$refs.verificationDocumentInput) {
                            this.$refs.verificationDocumentInput.value = '';
                        }
                    } catch (error) {
                        this.uploadError = error.message || 'Unable to remove uploaded PSA birth certificate.';
                    } finally {
                        this.uploadBusy = false;
                    }
                }
            }">
        @csrf

        {{-- Username --}}
        <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                Username <span class="text-red-500">*</span>
            </label>
            <input id="username" name="username" type="text" required
                   value="{{ old('username') }}"
                   pattern="[a-z0-9_\-]+"
                   minlength="3" maxlength="30"
                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                   placeholder="e.g. maria_santos">
            <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, underscores and hyphens only.</p>
            @error('username')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Child Verification Document --}}
        <div class="mb-6">
            @php
                $existingVerificationPath = $tempChildVerificationUpload['path'] ?? null;
                $existingVerificationName = $tempChildVerificationUpload['original_name'] ?? ($existingVerificationPath ? basename($existingVerificationPath) : null);
                $existingVerificationPreviewUrl = $tempChildVerificationUpload['preview_url'] ?? ($existingVerificationPath ? asset('storage/' . $existingVerificationPath) : null);
            @endphp
            <label for="verification_document" class="block text-sm font-medium text-gray-700 mb-1">
                PSA Birth Certificate only <span class="text-red-500">*</span>
            </label>
            <input id="verification_document" name="verification_document" type="file"
                   x-ref="verificationDocumentInput"
                   :required="!upload.hasStored"
                   :disabled="uploadBusy"
                   @change="uploadVerificationDocument($event)"
                   accept=".jpg,.jpeg,.png,.pdf"
                   class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 file:mr-3 file:rounded-lg file:border-0 file:bg-purple-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-purple-700">
                 <p class="mt-1 text-xs text-gray-500">Upload PSA birth certificate (JPG, PNG, or PDF, max 5MB).</p>

            <p x-show="uploadError" x-cloak class="mt-2 text-xs text-red-600" x-text="uploadError"></p>

            <div x-show="upload.hasStored" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3" data-testid="child-verification-preview">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-emerald-800">Uploaded PSA document ready for submission</p>
                        <p class="mt-1 text-xs text-emerald-700 break-all" x-text="upload.originalName || 'Uploaded document'"></p>
                    </div>
                    <button type="button"
                            @click="removeVerificationDocumentUpload()"
                            :disabled="uploadBusy"
                            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                            aria-label="Remove uploaded PSA birth certificate">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <button type="button"
                       x-show="upload.previewUrl"
                       x-cloak
                       @click="openUploadPreview(upload.previewUrl, upload.originalName)"
                       class="inline-flex rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                        Preview
                    </button>
                </div>
            </div>

            @if(!empty($existingVerificationPath))
                <div class="hidden mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3" data-testid="child-verification-preview">
                    <p class="text-xs font-semibold text-emerald-800">Uploaded PSA document ready for submission</p>
                    <p class="mt-1 text-xs text-emerald-700">{{ $existingVerificationName }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        @if($existingVerificationPreviewUrl)
                            <a href="{{ $existingVerificationPreviewUrl }}" class="inline-flex rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                Preview
                            </a>
                        @endif
                    </div>
                </div>
            @endif
            @error('verification_document')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input id="password" name="password" :type="showPass ? 'text' : 'password'" required
                      x-model="password"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition pr-10"
                       placeholder="••••••••">
                <button type="button" @click="showPass = !showPass"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            <div x-show="password" x-cloak class="mt-2">
                <div class="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full transition-all duration-300"
                         :style="`width: ${password ? (score() / 5) * 100 : 0}%`"
                         :class="score() <= 2 ? 'bg-red-500' : (score() <= 4 ? 'bg-amber-500' : 'bg-emerald-500')"></div>
                </div>
                <div class="mt-1.5 min-h-[18px] flex items-center justify-between gap-2 text-xs">
                    <span class="font-medium"
                          :class="password ? (score() <= 2 ? 'text-red-600' : (score() <= 4 ? 'text-amber-600' : 'text-emerald-600')) : 'text-gray-500'"
                          x-text="password ? `Strength: ${strengthLabel()}` : 'Strength: -'"></span>
                    <span class="text-right"
                          :class="password ? (score() === 5 ? 'text-emerald-600' : 'text-gray-500') : 'text-gray-500'"
                          x-text="password ? (score() === 5 ? 'All requirements met' : 'Use upper, lower, number, symbol') : 'Min 8 chars'"></span>
                </div>
            </div>
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                Confirm Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation"
                       :type="showConfirm ? 'text' : 'password'" required
                      x-model="passwordConfirmation"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition pr-10"
                       placeholder="••••••••">
                <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            <p class="mt-1 min-h-[18px] text-xs"
               :class="!passwordConfirmation ? 'text-gray-500' : (passwordConfirmation === password ? 'text-emerald-600' : 'text-red-600')"
               x-text="!passwordConfirmation ? 'Confirm your password.' : (passwordConfirmation === password ? 'Passwords match.' : 'Passwords do not match.')"></p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('parent.create-child.location') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
            <button type="submit"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                    :disabled="!upload.hasStored || uploadBusy"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60">
                Create Child Account →
            </button>
        </div>

        <div x-show="previewModalOpen"
             x-cloak
             @keydown.escape.window="closeUploadPreview()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 py-6">
            <div class="absolute inset-0" @click="closeUploadPreview()"></div>

            <div class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900" x-text="previewModalTitle || 'PSA Birth Certificate Preview'"></h3>
                    <button type="button" @click="closeUploadPreview()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-auto bg-gray-50 p-4">
                    <template x-if="previewModalType === 'image'">
                        <img :src="previewModalUrl" alt="PSA birth certificate preview" class="mx-auto max-h-[68vh] w-auto max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                    </template>

                    <template x-if="previewModalType === 'pdf'">
                        <iframe :src="previewModalUrl + '#toolbar=0&navpanes=0'" title="PSA birth certificate preview" class="h-[68vh] w-full rounded-lg border border-gray-200 bg-white"></iframe>
                    </template>

                    <template x-if="previewModalType === 'file'">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
                            <p class="text-sm text-gray-600">Inline preview is not available for this file type.</p>
                            <a :href="previewModalUrl" download class="mt-4 inline-flex rounded-lg bg-brand-purple-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Download file</a>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </form>

</x-auth-split-layout>
