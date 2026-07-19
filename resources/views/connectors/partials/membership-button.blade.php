@if($state === 'member')
    <button type="button" disabled class="rounded-lg bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">Member</button>
@elseif($state === 'pending')
    <button type="button" disabled class="rounded-lg bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">Pending Approval</button>
@elseif($state === 'invited')
    <a href="{{ route('connectors.invitations.index') }}" class="rounded-lg bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700">Invitation Received</a>
@else
    <form method="POST" action="{{ route('connectors.membership-requests.store', $connector) }}">
        @csrf
        <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Request to Join</button>
    </form>
@endif
