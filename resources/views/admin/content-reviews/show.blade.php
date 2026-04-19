@extends('layouts.admin')

@section('content')
    @php
        $canModerate = $reviewRequest->status === 'in_review';
        $canStartReview = $reviewRequest->status === 'submitted';
        $moderationEnabled = (bool) config('features.moderation_enabled', false);
    @endphp

    <div class="space-y-6" x-data="{
        approveModalOpen: false,
        rejectModalOpen: false,
        instructorPreviewOpen: false,
        instructorPreviewTab: 'profile',
        moderationEditorInitRetries: {},
        moderationEditorConfig: {
            license_key: 'gpl',
            menubar: false,
            branding: false,
            height: 220,
            plugins: 'lists link table code',
            toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link table | removeformat | code',
            content_style: 'body { font-family: Poppins, sans-serif; font-size:14px }'
        },
        initModerationEditor(editorId) {
            if (typeof tinymce === 'undefined') {
                const retries = this.moderationEditorInitRetries[editorId] ?? 0;

                if (retries < 20) {
                    this.moderationEditorInitRetries[editorId] = retries + 1;
                    window.setTimeout(() => this.initModerationEditor(editorId), 50);
                }

                return;
            }

            this.moderationEditorInitRetries[editorId] = 0;

            if (tinymce.get(editorId)) {
                return;
            }

            tinymce.init({
                ...this.moderationEditorConfig,
                selector: '#' + editorId,
            });
        },
        destroyModerationEditor(editorId) {
            this.moderationEditorInitRetries[editorId] = 0;

            if (typeof tinymce === 'undefined') {
                return;
            }

            const editor = tinymce.get(editorId);

            if (!editor) {
                return;
            }

            editor.save();
            editor.remove();
        },
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
            <span class="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-700">
                {{ data_get($workspace, 'module.status_label', 'Pending Review') }}
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                @include('admin.content-reviews.partials.module-overview', ['reviewRequest' => $reviewRequest, 'workspace' => $workspace])
                @include('admin.content-reviews.partials.workspace-tree', ['workspace' => $workspace])
            </div>

            <div class="space-y-6">
                @if($moderationEnabled)
                    @include('admin.content-reviews.partials.instructor-credibility', ['workspace' => $workspace])
                @endif

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
                            @if($reviewRequest->feedback)
                                <div class="prose prose-sm mt-1 max-w-none text-gray-700">
                                    {!! strip_tags((string) $reviewRequest->feedback, '<p><br><strong><em><ul><ol><li><a><blockquote><code>') !!}
                                </div>
                            @else
                                <p class="mt-1 text-sm text-gray-700">No moderation notes recorded.</p>
                            @endif
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
                            <div class="prose prose-sm mt-1 max-w-none text-amber-800">
                                {!! strip_tags((string) $reviewRequest->feedback, '<p><br><strong><em><ul><ol><li><a><blockquote><code>') !!}
                            </div>
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

        @if($moderationEnabled)
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
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if($historyItem->feedback)
                                            <div class="prose prose-sm max-w-none text-gray-600">
                                                {!! strip_tags((string) $historyItem->feedback, '<p><br><strong><em><ul><ol><li><a><blockquote><code>') !!}
                                            </div>
                                        @else
                                            No notes
                                        @endif
                                    </td>
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
        @endif

        @include('admin.content-reviews.partials.instructor-preview-modal', ['workspace' => $workspace])
    </div>
@endsection

@if ($canModerate)
    @push('scripts')
        <script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
    @endpush
@endif
