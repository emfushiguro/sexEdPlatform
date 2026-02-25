@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8 max-w-2xl">
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold">Create New User</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded shadow p-6">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <!-- Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" 
                    class="w-full border rounded px-3 py-2 focus:ring focus:border-blue-400" required>
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" 
                    class="w-full border rounded px-3 py-2 focus:ring focus:border-blue-400" required>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" name="password" id="password" 
                    class="w-full border rounded px-3 py-2 focus:ring focus:border-blue-400" required>
                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
            </div>

            <!-- Password Confirmation -->
            <div class="mb-4">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" 
                    class="w-full border rounded px-3 py-2 focus:ring focus:border-blue-400" required>
            </div>

            <!-- Role -->
            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" id="role" class="w-full border rounded px-3 py-2 focus:ring focus:border-blue-400" required>
                    <option value="">Select Role</option>
                    <option value="learner" {{ old('role') == 'learner' ? 'selected' : '' }}>Learner</option>
                    <option value="instructor" {{ old('role') == 'instructor' ? 'selected' : '' }}>Instructor</option>
                    <option value="counselor" {{ old('role') == 'counselor' ? 'selected' : '' }}>Counselor</option>
                    <option value="clinic" {{ old('role') == 'clinic' ? 'selected' : '' }}>Clinic</option>
                    <option value="organization" {{ old('role') == 'organization' ? 'selected' : '' }}>Organization</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full border rounded px-3 py-2 focus:ring focus:border-blue-400" required>
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded shadow font-semibold">
                    Create User
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded shadow font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
