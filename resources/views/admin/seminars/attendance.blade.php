@extends('layouts.admin')

@section('title', 'Attendance')
@section('page-title', 'Attendance')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $seminar->title }}</h1>
                <p class="mt-1 text-sm text-gray-600">Full registration and attendance roster.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.seminars.attendance.export', $seminar) }}" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Export CSV</a>
                <a href="{{ route('admin.seminars.show', $seminar) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to event</a>
            </div>
        </div>

        @if(in_array($seminar->event_format, ['in_person', 'external'], true) && ! in_array($seminar->status, ['cancelled', 'archived'], true))
            @include('seminars._attendance-code-management', [
                'generateAction' => route('admin.seminars.attendance.code.generate', $seminar),
                'disableAction' => route('admin.seminars.attendance.code.disable', $seminar),
            ])
        @endif

        @foreach(['success', 'error', 'warning'] as $type)
            @if(session($type))
                <div class="rounded-lg border px-4 py-3 text-sm {{ $type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                    {{ session($type) }}
                </div>
            @endif
        @endforeach

        @include('seminars._attendance-roster')
        <div>{{ $registrants->links() }}</div>
    </div>
@endsection
