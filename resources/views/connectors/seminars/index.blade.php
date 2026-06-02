@extends('layouts.connector-app')

@section('title', 'Seminars')
@section('page-title', 'Seminars')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Seminars</h2>
            <p class="mt-1 text-sm text-gray-600">Manage free webinars and physical seminars for your connector.</p>
        </div>
        <a href="{{ route('connector.seminars.create', $connector) }}" class="inline-flex items-center justify-center rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Create Seminar</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Starts</th>
                    <th class="px-4 py-3 text-right">Registrants</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($seminars as $seminar)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="font-semibold text-purple-700 hover:text-purple-900">{{ $seminar->title }}</a>
                            <div class="text-xs text-gray-500">{{ config('seminars.categories.'.$seminar->category, ucfirst((string) $seminar->category)) }}</div>
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $seminar->type }}</td>
                        <td class="px-4 py-3 capitalize">{{ $seminar->status }}</td>
                        <td class="px-4 py-3">{{ optional($seminar->starts_at ?? $seminar->schedule)->format('M d, Y g:i A') }}</td>
                        <td class="px-4 py-3 text-right">{{ $seminar->registrants_count ?? $seminar->registrants()->count() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">No seminars yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $seminars->links() }}</div>
@endsection
