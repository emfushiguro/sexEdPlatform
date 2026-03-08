{{-- resources/views/layouts/instructor-header.blade.php --}}
@php
$headerPendingEnrollments = \App\Models\ModuleEnrollment::pending()
    ->whereHas('module', fn($q) => $q->where('created_by', auth()->id()))
    ->with(['user', 'module'])
    ->latest()
    ->limit(10)
    ->get();
$headerPendingCount = $headerPendingEnrollments->count();
@endphp

<header
    class="sticky top-0 z-[9998] bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 h-16 flex items-center px-4 md:px-6 gap-4"
    x-data="instructorSearch()"
>

    {{-- ── Sidebar toggle (desktop) ── --}}
    <button
        @click="$store.instructorSidebar.toggleExpanded()"
        class="hidden xl:flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        title="Toggle sidebar"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- ── Sidebar toggle (mobile) ── --}}
    <button
        @click="$store.instructorSidebar.toggleMobileOpen()"
        class="flex xl:hidden items-center justify-center w-9 h-9 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        title="Open menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- ── Search bar ── --}}
    <div class="flex-1 max-w-lg relative">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input
                type="text"
                x-model="query"
                @input.debounce.300ms="search()"
                @focus="open = true"
                @click.away="open = false"
                placeholder="Search modules, lessons, learners..."
                class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-400 transition-all"
                autocomplete="off"
            >
        </div>

        {{-- Search results dropdown --}}
        <div
            x-show="open && (results.modules.length || results.lessons.length || results.learners.length)"
            x-cloak
            @click.away="open = false"
            class="absolute top-full mt-1 left-0 right-0 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 z-50 overflow-hidden"
        >
            <template x-if="results.modules.length">
                <div class="p-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Modules</p>
                    <template x-for="item in results.modules" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 transition-colors text-sm text-gray-700">
                            <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253" />
                            </svg>
                            <span x-text="item.title" class="truncate"></span>
                        </a>
                    </template>
                </div>
            </template>

            <template x-if="results.lessons.length">
                <div class="p-2 border-t border-gray-50">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Lessons</p>
                    <template x-for="item in results.lessons" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 transition-colors text-sm text-gray-700">
                            <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span x-text="item.title" class="truncate"></span>
                        </a>
                    </template>
                </div>
            </template>

            <template x-if="results.learners.length">
                <div class="p-2 border-t border-gray-50">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Learners</p>
                    <template x-for="item in results.learners" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 transition-colors text-sm text-gray-700">
                            <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span x-text="item.name" class="truncate"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center gap-3 ml-auto">

        {{-- ── Dark mode toggle ── --}}
        <button
            @click="$store.theme.toggle()"
            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            title="Toggle dark mode"
        >
            {{-- Sun icon (shown in dark mode) --}}
            <svg x-show="$store.theme.mode === 'dark'" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M18.364 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
            </svg>
            {{-- Moon icon (shown in light mode) --}}
            <svg x-show="$store.theme.mode !== 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>

        {{-- ── Notification bell ── --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="relative w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                title="Notifications"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if($headerPendingCount > 0)
                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 flex items-center justify-center rounded-full bg-red-500 text-white text-[9px] font-bold">
                    {{ $headerPendingCount > 9 ? '9+' : $headerPendingCount }}
                </span>
                @endif
            </button>

            {{-- Notification dropdown --}}
            <div
                x-show="open"
                @click.away="open = false"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 z-50 overflow-hidden"
            >
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Pending Requests</h3>
                    @if($headerPendingCount > 0)
                    <span class="text-xs font-medium text-purple-600">{{ $headerPendingCount }} pending</span>
                    @endif
                </div>

                @if($headerPendingEnrollments->isEmpty())
                <div class="px-4 py-6 text-center">
                    <p class="text-sm text-gray-400">No pending requests</p>
                </div>
                @else
                <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                    @foreach($headerPendingEnrollments as $enrollment)
                    <div class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-bold flex-shrink-0">
                                {{ strtoupper(mb_substr($enrollment->user->first_name ?? $enrollment->user->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 truncate">
                                    {{ $enrollment->user->first_name ?? $enrollment->user->name }} {{ $enrollment->user->last_name ?? '' }}
                                </p>
                                <p class="text-[11px] text-gray-500 truncate">{{ $enrollment->module->title ?? 'Unknown module' }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $enrollment->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-2 ml-11">
                            <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Reject</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="px-4 py-2 border-t border-gray-100">
                    <a href="{{ route('instructor.enrollments.index') }}" class="block text-center text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors py-1">
                        View all requests →
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Avatar dropdown ── --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"
                title="Account menu"
            >
                {{ strtoupper(mb_substr(Auth::user()->first_name ?? Auth::user()->name, 0, 1)) }}
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 z-50 overflow-hidden py-1"
            >
                <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate">Instructor</p>
                </div>
                <form method="POST" action="{{ route('instructor.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log out
                    </button>
                </form>
            </div>
        </div>

    </div>
</header>

@push('scripts')
<script>
function instructorSearch() {
    return {
        query: '',
        open: false,
        results: { modules: [], lessons: [], learners: [] },
        async search() {
            if (this.query.length < 2) {
                this.results = { modules: [], lessons: [], learners: [] };
                this.open = false;
                return;
            }
            try {
                const res = await fetch(`/instructor/search?q=${encodeURIComponent(this.query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                if (res.ok) {
                    this.results = await res.json();
                    this.open = true;
                }
            } catch(e) {
                console.error('Search failed', e);
            }
        }
    };
}
</script>
@endpush
