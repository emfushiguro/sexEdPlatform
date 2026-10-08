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
	<div class="p-6 text-white rounded-2xl"
		 style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
		<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<h1 class="text-2xl font-bold">Guardian Link Invitation</h1>
				<p class="mt-1 text-sm text-white/80">
					@if($isChildViewer)
						Review and decide on this guardian invitation request.
					@else
						Track the learner's response to your invitation.
					@endif
				</p>
			</div>
			<a href="{{ $isParentViewer ? route('parent.invitations.index') : route('learner.dashboard') }}"
			   class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-xl bg-white/20 hover:bg-white/30">
				{{ $isParentViewer ? 'Back to Invitations' : 'Back to Dashboard' }}
			</a>
		</div>
	</div>

	@if(session('success'))
		<div class="px-4 py-3 text-sm text-green-800 border border-green-200 bg-green-50 rounded-xl">
			{{ session('success') }}
		</div>
	@endif

	@if($errors->any())
		<div class="px-4 py-3 text-sm text-red-800 border border-red-200 bg-red-50 rounded-xl">
			{{ $errors->first() }}
		</div>
	@endif

	<div class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl">
		<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<h2 class="text-lg font-semibold text-gray-900">Invitation Details</h2>
				<p class="mt-1 text-sm text-gray-500">Sent {{ $invitation->created_at?->format('M d, Y h:i A') }}</p>
			</div>
			<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
				{{ ucfirst($statusValue) }}
			</span>
		</div>

			<div class="px-4 py-3 mt-4 border border-indigo-100 rounded-xl bg-indigo-50">
			<p class="text-xs font-semibold tracking-wide text-indigo-700 uppercase">Claimed relationship</p>
			<p class="mt-1 text-sm font-semibold text-indigo-900">{{ $invitation->relationshipLabel() }}</p>
			<p class="mt-2 text-sm leading-6 text-indigo-900">This information is shown so you can understand who requested the connection. Accepting sends the relationship request for administrative review and does not grant guardian access immediately.</p>
		</div>


		<div class="grid grid-cols-1 gap-3 mt-4 text-sm sm:grid-cols-2">
			<div class="px-3 py-2 border border-gray-100 rounded-xl bg-gray-50">
				<div class="flex items-center gap-3">
					@if($guardianAvatarUrl)
						<img src="{{ $guardianAvatarUrl }}" alt="Guardian avatar" class="object-cover w-10 h-10 border border-gray-200 rounded-full">
					@else
						<span class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold text-purple-700 bg-purple-100 rounded-full" aria-hidden="true">{{ strtoupper(substr($guardianSummary['name'], 0, 1)) }}</span>
					@endif
					<div class="min-w-0">
						<p class="text-xs text-gray-500">Guardian</p>
						<p class="mt-1 font-semibold text-gray-900">{{ $guardianSummary['name'] }}</p>
					</div>
				</div>
			</div>
			<div class="px-3 py-2 border border-gray-100 rounded-xl bg-gray-50">
				<div class="flex items-center gap-3">
					@if($learnerAvatarUrl)
						<img src="{{ $learnerAvatarUrl }}" alt="Learner avatar" class="object-cover w-10 h-10 border border-gray-200 rounded-full">
					@else
						<span class="inline-flex items-center justify-center w-10 h-10 text-sm font-bold text-indigo-700 bg-indigo-100 rounded-full" aria-hidden="true">{{ strtoupper(substr($learnerSummary['name'], 0, 1)) }}</span>
					@endif
					<div class="min-w-0">
						<p class="text-xs text-gray-500">Learner</p>
						<p class="mt-1 font-semibold text-gray-900">{{ $learnerSummary['name'] }}</p>
					</div>
				</div>
			</div>
		</div>

		@if($guardianProfile)
			<article x-data="{ showDetails: true }" class="relative mt-5 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl">
				<div class="flex items-center gap-4 p-5 pr-14">
					@if($guardianProfileAvatarUrl)
						<img src="{{ $guardianProfileAvatarUrl }}"
							 alt="{{ $guardianProfile['name'] }} avatar"
							 class="flex-shrink-0 object-cover border border-gray-200 rounded-full shadow h-14 w-14">
					@else
						<div class="flex items-center justify-center flex-shrink-0 text-xl font-bold text-white rounded-full shadow h-14 w-14"
							 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
							{{ strtoupper(substr($guardianProfile['name'], 0, 1)) }}
						</div>
					@endif

					<div class="flex-1 min-w-0">
						<div class="flex items-center gap-2">
							<h2 class="font-semibold text-gray-900 truncate">{{ $guardianProfile['name'] }}</h2>
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

				<div class="grid grid-cols-3 py-3 text-center border-t border-b border-gray-100 divide-x divide-gray-100 bg-gray-50/60">
					<div class="px-2">
						<p class="text-lg font-bold text-purple-700">{{ !is_null($guardianProfile['age']) ? $guardianProfile['age'] : '-' }}</p>
						<p class="text-xs text-gray-500">Age</p>
					</div>
					<div class="px-2">
						<p class="text-sm font-semibold text-gray-900">{{ $guardianProfile['gender'] ?: '-' }}</p>
						<p class="text-xs text-gray-500">Gender</p>
					</div>
					<div class="min-w-0 px-2">
						<p class="text-sm font-semibold text-gray-900 truncate">{{ $guardianProfile['location_short'] ?: 'N/A' }}</p>
						<p class="text-xs text-gray-500">Location</p>
					</div>
				</div>

				<div id="guardian-profile-details" x-cloak x-show="showDetails" x-transition.opacity.duration.200ms class="px-5 py-4 space-y-2 text-sm border-b border-gray-100">
					<div class="flex items-center justify-between gap-2">
						<span class="text-gray-500">Email</span>
						<span class="font-medium text-gray-900 truncate">{{ $guardianProfile['email'] ?: 'N/A' }}</span>
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
						<span class="font-medium text-right text-gray-900">{{ $guardianProfile['location'] ?: 'N/A' }}</span>
					</div>
					@if($guardianProfile['about'])
						<p class="pt-1 text-xs text-gray-500">{{ $guardianProfile['about'] }}</p>
					@endif
				</div>

				<div class="px-5 py-3 bg-gray-50/60">
					<button type="button"
							@click="showDetails = !showDetails"
							:aria-expanded="showDetails.toString()"
							aria-controls="guardian-profile-details"
							class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:opacity-90 active:scale-[0.99]"
							style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
						<svg class="w-4 h-4 transition-transform duration-200" :class="showDetails ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
						</svg>
						<span x-text="showDetails ? 'Hide Details' : 'View Details'"></span>
					</button>
				</div>
			</article>
		@endif

		@if($invitation->message)
			<div class="px-4 py-3 mt-4 border border-purple-100 rounded-xl bg-purple-50">
				<p class="text-xs font-semibold tracking-wide text-purple-700 uppercase">Guardian Message</p>
				<p class="mt-1 text-sm text-purple-900">{{ $invitation->message }}</p>
			</div>
		@endif

		@if($invitation->conversation)
			<div class="mt-4">
				<a href="{{ route('chat.conversation.open', $invitation->conversation) }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700">
					Open Conversation
				</a>
			</div>
		@elseif($isChildViewer && $statusValue === 'pending' && ! $invitation->isExpired())
			<div class="mt-4">
				<form method="POST" action="{{ route('parent.invitations.conversation', $invitation) }}">
					@csrf
					<button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700">
						Message Guardian
					</button>
				</form>
			</div>
		@endif

		@if($invitation->decision_note)
			<div class="px-4 py-3 mt-4 border border-gray-200 rounded-xl bg-gray-50">
				<p class="text-xs font-semibold tracking-wide text-gray-600 uppercase">Response Note</p>
				<p class="mt-1 text-sm text-gray-800">{{ $invitation->decision_note }}</p>
			</div>
		@endif
	</div>

	@if($isChildViewer && $statusValue === 'pending')
		<div class="p-5 space-y-4 bg-white border border-gray-200 shadow-sm rounded-2xl">
			<h3 class="text-base font-semibold text-gray-900">Respond to Invitation</h3>
			<p class="text-sm text-gray-500">Accepting this request confirms the link. Any required relationship verification remains subject to admin review.</p>

			<form method="POST" action="{{ route('parent.invitations.respond', $invitation) }}" class="space-y-3">
				@csrf
				<input type="hidden" name="decision" value="accept">
				<button type="submit"
						class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-xl hover:opacity-90"
						style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
					Accept Invitation
				</button>
			</form>

			<form method="POST" action="{{ route('parent.invitations.respond', $invitation) }}" class="space-y-3">
				@csrf
				<input type="hidden" name="decision" value="reject">
				<div>
					<label for="note" class="block mb-1 text-xs font-medium text-gray-600">Optional rejection note</label>
					<input id="note"
						   type="text"
						   name="note"
						   maxlength="500"
						   value="{{ old('note') }}"
						   placeholder="Share context for the guardian"
						   class="w-full px-3 py-2 text-sm text-gray-900 border border-gray-200 rounded-xl bg-gray-50 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
				</div>
				<button type="submit"
						class="inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50">
					Reject Invitation
				</button>
			</form>
		</div>
	@endif

	@if($isParentViewer && $statusValue === 'pending')
		<div class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl">
			<h3 class="text-base font-semibold text-gray-900">Guardian Action</h3>
			<p class="mt-1 text-sm text-gray-500">Cancel this invitation if it was sent in error.</p>

			<form method="POST" action="{{ route('parent.invitations.cancel', $invitation) }}" class="mt-4">
				@csrf
				<button type="submit"
						class="inline-flex items-center px-4 py-2 text-sm font-semibold border rounded-xl border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
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
