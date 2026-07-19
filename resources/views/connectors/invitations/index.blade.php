@extends(auth()->user()?->isInstructor() ? 'layouts.instructor-app' : 'layouts.learner-app')

@section('title', 'Connector Invitations')
@section('content')
<div class="mx-auto max-w-5xl space-y-5 px-4 py-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-purple-700">Connectors</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Invitations</h1>
    </div>

    @if($invitations->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
            <h2 class="text-base font-semibold text-gray-900">No pending invitations</h2>
            <p class="mt-1 text-sm text-gray-500">Connector invitations appear here when an organization invites you.</p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach($invitations as $invitation)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ $invitation->connector?->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">Role: {{ $invitation->role?->name ?? 'Member' }}. Invited by {{ $invitation->inviter?->name ?? 'connector manager' }}.</p>
                        </div>
                        <div class="flex gap-2" x-data="{ acceptOpen: false, declineOpen: false }">
                            <form method="POST" action="{{ route('connector.invitations.accept', [$invitation->connector, $invitation]) }}" x-ref="acceptForm">
                                @csrf
                                <button type="button" @click="acceptOpen = true" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Accept</button>
                            </form>
                            <form method="POST" action="{{ route('connector.invitations.reject', [$invitation->connector, $invitation]) }}" x-ref="declineForm">
                                @csrf
                                <button type="button" @click="declineOpen = true" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Decline</button>
                            </form>
                            <div x-show="acceptOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                <div @click.outside="acceptOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                                    <h3 class="text-lg font-bold text-gray-900">Accept invitation?</h3>
                                    <p class="mt-2 text-sm text-gray-600">You will join {{ $invitation->connector?->name }} and get access allowed by assigned role.</p>
                                    <div class="mt-5 flex justify-end gap-3">
                                        <button type="button" @click="acceptOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                                        <button type="button" @click="$refs.acceptForm.submit()" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Accept</button>
                                    </div>
                                </div>
                            </div>
                            <div x-show="declineOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                                <div @click.outside="declineOpen = false" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                                    <h3 class="text-lg font-bold text-gray-900">Decline invitation?</h3>
                                    <p class="mt-2 text-sm text-gray-600">This invitation will be rejected. You can still request membership later if connector allows it.</p>
                                    <div class="mt-5 flex justify-end gap-3">
                                        <button type="button" @click="declineOpen = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button>
                                        <button type="button" @click="$refs.declineForm.submit()" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Decline</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $invitations->links() }}
    @endif
</div>
@endsection
