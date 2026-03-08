<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            {{-- Small logo top-left --}}
            <div class="absolute top-8 left-8">
                <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="h-10 w-auto opacity-80">
            </div>
            {{-- Icon bubble --}}
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-8 shadow-lg">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            {{-- Headline --}}
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Check your inbox</h2>
            {{-- Sub-text --}}
            <p class="text-white/80 text-lg max-w-xs">We sent a verification link to your email address</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

    <div class="mb-4 text-sm text-gray-600">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-auth-split-layout>
