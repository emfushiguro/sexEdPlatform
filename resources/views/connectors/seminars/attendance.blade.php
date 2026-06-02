@extends('layouts.connector-app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $seminar->title }}</h2>
            <p class="mt-1 text-sm text-gray-600">Livestream attendance summary.</p>
        </div>
        <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3">Left</th>
                    <th class="px-4 py-3">Duration</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($attendances as $attendance)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $attendance->user?->name }}</div>
                            <div class="text-xs text-gray-500">{{ $attendance->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ optional($attendance->joined_at)->format('M d, g:i A') ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ optional($attendance->left_at)->format('M d, g:i A') ?? 'N/A' }}</td>
                        <td class="px-4 py-3">{{ number_format($attendance->total_seconds / 60, 1) }} min</td>
                        <td class="px-4 py-3 capitalize">{{ $attendance->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">No attendance recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $attendances->links() }}</div>
@endsection
