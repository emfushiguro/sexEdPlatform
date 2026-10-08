@extends('layouts.connector-app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $seminar->title }}</h2>
            <p class="mt-1 text-sm text-gray-600">Full registration and attendance roster.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('connector.seminars.attendance.export', [$connector, $seminar]) }}" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Export CSV</a>
            <a href="{{ route('connector.seminars.show', [$connector, $seminar]) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
        </div>
    </div>

    @if(in_array($seminar->event_format, ['in_person', 'external'], true) && ! in_array($seminar->status, ['cancelled', 'archived'], true))
        <div class="mb-6">@include('seminars._attendance-code-management', [
            'generateAction' => route('connector.seminars.attendance.code.generate', [$connector, $seminar]),
            'disableAction' => route('connector.seminars.attendance.code.disable', [$connector, $seminar]),
        ])</div>
    @endif

    @include('seminars._attendance-roster')
    <div class="mt-4">{{ $registrants->links() }}</div>
@endsection
