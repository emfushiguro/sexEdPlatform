@extends('layouts.admin')
@section('title', 'My Admin Profile')
@section('page-title', 'My Admin Profile')
@section('content')
<div class="space-y-5">
    <div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                @if($profile->avatar_path)
                    <img
                        src="{{ asset('storage/' . ltrim((string) $profile->avatar_path, '/')) }}"
                        alt="{{ $profile->public_display_name }}"
                        class="w-14 h-14 rounded-2xl object-cover border border-gray-200"
                    >
                @else
                    <div class="w-14 h-14 rounded-2xl bg-brand-100 text-brand-700 font-bold text-xl flex items-center justify-center">
                        {{ strtoupper(substr((string) $profile->public_display_name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $profile->public_display_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $profile->affiliation }}</p>
                    <p class="text-xs text-gray-400 mt-1">Platform Developer</p>
                </div>
            </div>
            <a href="{{ route('admin.profile.edit') }}"
               class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition-colors">
                Edit Profile
            </a>
        </div>

        <div class="mt-6 border-t border-gray-100 pt-5">
            <p class="text-xs text-gray-400 mb-1">Bio</p>
            <p class="text-sm text-gray-700 leading-6">
                {{ $profile->bio ?: 'No bio added yet.' }}
            </p>
        </div>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-gray-100 pt-5">
            <div>
                <p class="text-xs text-gray-400 mb-1">Member Since</p>
                <p class="text-sm font-semibold text-gray-900">{{ optional($user->created_at)->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Last Updated</p>
                <p class="text-sm font-semibold text-gray-900">{{ optional($profile->updated_at)->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-1">Public Attribution</p>
                <p class="text-sm font-semibold text-gray-900">{{ $profile->show_individual_attribution ? 'Enabled' : 'Disabled' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
