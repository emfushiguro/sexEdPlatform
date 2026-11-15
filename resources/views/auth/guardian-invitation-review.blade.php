<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">A little guidance goes a long way</h2>
            <p class="max-w-xs text-lg text-white/80">Help a dependent take their next step with your support.</p>
        </div>
    </x-slot>
    <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-purple-700">Guardian invitation</p>
    <h1 class="text-2xl font-bold text-purple-900">A dependent is asking for your help</h1>
    <p class="mt-3 text-sm leading-6 text-gray-600">Someone has asked you to help with their Conscious Connections registration. You can review the request before deciding whether to continue.</p>
    <h2 class="mt-7 text-base font-semibold text-gray-900">What happens next?</h2>
    <ol class="mt-3 list-decimal space-y-3 pl-5 text-sm leading-6 text-gray-700">
        <li>Sign in or register using an email you control. It can differ from the address that received this invitation.</li>
        <li>Complete any required guardian verification and onboarding. If you leave the process, return to the invitation in your email to continue.</li>
        <li>Review the dependent's details and submit evidence of your relationship. A minor needs Guardian authorization; an adult can consent personally or use separately reviewed representative authority.</li>
    </ol>
    <p class="mt-6 rounded-xl bg-purple-50 p-4 text-sm leading-6 text-purple-900">Opening this invitation does not create an account or authorize the request. If you do not recognize it, you can close this page.</p>
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('minor.registration.accept', ['token' => $token]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-800 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-900">Sign in</a>
        <a href="{{ route('minor.registration.accept', ['token' => $token, 'action' => 'register']) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-purple-700 px-4 py-2 text-sm font-semibold text-purple-800 hover:bg-purple-50">Create a Guardian account</a>
    </div>
    <a href="{{ url('/') }}" class="mt-4 block text-center text-sm font-semibold text-purple-800 underline">Return to home</a>
</x-auth-split-layout>
