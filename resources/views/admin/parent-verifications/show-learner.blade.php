@extends('layouts.admin')

@section('title', 'Learner Identity Review')
@section('page-title', 'Learner Identity Review')

@section('content')
@php
    $images = $case->evidence->keyBy('slot');
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

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <a href="{{ route('admin.parent-verifications.index', ['type' => 'learners', 'status' => $case->status]) }}" class="text-sm font-semibold text-brand-700 hover:underline">← Back to learner cases</a>
    @if(session('success')) <p class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p> @endif
    @if($errors->any()) <div class="rounded-xl bg-rose-50 p-4 text-rose-800">{{ $errors->first() }}</div> @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">{{ $case->learner?->full_name }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $case->learner?->email }}</p>
        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="font-semibold text-gray-500">Date of birth</dt><dd>{{ $case->learner?->birthdate?->format('M d, Y') ?? 'Missing' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Pathway</dt><dd class="capitalize">{{ $case->pathway }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Status</dt><dd class="capitalize">{{ $case->status }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Document</dt><dd>{{ $documentLabel }}@if($case->government_id_type === 'other') — {{ $case->government_id_type_other }} @endif</dd></div>
            <div><dt class="font-semibold text-gray-500">Submitted</dt><dd>{{ $case->submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewer</dt><dd>{{ $reviewer?->full_name ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Reviewed</dt><dd>{{ $case->reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}</dd></div>
            <div><dt class="font-semibold text-gray-500">Approved</dt><dd>{{ $case->approved_at?->format('M d, Y h:i A') ?? '—' }}</dd></div>
        </dl>
        @if($case->rejection_reason)<p class="mt-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-800"><strong>Rejection reason:</strong> {{ $case->rejection_reason }}</p>@endif
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        @foreach(['identity_front' => 'ID front', 'identity_back' => 'ID back', 'selfie' => 'Selfie'] as $slot => $label)
            @if($images->has($slot))
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-3 font-semibold text-gray-900">{{ $label }}</h2>
                    <a href="{{ route('admin.parent-verifications.learners.evidence', [$case, $slot]) }}" target="_blank" rel="noopener" aria-label="Open {{ $label }} full size">
                        <img src="{{ route('admin.parent-verifications.learners.evidence', [$case, $slot]) }}" alt="{{ $label }} evidence" class="max-h-96 w-full rounded-xl bg-gray-100 object-contain">
                    </a>
                    <p class="mt-2 text-xs text-gray-500">Open image to zoom.</p>
                </div>
            @endif
        @endforeach
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900">Manual review guidance</h2>
        <p class="mt-1 text-sm text-gray-600">Use these checks to guide your decision. Checks are not saved and never approve a case.</p>
        <ul class="mt-4 space-y-3">
            @foreach($guidance as $item)
                <li><label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" class="mt-0.5 rounded border-gray-300">{{ $item }}</label></li>
            @endforeach
        </ul>
        @if($case->status === 'pending' && $case->superseded_at === null)
            <div class="mt-6 grid gap-6 border-t border-gray-100 pt-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('admin.parent-verifications.learners.approve', $case) }}" class="space-y-4">
                    @csrf
                    @if($case->pathway === 'adult' && $case->government_id_type === 'other')
                        <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="confirm_government_issued" value="1" required class="mt-0.5 rounded border-gray-300">I confirm that I determined this Other ID is government-issued.</label>
                    @endif
                    <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">Approve identity</button>
                </form>
                <form method="POST" action="{{ route('admin.parent-verifications.learners.reject', $case) }}" class="space-y-3">
                    @csrf
                    <label for="reason" class="block text-sm font-semibold text-gray-700">Rejection reason</label>
                    <select id="reason" name="reason" required class="w-full rounded-xl border border-gray-300 p-3 text-sm">
                        <option value="">Select a reason</option>
                        @foreach(\App\Enums\LearnerIdentityRejectionReason::cases() as $reason)
                            <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800">Reject identity</button>
                </form>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900">Audit history</h2>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm">
            <thead><tr class="border-b border-gray-200 text-gray-500"><th class="py-2">When</th><th>Action</th><th>Actor</th><th>Change</th><th>Reason</th></tr></thead>
            <tbody>@foreach($case->audits->sortByDesc('id') as $audit)
                <tr class="border-b border-gray-100"><td class="py-2">{{ $audit->created_at?->format('M d, Y h:i A') }}</td><td class="capitalize">{{ $audit->action }}</td><td>{{ $audit->actor?->full_name ?? 'System' }}</td><td>{{ $audit->from_status ?? '—' }} → {{ $audit->to_status ?? '—' }}</td><td>{{ $audit->reason ?? '—' }}</td></tr>
            @endforeach</tbody>
        </table></div>
    </section>
</div>
@endsection
