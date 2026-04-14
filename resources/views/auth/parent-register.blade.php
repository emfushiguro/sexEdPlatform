<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Guide their journey</h2>
            <p class="text-white/80 text-lg max-w-xs">Register as a parent or guardian</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

    <!-- Header -->
    <div class="mb-5">
        <h2 class="text-2xl font-bold text-purple-900">Personal Information</h2>
        <p class="mt-1 text-sm text-gray-500">Tell us about yourself</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-5 rounded-lg">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $existingGovernmentPath = $tempGovernmentIdUpload['path'] ?? ($parentInfo['government_id_path'] ?? null);
        $existingGovernmentName = $tempGovernmentIdUpload['original_name'] ?? ($existingGovernmentPath ? basename($existingGovernmentPath) : null);
        $existingGovernmentPreviewUrl = $tempGovernmentIdUpload['preview_url'] ?? ($existingGovernmentPath ? asset('storage/' . $existingGovernmentPath) : null);
    @endphp

    <form method="POST" action="{{ route('parent.register.store') }}" enctype="multipart/form-data"
          x-data="{
              birthdate: '{{ old('birthdate', $parentInfo['birthdate'] ?? '') }}',
              age: null,
              uploadBusy: false,
              uploadError: '',
              upload: {
                  hasStored: @js(!empty($existingGovernmentPath)),
                  path: @js($existingGovernmentPath ?? ''),
                  originalName: @js($existingGovernmentName ?? ''),
                  previewUrl: @js($existingGovernmentPreviewUrl ?? ''),
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
                  this.previewModalTitle = name || 'Government ID Preview';
                  this.previewModalType = this.resolvePreviewType(name, url);
                  this.previewModalOpen = true;
              },
              closeUploadPreview() {
                  this.previewModalOpen = false;
                  this.previewModalUrl = '';
                  this.previewModalType = 'file';
                  this.previewModalTitle = '';
              },
              calculateAge() {
                  if (!this.birthdate) { this.age = null; return; }
                  const today = new Date();
                  const birth = new Date(this.birthdate);
                  let age = today.getFullYear() - birth.getFullYear();
                  const m = today.getMonth() - birth.getMonth();
                  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
                  this.age = age;
              },
              csrfToken() {
                  return document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
              },
              async uploadGovernmentId(event) {
                  const file = event.target.files?.[0];
                  this.uploadError = '';

                  if (!file) {
                      return;
                  }

                  this.uploadBusy = true;

                  try {
                      const formData = new FormData();
                      formData.append('government_id', file);

                      const response = await fetch(@js(route('parent.register.temp-upload')), {
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
                          throw new Error(firstError || payload.message || 'Unable to upload government ID.');
                      }

                      this.upload.hasStored = true;
                      this.upload.path = payload?.upload?.path || '';
                      this.upload.originalName = payload?.upload?.original_name || file.name;
                      this.upload.previewUrl = payload?.upload?.preview_url || '';
                  } catch (error) {
                      this.uploadError = error.message || 'Unable to upload government ID.';
                  } finally {
                      this.uploadBusy = false;
                      event.target.value = '';
                  }
              },
              async removeGovernmentIdUpload() {
                  this.uploadError = '';
                  this.uploadBusy = true;

                  try {
                      const response = await fetch(@js(route('parent.register.temp-upload.remove')), {
                          method: 'DELETE',
                          headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest',
                              'X-CSRF-TOKEN': this.csrfToken(),
                          },
                      });

                      const payload = await response.json().catch(() => ({}));

                      if (!response.ok) {
                          throw new Error(payload.message || 'Unable to remove uploaded government ID.');
                      }

                      this.upload.hasStored = false;
                      this.upload.path = '';
                      this.upload.originalName = '';
                      this.upload.previewUrl = '';
                      this.closeUploadPreview();

                      if (this.$refs.governmentIdInput) {
                          this.$refs.governmentIdInput.value = '';
                      }
                  } catch (error) {
                      this.uploadError = error.message || 'Unable to remove uploaded government ID.';
                  } finally {
                      this.uploadBusy = false;
                  }
              }
          }"
          x-init="calculateAge()">
        @csrf

        <div class="space-y-4">

            <!-- First Name + Last Name -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input id="first_name" name="first_name" type="text" required value="{{ old('first_name', $parentInfo['first_name'] ?? '') }}"
                           placeholder="Juan"
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input id="last_name" name="last_name" type="text" required value="{{ old('last_name', $parentInfo['last_name'] ?? '') }}"
                           placeholder="dela Cruz"
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Middle Initial + Suffix -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">Middle Initial <span class="text-gray-400 font-normal text-xs">(Optional)</span></label>
                    <input id="middle_initial" name="middle_initial" type="text" value="{{ old('middle_initial', $parentInfo['middle_initial'] ?? '') }}"
                           maxlength="10" placeholder="D."
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    @error('middle_initial')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-gray-400 font-normal text-xs">(Optional)</span></label>
                    <select id="suffix" name="suffix"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                        @php $psfx = old('suffix', $parentInfo['suffix'] ?? ''); @endphp
                        <option value="">-- None --</option>
                        <option value="Jr." {{ $psfx == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                        <option value="Sr." {{ $psfx == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                        <option value="II"  {{ $psfx == 'II'  ? 'selected' : '' }}>II</option>
                        <option value="III" {{ $psfx == 'III' ? 'selected' : '' }}>III</option>
                        <option value="IV"  {{ $psfx == 'IV'  ? 'selected' : '' }}>IV</option>
                        <option value="V"   {{ $psfx == 'V'   ? 'selected' : '' }}>V</option>
                    </select>
                    @error('suffix')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Birthdate -->
            <div>
                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                <input id="birthdate" name="birthdate" type="date"
                       x-model="birthdate" @change="calculateAge()"
                       value="{{ old('birthdate', $parentInfo['birthdate'] ?? '') }}" required
                       min="{{ now()->subYears(100)->format('Y-m-d') }}"
                       max="{{ now()->subYears(18)->format('Y-m-d') }}"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">

                <div x-show="age !== null" class="mt-2">
                    <div x-show="age >= 18" class="flex items-center text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        You are <strong class="mx-1" x-text="age"></strong> years old — eligible!
                    </div>
                    <div x-show="age < 18" class="flex items-center text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                        <svg class="w-4 h-4 mr-1.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        You must be 18 or older to register as a parent.
                    </div>
                </div>
                @error('birthdate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Government ID Upload -->
            <div>
                <label for="government_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Government-Issued ID <span class="text-red-500">*</span>
                </label>
                <input id="government_id" name="government_id" type="file"
                       accept=".jpg,.jpeg,.png,.pdf"
                       x-ref="governmentIdInput"
                       :required="!upload.hasStored"
                       @change="uploadGovernmentId($event)"
                       :disabled="uploadBusy"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 file:mr-3 file:rounded-lg file:border-0 file:bg-purple-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-purple-700">
                <p class="mt-1 text-xs text-gray-500">Upload one valid government ID (JPG, PNG, or PDF, max 5MB).</p>

                <p x-show="uploadError" x-cloak class="mt-2 text-xs text-red-600" x-text="uploadError"></p>

                <div x-show="upload.hasStored" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3" data-testid="parent-government-id-preview">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-emerald-800">Uploaded file ready for submission</p>
                            <p class="mt-1 text-xs text-emerald-700 break-all" x-text="upload.originalName || 'Uploaded document'"></p>
                        </div>
                        <button type="button"
                                @click="removeGovernmentIdUpload()"
                                :disabled="uploadBusy"
                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
                                aria-label="Remove uploaded government ID">
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

                @if(!empty($existingGovernmentPath))
                    <div class="hidden mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3" data-testid="parent-government-id-preview">
                        <p class="text-xs font-semibold text-emerald-800">Uploaded file ready for submission</p>
                        <p class="mt-1 text-xs text-emerald-700">{{ $existingGovernmentName }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            @if($existingGovernmentPreviewUrl)
                                <a href="{{ $existingGovernmentPreviewUrl }}" class="inline-flex rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                    Preview
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @error('government_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Continue button -->
            <div class="pt-2">
                <button type="submit"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                        class="w-full flex items-center justify-center gap-2 px-8 py-3 text-sm font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
                    Continue
                </button>
                <div class="mt-4 text-center text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-brand-purple-primary font-medium hover:underline">Login</a>
                </div>
            </div>

            <div x-show="previewModalOpen"
                 x-cloak
                 @keydown.escape.window="closeUploadPreview()"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 px-4 py-6">
                <div class="absolute inset-0" @click="closeUploadPreview()"></div>

                <div class="relative z-10 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900" x-text="previewModalTitle || 'Government ID Preview'"></h3>
                        <button type="button" @click="closeUploadPreview()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[75vh] overflow-auto bg-gray-50 p-4">
                        <template x-if="previewModalType === 'image'">
                            <img :src="previewModalUrl" alt="Government ID preview" class="mx-auto max-h-[68vh] w-auto max-w-full rounded-lg border border-gray-200 bg-white object-contain">
                        </template>

                        <template x-if="previewModalType === 'pdf'">
                            <iframe :src="previewModalUrl + '#toolbar=0&navpanes=0'" title="Government ID preview" class="h-[68vh] w-full rounded-lg border border-gray-200 bg-white"></iframe>
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

        </div>
    </form>

</x-auth-split-layout>
