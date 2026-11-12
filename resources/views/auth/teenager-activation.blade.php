<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Finish your account</h2>
            <p class="max-w-xs text-lg text-white/80">Choose your own password before submitting Teenager identity evidence.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-purple-900">Activate {{ $child->name }}</h1>
        <p class="mt-1 text-sm text-gray-600">The guardian relationship is approved. Set the Teenager's final sign-in details.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4" role="alert">
            <ul class="list-inside list-disc text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('minor.activation.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="code" value="{{ old('code', $code) }}">

        <div>
            <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
            <input id="username" name="username" type="text" required autocomplete="username" value="{{ old('username', $profile?->username) }}" pattern="[a-z0-9_-]+" minlength="3" maxlength="30" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Your password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm your password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <label class="flex items-start gap-2 rounded-xl border border-purple-200 bg-purple-50 p-3 text-sm text-purple-900">
            <input type="checkbox" name="privacy_notice" value="1" required class="mt-0.5 rounded border-purple-300">
            <span>I have read the Teenager Privacy Notice and understand how my information is used.</span>
        </label>

        <button type="submit" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl px-8 py-3.5 font-semibold text-white shadow-md transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Activate account</button>
    </form>
</x-auth-split-layout>
