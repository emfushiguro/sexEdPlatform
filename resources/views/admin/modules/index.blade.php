@extends('layouts.admin')

@section('title', 'All Modules')

@section('content')
@php
    $scope = $scope ?? 'all';
    $status = $status ?? 'all';
    $search = $search ?? '';
    $ownerType = $ownerType ?? 'all';

    $contentRoutePrefix = 'admin';
    $isContentAdminPanel = true;

    $prefillModule = null;
    $prefillModulePayload = null;

    if (request()->filled('edit_module')) {
        $prefillModule = $modules->getCollection()->firstWhere('id', (int) request('edit_module'));
        if ($prefillModule) {
            $prefillModulePayload = [
                'id' => $prefillModule->id,
                'title' => $prefillModule->title,
                'description' => $prefillModule->description,
                'age_bracket' => $prefillModule->min_age >= 18 ? 'adults' : ($prefillModule->min_age >= 13 ? 'teens' : 'kids'),
                'enrollment_mode' => $prefillModule->enrollment_mode,
                'access_type' => $prefillModule->access_type,
                'price_amount' => $prefillModule->price_amount,
                'price_currency' => $prefillModule->price_currency,
                'enrollment_limit' => $prefillModule->enrollment_limit,
                'is_published' => (bool) $prefillModule->is_published,
                'thumbnail_url' => $prefillModule->thumbnail_url,
                'action' => $prefillModule->trashed() ? 'archive' : ($prefillModule->is_published ? 'publish' : 'draft'),
            ];
        }
    }

    $statusOptions = [
        'all' => 'All Statuses',
        'published' => 'Published',
        'draft' => 'Draft',
        'pending' => 'Pending Review',
        'archived' => 'Archived',
    ];

    $scopeTabs = [
        'all' => 'All Modules',
        'platform' => 'Platform Modules',
        'instructor' => 'Instructor Modules',
    ];

    $ownerTypeOptions = [
        'all' => 'All Owner Types',
        'platform' => 'Platform',
        'instructor' => 'Instructor',
    ];
@endphp

<div x-data="{
    searchTerm: @js($search),
    statusFilter: @js($status),
    ownerFilter: @js($ownerType),
    deleteModalOpen: false,
    deleteForm: null,
    deleteMessage: 'Proceed with this action?',
    openDeleteConfirm(form, message) {
        this.deleteForm = form;
        this.deleteMessage = message;
        this.deleteModalOpen = true;
    },
    closeDeleteConfirm() {
        this.deleteModalOpen = false;
        this.deleteForm = null;
        this.deleteMessage = 'Proceed with this action?';
    },
    confirmDelete() {
        if (this.deleteForm) {
            this.deleteForm.submit();
        }
    },
    resetFilters() {
        this.searchTerm = '';
        this.statusFilter = 'all';
        this.ownerFilter = 'all';
    },
    matchesModule(module) {
        const query = this.searchTerm.trim().toLowerCase();
        const searchMatch = query === ''
            || module.title.includes(query)
            || module.description.includes(query)
            || module.owner.includes(query);

        const statusMatch = this.statusFilter === 'all' || module.status === this.statusFilter;
        const ownerMatch = this.ownerFilter === 'all' || module.ownerType === this.ownerFilter;

        return searchMatch && statusMatch && ownerMatch;
    }
}"
@if(request()->boolean('create_module'))
    x-init="$store.modals.openModuleModal()"
@elseif($prefillModulePayload)
    x-init="$store.modals.openModuleModal({{ Js::from($prefillModulePayload) }})"
