@php
    $reviewCase = $identityCase ?? ($case ?? null);
    $educationLabel = \App\Models\InstructorApplication::EDUCATIONAL_BACKGROUND_LABELS[$application->educational_background] ?? $application->educational_background;
    $documents = [
        ['label' => 'Government ID', 'slot' => 'government_id', 'path' => $application->government_id_path],
        ['label' => 'NBI / Police clearance', 'slot' => 'clearance', 'path' => $application->clearance_path],
        ['label' => 'CV or resume', 'slot' => 'cv_resume', 'path' => $application->cv_resume_path],
        ['label' => 'Teaching credential', 'slot' => 'teaching_credential', 'path' => $application->teaching_credential_path],
        ['label' => 'Sex education certificate', 'slot' => 'sexed_certificate', 'path' => $application->sexed_certificate_path],
        ['label' => 'Professional license', 'slot' => 'professional_license', 'path' => $application->professional_license_path],
    ];
    $documents = collect($documents)->filter(fn ($document) => filled($document['path']))->values();
    $documentRequest = $application->application_metadata['document_change_request'] ?? null;
    $latestReview = $application->latestReview;
    $reviewerName = $latestReview?->reviewedBy?->name ?? $application->approvedBy?->name;
    $decisionAt = $latestReview?->reviewed_at ?? $application->approved_at;
@endphp

