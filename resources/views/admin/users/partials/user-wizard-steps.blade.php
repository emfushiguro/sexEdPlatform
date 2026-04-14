@php
    $isCreateMode = ($mode ?? 'create') === 'create';
@endphp

<div x-show="currentStep === 1" x-cloak class="space-y-4 max-w-3xl mx-auto">
    <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Step 1: Identity</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
            <input type="text" name="name" id="name" x-model="form.name" value="{{ old('name', $user->name ?? '') }}" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
            <input type="email" name="email" id="email" x-model="form.email" value="{{ old('email', $user->email ?? '') }}" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition">
        </div>
        <div>
            <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1.5">Birthdate</label>
            <input type="date" name="birthdate" id="birthdate" x-model="form.birthdate" value="{{ old('birthdate', isset($user) ? optional($user->birthdate)->format('Y-m-d') : '') }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $isCreateMode ? 'Password' : 'New Password' }}</label>
            <div class="relative">
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" {{ $isCreateMode ? 'required' : '' }} class="w-full px-3 py-2.5 pr-11 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-brand-700" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 3 18 18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587a2 2 0 0 0 2.829 2.829"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 5.09A9.953 9.953 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a9.963 9.963 0 0 1-4.293 5.067M6.228 6.228A9.957 9.957 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7 1.61 0 3.13-.38 4.478-1.055"/></svg>
                </button>
            </div>
            @unless($isCreateMode)
                <p class="mt-1 text-xs text-gray-500">Leave blank to keep current password.</p>
            @endunless
        </div>
        <div class="md:col-span-2">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
            <div class="relative">
                <input :type="showPasswordConfirmation ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" {{ $isCreateMode ? 'required' : '' }} class="w-full px-3 py-2.5 pr-11 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition">
                <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-brand-700" :aria-label="showPasswordConfirmation ? 'Hide confirmation password' : 'Show confirmation password'">
                    <svg x-show="!showPasswordConfirmation" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="showPasswordConfirmation" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 3 18 18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587a2 2 0 0 0 2.829 2.829"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 5.09A9.953 9.953 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a9.963 9.963 0 0 1-4.293 5.067M6.228 6.228A9.957 9.957 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7 1.61 0 3.13-.38 4.478-1.055"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="currentStep === 2" x-cloak class="space-y-4 max-w-3xl mx-auto">
    <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Step 2: Role and Lifecycle</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
            <select name="role" id="role" x-model="selectedRole" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500/30">
                <option value="">Select role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" @selected((old('role', $selectedRole ?? '') === $role->name))>{{ str($role->name)->headline() }}</option>
                @endforeach
                <option value="others" @selected(old('role', $selectedRole ?? '') === 'others')>Others (Create New Role)</option>
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
            <select name="status" id="status" x-model="form.status" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500/30">
                <option value="active" @selected(old('status', $selectedStatus ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $selectedStatus ?? '') === 'inactive')>Inactive</option>
                @unless($isCreateMode)
                    <option value="suspended" @selected(old('status', $selectedStatus ?? '') === 'suspended')>Suspended</option>
                    <option value="archived" @selected(old('status', $selectedStatus ?? '') === 'archived')>Archived</option>
                @endunless
            </select>
        </div>

        <div class="md:col-span-2" x-show="selectedRole === 'others'" x-cloak>
            <label for="new_role_name" class="block text-sm font-medium text-gray-700 mb-1.5">New Role Name</label>
            <input type="text" name="new_role_name" id="new_role_name" value="{{ old('new_role_name') }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition" placeholder="e.g. community-moderator">
        </div>

        @unless($isCreateMode)
            <div class="md:col-span-2">
                <label for="role_change_reason" class="block text-sm font-medium text-gray-700 mb-1.5">Role Change Reason</label>
                <textarea name="role_change_reason" id="role_change_reason" rows="2" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition" placeholder="Optional reason for audit trail">{{ old('role_change_reason') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label for="role_change_custom_notes" class="block text-sm font-medium text-gray-700 mb-1.5">Role Change Notes</label>
                <textarea name="role_change_custom_notes" id="role_change_custom_notes" rows="4" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition" placeholder="Optional context or guidance notes">{{ old('role_change_custom_notes') }}</textarea>
            </div>
        @endunless
    </div>
</div>

