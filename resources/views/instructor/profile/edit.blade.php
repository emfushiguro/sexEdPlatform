@extends('layouts.instructor-app')

@section('content')
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Instructor Profile</h1>
        <p class="text-sm text-gray-600 mt-1">Update only instructor-managed profile fields.</p>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('instructor.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Professional Bio</label>
                <textarea name="bio" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('bio', $profile->bio) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Educational Background</label>
                <input type="text" name="educational_background" value="{{ old('educational_background', $profile->educational_background) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Professional Background</label>
                <textarea name="professional_background" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('professional_background', $profile->professional_background) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Primary Expertise</label>
                <input type="text" name="primary_expertise" value="{{ old('primary_expertise', $profile->primary_expertise) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                <input type="number" min="0" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('instructor.profile.show') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900">Update Password</h2>
        <p class="text-sm text-gray-600 mt-1">For security, your current password is required.</p>
        <form method="POST" action="{{ route('profile.password.update') }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                <input type="password" name="current_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Update Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
