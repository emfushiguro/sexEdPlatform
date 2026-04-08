@extends('layouts.admin')
@section('title', 'Create User')
@section('page-title', 'Create User')
@section('content')

<div class="mb-5">
 <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
 Back to Users
 </a>
</div>

@if($errors->any())
<div class="mb-5 rounded-xl bg-error-50 border border-error-200 px-4 py-3">
 <ul class="list-disc list-inside text-sm text-error-700 space-y-1">
 @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
 </ul>
</div>
@endif

<div class="max-w-2xl">
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="px-6 py-4 border-b border-gray-100 ">
 <h3 class="text-base font-semibold text-gray-900 ">New User Details</h3>
 <p class="text-xs text-gray-500 mt-1">Admin accounts created here have full phase-1 governance capability.</p>
 </div>
 <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-5">
 @csrf
 <div>
 <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
 <input type="text" name="name" id="name" value="{{ old('name') }}" required
 class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 </div>
 <div>
 <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
 <input type="email" name="email" id="email" value="{{ old('email') }}" required
 class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 </div>
 <div>
 <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
 <input type="password" name="password" id="password" required
 class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 <p class="mt-1 text-xs text-gray-400">Minimum 8 characters</p>
 </div>
 <div>
 <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
 <input type="password" name="password_confirmation" id="password_confirmation" required
 class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 </div>
 <div class="grid grid-cols-2 gap-4">
 <div>
 <div>
 <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1.5">Birthdate <span class="text-xs text-gray-400">(optional, used for age bracket filtering)</span></label>
 <input type="date" name="birthdate" id="birthdate" value="{{ old('birthdate') }}"
 class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 </div>
 <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
 <select name="role" id="role" required class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option value="">Select Role</option>
 <option value="learner" @selected(old('role')=='learner')>Learner</option>
 <option value="instructor" @selected(old('role')=='instructor')>Instructor</option>
 <option value="counselor" @selected(old('role')=='counselor')>Counselor</option>
 <option value="clinic" @selected(old('role')=='clinic')>Clinic</option>
 <option value="organization" @selected(old('role')=='organization')>Organization</option>
 <option value="admin" @selected(old('role')=='admin')>Admin</option>
 </select>
 </div>
 <div>
 <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
 <select name="status" id="status" required class="w-full px-3 py-2.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option value="active" @selected(old('status')=='active')>Active</option>
 <option value="inactive" @selected(old('status')=='inactive')>Inactive</option>
 <option value="suspended" @selected(old('status')=='suspended')>Suspended</option>
 <option value="archived" @selected(old('status')=='archived')>Archived</option>
 </select>
 </div>
 </div>
 <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 ">
 <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</a>
 <button type="submit" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Create User</button>
 </div>
 </form>
 </div>
</div>
@endsection
