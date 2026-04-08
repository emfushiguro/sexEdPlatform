@extends('layouts.admin')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('content')
@php
	$selectedSegment = $filters['segment'] ?? '';
	$roleMap = [
		'learner' => 'bg-brand-50 text-brand-700',
		'instructor' => 'bg-purple-50 text-purple-700',
		'counselor' => 'bg-success-50 text-success-700',
		'clinic' => 'bg-teal-50 text-teal-700',
		'organization' => 'bg-indigo-50 text-indigo-700',
		'admin' => 'bg-error-50 text-error-700',
	];
	$statusMap = [
		'active' => 'bg-success-50 text-success-700',
		'inactive' => 'bg-gray-100 text-gray-600',
		'suspended' => 'bg-error-50 text-error-700',
		'archived' => 'bg-amber-50 text-amber-700',
	];

	$segmentTabs = [
		['key' => '', 'label' => 'All Users', 'count' => $stats['total'] ?? 0],
		['key' => 'learners', 'label' => 'Learners', 'count' => $stats['learners'] ?? 0],
		['key' => 'parents', 'label' => 'Parents', 'count' => $stats['parents'] ?? 0],
		['key' => 'instructors', 'label' => 'Instructors', 'count' => $stats['instructors'] ?? 0],
		['key' => 'admins', 'label' => 'Admins', 'count' => $stats['admins'] ?? 0],
	];

	$cards = [
		['label' => 'Total Users', 'value' => $stats['total'] ?? 0],
		['label' => 'Active', 'value' => $stats['active'] ?? 0],
		['label' => 'Learners', 'value' => $stats['learners'] ?? 0],
		['label' => 'Parents', 'value' => $stats['parents'] ?? 0],
		['label' => 'Instructors', 'value' => $stats['instructors'] ?? 0],
		['label' => 'Admins', 'value' => $stats['admins'] ?? 0],
		['label' => 'Archived', 'value' => $stats['archived'] ?? 0],
		['label' => 'Premium', 'value' => $stats['premium'] ?? 0],
	];
@endphp

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
	@foreach($cards as $card)
		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
			<p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
			<p class="text-xs text-gray-500 mt-1">{{ $card['label'] }}</p>
		</div>
	@endforeach
</div>

