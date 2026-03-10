<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center overflow-hidden">
            <div class="absolute top-0 left-0 w-40 h-40 bg-white/5 rounded-br-full"></div>
            <div class="absolute bottom-0 right-0 w-56 h-56 bg-white/5 rounded-tl-full"></div>

            <div class="relative mb-6 z-10">
                <div class="w-32 h-32 bg-white/15 rounded-3xl flex items-center justify-center shadow-2xl backdrop-blur-sm border border-white/20">
                    <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="w-24 h-24 object-contain drop-shadow-lg">
                </div>
            </div>

            <h1 class="text-2xl font-bold text-white mb-1 tracking-wide z-10">Concious Connections</h1>
            <div class="w-12 h-0.5 bg-white/40 rounded-full mx-auto mb-6 z-10"></div>

            <h2 class="text-3xl font-bold text-white mb-3 leading-tight z-10">Check your inbox</h2>
            <p class="text-white/75 text-base max-w-[200px] leading-relaxed z-10">We sent a link to your Gmail address</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

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
    @endif
</x-auth-split-layout>
