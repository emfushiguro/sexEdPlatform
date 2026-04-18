{{-- resources/views/layouts/instructor-header.blade.php --}}
@php
$headerPendingEnrollments = \App\Models\ModuleEnrollment::pending()
    ->whereHas('module', fn($q) => $q->where('created_by', auth()->id()))
    ->with(['user', 'module'])
    ->latest()
    ->limit(10)
    ->get();
$headerPendingCount = $headerPendingEnrollments->count();
$headerQuizTakingSummary = $quizTakingSummary ?? [
    'attempt_count' => 0,
    'learner_count' => 0,
];
$headerInstructorNotifications = $instructorNotifications ?? auth()->user()->notifications()->latest()->limit(8)->get();
$headerUnreadCount = auth()->user()->unreadNotifications()->count();
$notificationBadgeCount = $headerUnreadCount;
$payloadNormalizer = app(\App\Support\NotificationPayloadNormalizer::class);
$headerUser = auth()->user();
$headerAvatarPath = $headerUser?->instructorProfile?->profile_photo_path ?? $headerUser?->learnerProfile?->avatar_path;
$headerAvatarUrl = null;
if (is_string($headerAvatarPath) && trim($headerAvatarPath) !== '') {
    $normalizedHeaderAvatarPath = ltrim(trim($headerAvatarPath), '/');

    if (\Illuminate\Support\Str::startsWith($normalizedHeaderAvatarPath, ['http://', 'https://', '//'])) {
        $headerAvatarUrl = $normalizedHeaderAvatarPath;
    } else {
        if (\Illuminate\Support\Str::startsWith($normalizedHeaderAvatarPath, 'storage/')) {
            $normalizedHeaderAvatarPath = substr($normalizedHeaderAvatarPath, 8);
        }

        $headerAvatarUrl = asset('storage/' . $normalizedHeaderAvatarPath);
    }
}
@endphp

<header
    class="sticky top-0 z-[9998] bg-white border-b border-gray-200 h-16 flex items-center px-4 md:px-6 gap-4"
