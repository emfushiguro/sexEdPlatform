@extends('layouts.admin')
@section('title', 'Edit Admin Profile')
@section('page-title', 'Edit Admin Profile')

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.profile.show') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Profile
        </a>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-center gap-4 sm:gap-6" x-data="{ photoPreview: null }">
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username', $user->name) }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                <p class="mt-1 text-xs text-gray-500">Used across admin identity and chat visibility.</p>
                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="public_display_name" class="block text-sm font-medium text-gray-700">Public Display Name</label>
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

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Credentials</p>
            <p class="mt-1 text-sm text-gray-600">Update your password when needed. Leave password fields blank to keep your current password.</p>

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

        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">System managed fields</p>
            <p class="mt-2 text-sm text-gray-600">Role and permissions are managed from user and RBAC administration screens and cannot be edited here.</p>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.profile.show') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">Cancel</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Save Profile</button>
        </div>
    </form>
</div>
@endsection
