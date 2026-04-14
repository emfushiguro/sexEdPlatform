@extends('layouts.admin')
@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('content')

<div class="mb-5">
 <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
 Back to Profile
 </a>
</div>

@if($errors->any())
<div class="mb-5 rounded-xl bg-error-50 border border-error-200 px-4 py-3">
 <ul class="list-disc list-inside text-sm text-error-700 space-y-1">
 @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
 </ul>
</div>
@endif

@include('admin.users.partials.user-wizard-modal', [
	'mode' => 'edit',
	'title' => 'Edit User: ' . $user->name,
	'subtitle' => 'Use the guided wizard to update identity, role lifecycle, and permission overrides.',
	'action' => route('admin.users.update', $user),
	'method' => 'PUT',
	'cancelUrl' => route('admin.users.show', $user),
	'user' => $user,
	'roles' => $roles,
	'permissions' => $permissions,
	'permissionDescriptions' => $permissionDescriptions,
	'rolePermissionMap' => $rolePermissionMap,
	'canManagePermissions' => $canManagePermissions,
	'directPermissions' => $directPermissions ?? [],
	'selectedRole' => old('role', $user->roles()->value('name') ?: $user->role),
	'selectedStatus' => old('status', $user->status),
])
@endsection
