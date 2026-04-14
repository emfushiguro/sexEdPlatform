@php
    $selectedSegment = $filters['segment'] ?? '';
    $usersIndexRoute = request()->routeIs('admin.learners.*') ? 'admin.learners.index' : 'admin.users.index';
    $isLearnerMonitoring = request()->routeIs('admin.learners.*') || $selectedSegment === 'learners';
@endphp

<div class="px-6 py-4 border-b border-gray-100" x-data="{ timer: null }">
    <form method="GET" action="{{ route($usersIndexRoute) }}" x-ref="filtersForm">
        <input type="hidden" name="segment" value="{{ $selectedSegment }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            <input
                type="text"
                name="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search name, email, or role"
                class="lg:col-span-2 px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition"
                x-on:input="clearTimeout(timer); timer = setTimeout(() => { $refs.filtersForm.submit(); }, 400)"
            >

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

            <select name="date_preset" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                <option value="">Custom Dates</option>
                <option value="today" @selected(($filters['date_preset'] ?? '') === 'today')>Today</option>
                <option value="this_week" @selected(($filters['date_preset'] ?? '') === 'this_week')>This Week</option>
                <option value="this_month" @selected(($filters['date_preset'] ?? '') === 'this_month')>This Month</option>
                <option value="last_30_days" @selected(($filters['date_preset'] ?? '') === 'last_30_days')>Last 30 Days</option>
            </select>

            <input
                type="date"
                name="created_from"
                value="{{ $filters['created_from'] ?? '' }}"
                class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                title="Created from"
            >

            <input
                type="date"
                name="created_to"
                value="{{ $filters['created_to'] ?? '' }}"
                class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                title="Created to"
            >

            @if($isLearnerMonitoring)
                <select name="learner_scope" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="all" @selected(($filters['learner_scope'] ?? 'all') === 'all')>All Learners</option>
                    <option value="platform" @selected(($filters['learner_scope'] ?? '') === 'platform')>Platform Enrolled</option>
                    <option value="instructor" @selected(($filters['learner_scope'] ?? '') === 'instructor')>Instructor Enrolled</option>
                </select>
            @endif

            <select name="per_page" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                @foreach([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / page</option>
                @endforeach
            </select>

            <div class="flex items-center justify-end gap-2 lg:col-span-2">
                <a href="{{ route($usersIndexRoute, ['segment' => $selectedSegment]) }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Clear</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors">Apply</button>
            </div>
        </div>
    </form>
</div>
