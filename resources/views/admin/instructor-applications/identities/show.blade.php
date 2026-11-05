@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@section('content')
@php
    $reviewUser = $case?->user ?? $application?->user;
    $attempt = $evidence['attempt'] ?? null;
    $idLabel = $attempt?->local_government_id_type
        ? config('guardian_identity.id_types.'.$attempt->local_government_id_type.'.label', str($attempt->local_government_id_type)->headline())
        : 'Not submitted';
    $statusLabel = ucwords(str_replace('_', ' ', $case?->status ?? $application?->status ?? 'pending'));
    $reasonLabels = [
        'identity_mismatch' => 'Identity details do not match',
        'provider_unavailable' => 'Identity evidence unavailable',
        'not_eligible' => 'Applicant is not eligible',
        'other' => 'Other review reason',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <a href="{{ route('admin.instructor-applications.identities.index', ['status' => $case?->status ?? 'all']) }}" class="text-sm font-semibold text-brand-700 hover:underline">Back to Instructor Applications</a>

    @if(session('success'))<p class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800" role="alert">{{ session('error') }}</p>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">{{ $case ? 'Stage One' : 'Stage Two' }}</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Instructor Applications</h1>
                @if($case)
                <p class="mt-1 text-sm text-gray-600">{{ $case->user?->full_name ?? $case->user?->name }} · {{ $case->user?->email }}</p>
            </div>
            @else
                <p class="mt-1 text-sm text-gray-600">{{ $reviewUser?->full_name ?? $reviewUser?->name ?? 'Unknown applicant' }} · {{ $reviewUser?->email ?? 'No email' }}</p>
            </div>
            @endif
            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-800">{{ $statusLabel }}</span>
        </div>
        @if($case && $canReviewIdentities)
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="font-semibold text-gray-500">Status</dt><dd class="mt-1">{{ $statusLabel }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Submission round</dt><dd class="mt-1">{{ $case->submission_round }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Submitted</dt><dd class="mt-1">{{ $case->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Reviewed</dt><dd class="mt-1">{{ $case->reviewed_at?->format('M j, Y g:i A') ?? 'Not reviewed' }}</dd></div>
                <div><dt class="font-semibold text-gray-500">Government ID</dt><dd class="mt-1">{{ $idLabel }}</dd></div>
            </dl>
        @elseif($case)
            <p class="mt-6 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">Stage One identity details are restricted to identity reviewers.</p>
        @endif
    </section>

    @if($case && $canReviewIdentities)
        @include('admin.parent-verifications.partials.identity-evidence-summary', [
            'personaAttempts' => $case->personaInquiries,
        ])

    @if($case->status === 'pending_review' && $attempt)
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="decision-title">
            <h2 id="decision-title" class="text-lg font-bold text-gray-900">Review decision</h2>
            <p class="mt-1 text-sm text-gray-600">Review the submitted information and evidence, then choose the next step.</p>
            <div class="mt-5 flex flex-wrap gap-3 border-t border-gray-100 pt-5">
                <button type="button" data-testid="approve-identity-trigger" @click="$refs.approveDialog.showModal()"
                        class="min-h-11 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Approve identity
                </button>
                <button type="button" data-testid="request-changes-trigger" @click="$refs.requestDialog.showModal()"
                        class="min-h-11 rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">
                    Request changes
                </button>
            </div>

            <dialog x-ref="approveDialog" role="dialog" aria-modal="true" data-testid="approve-identity-dialog" @click.self="$refs.approveDialog.close()"
                    @if($errors->hasAny(['declared_confirmed', 'provider_confirmed', 'sandbox_philippine_id_confirmed', 'sandbox_id_type_confirmed', 'sandbox_portraits_compared'])) x-init="$nextTick(() => $refs.approveDialog.showModal())" @endif
                    aria-labelledby="approve-identity-title" aria-describedby="approve-identity-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Confirm decision</p>
                            <h3 id="approve-identity-title" class="mt-2 text-xl font-bold text-gray-900">Approve this identity?</h3>
                            <p id="approve-identity-description" class="mt-2 text-sm leading-6 text-gray-600">This approves Stage One for {{ $case->user?->full_name ?? $case->user?->name }} and notifies the applicant.</p>
                        </div>
                        <button type="button" @click="$refs.approveDialog.close()" aria-label="Close approval dialog" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-700">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.instructor-applications.identities.approve', $case) }}" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                        <input type="hidden" name="persona_inquiry_id" value="{{ $attempt->id }}">
                        <fieldset class="space-y-3">
                            <legend class="text-sm font-semibold text-gray-900">Required confirmations</legend>
                            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm text-gray-700"><input type="checkbox" name="declared_confirmed" value="1" required class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700"> <span>I reviewed the submitted information.</span></label>
                            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm text-gray-700"><input type="checkbox" name="provider_confirmed" value="1" required class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700"> <span>I reviewed the identity evidence.</span></label>
                            @if($case->sandbox_manual_review)
                                @foreach([
                                    'sandbox_philippine_id_confirmed' => 'I confirmed the ID was issued in the Philippines.',
                                    'sandbox_id_type_confirmed' => 'I confirmed the selected ID type.',
                                    'sandbox_portraits_compared' => 'I compared the ID and selfie portraits.',
                                ] as $field => $label)
                                    <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm text-gray-700"><input type="checkbox" name="{{ $field }}" value="1" required class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700"> <span>{{ $label }}</span></label>
                                    @error($field)<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                                @endforeach
                            @endif
                        </fieldset>
                        @error('declared_confirmed')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        @error('provider_confirmed')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="$refs.approveDialog.close()" class="min-h-11 rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                            <button type="submit" class="min-h-11 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Confirm approval</button>
                        </div>
                    </form>
                </div>
            </dialog>

            <dialog x-ref="requestDialog" role="dialog" aria-modal="true" data-testid="request-changes-dialog" @click.self="$refs.requestDialog.close()"
                    @if($errors->hasAny(['requested_action_codes', 'requested_action_codes.*']) || ($errors->has('reason_note') && old('requested_action_codes') !== null)) x-init="$nextTick(() => $refs.requestDialog.showModal())" @endif
                    aria-labelledby="request-changes-title" aria-describedby="request-changes-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Review decision</p>
                            <h3 id="request-changes-title" class="mt-2 text-xl font-bold text-gray-900">Request changes</h3>
                            <p id="request-changes-description" class="mt-2 text-sm leading-6 text-gray-600">Select what the applicant should update before submitting again.</p>
                        </div>
                        <button type="button" @click="$refs.requestDialog.close()" aria-label="Close request changes dialog" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-rose-700">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.instructor-applications.identities.request-action', $case) }}" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                        <input type="hidden" name="persona_inquiry_id" value="{{ $attempt->id }}">
                        <fieldset class="space-y-3">
                            <legend class="text-sm font-semibold text-gray-800">Requested changes</legend>
                            @foreach(['retry_persona' => 'Retry identity verification', 'replace_evidence' => 'Replace evidence', 'clarify_personal_information' => 'Clarify personal information'] as $code => $label)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm text-gray-700"><input type="checkbox" name="requested_action_codes[]" value="{{ $code }}" @checked(in_array($code, (array) old('requested_action_codes', []), true)) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-rose-700 focus:ring-rose-700"> <span>{{ $label }}</span></label>
                            @endforeach
                            @error('requested_action_codes')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                            @error('requested_action_codes.*')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        </fieldset>
                        <label class="block text-sm font-semibold text-gray-800" for="request_reason_note">Note for the applicant <span class="font-normal text-gray-500">(optional)</span>
                            <textarea id="request_reason_note" name="reason_note" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border-gray-300">{{ old('reason_note') }}</textarea>
                        </label>
                        @error('reason_note')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                            <button type="button" @click="$refs.requestDialog.close(); $refs.rejectDialog.showModal()" class="min-h-11 rounded-xl border border-rose-200 px-5 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700">Final rejection</button>
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" @click="$refs.requestDialog.close()" class="min-h-11 rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                                <button type="submit" class="min-h-11 rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">Request changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </dialog>

            <dialog x-ref="rejectDialog" role="dialog" aria-modal="true" data-testid="reject-identity-dialog" @click.self="$refs.rejectDialog.close()"
                    @if($errors->has('reason_code') || ($errors->has('reason_note') && old('reason_code') !== null)) x-init="$nextTick(() => $refs.rejectDialog.showModal())" @endif
                    aria-labelledby="reject-identity-title" aria-describedby="reject-identity-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Confirm decision</p>
                            <h3 id="reject-identity-title" class="mt-2 text-xl font-bold text-gray-900">Reject this identity?</h3>
                            <p id="reject-identity-description" class="mt-2 text-sm leading-6 text-gray-600">Use final rejection only when no further correction is appropriate.</p>
                        </div>
                        <button type="button" @click="$refs.rejectDialog.close()" aria-label="Close rejection dialog" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-rose-700">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.instructor-applications.identities.reject', $case) }}" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                        <input type="hidden" name="persona_inquiry_id" value="{{ $attempt->id }}">
                        <label class="block text-sm font-semibold text-gray-800" for="reason_code">Rejection reason
                            <select id="reason_code" name="reason_code" required class="mt-2 w-full rounded-xl border-gray-300">
                                <option value="">Select a reason</option>
                                @foreach($reasonLabels as $code => $label)<option value="{{ $code }}" @selected(old('reason_code') === $code)>{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        @error('reason_code')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <label class="block text-sm font-semibold text-gray-800" for="reject_reason_note">Note for the applicant <span class="font-normal text-gray-500">(optional)</span>
                            <textarea id="reject_reason_note" name="reason_note" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border-gray-300">{{ old('reason_note') }}</textarea>
                        </label>
                        @error('reason_note')<p class="text-sm text-rose-700" role="alert">{{ $message }}</p>@enderror
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="$refs.rejectDialog.close()" class="min-h-11 rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                            <button type="submit" class="min-h-11 rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">Reject identity</button>
                        </div>
                    </form>
                </div>
            </dialog>
        </section>
    @endif
    @endif

    @if($application)
        @include('admin.instructor-applications.partials.stage-two-review', [
            'application' => $application,
            'identityCase' => $case,
            'canReviewApplications' => $canReviewApplications,
        ])
    @endif

    @if($case && $canReviewIdentities)
    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900">Decision history</h2>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-3 py-3">When</th><th class="px-3 py-3">Action</th><th class="px-3 py-3">Actor</th><th class="px-3 py-3">Change</th><th class="px-3 py-3">Reason</th></tr></thead><tbody class="divide-y divide-gray-100">
            @foreach($case->audits->sortByDesc('created_at') as $audit)
                <tr><td class="px-3 py-3">{{ $audit->created_at?->format('M j, Y g:i A') }}</td><td class="px-3 py-3 capitalize">{{ str_replace('_', ' ', $audit->action) }}</td><td class="px-3 py-3">{{ $audit->actor?->full_name ?? 'System' }}</td><td class="px-3 py-3">{{ $audit->from_status ?? '—' }} → {{ $audit->to_status ?? '—' }}</td><td class="px-3 py-3">{{ $audit->reason ?? '—' }}</td></tr>
            @endforeach
        </tbody></table></div>
    </section>
    @endif
</div>
@endsection
