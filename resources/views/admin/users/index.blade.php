@extends('layouts.admin')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('content')
@php
	$selectedSegment = $filters['segment'] ?? '';
	$usersIndexRoute = request()->routeIs('admin.learners.*') ? 'admin.learners.index' : 'admin.users.index';
	$wizard = $wizard ?? ['render' => false, 'open' => false, 'mode' => 'create'];
	$wizardCloseUrl = $wizard['closeUrl'] ?? route($usersIndexRoute, request()->except(['wizard', 'wizard_user']));
@endphp

<div
	x-data="adminUsersIndex({
		endpoint: @js(route($usersIndexRoute)),
		selectedSegment: @js($selectedSegment),
		wizardOpen: @js((bool) ($wizard['open'] ?? false)),
		wizardMode: @js((string) ($wizard['mode'] ?? 'create')),
		wizardCloseUrl: @js($wizardCloseUrl),
		createWizardUrl: @js(route('admin.users.create')),
		initialFilters: @js([
			'search' => $filters['search'] ?? '',
			'role' => $filters['role'] ?? '',
			'status' => $filters['status'] ?? '',
			'account_type' => $filters['account_type'] ?? '',
			'created_from' => $filters['created_from'] ?? '',
			'created_to' => $filters['created_to'] ?? '',
			'per_page' => (string) request('per_page', 25),
		]),
		initialPage: @js((int) request('page', 1)),
	})"
	class="space-y-8"
>
	@foreach(['success','error','warning'] as $type)
		@if(session($type))
			@php
				$cfg = [
					'success' => ['bg' => 'bg-success-50 border-success-200 text-success-700', 'icon' => 'M5 13l4 4L19 7'],
					'error' => ['bg' => 'bg-error-50 border-error-200 text-error-700', 'icon' => 'M6 18L18 6M6 6l12 12'],
					'warning' => ['bg' => 'bg-warning-50 border-warning-200 text-warning-700', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
				];
			@endphp
			<div class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm {{ $cfg[$type]['bg'] }}">
				<svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg[$type]['icon'] }}"/>
				</svg>
				{{ session($type) }}
			</div>
		@endif
	@endforeach

	@include('admin.users.partials.stats-cards', ['stats' => $stats])

	<section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
		<div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
			<div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
				<div>
					<h2 class="text-xl font-bold text-gray-900">Admin User Management</h2>
					<p class="mt-1 text-sm text-gray-500">Monitor account lifecycle, role governance, and access overrides in one place.</p>
				</div>
				@if($canCreateUsers ?? false)
					<button type="button"
					   @click="openCreateWizard()"
					   class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-800 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-300/40 transition hover:brightness-105">
						<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
						</svg>
						Create User
					</button>
				@endif
			</div>

		</div>

		<div x-show="loading" x-cloak class="px-6 py-3 border-b border-brand-100 bg-brand-50/60 text-xs font-semibold text-brand-700">Updating table results...</div>
		@include('admin.users.partials.users-table', ['users' => $users])
	</section>

	@if(($wizard['render'] ?? false) && isset($wizard['roles'], $wizard['permissions'], $wizard['rolePermissionMap']))
		<div x-show="wizardOpen" x-cloak class="fixed inset-0 z-[100100] flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" @keydown.escape.window="closeWizard()">
			<div class="absolute inset-0 bg-gray-900/65 backdrop-blur-sm" @click="closeWizard()"></div>
			<div class="relative w-full max-w-6xl">
				@include('admin.users.partials.user-wizard-modal', [
					'mode' => $wizard['mode'] ?? 'create',
					'title' => $wizard['title'] ?? 'User Wizard',
					'subtitle' => $wizard['subtitle'] ?? 'Configure user details and access controls.',
					'action' => $wizard['action'] ?? route('admin.users.store'),
					'method' => $wizard['method'] ?? 'POST',
					'cancelUrl' => $wizardCloseUrl,
					'user' => $wizard['user'] ?? null,
					'roles' => $wizard['roles'],
					'permissions' => $wizard['permissions'],
					'permissionDescriptions' => $wizard['permissionDescriptions'] ?? [],
					'rolePermissionMap' => $wizard['rolePermissionMap'],
					'canManagePermissions' => $wizard['canManagePermissions'] ?? false,
					'directPermissions' => $wizard['directPermissions'] ?? [],
					'selectedRole' => $wizard['selectedRole'] ?? old('role', ''),
					'selectedStatus' => $wizard['selectedStatus'] ?? old('status', 'active'),
					'asOverlay' => true,
				])
			</div>
		</div>
	@endif

	<div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center p-4 sm:p-6 lg:p-8" role="dialog" aria-modal="true">
		<div class="fixed inset-0 bg-gray-900/65 backdrop-blur-sm" @click="closeDeleteModal()"></div>

		<div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
			<div class="px-6 pt-6 pb-4 border-b border-gray-100">
				<h3 class="text-lg font-bold text-gray-900">Confirm User Deletion</h3>
				<p class="mt-2 text-sm text-gray-600">You are about to permanently delete <span class="font-semibold text-gray-900" x-text="deleteModalName"></span>. This action cannot be undone.</p>
				<div class="mt-3 rounded-xl border border-error-200 bg-error-50 px-3 py-2 text-xs font-medium text-error-700">
					This is permanent and cannot be reversed.
				</div>
			</div>
			<form method="POST" :action="deleteModalAction" class="px-6 py-5 flex items-center justify-end gap-3">
				@csrf
				@method('DELETE')
				<button type="button" @click="closeDeleteModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
				<button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-error-600 hover:bg-error-700 text-white text-sm font-semibold transition-colors">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
					Yes, Delete User
				</button>
			</form>
		</div>
	</div>
