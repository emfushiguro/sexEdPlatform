@extends('layouts.admin')

@section('title', 'Seminar Moderation')

@section('content')
    <div class="p-6">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Seminar Moderation</h1>
                <p class="mt-1 text-sm text-gray-600">Review connector-owned seminars and webinar activity.</p>
            </div>
        </div>

        <form method="GET" class="mb-5 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-5">
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach(['draft', 'published', 'cancelled', 'completed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="connector_id" class="rounded-lg border-gray-300 text-sm">
                <option value="">All connectors</option>
                @foreach($connectors as $connector)
                    <option value="{{ $connector->id }}" @selected((string) request('connector_id') === (string) $connector->id)>{{ $connector->name }}</option>
                @endforeach
            </select>
            <select name="category" class="rounded-lg border-gray-300 text-sm">
                <option value="">All categories</option>
                @foreach(config('seminars.categories') as $key => $label)
                    <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="rounded-lg border-gray-300 text-sm">
            <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Filter</button>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Seminar</th>
                        <th class="px-4 py-3">Connector</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Schedule</th>
                        <th class="px-4 py-3">Moderation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($seminars as $seminar)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.seminars.show', $seminar) }}" class="font-semibold text-purple-700 hover:text-purple-900">{{ $seminar->title }}</a>
                                <div class="text-xs text-gray-500">{{ $seminar->category }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $seminar->connector?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 capitalize">{{ $seminar->status }}</td>
                            <td class="px-4 py-3">{{ optional($seminar->starts_at)->format('M d, Y g:i A') }}</td>
                            <td class="px-4 py-3">{{ $seminar->admin_moderation_status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">No seminars found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $seminars->links() }}</div>
    </div>
@endsection
