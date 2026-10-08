@extends('layouts.admin')

@section('title', 'Learner Identity Review')
@section('page-title', 'Learner Identity Review')

@section('content')
@php
    $images = $case->evidence->keyBy('slot');
    $dateOfBirth = $case->learner?->birthdate;
    $learnerAge = $case->learner?->calculateAge();
    $documentLabel = match ($case->document_type) {
        'school_id' => 'School ID',
        'institution_id' => 'Institution ID',
        'government_id' => data_get(config('guardian_identity.id_types', []), $case->government_id_type.'.label', 'Government ID'),
        default => 'Not submitted',
    };
    $guidance = $case->pathway === 'adult'
        ? ['Account name, ID details, and date of birth are consistent.', 'The document is readable and appears valid.', 'The selfie is clear and recent.', 'The selfie reasonably corresponds to the ID photo.']
        : ['The document is readable.', 'Submitted information and age are consistent.', 'The selfie is clear and recent.', 'Check linked guardian requirements when present.'];
@endphp

<div class="px-4 py-8 mx-auto space-y-6 max-w-7xl">
    <a href="{{ route('admin.parent-verifications.index', ['type' => 'learners', 'status' => $case->status]) }}" class="text-sm font-semibold text-brand-700 hover:underline">← Back to learner cases</a>
    @if(session('success')) <p class="p-4 rounded-xl bg-emerald-50 text-emerald-800">{{ session('success') }}</p> @endif
    @if($errors->any()) <div class="p-4 rounded-xl bg-rose-50 text-rose-800">{{ $errors->first() }}</div> @endif

    <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
        <h1 class="text-2xl font-bold text-gray-900">{{ $case->learner?->full_name }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $case->learner?->email }}</p>
        <dl class="grid gap-4 mt-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="font-semibold text-gray-500">Date of birth</dt>
                <dd>{{ $dateOfBirth?->format('M j, Y') ?? 'Missing' }}@if($learnerAge !== null) ({{ $learnerAge }})@endif</dd>
            </div>
            <div><dt class="font-semibold text-gray-500">Pathway</dt><dd class="capitalize">{{ $case->pathway }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Status</dt><dd class="capitalize">{{ $case->status }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Document</dt><dd>{{ $documentLabel }}@if($case->government_id_type === 'other') — {{ $case->government_id_type_other }} @endif</dd></div>
            <div><dt class="font-semibold text-gray-500">Submitted</dt><dd>{{ $case->submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewer</dt><dd>{{ $reviewer?->full_name ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewed</dt><dd>{{ $case->reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Approved</dt><dd>{{ $case->approved_at?->format('M d, Y h:i A') ?? '—' }}</dd></div>
        </dl>
        @if($case->rejection_reason)<p class="p-4 mt-5 text-sm rounded-xl bg-rose-50 text-rose-800"><strong>Rejection reason:</strong> {{ $case->rejection_reason }}</p>@endif
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        @foreach(['identity_front' => 'ID front', 'identity_back' => 'ID back', 'selfie' => 'Selfie'] as $slot => $label)
            @if($images->has($slot))
                <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-2xl">
                    <h2 class="mb-3 font-semibold text-gray-900">{{ $label }}</h2>
                    <a href="{{ route('admin.parent-verifications.learners.evidence', [$case, $slot]) }}" target="_blank" rel="noopener" aria-label="Open {{ $label }} full size">
                        <img src="{{ route('admin.parent-verifications.learners.evidence', [$case, $slot]) }}" alt="{{ $label }} evidence" class="object-contain w-full bg-gray-100 max-h-96 rounded-xl">
                    </a>
                    <p class="mt-2 text-xs text-gray-500">Open image to zoom.</p>
                </div>
            @endif
        @endforeach
    </section>

    
        @if($case->status === 'pending' && $case->superseded_at === null)
            <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-gray-100">
                <button type="button" data-testid="approve-identity-trigger" @click="$refs.approveDialog.showModal()"
                        class="px-5 py-3 text-sm font-semibold text-white transition min-h-11 rounded-xl bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Approve identity
                </button>
                <button type="button" data-testid="reject-identity-trigger" @click="$refs.rejectDialog.showModal()"
                        class="px-5 py-3 text-sm font-semibold text-white transition min-h-11 rounded-xl bg-rose-700 hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">
                    Reject identity
                </button>
            </div>

            <dialog x-ref="approveDialog" role="dialog" aria-modal="true" data-testid="approve-identity-dialog" @click.self="$refs.approveDialog.close()"
                    aria-labelledby="approve-identity-title" aria-describedby="approve-identity-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Confirm decision</p>
                            <h3 id="approve-identity-title" class="mt-2 text-xl font-bold text-gray-900">Approve this identity?</h3>
                            <p id="approve-identity-description" class="mt-2 text-sm leading-6 text-gray-600">
                                This will approve {{ $case->learner?->full_name }}’s current evidence and notify the learner.
                            </p>
                        </div>
                        <button type="button" @click="$refs.approveDialog.close()" aria-label="Close approval dialog"
                                class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-700">×</button>
                    </div>
                    @if($case->pathway === 'adult' && $case->government_id_type === 'other')
                        <form method="POST" action="{{ route('admin.parent-verifications.learners.approve', $case) }}" class="mt-6 space-y-5">
                            @csrf
                            <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                            <label class="flex items-start gap-3 p-4 text-sm text-gray-700 border border-gray-200 rounded-xl">
                                <input type="checkbox" name="confirm_government_issued" value="1" required @checked(old('confirm_government_issued'))
                                       class="mt-0.5 h-5 w-5 rounded border-gray-300 text-emerald-700 focus:ring-emerald-700">
                                <span>I confirm I determined this Other ID is government-issued.</span>
                            </label>
                            @error('confirm_government_issued')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" @click="$refs.approveDialog.close()" class="px-5 py-3 text-sm font-semibold text-gray-700 border border-gray-300 min-h-11 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                                <button type="submit" class="px-5 py-3 text-sm font-semibold text-white min-h-11 rounded-xl bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Confirm approval</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.parent-verifications.learners.approve', $case) }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" @click="$refs.approveDialog.close()" class="px-5 py-3 text-sm font-semibold text-gray-700 border border-gray-300 min-h-11 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                                <button type="submit" class="px-5 py-3 text-sm font-semibold text-white min-h-11 rounded-xl bg-emerald-700 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Confirm approval</button>
                            </div>
                        </form>
                    @endif
                </div>
            </dialog>

            <dialog x-ref="rejectDialog" role="dialog" aria-modal="true" data-testid="reject-identity-dialog" @click.self="$refs.rejectDialog.close()"
                    aria-labelledby="reject-identity-title" aria-describedby="reject-identity-description"
                    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Confirm decision</p>
                            <h3 id="reject-identity-title" class="mt-2 text-xl font-bold text-gray-900">Reject this identity?</h3>
                            <p id="reject-identity-description" class="mt-2 text-sm leading-6 text-gray-600">
                                Choose a reason the learner can use to correct their submission.
                            </p>
                        </div>
                        <button type="button" @click="$refs.rejectDialog.close()" aria-label="Close rejection dialog"
                                class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-rose-700">×</button>
                    </div>
                    <form method="POST" action="{{ route('admin.parent-verifications.learners.reject', $case) }}" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                        <div>
                            <label for="reason" class="block text-sm font-semibold text-gray-800">Rejection reason</label>
                            <select id="reason" name="reason" required aria-invalid="{{ $errors->has('reason') ? 'true' : 'false' }}"
                                    class="w-full p-3 mt-2 text-sm bg-white border border-gray-300 rounded-xl focus:border-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-600/20">
                                <option value="">Select a reason</option>
                                @foreach(\App\Enums\LearnerIdentityRejectionReason::cases() as $reason)
                                    <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                                @endforeach
                            </select>
                            @error('reason')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="$refs.rejectDialog.close()" class="px-5 py-3 text-sm font-semibold text-gray-700 border border-gray-300 min-h-11 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">Cancel</button>
                            <button type="submit" class="px-5 py-3 text-sm font-semibold text-white min-h-11 rounded-xl bg-rose-700 hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">Confirm rejection</button>
                        </div>
                    </form>
                </div>
            </dialog>
        @endif
    </section>

    <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
        <h2 class="text-lg font-bold text-gray-900">Audit history</h2>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm text-left">
            <thead><tr class="text-gray-500 border-b border-gray-200"><th class="py-2">When</th><th>Action</th><th>Actor</th><th>Change</th><th>Reason</th></tr></thead>
            <tbody>@foreach($case->audits->sortByDesc('id') as $audit)
                <tr class="border-b border-gray-100"><td class="py-2">{{ $audit->created_at?->format('M d, Y h:i A') }}</td><td class="capitalize">{{ $audit->action }}</td><td>{{ $audit->actor?->full_name ?? 'System' }}</td><td>{{ $audit->from_status ?? '—' }} → {{ $audit->to_status ?? '—' }}</td><td>{{ $audit->reason ?? '—' }}</td></tr>
            @endforeach</tbody>
        </table></div>
    </section>
</div>
@endsection
