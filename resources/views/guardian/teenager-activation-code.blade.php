<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Activation handoff</h2>
            <p class="max-w-xs text-lg text-white/80">Hand this code to the Teenager. Never choose or receive their password.</p>
        </div>
    </x-slot>

    <div class="space-y-5 text-center">
        <h1 class="text-2xl font-bold text-purple-900">Activate {{ $child->name }}</h1>
        <p class="text-sm text-gray-600">This one-time code expires {{ $handoff->expires_at?->format('M d, Y H:i T') }}.</p>
        <p class="rounded-2xl bg-purple-50 px-4 py-5 text-3xl font-bold tracking-[0.25em] text-purple-900">{{ $code }}</p>
        <a href="{{ route('minor.activation.show', ['code' => $code]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-700 px-6 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Open activation page</a>
    </div>
</x-auth-split-layout>
