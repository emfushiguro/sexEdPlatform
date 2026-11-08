@php
    $errorKeys = $errors->keys();
    $hasSpecificEvidenceError = collect($errorKeys)->contains(
        fn ($key) => str_contains($key, '.') && array_key_exists(explode('.', $key, 2)[0], $requirementCatalog)
    );
    $initialStep = $errors->any() ? 6 : 1;

    foreach ($errorKeys as $errorKey) {
        if ($errorKey === 'evidence' && $hasSpecificEvidenceError) {
            continue;
        }

        $field = explode('.', $errorKey, 2)[0];
        $step = match (true) {
            in_array($field, ['category', 'organization_type', 'organization_type_detail'], true) => 1,
            in_array($field, ['name', 'organization_email', 'contact_number', 'website_url', 'city_code', 'barangay_code', 'address_line', 'description'], true) => 2,
            $field === 'logo' => 3,
            in_array($field, ['representative_identity', 'representative_authority', 'reuse_representative_identity'], true) => 5,
            in_array($field, ['confirm_accuracy', 'verification_notes'], true) => 6,
            default => 4,
        };
        $initialStep = min($initialStep, $step);
    }

    $requirementKeysByGroup = collect($requirementCatalog)
        ->groupBy('rule_group', true)
        ->map(fn ($requirements) => $requirements->keys()->all())
        ->all();
@endphp

@extends('layouts.learner-app')

@section('title', 'Register Connector')

