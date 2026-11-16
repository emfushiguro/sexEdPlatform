<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Suggest sign-in details</h2>
            <p class="max-w-xs text-lg text-white/80">The dependent will choose their own password after the Guardian relationship is approved.</p>
        </div>
    </x-slot>

    <x-wizard-stepper flow="dependent" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-purple-900">Dependent sign-in details</h1>
        <p class="mt-1 text-sm text-gray-600">You may propose a username and add the dependent's personal email. They will choose their own password.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4">
            <ul class="list-inside list-disc text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('parent.create-child.credentials.store') }}">
        @csrf

        <div class="mb-4">
            <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Proposed username <span class="font-normal text-gray-400">(optional)</span></label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" pattern="[a-z0-9_\-]+" minlength="3" maxlength="30"
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, underscores, and hyphens only.</p>
        </div>

        <div class="mb-6">
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Dependent's personal email <span class="font-normal text-gray-400">(optional)</span></label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            <p class="mt-1 text-xs text-gray-500">Leave blank if the dependent does not have a personal mailbox.</p>
        </div>

        <div class="flex items-center justify-between border-t border-gray-200 pt-4">
            <a href="{{ route('parent.create-child.location') }}" class="text-sm text-gray-500 hover:text-gray-700">Back</a>
            <button type="submit" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" class="inline-flex items-center justify-center rounded-xl px-8 py-3.5 font-semibold text-white shadow-md transition hover:opacity-90">
                Continue
            </button>
        </div>
    </form>
</x-auth-split-layout>
