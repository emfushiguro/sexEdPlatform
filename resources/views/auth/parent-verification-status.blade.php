<x-auth-split-layout :showTabs="false">
    @php
        $status = $user->parent_verification_status ?? 'pending';
        $isApproved = $isApproved ?? ($status === 'approved');
        $showApprovedModal = (bool) session('show_parent_approved_modal', false);
        $parentReasonRaw = (string) ($user->parent_verification_rejection_reason ?? '');
        $parentRejectionReasonText = trim((string) preg_replace(
            '/\s+/u',
            ' ',
            str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($parentReasonRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        ));
    @endphp

    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Conscious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">
                {{ $isApproved ? 'Verification Approved' : 'Parent Verification' }}
            </h2>
            <p class="text-white/80 text-lg max-w-xs">
                {{ $isApproved
                    ? 'Your parent account is approved. You can start learning or create a child account.'
                    : 'Your account is being reviewed by an administrator.' }}
            </p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Verification Status</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ $isApproved
                ? 'Your parent verification is complete. Choose what you want to do next.'
                : 'We are reviewing your parent/guardian identity document.' }}
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($isApproved)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-4">
            <p class="font-semibold">Verification result: Approved</p>
            <p class="mt-1">Your parent account is now active. You can continue learning and manage child accounts.</p>
        </div>
    @elseif($status === 'rejected')
        <div class="mb-4 overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm">
            <div class="border-b border-red-100 bg-red-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-700" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 114.906 3.765a.75.75 0 11-.812 1.26A6.5 6.5 0 1016.5 10a.75.75 0 011.5 0zm-8.75-3.25a.75.75 0 011.5 0v3a.75.75 0 01-1.5 0v-3zM10 13a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-red-800">Verification result: Rejected</p>
                        <p class="mt-0.5 text-xs text-red-700">Your last submission needs correction before we can continue verification.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3 px-4 py-4 text-sm text-gray-700">
                <div class="rounded-xl border border-red-100 bg-red-50/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Admin Feedback</p>
                    @if($parentRejectionReasonText !== '')
                        <p class="mt-1 break-words text-sm text-red-900">{{ $parentRejectionReasonText }}</p>
                    @else
                        <p class="mt-1 text-sm text-red-900">No reason provided by administrator.</p>
                    @endif
                </div>

                <div class="rounded-xl border border-purple-100 bg-purple-50/60 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-purple-800">Next Steps</p>
                    <ul class="mt-1 space-y-1 text-xs text-purple-900">
                        <li>1. Prepare a clear and readable government-issued ID.</li>
                        <li>2. Ensure details are complete and not cropped.</li>
                        <li>3. Upload and submit for another admin review.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mb-6"
             x-data="{
                showResubmitForm: {{ $errors->has('government_id') ? 'true' : 'false' }},
                upload: {
                    originalName: '',
                    previewUrl: '',
                    mimeType: '',
                    hasStored: false,
                },
                imagePreviewUrl: '',
                uploadError: '',
                isImageUpload(name, mimeType = '') {
                    if ((mimeType || '').toLowerCase().startsWith('image/')) {
                        return true;
                    }

                    const lowerName = (name || '').toLowerCase();
                    return ['.jpg', '.jpeg', '.png', '.gif', '.webp'].some((ext) => lowerName.endsWith(ext));
                },
                onResubmitGovernmentIdChange(event) {
                    this.uploadError = '';
                    const file = event.target.files && event.target.files.length ? event.target.files[0] : null;

                    if (!file) {
                        this.removeResubmitGovernmentId();
                        return;
                    }

                    this.upload.originalName = file.name;
                    this.upload.mimeType = file.type || '';
                    this.upload.hasStored = true;

                    if (this.upload.previewUrl) {
                        URL.revokeObjectURL(this.upload.previewUrl);
                    }

                    this.upload.previewUrl = this.isImageUpload(file.name, file.type) ? URL.createObjectURL(file) : '';
                },
                removeResubmitGovernmentId() {
                    if (this.upload.previewUrl) {
                        URL.revokeObjectURL(this.upload.previewUrl);
                    }

                    this.closeImagePreview();

                    this.upload.originalName = '';
                    this.upload.previewUrl = '';
                    this.upload.mimeType = '';
                    this.upload.hasStored = false;

                    if (this.$refs.resubmitGovernmentIdInput) {
                        this.$refs.resubmitGovernmentIdInput.value = '';
                    }
                },
                openImagePreview(url) {
                    if (!url) {
                        return;
                    }

                    this.imagePreviewUrl = url;
                },
                closeImagePreview() {
                    this.imagePreviewUrl = '';
                }
             }">
            <button type="button"
                    @click="showResubmitForm = !showResubmitForm"
                    :aria-expanded="showResubmitForm.toString()"
                    class="w-full inline-flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 2.75a.75.75 0 00-1.5 0v6.44L7.53 7.47a.75.75 0 10-1.06 1.06l3 3a.75.75 0 001.06 0l3-3a.75.75 0 10-1.06-1.06l-1.72 1.72V2.75z" />
                        <path d="M3.5 12.75A1.75 1.75 0 015.25 11h9.5a1.75 1.75 0 011.75 1.75v3A1.75 1.75 0 0114.75 17h-9.5A1.75 1.75 0 013.5 15.75v-3z" />
                    </svg>
                    <span>Resubmit Parent Verification</span>
                </span>
                <svg class="h-4 w-4 transition-transform" :class="showResubmitForm ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>

            <form x-show="showResubmitForm"
                  x-transition.opacity.duration.200ms
                  x-cloak
                  method="POST"
                  action="{{ route('parent.verification.resubmit') }}"
                  enctype="multipart/form-data"
                  class="mt-3 space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                @csrf

                <div>
                    <label for="government_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Corrected Government-Issued ID <span class="text-red-500">*</span>
                    </label>
                    <div class="rounded-xl border border-dashed border-purple-300 bg-purple-50/40 p-3">
                        <div class="relative">
                            <input id="government_id"
                                   name="government_id"
                                   type="file"
                                   required
                                   x-ref="resubmitGovernmentIdInput"
                                   accept=".jpg,.jpeg,.png,.pdf"
                                   @change="onResubmitGovernmentIdChange($event)"
                                   class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-1.5">
                                <span class="inline-flex items-center rounded-lg bg-purple-50 px-4 py-2.5 text-sm font-semibold text-purple-700">Choose File</span>
                                <span class="min-w-0 truncate text-sm text-gray-500" x-text="upload.originalName || 'Upload corrected government ID'" ></span>
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-gray-600">Accepted formats: JPG, PNG, PDF. Maximum file size: 5MB.</p>

                        <div x-show="upload.hasStored" x-cloak class="mt-2 p-2 bg-white rounded-lg border border-gray-100 flex items-center gap-3" data-testid="parent-resubmit-government-id-preview">
                            <template x-if="upload.previewUrl && isImageUpload(upload.originalName, upload.mimeType)">
                                <div class="relative group cursor-zoom-in" @click.stop="openImagePreview(upload.previewUrl)">
                                    <img :src="upload.previewUrl" class="h-10 w-10 object-cover rounded-md border border-gray-200 group-hover:opacity-75 transition-opacity" alt="Corrected government ID preview">
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                        </svg>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!(upload.previewUrl && isImageUpload(upload.originalName, upload.mimeType))">
                                <div class="h-10 w-10 flex items-center justify-center bg-gray-200 rounded-md border border-gray-300 text-gray-500">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </template>
                            <div class="min-w-0 flex-1">
                                <span x-text="upload.originalName || 'Uploaded document'" class="block text-xs text-gray-600 font-medium truncate"></span>
                            </div>
                            <button type="button"
                                    @click="removeResubmitGovernmentId()"
                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100"
                                    aria-label="Remove selected corrected government ID">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <p x-show="upload.hasStored"
                           x-cloak
                           x-text="'Selected file: ' + upload.originalName"
                           class="mt-1 text-xs font-medium text-purple-800"></p>
                    </div>

                    @error('government_id')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">After submitting, your status will return to pending review.</p>
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
                            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Submit Resubmission
                    </button>
                </div>
            </form>

            <div x-show="imagePreviewUrl"
                 x-cloak
                 @keydown.escape.window="closeImagePreview()"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 py-6">
                <div class="absolute inset-0" @click="closeImagePreview()"></div>

                <div class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">Corrected ID Preview</h3>
                        <button type="button" @click="closeImagePreview()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Close image preview">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[75vh] overflow-auto bg-gray-50 p-4">
                        <img :src="imagePreviewUrl" alt="Corrected government ID preview" class="mx-auto max-h-[68vh] w-auto max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
            <p class="font-semibold">Verification result: Pending Review</p>
            <p class="mt-1">You will receive an email once your parent account has been approved.</p>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            While this is pending, child account creation and parent management features are disabled.
        </p>
    @endif

    <div class="space-y-3" x-data="{ showApprovedModal: {{ $isApproved && $showApprovedModal ? 'true' : 'false' }} }">
        @if($isApproved)
            <a href="{{ route('learner.dashboard') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
               style="background: linear-gradient(135deg, #0EA5E9, #2563EB);">
                Start Learning
            </a>

            <a href="{{ route('parent.create-child') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
               style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                Create Child Account
            </a>

            <div x-cloak
                 x-show="showApprovedModal"
                 x-transition.opacity
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                     @click.away="showApprovedModal = false">
                    <h3 class="text-xl font-bold text-purple-900">Parent Verification Approved</h3>
                    <p class="mt-2 text-sm text-gray-600">Your parent account is now active. Choose what you want to do next.</p>

                    <div class="mt-5 space-y-3">
                        <a href="{{ route('learner.dashboard') }}"
                           class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
                           style="background: linear-gradient(135deg, #0EA5E9, #2563EB);">
                            Start Learning
                        </a>

                        <a href="{{ route('parent.create-child') }}"
                           class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
                           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            Create Child Account
                        </a>
                    </div>

                </div>
            </div>
        @else
            <a href="https://mail.google.com"
               target="_blank"
               rel="noopener"
               class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                Open Gmail Inbox
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                Log Out
            </button>
        </form>
    </div>
</x-auth-split-layout>
