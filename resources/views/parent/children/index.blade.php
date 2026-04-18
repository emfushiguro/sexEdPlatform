@extends('layouts.learner-app')

@section('title', 'My Children')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Gradient banner header --}}
    <div class="rounded-2xl p-6 text-white flex items-center justify-between"
         style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div>
            <h1 class="text-2xl font-bold">My Children</h1>
            <p class="text-white/80 text-sm mt-1">Manage your child applications and monitor approved accounts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('parent.invitations.index') }}"
               class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 text-white font-semibold py-2 px-4 rounded-xl transition text-sm backdrop-blur-sm border border-white/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H6a4 4 0 00-4 4v1h5m6-5a4 4 0 10-8 0 4 4 0 008 0zm6-3a3 3 0 10-6 0 3 3 0 006 0z"/>
                </svg>
                Invite Existing Learner
            </a>
            <a href="{{ route('parent.create-child') }}"
               class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-semibold py-2 px-4 rounded-xl transition text-sm backdrop-blur-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Child
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->has('verification_document'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
            {{ $errors->first('verification_document') }}
        </div>
    @endif

    @php
        $pendingApprovalNotifications = $pendingApprovalNotifications ?? collect();
        $outgoingInvitations = $outgoingInvitations ?? collect();
    @endphp

    @if($pendingApprovalNotifications->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Pending Enrollment Approvals</h2>
                    <p class="text-sm text-gray-500 mt-1">Unread child enrollment requests that need your review.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                    {{ $pendingApprovalNotifications->count() }} unread
                </span>
            </div>

            <div class="mt-4 space-y-3">
                @foreach($pendingApprovalNotifications as $notification)
                    @php
                        $childName = data_get($notification->data, 'child_name', 'Child');
                        $moduleTitle = data_get($notification->data, 'module_title', 'Module');
                        $actionUrl = data_get($notification->data, 'action_url');
                    @endphp

                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $childName }} requested access to {{ $moduleTitle }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>

                            @if($actionUrl)
                                <a href="{{ route('learner.notifications.read', $notification->id) }}"
                                   class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-100">
                                    Review Enrollment Request
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($outgoingInvitations->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Parent Link Invitations</h2>
                    <p class="text-sm text-gray-500 mt-1">Track invitations sent to existing learner accounts.</p>
                </div>
                <a href="{{ route('parent.invitations.index') }}"
                   class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-100">
                    Manage Invitations
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @foreach($outgoingInvitations as $invitation)
                    @php
                        $statusValue = $invitation->status instanceof \App\Enums\ParentChildInvitationStatus
                            ? $invitation->status->value
                            : (string) $invitation->status;
                        $statusClass = match ($statusValue) {
                            'accepted' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                            'cancelled' => 'bg-gray-100 text-gray-700',
                            'expired' => 'bg-orange-100 text-orange-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                    @endphp
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $invitation->child?->name ?? 'Learner' }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $invitation->child?->email ?? 'No email' }}
                                    @if($invitation->child?->learnerProfile?->username)
                                        · {{ $invitation->child->learnerProfile->username }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($statusValue) }}
                                </span>
                                <a href="{{ route('parent.invitations.show', $invitation) }}"
                                   class="inline-flex items-center rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-50">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Children list --}}
    @if($children->isEmpty())
        {{-- Empty state --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center shadow-sm">
            <div class="w-20 h-20 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No children added yet</h3>
            <p class="text-gray-500 text-sm mb-6 max-w-sm mx-auto">
                Create a learning account for your child. You'll be able to monitor their progress and quiz results.
            </p>
            <a href="{{ route('parent.create-child') }}"
               style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
               class="inline-flex items-center gap-2 text-white font-semibold py-2.5 px-6 rounded-xl hover:opacity-90 transition text-sm shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Your First Child
            </a>
        </div>
    @else
        {{-- Children cards grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($children as $child)
                @php
                    $verificationStatus = $child->pivot->verification_status ?? 'pending';
                    $isApprovedChild = $verificationStatus === 'approved';
                    $childAvatarUrl = $child->learnerProfile?->avatar_path
                        ? asset('storage/' . ltrim((string) $child->learnerProfile->avatar_path, '/'))
                        : null;
                @endphp
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden">

                    {{-- Card top: avatar + info + badge --}}
                    <div class="p-5 flex items-center gap-4">
                        {{-- Avatar initials --}}
                        @if($childAvatarUrl)
                            <img src="{{ $childAvatarUrl }}"
                                 alt="{{ $child->full_name }} avatar"
                                 class="flex-shrink-0 w-14 h-14 rounded-full object-cover border border-gray-200 shadow">
                        @else
                            <div class="flex-shrink-0 w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold shadow"
                                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                {{ strtoupper(substr($child->first_name ?? $child->name, 0, 1)) }}{{ strtoupper(substr($child->last_name ?? '', 0, 1)) }}
                            </div>
                        @endif

                        {{-- Name & age --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 truncate">{{ $child->full_name }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if($child->learnerProfile?->birthdate)
                                    {{ \Carbon\Carbon::parse($child->learnerProfile->birthdate)->age }} years old.
                                @endif
                                Added {{ $child->created_at->diffForHumans() }}
                            </p>
                            @if($child->learnerProfile?->username)
                                <p class="text-xs text-purple-600 mt-0.5">{{ $child->learnerProfile->username }}</p>
                            @endif
                        </div>

                        {{-- Consent + verification badges --}}
                        <div class="flex flex-col items-end gap-1">
                            @if($child->learnerProfile?->requires_parental_consent)
                                <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Under 13</span>
                            @else
                                <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Teen</span>
                            @endif

                            <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full {{ $isApprovedChild ? 'bg-emerald-100 text-emerald-700' : ($verificationStatus === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ ucfirst($verificationStatus) }}
                            </span>
                        </div>
                    </div>

                    @if(!$isApprovedChild)
                        <div class="px-5 py-3 border-t border-gray-100 {{ $verificationStatus === 'rejected' ? 'bg-rose-50' : 'bg-amber-50' }}">
                            <p class="text-xs {{ $verificationStatus === 'rejected' ? 'text-rose-700' : 'text-amber-800' }}">
                                @if($verificationStatus === 'rejected')
                                    Verification needs correction.
                                    @if(!empty($child->pivot->verification_rejection_reason))
                                        Reason: {{ trim((string) preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags((string) $child->pivot->verification_rejection_reason), ENT_QUOTES | ENT_HTML5, 'UTF-8')))) }}
                                    @endif
                                @else
                                    Pending verification.
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Stats row --}}
                    <div class="border-t border-gray-100 grid grid-cols-3 divide-x divide-gray-100 text-center py-3 bg-gray-50/50">
                        <div class="px-2">
                            <p class="text-lg font-bold text-purple-700">{{ $child->moduleEnrollments()->count() }}</p>
                            <p class="text-xs text-gray-500">Modules</p>
                        </div>
                        <div class="px-2">
                            <p class="text-lg font-bold text-purple-700">{{ $child->quizAttempts()->count() }}</p>
                            <p class="text-xs text-gray-500">Quizzes</p>
                        </div>
                        <div class="px-2">
                            <p class="text-lg font-bold text-purple-700">{{ $child->achievements()->count() }}</p>
                            <p class="text-xs text-gray-500">Achievements</p>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    @if($child->moduleEnrollments()->count() > 0)
                        @php
                            $enrolledModuleIds = $child->moduleEnrollments()
                                ->whereIn('status', ['approved', 'completed'])
                                ->pluck('module_id');

                            $totalLessons = \App\Models\Lesson::query()
                                ->whereIn('module_id', $enrolledModuleIds)
                                ->count();

                            $completedLessons = \App\Models\UserProgress::query()
                                ->where('user_id', $child->id)
                                ->whereIn('module_id', $enrolledModuleIds)
                                ->where('completed', true)
                                ->distinct('lesson_id')
                                ->count('lesson_id');

                            $pct = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                        @endphp
                        <div class="px-5 py-3 border-t border-gray-100">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Overall Progress</span>
                                <span class="font-medium text-gray-700">{{ $pct }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all"
                                     style="width: {{ $pct }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);">
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                        @if($isApprovedChild)
                            <a href="{{ route('parent.children.show', $child->id) }}"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-purple-700 hover:text-purple-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                View Child Dashboard
                            </a>

                            <span class="text-sm text-gray-400">
                                {{ $child->quizAttempts()->count() > 0 ? 'Quiz activity available' : 'No quiz data yet' }}
                            </span>
                        @else
                            @if($verificationStatus === 'rejected')
                                <form method="POST"
                                      action="{{ route('parent.children.verification.resubmit', $child) }}"
                                      enctype="multipart/form-data"
                                      class="w-full space-y-2">
                                    @csrf
                                    <label for="verification_document_{{ $child->id }}" class="block text-xs font-medium text-gray-700">
                                        Upload corrected PSA document
                                    </label>
                                    <input id="verification_document_{{ $child->id }}"
                                           name="verification_document"
                                           type="file"
                                           required
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           class="w-full rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs text-gray-900 file:mr-2 file:rounded-md file:border-0 file:bg-purple-100 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-purple-700">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs text-gray-500">JPG, PNG, or PDF up to 5MB.</span>
                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-semibold text-white"
                                                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                                            Resubmit Child Verification
                                        </button>
                                    </div>
                                </form>
                            @else
                                <span class="text-sm text-gray-500">Pending verification</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
