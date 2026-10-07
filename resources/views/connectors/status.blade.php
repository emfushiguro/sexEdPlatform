@extends('layouts.learner-app')

@section('title', 'Connector Status')

@section('content')
@php
    $operationalStatus = str($connector->status)->replace('_', ' ')->title();
    $verificationStatus = $connector->verification_status
        ? str($connector->verification_status)->replace('_', ' ')->title()
        : 'Legacy verification';
@endphp

<section class="mx-auto max-w-5xl space-y-6" x-data="{ showWithdrawModal: false }">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-purple-700">Connector status</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ $connector->category ? str($connector->category)->replace('_', ' ')->title() : 'Connector organization' }}</p>
            </div>

            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $connector->name }} logo" class="h-20 w-20 rounded-xl object-cover ring-1 ring-gray-200">
            @endif
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Operational status</p>
                <p class="mt-2 inline-flex rounded-full bg-purple-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-purple-700">{{ $operationalStatus }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Verification status</p>
                <p class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-gray-700">{{ $verificationStatus }}</p>
            </div>
        </div>

        <p class="mt-5 rounded-xl bg-purple-50 p-4 text-sm text-purple-900">
            {{ $nextAction }}
        </p>

        @if($isApplicant && $connector->status === 'verified')
            <a href="{{ route('connector.dashboard', $connector) }}" class="mt-4 inline-flex rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">
                Open dashboard
            </a>
        @elseif(! $isApplicant)
            <p class="mt-4 text-sm text-gray-600">Only the connector applicant can view verification evidence or submit replacements.</p>
        @endif
    </div>

    @if($isApplicant)
        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('success') }}</div>
        @endif

        @if($reviewHistory->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="review-history-heading">
                <h2 id="review-history-heading" class="text-lg font-bold text-gray-900">Review history</h2>
                <ol class="mt-4 space-y-3">
                    @foreach($reviewHistory as $review)
                        <li class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ str($review['status'])->replace('_', ' ')->title() }}</span>
                                @if($review['reviewed_at'])
                                    <time class="text-xs text-gray-500" datetime="{{ $review['reviewed_at']->toIso8601String() }}">{{ $review['reviewed_at']->format('M j, Y g:i A') }}</time>
                                @endif
                            </div>
                            @if($review['reason'])
                                <p class="mt-2 text-sm text-gray-600">{{ $review['reason'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if($requirements->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" aria-labelledby="requirements-heading">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="requirements-heading" class="text-lg font-bold text-gray-900">Verification requirements</h2>
                        <p class="mt-1 text-sm text-gray-600">Your submitted evidence and the next required action are shown below.</p>
                    </div>
                    <p class="text-sm font-semibold text-gray-700">
                        {{ $requirements->where('status', 'satisfied')->count() }} of {{ $requirements->count() }} satisfied
                    </p>
                </div>

                <div class="mt-6 space-y-6">
                    @foreach($requirements->groupBy('evidence_category') as $category => $categoryRequirements)
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">{{ str($category)->replace('_', ' ')->title() }}</h3>
                            <div class="mt-3 space-y-4">
                                @foreach($categoryRequirements as $requirement)
                                    @php
                                        $definition = is_array($requirement->definition_snapshot) ? $requirement->definition_snapshot : [];
                                        $isEligible = $eligibleRequirements->contains('id', $requirement->id);
                                        $metadataFields = $definition['metadata_fields'] ?? [];
                                        $acceptedMimes = implode(',', $definition['accepted_mime_types'] ?? []);
                                    @endphp
                                    <article class="rounded-xl border border-gray-200 p-4">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">{{ $requirement->label }}</h4>
                                                <p class="mt-1 text-sm text-gray-600">{{ $requirement->guidance }}</p>
                                            </div>
                                            <span class="inline-flex w-fit rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-gray-700">{{ str($requirement->status)->replace('_', ' ')->title() }}</span>
                                        </div>

                                        @if($requirement->documents->where('status', '!=', 'superseded')->isNotEmpty())
                                            <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                                @foreach($requirement->documents->where('status', '!=', 'superseded') as $document)
                                                    <li class="rounded-lg bg-gray-50 p-3">
                                                        <span class="font-medium text-gray-800">{{ $document->original_name }}</span>
                                                        <span class="ml-2 text-xs uppercase tracking-wide text-gray-500">{{ $document->status }}</span>
                                                        @if($document->status === 'rejected' && $document->applicant_rejection_reason)
                                                            <p class="mt-1 text-sm text-red-700">{{ $document->applicant_rejection_reason }}</p>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if($canResubmit && $isEligible)
                                            <div class="mt-4 rounded-lg border border-purple-100 bg-purple-50 p-4">
                                                <label for="resubmit-{{ str($requirement->requirement_key)->slug('-') }}" class="block text-sm font-semibold text-purple-900">Replacement file</label>
                                                <input
                                                    id="resubmit-{{ str($requirement->requirement_key)->slug('-') }}"
                                                    name="evidence[{{ $requirement->requirement_key }}][files][]"
                                                    type="file"
                                                    accept="{{ $acceptedMimes }}"
                                                    required
                                                    class="mt-2 block w-full text-sm text-gray-700"
                                                >
                                                @foreach($metadataFields as $field)
                                                    <label for="resubmit-{{ str($requirement->requirement_key)->slug('-') }}-{{ $field }}" class="mt-3 block text-sm font-medium text-gray-700">{{ str($field)->headline() }}</label>
                                                    <input
                                                        id="resubmit-{{ str($requirement->requirement_key)->slug('-') }}-{{ $field }}"
                                                        name="evidence[{{ $requirement->requirement_key }}][{{ $field }}]"
                                                        type="{{ in_array($field, ['issue_date', 'expiry_date'], true) ? 'date' : 'text' }}"
                                                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                                    >
                                                @endforeach
                                                @error('evidence.'.$requirement->requirement_key.'.files')
                                                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if($canResubmit)
            <section class="rounded-2xl border border-purple-200 bg-white p-6 shadow-sm" aria-labelledby="resubmit-heading">
                <h2 id="resubmit-heading" class="text-lg font-bold text-gray-900">Submit verification updates</h2>
                <p class="mt-1 text-sm text-gray-600">Only rejected, expired, or administrator-requested requirements can be replaced. Accepted evidence stays on the record.</p>

                @if($eligibleRequirements->isNotEmpty())
                    <form method="POST" action="{{ route('connector.verification.resubmit', $connector) }}" enctype="multipart/form-data" class="mt-5">
                        @csrf
                        <input type="hidden" name="submission_round" value="{{ $connector->current_submission_round }}">
                        @error('evidence')
                            <p class="mb-3 text-sm text-red-700" role="alert">{{ $message }}</p>
                        @enderror
                        @error('submission_round')
                            <p class="mb-3 text-sm text-red-700" role="alert">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">
                            Submit replacement evidence
                        </button>
                    </form>
                @else
                    <p class="mt-4 text-sm text-gray-600">There are no replacement uploads available on this application yet.</p>
                @endif
            </section>
        @endif

        @if($connector->status === 'pending' && $connector->created_by === auth()->id())
            <div class="flex justify-end">
                <button type="button" @click="showWithdrawModal = true" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                    Withdraw Application
                </button>
            </div>
        @endif
    @endif

    @if($isApplicant && $connector->status === 'pending' && $connector->created_by === auth()->id())
        <div
            x-cloak
            x-show="showWithdrawModal"
            class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/50 px-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="withdraw-heading"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.outside="showWithdrawModal = false">
                <h2 id="withdraw-heading" class="text-lg font-bold text-gray-900">Withdraw application</h2>
                <p class="mt-3 text-sm text-gray-600">This cancels the pending connector application before an admin decision. You can submit a new application later if needed.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showWithdrawModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <form method="POST" action="{{ route('connector.withdraw', $connector) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm Withdrawal</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</section>
@endsection
