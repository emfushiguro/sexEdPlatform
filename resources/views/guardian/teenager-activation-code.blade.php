<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Activation handoff</h2>
            <p class="max-w-xs text-lg text-white/80">Hand this code to the dependent. They choose their own password.</p>
        </div>
    </x-slot>

    <div class="space-y-5 text-center">
        <h1 class="text-2xl font-bold text-purple-900">Activate {{ $child->name }}</h1>
        <x-activation-expiry :expires-at="$handoff->expires_at" class="text-sm text-gray-600" />
                <div x-data="{ copied: false, async copy() { try { await navigator.clipboard.writeText(this.$refs.code.value); this.copied = true; } catch (e) { this.$refs.code.select(); this.copied = document.execCommand('copy'); } } }" class="rounded-2xl bg-purple-50 px-4 py-5">
            <input x-ref="code" readonly aria-label="Activation code" class="w-full bg-transparent text-center text-3xl font-bold tracking-[0.25em] text-purple-900" value="{{ $code }}">
            <button type="button" @click="copy()" class="mt-3 rounded-lg border border-purple-400 px-4 py-2 text-sm font-semibold text-purple-800" x-text="copied ? 'Copied' : 'Copy code'"></button><span class="sr-only" aria-live="polite" x-text="copied ? 'Activation code copied' : ''"></span>
        </div>
        <div class="rounded-xl border border-purple-200 p-4 text-left text-sm text-gray-700">
            <p class="font-semibold">On another device</p>
            <p class="mt-2">Open Conscious Connections, choose “Activate a dependent account” on the sign-in page, then enter this code.</p>
            <p class="mt-2 break-all">Activation address: <a class="text-purple-800 underline" href="{{ rtrim(config('app.url'), '/') . route('minor.activation.show', [], false) }}">{{ rtrim(config('app.url'), '/') . route('minor.activation.show', [], false) }}</a></p>
            <p class="mt-2">The other device must be able to reach this website. A 127.0.0.1 or localhost address works only on the computer running it.</p>
        </div>
        <a href="{{ route('parent.children.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-purple-700 px-6 py-3 font-semibold text-purple-800">Back to My Dependents</a>
        <a href="{{ route('minor.activation.show', ['code' => $code]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-700 px-6 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Open activation page</a>
    </div>
</x-auth-split-layout>
