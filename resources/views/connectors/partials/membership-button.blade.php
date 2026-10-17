@if($state === 'member')
    <button type="button" disabled class="rounded-lg bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Member</button>
@elseif($state === 'pending')
    <button type="button" disabled class="rounded-lg bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Pending Approval</button>
@elseif($state === 'invited')
    <a href="{{ route('connectors.invitations.index') }}" class="rounded-lg bg-brand-100 px-4 py-2 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-200 focus-visible:ring-brand-600 dark:bg-brand-950 dark:text-brand-300 dark:hover:bg-brand-900 dark:ring-offset-gray-900">Invitation Received</a>
@else
    <form method="POST" action="{{ route('connectors.membership-requests.store', $connector) }}">
        @csrf
        <button class="rounded-lg bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900 px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 focus-visible:ring-brand-600 dark:ring-offset-gray-900">Request to Join</button>
    </form>
@endif
