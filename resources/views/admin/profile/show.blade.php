@extends('layouts.admin')
@section('title', 'My Admin Profile')
@section('page-title', 'My Admin Profile')
@section('content')
@php
    $allowedTabs = ['public', 'credentials'];
    $requestedTab = old('profile_tab', $editModalTab ?? 'public');
    $initialTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'public';
    $openEditModal = ($forceOpenEditModal ?? false) || $errors->any();
@endphp

<div
    class="space-y-5"
    x-data="{
        editModalOpen: @js($openEditModal),
        activeTab: @js($initialTab),
        photoPreview: null,
        openEditModal(tab = 'public') {
            this.activeTab = tab;
            this.editModalOpen = true;
        },
        closeEditModal() {
            this.editModalOpen = false;
            this.photoPreview = null;
        }
    }"
    @keydown.escape.window="if (editModalOpen) { closeEditModal(); }"
>
    <div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 text-sm font-medium border rounded-xl border-emerald-200 bg-emerald-50 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-theme-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                @if($profile->avatar_path)
                    <img
                        src="{{ asset('storage/' . ltrim((string) $profile->avatar_path, '/')) }}"
                        alt="{{ $profile->public_display_name }}"
                        class="object-cover border border-gray-200 w-14 h-14 rounded-2xl"
                    >
                @else
                    <div class="flex items-center justify-center text-xl font-bold w-14 h-14 rounded-2xl bg-brand-100 text-brand-700">
                        {{ strtoupper(substr((string) $profile->public_display_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $profile->public_display_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $profile->affiliation }}</p>
                    <p class="mt-1 text-xs text-gray-400">Platform Developer</p>
                </div>
            </div>
            <a href="{{ route('admin.profile.edit') }}"
               @click.prevent="openEditModal('public')"
               class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                Edit Profile
            </a>
        </div>

        <div class="pt-5 mt-6 border-t border-gray-100">
            <p class="mb-1 text-xs text-gray-400">Bio</p>
            <p class="text-sm leading-6 text-gray-700">
                {{ $profile->bio ?: 'I lead platform quality, content governance, and learning experience standards for Conscious Connections.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 pt-5 mt-6 border-t border-gray-100 sm:grid-cols-3">
            <div>
                <p class="mb-1 text-xs text-gray-400">Member Since</p>
                <p class="text-sm font-semibold text-gray-900">{{ optional($user->created_at)->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs text-gray-400">Last Updated</p>
                <p class="text-sm font-semibold text-gray-900">{{ optional($profile->updated_at)->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="mb-1 text-xs text-gray-400">Public Attribution</p>
                <p class="text-sm font-semibold text-gray-900">{{ $profile->show_individual_attribution ? 'Enabled' : 'Disabled' }}</p>
            </div>
        </div>
    </div>

    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4 sm:p-6 lg:p-8" role="dialog" aria-modal="true" aria-labelledby="admin-profile-modal-title">
        <div class="fixed inset-0 bg-gray-900/65 backdrop-blur-sm" @click="closeEditModal()"></div>

        <div class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all max-h-[90vh] flex flex-col">
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gray-50/70">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 id="admin-profile-modal-title" class="text-lg font-bold text-gray-900">Edit Admin Profile</h3>
                        <p class="mt-1 text-sm text-gray-600">Update your public identity and account credentials in separate tabs for safer profile management.</p>
                    </div>
                    <button type="button" @click="closeEditModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700" aria-label="Close profile editor">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 inline-flex rounded-xl border border-gray-200 bg-white p-1">
                    <button
                        type="button"
                        @click="activeTab = 'public'"
                        :class="activeTab === 'public' ? 'bg-brand-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                    >
                        Public Profile
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'credentials'"
                        :class="activeTab === 'credentials' ? 'bg-brand-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                    >
                        Account Credentials
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-6" x-show="activeTab === 'public'" x-cloak>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="profile_tab" value="public">

                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                            <div class="h-24 w-24 overflow-hidden rounded-full border-2 border-brand-100 bg-gray-50">
                                <template x-if="!photoPreview">
                                    @if($profile->avatar_path)
                                        <img src="{{ asset('storage/' . ltrim((string) $profile->avatar_path, '/')) }}" alt="{{ $profile->public_display_name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="h-full w-full flex items-center justify-center bg-brand-100 text-brand-700 text-xl font-bold">
                                            {{ strtoupper(substr((string) $profile->public_display_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </template>
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" alt="Avatar preview" class="h-full w-full object-cover">
                                </template>
                            </div>

                            <div>
                                <input type="file" name="avatar" class="hidden" x-ref="avatar" accept="image/*" @change="
                                    const file = $refs.avatar.files[0];
                                    if (!file) return;
                                    const reader = new FileReader();
                                    reader.onload = (e) => { photoPreview = e.target.result; };
                                    reader.readAsDataURL(file);
                                ">
                                <button type="button" @click="$refs.avatar.click()" class="rounded-lg bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100">Change avatar</button>
                                <p class="mt-2 text-xs text-gray-500">JPG, PNG or WEBP. Max 2MB.</p>
                                @error('avatar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="public_display_name" class="block text-sm font-medium text-gray-700">Display Name</label>
                            <input id="public_display_name" type="text" name="public_display_name" value="{{ old('public_display_name', $profile->public_display_name) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                            @error('public_display_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="affiliation" class="block text-sm font-medium text-gray-700">Affiliation</label>
                            <input id="affiliation" type="text" name="affiliation" value="{{ old('affiliation', $profile->affiliation) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                            @error('affiliation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                        <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('bio', $profile->bio) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Professional, platform-focused biography shown in creator transparency views.</p>
                        @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <label for="show_individual_attribution" class="flex items-start gap-3">
                            <input id="show_individual_attribution" type="checkbox" name="show_individual_attribution" value="1" @checked(old('show_individual_attribution', $profile->show_individual_attribution)) class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900">Show Individual Attribution</span>
                                <span class="block text-xs text-gray-500 mt-1">When enabled, module ownership transparency can show your public display name beside the team identity.</span>
                            </span>
                        </label>
                        @error('show_individual_attribution')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="closeEditModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition-colors">Save Public Profile</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6" x-show="activeTab === 'credentials'" x-cloak>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="profile_tab" value="credentials">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Admin Login Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                        <p class="mt-1 text-xs text-gray-500">Use a valid Gmail address (for example: name@gmail.com). This email is used for admin login.</p>
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Password</p>
                        <p class="mt-1 text-sm text-gray-600">Leave password fields blank to keep your current password.</p>
                        <p class="mt-1 text-xs text-gray-500">New password must be at least 8 characters and include uppercase, lowercase, number, and symbol characters.</p>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input id="current_password" type="password" name="current_password" autocomplete="current-password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input id="new_password" type="password" name="new_password" autocomplete="new-password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @error('new_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input id="new_password_confirmation" type="password" name="new_password_confirmation" autocomplete="new-password" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="closeEditModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-colors">Save Account Credentials</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