</div>
@endsection

@push('scripts')
	<script>
		function adminUsersIndex(config) {
			return {
				endpoint: config.endpoint,
				selectedSegment: config.selectedSegment || '',
				page: Number(config.initialPage || 1),
				wizardOpen: Boolean(config.wizardOpen || false),
				wizardOpenedFromServer: Boolean(config.wizardOpen || false),
				wizardMode: config.wizardMode || 'create',
				wizardCloseUrl: config.wizardCloseUrl || config.endpoint,
				createWizardUrl: config.createWizardUrl || config.endpoint,
				loading: false,
				requestToken: 0,
				deleteModalOpen: false,
				deleteModalName: '',
				deleteModalAction: '',
				filters: {
					search: config.initialFilters?.search || '',
					role: config.initialFilters?.role || '',
					status: config.initialFilters?.status || '',
					account_type: config.initialFilters?.account_type || '',
					created_from: config.initialFilters?.created_from || '',
					created_to: config.initialFilters?.created_to || '',
					per_page: config.initialFilters?.per_page || '25',
				},
				buildQuery(pageOverride = null) {
					const query = new URLSearchParams();
					if (this.selectedSegment !== '') {
						query.set('segment', this.selectedSegment);
					}
					Object.entries(this.filters).forEach(([key, value]) => {
						if (value !== null && value !== undefined && value !== '') {
							query.set(key, value);
						}
					});
					query.set('page', String(pageOverride ?? this.page ?? 1));
					query.set('partial', '1');
					return query;
				},
				refresh(resetPage = false) {
					if (resetPage) {
						this.page = 1;
					}
					this.fetchTable();
				},
				fetchTable() {
					const token = ++this.requestToken;
					this.loading = true;
					const url = `${this.endpoint}?${this.buildQuery().toString()}`;

					fetch(url, {
						headers: {
							'X-Requested-With': 'XMLHttpRequest',
							'Accept': 'application/json',
						},
					})
						.then((response) => response.json())
						.then((payload) => {
							if (token !== this.requestToken) {
								return;
							}

							if (this.$refs.rowsWrapper) {
								this.$refs.rowsWrapper.innerHTML = payload.rows || '';
							}

							if (this.$refs.paginationWrapper) {
								this.$refs.paginationWrapper.innerHTML = payload.pagination || '';
							}
						})
						.finally(() => {
							if (token === this.requestToken) {
								this.loading = false;
							}
						});
				},
				handlePaginationClick(event) {
					const link = event.target.closest('a');
					if (!link || !this.$refs.paginationWrapper || !this.$refs.paginationWrapper.contains(link)) {
						return;
					}

					event.preventDefault();
					const parsed = new URL(link.href);
					this.page = Number(parsed.searchParams.get('page') || 1);
					this.fetchTable();
				},
				openDeleteModal(name, action) {
					this.deleteModalName = name;
					this.deleteModalAction = action;
					this.deleteModalOpen = true;
				},
				closeDeleteModal() {
					this.deleteModalOpen = false;
					this.deleteModalName = '';
					this.deleteModalAction = '';
				},
				closeWizard() {
					if (!this.wizardOpenedFromServer) {
						this.wizardOpen = false;
						return;
					}

					window.location.assign(this.wizardCloseUrl);
				},
				openCreateWizard() {
					if (this.wizardMode !== 'create') {
						window.location.assign(this.createWizardUrl);
						return;
					}

					this.wizardOpenedFromServer = false;
					this.wizardOpen = true;
				},
				clearColumnFilters() {
					this.filters.search = '';
					this.filters.role = '';
					this.filters.status = '';
					this.filters.account_type = '';
					this.filters.created_from = '';
					this.filters.created_to = '';
					this.filters.per_page = '25';
					this.refresh(true);
				},
				init() {
					this.$el.addEventListener('click', (event) => this.handlePaginationClick(event));
				}
			};
		}
	</script>
@endpush