@section('content')
<section class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    <div
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        x-data="{
            currentStep: @js($initialStep),
            cityCode: @js(old('city_code', '')),
            selectedBarangayCode: @js(old('barangay_code', '')),
            barangays: @js($barangays),
            organizationTypes: @js($organizationTypes),
            requirementKeysByGroup: @js($requirementKeysByGroup),
            selectedOrganizationType: @js($selectedOrganizationType),
            previousOrganizationType: @js($selectedOrganizationType),
            reuseRepresentativeIdentity: @js((bool) old('reuse_representative_identity')),
            selectedEvidenceFiles: {},
            draggingEvidenceKey: null,
            logoPreviewUrl: null,
            isLoadingBarangays: false,

            init() {
                this.$nextTick(() => this.focusStepHeading());
            },

            availableRequirementKeys() {
                const type = this.organizationTypes[this.selectedOrganizationType];

                if (!type) {
                    return [];
                }

                return type.requirements || [];
            },

            isRequirementVisible(key) {
                return this.availableRequirementKeys().includes(key);
            },

            isGroupVisible(group) {
                return (this.requirementKeysByGroup[group] || []).some((key) => this.isRequirementVisible(key));
            },

            isRequirementDisabled(key) {
                return !this.isRequirementVisible(key) || (key === 'representative_identity' && this.reuseRepresentativeIdentity);
            },

            syncRequirements() {
                const nextKeys = this.availableRequirementKeys();
                const previousKeys = this.organizationTypes[this.previousOrganizationType]?.requirements || [];
                const removedKeys = previousKeys.filter((key) => !nextKeys.includes(key));
                const hasRemovedFiles = removedKeys.some((key) => {
                    const input = Array.from(this.$root.querySelectorAll('[data-requirement-file]'))
                        .find((element) => element.dataset.requirementFile === key);

                    return input && input.files && input.files.length > 0;
                });

                if (hasRemovedFiles && ! window.confirm('Changing the organization type will clear incompatible evidence selections. Continue?')) {
                    this.selectedOrganizationType = this.previousOrganizationType;

                    return;
                }

                removedKeys.forEach((key) => this.clearEvidenceFiles(key));
                this.previousOrganizationType = this.selectedOrganizationType;
            },

            evidenceFileChanged(key, event) {
                this.selectedEvidenceFiles[key] = Array.from(event.target.files || []).map((file) => file.name);
            },

            clearEvidenceFiles(key, restoreFocus = false) {
                const input = Array.from(this.$root.querySelectorAll('[data-requirement-file]'))
                    .find((element) => element.dataset.requirementFile === key);

                if (input) {
                    input.value = '';
                }

                this.selectedEvidenceFiles[key] = [];

                if (restoreFocus && input && ! input.disabled) {
                    this.$nextTick(() => input.focus());
                }
            },

            dropEvidenceFiles(key, event) {
                this.draggingEvidenceKey = null;

                const input = Array.from(this.$root.querySelectorAll('[data-requirement-file]'))
                    .find((element) => element.dataset.requirementFile === key);

                if (!input || this.isRequirementDisabled(key) || !event.dataTransfer?.files.length) {
                    return;
                }

                const transfer = new DataTransfer();
                Array.from(event.dataTransfer.files).forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
                this.evidenceFileChanged(key, { target: input });
            },

            async loadBarangays(cityCode) {
                this.cityCode = cityCode;
                this.selectedBarangayCode = '';
                this.barangays = [];

                if (!cityCode) {
                    return;
                }

                this.isLoadingBarangays = true;

                try {
                    const response = await fetch('/api/barangays/' + encodeURIComponent(cityCode));
                    this.barangays = response.ok ? await response.json() : [];
                } finally {
                    this.isLoadingBarangays = false;
                }
            },

            focusStepHeading() {
                const heading = Array.from(this.$root.querySelectorAll('[data-step-heading]'))
                    .find((element) => element.dataset.stepHeading === String(this.currentStep));

                heading?.focus();
            },

            goToStep(step) {
                this.currentStep = Math.min(6, Math.max(1, step));
                this.$nextTick(() => this.focusStepHeading());
            },

            continueStep() {
                const panel = Array.from(this.$root.querySelectorAll('[data-step-panel]'))
                    .find((element) => element.dataset.stepPanel === String(this.currentStep));
                const invalid = panel?.querySelector(':invalid');

                if (invalid) {
                    invalid.focus();
                    invalid.reportValidity();

                    return;
                }

                this.goToStep(this.currentStep + 1);
            },

            previewLogo(event) {
                this.revokeLogoPreview();
                const file = event.target.files?.[0];

                if (file) {
                    this.logoPreviewUrl = URL.createObjectURL(file);
                }
            },

            revokeLogoPreview() {
                if (this.logoPreviewUrl) {
                    URL.revokeObjectURL(this.logoPreviewUrl);
                    this.logoPreviewUrl = null;
                }
            },

            removeLogo() {
                this.revokeLogoPreview();

                const input = this.$root.querySelector('[data-logo-input]');

                if (input) {
                    input.value = '';
                }
            }
        }"
    >
        <div class="border-b border-brand-900/20 bg-gradient-to-br from-brand-500 via-brand-700 to-brand-900 px-5 py-7 text-white sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-100">Connector registration</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Build a trusted local partner profile.</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-brand-50/90">
                        Tell us who your organization is, where it operates in Cavite, and which documents support its authority to participate.
                    </p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-brand-50/90">
                    <span class="font-semibold text-white">One application.</span>
                    <span class="block text-xs text-brand-50/90">Your evidence stays private during review.</span>
                </div>
            </div>

            <ol class="mt-8 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6" aria-label="Registration progress">
                @foreach(range(1, 6) as $step)
                    <li>
                        <button
                            type="button"
                            data-step="{{ $step }}"
                            @click="goToStep({{ $step }})"
                            aria-current="{{ $initialStep === $step ? 'step' : 'false' }}"
                            :aria-current="currentStep === {{ $step }} ? 'step' : null"
                            class="flex min-h-11 w-full items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-left text-xs transition hover:border-brand-100/80 hover:bg-white/10"
                            :class="currentStep === {{ $step }} ? 'border-brand-100 bg-white/15 text-white' : 'text-brand-100/70'"
                        >
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-current font-semibold">{{ $step }}</span>
                            <span>{{ ['Organization type', 'Organization details', 'Logo', 'Organization evidence', 'Representative', 'Confirmation'][ $step - 1 ] }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </div>

        <form
            method="POST"
            action="{{ route('connectors.store') }}"
            enctype="multipart/form-data"
            class="space-y-8 p-5 sm:p-8"
        >
            @csrf

            <div class="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-950" aria-live="polite">
                <span x-show="currentStep === 1"><strong>Step 1 of 6:</strong> choose your organization type.</span>
                <span x-show="currentStep === 2"><strong>Step 2 of 6:</strong> share the organization’s Cavite location and contact details.</span>
                <span x-show="currentStep === 3"><strong>Step 3 of 6:</strong> add an optional visual mark for your public connector profile.</span>
                <span x-show="currentStep === 4"><strong>Step 4 of 6:</strong> upload evidence for your organization.</span>
                <span x-show="currentStep === 5"><strong>Step 5 of 6:</strong> verify the representative’s identity and authority.</span>
                <span x-show="currentStep === 6"><strong>Step 6 of 6:</strong> review and confirm your application.</span>
            </div>

            <fieldset data-step-panel="1" x-show="currentStep === 1" class="space-y-6">
                <legend
                    data-step-heading="1"
                    tabindex="-1"
                    class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none"
                >1. Organization type</legend>
                <p class="max-w-2xl text-sm leading-6 text-gray-600">
                    Choose the type that best describes your organization. This determines the verification evidence needed for your application.
                </p>

                <div class="max-w-xl">
                    <label>
                        <span class="text-sm font-semibold text-gray-800">Organization type <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <select
                            name="organization_type"
                            x-model="selectedOrganizationType"
                            @change="syncRequirements()"
                            aria-describedby="error-organization_type"
                            class="mt-2 min-h-11 w-full rounded-xl border-gray-300 bg-white focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="">Select organization type</option>
                            @foreach($organizationTypes as $value => $type)
                                <option value="{{ $value }}" @selected($selectedOrganizationType === $value)>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <p id="error-organization_type" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('organization_type'){{ $message }}@enderror</p>
                        <p x-show="selectedOrganizationType === 'school_educational_institution'" class="mt-2 text-sm leading-6 text-gray-600">Schools must provide organization identity evidence and at least one applicable DepEd, CHED, or TESDA authority document.</p>
                    </label>
                    <label x-show="selectedOrganizationType === 'other'" class="mt-4 block">
                        <span class="text-sm font-semibold text-gray-800">Please specify your organization type <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <input name="organization_type_detail" value="{{ old('organization_type_detail') }}" maxlength="255" :required="selectedOrganizationType === 'other'" :disabled="selectedOrganizationType !== 'other'" aria-describedby="error-organization_type_detail" class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500" placeholder="e.g. Independent youth center">
                        <p id="error-organization_type_detail" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('organization_type_detail'){{ $message }}@enderror</p>
                        <p class="mt-1 text-sm leading-6 text-gray-600">Provide evidence of your organization and your representative’s identity and authority. Reviewers may ask for additional documents suited to the type you specify.</p>
                    </label>
                </div>
            </fieldset>

            <fieldset data-step-panel="2" x-show="currentStep === 2" class="space-y-6">
                <legend
                    data-step-heading="2"
                    tabindex="-1"
                    class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none"
                >2. Organization information</legend>

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-gray-800">Connector / organization name <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <input id="name" name="name" value="{{ old('name') }}" aria-describedby="error-name" class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
                        <p id="error-name" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('name'){{ $message }}@enderror</p>
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-gray-800">Organization email <span class="text-xs font-normal text-gray-500">(optional)</span></span>
                        <input type="email" name="organization_email" value="{{ old('organization_email') }}" aria-describedby="error-organization_email" class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                        <p id="error-organization_email" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('organization_email'){{ $message }}@enderror</p>
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-gray-800">Contact number <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <input name="contact_number" value="{{ old('contact_number') }}" aria-describedby="error-contact_number" class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
                        <p id="error-contact_number" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('contact_number'){{ $message }}@enderror</p>
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-gray-800">Website / social link <span class="text-xs font-normal text-gray-500">(optional)</span></span>
                        <input type="url" name="website_url" value="{{ old('website_url') }}" aria-describedby="error-website_url" class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500">
                        <p id="error-website_url" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('website_url'){{ $message }}@enderror</p>
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-gray-800">Cavite City / Municipality <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <select
                            name="city_code"
                            x-model="cityCode"
                            @change="loadBarangays($event.target.value)"
                            aria-describedby="error-city_code"
                            class="mt-2 min-h-11 w-full rounded-xl border-gray-300 bg-white focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="">Select city</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->code }}" @selected(old('city_code') === $city->code)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        <p id="error-city_code" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('city_code'){{ $message }}@enderror</p>
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-gray-800">Barangay <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <select
                            name="barangay_code"
                            x-model="selectedBarangayCode"
                            aria-describedby="error-barangay_code"
                            class="mt-2 min-h-11 w-full rounded-xl border-gray-300 bg-white focus:border-brand-500 focus:ring-brand-500"
                            required
                            :disabled="!cityCode || isLoadingBarangays"
                        >
                            <option value="">Select barangay</option>
                            <option value="" x-show="isLoadingBarangays">Loading barangays...</option>
                            <template x-for="barangay in barangays" :key="barangay.code">
                                <option :value="barangay.code" x-text="barangay.name"></option>
                            </template>
                        </select>
                        <p id="error-barangay_code" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('barangay_code'){{ $message }}@enderror</p>
                    </label>

                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-gray-800">Address line <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <input name="address_line" value="{{ old('address_line') }}" aria-describedby="error-address_line" class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500" required>
                        <p id="error-address_line" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('address_line'){{ $message }}@enderror</p>
                    </label>

                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-gray-800">Organization description <span class="text-xs font-normal text-gray-500">(optional)</span></span>
                        <textarea name="description" rows="4" aria-describedby="error-description" class="mt-2 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500">{{ old('description') }}</textarea>
                        <p id="error-description" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('description'){{ $message }}@enderror</p>
                    </label>
                </div>
            </fieldset>

            <fieldset data-step-panel="3" x-show="currentStep === 3" class="space-y-6">
                <legend
                    data-step-heading="3"
                    tabindex="-1"
                    class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none"
                >3. Organization logo</legend>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-dashed border-gray-300 bg-white">
                            <span x-show="!logoPreviewUrl" class="px-3 text-center text-xs text-gray-400">No logo selected</span>
                            <img x-show="logoPreviewUrl" :src="logoPreviewUrl" alt="Selected organization logo preview" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <label class="inline-flex min-h-11 cursor-pointer items-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
                                Choose logo
                                <input
                                    type="file"
                                    name="logo"
                                    data-logo-input
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    @change="previewLogo($event)"
                                    aria-describedby="error-logo"
                                    class="sr-only"
                                >
                            </label>
                            <button type="button" @click="removeLogo()" class="ml-2 min-h-11 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-white">Remove</button>
                            <p class="mt-2 text-xs leading-5 text-gray-600">Optional JPEG, PNG, or WebP. Maximum 2 MB.</p>
                            <p class="text-xs leading-5 text-gray-500">This logo is for the public connector profile; it is not verification evidence.</p>
                            <p id="error-logo" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('logo'){{ $message }}@enderror</p>
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset data-step-panel="4" x-show="currentStep === 4" class="space-y-6">
                <legend
                    data-step-heading="4"
                    tabindex="-1"
                    class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none"
                >4. Organization evidence</legend>
                <p class="max-w-3xl text-sm leading-6 text-gray-600">
                    Upload only the documents relevant to your selected organization type. Files stay private during review.
                </p>
                <p x-show="selectedOrganizationType === 'school_educational_institution'" x-cloak class="text-sm leading-6 text-gray-600">For regulatory eligibility, upload at least one applicable DepEd, CHED, or TESDA authority document. You do not need all three.</p>
                <p x-show="!selectedOrganizationType" x-cloak class="rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-950">Choose an organization type in Step 1 to see its documents.</p>

                <p id="error-evidence" class="min-h-5 text-sm text-rose-700" role="alert">@error('evidence'){{ $message }}@enderror</p>

                @foreach($requirementKeysByGroup as $groupKey => $keys)
                    @continue(in_array($groupKey, ['representative_identity', 'representative_authority'], true))
                    <section x-show="isGroupVisible('{{ $groupKey }}')" x-cloak class="space-y-4 rounded-2xl border border-gray-200 p-4 sm:p-5">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $groupKey)) }}</h3>
                            <p class="mt-1 text-xs leading-5 text-gray-500">Only documents applicable to your selected organization type appear here.</p>
                        </div>

                        @foreach($keys as $key)
                            @php($requirement = $requirementCatalog[$key])
                            @include('connectors.partials.evidence-card')
                        @endforeach
                    </section>
                @endforeach

                <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-950">
                    If a file is rejected, browsers cannot restore the upload for security reasons. You will need to select that file again when resubmitting.
                </p>
            </fieldset>

            <fieldset data-step-panel="5" x-show="currentStep === 5" class="space-y-6">
                <legend
                    data-step-heading="5"
                    tabindex="-1"
                    class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none"
                >5. Representative verification</legend>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Representative account</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-gray-600">{{ auth()->user()->email }}</p>
                    <p class="mt-3 text-sm leading-6 text-gray-600">This signed-in account will be the primary representative and pending Owner of the connector application.</p>
                </div>

                <label class="flex items-start gap-3 rounded-2xl border border-gray-200 p-4">
                    <input
                        type="checkbox"
                        name="reuse_representative_identity"
                        value="1"
                        x-model="reuseRepresentativeIdentity"
                        @change="if (reuseRepresentativeIdentity) clearEvidenceFiles('representative_identity')"
                        @checked(old('reuse_representative_identity'))
                        class="mt-1 h-5 w-5 rounded border-gray-300 focus:border-brand-500 focus:ring-brand-500"
                    >
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Reuse my approved learner identity case</span>
                        <span class="mt-1 block text-sm leading-6 text-gray-600">If eligible, reviewers will reference that case without copying its private files. Representative authority evidence is still required.</span>
                    </span>
                </label>
                <p id="error-reuse_representative_identity" class="min-h-5 text-xs text-rose-700" role="alert">@error('reuse_representative_identity'){{ $message }}@enderror</p>
                <p x-show="reuseRepresentativeIdentity" x-cloak class="text-sm text-amber-900">To upload an identity document, turn off approved identity reuse.</p>

                @foreach(['representative_identity' => 'Representative identity', 'representative_authority' => 'Representative authority'] as $groupKey => $groupLabel)
                    <section x-show="isGroupVisible('{{ $groupKey }}')" x-cloak class="space-y-4 rounded-2xl border border-gray-200 p-4 sm:p-5">
                        <h3 class="text-base font-semibold text-gray-900">{{ $groupLabel }}</h3>
                        @foreach($requirementCatalog as $key => $requirement)
                            @if($requirement['rule_group'] === $groupKey)
                                @include('connectors.partials.evidence-card')
                            @endif
                        @endforeach
                    </section>
                @endforeach

                <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-950">If the form has a validation error, browsers cannot restore selected files. Choose them again before submitting.</p>
            </fieldset>

            <fieldset data-step-panel="6" x-show="currentStep === 6" class="space-y-6">
                <legend data-step-heading="6" tabindex="-1" class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none">6. Confirmation</legend>

                <label class="block">
                    <span class="text-sm font-semibold text-gray-800">Verification notes <span class="text-xs font-normal text-gray-500">(optional)</span></span>
                    <textarea name="verification_notes" rows="3" aria-describedby="error-verification_notes" class="mt-2 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500">{{ old('verification_notes') }}</textarea>
                    <p id="error-verification_notes" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('verification_notes'){{ $message }}@enderror</p>
                </label>

                <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                    <h3 class="font-semibold text-brand-950">Before you submit</h3>
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-brand-950">
                        <li>Your organization information and evidence are accurate and current.</li>
                        <li>Representative authority evidence is included even when identity reuse is selected.</li>
                        <li>Uploaded evidence remains private to the verification workflow.</li>
                    </ul>
                </div>

                <label class="flex items-start gap-3 rounded-2xl border border-gray-300 p-4">
                    <input
                        type="checkbox"
                        name="confirm_accuracy"
                        value="1"
                        @checked(old('confirm_accuracy'))
                        aria-describedby="error-confirm_accuracy"
                        class="mt-1 h-5 w-5 rounded border-gray-300 focus:border-brand-500 focus:ring-brand-500"
                        required
                    >
                    <span class="text-sm leading-6 text-gray-800">I confirm that the information and evidence submitted here are accurate to the best of my knowledge.</span>
                </label>
                <p id="error-confirm_accuracy" class="min-h-5 text-xs text-rose-700" role="alert">@error('confirm_accuracy'){{ $message }}@enderror</p>
            </fieldset>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    @click="goToStep(currentStep - 1)"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >Back</button>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        x-show="currentStep < 6"
                        @click="continueStep()"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
                    >Continue</button>
                    <button
                        type="submit"
                        x-show="currentStep === 6"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
                    >Submit application</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
