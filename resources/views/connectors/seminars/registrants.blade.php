@extends('layouts.connector-app')

@section('title', 'Registrants')
@section('page-title', 'Registrant Management')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="overflow-hidden rounded-[24px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.14),_transparent_34%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Educational Events</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Registrant Management</h2>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="shrink-0">@include('seminars._thumbnail', ['variant' => 'compact', 'loading' => 'eager'])</div>
                        <div class="min-w-0">
                            <h3 class="break-words text-sm font-semibold text-gray-900">{{ $seminar->title }}</h3>
                            <p class="mt-1 text-xs text-gray-500">Search, filter, and manage event registrations.</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg>
                        Back to event
                    </a>
                    <a href="{{ route('connector.seminars.registrants.export', [$connector, $seminar]) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m-4-4 4 4 4-4M5 16v4h14v-4"/></svg>
                        Export CSV
                    </a>
                </div>
            </div>
        </div>

    <form method="GET" action="{{ route('connector.seminars.registrants.index', [$connector, $seminar]) }}" class="grid gap-4 border-b border-gray-100 p-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="min-w-0">
            <label for="registrant-search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</label>
            <input type="search" id="registrant-search" name="search" value="{{ $search }}" placeholder="Search name or email" class="min-h-11 w-full rounded-xl border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500">
        </div>
        <div class="min-w-0">
        <label for="registrant-status" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
        <select id="registrant-status" name="status" class="min-h-11 w-full rounded-xl border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500">
            <option value="">All statuses</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="registered" @selected($status === 'registered')>Approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
            <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">Filter</button>
            @if($search !== '' || $status !== '')
                <a href="{{ route('connector.seminars.registrants.index', [$connector, $seminar]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear filters</a>
            @endif
        </div>
    </form>

    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-900">Registrants</h3>
        <span class="text-xs text-gray-500">{{ number_format($registrants->total()) }} {{ str('registrant')->plural($registrants->total()) }}{{ $search !== '' || $status !== '' ? ' found' : '' }}</span>
    </div>
    <div class="overflow-x-auto" role="region" aria-label="Event registrants" tabindex="0">
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
                    <tr class="transition-colors hover:bg-gray-50">
                        <td class="px-4 py-3">
                            @include('seminars._participant-profile', ['user' => $registrant->user])
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $registrant->participant_type }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $registrant->registered_at ? $registrant->registered_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i:s A').' PHT' : 'Unknown' }}</td>
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

    @if($registrants->hasPages())
        <div class="border-t border-gray-100 px-6 py-4">{{ $registrants->links() }}</div>
    @endif
    </section>
</div>
@endsection
