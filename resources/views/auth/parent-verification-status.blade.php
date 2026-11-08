<x-auth-split-layout :showTabs="false">
    @php
        $status = $user->parent_verification_status ?? 'pending';
        $resubmissionAllowed = $user->parent_identity_resubmission_allowed !== false;
        $sandboxApprovalUnavailable = $status === 'approved'
            && $user->parent_identity_sandbox_manual_review
            && ! \App\Models\PersonaInquiry::localManualReviewEnabled();
        $isApproved = ($isApproved ?? ($status === 'approved')) && ! $sandboxApprovalUnavailable;
        $showApprovedModal = (bool) session('show_parent_approved_modal', false);
        $parentReasonRaw = (string) ($user->parent_verification_rejection_reason ?? '');
        $parentRejectionReasonText = trim((string) preg_replace(
            '/\s+/u',
            ' ',
            str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($parentReasonRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        ));
        $requestedCorrectionLabels = collect((array) ($user->parent_identity_requested_corrections ?? []))
            ->map(fn (string $correction): ?string => match ($user->parent_identity_evidence_source === 'persona' && in_array($correction, ['id', 'selfie'], true) ? 'persona_identity' : $correction) {
                'persona_identity' => 'ID and selfie',
                'id' => 'ID document',
                'selfie' => 'Selfie',
                default => null,
            })
            ->filter()
            ->unique()
            ->values();
    @endphp

    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Conscious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">
                {{ $isApproved ? 'Verification Approved' : 'Guardian Verification' }}
            </h2>
            <p class="text-white/80 text-lg max-w-xs">
                {{ $sandboxApprovalUnavailable
                    ? 'Contact support to continue.'
                    : ($isApproved
                    ? 'Your guardian account is approved. You can start learning or create a dependent account.'
                    : ($status === 'rejected' ? ($resubmissionAllowed ? 'Your submission needs correction.' : 'Contact support to continue.') : 'Your account is being reviewed by an administrator.')) }}
            </p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Verification Status</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ $sandboxApprovalUnavailable
                ? 'Contact support to continue.'
                : ($isApproved
                ? 'Your guardian verification is complete. Choose what you want to do next.'
                : ($status === 'rejected' ? ($resubmissionAllowed ? 'Review the feedback and submit your identity evidence again.' : 'Contact support to continue.') : 'We are reviewing your guardian identity document.')) }}
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($user->parent_identity_evidence_source === 'persona' && ! $isApproved && $status === 'pending')
        <p class="mb-4 text-sm text-gray-700">Your ID and selfie were submitted for review. No additional upload is required.</p>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($isApproved)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-4">
            <p class="font-semibold">Verification result: Approved</p>
            <p class="mt-1">Your guardian account is now active. You can continue learning and manage dependent accounts.</p>
        </div>
    @elseif($sandboxApprovalUnavailable)
        <p class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">Contact support to continue.</p>
    @elseif($status === 'rejected')
        <div class="mb-4 space-y-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
            <p class="font-semibold">Verification result: Rejected</p>
            <p>{{ $resubmissionAllowed ? 'Your last submission needs correction before we can continue verification.' : 'Your guardian verification was rejected.' }}</p>
            <p class="break-words"><strong>Admin Feedback:</strong> {{ $parentRejectionReasonText !== '' ? $parentRejectionReasonText : 'No reason provided by administrator.' }}</p>
            @if($resubmissionAllowed)
                @if($requestedCorrectionLabels->isNotEmpty())
                    <p><strong>Requested updates:</strong> {{ $requestedCorrectionLabels->join(', ', ' and ') }}</p>
                @endif
                <p>Submit clear, complete ID and selfie images together for another review.</p>
            @else
                <p role="status">Contact support to continue.</p>
            @endif
        </div>

        @if($resubmissionAllowed)
        <a href="{{ route('guardian.verification.create') }}"
           class="mb-6 w-full inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            Resubmit Guardian Verification
        </a>
        @endif
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
            <p class="font-semibold">Verification result: Pending Review</p>
            <p class="mt-1">You will receive an email when the review is complete.</p>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            While this is pending, dependent account creation and guardian management features are disabled.
        </p>
    @endif

    <div class="space-y-3" x-data="{ showApprovedModal: {{ $isApproved && $showApprovedModal ? 'true' : 'false' }} }">
        @if($isApproved)
            <a href="{{ route('learner.dashboard') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
               style="background: linear-gradient(135deg, #0EA5E9, #2563EB);">
                Start Learning
            </a>

            <a href="{{ route('parent.create-child') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
               style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                Create Dependent Account
            </a>

            <div x-cloak
                 x-show="showApprovedModal"
                 x-transition.opacity
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                     @click.away="showApprovedModal = false">
                    <h3 class="text-xl font-bold text-purple-900">Guardian Verification Approved</h3>
                    <p class="mt-2 text-sm text-gray-600">Your guardian account is now active. Choose what you want to do next.</p>

                    <div class="mt-5 space-y-3">
                        <a href="{{ route('learner.dashboard') }}"
                           class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
                           style="background: linear-gradient(135deg, #0EA5E9, #2563EB);">
                            Start Learning
                        </a>

                        <a href="{{ route('parent.create-child') }}"
                           class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
                           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            Create Dependent Account
                        </a>
                    </div>

                </div>
            </div>
        @elseif($status === 'pending')
            <a href="{{ route('guardian.verification.status') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                Check status
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" data-logout-form>
            @csrf
            <button type="submit"
                    class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                Log Out
            </button>
        </form>
    </div>
</x-auth-split-layout>
