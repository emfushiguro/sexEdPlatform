@extends('layouts.admin')

@section('title', 'Attendance monitoring')
@section('page-title', 'Attendance monitoring')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Attendance monitoring</h1>
            <p class="mt-1 text-sm text-gray-600">Read-only registration and attendance records across Connector events.</p>
        </div>
        <form method="GET" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-sm font-medium text-gray-700">Event
                <input name="event" value="{{ request('event') }}" maxlength="255" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Event title">
            </label>
            <label class="text-sm font-medium text-gray-700">Connector organization
                <select name="connector_id" class="mt-1 w-full rounded-lg border-gray-300">
                    <option value="">All organizations</option>
                    @foreach($connectors as $connector)
                        <option value="{{ $connector->id }}" @selected((string) request('connector_id') === (string) $connector->id)>{{ $connector->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Event date (PHT)
                <input type="date" name="event_date" value="{{ request('event_date') }}" class="mt-1 w-full rounded-lg border-gray-300">
            </label>
            <label class="text-sm font-medium text-gray-700">Attendance status
                <select name="attendance_status" class="mt-1 w-full rounded-lg border-gray-300">
                    <option value="">All statuses</option>
                    @foreach(['not_submitted' => 'Not submitted', 'submitted' => 'Attendance code submitted', 'attended' => 'Attended', 'not_present' => 'Not present', 'joined' => 'Joined', 'left' => 'Left', 'registered' => 'Registered'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('attendance_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-3">
                <button type="submit" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Filter</button>
                <a href="{{ route('admin.seminars.attendance.monitor') }}" class="px-2 py-2 text-sm font-semibold text-gray-600">Reset</a>
            </div>
            @if($errors->any())<p class="text-sm text-red-700 sm:col-span-2">{{ $errors->first() }}</p>@endif
        </form>
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        @foreach(['Event / organization', 'Participant', 'Registration', 'Attendance', 'Recorded by', 'Submission timestamp (PHT)'] as $heading)
                            <th class="px-4 py-3">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($registrants as $registrant)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <div class="mb-2">@include('seminars._thumbnail', ['seminar' => $registrant->seminar, 'variant' => 'compact'])</div>
                                <a href="{{ route('admin.seminars.attendance', $registrant->seminar) }}" class="font-semibold text-purple-700 hover:underline">{{ $registrant->seminar->title }}</a>
                                <p class="text-xs text-gray-500">{{ $registrant->seminar->connector?->name }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $registrant->seminar->formattedSchedule() }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $registrant->user?->name }}<p class="text-xs text-gray-500">{{ $registrant->user?->email }}</p></td>
                            <td class="px-4 py-3">{{ str($registrant->status)->replace('_', ' ')->ucfirst() }}</td>
                            <td class="px-4 py-3">{{ $registrant->attendance_method === 'attendance_code' ? 'Attendance submitted' : ($registrant->attendance_status ? str($registrant->attendance_status)->replace('_', ' ')->ucfirst() : 'Not submitted') }}</td>
                            <td class="px-4 py-3">{{ $registrant->attendance_method ? str($registrant->attendance_method)->replace('_', ' ')->ucfirst() : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $registrant->submission_at ? \Illuminate\Support\Carbon::parse($registrant->submission_at)->timezone(config('app.display_timezone'))->format('M d, Y g:i:s A') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No matching registrations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $registrants->links() }}</div>
    </div>
@endsection
