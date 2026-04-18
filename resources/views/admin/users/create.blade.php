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
<div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3">
 <ul class="list-disc list-inside text-sm text-rose-700 space-y-1">
 @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
 </ul>
</div>
@endif

@include('admin.users.partials.user-wizard-modal', [
	'mode' => 'create',
	'title' => 'Create New User',
	'subtitle' => 'Use the guided wizard to configure identity, role, permissions, and confirmation.',
	'action' => route('admin.users.store'),
	'method' => 'POST',
	'cancelUrl' => route('admin.users.index'),
	'roles' => $roles,
	'permissions' => $permissions,
	'permissionDescriptions' => $permissionDescriptions,
	'rolePermissionMap' => $rolePermissionMap,
	'canManagePermissions' => $canManagePermissions,
	'selectedRole' => old('role', ''),
	'selectedStatus' => old('status', 'active'),
])
@endsection
