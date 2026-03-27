@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $reviewRequest->module->title }}</h1>
            <p class="text-sm text-gray-500">Review status: {{ $reviewRequest->status }}</p>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Submission</h2>
                <p class="mt-1 text-sm text-gray-900">Submitted by: {{ $reviewRequest->revision?->submitter?->name ?? 'Unknown' }}</p>
                <p class="text-sm text-gray-900">Submitted at: {{ optional($reviewRequest->submitted_at)->toDayDateTimeString() }}</p>
            </div>

            @if ($reviewRequest->feedback)
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Feedback</h2>
                    <p class="mt-1 text-sm text-gray-900">{{ $reviewRequest->feedback }}</p>
                </div>
            @endif

            <div class="flex gap-3">
                @include('admin.content-reviews._approve-modal', ['reviewRequest' => $reviewRequest])
                @include('admin.content-reviews._reject-modal', ['reviewRequest' => $reviewRequest])
            </div>
        </div>
    </div>
@endsection
