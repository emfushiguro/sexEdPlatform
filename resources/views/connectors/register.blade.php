@php
    $initialStep = 1;

    foreach ($errors->keys() as $errorKey) {
        if (str_starts_with($errorKey, 'evidence')) {
            $initialStep = 4;
            break;
        }

        if (in_array($errorKey, ['reuse_representative_identity', 'confirm_accuracy', 'verification_notes'], true)) {
            $initialStep = max($initialStep, 5);
        } elseif ($errorKey === 'logo') {
            $initialStep = max($initialStep, 3);
        } elseif (in_array($errorKey, ['name', 'organization_email', 'contact_number', 'website_url', 'city_code', 'barangay_code', 'address_line', 'description'], true)) {
            $initialStep = max($initialStep, 2);
        }
    }

    $enabledRequirementKeys = $initialRequirementKeys === []
        ? array_keys($requirementCatalog)
        : $initialRequirementKeys;
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
            categories: @js($categories),
            organizationTypes: @js($organizationTypes),
            selectedCategory: @js(old('category', '')),
            selectedOrganizationType: @js(old('organization_type', '')),
            activeRequirementKeys: @js($enabledRequirementKeys),
            previousCategory: @js(old('category', '')),
            previousOrganizationType: @js(old('organization_type', '')),
            logoPreviewUrl: null,
            isLoadingBarangays: false,

            init() {
                this.$nextTick(() => this.focusStepHeading());
            },

            availableRequirementKeys() {
                const type = this.organizationTypes[this.selectedOrganizationType];

                if (!type || !type.categories.includes(this.selectedCategory)) {
                    return [];
                }

                return type.requirements || [];
            },

            isRequirementVisible(key) {
                return this.activeRequirementKeys.includes(key);
            },

            syncRequirements() {
                const nextKeys = this.availableRequirementKeys();
                const removedKeys = this.activeRequirementKeys.filter((key) => !nextKeys.includes(key));
                const hasRemovedFiles = removedKeys.some((key) => {
                    const input = Array.from(this.$root.querySelectorAll('[data-requirement-file]'))
                        .find((element) => element.dataset.requirementFile === key);

                    return input && input.files && input.files.length > 0;
                });

                if (hasRemovedFiles && ! window.confirm('Changing the organization type will clear incompatible evidence selections. Continue?')) {
                    this.selectedCategory = this.previousCategory;
                    this.selectedOrganizationType = this.previousOrganizationType;

                    return;
                }

                this.activeRequirementKeys = nextKeys;
                this.previousCategory = this.selectedCategory;
                this.previousOrganizationType = this.selectedOrganizationType;
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
                this.currentStep = Math.min(5, Math.max(1, step));
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

            <ol class="mt-8 grid gap-2 sm:grid-cols-5" aria-label="Registration progress">
                @foreach(range(1, 5) as $step)
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
                            <span>{{ ['Organization type', 'Organization details', 'Logo', 'Evidence', 'Representative'][ $step - 1 ] }}</span>
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
                <span x-show="currentStep === 1"><strong>Step 1 of 5:</strong> choose the organization category and legal or operating type.</span>
                <span x-show="currentStep === 2"><strong>Step 2 of 5:</strong> share the organization’s Cavite location and contact details.</span>
                <span x-show="currentStep === 3"><strong>Step 3 of 5:</strong> add an optional visual mark for your public connector profile.</span>
                <span x-show="currentStep === 4"><strong>Step 4 of 5:</strong> attach the evidence reviewers will use to verify the application.</span>
                <span x-show="currentStep === 5"><strong>Step 5 of 5:</strong> confirm the representative account and submit.</span>
            </div>

            <fieldset data-step-panel="1" x-show="currentStep === 1" class="space-y-6">
                <legend
                    data-step-heading="1"
                    tabindex="-1"
                    class="text-xl font-semibold tracking-tight text-gray-900 focus:outline-none"
                >1. Organization type</legend>
                <p class="max-w-2xl text-sm leading-6 text-gray-600">
                    Category describes the work your connector supports. Organization type describes the legal, institutional, or community form that is applying.
                </p>

                <div class="grid gap-5 md:grid-cols-2">
                    <label>
                        <span class="text-sm font-semibold text-gray-800">Category <span class="text-rose-600" aria-hidden="true">*</span></span>
                        <select
                            name="category"
                            x-model="selectedCategory"
                            @change="syncRequirements()"
                            aria-describedby="error-category"
                            class="mt-2 min-h-11 w-full rounded-xl border-gray-300 bg-white focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="">Select category</option>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p id="error-category" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('category'){{ $message }}@enderror</p>
                    </label>

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
                                <option value="{{ $value }}" @selected(old('organization_type') === $value)>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <p id="error-organization_type" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('organization_type'){{ $message }}@enderror</p>
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
                >4. Verification evidence</legend>
                <p class="max-w-3xl text-sm leading-6 text-gray-600">
                    Upload only the evidence relevant to your selected organization type. Files are stored privately for reviewers; accepted evidence is retained with the verification history.
                </p>

                <p id="error-evidence" class="min-h-5 text-sm text-rose-700" role="alert">@error('evidence'){{ $message }}@enderror</p>

                @foreach(['organization_identity' => 'Organization identity', 'regulatory_eligibility' => 'Regulatory eligibility', 'representative_identity' => 'Representative identity', 'representative_authority' => 'Representative authority', 'supporting_evidence' => 'Supporting evidence'] as $groupKey => $groupLabel)
                    <section class="space-y-4 rounded-2xl border border-gray-200 p-4 sm:p-5">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ $groupLabel }}</h3>
                            <p class="mt-1 text-xs leading-5 text-gray-500">Configured requirements appear below. Optional requirements may not apply to every organization.</p>
                        </div>

                        @foreach($requirementCatalog as $key => $requirement)
                            @if($requirement['rule_group'] === $groupKey)
                                @php($isInitiallyEnabled = in_array($key, $enabledRequirementKeys, true))
                                <article
                                    x-show="isRequirementVisible('{{ $key }}')"
                                    data-requirement-key="{{ $key }}"
                                    class="rounded-xl border border-gray-200 bg-white p-4"
                                >
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ $requirement['label'] }}</h4>
                                            <p class="mt-1 text-sm leading-6 text-gray-600">{{ $requirement['guidance'] }}</p>
                                        </div>
                                        <span class="shrink-0 text-xs font-semibold {{ $requirement['required'] ? 'text-rose-700' : 'text-gray-500' }}">
                                            {{ $requirement['required'] ? 'Required' : 'Optional' }}
                                        </span>
                                    </div>

                                    @if($requirement['issuing_authority'])
                                        <p class="mt-3 text-xs text-gray-500">Typical issuer: {{ $requirement['issuing_authority'] }}</p>
                                    @endif

                                    <label class="mt-4 block">
                                        <span class="text-sm font-semibold text-gray-800">Upload file{{ ($requirement['maximum_evidence_count'] ?? 1) > 1 ? 's' : '' }}</span>
                                        <input
                                            type="file"
                                            name="evidence[{{ $key }}][files][]"
                                            data-requirement-file="{{ $key }}"
                                            multiple
                                            accept="{{ implode(',', $requirement['accepted_mime_types']) }}"
                                            @disabled(! $isInitiallyEnabled)
                                            x-bind:disabled="!isRequirementVisible('{{ $key }}')"
                                            aria-describedby="error-evidence-{{ $key }}-files"
                                            class="mt-2 block min-h-11 w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 p-3 text-sm text-gray-700"
                                        >
                                        <span class="mt-1 block text-xs text-gray-500">PDF, JPEG, PNG, or WebP; {{ number_format($requirement['max_kilobytes'] / 1024, 1) }} MB maximum per file.</span>
                                        <p id="error-evidence-{{ $key }}-files" class="mt-1 min-h-5 text-xs text-rose-700" role="alert">@error('evidence.'.$key.'.files'){{ $message }}@enderror</p>
                                    </label>

                                    @foreach($requirement['metadata_fields'] as $metadataField)
                                        <label class="mt-3 block">
                                            <span class="text-sm font-semibold text-gray-800">{{ str($metadataField)->replace('_', ' ')->title() }}</span>
                                            <input
                                                type="{{ in_array($metadataField, ['issue_date', 'expiry_date'], true) ? 'date' : 'text' }}"
                                                name="evidence[{{ $key }}][{{ $metadataField }}]"
                                                value="{{ old('evidence.'.$key.'.'.$metadataField) }}"
                                                @disabled(! $isInitiallyEnabled)
                                                x-bind:disabled="!isRequirementVisible('{{ $key }}')"
                                                class="mt-2 min-h-11 w-full rounded-xl border-gray-300 focus:border-brand-500 focus:ring-brand-500"
                                            >
                                        </label>
                                    @endforeach
                                </article>
                            @endif
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
                >5. Representative and confirmation</legend>

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
                        @checked(old('reuse_representative_identity'))
                        class="mt-1 h-5 w-5 rounded border-gray-300 focus:border-brand-500 focus:ring-brand-500"
                    >
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">Reuse my approved learner identity case</span>
                        <span class="mt-1 block text-sm leading-6 text-gray-600">If eligible, reviewers will reference that case without copying its private files. Representative authority evidence is still required.</span>
                    </span>
                </label>
                <p id="error-reuse_representative_identity" class="min-h-5 text-xs text-rose-700" role="alert">@error('reuse_representative_identity'){{ $message }}@enderror</p>

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
                        x-show="currentStep < 5"
                        @click="continueStep()"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
                    >Continue</button>
                    <button
                        type="submit"
                        x-show="currentStep === 5"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800"
                    >Submit application</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
