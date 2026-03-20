<x-auth-split-layout
    :showTabs="false"
    logo="/media/Logo.png"
>
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center text-white">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-36 w-auto mx-auto mb-3 drop-shadow-lg">
            </div>
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 border border-white/20 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <p class="text-xs uppercase tracking-[0.28em] text-purple-100/80">Admin Authentication</p>
            <h2 class="mt-3 text-4xl font-extrabold tracking-tight">Control Room Login</h2>
            <p class="mt-3 max-w-sm text-sm text-purple-100/85">
                Operational access for billing, users, and platform health. Every login is tracked and audited.
            </p>
        </div>
    </x-slot>

    <div class="mb-8">
        <h1 class="text-4xl font-bold text-purple-900">Administrator Command Center</h1>
        <p class="mt-2 text-sm text-gray-600">Secure access for platform operators</p>
    </div>

    <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4" x-data="{ showPassword: false, loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Admin Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="name@conciousconnections.com"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Admin Password</label>
            <div class="relative">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 pr-12 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent"
                />
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-gray-600 transition-colors"
                    aria-label="Toggle password visibility"
                >
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button
            type="submit"
            :disabled="loading"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
            class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
        >
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="4" class="opacity-80"></path>
            </svg>
            <span x-text="loading ? 'Checking credentials...' : 'Enter Secure Panel'">Enter Secure Panel</span>
        </button>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            Authorized personnel only. Suspicious sign-ins are rate-limited and logged.
        </div>
    </form>
</x-auth-split-layout>
