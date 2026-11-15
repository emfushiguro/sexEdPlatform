<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold leading-tight text-white">Finish your account</h2>
            <p class="max-w-xs text-lg text-white/80">Choose your own password before submitting identity evidence.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-purple-900">Activate {{ $child->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">The Guardian-Dependent relationship is approved. Set your private sign-in details.</p>
    </div>

    @include('auth.partials.dependent-page-help', ['step' => 'activation'])
    @if(!empty($expiresAt))<x-activation-expiry :expires-at="$expiresAt" class="mb-4 text-sm text-gray-600" />@endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4" role="alert">
            <ul class="list-inside list-disc text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('minor.activation.store') }}" class="space-y-5" x-data="{ consentMethod: @js(old('consent_method', 'self')), password: '', confirmation: '', showPassword: false, showConfirmation: false, get score() { return [this.password.length >= 8, /[a-z]/.test(this.password), /[A-Z]/.test(this.password), /[0-9]/.test(this.password), /[^A-Za-z0-9]/.test(this.password)].filter(Boolean).length; } }">
        @csrf
        <input type="hidden" name="code" value="{{ old('code', $code) }}">

        <div>
            <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
            <input id="username" name="username" type="text" required autocomplete="username" value="{{ old('username', $profile?->username) }}" pattern="[a-z0-9_-]+" minlength="3" maxlength="30" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <p class="mb-2 text-sm text-purple-900">Pause here if you would like privacy before choosing your password.</p>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Your password</label>
            <div class="relative">
                <input id="password" name="password" type="password" :type="showPassword ? 'text' : 'password'" x-model="password" required minlength="8" autocomplete="new-password" aria-describedby="password-help" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 pr-20 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
                <button type="button" @click="showPassword = !showPassword" :aria-pressed="showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" aria-controls="password" class="absolute right-2 top-0 min-h-11 px-2 text-gray-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path x-show="showPassword" stroke-width="2" d="M3 3l18 18"/></svg></button>
            </div>
            <p id="password-help" class="mt-2 text-xs text-gray-600">Use at least 8 characters, uppercase and lowercase letters, a number, and a symbol.</p>
            <div x-show="password.length" x-cloak class="mt-2">
                <div role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="5" :aria-valuenow="score" class="h-2 overflow-hidden rounded-full bg-gray-200"><div class="h-full transition-all" :class="score <= 2 ? 'bg-red-500' : (score <= 4 ? 'bg-amber-500' : 'bg-emerald-500')" :style="'width: ' + score * 20 + '%' "></div></div>
                <p class="mt-1 text-xs text-gray-700" aria-live="polite" x-text="score === 5 ? 'Strength: Strong ? all requirements met' : (score <= 2 ? 'Strength: Weak' : 'Strength: Fair') + ' ? use upper, lower, number, symbol'"></p>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm your password</label>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation" type="password" :type="showConfirmation ? 'text' : 'password'" x-model="confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 pr-20 text-sm text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-500">
                <button type="button" @click="showConfirmation = !showConfirmation" :aria-pressed="showConfirmation" :aria-label="showConfirmation ? 'Hide password' : 'Show password'" aria-controls="password_confirmation" class="absolute right-2 top-0 min-h-11 px-2 text-gray-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><path x-show="showConfirmation" stroke-width="2" d="M3 3l18 18"/></svg></button>
            </div>
            <p x-show="confirmation.length" x-cloak class="mt-2 text-xs text-gray-700" aria-live="polite" x-text="confirmation === password ? 'Passwords match.' : 'Passwords do not match yet.'"></p>
        </div>

        @if(! $child->requiresGuardian())
        @if($adultAuthority)
        <fieldset class="space-y-3 rounded-xl border border-purple-200 bg-purple-50 p-4 text-sm text-purple-900">
            <legend class="font-semibold">How is registration authorized?</legend>
            <label class="flex gap-2"><input type="radio" name="consent_method" value="self" x-model="consentMethod" required><span>I am authorizing my account myself.</span></label>
            <label class="flex gap-2"><input type="radio" name="consent_method" value="representative" x-model="consentMethod"><span>My verified representative has authorized registration for me.</span></label>
            <p x-show="consentMethod === 'representative'" x-cloak>The representative's authority and consent were reviewed for this registration. You still choose your own password and control optional Guardian access.</p>
        </fieldset>
        @else
        <input type="hidden" name="consent_method" value="self">
        @endif
        <label x-show="consentMethod === 'self'" class="flex items-start gap-2 rounded-xl border border-purple-200 bg-purple-50 p-3 text-sm text-purple-900">
            <input type="checkbox" name="adult_consent" value="1" :required="consentMethod === 'self'" :disabled="consentMethod !== 'self'" class="mt-0.5 rounded border-purple-300">
            <span>I choose to create this account and link it to this Guardian. I agree to the <a class="underline" href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a> and processing of my identity and learning records under the <a class="underline" href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Notice</a>. I control optional Guardian access to my learning and support information.</span>
        </label>
        @endif
        <label @if(! $child->requiresGuardian()) x-show="consentMethod === 'self'" @endif class="flex items-start gap-2 rounded-xl border border-purple-200 bg-purple-50 p-3 text-sm text-purple-900">
            <input type="checkbox" name="privacy_notice" value="1" @if(! $child->requiresGuardian()) :required="consentMethod === 'self'" :disabled="consentMethod !== 'self'" @else required @endif class="mt-0.5 rounded border-purple-300">
            <span>I have read the <a class="underline" href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Notice</a> and understand how my information is used. My password is private and I should not share it.</span>
        </label>

        <button type="submit" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl px-8 py-3.5 font-semibold text-white shadow-md transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Activate account</button>
    </form>
</x-auth-split-layout>
