<x-auth-split-layout :showTabs="false">
    <x-slot name="panel"><div class="flex h-full items-center justify-center p-12 text-center text-white"><h2 class="text-4xl font-bold">Your account, your choices</h2></div></x-slot>
    <h1 class="text-2xl font-bold text-purple-900">Review your adult account</h1>
    <p class="mt-3 text-sm text-gray-700">You can now decide how your account is used. Your Guardian's access to your learning and support information needs your permission. You can choose those permissions in your account settings.</p>
    <p class="mt-3 text-sm text-gray-700">You may need to submit identity evidence for a new adult review. Your password remains private.</p>
    @if($errors->any())<p role="alert" class="mt-4 text-sm text-red-700">Please review and accept the notice to continue.</p>@endif
    <form method="POST" action="{{ route('dependent.adult-consent.store') }}" class="mt-6 space-y-5">
        @csrf
        <label class="flex items-start gap-3 rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-purple-900">
            <input type="checkbox" name="adult_consent" value="1" required class="mt-1">
            <span>I choose to continue using my account and agree to the <a class="underline" href="{{ route('terms') }}">Terms</a> and the processing described in the <a class="underline" href="{{ route('privacy') }}">Privacy Notice</a>.</span>
        </label>
        <button class="w-full rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white" type="submit">Continue</button>
    </form>
</x-auth-split-layout>
