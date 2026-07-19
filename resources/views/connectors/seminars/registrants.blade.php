@extends('layouts.connector-app')

@section('title', 'Registrants')
@section('page-title', 'Registrant Management')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $seminar->title }}</h2>
            <p class="mt-1 text-sm text-gray-600">Search, filter, approve, reject, and inspect registrants.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
            <a href="{{ route('connector.seminars.registrants.export', [$connector, $seminar]) }}" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Export CSV</a>
        </div>
    </div>

    <form method="GET" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-3">
        <input name="search" value="{{ $search }}" placeholder="Search name or email" class="rounded-lg border-gray-300 text-sm">
        <select name="status" class="rounded-lg border-gray-300 text-sm">
            <option value="">All statuses</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="registered" @selected($status === 'registered')>Approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Registrant</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Registered</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($registrants as $registrant)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $registrant->user?->name ?? 'Unknown user' }}</div>
                            <div class="text-xs text-gray-500">{{ $registrant->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $registrant->participant_type }}</td>
                        <td class="px-4 py-3">{{ $registrant->registered_at?->format('M d, Y g:i A') ?? 'Unknown' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold capitalize {{ ['pending' => 'bg-amber-100 text-amber-800', 'registered' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-800'][$registrant->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $registrant->status === 'registered' ? 'approved' : $registrant->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @if($registrant->status === 'pending')
                                    <form method="POST" action="{{ route('connector.seminars.registrants.approve', [$connector, $seminar, $registrant]) }}">
                                        @csrf
                                        <button class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Approve" aria-label="Approve">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('connector.seminars.registrants.reject', [$connector, $seminar, $registrant]) }}">
                                        @csrf
                                        <input type="hidden" name="rejection_reason" value="Not eligible">
                                        <button class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Reject" aria-label="Reject">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                @endif
                                @if(in_array($registrant->status, ['registered', 'rejected'], true))
                                    <form method="POST" action="{{ route('connector.seminars.registrants.destroy', [$connector, $seminar, $registrant]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50" title="Delete record" aria-label="Delete record">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M19.228 5.79 18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No registrants found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $registrants->links() }}
</div>
@endsection
