@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ data_get($workspace, 'module.title', $reviewRequest->module_title) }}</h1>
                <p class="text-sm text-gray-500 mt-1">Review status: {{ ucfirst(str_replace('_', ' ', $reviewRequest->status)) }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                Pending Review
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                @include('admin.content-reviews.partials.module-overview', ['reviewRequest' => $reviewRequest, 'workspace' => $workspace])
                @include('admin.content-reviews.partials.workspace-tree', ['workspace' => $workspace])
            </div>

            <div class="space-y-6">
                @include('admin.content-reviews.partials.instructor-credibility', ['workspace' => $workspace])

                <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Moderation Actions</h2>

                    @if ($reviewRequest->feedback)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            <p class="font-semibold">Previous Feedback</p>
                            <p class="mt-1">{{ $reviewRequest->feedback }}</p>
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @include('admin.content-reviews._approve-modal', ['reviewRequest' => $reviewRequest])
                        @include('admin.content-reviews._reject-modal', ['reviewRequest' => $reviewRequest])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