<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
	<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
		<h3 class="text-base font-semibold text-gray-900">Admin User Management</h3>
		<a href="{{ route('admin.users.create') }}"
		   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
			<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
			</svg>
			Create User
		</a>
	</div>

	<div class="px-6 py-4 border-b border-gray-100">
		<div class="flex flex-wrap gap-2">
			@foreach($segmentTabs as $tab)
				@php
					$tabQuery = array_filter(array_merge(request()->query(), ['segment' => $tab['key']]));
					$isActive = $selectedSegment === $tab['key'];
				@endphp
				<a href="{{ route('admin.users.index', $tabQuery) }}"
				   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-colors {{ $isActive ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
					<span>{{ $tab['label'] }}</span>
					<span class="inline-flex h-5 min-w-5 px-1.5 items-center justify-center rounded-full text-[10px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-white text-gray-600' }}">{{ $tab['count'] }}</span>
				</a>
			@endforeach
		</div>
	</div>

	@include('admin.partials.table-filter-bar', ['label' => 'Users Filters', 'hint' => 'Search by identity, role, lifecycle status, account type, or age bracket.'])

	<form method="GET" class="px-6 py-4 border-b border-gray-100">
		<input type="hidden" name="segment" value="{{ $selectedSegment }}">
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
			<input type="text"
				   name="search"
				   value="{{ $filters['search'] ?? '' }}"
				   placeholder="Search name or email"
				   class="px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition lg:col-span-2">

			<select name="role" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
				<option value="">All Roles</option>
				@foreach(['learner','instructor','counselor','clinic','organization','admin'] as $role)
					<option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucfirst($role) }}</option>
				@endforeach
			</select>

			<select name="status" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
				<option value="">All Status</option>
				@foreach(['active','inactive','suspended','archived'] as $status)
					<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
				@endforeach
			</select>

			<select name="account_type" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
				<option value="">All Account Types</option>
				@foreach(['learner-child','learner-teen','learner-adult','parent','instructor','admin'] as $type)
					<option value="{{ $type }}" @selected(($filters['account_type'] ?? '') === $type)>{{ ucfirst(str_replace('-', ' ', $type)) }}</option>
				@endforeach
			</select>

			<select name="age_bracket" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
				<option value="">All Age Brackets</option>
				@foreach(['kids','teens','adults'] as $bracket)
					<option value="{{ $bracket }}" @selected(($filters['age_bracket'] ?? '') === $bracket)>{{ ucfirst($bracket) }}</option>
				@endforeach
			</select>
		</div>

		<div class="mt-3 flex items-center justify-end gap-2">
			<a href="{{ route('admin.users.index', ['segment' => $selectedSegment]) }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Clear</a>
			<button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors">Apply Filters</button>
		</div>
	</form>

	<div class="overflow-x-auto">
		<table class="min-w-full divide-y divide-gray-100">
			<thead class="bg-gray-50">
			<tr>
				<th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
				<th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Account Type</th>
				<th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
				<th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
				<th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Transparency</th>
				<th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
				<th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
			</tr>
			</thead>
			<tbody class="divide-y divide-gray-100">
			@forelse($users as $user)
				@php
					$accountType = $user->account_type ?: $user->deriveAccountType();
				@endphp
				<tr class="hover:bg-gray-50 transition-colors">
					<td class="px-5 py-3">
						<div class="flex items-center gap-3">
							<div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-xs font-bold flex-shrink-0">
								{{ strtoupper(substr($user->name, 0, 1)) }}
							</div>
							<div>
								<p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
								<p class="text-xs text-gray-500">{{ $user->email }}</p>
							</div>
						</div>
					</td>
					<td class="px-5 py-3 text-sm text-gray-700">{{ ucfirst(str_replace('-', ' ', (string) $accountType)) }}</td>
					<td class="px-5 py-3">
						<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleMap[$user->role] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($user->role) }}</span>
					</td>
					<td class="px-5 py-3">
						<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusMap[$user->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($user->status) }}</span>
					</td>
					<td class="px-5 py-3">
						<div class="flex flex-wrap gap-1.5">
							@if((int) ($user->parents_count ?? 0) > 0)
								<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-sky-50 text-sky-700">Has Parent</span>
							@endif
							@if((int) ($user->children_count ?? 0) > 0)
								<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-emerald-50 text-emerald-700">Has Children</span>
							@endif
							@if((int) ($user->instructor_applications_count ?? 0) > 0)
								<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-purple-50 text-purple-700">Instructor Lineage</span>
							@endif
						</div>
					</td>
					<td class="px-5 py-3 text-sm text-gray-500">{{ optional($user->created_at)->format('M d, Y') }}</td>
					<td class="px-5 py-3">
						<div class="flex items-center justify-end gap-1">
							<a href="{{ route('admin.users.show', $user) }}" title="View" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 transition-colors">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
							</a>
							<a href="{{ route('admin.users.edit', $user) }}" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:bg-amber-50 hover:text-amber-700 transition-colors">
								<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
							</a>
							@if($user->id !== auth()->id())
								<form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
									@csrf
									@method('DELETE')
									<button type="submit" title="Delete" class="p-1.5 rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 transition-colors">
										<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
									</button>
								</form>
							@endif
						</div>
					</td>
				</tr>
			@empty
				<tr>
					<td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">No users found for the current filters.</td>
				</tr>
			@endforelse
			</tbody>
		</table>
	</div>

	@if($users->hasPages())
		<div class="px-6 py-4 border-t border-gray-100">{{ $users->withQueryString()->links() }}</div>
	@endif
</div>
@endsection
