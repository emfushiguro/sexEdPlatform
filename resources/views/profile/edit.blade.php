<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(auth()->user()->isLearner() && ! auth()->user()->isParentRegistration())
                @include('auth.partials.dependent-page-help', ['step' => 'settings'])
            @endif
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            @if(app(\App\Services\Auth\AdultRecoveryCodeService::class)->eligible(auth()->user()))
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg"><h3 class="font-semibold text-gray-900">Account recovery</h3><p class="mt-2 text-sm text-gray-600">Create or replace your private recovery codes.</p><a class="mt-3 inline-block font-semibold text-purple-700 underline" href="{{ route('password.recovery-codes.settings') }}">Manage recovery codes</a></div>
            @endif
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
