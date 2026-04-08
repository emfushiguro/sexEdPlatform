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
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
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
			<h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Parent-Child Transparency</h3>

			@if($linkedParent)
				<div class="rounded-xl border border-sky-200 bg-sky-50 p-4 mb-4">
					<p class="text-xs text-sky-700 font-semibold uppercase tracking-wide">Linked Parent</p>
					<p class="text-sm font-semibold text-gray-900 mt-1">{{ $linkedParent->name }}</p>
					<p class="text-xs text-gray-600">{{ $linkedParent->email }}</p>
					<div class="mt-3 flex items-center gap-2">
						@php
							$parentRelation = $parentRelationships->first();
							$isVerified = optional($parentRelation)->relationship_verified_at !== null;
						@endphp
						<form method="POST" action="{{ route('admin.users.relationships.verification') }}">
							@csrf
							@method('PATCH')
							<input type="hidden" name="parent_user_id" value="{{ $linkedParent->id }}">
							<input type="hidden" name="child_user_id" value="{{ $user->id }}">
							<input type="hidden" name="is_verified" value="{{ $isVerified ? 0 : 1 }}">
							<button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $isVerified ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }} transition-colors">
								{{ $isVerified ? 'Mark Unverified' : 'Mark Verified' }}
							</button>
						</form>
						<form method="POST" action="{{ route('admin.users.relationships.detach') }}" onsubmit="return confirm('Detach this parent-child relationship?')">
							@csrf
							@method('DELETE')
							<input type="hidden" name="parent_user_id" value="{{ $linkedParent->id }}">
							<input type="hidden" name="child_user_id" value="{{ $user->id }}">
							<button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Detach</button>
						</form>
					</div>
				</div>
			@endif

			@if($childRelationships->isNotEmpty())
				<div class="space-y-3 mb-4">
					@foreach($childRelationships as $relationship)
						<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
							<div class="flex items-start justify-between gap-3">
								<div>
									<p class="text-sm font-semibold text-gray-900">{{ $relationship->child?->name ?? 'Unknown Child' }}</p>
									<p class="text-xs text-gray-600">{{ $relationship->child?->email }}</p>
									<p class="text-[11px] text-gray-500 mt-1">Verification: {{ $relationship->relationship_verified_at ? 'Verified' : 'Unverified' }}</p>
								</div>
								<div class="flex items-center gap-2">
									<form method="POST" action="{{ route('admin.users.relationships.verification') }}">
										@csrf
										@method('PATCH')
										<input type="hidden" name="parent_user_id" value="{{ $user->id }}">
										<input type="hidden" name="child_user_id" value="{{ $relationship->child_user_id }}">
										<input type="hidden" name="is_verified" value="{{ $relationship->relationship_verified_at ? 0 : 1 }}">
										<button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 transition-colors">Toggle Verification</button>
									</form>
									<form method="POST" action="{{ route('admin.users.relationships.detach') }}" onsubmit="return confirm('Detach this parent-child relationship?')">
										@csrf
										@method('DELETE')
										<input type="hidden" name="parent_user_id" value="{{ $user->id }}">
										<input type="hidden" name="child_user_id" value="{{ $relationship->child_user_id }}">
										<button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-100 text-rose-700 hover:bg-rose-200 transition-colors">Detach</button>
									</form>
								</div>
							</div>
						</div>
					@endforeach
				</div>
			@endif

			<form method="POST" action="{{ route('admin.users.relationships.attach') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
				@csrf
				@if($childRelationships->isNotEmpty())
					<input type="hidden" name="parent_user_id" value="{{ $user->id }}">
					<input type="number" name="child_user_id" placeholder="Child User ID" class="sm:col-span-2 px-3 py-2 rounded-lg border border-gray-200 text-sm" required>
				@elseif($linkedParent)
					<input type="hidden" name="child_user_id" value="{{ $user->id }}">
					<input type="number" name="parent_user_id" placeholder="Parent User ID" class="sm:col-span-2 px-3 py-2 rounded-lg border border-gray-200 text-sm" required>
				@else
					<input type="number" name="parent_user_id" placeholder="Parent User ID" class="sm:col-span-2 px-3 py-2 rounded-lg border border-gray-200 text-sm" required>
					<input type="number" name="child_user_id" placeholder="Child User ID" class="sm:col-span-2 px-3 py-2 rounded-lg border border-gray-200 text-sm" required>
				@endif
				<label class="inline-flex items-center gap-2 text-xs text-gray-600">
					<input type="checkbox" name="is_verified" value="1" class="rounded border-gray-300">
					Verified
				</label>
				<button type="submit" class="sm:col-span-5 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition-colors">Attach Parent-Child Relationship</button>
			</form>
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
			<h3 class="text-sm font-semibold text-gray-700 mb-4">Lifecycle Controls</h3>

			<form method="POST" action="{{ route('admin.users.status.update', $user) }}" class="space-y-3 mb-4">
				@csrf
				@method('PATCH')
				<label class="block text-xs font-medium text-gray-600">Update Status</label>
				<select name="status" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" required>
					@foreach(['active','inactive','suspended','archived'] as $status)
						<option value="{{ $status }}" @selected($user->status === $status)>{{ ucfirst($status) }}</option>
					@endforeach
				</select>
				<input type="text" name="reason" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Optional status reason">
				<button type="submit" class="w-full px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold transition-colors">Save Status</button>
			</form>

			<form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="space-y-3">
				@csrf
				@method('PATCH')
				<label class="block text-xs font-medium text-gray-600">Change Role</label>
				<select name="role" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" required>
					@foreach(['learner','instructor','counselor','clinic','organization','admin'] as $role)
						<option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>
					@endforeach
				</select>
				<textarea name="reason" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Required reason for role change" required></textarea>
				<button type="submit" class="w-full px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold transition-colors">Apply Role Change</button>
			</form>
		</div>

		<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
			<h3 class="text-sm font-semibold text-gray-700 mb-4">Quick Links</h3>
			<div class="space-y-2">
				<a href="{{ route('admin.payments.index') }}?search={{ $user->email }}" class="block px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">View Payments</a>
				<a href="{{ route('admin.subscribers.index') }}?search={{ $user->email }}" class="block px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">View Subscriptions</a>
				@if($user->id !== auth()->id())
					<form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
						@csrf
						@method('DELETE')
						<button type="submit" class="w-full px-4 py-2.5 rounded-lg border border-rose-200 text-sm text-rose-700 hover:bg-rose-50 transition-colors">Delete User</button>
					</form>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection
