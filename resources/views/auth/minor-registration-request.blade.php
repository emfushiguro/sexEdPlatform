<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Ask a Guardian for help</h2>
            <p class="max-w-xs text-lg text-white/80">A Teenager can start the handoff without creating an account or sharing identity evidence.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-purple-900">Guardian-assisted registration</h1>
        <p class="mt-1 text-sm text-gray-600">We will send one private invitation to the guardian email you provide.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4" role="alert">
            <ul class="list-inside list-disc text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('minor.registration.store') }}" class="space-y-5">
        @csrf

        @unless($existingTeenager)
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="mb-1 block text-sm font-medium text-gray-700">First name</label>
                    <input id="first_name" name="first_name" type="text" required value="{{ old('first_name', $pendingMinorRegistration['first_name'] ?? '') }}" autocomplete="given-name" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label for="last_name" class="mb-1 block text-sm font-medium text-gray-700">Last name</label>
                    <input id="last_name" name="last_name" type="text" required value="{{ old('last_name', $pendingMinorRegistration['last_name'] ?? '') }}" autocomplete="family-name" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>

            <div>
                <label for="birthdate" class="mb-1 block text-sm font-medium text-gray-700">Birthdate</label>
                <input id="birthdate" name="birthdate" type="date" required value="{{ old('birthdate', $pendingMinorRegistration['birthdate'] ?? '') }}" max="{{ now()->format('Y-m-d') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
                <p class="mt-1 text-xs text-gray-500">Guardian-assisted registration accepts ages 10 through 17.</p>
            </div>
        @else
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-purple-900">
                <p class="font-semibold">Request help for {{ $child->name }}</p>
                <p class="mt-1">Your birthdate stays authoritative on your existing account.</p>
            </div>
        @endunless

        <div>
            <label for="guardian_email" class="mb-1 block text-sm font-medium text-gray-700">Guardian email</label>
            <input id="guardian_email" name="guardian_email" type="email" required value="{{ old('guardian_email') }}" autocomplete="email" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
            <p class="mt-1 text-xs text-gray-500">Use the guardian's own email. We do not create a learner account from this request.</p>
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl px-8 py-3.5 font-semibold text-white shadow-md transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Send guardian invitation</button>
    </form>
</x-auth-split-layout>
