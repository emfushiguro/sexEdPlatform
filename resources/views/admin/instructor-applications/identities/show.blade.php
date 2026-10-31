@extends('layouts.admin')

@section('title', 'Instructor Identity Review')
@section('page-title', 'Instructor Identity Review')

@section('content')
@php
    $attempt = $evidence['attempt'] ?? null;
    $provider = $evidence['provider_attributes'] ?? [];
    $comparison = (array) ($case->comparison_summary ?? []);
    $declared = (array) ($case->declared_snapshot ?? []);
    $fields = [
        'first_name' => ['label' => 'First name', 'provider' => 'name-first'],
        'middle_initial' => ['label' => 'Middle name', 'provider' => 'name-middle'],
        'last_name' => ['label' => 'Last name', 'provider' => 'name-last'],
        'birthdate' => ['label' => 'Birthdate', 'provider' => 'birthdate'],
        'city_name' => ['label' => 'City', 'provider' => 'city'],
        'barangay_name' => ['label' => 'Barangay', 'provider' => 'subdivision'],
    ];
    $statusLabel = ucwords(str_replace('_', ' ', $case->status));
    $reasonLabels = [
        'identity_mismatch' => 'Identity details do not match',
        'provider_unavailable' => 'Provider evidence unavailable',
        'not_eligible' => 'Applicant is not eligible',
        'other' => 'Other review reason',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <a href="{{ route('admin.instructor-applications.identities.index', ['status' => $case->status]) }}" class="text-sm font-semibold text-brand-700 hover:underline">← Back to identity queue</a>

    @if(session('success'))<p class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800" role="alert">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Stage One</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Identity Review</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $case->user?->full_name ?? $case->user?->name }} · {{ $case->user?->email }}</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-800">{{ $statusLabel }}</span>
        </div>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="font-semibold text-gray-500">Source</dt><dd class="mt-1 capitalize">{{ str_replace('_', ' ', $case->source) }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Submission round</dt><dd class="mt-1">{{ $case->submission_round }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Submitted</dt><dd class="mt-1">{{ $case->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewed</dt><dd class="mt-1">{{ $case->reviewed_at?->format('M j, Y g:i A') ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Persona status</dt><dd class="mt-1">{{ $attempt?->persona_status ?: 'Unavailable' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Policy outcome</dt><dd class="mt-1">{{ $attempt?->verification_outcome ? ucwords(str_replace('_', ' ', $attempt->verification_outcome)) : 'Unavailable' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Country policy</dt><dd class="mt-1">{{ $attempt?->country_policy_status ?: 'Unavailable' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Document policy</dt><dd class="mt-1">{{ $attempt?->document_policy_status ?: 'Unavailable' }}</dd></div>
        </dl>
        @if($case->sandbox_manual_review)
            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="note"><strong>Sandbox review:</strong> confirm the submitted ID, selected ID type, and ID/selfie portraits before approval. This is not production identity proof.</p>
        @endif
    </section>

    @include('admin.parent-verifications.partials.persona-summary', [
        'compactPersonaSummary' => true,
        'personaAttempts' => $case->personaInquiries,
        'sandboxManualReview' => $case->sandbox_manual_review,
    ])

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Declared and provider attributes</h2>
                <p class="mt-1 text-sm text-gray-600">Provider values are fetched for this review only. They are allowlisted and are not stored in the case.</p>
            </div>
            <span class="text-sm {{ ($evidence['provider_available'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">{{ ($evidence['provider_available'] ?? false) ? 'Provider values available' : 'Provider values unavailable' }}</span>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-3 py-3">Field</th><th class="px-3 py-3">Declared</th><th class="px-3 py-3">Provider</th><th class="px-3 py-3">Comparison</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($fields as $key => $field)
                        @php($outcome = data_get($comparison, 'fields.'.$key.'.outcome', 'unavailable'))
                        <tr>
                            <th class="px-3 py-3 font-semibold text-gray-700">{{ $field['label'] }}</th>
                            <td class="px-3 py-3">{{ $declared[$key] ?? 'Unavailable' }}</td>
                            <td class="px-3 py-3">{{ $provider[$field['provider']] ?? 'Unavailable' }}</td>
                            <td class="px-3 py-3 font-semibold {{ $outcome === 'match' ? 'text-emerald-700' : ($outcome === 'mismatch' ? 'text-rose-700' : 'text-amber-700') }}">{{ ucwords(str_replace('_', ' ', $outcome)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if($case->status === 'pending_review' && $attempt)
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="decision-title">
            <h2 id="decision-title" class="text-lg font-bold text-gray-900">Record Stage One decision</h2>
            <p class="mt-1 text-sm text-gray-600">The current submission round and Persona attempt are required so an old review cannot decide a newer attempt.</p>
            <div class="mt-5 grid gap-6 lg:grid-cols-3">
                <form method="POST" action="{{ route('admin.instructor-applications.identities.approve', $case) }}" class="space-y-4 rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                    @csrf
                    <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                    <input type="hidden" name="persona_inquiry_id" value="{{ $attempt->id }}">
                    <h3 class="font-semibold text-emerald-900">Approve</h3>
                    <label class="flex gap-2 text-sm text-gray-700"><input type="checkbox" name="declared_confirmed" value="1" required> I reviewed the declared information.</label>
                    <label class="flex gap-2 text-sm text-gray-700"><input type="checkbox" name="provider_confirmed" value="1" required> I reviewed the provider policy and comparison.</label>
                    @if($case->sandbox_manual_review)
                        <label class="flex gap-2 text-sm text-gray-700"><input type="checkbox" name="sandbox_philippine_id_confirmed" value="1" required> Philippine issuance confirmed.</label>
                        <label class="flex gap-2 text-sm text-gray-700"><input type="checkbox" name="sandbox_id_type_confirmed" value="1" required> Selected ID type confirmed.</label>
                        <label class="flex gap-2 text-sm text-gray-700"><input type="checkbox" name="sandbox_portraits_compared" value="1" required> ID and selfie portraits compared.</label>
                    @endif
                    <label class="block text-sm text-gray-700">Rationale if a field mismatches
                        <textarea name="mismatch_rationale" rows="3" maxlength="2000" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                    </label>
                    <button class="min-h-11 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Approve Stage One</button>
                </form>

                <form method="POST" action="{{ route('admin.instructor-applications.identities.request-action', $case) }}" class="space-y-4 rounded-xl border border-amber-200 bg-amber-50/40 p-4">
                    @csrf
                    <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                    <input type="hidden" name="persona_inquiry_id" value="{{ $attempt->id }}">
                    <h3 class="font-semibold text-amber-900">Request action</h3>
                    @foreach(['retry_persona' => 'Retry Persona', 'replace_evidence' => 'Replace evidence', 'clarify_personal_information' => 'Clarify personal information'] as $code => $label)
                        <label class="flex gap-2 text-sm text-gray-700"><input type="checkbox" name="requested_action_codes[]" value="{{ $code }}"> {{ $label }}</label>
                    @endforeach
                    <textarea name="reason_note" rows="3" maxlength="2000" placeholder="Explain what the applicant should do" class="w-full rounded-lg border-gray-300"></textarea>
                    <button class="min-h-11 rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white">Request action</button>
                </form>

                <form method="POST" action="{{ route('admin.instructor-applications.identities.reject', $case) }}" class="space-y-4 rounded-xl border border-rose-200 bg-rose-50/40 p-4">
                    @csrf
                    <input type="hidden" name="submission_round" value="{{ $case->submission_round }}">
                    <input type="hidden" name="persona_inquiry_id" value="{{ $attempt->id }}">
                    <h3 class="font-semibold text-rose-900">Reject</h3>
                    <label class="block text-sm text-gray-700">Reason
                        <select name="reason_code" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select a reason</option>
                            @foreach($reasonLabels as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <textarea name="reason_note" rows="3" maxlength="2000" placeholder="Optional note" class="w-full rounded-lg border-gray-300"></textarea>
                    <button class="min-h-11 rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Reject Stage One</button>
                </form>
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900">Decision history</h2>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-3 py-3">When</th><th class="px-3 py-3">Action</th><th class="px-3 py-3">Actor</th><th class="px-3 py-3">Change</th><th class="px-3 py-3">Reason</th></tr></thead><tbody class="divide-y divide-gray-100">
            @foreach($case->audits->sortByDesc('created_at') as $audit)
                <tr><td class="px-3 py-3">{{ $audit->created_at?->format('M j, Y g:i A') }}</td><td class="px-3 py-3 capitalize">{{ str_replace('_', ' ', $audit->action) }}</td><td class="px-3 py-3">{{ $audit->actor?->full_name ?? 'System' }}</td><td class="px-3 py-3">{{ $audit->from_status ?? '—' }} → {{ $audit->to_status ?? '—' }}</td><td class="px-3 py-3">{{ $audit->reason ?? '—' }}</td></tr>
            @endforeach
        </tbody></table></div>
    </section>
</div>
@endsection
