@extends('layouts.connector-app')

@section('title', 'Roles')
@section('page-title', 'Roles & Permissions')

@php
    $roleRows = $connector->roles->map(fn ($role) => [
        'id' => $role->id,
        'name' => $role->name,
        'description' => $role->is_owner ? 'Protected Owner role' : ($role->description ?: 'Custom role'),
        'permission_count' => $role->permissions->count(),
        'is_owner' => (bool) $role->is_owner,
        'is_protected' => (bool) $role->is_protected,
    ])->values();
@endphp

@section('content')
<div x-data="connectorRolesPage({ roles: @js($roleRows) })" class="space-y-6">
    <section class="rounded-[24px] border border-gray-200 bg-white p-6 shadow-theme-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Access Model</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900">Connector Roles</h2>
            </div>
            <button type="button" @click="openWizard()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                Create Role
            </button>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <template x-for="role in roles" :key="role.id">
                <article class="rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-900" x-text="role.name"></h3>
                            <p class="mt-1 text-sm leading-5 text-gray-500" x-text="role.description"></p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="role.is_owner ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'" x-text="role.is_owner ? 'Owner' : role.permission_count + ' perms'"></span>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <div x-show="wizardOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="wizardOpen = false"></div>

        <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-gray-100 bg-gray-50/70 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Role Wizard</p>
                        <h3 class="text-xl font-bold text-gray-900">Create Connector Role</h3>
                    </div>
                    <button type="button" @click="wizardOpen = false" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mx-auto mt-6 max-w-xl">
                    <div class="relative flex items-center justify-between">
                        <template x-for="s in [1,2,3]" :key="s">
                            <div class="relative z-10 flex w-28 flex-col items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition"
                                    :class="step === s ? 'bg-gradient-to-br from-purple-600 to-indigo-700 text-white ring-4 ring-purple-100' : (step > s ? 'bg-purple-600 text-white' : 'border-2 border-gray-200 bg-white text-gray-400')">
                                    <template x-if="step > s"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/></svg></template>
                                    <template x-if="step <= s"><span x-text="s"></span></template>
                                </div>
                                <span class="mt-2 text-center text-xs font-semibold uppercase tracking-wide" :class="step >= s ? 'text-purple-700' : 'text-gray-400'" x-text="s === 1 ? 'Info' : (s === 2 ? 'Permissions' : 'Review')"></span>
                            </div>
                        </template>
                        <div class="absolute left-14 right-14 top-5 h-0.5 bg-gray-200">
                            <div class="h-full bg-purple-600 transition-all duration-500" :style="'width: ' + ((step - 1) / 2 * 100) + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('connector.roles.store', $connector) }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="flex-1 overflow-y-auto p-6">
                    <div x-show="step === 1" class="space-y-4">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Role Name</span>
                            <input name="name" x-model="roleName" class="mt-1 w-full rounded-xl border-gray-300 text-sm" required>
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Role Description</span>
                            <textarea name="description" x-model="roleDescription" rows="4" class="mt-1 w-full rounded-xl border-gray-300 text-sm"></textarea>
                        </label>
                    </div>

                    <div x-show="step === 2" x-cloak class="space-y-4">
                        @foreach($permissionGroups as $group => $permissions)
                            <fieldset class="rounded-2xl border border-gray-100 p-4">
                                <legend class="px-1 text-sm font-bold capitalize text-gray-900">{{ $group }}</legend>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach($permissions as $key => $label)
                                        <label class="flex items-start gap-2 rounded-xl px-3 py-2 text-sm hover:bg-purple-50">
                                            <input type="checkbox" name="permissions[]" value="{{ $key }}" x-model="selectedPermissions" class="mt-0.5 rounded border-gray-300 text-purple-700">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <div x-show="step === 3" x-cloak class="space-y-5">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Role Information</p>
                            <h4 class="mt-2 font-bold text-gray-900" x-text="roleName || 'Untitled role'"></h4>
                            <p class="mt-1 text-sm text-gray-600" x-text="roleDescription || 'No description provided.'"></p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Selected Permissions</p>
                            <p class="mt-2 text-sm text-gray-600"><span class="font-semibold text-gray-900" x-text="selectedPermissions.length"></span> permissions selected.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <button type="button" x-show="step > 1" @click="step--" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Back</button>
                    <span x-show="step === 1"></span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="wizardOpen = false" class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-800">Cancel</button>
                        <button type="button" x-show="step < 3" @click="nextStep()" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Continue</button>
                        <button type="submit" x-show="step === 3" x-cloak class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Create Role</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function connectorRolesPage(config) {
        return {
            roles: config.roles || [],
            wizardOpen: {{ $errors->any() ? 'true' : 'false' }},
            step: 1,
            roleName: @js(old('name', '')),
            roleDescription: @js(old('description', '')),
            selectedPermissions: @js(old('permissions', [])),
            openWizard() {
                this.wizardOpen = true;
                this.step = 1;
            },
            nextStep() {
                if (this.step === 1 && !this.roleName.trim()) {
                    return;
                }
                this.step = Math.min(3, this.step + 1);
            },
        };
    }
</script>
@endsection
