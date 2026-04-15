@extends('layouts.admin')
@section('title', 'User Profile')
@section('page-title', 'User Profile')
@section('content')
<div class="mb-5">
	<a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
		<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
		</svg>
		Back to Users
	</a>
</div>

@php
	$roleMap = [
		'learner' => 'bg-brand-50 text-brand-700',
		'instructor' => 'bg-brand-50 text-brand-700',
		'counselor' => 'bg-success-50 text-success-700',
		'clinic' => 'bg-teal-50 text-teal-700',
		'organization' => 'bg-brand-50 text-brand-700',
		'admin' => 'bg-error-50 text-error-700',
	];
	$statusMap = [
		'active' => 'bg-success-50 text-success-700',
		'inactive' => 'bg-gray-100 text-gray-600',
		'suspended' => 'bg-error-50 text-error-700',
		'archived' => 'bg-amber-50 text-amber-700',
	];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5"
	x-data="adminUserProfilePage({
		currentStatus: @js($user->status),
		initialStatus: @js(old('status', $user->status)),
		initialStatusReason: @js(old('reason', '')),
		initialRole: @js(old('role', $user->role)),
		initialNewRoleName: @js(old('new_role_name', '')),
	})"
>
	<div class="xl:col-span-2 space-y-5">
		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
			<div class="flex items-start justify-between gap-4 mb-6">
				<div class="flex items-center gap-4">
					<div class="w-16 h-16 rounded-2xl bg-brand-100 flex items-center justify-center text-brand-600 text-2xl font-bold">
						{{ strtoupper(substr($user->name, 0, 1)) }}
					</div>
					<div>
						<h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
						<p class="text-sm text-gray-500">{{ $user->email }}</p>
						<div class="flex items-center flex-wrap gap-2 mt-2">
							<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleMap[$user->role] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($user->role) }}</span>
							<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusMap[$user->status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($user->status) }}</span>
							<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ ucfirst(str_replace('-', ' ', (string) ($user->account_type ?: $user->deriveAccountType()))) }}</span>
						</div>
					</div>
				</div>
				<a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 text-sm font-medium transition-colors">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
					Edit
				</a>
			</div>

			<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
				<div>
					<p class="text-xs text-gray-400 mb-0.5">Member Since</p>
					<p class="text-sm font-semibold text-gray-900">{{ optional($user->created_at)->format('M d, Y') }}</p>
				</div>
				<div>
					<p class="text-xs text-gray-400 mb-0.5">Age Bracket</p>
					<p class="text-sm font-semibold text-gray-900">{{ ucfirst((string) ($user->age_bracket_cached ?: ($user->deriveAgeBracketCache() ?? 'n/a'))) }}</p>
				</div>
				<div>
					<p class="text-xs text-gray-400 mb-0.5">Email Verified</p>
					<p class="text-sm font-semibold text-gray-900">{{ $user->email_verified_at ? 'Yes' : 'No' }}</p>
				</div>
				<div>
					<p class="text-xs text-gray-400 mb-0.5">Payments</p>
					<p class="text-sm font-semibold text-gray-900">P{{ number_format((float) ($stats['total_payments'] ?? 0), 2) }}</p>
				</div>
			</div>
		</div>

		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
			@include('admin.users.partials.relationship-transparency-panel', [
				'linkedParent' => $linkedParent,
				'parentRelationships' => $parentRelationships,
				'childRelationships' => $childRelationships,
			])
		</div>

		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
			<h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Learner-To-Instructor Lineage</h3>

			@if($instructorLineage->isEmpty())
				<p class="text-sm text-gray-500">No instructor application records found for this user.</p>
			@else
				<div class="space-y-3">
					@foreach($instructorLineage as $application)
						<div class="rounded-xl border border-gray-200 p-4">
							<div class="flex items-center justify-between gap-3">
								<p class="text-sm font-semibold text-gray-900">Application #{{ $application->id }}</p>
								<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst((string) $application->status) }}</span>
							</div>
							<p class="text-xs text-gray-500 mt-1">Submitted {{ optional($application->created_at)->diffForHumans() }}</p>
							@if($application->latestReview)
								<p class="text-xs text-gray-600 mt-2">Latest review by {{ optional($application->latestReview->reviewedBy)->name ?? 'System' }} at {{ optional($application->latestReview->reviewed_at)->format('M d, Y h:i A') }}</p>
							@endif
						</div>
					@endforeach
				</div>
			@endif
		</div>

		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
			<h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Role Transition Timeline</h3>
			@if($roleTransitions->isEmpty())
				<p class="text-sm text-gray-500">No role transitions recorded.</p>
			@else
				<div class="space-y-3">
					@foreach($roleTransitions as $transition)
						<div class="rounded-xl border border-gray-200 p-4">
							<p class="text-sm font-semibold text-gray-900">{{ ucfirst((string) $transition->from_role) }} -> {{ ucfirst((string) $transition->to_role) }}</p>
							<p class="text-xs text-gray-500 mt-1">{{ optional($transition->transitioned_at)->format('M d, Y h:i A') }} by {{ optional($transition->approvedBy)->name ?? 'System' }}</p>
							@if($transition->reason)
								<p class="text-xs text-gray-600 mt-2">Reason: {{ $transition->reason }}</p>
							@endif
						</div>
					@endforeach
				</div>
			@endif
		</div>
	</div>

	<div class="space-y-5">
		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
			<h3 class="text-sm font-semibold text-gray-700 mb-2">Lifecycle Controls</h3>
			<p class="text-xs text-gray-500 mb-4">All role and status updates are managed through confirmation modals for safer actions.</p>

			<div class="grid grid-cols-1 gap-2">
				<button type="button" @click="openStatusModal()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition-colors">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
					Change Status
				</button>
				<button type="button" @click="openRoleModal()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-colors">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.467 0 4.785.636 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
					Change Role
				</button>
			</div>
		</div>

		@include('admin.users.partials.quick-links-panel', ['user' => $user])
	</div>

	<div x-show="statusModalOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4 sm:p-6 lg:p-8" role="dialog" aria-modal="true">
		<div class="fixed inset-0 bg-gray-900/65 backdrop-blur-sm" @click="closeStatusModal()"></div>

		<div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
			<div class="px-6 pt-6 pb-4 border-b border-gray-100">
				<h3 class="text-lg font-bold text-gray-900">Change User Status</h3>
				<p class="mt-2 text-sm text-gray-600">Update lifecycle status with validation and confirmation before applying changes.</p>
			</div>

			<form method="POST" action="{{ route('admin.users.status.update', $user) }}" class="px-6 py-5 space-y-4">
				@csrf
				@method('PATCH')

				<div>
					<label for="status_modal_status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
					<select id="status_modal_status" name="status" x-model="statusForm.status" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30" required>
						@foreach(['active','inactive','suspended','archived'] as $status)
							<option value="{{ $status }}">{{ ucfirst($status) }}</option>
						@endforeach
					</select>
				</div>

				<div>
					<label for="status_modal_reason" class="block text-sm font-medium text-gray-700 mb-1.5">Reason</label>
					<input
						type="text"
						id="status_modal_reason"
						name="reason"
						x-model="statusForm.reason"
						:required="statusForm.status === 'archived' || (currentStatus === 'archived' && statusForm.status !== 'archived')"
						class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
						placeholder="Required when archiving or restoring archived users"
					>
				</div>

				<div class="flex items-center justify-end gap-3 pt-2">
					<button type="button" @click="closeStatusModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
					<button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition-colors">Confirm Status Change</button>
				</div>
			</form>
		</div>
	</div>

	<div x-show="roleModalOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4 sm:p-6 lg:p-8" role="dialog" aria-modal="true">
		<div class="fixed inset-0 bg-gray-900/65 backdrop-blur-sm" @click="closeRoleModal()"></div>

		<div class="relative w-full max-w-xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
			<div class="px-6 pt-6 pb-4 border-b border-gray-100">
				<h3 class="text-lg font-bold text-gray-900">Change User Role</h3>
				<p class="mt-2 text-sm text-gray-600">Assign a supported role and provide optional rich-text notes for the transition.</p>
			</div>

			<form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="px-6 py-5 space-y-4" @submit="syncRoleEditor()">
				@csrf
				@method('PATCH')

				<div>
					<label for="role_modal_role" class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
					<select id="role_modal_role" name="role" x-model="roleForm.role" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30" required>
						<option value="admin">Admin</option>
						<option value="instructor">Instructor</option>
						<option value="learner">Learner</option>
						<option value="others">Others (Create New Role)</option>
					</select>
				</div>

				<div x-show="roleForm.role === 'others'" x-cloak>
					<label for="role_modal_new_role_name" class="block text-sm font-medium text-gray-700 mb-1.5">New Role Name</label>
					<input
						type="text"
						id="role_modal_new_role_name"
						name="new_role_name"
						x-model="roleForm.newRoleName"
						class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
						placeholder="e.g. community-moderator"
					>
				</div>

				<div>
					<label for="custom_notes" class="block text-sm font-medium text-gray-700 mb-1.5">Role Change Notes (TinyMCE)</label>
					<textarea
						name="custom_notes"
						id="custom_notes"
						rows="5"
						class="js-role-change-editor w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700"
						placeholder="Optional rich-text transition notes"
					>{{ old('custom_notes') }}</textarea>
				</div>

				<div class="flex items-center justify-end gap-3 pt-2">
					<button type="button" @click="closeRoleModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
					<button type="submit" class="px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-colors">Confirm Role Change</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@push('scripts')
	<script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
	<script>
		function adminUserProfilePage(config) {
			return {
				currentStatus: config.currentStatus,
				statusModalOpen: false,
				roleModalOpen: false,
				statusForm: {
					status: config.initialStatus,
					reason: config.initialStatusReason,
				},
				roleForm: {
					role: config.initialRole,
					newRoleName: config.initialNewRoleName,
				},
				openStatusModal() {
					this.statusModalOpen = true;
				},
				closeStatusModal() {
					this.statusModalOpen = false;
				},
				openRoleModal() {
					this.roleModalOpen = true;
					this.$nextTick(() => {
						this.initRoleEditor();
					});
				},
				closeRoleModal() {
					this.roleModalOpen = false;
				},
				initRoleEditor() {
					if (typeof tinymce === 'undefined') {
						return;
					}

					tinymce.remove('textarea.js-role-change-editor');
					tinymce.init({
						selector: 'textarea.js-role-change-editor',
						license_key: 'gpl',
						menubar: false,
						branding: false,
						height: 220,
						plugins: 'lists link code',
						toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
						content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }'
					});
				},
				syncRoleEditor() {
					if (typeof tinymce !== 'undefined') {
						tinymce.triggerSave();
					}
				}
			};
		}

		document.addEventListener('DOMContentLoaded', function () {
			if (!document.querySelector('.js-role-change-editor') || typeof tinymce === 'undefined') {
				return;
			}

			if (document.querySelector('[x-data^="adminUserProfilePage"]')) {
				return;
			}

			tinymce.remove('textarea.js-role-change-editor');
			tinymce.init({
				selector: 'textarea.js-role-change-editor',
				license_key: 'gpl',
				menubar: false,
				branding: false,
				height: 220,
				plugins: 'lists link code',
				toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
				content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }'
			});
		});
	</script>
@endpush
