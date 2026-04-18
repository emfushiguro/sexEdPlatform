@extends('layouts.learner-app')

@section('title', 'Parent Invitations')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="rounded-2xl p-6 text-white"
         style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Parent Link Invitations</h1>
                <p class="text-white/80 text-sm mt-1">Invite an existing learner account to connect with your parent account.</p>
            </div>
            <a href="{{ route('parent.children.index') }}"
               class="inline-flex items-center rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/30">
                Back to My Children
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-semibold text-gray-900">Send New Invitation</h2>
        <p class="text-sm text-gray-500 mt-1">Enter the learner's username or email address. Eligible age range is 5-17.</p>

        <form method="POST" action="{{ route('parent.invitations.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">Learner Username or Email</label>
                <input id="identifier"
                       name="identifier"
                       type="text"
                       required
                       value="{{ old('identifier') }}"
                       placeholder="e.g. learnerusername or learner@email.com"
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">
                @error('identifier')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message (optional)</label>
                <textarea id="message"
                          name="message"
                          rows="3"
                          maxlength="500"
                          placeholder="Add a short context for the learner"
                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-100">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white hover:opacity-90"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                    Send Invitation
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-semibold text-gray-900">Outgoing Invitations</h2>

        @if($outgoingInvitations->isEmpty())
            <p class="text-sm text-gray-500 mt-3">No invitations sent yet.</p>
        @else
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
                                <p class="text-xs text-gray-400 mt-1">Sent {{ $invitation->created_at?->diffForHumans() }}</p>
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
        @endif
    </div>
</div>
@endsection
