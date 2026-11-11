<x-auth-split-layout :showTabs="false" logo="/media/Logo.png">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="h-36 w-auto mb-6 drop-shadow-lg">
            <h2 class="text-4xl font-extrabold tracking-tight">Admin Authentication</h2>
            <p class="mt-3 text-white/80">A second step protects platform operations.</p>
        </div>
    </x-slot>

    <div
        x-data="{
            digits: @js(old('digits', array_fill(0, 6, ''))),
            expiresAt: {{ $codeExpiresAt * 1000 }},
            resendAt: {{ $resendAvailableAt * 1000 }},
            now: Date.now(),
            loading: false,
            resending: false,
            start() { this.tick = setInterval(() => this.now = Date.now(), 1000) },
            remaining(target) { return Math.max(0, Math.ceil((target - this.now) / 1000)) },
            clock(target) { const value = this.remaining(target); return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}` },
            inputs() { return [...this.$root.querySelectorAll('[data-otp-digit]')] },
            input(index, event) {
                event.target.value = event.target.value.replace(/\D/g, '').slice(-1)
                this.digits[index] = event.target.value
                if (event.target.value && index < 5) this.inputs()[index + 1].focus()
            },
            back(index, event) {
                if (!event.target.value && index > 0) this.inputs()[index - 1].focus()
            },
            paste(event) {
                const value = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
                value.split('').forEach((digit, index) => { this.digits[index] = digit; this.inputs()[index].value = digit })
                this.inputs()[Math.min(value.length, 6) - 1]?.focus()
            }
        }"
        x-init="start()"
    >
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-purple-900">Two-Step Verification</h1>
            <p class="mt-2 text-sm text-gray-600">Enter the code sent to <strong>{{ $maskedEmail }}</strong>.</p>
        </div>

        @if($deliveryFailed)
            <div role="alert" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                We could not deliver the current code. Request another code when resend becomes available.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.mfa.verify') }}" @submit="loading = true" class="space-y-5">
            @csrf
            <fieldset>
                <legend class="block text-sm font-semibold text-gray-700 mb-3">Six-digit verification code</legend>
                <div data-otp-row class="flex flex-nowrap justify-center gap-1 sm:gap-2" @paste.prevent="paste($event)">
                    @foreach(range(0, 5) as $index)
                        <input
                            data-otp-digit
                            name="digits[]"
                            value="{{ old('digits.'.$index) }}"
                            x-model="digits[{{ $index }}]"
                            @input="input({{ $index }}, $event)"
                            @keydown.backspace="back({{ $index }}, $event)"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="1"
                            autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                            aria-label="Digit {{ $index + 1 }} of 6"
                            class="h-14 w-10 shrink-0 rounded-xl border border-gray-200 bg-gray-50 text-center text-xl font-bold text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-brand-purple-primary sm:w-12"
                            {{ $index === 0 ? 'autofocus' : '' }}
                        >
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </fieldset>

            <p class="text-center text-sm text-gray-500" aria-live="polite">
                Code expires in <strong x-text="clock(expiresAt)"></strong>
            </p>

            <button
                type="submit"
                :disabled="loading || remaining(expiresAt) === 0"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                class="w-full rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-md disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span x-text="loading ? 'Verifying...' : 'Verify and Continue'"></span>
            </button>
        </form>

        <form method="POST" action="{{ route('admin.mfa.resend') }}" @submit="resending = true" class="mt-4 text-center">
            @csrf
            <button type="submit" :disabled="resending || remaining(resendAt) > 0" class="text-sm font-semibold text-purple-700 disabled:text-gray-400">
                <span x-show="remaining(resendAt) > 0">Resend available in <span x-text="clock(resendAt)"></span></span>
                <span x-show="remaining(resendAt) === 0" x-text="resending ? 'Sending...' : 'Resend code'"></span>
            </button>
            <x-input-error :messages="$errors->get('resend')" class="mt-2" />
        </form>

        <form method="POST" action="{{ route('admin.mfa.cancel') }}" class="mt-3 text-center">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Cancel and return to login</button>
        </form>
    </div>
</x-auth-split-layout>
