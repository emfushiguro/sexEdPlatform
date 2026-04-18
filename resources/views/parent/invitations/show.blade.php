@extends('layouts.learner-app')

@section('title', 'Parent Invitation')

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
	$childBirthdate = $invitation->child?->birthdate ?? $invitation->child?->learnerProfile?->birthdate;
	$childAge = $childBirthdate
		? \Carbon\Carbon::parse($childBirthdate)->age
		: null;
@endphp

<div class="max-w-4xl mx-auto space-y-6">
	<div class="rounded-2xl p-6 text-white"
		 style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
		<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<h1 class="text-2xl font-bold">Parent Link Invitation</h1>
				<p class="text-white/80 text-sm mt-1">
					@if($isChildViewer)
						Review and decide on this parent invitation request.
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
				<p class="text-xs text-gray-500">Parent</p>
				<p class="font-semibold text-gray-900 mt-1">{{ $invitation->inviterParent?->name ?? 'Parent' }}</p>
				<p class="text-xs text-gray-500 mt-1">{{ $invitation->inviterParent?->email }}</p>
			</div>
			<div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2">
				<p class="text-xs text-gray-500">Learner</p>
				<p class="font-semibold text-gray-900 mt-1">{{ $invitation->child?->name ?? 'Learner' }}</p>
				<p class="text-xs text-gray-500 mt-1">
					{{ $invitation->child?->email }}
					@if($invitation->child?->learnerProfile?->username)
						· {{ $invitation->child->learnerProfile->username }}
					@endif
				</p>
				@if(!is_null($childAge))
					<p class="text-xs text-gray-500 mt-1">{{ $childAge }} years old</p>
				@endif
			</div>
		</div>

		@if($invitation->message)
			<div class="mt-4 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3">
				<p class="text-xs font-semibold uppercase tracking-wide text-purple-700">Parent Message</p>
				<p class="mt-1 text-sm text-purple-900">{{ $invitation->message }}</p>
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
			<p class="text-sm text-gray-500">Accepting this request creates an approved parent-child link for guidance and monitoring.</p>

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
						   placeholder="Share context for the parent"
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
			<h3 class="text-base font-semibold text-gray-900">Parent Action</h3>
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