<div x-show="currentStep === 3" x-cloak class="space-y-5 max-w-4xl mx-auto" x-data="{ permissionCatalog: @js($permissions) }">
    <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Step 3: Permissions</h4>

    <div class="rounded-xl border border-gray-200 bg-white p-4" x-show="selectedRole" x-cloak>
        <button type="button" class="flex w-full items-center justify-between text-left" @click="toggleSection('showRolePermissions')">
            <span>
                <h5 class="text-sm font-semibold text-gray-900">Role Permissions</h5>
                <p class="text-xs text-gray-500">Show or hide inherited permissions for the selected role.</p>
            </span>
            <span class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600" x-text="showRolePermissions ? 'Hide' : 'Show'"></span>
        </button>

        <div x-show="showRolePermissions" x-cloak class="mt-4 space-y-4">
            <div x-show="selectedRole !== 'others'" x-cloak class="rounded-xl border border-brand-100 bg-brand-50/45 p-4">
                <div class="flex flex-wrap gap-2">
                    <template x-for="permission in inheritedPermissions()" :key="'inherited-' + permission">
                        <span class="inline-flex items-center rounded-full border border-brand-200 bg-white px-3 py-1 text-xs font-semibold text-brand-700" x-text="permission"></span>
                    </template>
                </div>
                <p x-show="inheritedPermissions().length === 0" class="text-xs text-gray-500" x-cloak>No permissions configured for this role.</p>
            </div>

            <div x-show="selectedRole === 'others'" x-cloak class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">New Role Baseline Permissions</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <template x-for="permission in permissionCatalog" :key="permission">
                        <label class="rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors"
                               :class="(form.newRolePermissions || []).includes(permission) ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-200'">
                            <span class="flex items-center gap-2.5">
                                <input type="checkbox"
                                       name="new_role_permissions[]"
                                       :value="permission"
                                       x-model="form.newRolePermissions"
                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span x-text="permission"></span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </div>

    @if($canManagePermissions)
        <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3" x-show="selectedRole" x-cloak>
            <button type="button" class="flex w-full items-center justify-between text-left" @click="toggleSection('showPermissionOverrides')">
                <span>
                    <h5 class="text-sm font-semibold text-gray-900">User Permission Overrides</h5>
                    <p class="text-xs text-gray-500">Show or hide direct permission overrides for this user.</p>
                </span>
                <span class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600" x-text="showPermissionOverrides ? 'Hide' : 'Show'"></span>
            </button>

            <div x-show="showPermissionOverrides" x-cloak class="space-y-3">
                <input type="hidden" name="apply_permission_overrides" value="1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <template x-for="permission in permissionCatalog" :key="'override-' + permission">
                        <label class="rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors"
                               :class="(form.directPermissions || []).includes(permission) ? 'border-brand-300 bg-brand-50 text-brand-800' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-200'">
                            <span class="flex items-center gap-2.5">
                                <input type="checkbox"
                                       name="direct_permissions[]"
                                       :value="permission"
                                       x-model="form.directPermissions"
                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span x-text="permission"></span>
                            </span>
                        </label>
                    </template>
                </div>

                <input type="hidden" name="direct_permissions_present" value="1">
            </div>
        </div>
    @endif

    <p x-show="!selectedRole" class="text-xs text-gray-500" x-cloak>Select a role first to load permissions.</p>
</div>

<div x-show="currentStep === 4" x-cloak class="space-y-4 max-w-3xl mx-auto">
    <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide">Step 4: Confirm and Save</h4>

    <div class="rounded-2xl border border-gray-200 bg-gradient-to-b from-gray-50 to-white p-5 space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Identity Details</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Name</dt>
                        <dd class="font-semibold text-gray-900 text-right" x-text="form.name || 'Not set'"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Email</dt>
                        <dd class="font-semibold text-gray-900 text-right" x-text="form.email || 'Not set'"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Birthdate</dt>
                        <dd class="font-semibold text-gray-900 text-right" x-text="form.birthdate || 'Not set'"></dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Role Assignment</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Role</dt>
                        <dd class="font-semibold text-gray-900 text-right" x-text="selectedRole ? selectedRole.split('-').join(' ') : 'Not set'"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Status</dt>
                        <dd class="font-semibold text-gray-900 text-right" x-text="form.status || 'Not set'"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">Permission Count</dt>
                        <dd class="font-semibold text-gray-900 text-right" x-text="summaryPermissions().length"></dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <button type="button" class="flex w-full items-center justify-between text-left" @click="toggleSection('showReviewPermissions')">
                <span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Permission Summary</p>
                    <p class="text-sm text-gray-600"><span class="font-semibold text-gray-900" x-text="summaryPermissions().length"></span> total effective permissions</p>
                </span>
                <span class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600" x-text="showReviewPermissions ? 'Hide' : 'Show'"></span>
            </button>

            <div x-show="showReviewPermissions" x-cloak class="mt-4 flex flex-wrap gap-2">
                <template x-for="permission in summaryPermissions()" :key="'summary-' + permission">
                    <span class="inline-flex items-center rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-[11px] font-semibold text-brand-700" x-text="permission"></span>
                </template>
                <span x-show="summaryPermissions().length === 0" class="text-sm text-gray-500" x-cloak>No permissions selected.</span>
            </div>
        </section>

        <div class="rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-3 text-xs text-amber-800">
            Review this configuration carefully. Once saved, role and permission changes immediately affect this user's access scope.
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="wizard_confirm" value="1" x-model="confirmed" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500/40" required>
        I confirm these changes are accurate and comply with role governance rules.
    </label>
</div>
