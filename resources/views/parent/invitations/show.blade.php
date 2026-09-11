@extends('layouts.learner-app')

@section('title', 'Guardian Invitation')

@section('content')
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
	$guardianAvatarPath = $guardianSummary['avatar_path'] ?? null;
	$guardianAvatarUrl = $guardianAvatarPath
		? asset('storage/' . ltrim((string) $guardianAvatarPath, '/'))
		: null;
	$guardianProfileAvatarUrl = !empty($guardianProfile['avatar_path'] ?? null)
		? asset('storage/' . ltrim((string) $guardianProfile['avatar_path'], '/'))
		: null;
	$learnerAvatarPath = $learnerSummary['avatar_path'] ?? null;
	$learnerAvatarUrl = $learnerAvatarPath
		? asset('storage/' . ltrim((string) $learnerAvatarPath, '/'))
		: null;
@endphp

<div class="max-w-4xl mx-auto space-y-6" x-data="guardianInvitationDisclosure()" @keydown.escape.window="close()">
	<div class="rounded-2xl p-6 text-white"
		 style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
		<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<h1 class="text-2xl font-bold">Guardian Link Invitation</h1>
				<p class="text-white/80 text-sm mt-1">
					@if($isChildViewer)
						Review and decide on this guardian invitation request.
					@else
						Track the learner's response to your invitation.
					@endif
				</p>
			</div>
			<a href="{{ $isParentViewer ? route('parent.invitations.index') : route('learner.dashboard') }}"
			   class="inline-flex items-center rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/30">
				{{ $isParentViewer ? 'Back to Invitations' : 'Back to Dashboard' }}
			</a>
		</div>
	</div>

	@if(session('success'))
		<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
			{{ session('success') }}
		</div>
	@endif

	@if($errors->any())
		<div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
			{{ $errors->first() }}
		</div>
	@endif

	<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
		<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<h2 class="text-lg font-semibold text-gray-900">Invitation Details</h2>
				<p class="text-sm text-gray-500 mt-1">Sent {{ $invitation->created_at?->format('M d, Y h:i A') }}</p>
			</div>
			<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
				{{ ucfirst($statusValue) }}
			</span>
		</div>

		<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
			<div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2">
				<div class="flex items-center gap-3">
					@if($guardianAvatarUrl)
						<img src="{{ $guardianAvatarUrl }}" alt="Guardian avatar" class="h-10 w-10 rounded-full border border-gray-200 object-cover">
					@else
						<span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700" aria-hidden="true">{{ strtoupper(substr($guardianSummary['name'], 0, 1)) }}</span>
					@endif
					<div class="min-w-0">
						<p class="text-xs text-gray-500">Guardian</p>
						<p class="font-semibold text-gray-900 mt-1">{{ $guardianSummary['name'] }}</p>
					</div>
				</div>
				@if($guardianSummary['identity_verified'])
					<p class="mt-1 text-xs font-semibold text-emerald-700">Guardian identity administratively verified</p>
				@endif
			</div>
			<div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2">
				<div class="flex items-center gap-3">
					@if($learnerAvatarUrl)
						<img src="{{ $learnerAvatarUrl }}" alt="Learner avatar" class="h-10 w-10 rounded-full border border-gray-200 object-cover">
					@else
						<span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700" aria-hidden="true">{{ strtoupper(substr($learnerSummary['name'], 0, 1)) }}</span>
					@endif
					<div class="min-w-0">
						<p class="text-xs text-gray-500">Learner</p>
						<p class="font-semibold text-gray-900 mt-1">{{ $learnerSummary['name'] }}</p>
					</div>
				</div>
				<p class="text-xs text-gray-500 mt-1">
					@if($learnerSummary['username'])
						· {{ $invitation->child->learnerProfile->username }}
					@endif
				</p>
			</div>
		</div>

		@if($guardianProfile)
			<article x-data="{ showDetails: true }" class="relative mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
				<div class="flex items-center gap-4 p-5 pr-14">
					@if($guardianProfileAvatarUrl)
						<img src="{{ $guardianProfileAvatarUrl }}"
							 alt="{{ $guardianProfile['name'] }} avatar"
							 class="h-14 w-14 flex-shrink-0 rounded-full border border-gray-200 object-cover shadow">
					@else
						<div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full text-xl font-bold text-white shadow"
							 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
							{{ strtoupper(substr($guardianProfile['name'], 0, 1)) }}
						</div>
					@endif

					<div class="min-w-0 flex-1">
						<div class="flex items-center gap-2">
							<h2 class="truncate font-semibold text-gray-900">{{ $guardianProfile['name'] }}</h2>
							<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ ucfirst($statusValue) }}</span>
						</div>
						<p class="mt-0.5 text-xs text-gray-500">
							@if(!is_null($guardianProfile['age'])){{ $guardianProfile['age'] }} years old.@endif
							Sent {{ $invitation->created_at?->diffForHumans() }}
						</p>
						@if($guardianProfile['username'])
							<p class="mt-0.5 text-xs text-purple-600">{{ '@'.$guardianProfile['username'] }}</p>
						@endif
					</div>
				</div>

				<div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-t border-gray-100 bg-gray-50/60 py-3 text-center">
					<div class="px-2">
						<p class="text-lg font-bold text-purple-700">{{ !is_null($guardianProfile['age']) ? $guardianProfile['age'] : '-' }}</p>
						<p class="text-xs text-gray-500">Age</p>
					</div>
					<div class="px-2">
						<p class="text-sm font-semibold text-gray-900">{{ $guardianProfile['gender'] ?: '-' }}</p>
						<p class="text-xs text-gray-500">Gender</p>
					</div>
					<div class="min-w-0 px-2">
						<p class="truncate text-sm font-semibold text-gray-900">{{ $guardianProfile['location_short'] ?: 'N/A' }}</p>
						<p class="text-xs text-gray-500">Location</p>
					</div>
				</div>

				<div id="guardian-profile-details" x-cloak x-show="showDetails" x-transition.opacity.duration.200ms class="space-y-2 border-b border-gray-100 px-5 py-4 text-sm">
					<div class="flex items-center justify-between gap-2">
						<span class="text-gray-500">Email</span>
						<span class="truncate font-medium text-gray-900">{{ $guardianProfile['email'] ?: 'N/A' }}</span>
					</div>
					<div class="flex items-center justify-between gap-2">
						<span class="text-gray-500">Username</span>
						<span class="font-medium text-gray-900">{{ $guardianProfile['username'] ? '@'.$guardianProfile['username'] : 'N/A' }}</span>
					</div>
					<div class="flex items-center justify-between gap-2">
						<span class="text-gray-500">Birthdate</span>
						<span class="font-medium text-gray-900">{{ $guardianProfile['birthdate'] ? \Carbon\Carbon::parse($guardianProfile['birthdate'])->format('M d, Y') : 'N/A' }}</span>
					</div>
					<div class="flex items-center justify-between gap-2">
						<span class="text-gray-500">Gender</span>
						<span class="font-medium text-gray-900">{{ $guardianProfile['gender'] ?: 'N/A' }}</span>
					</div>
					<div class="flex items-center justify-between gap-2">
						<span class="text-gray-500">Location</span>
						<span class="text-right font-medium text-gray-900">{{ $guardianProfile['location'] ?: 'N/A' }}</span>
					</div>
					@if($guardianProfile['about'])
						<p class="pt-1 text-xs text-gray-500">{{ $guardianProfile['about'] }}</p>
					@endif
				</div>

				<div class="bg-gray-50/60 px-5 py-3">
					<button type="button"
							@click="showDetails = !showDetails"
							:aria-expanded="showDetails.toString()"
							aria-controls="guardian-profile-details"
							class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:opacity-90 active:scale-[0.99]"
							style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
						<svg class="h-4 w-4 transition-transform duration-200" :class="showDetails ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
						</svg>
						<span x-text="showDetails ? 'Hide Details' : 'View Details'"></span>
					</button>
				</div>
			</article>
		@endif

		@if($invitation->message)
			<div class="mt-4 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3">
				<p class="text-xs font-semibold uppercase tracking-wide text-purple-700">Guardian Message</p>
				<p class="mt-1 text-sm text-purple-900">{{ $invitation->message }}</p>
			</div>
		@endif

		<div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
			<p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Claimed relationship</p>
			<p class="mt-1 text-sm font-semibold text-indigo-900">{{ $invitation->relationshipLabel() }}</p>
			<p class="mt-2 text-sm leading-6 text-indigo-900">This information is shown so you can understand who requested the connection. Accepting sends the relationship evidence for administrative review and does not grant guardian access immediately.</p>
		</div>

		<div class="mt-4" x-data="guardianInvitationDisclosure()" @keydown.escape.window="close()">
			<button
				type="button"
				class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
				:aria-expanded="open.toString()"
				aria-controls="guardian-information-panel"
				@click="toggle($event)"
			>
				View Guardian Information
			</button>
			<div id="guardian-information-panel" x-cloak x-show="open" x-transition class="mt-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm" tabindex="-1">
				<p class="font-semibold text-gray-900">{{ $guardianSummary['name'] }}</p>
				@if($guardianSummary['identity_verified'])
					<p class="mt-1 text-xs text-emerald-700">Identity administratively verified</p>
				@endif
				@if($guardianSummary['member_since'])
					<p class="mt-1 text-xs text-gray-500">Member since {{ $guardianSummary['member_since'] }}</p>
				@endif
				<p class="mt-2 text-xs leading-5 text-gray-600">Only limited identity context is shown here. Contact details and evidence files remain private.</p>
			</div>
		</div>

		@if($invitation->conversation)
			<div class="mt-4">
				<a href="{{ route('chat.conversation.open', $invitation->conversation) }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
					Open Conversation
				</a>
			</div>
		@elseif($isChildViewer && $statusValue === 'pending' && ! $invitation->isExpired())
			<div class="mt-4">
				<form method="POST" action="{{ route('parent.invitations.conversation', $invitation) }}">
					@csrf
					<button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
						Message Guardian
					</button>
				</form>
			</div>
		@endif

		@if($invitation->decision_note)
			<div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
				<p class="text-xs font-semibold uppercase tracking-wide text-gray-600">Response Note</p>
				<p class="mt-1 text-sm text-gray-800">{{ $invitation->decision_note }}</p>
			</div>
		@endif
	</div>

	@if($isChildViewer && $statusValue === 'pending')
		<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-4">
			<h3 class="text-base font-semibold text-gray-900">Respond to Invitation</h3>
			<p class="text-sm text-gray-500">Accepting this request confirms the link. Any required relationship verification remains subject to admin review.</p>

			<form method="POST" action="{{ route('parent.invitations.respond', $invitation) }}" class="space-y-3">
				@csrf
				<input type="hidden" name="decision" value="accept">
				<button type="submit"
						class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
						style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
					Accept Invitation
				</button>
			</form>

			<form method="POST" action="{{ route('parent.invitations.respond', $invitation) }}" class="space-y-3">
				@csrf
				<input type="hidden" name="decision" value="reject">
				<div>
					<label for="note" class="block text-xs font-medium text-gray-600 mb-1">Optional rejection note</label>
					<input id="note"
						   type="text"
						   name="note"
						   maxlength="500"
						   value="{{ old('note') }}"
						   placeholder="Share context for the guardian"
						   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
				</div>
				<button type="submit"
						class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
					Reject Invitation
				</button>
			</form>
		</div>
	@endif

	@if($isParentViewer && $statusValue === 'pending')
		<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
			<h3 class="text-base font-semibold text-gray-900">Guardian Action</h3>
			<p class="text-sm text-gray-500 mt-1">Cancel this invitation if it was sent in error.</p>

			<form method="POST" action="{{ route('parent.invitations.cancel', $invitation) }}" class="mt-4">
				@csrf
				<button type="submit"
						class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
					Cancel Invitation
				</button>
			</form>
		</div>
	@endif
</div>
@endsection

@push('scripts')
<script>
    window.guardianInvitationDisclosure = () => ({
        open: false,
        trigger: null,
        toggle(event) {
            this.trigger = event.currentTarget;
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => document.getElementById('guardian-information-panel')?.focus());
            }
        },
        close() {
            if (!this.open) return;
            this.open = false;
            this.$nextTick(() => this.trigger?.focus());
        },
    });
</script>
@endpush
