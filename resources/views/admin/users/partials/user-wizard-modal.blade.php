@php
    $wizardTitle = $title ?? 'User Wizard';
    $wizardSubtitle = $subtitle ?? 'Configure user details and access controls.';
    $formMethod = strtoupper($method ?? 'POST');
    $isOverlay = (bool) ($asOverlay ?? false);
@endphp

<div
    x-data="{
        currentStep: Number(@js((int) old('wizard_step', 1))),
        maxStep: 4,
        selectedRole: @js((string) ($selectedRole ?? old('role', ''))),
        rolePermissionMap: @js($rolePermissionMap),
        showPassword: false,
        showPasswordConfirmation: false,
        showRolePermissions: false,
        showPermissionOverrides: false,
        showReviewPermissions: false,
        form: {
            name: @js((string) old('name', $user->name ?? '')),
            email: @js((string) old('email', $user->email ?? '')),
            birthdate: @js((string) old('birthdate', isset($user) ? optional($user->birthdate)->format('Y-m-d') : '')),
            status: @js((string) old('status', $selectedStatus ?? 'active')),
            directPermissions: @js(array_values(old('direct_permissions', $directPermissions ?? []))),
            newRolePermissions: @js(array_values(old('new_role_permissions', []))),
        },
        confirmed: @js((bool) old('wizard_confirm', false)),
        inheritedPermissions() {
            return this.rolePermissionMap[this.selectedRole] ?? [];
        },
        summaryPermissions() {
            const rolePermissions = this.selectedRole === 'others'
                ? (this.form.newRolePermissions ?? [])
                : this.inheritedPermissions();

            const directPermissions = this.form.directPermissions ?? [];

            return Array.from(new Set([...rolePermissions, ...directPermissions]));
        },
        nextStep() {
            if (this.currentStep < this.maxStep) {
                this.currentStep += 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep -= 1;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        toggleSection(sectionName) {
            this[sectionName] = !this[sectionName];
        }
    }"
    class="{{ $isOverlay ? 'w-full' : 'max-w-5xl mx-auto' }}"
>
    <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden {{ $isOverlay ? 'max-h-[90vh]' : '' }}">
        <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gray-50/70">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $wizardTitle }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $wizardSubtitle }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">Step <span class="mx-1" x-text="currentStep"></span> of <span class="ml-1" x-text="maxStep"></span></span>
                    <button type="button"
                            @click="typeof closeWizard === 'function' ? closeWizard() : window.location.assign(@js($cancelUrl))"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700"
                            aria-label="Close wizard">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="max-w-xl mx-auto">
                <div class="flex items-center justify-between relative">
                    <div class="absolute top-5 left-12 right-12 h-0.5 rounded-full bg-gray-200 z-0"></div>
                    <div class="absolute top-5 left-12 h-0.5 rounded-full bg-gradient-to-r from-brand-600 to-brand-800 transition-all duration-500 ease-in-out z-0"
                         :style="'width: calc((100% - 6rem) * ' + ((currentStep - 1) / 3) + ')'"></div>

                    <template x-for="step in [1,2,3,4]" :key="step">
                        <div class="flex flex-col items-center relative z-10 w-24">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 shadow-sm"
                                 :class="{
                                     'bg-gradient-to-br from-purple-600 to-indigo-700 text-white ring-4 ring-purple-100': currentStep === step,
                                     'bg-purple-600 text-white': currentStep > step,
                                     'bg-white border-2 border-gray-200 text-gray-400': currentStep < step
                                 }">
                                <template x-if="currentStep > step">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </template>
                                <template x-if="currentStep <= step">
                                    <span class="text-sm font-bold" x-text="step"></span>
                                </template>
                            </div>
                            <span class="mt-2 text-[11px] font-semibold uppercase tracking-wider text-center"
                                  :class="currentStep >= step ? 'text-purple-700' : 'text-gray-400'"
                                  x-text="step === 1 ? 'Identity' : (step === 2 ? 'Access' : (step === 3 ? 'Permissions' : 'Review'))"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ $action }}" class="flex flex-col h-full overflow-hidden">
            @csrf
            @if($formMethod !== 'POST')
                @method($formMethod)
            @endif
            <input type="hidden" name="wizard_step" :value="currentStep">
            <input type="hidden" name="wizard_mode" value="{{ $mode ?? 'create' }}">
            <input type="hidden" name="wizard_user_id" value="{{ $user->id ?? '' }}">

            <div class="p-6 overflow-y-auto flex-1 bg-white">
                @include('admin.users.partials.user-wizard-steps', [
                    'mode' => $mode ?? 'create',
                    'user' => $user ?? null,
                    'roles' => $roles,
                    'permissions' => $permissions,
                    'permissionDescriptions' => $permissionDescriptions,
                    'canManagePermissions' => $canManagePermissions,
                    'directPermissions' => $directPermissions ?? [],
                    'selectedRole' => $selectedRole ?? old('role', ''),
                    'selectedStatus' => $selectedStatus ?? old('status', 'active'),
                ])
            </div>

            <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-between rounded-b-2xl">
                <button type="button"
                        x-show="currentStep > 1"
                        @click="prevStep()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    Back
                </button>
                <div x-show="currentStep === 1" class="w-20"></div>

                <div class="flex items-center gap-3">
                    <a href="{{ $cancelUrl }}" class="text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors px-4">Cancel</a>

                    <button type="button"
                            x-show="currentStep < maxStep"
                            @click="nextStep()"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                        Continue
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>

                    <button type="submit"
                            x-show="currentStep === maxStep"
                            x-cloak
                            :disabled="!confirmed"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white disabled:opacity-60 disabled:cursor-not-allowed hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                        <span>{{ ($mode ?? 'create') === 'edit' ? 'Update User' : 'Create User' }}</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
