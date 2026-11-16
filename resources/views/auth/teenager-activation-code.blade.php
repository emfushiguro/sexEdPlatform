<x-auth-split-layout :showTabs="false">
    <x-slot name="panel"><div class="flex h-full items-center justify-center p-12 text-center text-white"><h2 class="text-4xl font-bold">Activate your account</h2></div></x-slot>
    <h1 class="text-2xl font-bold text-purple-900">Enter your activation code</h1>
    <p class="mt-2 text-sm text-gray-600">Ask your guardian for the one-time code. You will choose your own password on the next page.</p>
    @if ($errors->any())<p role="alert" class="mt-4 text-sm text-red-700">This activation code is invalid or expired. Ask your guardian for a new one.</p>@endif
    @include('auth.partials.dependent-page-help', ['step' => 'activation'])
    <form method="GET" action="{{ route('minor.activation.show') }}" class="mt-6 space-y-4">
        <label for="code" class="block text-sm font-medium">Activation code</label>
        <input id="code" name="code" required maxlength="32" autocomplete="off" class="w-full rounded-xl border border-gray-300 px-3 py-2">
        <button type="submit" class="w-full rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white">Continue</button>
    </form>
</x-auth-split-layout>
