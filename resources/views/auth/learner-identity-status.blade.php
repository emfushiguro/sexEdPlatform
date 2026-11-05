<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Identity Verification</h2>
        </div>
    </x-slot>
    <main class="mx-auto max-w-2xl space-y-5">
        @unless($supportHold)<x-wizard-stepper :is-parent-flow="false" />@endunless
        <h1 class="text-3xl font-bold text-gray-900">Identity verification status</h1>
        @if($supportHold)
            <p class="text-gray-800">This account needs guardian support before identity verification can continue. Please contact support.</p>
        @elseif($case->status === 'pending')
            <section class="space-y-4 rounded-2xl border border-amber-200 bg-amber-50 p-5" aria-labelledby="pending-review-title">
                <div>
                    <h2 id="pending-review-title" class="text-lg font-semibold text-amber-950">Pending review</h2>
                    @if($case->evidence_source === 'persona')
                        <p class="mt-2 text-sm text-gray-700">Your ID and selfie were submitted for review. No additional upload is required.</p>
                    @endif
                    <p class="mt-2 text-sm leading-6 text-gray-700">
                        We’ll email you at <span class="font-semibold">{{ $case->learner?->email ?? auth()->user()->email }}</span> when the review is complete.
                        You can return here to check your status.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('learner.identity.status') }}" data-testid="refresh-identity-status"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-800 px-5 py-3 text-sm font-semibold text-white hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">
                        Refresh review status
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Log out
                        </button>
                    </form>
                </div>
            </section>
        @elseif($case->status === 'rejected')
            @php
                $resubmissionAllowed = $case->resubmission_allowed !== false;
                $correctionLabels = ['identity_front' => 'ID front', 'identity_back' => 'ID back', 'selfie' => 'Selfie', 'persona_identity' => 'ID and selfie'];
            @endphp
            <h2 class="text-lg font-semibold text-gray-900">{{ $resubmissionAllowed ? 'Changes required' : 'Rejected' }}</h2>
            @if($case->rejection_reason)
                <p class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-gray-900">{{ $case->rejection_reason }}</p>
            @endif
            @if($resubmissionAllowed)
                <p class="text-gray-800">Update the requested items and submit them for another review.</p>
                @if($case->requested_corrections)
                    <ul class="list-inside list-disc space-y-1 text-gray-800">
                        @foreach($case->requested_corrections as $slot)
                            @if(isset($correctionLabels[$slot]))<li>{{ $correctionLabels[$slot] }}</li>@endif
                        @endforeach
                    </ul>
                @endif
                @if($case->correction_note)
                    <p class="whitespace-pre-line rounded-lg border border-amber-200 bg-amber-50 p-4 text-gray-900">{{ $case->correction_note }}</p>
                @endif
                <a href="{{ route('learner.identity.create') }}" class="inline-block rounded-lg bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Update requested items</a>
            @else
                <p class="text-gray-800" role="status">Contact support to continue.</p>
            @endif
        @elseif($case->status === 'approved')
            @if($case->sandbox_manual_review && ! \App\Models\PersonaInquiry::localManualReviewEnabled())
                <p class="text-gray-800" role="status">Contact support to continue.</p>
            @else
                <h2 class="text-lg font-semibold text-gray-900">Approved</h2>
                <p class="text-gray-800">Your identity verification is approved.</p>
                <a href="{{ route('profile.complete') }}" class="inline-block rounded-lg bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Continue to profile</a>
            @endif
        @endif
        <p><a href="{{ route('privacy') }}" class="font-semibold text-purple-800 underline">Privacy policy</a></p>
    </main>
</x-auth-split-layout>
