@extends('layouts.instructor-app')

@section('title', 'Speaker Invitations')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Seminars</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Speaker Invitations</h1>
        </div>p
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ $search }}" placeholder="Search invitations..." class="text-sm border-gray-300 rounded-xl">
            <button class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-brand-700">Search</button>
        </form>
    </div>

    <div class="overflow-hidden bg-white border border-gray-200 rounded-2xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Seminar</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Connector</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Date</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-left text-gray-500 uppercase">Status</th>
                    <th class="px-5 py-3 text-xs font-bold tracking-wide text-right text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($invitations as $speaker)
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $speaker->seminar?->title }}</td>
                        <td class="px-5 py-4 text-sm text-gray-600">{{ $speaker->seminar?->connector?->name }}</td>
                        <td class="px-5 py-4 text-sm text-gray-600">{{ $speaker->seminar?->localStartsAt() ? $speaker->seminar->localStartsAt()->format('M d, Y g:i A').' PHT' : 'TBA' }}</td>
                        <td class="px-5 py-4"><span class="px-3 py-1 text-xs font-bold text-gray-700 capitalize bg-gray-100 rounded-full">{{ $speaker->status }}</span></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('instructor.speaker-invitations.show', $speaker) }}" class="px-3 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-sm text-center text-gray-500">No speaker invitations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invitations->links() }}
</div>
@endsection
