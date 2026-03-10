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

            <h2 class="text-3xl font-bold text-white mb-3 leading-tight z-10">Your credentials</h2>
            <p class="text-white/75 text-base max-w-[200px] leading-relaxed z-10">Secure your parent account</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Personal Info', 'active' => false, 'done' => true],
        ['label' => 'Account Info', 'active' => true, 'done' => false],
        ['label' => 'Verify Email', 'active' => false, 'done' => false],
        ['label' => 'Profile', 'active' => false, 'done' => false],
    ]" />

    <!-- Header -->
    <div class="mb-5">
        <h2 class="text-2xl font-bold text-purple-900">Account Information</h2>
        <p class="mt-1 text-sm text-gray-500">Step 2 of 2 — Set up your login credentials</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-5 rounded-lg">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('parent.register.account.store') }}"
          x-data="{ showPassword: false, showConfirm: false }">
        @csrf

        <div class="space-y-4">

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address <span class="text-red-500">*</span>
                    <span class="text-xs text-blue-600 font-normal ml-1">(Gmail only)</span>
                </label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                       placeholder="parent@gmail.com"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                <p class="mt-1 text-xs text-gray-500">Must be a Gmail address. A verification link will be sent here.</p>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required
                           placeholder="••••••••"
                           class="w-full px-3 py-2 pr-10 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Min. 8 characters, mixed case, numbers & symbols</p>
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" :type="showConfirm ? 'text' : 'password'" required
                           placeholder="••••••••"
                           class="w-full px-3 py-2 pr-10 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Terms -->
            <div>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" required
                           class="mt-0.5 h-4 w-4 text-brand-purple-primary border-gray-300 rounded focus:ring-brand-purple-primary">
                    <span class="text-sm text-gray-600">
                        I agree to the
                        <a href="{{ route('terms') }}" target="_blank" class="text-brand-purple-primary hover:underline font-medium">Terms of Service</a>
                        and
                        <a href="{{ route('privacy') }}" target="_blank" class="text-brand-purple-primary hover:underline font-medium">Privacy Policy</a>
                    </span>
                </label>
            </div>

            <!-- Buttons -->
            <div class="pt-2 space-y-3">
                <button type="submit"
                        class="w-full bg-brand-purple-primary text-white py-3 px-6 rounded-xl font-semibold text-sm hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    Create Parent Account →
                </button>
                <a href="{{ route('parent.register') }}"
                   class="w-full block text-center py-2.5 px-6 rounded-xl text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition">
                    ← Back to Personal Info
                </a>
            </div>

        </div>
    </form>

</x-auth-split-layout>
