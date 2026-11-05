<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Almost there</h2>
            <p class="mt-4 max-w-sm text-white/80">Use an email you can verify. You can resume the application after signing in.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-purple-900">Create applicant account</h1>
        <p class="mt-1 text-sm text-gray-600">We will send a normal email verification link before identity review.</p>
    </div>

    @if ($errors->any())
        <div id="applicant-account-error-summary" data-error-summary role="alert" tabindex="-1" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('instructor-application.register.account.store') }}" data-instructor-form @if ($errors->any()) aria-describedby="applicant-account-error-summary" @endif @submit="loading = true" x-data="{
        loading: false,
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        showPasswordConfirmation: false,
        checks() {
            return {
                length: this.password.length >= 8,
                lower: /[a-z]/.test(this.password),
                upper: /[A-Z]/.test(this.password),
                number: /\d/.test(this.password),
                symbol: /[^A-Za-z0-9]/.test(this.password),
            };
        },
        score() {
            return Object.values(this.checks()).filter(Boolean).length;
        },
        strengthLabel() {
            const score = this.score();
            if (score <= 2) return 'Weak';
            if (score <= 4) return 'Good';
            return 'Strong';
        },
    }">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-600">*</span></label>
                <input id="email" type="email" name="email" required autofocus autocomplete="username" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-email">
                @error('email')<p id="error-email" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-600">*</span></label>
                <div class="relative mt-1">
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" x-model="password" class="w-full rounded-xl border-gray-300 pr-11" aria-describedby="error-password">
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-purple-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-1" :aria-label="showPassword ? 'Hide password' : 'Show password'" :title="showPassword ? 'Hide password' : 'Show password'">
                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 3 18 18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587a2 2 0 0 0 2.829 2.829"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 5.09A9.953 9.953 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a9.963 9.963 0 0 1-4.293 5.067M6.228 6.228A9.957 9.957 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7 1.61 0 3.13-.38 4.478-1.055"/></svg>
                    </button>
                </div>
                <div x-show="password" x-cloak class="mt-2">
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full transition-all duration-300" :style="`width: ${password ? (score() / 5) * 100 : 0}%`" :class="score() <= 2 ? 'bg-red-500' : (score() <= 4 ? 'bg-amber-500' : 'bg-emerald-500')"></div>
                    </div>
                    <div class="mt-1.5 flex min-h-[18px] items-center justify-between gap-2 text-xs">
                        <span class="font-medium" :class="password ? (score() <= 2 ? 'text-red-600' : (score() <= 4 ? 'text-amber-600' : 'text-emerald-600')) : 'text-gray-500'" x-text="password ? `Strength: ${strengthLabel()}` : 'Strength: -'"></span>
                        <span class="text-right" :class="password ? (score() === 5 ? 'text-emerald-600' : 'text-gray-500') : 'text-gray-500'" x-text="password ? (score() === 5 ? 'All requirements met' : 'Use upper, lower, number, symbol') : 'Min 8 chars'"></span>
                    </div>
                </div>
                @error('password')<p id="error-password" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password <span class="text-red-600">*</span></label>
                <div class="relative mt-1">
                    <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" x-model="passwordConfirmation" class="w-full rounded-xl border-gray-300 pr-11">
                    <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 inline-flex items-center px-3 text-gray-400 hover:text-purple-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-1" :aria-label="showPasswordConfirmation ? 'Hide confirmation password' : 'Show confirmation password'" :title="showPasswordConfirmation ? 'Hide confirmation password' : 'Show confirmation password'">
                        <svg x-show="!showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <svg x-show="showPasswordConfirmation" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 3 18 18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.584 10.587a2 2 0 0 0 2.829 2.829"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 5.09A9.953 9.953 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a9.963 9.963 0 0 1-4.293 5.067M6.228 6.228A9.957 9.957 0 0 0 2.458 12c1.274 4.057 5.065 7 9.542 7 1.61 0 3.13-.38 4.478-1.055"/></svg>
                    </button>
                </div>
                <p class="mt-1 min-h-[18px] text-xs" :class="!passwordConfirmation ? 'text-gray-500' : (passwordConfirmation === password ? 'text-emerald-600' : 'text-red-600')" x-text="!passwordConfirmation ? 'Confirm your password.' : (passwordConfirmation === password ? 'Passwords match.' : 'Passwords do not match.')"></p>
            </div>
            <button type="submit" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white disabled:cursor-wait disabled:opacity-50">
                <span x-show="!loading">Create applicant account</span>
                <span x-show="loading" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Creating account…
                </span>
            </button>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
            <a class="block text-center text-sm text-purple-700" href="{{ route('instructor-application.register.personal') }}">Back to personal information</a>
        </div>
    </form>
</x-auth-split-layout>
