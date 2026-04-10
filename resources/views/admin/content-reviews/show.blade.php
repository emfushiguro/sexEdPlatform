@extends('layouts.admin')

@section('content')
    @php
        $canModerate = $reviewRequest->status === 'in_review';
        $canStartReview = $reviewRequest->status === 'submitted';
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

                <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5 space-y-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Moderation Result</h2>

                    <div class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Current Status</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $reviewRequest->status)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Decision Date</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ optional($reviewRequest->reviewed_at)->format('M d, Y h:i A') ?? 'Not finalized yet' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Reviewer</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ $reviewRequest->reviewer?->name ?? 'Not assigned yet' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Admin Notes / Feedback</p>
                            <p class="mt-1 text-sm text-gray-700">{{ $reviewRequest->feedback ?: 'No moderation notes recorded.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Moderation Actions</h2>

                    @if ($canStartReview)
                        <form method="POST" action="{{ route('admin.content-reviews.start-review', $reviewRequest) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                                Mark As Under Review
                            </button>
                        </form>
                    @endif

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
                            @if ($canStartReview)
                                <p class="mt-1">Start review first before approving or rejecting this submission.</p>
                            @else
                                <p class="mt-1">This submission has already been finalized as <span class="font-semibold">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $reviewRequest->status)) }}</span> and can no longer be moderated.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Past Moderation History</h2>
            <p class="mt-1 text-sm text-gray-500">Complete moderation timeline for this module across submitted revisions.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Reviewed</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Admin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($moderationHistory as $historyItem)
                            @php
                                $historyStatusLabel = $historyItem->status === 'approved'
                                    ? 'Approved / Published'
                                    : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $historyItem->status));
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $historyStatusLabel }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($historyItem->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($historyItem->reviewed_at)->format('M d, Y h:i A') ?? 'Pending' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $historyItem->submitter?->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $historyItem->reviewer?->name ?? 'Not assigned' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $historyItem->feedback ?: 'No notes' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No moderation history records found for this module.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