>
    <span class="hidden" data-chat-unread-badge-role="instructor"></span>

    {{-- ── Sidebar toggle (desktop) ── --}}
    <button
        @click="$store.instructorSidebar.toggleExpanded()"
        class="hidden xl:flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors"
        title="Toggle sidebar"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- ── Sidebar toggle (mobile) ── --}}
    <button
        @click="$store.instructorSidebar.toggleMobileOpen()"
        class="flex xl:hidden items-center justify-center w-10 h-10 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors"
        title="Open menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <div class="flex-1"></div>

    <div class="flex items-center gap-3 ml-auto">

        {{-- ── Notification bell ── --}}
        <div
            class="relative"
            x-data="{
                open: false,
                syncReadState() {
                    window.axios.post('{{ route('instructor.notifications.dropdown-open') }}').catch(() => {});
                }
            }"
        >
            <button
                @click="open = !open; if (open) { syncReadState(); }"
                class="relative w-10 h-10 flex items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 hover:bg-gray-100 transition-colors"
                title="Notifications"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if($notificationBadgeCount > 0)
                <span data-testid="instructor-notification-badge" class="absolute -top-0.5 -right-0.5 w-4 h-4 flex items-center justify-center rounded-full bg-red-500 text-white text-[9px] font-bold">
                    {{ $notificationBadgeCount > 9 ? '9+' : $notificationBadgeCount }}
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
                class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden"
            >
                <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-brand-50 via-white to-brand-100/70 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                        <p class="text-xs text-gray-500">Instructor-side updates and actions.</p>
                    </div>
                    @if($headerUnreadCount > 0)
                    <form method="POST" action="{{ route('instructor.notifications.mark-all-read') }}">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-brand-700 hover:text-brand-900 transition-colors">
                            Mark all read
                        </button>
                    </form>
                    @endif
                </div>

                @if(($headerQuizTakingSummary['attempt_count'] ?? 0) > 0)
                <div class="px-4 py-3 border-b border-gray-50 bg-brand-50/70" data-testid="quiz-taking-summary">
                    <p class="text-xs font-semibold text-brand-700">New quiz activity</p>
                    <p class="text-[11px] text-brand-700/80 mt-0.5">
                        {{ $headerQuizTakingSummary['attempt_count'] }} attempts from {{ $headerQuizTakingSummary['learner_count'] }} learners in the last 24 hours.
                    </p>
                </div>
                @endif

                @if($headerInstructorNotifications->isNotEmpty())
                <div class="divide-y divide-gray-50 max-h-56 overflow-y-auto">
                    @foreach($headerInstructorNotifications as $notification)
                    @php
                        $isUnread = is_null($notification->read_at);
                        $normalized = $payloadNormalizer->normalize((array) $notification->data);
                        $isChatMessage = $normalized['type'] === 'chat_message_received';
                        $senderName = $normalized['sender_name'] ?? 'User';
                        $senderAvatarUrl = $normalized['sender_avatar_url'] ?? null;
                        $messagePreview = $normalized['message_preview'] ?: $normalized['message'];

                        $severityClass = match($normalized['severity']) {
                            'success' => 'border-l-4 border-emerald-500',
                            'error' => 'border-l-4 border-rose-500',
                            default => 'border-l-4 border-slate-300',
                        };
                    @endphp
                    <a href="{{ route('instructor.notifications.read', $notification->id) }}" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition-colors {{ $severityClass }} {{ $isUnread ? 'bg-rose-50/40' : '' }}">
                        @if($isChatMessage)
                            <div class="h-9 w-9 shrink-0 rounded-full overflow-hidden bg-blue-100 text-blue-700 flex items-center justify-center">
                                @if($senderAvatarUrl)
                                    <img src="{{ $senderAvatarUrl }}" alt="{{ $senderName }}" class="h-9 w-9 rounded-full object-cover">
                                @else
                                    <span class="text-xs font-bold">{{ $normalized['sender_initial'] }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-800">{{ $normalized['title'] }}</p>
                            @if($isChatMessage)
                                <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ $senderName }}</p>
                            @endif
                            <p class="text-[11px] text-gray-600 mt-0.5 line-clamp-2">{{ $isChatMessage ? '"' . $messagePreview . '"' : $normalized['message'] }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="px-4 py-2 border-t border-gray-100">
                    <a href="{{ route('instructor.notifications.index') }}" class="block text-center text-xs font-medium text-brand-700 hover:text-brand-900 transition-colors py-1">
                        View all notifications ->
                    </a>
                </div>
                @endif

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
                                <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors">Reject</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="px-4 py-2 border-t border-gray-100">
                    <a href="{{ route('instructor.enrollments.index') }}" class="block text-center text-xs font-medium text-brand-700 hover:text-brand-900 transition-colors py-1">
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
                class="flex items-center gap-2 px-2 py-1.5 rounded-xl border border-gray-200 hover:bg-gray-100 transition-colors"
                title="Account menu"
            >
                @if($headerAvatarUrl)
                    <img src="{{ $headerAvatarUrl }}" alt="Instructor profile" class="w-8 h-8 rounded-full object-cover border border-gray-200">
                @else
                    <span class="w-8 h-8 rounded-full inline-flex items-center justify-center text-white text-sm font-bold bg-gradient-to-r from-brand-500 to-brand-900">
                        {{ strtoupper(mb_substr(Auth::user()->first_name ?? Auth::user()->name, 0, 1)) }}
                    </span>
                @endif
                <span class="hidden sm:block max-w-[110px] truncate text-sm font-medium text-gray-700">{{ Auth::user()->first_name ?? Auth::user()->name }}</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
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
                class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1"
            >
                <div class="px-4 py-2 border-b border-gray-100">
                    <p class="text-xs font-semibold text-gray-900 truncate">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                    <p class="text-[11px] text-gray-400 truncate">Instructor</p>
                </div>

                <a href="{{ route('instructor.profile.show') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.4 0 4.66.605 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    View Profile
                </a>

                @include('partials.chat-status-selector')

                <form method="POST" action="{{ route('instructor.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2">
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
