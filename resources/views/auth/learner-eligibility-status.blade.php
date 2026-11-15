<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mb-6 h-20 w-auto">
            <h2 class="text-3xl font-bold">Account setup</h2>
        </div>
    </x-slot>

    <main class="mx-auto max-w-2xl space-y-5">
        <h1 class="text-3xl font-bold text-gray-900">Complete your account setup</h1>

        @switch($code)
            @case('guardian_required')
                <p class="text-gray-700">A Guardian must help complete the required account and relationship steps before you can continue.</p>
                <a href="{{ route('minor.registration.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Ask a Guardian for help</a>
                @break
            @case('relationship_pending')
                <p class="text-gray-700">Your Guardian relationship is waiting for the required review. You can return here later for an update.</p>
                @break
            @case('activation_required')
                <p class="text-gray-700">Your approved Guardian must provide the one-time activation handoff before you can continue.</p>
                @break
            @case('adult_email_required')
                <p class="text-gray-700">Add and verify your own email address before continuing.</p>
                <a href="{{ route('profile.edit') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Add your email</a>
                @break
            @case('profile_required')
                <p class="text-gray-700">Your required account checks are complete. Finish your profile to continue.</p>
                <a href="{{ route('profile.complete') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white focus:outline-none focus:ring-2 focus:ring-purple-700 focus:ring-offset-2">Continue setup</a>
                @break
            @default
                <p class="text-gray-700">This account cannot continue until the date-of-birth information is reviewed. Contact support for help.</p>
        @endswitch

        <div class="flex flex-wrap gap-4 text-sm">
            <a href="{{ route('help.index') }}" class="font-semibold text-purple-800 underline">Contact support</a>
            <a href="{{ route('privacy') }}" class="font-semibold text-purple-800 underline">Privacy policy</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="font-semibold text-purple-800 underline">Log out</button>
            </form>
        </div>
    </main>
</x-auth-split-layout>