<section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" data-testid="stage-two-review">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Stage Two</p>
            <h2 class="mt-2 text-xl font-bold text-gray-900">Stage Two application</h2>
            <p class="mt-1 text-sm text-gray-600">Review the applicant's teaching background and documents.</p>
        </div>
        <span class="rounded-full px-3 py-1.5 text-sm font-semibold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst($application->status) }}</span>
    </div>

    <dl class="mt-6 grid gap-5 text-sm sm:grid-cols-2">
        <div><dt class="font-semibold text-gray-500">Educational background</dt><dd class="mt-1 text-gray-900">{{ $educationLabel ?: 'Not provided' }}</dd></div>
        <div><dt class="font-semibold text-gray-500">Submitted</dt><dd class="mt-1 text-gray-900">{{ $application->submitted_at?->format('M j, Y g:i A') ?? $application->created_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</dd></div>
        <div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Professional background</dt><dd class="mt-1 whitespace-pre-line text-gray-900">{{ $application->bio ?: 'Not provided' }}</dd></div>
    </dl>

    <div class="mt-6 border-t border-gray-100 pt-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-bold text-gray-900">Submitted documents</h3>
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $documents->count() }} submitted</span>
        </div>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            @foreach($documents as $document)
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M9 13h6M9 17h6"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-800">{{ $document['label'] }}</p>
                            <p class="mt-0.5 text-xs text-emerald-700">Submitted</p>
                        </div>
                    </div>
                    @if($document['path'] && $canReviewApplications)
                        <a href="{{ route('admin.instructor-applications.documents.show', [$application, $document['slot']]) }}" target="_blank" rel="noopener" aria-label="Open {{ $document['label'] }}" title="Open document" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50/60 text-brand-700 transition hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 5h5v5m-1-4-7 7m-5 6h10a2 2 0 0 0 2-2v-3M5 5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3"/></svg>
                            <span class="sr-only">Open document</span>
                        </a>
                    @endif
                    </div>
                    @if($canReviewApplications)
                        @php
                            $documentUrl = route('admin.instructor-applications.documents.show', [$application, $document['slot']]);
                            $extension = strtolower(pathinfo($document['path'], PATHINFO_EXTENSION));
                        @endphp
                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                            <div x-data="{ unavailable: false }">
                                <a href="{{ $documentUrl }}" target="_blank" rel="noopener noreferrer" aria-label="View full {{ $document['label'] }}" x-show="!unavailable"><img src="{{ $documentUrl }}" alt="{{ $document['label'] }} preview" loading="lazy" x-on:error="unavailable = true" class="mt-3 h-56 w-full rounded-lg bg-gray-50 object-contain"></a>
                                <p x-show="unavailable" x-cloak role="status" class="mt-3 text-sm text-amber-900">Preview unavailable. Use the open button to try the document.</p>
                            </div>
                        @elseif($extension === 'pdf')
                            <iframe src="{{ $documentUrl }}" title="{{ $document['label'] }} preview" loading="lazy" class="mt-3 h-56 w-full rounded-lg border border-gray-100"></iframe>
                        @else
                            <p class="mt-3 text-sm text-gray-500">Preview unavailable. Use the open button to view this document.</p>
                        @endif
                    @endif
                </div>
            @endforeach
            @if($documents->isEmpty())<p class="text-sm text-gray-500">No documents were submitted.</p>@endif
        </div>
    </div>

    @if($application->status === 'pending' && $canReviewApplications)
        <section class="mt-6 border-t border-gray-100 pt-5" aria-labelledby="stage-two-decision-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 id="stage-two-decision-title" class="text-base font-bold text-gray-900">Review decision</h3>
                    <p class="mt-1 text-sm text-gray-600">Choose how to proceed after reviewing the application.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-testid="approve-stage-two-trigger" @click="$refs.stageTwoApproveDialog.showModal()" @disabled(!empty($documentRequest['slots'])) class="inline-flex min-h-10 items-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Approve application</button>
                    <button type="button" @click="$refs.stageTwoDocumentsDialog.showModal()" @disabled($documents->isEmpty() || !empty($documentRequest['slots'])) class="inline-flex min-h-10 items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50">Request document changes</button>
                    <button type="button" data-testid="reject-stage-two-trigger" @click="$refs.stageTwoRejectDialog.showModal()" class="inline-flex min-h-10 items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">Reject application</button>
                </div>
            </div>
            @if(!empty($documentRequest['slots']))
                <div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-900" role="status">
                    <p class="font-semibold">Waiting for replacement documents</p>
                    <p class="mt-1">{{ $documents->whereIn('slot', $documentRequest['slots'])->pluck('label')->join(', ') }}</p>
                    <p class="mt-1 whitespace-pre-line">{{ $documentRequest['note'] ?? '' }}</p>
                </div>
            @endif

            <dialog x-ref="stageTwoDocumentsDialog" aria-labelledby="stage-two-documents-title" @click.self="$refs.stageTwoDocumentsDialog.close()" class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-6 shadow-2xl backdrop:bg-slate-950/60">
                <h4 id="stage-two-documents-title" class="text-xl font-bold text-gray-900">Request document changes</h4>
                <p class="mt-2 text-sm text-gray-600">Select the documents to replace and explain what needs to change.</p>
                <form method="POST" action="{{ route('admin.instructor-applications.request-documents', $application) }}" class="mt-5 space-y-4">
                    @csrf
                    <fieldset class="space-y-2"><legend class="text-sm font-semibold">Documents</legend>
                        @foreach($documents as $document)
                            <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 text-sm"><input type="checkbox" name="slots[]" value="{{ $document['slot'] }}" class="rounded border-gray-300 text-brand-700"> {{ $document['label'] }}</label>
                        @endforeach
                    </fieldset>
                    <label class="block text-sm font-semibold" for="document-request-note">Instructions for the applicant<textarea id="document-request-note" name="note" required maxlength="2000" rows="3" class="mt-2 w-full rounded-xl border-gray-300"></textarea></label>
                    <div class="flex justify-end gap-2"><button type="button" @click="$refs.stageTwoDocumentsDialog.close()" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Send request</button></div>
                </form>
            </dialog>

            <dialog x-ref="stageTwoApproveDialog" role="dialog" aria-modal="true" aria-labelledby="stage-two-approve-title" @click.self="$refs.stageTwoApproveDialog.close()" class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Confirm decision</p><h4 id="stage-two-approve-title" class="mt-2 text-xl font-bold text-gray-900">Approve this application?</h4><p class="mt-2 text-sm leading-6 text-gray-600">The applicant will be notified and instructor access will be activated.</p></div>
                        <button type="button" @click="$refs.stageTwoApproveDialog.close()" aria-label="Close approval dialog" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.instructor-applications.approve', $application) }}" class="mt-6 space-y-5">
                        @csrf
                        @if($reviewCase)<input type="hidden" name="identity_case_id" value="{{ $reviewCase->id }}">@endif
                        <label class="block text-sm font-semibold text-gray-800" for="stage-two-approval-message-{{ $application->id }}">Message to applicant
                            <textarea id="stage-two-approval-message-{{ $application->id }}" name="admin_message" rows="4" required class="mt-2 w-full rounded-xl border-gray-300">{{ old('admin_message', trim(strip_tags(str_replace('</p>', "\n\n", \App\Services\InstructorApplicationService::defaultApprovalMessage())))) }}</textarea>
                        </label>
                        @error('admin_message')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" @click="$refs.stageTwoApproveDialog.close()" class="min-h-11 rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button><button type="submit" class="min-h-11 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">Confirm approval</button></div>
                    </form>
                </div>
            </dialog>

            <dialog x-ref="stageTwoRejectDialog" role="dialog" aria-modal="true" aria-labelledby="stage-two-reject-title" @click.self="$refs.stageTwoRejectDialog.close()" class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Confirm decision</p><h4 id="stage-two-reject-title" class="mt-2 text-xl font-bold text-gray-900">Reject this application?</h4><p class="mt-2 text-sm leading-6 text-gray-600">The applicant will receive the selected reason and message.</p></div>
                        <button type="button" @click="$refs.stageTwoRejectDialog.close()" aria-label="Close rejection dialog" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.instructor-applications.reject', $application) }}" class="mt-6 space-y-5">
                        @csrf
                        @if($reviewCase)<input type="hidden" name="identity_case_id" value="{{ $reviewCase->id }}">@endif
                        <label class="block text-sm font-semibold text-gray-800" for="stage-two-rejection-reason-{{ $application->id }}">Reason
                            <select id="stage-two-rejection-reason-{{ $application->id }}" name="rejection_reason_code" required class="mt-2 w-full rounded-xl border-gray-300"><option value="">Select a reason</option>@foreach(\App\Enums\InstructorApplicationRejectionReason::cases() as $reason)<option value="{{ $reason->value }}" @selected(old('rejection_reason_code') === $reason->value)>{{ $reason->label() }}</option>@endforeach</select>
                        </label>
                        @error('rejection_reason_code')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <label class="block text-sm font-semibold text-gray-800" for="stage-two-rejection-message-{{ $application->id }}">Message to applicant
                            <textarea id="stage-two-rejection-message-{{ $application->id }}" name="admin_message" rows="4" required class="mt-2 w-full rounded-xl border-gray-300">{{ old('admin_message', trim(strip_tags(str_replace('</p>', "\n\n", \App\Services\InstructorApplicationService::defaultRejectionMessage())))) }}</textarea>
                        </label>
                        @error('admin_message')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" @click="$refs.stageTwoRejectDialog.close()" class="min-h-11 rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button><button type="submit" class="min-h-11 rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800">Confirm rejection</button></div>
                    </form>
                </div>
            </dialog>
        </section>
    @elseif($application->status === 'approved')
        <p class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">Approved by {{ $reviewerName ?? 'an administrator' }}{{ $decisionAt ? ' on '.$decisionAt->format('M j, Y g:i A') : '' }}.</p>
    @elseif($application->status === 'rejected')
        <p class="mt-6 rounded-xl bg-rose-50 p-4 text-sm text-rose-800">Rejected by {{ $reviewerName ?? 'an administrator' }}{{ $decisionAt ? ' on '.$decisionAt->format('M j, Y g:i A') : '' }}.</p>
    @elseif(!$canReviewApplications)
        <p class="mt-6 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">Stage Two actions and document links are restricted to instructor application reviewers.</p>
    @endif
</section>
