@extends('layouts.admin')

@section('content')
    @php
        $canModerate = $reviewRequest->status === 'in_review';
    @endphp

    <div class="space-y-6" x-data="{
        approveModalOpen: false,
        rejectModalOpen: false,
        instructorPreviewOpen: false,
        instructorPreviewTab: 'profile',
        syncModerationEditors() {
            if (typeof tinymce !== 'undefined') {
                tinymce.triggerSave();
            }
        }
    }">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ data_get($workspace, 'module.title', $reviewRequest->module_title) }}</h1>
                <p class="text-sm text-gray-500 mt-1">Review status: {{ data_get($workspace, 'module.status_label', ucfirst(str_replace('_', ' ', $reviewRequest->status))) }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                {{ data_get($workspace, 'module.status_label', 'Pending Review') }}
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

                    @if ($canModerate)
                        <div class="flex flex-col gap-3">
                            @include('admin.content-reviews._approve-modal', ['reviewRequest' => $reviewRequest])
                            @include('admin.content-reviews._reject-modal', ['reviewRequest' => $reviewRequest])
                        </div>
                    @else
                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-700">
                            <p class="font-semibold text-gray-900">Moderation Locked</p>
                            <p class="mt-1">This submission has already been finalized as <span class="font-semibold">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $reviewRequest->status)) }}</span> and can no longer be moderated.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @include('admin.content-reviews.partials.instructor-preview-modal', ['workspace' => $workspace])
    </div>
@endsection

@if ($canModerate)
    @push('scripts')
        <script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof tinymce === 'undefined') {
                    return;
                }

                tinymce.remove('textarea.js-moderation-editor');
                tinymce.init({
                    selector: 'textarea.js-moderation-editor',
                    license_key: 'gpl',
                    menubar: false,
                    branding: false,
                    height: 220,
                    plugins: 'lists link table code',
                    toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link table | removeformat | code',
                    content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }'
                });
            });
        </script>
    @endpush
@endif
