@extends('layouts.connector-app')

@section('title', $seminar->title)
@section('page-title', 'Seminar Details')

@section('content')
    @php
        $avatarUrlFor = function ($user) {
            $path = $user?->learnerProfile?->avatar_path ?? $user?->instructorProfile?->profile_photo_path;

            return $path ? asset('storage/' . ltrim((string) $path, '/')) : null;
        };
        $statusStyles = [
            'draft' => 'bg-gray-100 text-gray-700 border-gray-200',
            'pending_review' => 'bg-amber-100 text-amber-800 border-amber-200',
            'approved' => 'bg-sky-100 text-sky-800 border-sky-200',
            'published' => 'bg-blue-100 text-blue-800 border-blue-200',
            'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'completed' => 'bg-purple-100 text-purple-800 border-purple-200',
            'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
            'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
            'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
        ];
        $statusLabel = \App\Enums\SeminarStatus::tryFrom($seminar->status)?->label() ?? str_replace('_', ' ', $seminar->status);
        $canArchive = in_array($seminar->status, ['rejected', 'completed', 'cancelled'], true);
        $canManageStatus = ! in_array($seminar->status, ['cancelled', 'completed', 'archived'], true);
        $registrantRows = $seminar->registrants
        ->sortByDesc(fn ($registrant) => $registrant->registered_at ?? $registrant->created_at)
        ->take(3)
        ->map(function ($registrant) use ($connector, $seminar, $avatarUrlFor) {
            $user = $registrant->user;
            $isLearner = $registrant->participant_type === 'learner';

            return [
                'id' => $registrant->id,
                'name' => $user?->name ?? 'Unknown user',
                'email' => $user?->email ?? '',
                'avatar' => $avatarUrlFor($user),
                'initial' => strtoupper(mb_substr($user?->name ?? 'U', 0, 1)),
                'registered_at' => $registrant->registered_at?->diffForHumans() ?? 'Unknown',
                'registered_at_full' => $registrant->registered_at?->format('M d, Y g:i A') ?? 'Unknown',
                'status' => $registrant->status,
                'participant_type' => $registrant->participant_type ?? 'participant',
                'age' => $isLearner ? ($user?->calculateAge() ?? 'Unknown') : null,
                'age_category' => $isLearner ? ($user?->age_bracket_cached ?? $user?->learnerProfile?->getAgeBracket() ?? 'Unknown') : null,
                'learner_type' => $isLearner ? ($user?->account_type ?? 'Learner') : null,
                'eligibility_summary' => $isLearner ? 'Matches selected learner age categories.' : null,
                'affiliation' => $user?->instructorProfile?->professional_background ?? 'No organization listed',
                'instructor_status' => $user?->instructorProfile?->status ?? $user?->status ?? 'Unknown',
                'approve_url' => route('connector.seminars.registrants.approve', [$connector, $seminar, $registrant]),
                'reject_url' => route('connector.seminars.registrants.reject', [$connector, $seminar, $registrant]),
                'delete_url' => route('connector.seminars.registrants.destroy', [$connector, $seminar, $registrant]),
                'search_blob' => strtolower(implode(' ', [$user?->name, $user?->email, $registrant->status, $registrant->participant_type])),
            ];
        })->values();
    @endphp
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6" x-data="{ statusOpen: false, cancelOpen: false, archiveOpen: false, completeOpen: false, cancelReason: 'Speaker unavailable', customCancelReason: '' }">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                        <span class="rounded-full border px-2.5 py-1 {{ $statusStyles[$seminar->status] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">{{ $statusLabel }}</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ $seminar->type }}</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold text-gray-900">{{ $seminar->title }}</h2>
                    <p class="mt-2 text-sm text-gray-600">{{ $seminar->purpose }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($seminar->type === 'webinar' && $seminar->livestream_channel && $seminar->status === 'published')
                        <a href="{{ route('connector.seminars.livestream', [$connector, $seminar]) }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Host Livestream</a>
                    @endif
                    <a href="{{ route('connector.seminars.attendance', [$connector, $seminar]) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50" title="Attendance" aria-label="Attendance">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M4.75 5.75h14.5v12.5H4.75z"/></svg>
                    </a>
                    <a href="{{ route('connector.seminars.edit', [$connector, $seminar]) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Edit" aria-label="Edit">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4 4 0 0 1-1.897 1.13L6 18l.8-2.685a4 4 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                    </a>
                    @if(in_array($seminar->status, ['draft', 'rejected'], true))
                        <form method="POST" action="{{ route('connector.seminars.submit-review', [$connector, $seminar]) }}">
                            @csrf
                            <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Submit for Review</button>
                        </form>
                    @endif
                    @if($seminar->status === 'approved')
                        <form method="POST" action="{{ route('connector.seminars.publish', [$connector, $seminar]) }}">
                            @csrf
                            <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Publish</button>
                        </form>
                    @endif
                    <button type="button" @click="statusOpen = true" @disabled(! $canManageStatus) class="rounded-lg border border-green-200 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50 disabled:cursor-not-allowed disabled:opacity-40">Manage Status</button>
                    <button type="button" @click="{{ $canArchive ? 'archiveOpen = true' : '' }}" @disabled(! $canArchive) title="{{ $canArchive ? 'Archive seminar' : 'Active seminars cannot be archived. Complete or cancel first.' }}" aria-label="Archive" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14M7 7v11h10V7M9 7V5h6v2"/></svg>
                    </button>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Schedule</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $seminar->localStartsAt()?->format('M d, Y g:i A') }} - {{ $seminar->localEndsAt()?->format('g:i A') }} PHT</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Capacity</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $seminar->capacity ?? 'Unlimited' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registrants</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $activeRegistrantCount }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $seminar->categoryDisplayName() }}</dd>
                </div>
            </dl>

            <div x-show="statusOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <div @click.outside="statusOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Manage Status</h3>
                    <p class="mt-2 text-sm text-gray-600">Choose the next valid status action for {{ $seminar->title }}.</p>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="statusOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button>
                        @if($seminar->status === 'published')
                            <button type="button" @click="statusOpen = false; completeOpen = true" class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Complete Seminar</button>
                        @endif
                        @if(in_array($seminar->status, ['pending_review', 'approved', 'published'], true))
                            <button type="button" @click="statusOpen = false; cancelOpen = true" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Cancel Seminar</button>
                        @endif
                    </div>
                </div>
            </div>

            <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <form method="POST" action="{{ route('connector.seminars.cancel', [$connector, $seminar]) }}" @click.outside="cancelOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    @csrf
                    <h3 class="text-lg font-bold text-gray-900">Cancel Seminar</h3>
                    <p class="mt-2 text-sm text-gray-600">Cancellation is recorded and active registrants are notified.</p>
                    <select x-model="cancelReason" class="mt-4 w-full rounded-lg border-gray-300 text-sm">
                        <option>Speaker unavailable</option>
                        <option>Technical difficulties</option>
                        <option>Other</option>
                    </select>
                    <textarea x-show="cancelReason === 'Other'" x-model="customCancelReason" class="mt-3 w-full rounded-lg border-gray-300 text-sm" rows="3" placeholder="Custom explanation"></textarea>
                    <input type="hidden" name="cancellation_reason" :value="cancelReason === 'Other' ? customCancelReason : cancelReason">
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="cancelOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</button>
                        <button :disabled="cancelReason === 'Other' && !customCancelReason.trim()" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50">Confirm Cancellation</button>
                    </div>
                </form>
            </div>

            <div x-show="completeOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <div @click.outside="completeOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Complete seminar?</h3>
                    <p class="mt-2 text-sm text-gray-600">This marks the seminar completed and finalizes attendance records.</p>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="completeOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <form method="POST" action="{{ route('connector.seminars.complete', [$connector, $seminar]) }}">
                            @csrf
                            <button class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Complete</button>
                        </form>
                    </div>
                </div>
            </div>

            <div x-show="archiveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <div @click.outside="archiveOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Archive seminar?</h3>
                    <p class="mt-2 text-sm text-gray-600">Archived seminars become read-only. Active seminars cannot be archived.</p>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="archiveOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <form method="POST" action="{{ route('connector.seminars.archive', [$connector, $seminar]) }}">
                            @csrf
                            <button class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Archive</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5" x-data="{
                open: false,
                search: '',
                results: [],
                selected: [],
                toggle(result) {
                    this.selected = this.selected.some(item => item.id === result.id)
                        ? this.selected.filter(item => item.id !== result.id)
                        : [...this.selected, result];
                },
                isSelected(id) { return this.selected.some(item => item.id === id); },
                async find() {
                    const response = await fetch('{{ route('connector.seminars.speakers.search', [$connector, $seminar]) }}?search=' + encodeURIComponent(this.search), { headers: { Accept: 'application/json' } });
                    this.results = await response.json();
                }
            }">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-bold text-gray-900">Speakers</h3>
                    <button type="button" @click="open = true; find()" class="inline-flex items-center gap-2 rounded-lg bg-purple-700 px-3 py-2 text-sm font-semibold text-white hover:bg-purple-800" title="Invite speaker" aria-label="Invite speaker">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                        Invite Speaker
                    </button>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->speakers as $speaker)
                        <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-100 p-3">
                            @php($speakerAvatar = $avatarUrlFor($speaker->user))
                            <div class="flex min-w-0 items-center gap-3">
                                @if($speakerAvatar)
                                    <img src="{{ $speakerAvatar }}" alt="" class="h-11 w-11 rounded-full object-cover">
                                @else
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-100 text-sm font-bold text-purple-700">{{ mb_substr($speaker->display_name, 0, 1) }}</span>
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-gray-900">{{ $speaker->display_name }}</div>
                                    <div class="truncate text-sm text-gray-500">{{ $speaker->user?->instructorProfile?->professional_background ?? $speaker->title ?? 'No organization listed' }}</div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ ucfirst($speaker->role ?? 'speaker') }} / {{ ucfirst($speaker->status ?? 'accepted') }}</div>
                                    @if($speaker->status === 'applied')
                                        <dl class="mt-2 space-y-1 text-xs text-gray-600">
                                            <div><dt class="font-semibold text-gray-500">Motivation</dt><dd>{{ $speaker->application_motivation }}</dd></div>
                                            <div><dt class="font-semibold text-gray-500">Expertise</dt><dd>{{ $speaker->application_expertise }}</dd></div>
                                            <div><dt class="font-semibold text-gray-500">Experience</dt><dd>{{ $speaker->application_experience }}</dd></div>
                                            @if($speaker->application_supporting_info)
                                                <div><dt class="font-semibold text-gray-500">Supporting info</dt><dd>{{ $speaker->application_supporting_info }}</dd></div>
                                            @endif
                                        </dl>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col gap-2">
                                @if($speaker->status === 'applied')
                                    <form method="POST" action="{{ route('connector.seminars.speakers.approve', [$connector, $seminar, $speaker]) }}">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('connector.seminars.speakers.reject', [$connector, $seminar, $speaker]) }}" x-data="{ open: false }">
                                        @csrf
                                        <button type="button" @click="open = !open" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Reject</button>
                                        <div x-show="open" x-cloak class="mt-2 w-56 space-y-2">
                                            <textarea name="review_note" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Optional note"></textarea>
                                            <button class="rounded-lg bg-rose-700 px-3 py-2 text-sm font-semibold text-white">Confirm</button>
                                        </div>
                                    </form>
                                @elseif(in_array($speaker->status, ['pending', 'accepted'], true))
                                    <form method="POST" action="{{ route('connector.seminars.speakers.destroy', [$connector, $seminar, $speaker]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-semibold text-red-600 hover:text-red-800">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No speakers assigned.</p>
                    @endforelse
                </div>

                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                    <div @click.outside="open = false" class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">Invite speakers</h4>
                                <p class="mt-1 text-sm text-gray-500">Select one or more active approved instructors.</p>
                            </div>
                            <button type="button" @click="open = false" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Close</button>
                        </div>
                        <input x-model="search" @input.debounce.300ms="find()" placeholder="Search instructors" class="mt-4 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <div class="mt-4 max-h-72 space-y-2 overflow-y-auto">
                            <template x-for="result in results" :key="result.id">
                                <button type="button" @click="toggle(result)" class="flex w-full items-center gap-3 rounded-lg border border-gray-100 p-3 text-left hover:bg-purple-50" :class="{ 'border-purple-300 bg-purple-50': isSelected(result.id) }">
                                    <template x-if="result.avatar"><img :src="'/storage/' + result.avatar.replace(/^\/+/, '')" alt="" class="h-10 w-10 rounded-full object-cover"></template>
                                    <template x-if="!result.avatar"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700" x-text="result.name.substring(0, 1)"></span></template>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-gray-900" x-text="result.name"></span>
                                        <span class="block truncate text-sm text-gray-500" x-text="result.organization || result.email"></span>
                                    </span>
                                </button>
                            </template>
                            <p x-show="results.length === 0" class="text-sm text-gray-500">No eligible instructors found.</p>
                        </div>
                        <form method="POST" action="{{ route('connector.seminars.speakers.store', [$connector, $seminar]) }}" class="mt-5 space-y-3">
                            @csrf
                            <template x-for="result in selected" :key="result.id"><input type="hidden" name="user_ids[]" :value="result.id"></template>
                            <textarea name="invitation_message" rows="3" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Optional invitation message"></textarea>
                            <div class="flex justify-end gap-3">
                                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                                <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800 disabled:opacity-50" :disabled="selected.length === 0">Send invitations</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5" x-data="registrantManager(@js($registrantRows), {{ $seminar->registration_approval_mode === 'manual' ? 'true' : 'false' }})">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-gray-900">Recent Registrants</h3>
                        <p class="mt-1 text-xs text-gray-500">Latest 3 requests only.</p>
                    </div>
                    <a href="{{ route('connector.seminars.registrants.index', [$connector, $seminar]) }}" class="rounded-lg bg-purple-700 px-3 py-2 text-sm font-semibold text-white hover:bg-purple-800">Manage Registrants</a>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <select x-model="tab" @change="page = 1" class="rounded-lg border-gray-300 text-sm">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <input x-model.debounce.150ms="search" @input="page = 1" placeholder="Search registrants" class="ml-auto min-w-48 rounded-lg border-gray-300 text-sm">
                </div>
                <p x-show="!manualMode" class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">Auto-approved seminar: approval queue is bypassed.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-3 py-2">Registrant</th>
                                <th class="px-3 py-2">Registered</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="registrant in paginated" :key="registrant.id">
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-3">
                                            <template x-if="registrant.avatar"><img :src="registrant.avatar" alt="" class="h-10 w-10 rounded-full object-cover"></template>
                                            <template x-if="!registrant.avatar"><span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-100 text-sm font-bold text-brand-700" x-text="registrant.initial"></span></template>
                                            <div>
                                                <div class="font-semibold text-gray-900" x-text="registrant.name"></div>
                                                <div class="text-xs capitalize text-gray-500" x-text="registrant.participant_type"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600" x-text="registrant.registered_at"></td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold capitalize" :class="statusClass(registrant.status)" x-text="registrant.status === 'registered' ? 'approved' : registrant.status"></span></td>
                                    <td class="px-3 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="open(registrant, 'view')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50" title="View"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S3.732 16.057 2.458 12Z"/></svg></button>
                                            <button x-show="manualMode && registrant.status === 'pending'" type="button" @click="open(registrant, 'approve')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Approve"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg></button>
                                            <button x-show="manualMode && registrant.status === 'pending'" type="button" @click="open(registrant, 'reject')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Reject"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                                            <button x-show="['registered', 'rejected'].includes(registrant.status)" type="button" @click="open(registrant, 'delete')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50" title="Delete record"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79 18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/></svg></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filtered.length === 0"><td colspan="4" class="px-3 py-8 text-center text-gray-500">No registrants found.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                    <div @click.outside="modalOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <template x-if="selected && mode === 'view'">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900" x-text="selected.name"></h3>
                                <p class="mt-1 text-sm text-gray-500" x-text="selected.email"></p>
                                <dl class="mt-4 space-y-2 text-sm">
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Registered</dt><dd class="font-semibold" x-text="selected.registered_at_full"></dd></div>
                                    <template x-if="selected.participant_type === 'learner'">
                                        <div class="space-y-2">
                                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Age</dt><dd class="font-semibold" x-text="selected.age"></dd></div>
                                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Age category</dt><dd class="font-semibold capitalize" x-text="selected.age_category"></dd></div>
                                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Learner type</dt><dd class="font-semibold" x-text="selected.learner_type"></dd></div>
                                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Eligibility</dt><dd class="text-right font-semibold" x-text="selected.eligibility_summary"></dd></div>
                                        </div>
                                    </template>
                                    <template x-if="selected.participant_type === 'instructor'">
                                        <div class="space-y-2">
                                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Organization</dt><dd class="font-semibold" x-text="selected.affiliation"></dd></div>
                                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Instructor status</dt><dd class="font-semibold capitalize" x-text="selected.instructor_status"></dd></div>
                                        </div>
                                    </template>
                                </dl>
                            </div>
                        </template>
                        <template x-if="selected && mode === 'approve'">
                            <form method="POST" :action="selected.approve_url">
                                @csrf
                                <h3 class="text-lg font-bold text-gray-900">Approve registrant?</h3>
                                <p class="mt-2 text-sm text-gray-600">This confirms <span class="font-semibold" x-text="selected.name"></span> and updates registration count.</p>
                                <div class="mt-5 flex justify-end gap-3"><button type="button" @click="modalOpen = false" class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button><button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Approve</button></div>
                            </form>
                        </template>
                        <template x-if="selected && mode === 'reject'">
                            <form method="POST" :action="selected.reject_url">
                                @csrf
                                <h3 class="text-lg font-bold text-gray-900">Reject registrant?</h3>
                                <select x-model="rejectReason" class="mt-4 w-full rounded-lg border-gray-300 text-sm"><option>Age requirement not met</option><option>Other</option></select>
                                <textarea x-show="rejectReason === 'Other'" x-model="customRejectReason" class="mt-3 w-full rounded-lg border-gray-300 text-sm" rows="3" placeholder="Custom explanation"></textarea>
                                <input type="hidden" name="rejection_reason" :value="rejectReason === 'Other' ? customRejectReason : rejectReason">
                                <div class="mt-5 flex justify-end gap-3"><button type="button" @click="modalOpen = false" class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button><button :disabled="rejectReason === 'Other' && !customRejectReason.trim()" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Reject</button></div>
                            </form>
                        </template>
                        <template x-if="selected && mode === 'delete'">
                            <form method="POST" :action="selected.delete_url">
                                @csrf
                                @method('DELETE')
                                <h3 class="text-lg font-bold text-gray-900">Delete registration record?</h3>
                                <p class="mt-2 text-sm text-gray-600">This removes historical record for <span class="font-semibold" x-text="selected.name"></span>.</p>
                                <div class="mt-5 flex justify-end gap-3"><button type="button" @click="modalOpen = false" class="rounded-lg border px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button><button class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Delete</button></div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900">Comments</h3>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->comments as $comment)
                        <div class="rounded-lg border border-gray-100 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $comment->user?->name }}</div>
                                    <p class="mt-1 text-sm text-gray-700">{{ $comment->body }}</p>
                                    <div class="mt-1 text-xs text-gray-500">{{ $comment->status }}</div>
                                </div>
                            </div>
                            @if($comment->status !== 'hidden')
                                <form method="POST" action="{{ route('connector.seminars.comments.hide', [$connector, $seminar, $comment]) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    <input name="reason" placeholder="Reason" required class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hide</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No comments yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900">Q&A</h3>
                <div class="mt-4 space-y-3">
                    @forelse($seminar->questions as $question)
                        <div class="rounded-lg border border-gray-100 p-3">
                            <div class="text-sm font-semibold text-gray-900">{{ $question->user?->name }}</div>
                            <p class="mt-1 text-sm text-gray-700">{{ $question->question }}</p>
                            @if($question->answer)
                                <p class="mt-2 rounded-lg bg-green-50 p-2 text-sm text-green-800">{{ $question->answer }}</p>
                            @endif
                            <div class="mt-1 text-xs text-gray-500">{{ $question->status }}</div>
                            @if($question->status !== 'hidden')
                                <form method="POST" action="{{ route('connector.seminars.questions.answer', [$connector, $seminar, $question]) }}" class="mt-3 flex gap-2">
                                    @csrf
                                    <input name="answer" placeholder="Answer" required class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <button class="rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800">Answer</button>
                                </form>
                                <form method="POST" action="{{ route('connector.seminars.questions.hide', [$connector, $seminar, $question]) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <input name="reason" placeholder="Reason" required class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hide</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No questions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <script>
        function registrantManager(rows, manualMode) {
            return {
                rows,
                manualMode,
                tabs: ['pending', 'approved', 'rejected'],
                tab: manualMode ? 'pending' : 'approved',
                search: '',
                page: 1,
                perPage: 3,
                modalOpen: false,
                selected: null,
                mode: 'view',
                rejectReason: 'Age requirement not met',
                customRejectReason: '',
                get filtered() {
                    const wanted = this.tab === 'approved' ? 'registered' : this.tab;
                    const search = this.search.trim().toLowerCase();

                    return this.rows.filter((row) => row.status === wanted)
                        .filter((row) => !search || row.search_blob.includes(search));
                },
                get totalPages() {
                    return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
                },
                get safePage() {
                    return Math.min(this.page, this.totalPages);
                },
                get paginated() {
                    return this.filtered.slice((this.safePage - 1) * this.perPage, this.safePage * this.perPage);
                },
                open(registrant, mode) {
                    this.selected = registrant;
                    this.mode = mode;
                    this.rejectReason = 'Age requirement not met';
                    this.customRejectReason = '';
                    this.modalOpen = true;
                },
                statusClass(status) {
                    return {
                        pending: 'bg-amber-100 text-amber-800',
                        registered: 'bg-emerald-100 text-emerald-800',
                        rejected: 'bg-rose-100 text-rose-800',
                    }[status] || 'bg-gray-100 text-gray-700';
                },
            };
        }
    </script>
@endsection
