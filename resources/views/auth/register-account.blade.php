<x-auth-split-layout
    :showTabs="true"
    activeTab="register"
    :loginRoute="route('learner.login')"
    :registerRoute="route('register')"
>
    <x-slot name="panel">
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            {{-- Small logo top-left --}}
            <div class="absolute top-8 left-8">
                <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="h-10 w-auto opacity-80">
            </div>
            {{-- Icon bubble --}}
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mb-8 shadow-lg">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            {{-- Headline --}}
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Almost there!</h2>
            {{-- Sub-text --}}
            <p class="text-white/80 text-lg max-w-xs">Create your credentials to protect your account</p>
        </div>
    </x-slot>

    <x-wizard-stepper />

    <!-- Heading -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-purple-900">Account Information</h2>
        <p class="mt-1 text-sm text-gray-500">Step 2 of 2 — Set up your login credentials</p>
    </div>

    <!-- Account Form -->
                <form method="POST" action="{{ route('register.account') }}" x-data="{
                    showPassword: false,
                    showConfirmPassword: false,
                    loading: false,
                }" @submit="loading = true">
                    @csrf

                    <div class="space-y-4">

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-gray-400 font-normal">(Gmail only)</span></label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="yourname@gmail.com"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                            />
                            <p class="mt-1 text-xs text-gray-500">We'll send a verification link to this email</p>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                                >
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Min 8 chars with uppercase, lowercase, numbers & symbols</p>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <input
                                    id="password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                                />
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                                >
                                    <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="loading"
                                class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <span x-show="!loading">Create Account</span>
                                <span x-show="loading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Creating account...</span>
                                </span>
                            </button>

                            <div class="mt-4 text-center">
                                <a href="{{ route('register') }}" class="text-sm text-gray-500 hover:text-brand-purple-primary transition-colors">
                                    ← Back to Personal Information
                                </a>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Links -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                            <a href="#" class="hover:text-brand-purple-primary transition-colors">Help</a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('terms') }}" class="hover:text-brand-purple-primary transition-colors">Terms</a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('privacy') }}" class="hover:text-brand-purple-primary transition-colors">Privacy</a>
                        </div>
                    </div>
                </form>

</x-auth-split-layout>
