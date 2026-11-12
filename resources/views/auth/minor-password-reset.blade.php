<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mx-auto mb-6 h-20 w-auto">
            <h2 class="text-4xl font-bold leading-tight text-white">Recover your account</h2>
            <p class="mt-4 max-w-xs text-lg text-white/80">Use the one-time code your Guardian shared with you.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-xl space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-purple-900">Set a new password</h1>
            <p class="mt-1 text-sm text-gray-500">Your Guardian can approve recovery, but only you choose the new password.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('minor.password.reset.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="mb-1 block text-sm font-medium text-gray-700">One-time recovery code</label>
                <input id="code" name="code" value="{{ old('code', $code) }}" required maxlength="32" autocomplete="one-time-code" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Learner username</label>
                <input id="username" name="username" value="{{ old('username') }}" required autocomplete="username" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Set new password</button>
        </form>

        <p class="text-sm text-gray-600">If your account is now an adult account or the code no longer works, contact support for help.</p>
        <div class="flex flex-wrap gap-4 text-sm">
            <a href="{{ route('help.index') }}" class="font-semibold text-purple-800 underline">Contact support</a>
            <a href="{{ route('login') }}" class="font-semibold text-purple-800 underline">Back to login</a>
        </div>
    </div>
</x-auth-split-layout>
