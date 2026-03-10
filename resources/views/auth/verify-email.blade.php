<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Check your inbox</h2>
            <p class="text-white/80 text-lg max-w-xs">We sent a link to your Gmail address</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Personal Info', 'active' => false, 'done' => true],
        ['label' => 'Account Info',  'active' => false, 'done' => true],
        ['label' => 'Verify Email',  'active' => true,  'done' => false],
        ['label' => 'Profile',       'active' => false, 'done' => false],
    ]" />

    @if($showSuccess)
        {{-- SUCCESS STATE: Email verified, countdown to profile --}}
        <div
            x-data="{
                countdown: 3,
                progress: 100,
                redirectUrl: '{{ route('profile.complete') }}',
                init() {
                    const interval = setInterval(() => {
                        this.countdown--;
                        this.progress = (this.countdown / 3) * 100;
                        if (this.countdown <= 0) {
                            clearInterval(interval);
                            window.location.href = this.redirectUrl;
                        }
                    }, 1000);
                }
            }"
            x-init="init()"
            class="text-center py-4"
        >
            {{-- Success icon --}}
            <div class="mx-auto mb-5 w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h3 class="text-2xl font-bold text-gray-800 mb-2">Email Verified!</h3>
            <p class="text-gray-500 text-sm mb-6 max-w-xs mx-auto">
                Your email has been confirmed. You'll be redirected to complete your profile in
                <strong x-text="countdown" class="text-brand-purple-primary"></strong> second<span x-show="countdown !== 1">s</span>…
            </p>

            {{-- Progress bar --}}
            <div class="w-full bg-gray-100 rounded-full h-2 mb-5">
                <div
                    class="h-2 rounded-full transition-all duration-1000 ease-linear"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                    :style="'width: ' + progress + '%'"
                ></div>
            </div>

            <a
                href="{{ route('profile.complete') }}"
                class="inline-flex items-center gap-2 text-sm text-brand-purple-primary font-medium hover:underline"
            >
                Continue now →
            </a>
        </div>
    @else
        {{-- WAITING STATE: Normal verify-email prompt --}}
        <div class="mb-4 text-sm text-gray-600">
            {{ __("Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.") }}
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
                    <button type="submit"
                            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
                        {{ __('Resend Verification Email') }}
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    @endif
</x-auth-split-layout>