@endif
class="space-y-5">

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h1 class="text-xl font-bold text-gray-900">All Modules</h1>
            <p class="text-sm text-gray-500">Platform and instructor modules in one consistent management view.</p>
        </div>
        <button @click="$store.modals.openModuleModal()"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Create Module
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-2 rounded-xl bg-gray-100 p-1">
        @foreach($scopeTabs as $tabValue => $tabLabel)
            <a href="{{ request()->fullUrlWithQuery(['scope' => $tabValue, 'page' => null]) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $scope === $tabValue ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    <form method="GET"
          action="{{ route('admin.modules.index') }}"
          @submit.prevent
          data-testid="admin-table-filter-bar"
          class="rounded-2xl border border-gray-200 bg-white p-4 md:p-5">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
            <div class="md:col-span-6">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Search Module Title</label>
                <input type="text"
                       x-model.debounce.250ms="searchTerm"
                       placeholder="Search module title..."
                       data-testid="admin-modules-search-input"
                       class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200">
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Status</label>
                <select x-model="statusFilter"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">Owner</label>
                <select x-model="ownerFilter"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    @foreach($ownerTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($ownerType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end gap-2">
            <button type="button" @click="resetFilters()" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-200">Reset</button>
        </div>

        <noscript>
            <div class="mt-3 flex items-center justify-end">
                <button type="submit" class="rounded-xl bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">Apply Filters</button>
            </div>
        </noscript>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($modules as $module)
            @php
                $ownerTypeValue = in_array($module->content_owner_type, ['admin', 'instructor'], true)
                    ? $module->content_owner_type
                    : ((string) optional($module->creator)->role === 'admin' ? 'admin' : 'instructor');

                $ownerName = $ownerTypeValue === 'admin'
                    ? 'Conscious Connections Team'
                    : (optional($module->creator)->full_name ?: optional($module->creator)->name ?: 'Instructor');

                $isInstructorOwned = $ownerTypeValue === 'instructor';
                $ownerTypeFilterValue = $ownerTypeValue === 'admin' ? 'platform' : 'instructor';

                $profilePhotoPath = optional(optional($module->creator)->instructorProfile)->profile_photo_path;
                $ownerAvatar = null;

                if ($isInstructorOwned && filled($profilePhotoPath)) {
                    $ownerAvatar = \Illuminate\Support\Str::startsWith($profilePhotoPath, ['http://', 'https://', '//'])
                        ? $profilePhotoPath
                        : asset('storage/' . ltrim(str_replace('storage/', '', (string) $profilePhotoPath), '/'));
                }

                $ownerInitials = strtoupper(substr((string) \Illuminate\Support\Str::of($ownerName)->trim()->explode(' ')->get(0, 'I'), 0, 1));

                if ($isInstructorOwned) {
                    $lastNameInitial = strtoupper(substr((string) \Illuminate\Support\Str::of($ownerName)->trim()->explode(' ')->get(1, ''), 0, 1));
                    $ownerInitials = trim($ownerInitials . $lastNameInitial);
                }

                $statusValue = $module->trashed()
                    ? 'archived'
                    : ($module->is_published
                        ? 'published'
                        : (in_array((string) $module->current_review_status, ['submitted', 'in_review', 'needs_revision'], true)
                            ? 'pending'
                            : 'draft'));

                $statusClasses = match ($statusValue) {
                    'published' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'archived' => 'bg-rose-100 text-rose-700 border-rose-200',
                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                };

                $statusLabel = match ($statusValue) {
                    'published' => 'Published',
                    'pending' => 'Pending',
                    'archived' => 'Archived',
                    default => 'Draft',
                };

                $moduleModalPayload = [
                    'id' => $module->id,
                    'title' => $module->title,
                    'description' => $module->description,
                    'age_bracket' => $module->min_age >= 18 ? 'adults' : ($module->min_age >= 13 ? 'teens' : 'kids'),
                    'enrollment_mode' => $module->enrollment_mode,
                    'access_type' => $module->access_type,
                    'price_amount' => $module->price_amount,
                    'price_currency' => $module->price_currency,
                    'enrollment_limit' => $module->enrollment_limit,
                    'is_published' => (bool) $module->is_published,
                    'thumbnail_url' => $module->thumbnail_url,
                    'action' => $module->trashed() ? 'archive' : ($module->is_published ? 'publish' : 'draft'),
                ];
            @endphp

                <div x-show="matchesModule({
                    title: @js(strtolower((string) $module->title)),
                    description: @js(strtolower(strip_tags((string) ($module->description ?? '')))),
                    owner: @js(strtolower((string) $ownerName)),
                    status: @js($statusValue),
                    ownerType: @js($ownerTypeFilterValue)
                 })"
                 x-cloak
                 class="rounded-2xl bg-white shadow-sm border border-gray-200 overflow-hidden flex flex-col transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                 data-owner-type="{{ $ownerTypeValue }}">
                <div class="relative h-36 overflow-hidden">
                    @if($module->thumbnail_url)
                        <img src="{{ $module->thumbnail_url }}" alt="{{ $module->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);"></div>
                        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 16px 16px;"></div>
                    @endif

                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full border {{ $statusClasses }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <div class="p-4 flex-1 flex flex-col gap-2">
                    <h3 class="font-semibold text-sm text-gray-900 leading-snug line-clamp-2">{{ $module->title }}</h3>
                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-2">{{ Str::limit(strip_tags($module->description ?? 'No description provided.'), 120) }}</p>

                    <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-3 py-2.5">
                        <div class="min-w-0 flex items-center gap-2.5">
                            @if($ownerAvatar)
                                <img src="{{ $ownerAvatar }}"
                                     alt="{{ $ownerName }}"
                                     data-testid="module-owner-avatar"
                                     class="h-8 w-8 rounded-full border border-gray-200 object-cover">
                            @else
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-[11px] font-semibold text-gray-600" data-testid="module-owner-avatar-fallback">
                                    {{ $ownerInitials !== '' ? $ownerInitials : 'CC' }}
                                </div>
                            @endif

                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-gray-800">{{ $ownerName }}</p>
                                <p class="text-[11px] text-gray-500">{{ $ownerTypeValue === 'admin' ? 'Platform' : 'Instructor' }}</p>
                            </div>
                        </div>

                        <p class="shrink-0 text-[11px] text-gray-500">{{ optional($module->created_at)->format('M d, Y') }}</p>
                    </div>

                    <div class="flex items-center gap-2 mt-auto pt-2 flex-wrap text-[11px] text-gray-500 font-medium">
                        <span>{{ $module->lessons_count }} lesson{{ $module->lessons_count !== 1 ? 's' : '' }}</span>
                        <span>·</span>
                        <span>{{ $module->quizzes_count }} quiz{{ $module->quizzes_count !== 1 ? 'zes' : '' }}</span>
                        <span>·</span>
                        <span>{{ $module->enrolled_count }} enrolled</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-4 py-2.5 flex items-center gap-1 bg-gray-50/60">
                    <a href="{{ route('admin.modules.show', $module) }}"
                       title="View"
                       class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>

                    @if(!$module->trashed())
                        <button type="button"
                                @if(!$isInstructorOwned)
                                    @click="$store.modals.openModuleModal({{ Js::from($moduleModalPayload) }})"
                                @endif
                                @if($isInstructorOwned) disabled @endif
                                title="{{ $isInstructorOwned ? 'Instructor-owned content is read-only in the admin panel.' : 'Edit' }}"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 transition-colors {{ $isInstructorOwned ? 'cursor-not-allowed opacity-50' : 'hover:text-brand-600 hover:bg-brand-50' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" class="inline"
                              @submit.prevent="@if($isInstructorOwned) false @else openDeleteConfirm($event.target, 'Archive this module?') @endif">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    @if($isInstructorOwned) disabled @endif
                                    title="{{ $isInstructorOwned ? 'Instructor-owned content is read-only in the admin panel.' : 'Archive' }}"
                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700'cursor-not-allowed opacity-50' : 'hover:text-amber-700 hover:bg-amber-50' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M5 7l1 12h12l1-12M9 7V4h6v3"/>
                                </svg>
                            </button>
                        </form>
                    @elseif($module->trashed())
                        <form method="POST" action="{{ route('admin.modules.restore', $module->id) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    @if($isInstructorOwned) disabled @endif
                                    title="{{ $isInstructorOwned ? 'Instructor-owned content is read-only in the admin panel.' : 'Restore' }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 transition-colors {{ $isInstructorOwned ? 'cursor-not-allowed opacity-50' : 'hover:text-emerald-700 hover:bg-emerald-50' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.modules.force-delete', $module->id) }}" class="inline"
                              @submit.prevent="@if($isInstructorOwned) false @else openDeleteConfirm($event.target, 'Permanently delete this archived module? This cannot be undone.') @endif">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    @if($isInstructorOwned) disabled @endif
                                    title="{{ $isInstructorOwned ? 'Instructor-owned content is read-only in the admin panel.' : 'Delete' }}"
                                    class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700'cursor-not-allowed opacity-50' : 'hover:text-rose-700 hover:bg-rose-50' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <p class="text-sm font-semibold text-gray-900">No modules found for the selected filters.</p>
                <p class="mt-1 text-xs text-gray-500">Try clearing filters or creating a new module.</p>
            </div>
        @endforelse
    </div>

    @if($modules->hasPages())
        <div class="pt-1">
            {{ $modules->appends(request()->query())->links() }}
        </div>
    @endif

    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/60" @click="closeDeleteConfirm()"></div>
    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Confirm Action</h3>
            <p class="mt-2 text-sm text-gray-600" x-text="deleteMessage"></p>
            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="closeDeleteConfirm()" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="button" @click="confirmDelete()" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm</button>
            </div>
        </div>
    </div>

</div>

@include('instructor.modules.partials.module-modal')
@endsection
