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
            <p class="text-gray-800">Your identity verification is pending review. We will let you know when the review is complete.</p>
        @elseif($case->status === 'rejected')
            <p class="text-gray-800">Your identity verification needs another submission.</p>
            @if($case->rejection_reason)
                <p class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-gray-900">{{ $case->rejection_reason }}</p>
            @endif
            <a href="{{ route('learner.identity.create') }}" class="inline-block rounded-lg bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Resubmit identity images</a>
        @elseif($case->status === 'approved')
            <p class="text-gray-800">Your identity verification is approved.</p>
            <a href="{{ route('profile.complete') }}" class="inline-block rounded-lg bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Continue to profile</a>
        @endif
        <p><a href="{{ route('privacy') }}" class="font-semibold text-purple-800 underline">Privacy policy</a></p>
    </main>
</x-auth-split-layout>
