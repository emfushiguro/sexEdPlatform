<x-auth-split-layout :showTabs="false">
    <x-slot name="panel"><div class="flex h-full flex-col items-center justify-center p-12 text-center"><img src="{{ asset('/media/Logo.png') }}" alt="" class="mb-4 h-20"><h2 class="text-4xl font-bold text-white">Keep your account yours</h2><p class="mt-4 text-white/80">Recovery codes help you sign in again if you forget your password.</p></div></x-slot>
    <h1 class="text-2xl font-bold text-purple-900">Your recovery codes</h1>
    @if(isset($recoveryCodes))
        <p class="mt-4 text-sm text-gray-700">Save these codes now. They will not be shown again. Each code works once. Anyone with your username and a code may reset your password.</p>
        <pre id="recovery-codes" class="mt-4 whitespace-pre-wrap rounded-xl bg-purple-50 p-4 text-center font-mono text-purple-950">{{ implode("\n", $recoveryCodes) }}</pre>
        <div class="mt-4 flex flex-wrap gap-3" x-data="{ copied: false }"><button type="button" @click="navigator.clipboard.writeText(document.getElementById('recovery-codes').textContent).then(() => copied = true)" class="rounded-xl border border-purple-700 px-4 py-3 font-semibold text-purple-800" x-text="copied ? 'Copied' : 'Copy codes'"></button><button type="button" onclick="window.print()" class="rounded-xl border border-purple-700 px-4 py-3 font-semibold text-purple-800">Print codes</button></div>
    @else
        <p class="mt-4 text-sm text-gray-700">Create eight private, single-use codes. Save them somewhere safe. If you lose your password and all codes, this method cannot recover your account. Creating a new set invalidates the old set.</p>
        @if($errors->any())<p role="alert" class="mt-4 text-sm text-red-700">{{ $errors->first() }}</p>@endif
        <form method="POST" action="{{ route('password.recovery-codes.generate') }}" class="mt-5 space-y-4">@csrf
            <label for="password" class="block text-sm font-semibold">Confirm your password</label><input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border-gray-200 bg-gray-50">
            <button class="w-full rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white">Generate recovery codes</button>
        </form>
    @endif
    <div class="mt-6 flex flex-wrap gap-4 text-sm"><a class="font-semibold text-purple-700 underline" href="{{ route('learner.identity.create') }}">Continue to identity verification</a><a class="font-semibold text-purple-700 underline" href="{{ route('profile.edit') }}">Back to account</a></div>
</x-auth-split-layout>
